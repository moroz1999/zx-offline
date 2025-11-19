<?php
declare(strict_types=1);

namespace App\ZxProds;

use Psr\Log\LoggerInterface;

final class ZxProdsTitleBucketResolver
{
    private const NUMERIC_BUCKET = '0-1';
    private const MAX_PREFIX_LENGTH = 4;
    private const DEFAULT_CATEGORY = 'Unknown';

    /**
     * @var array<string, array<string, array<string, bool>>>
     */
    private array $bucketMap = [];

    private bool $bucketMapLoaded = false;

    public function __construct(
        private readonly TitleNormalizer $titleNormalizer,
        private readonly TitleBucketsCacheStorage $cacheStorage,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getBucketForTitle(
        string $sanitizedTitle,
        string $platform,
        ?string $categoryTitle
    ): string {
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

        $platformBuckets = $this->bucketMap[$platform][$categoryKey] ?? null;

        $maxLength = min(self::MAX_PREFIX_LENGTH, strlen($normalized));

        if (is_array($platformBuckets) && $platformBuckets !== []) {
            for ($length = $maxLength; $length >= 1; $length--) {
                $prefix = substr($normalized, 0, $length);

                if (isset($platformBuckets[$prefix])) {
                    return $prefix;
                }
            }
        } else {
            $this->logger->warning(
                sprintf(
                    'ZxProdsTitleBucketResolver: no buckets for platform "%s", category "%s".',
                    $platform,
                    $categoryKey
                )
            );
        }

        return $firstCharacter;
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

        $map = [];

        foreach ($rawBuckets as $platform => $categories) {
            if (!is_string($platform) || !is_array($categories)) {
                continue;
            }

            foreach ($categories as $category => $bucketList) {
                if (!is_string($category) || !is_array($bucketList)) {
                    continue;
                }

                foreach ($bucketList as $prefix) {
                    if (!is_string($prefix) || $prefix === '') {
                        continue;
                    }

                    if (!isset($map[$platform])) {
                        $map[$platform] = [];
                    }
                    if (!isset($map[$platform][$category])) {
                        $map[$platform][$category] = [];
                    }

                    $map[$platform][$category][$prefix] = true;
                }
            }
        }

        $this->bucketMap = $map;

        $this->logger->info(
            sprintf(
                'ZxProdsTitleBucketResolver: loaded buckets for %d platforms.',
                count($this->bucketMap)
            )
        );
    }
}
