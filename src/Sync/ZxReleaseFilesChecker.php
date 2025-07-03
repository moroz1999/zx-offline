<?php
declare(strict_types=1);

namespace App\Sync;

use App\Archive\FileArchiveService;
use App\Archive\FileDirectoryResolver;
use App\Archive\TosecNameResolver;
use App\Files\FileRecord;
use App\Files\FilesRepository;
use App\ZxProds\ZxProdRecord;
use App\ZxProds\ZxProdsRepository;
use App\ZxReleases\ZxReleaseRecord;
use App\ZxReleases\ZxReleasesRepository;
use Psr\Log\LoggerInterface;

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

        $fileDtos = $this->filesRepository->getByReleaseId($release->id);

        foreach ($fileDtos as $fileDto) {
            $this->syncOneFile($fileDto, $prod, $release, $fileDtos);
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

        $relativePath = $this->fileDirectoryResolver->resolve($prod, $release);
        $this->fileArchiveService->checkPath($relativePath);

        $filePath = $relativePath . $tosecName;
        $targetPath = $this->fileArchiveService->getArchiveBasePath() . $filePath;

        if (!$this->fileArchiveService->fileExists($fileRecord)) {
            $this->logger->debug("File {$fileRecord->id} (Prod $prod->id \"$prod->title\" / Release $release->id \"$release->title\") requires download");

            $zxArtUrl = "https://zxart.ee/zxfile/id:$release->id/fileId:$fileRecord->id/";
            $this->downloadService->downloadFile($zxArtUrl, $targetPath, $fileRecord->md5);
        }
        
        if ($fileRecord->filePath !== $filePath) {
            $updatedFileRecord = new FileRecord(
                id: $fileRecord->id,
                zxReleaseId: $fileRecord->zxReleaseId,
                md5: $fileRecord->md5,
                type: $fileRecord->type,
                originalFileName: $fileRecord->originalFileName,
                fileName: $tosecName,
                filePath: $filePath
            );

            if ($fileRecord->filePath !== null) {
                $this->fileArchiveService->renameFile($fileRecord, $filePath);
                $this->logger->info("File {$fileRecord->id} renamed: '{$fileRecord->filePath}' -> '$tosecName'");
            }

            $this->filesRepository->update($updatedFileRecord);
        }
    }
}
