<?php
declare(strict_types=1);

namespace App\Archive;

use App\ZxProds\ZxProdRecord;
use App\ZxProds\ZxProdsTitleBucketResolver;
use App\ZxReleases\ZxReleaseRecord;

final readonly class FileDirectoryResolver
{
    public function __construct(
        private HardwarePlatformResolver   $hardwarePlatformResolver,
        private ZxProdsTitleBucketResolver $zxProdsTitleBucketResolver
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

        return array_map(function (string $platform) use ($category, $baseName): string {
            $bucket = $this->zxProdsTitleBucketResolver->getBucketForTitle($baseName, $platform, $category);

            return $platform
                . DIRECTORY_SEPARATOR . $category
                . DIRECTORY_SEPARATOR . $bucket
                . DIRECTORY_SEPARATOR . $baseName
                . DIRECTORY_SEPARATOR;
        }, $platforms);
    }
}
