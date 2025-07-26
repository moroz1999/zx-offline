<?php
declare(strict_types=1);

namespace App\Sync;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class DownloadService
{
    private const RETRY_LIMIT = 5;
    private const RETRY_DELAY_SEC = 3;

    public function __construct(
        private Client          $client,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * @throws DownloadFatalException|DownloadFailedException
     */
    public function downloadFile(string $url, array $targetPaths, ?string $expectedMd5 = null): void
    {
        $primaryPath = $targetPaths[0];

        if ($this->isValidFile($primaryPath, $expectedMd5)) {
            $this->logger->info("File already exists and MD5 matches: $primaryPath");
            $this->copyToTargets($primaryPath, $targetPaths);
            return;
        }

        $this->attemptDownload($url, $primaryPath, $expectedMd5);

        $this->copyToTargets($primaryPath, $targetPaths);
    }

    private function isValidFile(string $path, ?string $expectedMd5): bool
    {
        if (!file_exists($path) || $expectedMd5 === null) {
            return false;
        }

        return md5_file($path) === $expectedMd5;
    }

    /**
     * @throws DownloadFailedException|DownloadFatalException
     */
    private function attemptDownload(string $url, string $targetPath, ?string $expectedMd5): void
    {
        for ($attempt = 1; $attempt <= self::RETRY_LIMIT; $attempt++) {
            try {
                $this->logger->info("Downloading (attempt $attempt): $url");

                $this->performDownload($url, $targetPath);

                if ($this->isZeroSize($targetPath)) {
                    throw new DownloadFatalException("Downloaded file size is zero: $targetPath");
                }

                if ($expectedMd5 !== null && !$this->isValidFile($targetPath, $expectedMd5)) {
                    throw new DownloadFatalException("MD5 mismatch: expected $expectedMd5, got " . md5_file($targetPath));
                }

                $this->logger->info("Downloaded successfully: $targetPath");
                return;

            } catch (GuzzleException|RuntimeException $e) {
                $this->logger->warning("Download failed (attempt $attempt): {$e->getMessage()}");

                if ($attempt >= self::RETRY_LIMIT) {
                    throw new DownloadFailedException("Failed to download file after $attempt attempts", 0, $e);
                }

                sleep(self::RETRY_DELAY_SEC);
            }
        }
    }

    /**
     * @throws DownloadFatalException
     */
    private function performDownload(string $url, string $targetPath): void
    {
        $response = $this->client->get($url, [
            'stream' => true,
            'timeout' => 30,
        ]);

        $stream = $response->getBody();
        $target = fopen($targetPath, 'wb');

        if (!$target) {
            throw new DownloadFatalException("Cannot open target file: $targetPath");
        }

        while (!$stream->eof()) {
            $chunk = $stream->read(8192);
            fwrite($target, $chunk);
        }

        fclose($target);
    }

    private function isZeroSize(string $path): bool
    {
        return filesize($path) === 0;
    }

    /**
     * @throws DownloadFatalException
     */
    private function copyToTargets(string $source, array $targetPaths): void
    {
        foreach ($targetPaths as $path) {
            if ($path === $source) {
                continue;
            }
            if (file_exists($path)) {
                continue;
            }
            if (!copy($source, $path)) {
                throw new DownloadFatalException("Failed to copy downloaded file to: $path");
            }
            $this->logger->info("Copied to: $path");
        }
    }


}
