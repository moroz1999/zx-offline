<?php
declare(strict_types=1);

namespace App\Archive;

use App\ZxProds\ZxProdRecord;
use App\ZxReleases\ZxReleaseRecord;

final readonly class FileDirectoryResolver
{
    private const MAX_ENTRIES_PER_BUCKET = 400;

    public function __construct(
        private HardwarePlatformResolver $hardwarePlatformResolver,
        private NameSanitizer            $nameSanitizer,
        private FileArchiveService       $fileArchiveService,
        private DirectoryEntriesCounter  $directoryEntriesCounter, // inject FS counter for SRP/testability
    )
    {
    }

    /**
     * @return string[]
     */
    public function resolve(ZxProdRecord $prod, ZxReleaseRecord $release, string $baseName): array
    {
        $platforms = $this->hardwarePlatformResolver->resolvePlatformFolders($release);
        $category = $prod->categoryTitle ?: 'Misc';

        $titleSanitized = $this->nameSanitizer->sanitizeWithArticleHandling($prod->title);
        $firstChar = mb_strtoupper(mb_substr($titleSanitized, 0, 1));

        if (preg_match('/[A-Z]/u', $firstChar)) {
            $letter = $firstChar;
        } elseif (preg_match('/[0-9]/u', $firstChar)) {
            $letter = '0-9';
        } else {
            $letter = 'other';
        }

        return array_map(function (string $platform) use ($category, $letter, $baseName): string {
            $bucket = $this->resolveBucketLetterFolder($platform, $category, $letter, $baseName);

            return $platform
                . DIRECTORY_SEPARATOR . $category
                . DIRECTORY_SEPARATOR . $bucket
                . DIRECTORY_SEPARATOR . $baseName
                . DIRECTORY_SEPARATOR;
        }, $platforms);
    }

    /**
     * Picks a bucket folder like "A", "A2", "A3"... ensuring each has < MAX_ENTRIES_PER_BUCKET.
     */
    private function resolveBucketLetterFolder(string $platform, string $category, string $letter, string $baseName): string
    {
        $archiveBasePath = $this->fileArchiveService->getArchiveBasePath();
        $basePath = $archiveBasePath . $platform . DIRECTORY_SEPARATOR . $category;

        for ($suffixIndex = 1; $suffixIndex < PHP_INT_MAX; $suffixIndex++) {
            $suffix = $suffixIndex === 1 ? '' : (string)$suffixIndex;
            $candidateFolder = $letter . $suffix;

            $candidatePath = $basePath . DIRECTORY_SEPARATOR . $candidateFolder;
            // if there is already prod's folder in letter bucket, then use it.
            $candidateFullPath = $basePath . DIRECTORY_SEPARATOR . $candidateFolder . DIRECTORY_SEPARATOR . $baseName;
            if (is_dir($candidateFullPath)){
                return $candidatePath;
            }

            // Count directory entries; treat non-existing folder as 0.
            $entries = $this->directoryEntriesCounter->count($candidatePath);

            if ($entries < self::MAX_ENTRIES_PER_BUCKET) {
                return $candidateFolder;
            }
        }

        return $letter;
    }
}
