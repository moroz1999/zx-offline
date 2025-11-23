<?php
declare(strict_types=1);

namespace App\ZxProds;

use Psr\Log\LoggerInterface;

final class ZxProdsTitleBucketResolver
{
    private const NUMERIC_BUCKET = '0-1';
    private const DEFAULT_CATEGORY = 'Unknown';

    /**
     * @var array<string, array<string, array<string, bool>>>
     */
    private array $bucketMap = [];

    private bool $bucketMapLoaded = false;

    public function __construct(
        private readonly TitleNormalizer          $titleNormalizer,
        private readonly TitleBucketsCacheStorage $cacheStorage,
        private readonly LoggerInterface          $logger
    )
    {
    }

    public function getBucketForTitle(
        string  $sanitizedTitle,
        string  $platform,
        ?string $categoryTitle
    ): string
    {
        $this->ensureBucketCacheLoaded();

        $normalized = $this->titleNormalizer->normalize($sanitizedTitle);

        if ($normalized === '') {
            return self::NUMERIC_BUCKET;
        }

        $firstCharacter = $normalized[0];

        if (ctype_digit($firstCharacter)) {
            return self::NUMERIC_BUCKET;
        }

        $categoryKey = $this->resolveCategoryKey($categoryTitle);

        $bucketKeys = $this->bucketMap[$platform][$categoryKey] ?? null;

        if (!is_array($bucketKeys) || $bucketKeys === []) {
            $this->logger->warning(
                sprintf(
                    'ZxProdsTitleBucketResolver: no buckets for platform "%s", category "%s".',
                    $platform,
                    $categoryKey
                )
            );

            return $firstCharacter;
        }

        return $this->findBucket($bucketKeys, $normalized);
    }

    private function resolveCategoryKey(?string $categoryTitle): string
    {
        if ($categoryTitle === null || $categoryTitle === '') {
            return self::DEFAULT_CATEGORY;
        }

        return $categoryTitle;
    }

    private function ensureBucketCacheLoaded(): void
    {
        if ($this->bucketMapLoaded) {
            return;
        }

        $this->loadBucketCache();
        $this->bucketMapLoaded = true;
    }

    private function loadBucketCache(): void
    {
        $rawBuckets = $this->cacheStorage->load();

        if ($rawBuckets === []) {
            $this->logger->warning('ZxProdsTitleBucketResolver: bucket cache is empty.');
            $this->bucketMap = [];
            return;
        }

        $this->bucketMap = $rawBuckets;
    }

    /**
     * @param string[] $bucketKeys Sorted list of bucket keys.
     * @param string $normalizedTitle
     * @return string
     */
    private function findBucket(array $bucketKeys, string $normalizedTitle): string
    {
        $low = 0;
        $high = count($bucketKeys) - 1;

        while ($low <= $high) {
            $mid = (int)(($low + $high) / 2);
            $cmp = strcmp($normalizedTitle, $bucketKeys[$mid]);

            if ($cmp < 0) {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        if ($high < 0) {
            return $bucketKeys[0];
        }

        return $bucketKeys[$high];
    }
}
