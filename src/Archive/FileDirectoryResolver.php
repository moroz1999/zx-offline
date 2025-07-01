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

    public function resolve(ZxProdRecord $zxProdRecord, ZxReleaseRecord $release): string
    {
        $platform = $this->hardwarePlatformResolver->resolvePlatformFolder($release);
        $category = $zxProdRecord->categoryTitle ?: 'Misc';

        $titleSanitized = $this->nameSanitizer->sanitizeWithArticleHandling($zxProdRecord->title);
        $firstChar = mb_strtoupper(mb_substr($titleSanitized, 0, 1));

        if (preg_match('/[A-Z]/', $firstChar)) {
            $letter = $firstChar;
        } elseif (preg_match('/[0-9]/', $firstChar)) {
            $letter = '0-9';
        } else {
            $letter = 'other';
        }

        $prodName = $titleSanitized ?: 'UnnamedProd';

        return $platform . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $letter . DIRECTORY_SEPARATOR . $prodName . DIRECTORY_SEPARATOR;
    }
}
