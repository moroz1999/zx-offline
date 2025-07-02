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
    public function downloadFile(string $url, string $targetPath, ?string $expectedMd5 = null): void
    {
        $attempt = 0;

        while (true) {
            try {
                $this->logger->info("Downloading: $url");

                if (file_exists($targetPath)) {
                    throw new DownloadFatalException("File already exists: $targetPath");
                }

                $response = $this->client->get($url, [
                    'stream' => true,
                    'timeout' => 30,
                ]);

                $stream = $response->getBody();
                $target = fopen($targetPath, 'wb');

                if (!$target) {
                    throw new DownloadFatalException("Cannot open target file: $targetPath");
                }

                $written = 0;
                while (!$stream->eof()) {
                    $chunk = $stream->read(8192);
                    $written += strlen($chunk);
                    fwrite($target, $chunk);
                }
                fclose($target);

                if ($written === 0) {
                    throw new DownloadFatalException("Downloaded file size is zero: $targetPath");
                }

                if ($expectedMd5 !== null) {
                    $actualMd5 = md5_file($targetPath);
                    if ($actualMd5 !== strtolower($expectedMd5)) {
                        throw new DownloadFatalException("MD5 mismatch: expected $expectedMd5, got $actualMd5");
                    }
                }

                $this->logger->info("Downloaded successfully: $targetPath");
                return;

            } catch (GuzzleException|RuntimeException $e) {
                $attempt++;
                $this->logger->warning("Download failed (attempt $attempt, $url): {$e->getMessage()}");

                if ($attempt > self::RETRY_LIMIT) {
                    throw new DownloadFailedException("Failed to download file after $attempt attempts", 0, $e);
                }

                sleep(self::RETRY_DELAY_SEC);
            }
        }
    }
}
