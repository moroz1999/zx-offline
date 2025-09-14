<?php
declare(strict_types=1);

namespace App\Archive;

use RuntimeException;

/**
 * Validates archive entries to mitigate Zip Slip / path traversal.
 * This is a best-effort guard; extractors must still use safe APIs.
 */
final class PathTraversalGuard
{
    /**
     * @param list<string> $entryNames Raw entry names from the archive
     */
    public function assertSafe(array $entryNames): void
    {
        foreach ($entryNames as $entryName) {
            if ($entryName === '' || str_contains($entryName, "\0")) {
                throw new RuntimeException('Invalid archive entry name.');
            }

            // Normalize separators to platform separator for checks
            $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $entryName);

            if (str_starts_with($normalized, DIRECTORY_SEPARATOR)) {
                throw new RuntimeException("Archive entry attempts absolute path: $entryName");
            }

            if (preg_match('#(^|'.preg_quote(DIRECTORY_SEPARATOR, '#').')\.\.('.preg_quote(DIRECTORY_SEPARATOR, '#').'|$)#', $normalized) === 1) {
                throw new RuntimeException("Archive entry attempts path traversal: $entryName");
            }
        }
    }
}
