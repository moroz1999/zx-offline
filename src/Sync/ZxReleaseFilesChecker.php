<?php
declare(strict_types=1);

namespace App\Sync;

use App\Files\FilesRepository;
use App\Files\FileRecord;
use App\ZxReleases\ZxReleasesRepository;
use App\ZxProds\ZxProdsRepository;
use App\Api\ZxReleaseApiDto;
use App\Api\FileApiDto;
use Psr\Log\LoggerInterface;

final readonly class ZxReleaseFilesChecker
{
    public function __construct(
        private FilesRepository      $filesRepository,
        private ZxReleasesRepository $releasesRepository,
        private ZxProdsRepository    $prodsRepository,
        private DownloadService      $downloadService,
        private LoggerInterface      $logger,
    )
    {
    }

    public function syncReleaseFiles(int $id): void
    {
        $release = $this->releasesRepository->getById($id);
        if (!$release) {
            $this->logger->warning("Release {$release->id} not found");
            return;
        }

        $prod = $this->prodsRepository->getById($release->prodId);
        if (!$prod) {
            $this->logger->warning("Prod {$release->prodId} not found");
            return;
        }

        $existingFiles = $this->filesRepository->getByReleaseId($release->id);
        $existingMap = [];
        foreach ($existingFiles as $file) {
            $existingMap[$file->id] = $file;
        }

        foreach ($release->files as $fileDto) {
            $expectedName = $this->generateFileName($prod, $release, $fileDto);
            $fileId = $fileDto->id;

            if (!isset($existingMap[$fileId])) {
                // файл отсутствует в БД, нужно скачать
                $this->logger->info("File $fileId missing, downloading");
                $this->downloadService->downloadFile($release->id, $fileDto);
                continue;
            }

            $existingFile = $existingMap[$fileId];
            if ($existingFile->fileName !== $expectedName) {
                $updated = new FileRecord(
                    id: $existingFile->id,
                    zxReleaseId: $existingFile->zxReleaseId,
                    md5: $existingFile->md5,
                    type: $existingFile->type,
                    filePath: $expectedName
                );

                $this->filesRepository->updateFileName($existingFile, $updated);
                $this->logger->info("File {$existingFile->id} renamed: '{$existingFile->fileName}' -> '{$expectedName}'");
            }

            unset($existingMap[$fileId]);
        }

        // Всё. Лишние файлы (оставшиеся в existingMap) не трогаем — их удаляют в другом таске
    }

    private function generateFileName($prod, $release, FileApiDto $fileDto): string
    {
        $year = $release->year ?? $prod->year;
        $parts = [$prod->title];

        if ($release->releaseType) {
            $parts[] = "({$release->releaseType})";
        }
        if ($year) {
            $parts[] = "($year)";
        }

        $ext = strtolower($fileDto->type);
        return trim(implode(' ', $parts)) . '.' . $ext;
    }
}
