<?php
declare(strict_types=1);

namespace App\ZxProds;

use App\Archive\HardwarePlatformResolver;
use App\ZxReleases\ZxReleasesRepository;
use Psr\Log\LoggerInterface;

final class ZxProdsTitleBucketsBuilder
{
    private const MAX_ITEMS_PER_BUCKET = 200;
    private const MAX_PREFIX_LENGTH = 2;
    private const NUMERIC_BUCKET = '0-1';

    public function __construct(
        private readonly ZxProdsRepository        $zxProdsRepository,
        private readonly ZxReleasesRepository     $zxReleasesRepository,
        private readonly HardwarePlatformResolver $hardwarePlatformResolver,
        private readonly TitleNormalizer          $titleNormalizer,
        private readonly TitleBucketsCacheStorage $cacheStorage,
        private readonly LoggerInterface          $logger,
    )
    {
    }

    public function buildAndSaveBuckets(): void
    {
        $this->logger->info('ZxProdsTitleBucketsBuilder: building buckets for platform/category.');

        $prodIds = $this->zxProdsRepository->getAllIds();

        /** @var array<string, array<string, string[]>> $titlesByPlatformAndCategory */
        $titlesByPlatformAndCategory = [];

        $processed = 0;
        $total = count($prodIds);

        foreach ($prodIds as $prodId) {
            $processed++;

            if ($processed % 1000 === 0) {
                $this->logger->info(
                    sprintf(
                        'ZxProdsTitleBucketsBuilder: processed %d of %d prods.',
                        $processed,
                        $total
                    )
                );
            }

            $prod = $this->zxProdsRepository->getById((int)$prodId);

            if ($prod === null) {
                $this->logger->warning(
                    sprintf(
                        'ZxProdsTitleBucketsBuilder: prod not found by id: %d.',
                        $prodId
                    )
                );
                continue;
            }

            $normalizedTitle = $this->titleNormalizer->normalize($prod->sanitizedTitle);
            if ($normalizedTitle === '') {
                $normalizedTitle = '0';
            }

            $categoryKey = $prod->categoryTitle;
            if ($categoryKey === null) {
                continue;
            }

            $releases = $this->zxReleasesRepository->getByProdId($prod->id);
            if ($releases === []) {
                continue;
            }

            /** @var array<string, bool> $platformSet */
            $platformSet = [];

            foreach ($releases as $release) {
                $platforms = $this->hardwarePlatformResolver->resolvePlatformFolders($release);

                if ($platforms === []) {
                    continue;
                }

                foreach ($platforms as $platform) {
                    $platformSet[$platform] = true;
                }
            }

            if ($platformSet === []) {
                continue;
            }

            $uniquePlatforms = array_keys($platformSet);

            $this->addTitleToBuckets(
                titlesByPlatformAndCategory: $titlesByPlatformAndCategory,
                platforms: $uniquePlatforms,
                categoryKey: $categoryKey,
                normalizedTitle: $normalizedTitle
            );
        }

        /** @var array<string, array<string, string[]>> $finalBuckets */
        $finalBuckets = [];

        foreach ($titlesByPlatformAndCategory as $platform => $categories) {
            foreach ($categories as $category => $titles) {
                $uniqueTitles = array_values(array_unique($titles));
                sort($uniqueTitles, SORT_STRING);

                $bucketKeys = $this->buildBucketsByLetter($uniqueTitles);

                if (!array_key_exists($platform, $finalBuckets)) {
                    $finalBuckets[$platform] = [];
                }

                $finalBuckets[$platform][$category] = $bucketKeys;
            }
        }

        $this->cacheStorage->save($finalBuckets);

        $this->logger->info(
            sprintf(
                'ZxProdsTitleBucketsBuilder: buckets built for %d platforms.',
                count($finalBuckets)
            )
        );
    }

    /**
     * @param array<string, array<string, string[]>> $titlesByPlatformAndCategory
     * @param string[] $platforms
     * @param string $categoryKey
     * @param string $normalizedTitle
     */
    private function addTitleToBuckets(
        array  &$titlesByPlatformAndCategory,
        array  $platforms,
        string $categoryKey,
        string $normalizedTitle
    ): void
    {
        $uniquePlatforms = array_values(array_unique($platforms));

        foreach ($uniquePlatforms as $platform) {
            if (!array_key_exists($platform, $titlesByPlatformAndCategory)) {
                $titlesByPlatformAndCategory[$platform] = [];
            }

            if (!array_key_exists($categoryKey, $titlesByPlatformAndCategory[$platform])) {
                $titlesByPlatformAndCategory[$platform][$categoryKey] = [];
            }

            $titlesByPlatformAndCategory[$platform][$categoryKey][] = $normalizedTitle;
        }
    }

    /**
     * @param string[] $normalizedTitles
     * @return string[]
     */
    private function buildBucketsByLetter(array $normalizedTitles): array
    {
        $groups = [];

        foreach ($normalizedTitles as $title) {
            $first = $this->firstLetterKey($title);

            if (!isset($groups[$first])) {
                $groups[$first] = [];
            }

            $groups[$first][] = $title;
        }

        ksort($groups);

        $bucketKeys = [];

        foreach ($groups as $letter => $titlesForLetter) {

            $count = count($titlesForLetter);

            if ($count <= self::MAX_ITEMS_PER_BUCKET) {
                // один бакет = одна буква
                $bucketKeys[] = $letter;
                continue;
            }

            // много — режем по 200
            $chunks = array_chunk($titlesForLetter, self::MAX_ITEMS_PER_BUCKET);

            foreach ($chunks as $chunk) {
                $firstTitle = $chunk[0];
                $prefix = substr($firstTitle, 0, self::MAX_PREFIX_LENGTH);
                $bucketKeys[] = $prefix;
            }
        }

        return $bucketKeys;
    }

    private function firstLetterKey(string $title): string
    {
        if ($title === '' || ctype_digit($title[0])) {
            return self::NUMERIC_BUCKET;
        }

        return $title[0];
    }


}
