<?php
declare(strict_types=1);

namespace App\Archive;

use RuntimeException;
use ZipArchive;

/**
 * ZIP extractor using ZipArchive with Zip Slip guarding.
 */
final class ZipArchiveExtractor implements ArchiveExtractor
{
    public function supports(string $lowerRelativePath): bool
    {
        return str_ends_with($lowerRelativePath, '.zip');
    }

    public function listEntries(string $absoluteArchivePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($absoluteArchivePath) !== true) {
            throw new RuntimeException("Failed to open zip: $absoluteArchivePath");
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'] ?? '';
            if ($name !== '') {
                $entries[] = $name;
            }
        }

        $zip->close();
        return $entries;
    }

    public function extract(string $absoluteArchivePath, string $destinationDir): void
    {
        $zip = new ZipArchive();
        if ($zip->open($absoluteArchivePath) !== true) {
            throw new RuntimeException("Failed to open zip: $absoluteArchivePath");
        }

        if (!$zip->extractTo($destinationDir)) {
            $zip->close();
            throw new RuntimeException("Failed to extract zip to: $destinationDir");
        }

        $zip->close();
    }
}
