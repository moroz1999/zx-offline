<?php
declare(strict_types=1);

namespace App\ZxProds;

use JsonException;
use Psr\Log\LoggerInterface;

final class TitleBucketsCacheStorage
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $bucketsCacheDir,
    ) {
    }

    /**
     * @return array<string, array<string, string[]>>
     */
    public function load(): array
    {
        $cacheFilePath = $this->getCacheFilePath();

        if (!is_file($cacheFilePath)) {
            $this->logger->warning(
                sprintf(
                    'TitleBucketsCacheStorage: cache file not found: %s.',
                    $cacheFilePath
                )
            );
            return [];
        }

        $content = file_get_contents($cacheFilePath);

        if ($content === false) {
            $this->logger->error(
                sprintf(
                    'TitleBucketsCacheStorage: failed to read cache file: %s.',
                    $cacheFilePath
                )
            );
            return [];
        }

        try {
            $decoded = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            $this->logger->error(
                sprintf(
                    'TitleBucketsCacheStorage: failed to decode JSON: %s.',
                    $exception->getMessage()
                )
            );
            return [];
        }

        if (!is_array($decoded)) {
            $this->logger->error(
                sprintf(
                    'TitleBucketsCacheStorage: invalid cache file structure: %s.',
                    $cacheFilePath
                )
            );
            return [];
        }

        /** @var array<string, array<string, string[]>> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, array<string, string[]>> $buckets
     */
    public function save(array $buckets): void
    {
        $cacheFilePath = $this->getCacheFilePath();

        try {
            $json = json_encode(
                $buckets,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
            );
        } catch (JsonException $exception) {
            $this->logger->error(
                sprintf(
                    'TitleBucketsCacheStorage: failed to encode buckets to JSON: %s.',
                    $exception->getMessage()
                )
            );
            return;
        }

        $bytesWritten = file_put_contents($cacheFilePath, $json);

        if ($bytesWritten === false) {
            $this->logger->error(
                sprintf(
                    'TitleBucketsCacheStorage: failed to write cache file: %s.',
                    $cacheFilePath
                )
            );
            return;
        }

        $this->logger->info(
            sprintf(
                'TitleBucketsCacheStorage: cache file written: %s (%d bytes).',
                $cacheFilePath,
                $bytesWritten
            )
        );
    }

    private function getCacheFilePath(): string
    {
        return $this->bucketsCacheDir;
    }
}
