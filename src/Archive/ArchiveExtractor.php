<?php
declare(strict_types=1);

namespace App\Archive;

/**
 * Strategy interface for archive extractors.
 */
interface ArchiveExtractor
{
    /**
     * Whether this extractor supports the given (lowercased) relative path.
     */
    public function supports(string $lowerRelativePath): bool;

    /**
     * List raw entry names inside the archive for validation.
     * Entry names must be returned exactly as stored inside the archive.
     *
     * @return list<string>
     */
    public function listEntries(string $absoluteArchivePath): array;

    /**
     * Extracts archive to a destination directory.
     */
    public function extract(string $absoluteArchivePath, string $destinationDir): void;
}
