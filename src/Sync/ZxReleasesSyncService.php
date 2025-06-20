<?php
declare(strict_types=1);

namespace App\Sync;

use App\Api\ZxArtApiReleasesRequester;
use App\Api\ZxReleaseApiDto;
use App\Files\FileRecord;
use App\Files\FilesRepository;
use App\ZxReleases\ZxReleasesRepository;
use App\ZxReleases\ZxReleaseRecord;
use Psr\Log\LoggerInterface;

final readonly class ZxReleasesSyncService
{
    public function __construct(
        private ZxArtApiReleasesRequester $releasesApi,
        private ZxReleasesRepository $releasesRepository,
        private FilesRepository $filesRepository,
        private LoggerInterface $logger,
    ) {}

    public function sync(): void
    {
        $existingIds = array_flip($this->releasesRepository->getAllIds());

        foreach ($this->releasesApi->getAll() as $apiRelease) {
            $record = $this->mapToRecord($apiRelease);
            $existing = $this->releasesRepository->getById($record->id);
            unset($existingIds[$record->id]);

            if (!$existing) {
                $this->createRelease($record);
                $this->logger->info("Release $record->id created");
            } elseif ($record->dateModified > $existing->dateModified) {
                $this->updateRelease($record);
                $this->logger->info("Release $record->id updated");
            }

            $this->syncFiles($record->id, $apiRelease->files);
        }

        foreach (array_keys($existingIds) as $obsoleteId) {
            $this->releasesRepository->delete($obsoleteId);
            $this->logger->info("Release $obsoleteId deleted as removed from API");
        }
    }

    private function mapToRecord(ZxReleaseApiDto $dto): ZxReleaseRecord
    {
        return new ZxReleaseRecord(
            id: $dto->id,
            title: $dto->title,
            dateModified: $dto->dateModified,
        );
    }

    private function createRelease(ZxReleaseRecord $record): void
    {
        $this->releasesRepository->create($record);
    }

    private function updateRelease(ZxReleaseRecord $record): void
    {
        $this->releasesRepository->update($record);
//        $this->tasks->addTask(TaskTypes::check_release_files, (string)$record->id);
    }

    private function syncFiles(int $releaseId, array $apiFiles): void
    {
        $existingFiles = $this->filesRepository->getByReleaseId($releaseId);
        $existingMap = [];
        foreach ($existingFiles as $f) {
            $existingMap[$f->id] = $f;
        }

        foreach ($apiFiles as $fileDto) {
            $newFile = new FileRecord(
                id: $fileDto->id,
                zxReleaseId: $releaseId,
                md5: $fileDto->md5,
                type: $fileDto->type,
                filePath: 'todo: make it configurable',
            );

            if (!isset($existingMap[$newFile->id])) {
                $this->filesRepository->create($newFile);
                $this->logger->info("File $newFile->id $fileDto->fileName $newFile->zxReleaseId created");
                continue;
            }

            $existingFile = $existingMap[$newFile->id];

            if (
                $newFile->md5 !== $existingFile->md5
                || $newFile->type !== $existingFile->type
            ) {
                $this->filesRepository->update($newFile);
                $this->logger->info("File $newFile->id $fileDto->fileName $newFile->zxReleaseId updated");
            }

            unset($existingMap[$newFile->id]);
        }

        foreach (array_keys($existingMap) as $obsoleteFileId) {
            $this->filesRepository->delete($obsoleteFileId);
            $this->logger->info("File $obsoleteFileId deleted as removed from API");
        }
    }

    public function syncByProdId(int $id): void
    {

    }
}
