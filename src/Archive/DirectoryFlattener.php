<?php
declare(strict_types=1);

namespace App\Archive;

use FilesystemIterator;
use RuntimeException;

/**
 * Flattens structure if the extracted directory contains a single subdirectory.
 */
final class DirectoryFlattener
{
    /**
     * If the directory contains exactly one child which is a directory,
     * move its contents up and remove the child directory.
     */
    public function flattenIfSingleSubdirectory(string $directory): void
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
