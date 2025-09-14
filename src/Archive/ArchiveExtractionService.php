<?php
declare(strict_types=1);

namespace App\Archive;

use RuntimeException;

/**
 * Facade for extracting supported archives and removing them after a successful extraction.
 * - Picks proper extractor by extension
 * - Creates destination directory
 * - Guards against path traversal
 * - Flattens single nested dir
 * - Deletes original archive
 */
final readonly class ArchiveExtractionService
{
    /** @var ArchiveExtractor[] */
    private array $extractors;

    public function __construct(
        private PathTraversalGuard $pathTraversalGuard,
        private DirectoryFlattener $directoryFlattener,
        ZipArchiveExtractor $zipExtractor,
        PharTarExtractor $tarExtractor
    ) {
        $this->extractors = [$zipExtractor, $tarExtractor];
    }

    /**
     * Extracts an archive into a directory with the same name (no extension) and deletes the archive file.
     *
     * @param string $archiveBasePath Absolute base path
     * @param string $relativePath    Relative archive path (e.g. "games/file.zip")
     * @return string Relative path of extracted directory
     */
    public function extractAndRemove(string $archiveBasePath, string $relativePath): string
    {
        $absoluteArchivePath = $archiveBasePath . DIRECTORY_SEPARATOR . $relativePath;

        if (!is_file($absoluteArchivePath)) {
            throw new RuntimeException('Cannot extract missing file: ' . $absoluteArchivePath);
        }

        $extractor = $this->pickExtractor($relativePath);
        if ($extractor === null) {
            // Unsupported format: return as-is without extraction
            return $relativePath;
        }

        $relativeDirectoryPath = $this->stripExtensionChain($relativePath);
        $absoluteDirectoryPath = $archiveBasePath . DIRECTORY_SEPARATOR . $relativeDirectoryPath;

        $this->ensureDir($absoluteDirectoryPath);

        // Pre-flight: check entries for path traversal (best-effort)
        $this->pathTraversalGuard->assertSafe($extractor->listEntries($absoluteArchivePath));

        // Extract
        $extractor->extract($absoluteArchivePath, $absoluteDirectoryPath);

        // Post-process: flatten single nested directory if any
        $this->directoryFlattener->flattenIfSingleSubdirectory($absoluteDirectoryPath);

        // Remove original archive
        if (!unlink($absoluteArchivePath)) {
            throw new RuntimeException("Failed to delete archive after extraction: $absoluteArchivePath");
        }

        return $relativeDirectoryPath;
    }

    private function pickExtractor(string $relativePath): ?ArchiveExtractor
    {
        $lower = strtolower($relativePath);

        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($lower)) {
                return $extractor;
            }
        }

        return null;
    }

    /**
     * Strips common compressed archive extensions chains:
     * - file.zip -> file
     * - file.tar -> file
     * - file.tar.gz / file.tgz -> file
     */
    private function stripExtensionChain(string $relativePath): string
    {
        $dir = pathinfo($relativePath, PATHINFO_DIRNAME);
        $filename = pathinfo($relativePath, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        // Handle .tgz -> .tar.gz semantics
        if ($ext === 'tgz') {
            $filename = pathinfo($filename, PATHINFO_FILENAME); // drop implicit ".tar"
        } elseif ($ext === 'gz' || $ext === 'bz2' || $ext === 'xz' || $ext === 'zst') {
            // file.tar.gz -> remove .gz and .tar if present
            $maybeTar = pathinfo($filename, PATHINFO_EXTENSION);
            if (strtolower($maybeTar) === 'tar') {
                $filename = pathinfo($filename, PATHINFO_FILENAME);
            }
        } elseif ($ext === 'tar' || $ext === 'zip') {
            // single-strip already done by PATHINFO_FILENAME
        }

        return ($dir !== '.' ? $dir . DIRECTORY_SEPARATOR : '') . $filename;
    }

    private function ensureDir(string $absoluteDirectoryPath): void
    {
        if (!is_dir($absoluteDirectoryPath) && !mkdir($absoluteDirectoryPath, 0777, true) && !is_dir($absoluteDirectoryPath)) {
            throw new RuntimeException("Failed to create directory: $absoluteDirectoryPath");
        }
    }
}
