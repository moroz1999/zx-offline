<?php
declare(strict_types=1);

namespace App\Archive;

use PharData;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * TAR and TAR.GZ extractor using PharData.
 * Supports: .tar, .tar.gz, .tgz
 *
 * Note: PharData does not open .tar.gz directly for read; we must decompress to .tar first.
 */
final class PharTarExtractor implements ArchiveExtractor
{
    public function supports(string $lowerRelativePath): bool
    {
        return str_ends_with($lowerRelativePath, '.tar')
            || str_ends_with($lowerRelativePath, '.tar.gz')
            || str_ends_with($lowerRelativePath, '.tgz');
    }

    public function listEntries(string $absoluteArchivePath): array
    {
        [$tarPath, $cleanupTar] = $this->ensureTar($absoluteArchivePath);

        try {
            $phar = new PharData($tarPath);
        } catch (\Throwable $e) {
            if ($cleanupTar && is_file($tarPath)) {
                @unlink($tarPath);
            }
            throw new RuntimeException("Failed to open tar: $tarPath");
        }

        $entries = [];
        /** @var RecursiveIteratorIterator<\PharData> $it */
        $it = new RecursiveIteratorIterator($phar);
        foreach ($it as $file) {
            $entries[] = $file->getPathName(); // returns archive-internal path style
        }

        if ($cleanupTar && is_file($tarPath)) {
            @unlink($tarPath);
        }

        return $entries;
    }

    public function extract(string $absoluteArchivePath, string $destinationDir): void
    {
        [$tarPath, $cleanupTar] = $this->ensureTar($absoluteArchivePath);

        try {
            $phar = new PharData($tarPath);
            $phar->extractTo($destinationDir, null, true);
        } catch (\Throwable $e) {
            if ($cleanupTar && is_file($tarPath)) {
                @unlink($tarPath);
            }
            throw new RuntimeException("Failed to extract tar to: " . $destinationDir);
        }

        if ($cleanupTar && is_file($tarPath)) {
            unlink($tarPath);
        }
    }

    /**
     * Ensures we have a plain .tar file to work with.
     *
     * @return array{string,bool} [$tarPath, $cleanupTar]
     */
    private function ensureTar(string $absoluteArchivePath): array
    {
        $lower = strtolower($absoluteArchivePath);

        // .tgz -> pretend ".tar.gz"
        if (str_ends_with($lower, '.tgz') || str_ends_with($lower, '.tar.gz')) {
            // Decompress .gz to .tar (PharData::decompress() creates sibling file without .gz)
            try {
                $gzPhar = new PharData($absoluteArchivePath);
                $tarPath = preg_replace('/\.tgz$/i', '.tar', $absoluteArchivePath);
                if ($tarPath === $absoluteArchivePath) {
                    $tarPath = preg_replace('/\.tar\.gz$/i', '.tar', $absoluteArchivePath);
                }
                if (!is_string($tarPath)) {
                    throw new RuntimeException('Failed to determine .tar path for: ' . $absoluteArchivePath);
                }

                if (!is_file($tarPath)) {
                    $gzPhar->decompress(); // produces $tarPath
                }

                return [$tarPath, true];
            } catch (\Throwable $e) {
                throw new RuntimeException('Failed to decompress tar.gz: ' . $absoluteArchivePath);
            }
        }

        if (str_ends_with($lower, '.tar')) {
            return [$absoluteArchivePath, false];
        }

        throw new RuntimeException('Unsupported tar format: ' . $absoluteArchivePath);
    }
}
