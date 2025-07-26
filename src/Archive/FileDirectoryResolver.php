<?php
declare(strict_types=1);

namespace App\Archive;

use App\ZxProds\ZxProdRecord;
use App\ZxReleases\ZxReleaseRecord;

final readonly class FileDirectoryResolver
{
    public function __construct(
        private HardwarePlatformResolver $hardwarePlatformResolver,
        private readonly NameSanitizer   $nameSanitizer,
    )
    {
    }

    /**
     * @return string[]
     */
    public function resolve(ZxProdRecord $prod, ZxReleaseRecord $release): array
    {
        $platforms = $this->hardwarePlatformResolver->resolvePlatformFolders($release);
        $category = $prod->categoryTitle ?: 'Misc';

        $titleSanitized = $this->nameSanitizer->sanitizeWithArticleHandling($prod->title);
        $firstChar = mb_strtoupper(mb_substr($titleSanitized, 0, 1));

        if (preg_match('/[A-Z]/', $firstChar)) {
            $letter = $firstChar;
        } elseif (preg_match('/[0-9]/', $firstChar)) {
            $letter = '0-9';
        } else {
            $letter = 'other';
        }

        $prodName = $titleSanitized ?: 'UnnamedProd';
        $folderName = rtrim($prodName, '.');

        return array_map(static fn($platform) => $platform . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $letter . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR, $platforms);
    }
}
