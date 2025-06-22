<?php
declare(strict_types=1);

namespace App\Sync;

use App\Archive\FileArchiveService;
use App\Files\FilesRepository;
use App\Files\FileRecord;
use App\ZxReleases\ZxReleasesRepository;
use App\ZxProds\ZxProdsRepository;
use Psr\Log\LoggerInterface;

final readonly class ZxReleaseFilesChecker
{
    public function __construct(
        private FilesRepository      $filesRepository,
        private ZxReleasesRepository $releasesRepository,
        private ZxProdsRepository    $prodsRepository,
        private DownloadService      $downloadService,
        private FileArchiveService   $fileArchiveService,
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

        foreach ($existingFiles as $fileDto) {
            $generated = $this->generateFileName($prod, $release, $fileDto);
            $fileId = $fileDto->id;

            $archivePath = $this->fileArchiveService->getArchiveBasePath() . $generated;

            if (!$this->fileArchiveService->fileExists($fileDto)) {
                $this->logger->info("File $fileId missing, downloading");

                $zxArtUrl = "https://zxart.ee/zxfile/id:$release->id/fileId:$fileId/";
                $this->downloadService->downloadFile($zxArtUrl, $archivePath, $fileDto->md5);
                continue;
            }

            $existingFile = $existingMap[$fileId];
            if ($existingFile->filePath !== $generated) {
                $updated = new FileRecord(
                    id: $existingFile->id,
                    zxReleaseId: $existingFile->zxReleaseId,
                    md5: $existingFile->md5,
                    type: $existingFile->type,
                    filePath: $generated
                );

                $this->fileArchiveService->renameFile($existingFile, $generated);
                $this->filesRepository->update($updated);

                $this->logger->info("File {$existingFile->id} renamed: '{$existingFile->fileName}' -> '{$generated}'");
            }

            unset($existingMap[$fileId]);
        }
    }

    private function generateFileName($prod, $release, FileRecord $fileDto): string
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
