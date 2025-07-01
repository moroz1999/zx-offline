<?php
declare(strict_types=1);

namespace App\Sync;

use App\Api\ZxArtApiReleasesRequester;
use App\Api\ZxReleaseApiDto;
use App\Archive\FileArchiveService;
use App\Files\FileRecord;
use App\Files\FilesRepository;
use App\Tasks\TasksRepository;
use App\Tasks\TaskTypes;
use App\ZxReleases\ZxReleaseRecord;
use App\ZxReleases\ZxReleasesRepository;
use Psr\Log\LoggerInterface;

final readonly class ZxReleasesSyncService
{
    public function __construct(
        private TasksRepository           $tasks,
        private ZxArtApiReleasesRequester $releasesApi,
        private ZxReleasesRepository      $releasesRepository,
        private FilesRepository           $filesRepository,
        private FileArchiveService        $fileArchiveService,
        private LoggerInterface           $logger,
    )
    {
    }

    public function sync(): void
    {
        $existingIds = array_flip($this->releasesRepository->getAllIds());

        foreach ($this->releasesApi->getAll() as $apiRelease) {
            $record = $this->mapToRecord($apiRelease);
            $existing = $this->releasesRepository->getById($record->id);
            unset($existingIds[$record->id]);

            if (!$existing) {
                $this->createRelease($record);
                $this->tasks->addTask(TaskTypes::check_release_files, (string)$record->id);
                $this->logger->info("Release $record->id $record->title created");
            } elseif ($record->dateModified > $existing->dateModified) {
                $this->updateRelease($record);
                $this->tasks->addTask(TaskTypes::check_release_files, (string)$record->id);
                $this->logger->info("Release $record->id $record->title updated");
            } else {
                $this->logger->info("Release $record->id $record->title is not modified, skipped");
            }

            $this->syncFileRecords($record->id, $apiRelease->files);
        }

        foreach (array_keys($existingIds) as $obsoleteId) {
            $this->tasks->addTask(TaskTypes::delete_release, (string)$obsoleteId);
            $this->logger->info("Release $obsoleteId deleted as removed from API");
        }
    }

    private function mapToRecord(ZxReleaseApiDto $dto): ZxReleaseRecord
    {
        return new ZxReleaseRecord(
            id: $dto->id,
            prodId: $dto->prodId,
            title: $dto->title,
            dateModified: $dto->dateModified,
            languages: $dto->languages,
            publishers: $dto->publishers,
            year: $dto->year,
            releaseType: $dto->releaseType,
            version: $dto->version,
            hardware: $dto->hardware,
        );
    }

    private function createRelease(ZxReleaseRecord $record): void
    {
        $this->releasesRepository->create($record);
    }

    private function updateRelease(ZxReleaseRecord $record): void
    {
        $this->releasesRepository->update($record);
    }

    private function syncFileRecords(int $releaseId, array $apiFiles): void
    {
        $existingFiles = $this->filesRepository->getByReleaseId($releaseId);
        $existingMap = [];
        foreach ($existingFiles as $file) {
            $existingMap[$file->id] = $file;
        }

        foreach ($apiFiles as $fileDto) {
            $newFile = new FileRecord(
                id: $fileDto->id,
                zxReleaseId: $releaseId,
                md5: $fileDto->md5,
                type: $fileDto->type,
                originalFileName: $fileDto->fileName,
                fileName: null,
                filePath: null,
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
            $this->deleteReleaseFile($obsoleteFileId);

            $this->logger->info("File $obsoleteFileId deleted as removed from API");
        }
    }

    public function syncByProdId(int $id): void
    {

    }

    public function deleteRelease(int $releaseId): void
    {
        $files = $this->filesRepository->getByReleaseId($releaseId);
        array_map(fn(FileRecord $file) => $this->deleteReleaseFile($file->id), $files);

        $this->releasesRepository->delete($releaseId);
        $this->logger->info("Release $releaseId deleted");
    }

    public function deleteReleaseFile(int $fileId): void
    {
        $file = $this->filesRepository->getById($fileId);
        if (!$file) {
            return;
        }
        $this->fileArchiveService->deleteFile($file);
        $this->filesRepository->delete($fileId);

        $this->logger->info("File $file->id $file->filePath deleted");
    }
}
