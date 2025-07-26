<?php
declare(strict_types=1);

namespace App\Sync;

use App\Archive\FileArchiveService;
use App\Archive\FileDirectoryResolver;
use App\Archive\TosecNameResolver;
use App\Files\FilePathRecord;
use App\Files\FileRecord;
use App\Files\FilesRepository;
use App\ZxProds\ZxProdRecord;
use App\ZxProds\ZxProdsRepository;
use App\ZxReleases\ZxReleaseRecord;
use App\ZxReleases\ZxReleasesRepository;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

final readonly class ZxReleaseFilesChecker
{
    public function __construct(
        private FilesRepository       $filesRepository,
        private ZxReleasesRepository  $releasesRepository,
        private ZxProdsRepository     $prodsRepository,
        private DownloadService       $downloadService,
        private FileArchiveService    $fileArchiveService,
        private TosecNameResolver     $tosecNameResolver,
        private FileDirectoryResolver $fileDirectoryResolver,
        private LoggerInterface       $logger,
    )
    {
    }

    public function retryFile(int $fileId): void
    {
        $fileRecord = $this->filesRepository->getById($fileId);
        if (!$fileRecord) {
            $this->logger->warning("File $fileId not found");
            return;
        }

        $release = $this->releasesRepository->getById($fileRecord->zxReleaseId);
        if (!$release) {
            $this->logger->warning("Release $fileRecord->zxReleaseId not found");
            return;
        }

        $prod = $this->prodsRepository->getById($release->prodId);
        if (!$prod) {
            $this->logger->warning("Prod $release->prodId not found");
            return;
        }

        $allFiles = $this->filesRepository->getByReleaseId($release->id);

        $this->syncOneFile($fileRecord, $prod, $release, $allFiles);
    }

    public function syncReleaseFiles(int $id): void
    {
        $release = $this->releasesRepository->getById($id);
        if (!$release) {
            $this->logger->warning("Release $id not found");
            return;
        }

        $prod = $this->prodsRepository->getById($release->prodId);
        if (!$prod) {
            $this->logger->warning("Prod $release->prodId not found");
            return;
        }

        $fileRecords = $this->filesRepository->getByReleaseId($release->id);

        foreach ($fileRecords as $fileRecord) {
            $this->syncOneFile($fileRecord, $prod, $release, $fileRecords);
        }
    }

    private function syncOneFile(
        FileRecord      $fileRecord,
        ZxProdRecord    $prod,
        ZxReleaseRecord $release,
        array           $allFiles
    ): void
    {
        $duplicateIndex = 0;

        do {
            $tosecName = $this->tosecNameResolver->generateTosecName(
                $prod,
                $release,
                $allFiles,
                $fileRecord,
                $duplicateIndex
            );
            $duplicateIndex++;
        } while ($this->filesRepository->existsFileName($tosecName));

        $relativePaths = $this->fileDirectoryResolver->resolve($prod, $release);
        array_map(fn(string $path) => $this->fileArchiveService->checkPath($path), $relativePaths);

        $filePaths = array_map(fn(string $path) => $path . $tosecName, $relativePaths);
        $targetPaths = array_map(fn(string $filePath) => $this->fileArchiveService->getArchiveBasePath() . $filePath, $filePaths);

        $needsDownload = !$this->fileArchiveService->fileExists($fileRecord);

        if ($needsDownload) {
            $this->logger->debug("File {$fileRecord->id} (Prod $prod->id \"$prod->title\" / Release $release->id \"$release->title\") requires download");

            $zxArtUrl = "https://zxart.ee/zxfile/id:$release->id/fileId:$fileRecord->id/";
            $this->downloadService->downloadFile($zxArtUrl, $targetPaths, $fileRecord->md5);
        }

        $newFilePaths = array_map(
            static fn(string $path) => new FilePathRecord(
                id: Uuid::uuid4(),
                fileId: $fileRecord->id,
                filePath: $path
            ),
            $filePaths
        );

        $currentPathStrings = array_map(fn($fp) => $fp->filePath, $fileRecord->filePaths);

        if ($currentPathStrings !== $filePaths) {
            foreach ($fileRecord->getFilePaths() as $oldPath) {
                if ($oldPath->filePath !== null && !in_array($oldPath->filePath, $filePaths, true)) {
                    $this->fileArchiveService->renameFile($fileRecord, $oldPath->filePath, $tosecName);
                    $this->logger->info("File {$fileRecord->id} renamed: '{$oldPath->filePath}' -> '$tosecName'");
                }
            }

            $updatedFile = new FileRecord(
                id: $fileRecord->id,
                zxReleaseId: $fileRecord->zxReleaseId,
                md5: $fileRecord->md5,
                type: $fileRecord->type,
                originalFileName: $fileRecord->originalFileName,
                fileName: $tosecName,
                filePaths: $newFilePaths
            );

            $this->filesRepository->update($updatedFile);
        }
    }
}
