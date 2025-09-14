<?php
declare(strict_types=1);


namespace App\Archive;

/**
 * Counts entries within a directory path.
 * Implementations should return 0 if the directory does not exist.
 */
interface DirectoryEntriesCounter
{
    /**
     * @param non-empty-string $directoryPath
     * @return int Number of entries in the directory (files and/or subdirectories), excluding "." and "..".
     */
    public function count(string $directoryPath): int;
}
