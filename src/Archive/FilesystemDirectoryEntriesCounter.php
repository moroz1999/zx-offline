<?php
declare(strict_types=1);


namespace App\Archive;


use DirectoryIterator;
use RuntimeException;
use UnexpectedValueException;

/**
 * Native implementation using DirectoryIterator.
 * Safe, no recursion, ignores "." and "..".
 */
final class FilesystemDirectoryEntriesCounter implements DirectoryEntriesCounter
{
    public function count(string $directoryPath): int
    {
        if (!is_dir($directoryPath)) {
            return 0;
        }

        $count = 0;
        try {
            $iterator = new DirectoryIterator($directoryPath);
            foreach ($iterator as $info) {
                if ($info->isDot()) {
                    continue;
                }
                $count++;
            }
        } catch (UnexpectedValueException|RuntimeException $e) {
            // If directory becomes unreadable between checks, treat as empty to avoid blocking resolution.
            return 0;
        }

        return $count;
    }
}
