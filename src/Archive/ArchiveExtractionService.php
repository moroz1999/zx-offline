<?php
declare(strict_types=1);

namespace App\Archive;

use RuntimeException;
use ZipArchive;
use FilesystemIterator;

final class ArchiveExtractionService
{
    public function __construct()
    {
    }

    /**
     * Extracts ZIP archive into a directory with the same name without extension.
     * Deletes the archive file after successful extraction.
     *
     * @param string $archiveBasePath Absolute base path
     * @param string $relativePath Relative archive path (e.g. "games/file.zip")
     * @return string Relative path of extracted directory
     */
    public function extractAndRemove(string $archiveBasePath, string $relativePath): string
    {
        $absoluteArchivePath = $archiveBasePath . DIRECTORY_SEPARATOR . $relativePath;

        if (!is_file($absoluteArchivePath)) {
            throw new RuntimeException('Cannot extract missing file: ' . $absoluteArchivePath);
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            return $relativePath; // skip unsupported formats
        }

        $relativeDirectoryPath = $this->stripExtension($relativePath);
        $absoluteDirectoryPath = $archiveBasePath . DIRECTORY_SEPARATOR . $relativeDirectoryPath;
        if (!is_dir($absoluteDirectoryPath) && !mkdir($absoluteDirectoryPath, 0777, true) && !is_dir($absoluteDirectoryPath)) {
            throw new RuntimeException("Failed to create directory: $absoluteDirectoryPath");
        }

        $zip = new ZipArchive();
        if ($zip->open($absoluteArchivePath) !== true) {
            throw new RuntimeException("Failed to open zip: $absoluteArchivePath");
        }

        // Basic Zip Slip protection
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entryName = $stat['name'] ?? '';
            if ($entryName === '' || str_contains($entryName, "\0")) {
                $zip->close();
                throw new RuntimeException("Invalid zip entry name in: $absoluteArchivePath");
            }
            $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $entryName);
            if (str_contains($normalized, '..' . DIRECTORY_SEPARATOR) || str_starts_with($normalized, DIRECTORY_SEPARATOR)) {
                $zip->close();
                throw new RuntimeException("Zip entry attempts path traversal: $entryName");
            }
        }

        if (!$zip->extractTo($absoluteDirectoryPath)) {
            $zip->close();
            throw new RuntimeException("Failed to extract zip to: $absoluteDirectoryPath");
        }

        $zip->close();

        // Normalize if archive contained only one top-level directory
        $this->flattenIfSingleSubdirectory($absoluteDirectoryPath);

        if (!unlink($absoluteArchivePath)) {
            throw new RuntimeException("Failed to delete archive after extraction: $absoluteArchivePath");
        }

        return $relativeDirectoryPath;
    }

    private function stripExtension(string $relativePath): string
    {
        $info = pathinfo($relativePath);
        return ($info['dirname'] !== '.' ? $info['dirname'] . DIRECTORY_SEPARATOR : '')
            . $info['filename'];
    }

    /**
     * If the extracted directory contains only a single subdirectory,
     * move its contents one level up and remove the redundant subdirectory.
     */
    private function flattenIfSingleSubdirectory(string $directory): void
    {
        $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        if (!$iterator->valid()) {
            return; // empty directory
        }

        $firstEntry = $iterator->current();
        $iterator->next();
        if ($iterator->valid()) {
            return; // more than one entry -> leave as is
        }

        if ($firstEntry && $firstEntry->isDir()) {
            $nestedDirectory = $firstEntry->getPathname();
            $nestedIterator = new FilesystemIterator($nestedDirectory, FilesystemIterator::SKIP_DOTS);

            foreach ($nestedIterator as $entry) {
                $targetPath = $directory . DIRECTORY_SEPARATOR . $entry->getBasename();
                if (!rename($entry->getPathname(), $targetPath)) {
                    throw new RuntimeException("Failed to move {$entry->getPathname()} -> $targetPath");
                }
            }

            if (!rmdir($nestedDirectory)) {
                throw new RuntimeException("Failed to remove redundant directory: $nestedDirectory");
            }
        }
    }

}
