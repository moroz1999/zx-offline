<?php
declare(strict_types=1);

namespace App\Sync;

use App\Archive\FileArchiveService;
use App\Archive\FileDirectoryResolver;
use App\Archive\TosecNameResolver;
use App\Files\FileRecord;
use App\Files\FilesRepository;
use App\ZxProds\ZxProdsRepository;
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

    public function syncReleaseFiles(int $id): void
    {
        $release = $this->releasesRepository->getById($id);
        if (!$release) {
            $this->logger->warning("Release $id not found");
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
            $duplicateIndex = 0;

            do {
                $tosecName = $this->tosecNameResolver->generateTosecName($prod, $release, $existingFiles, $fileDto, $duplicateIndex);
                $duplicateIndex++;
            } while ($this->filesRepository->existsFileName($tosecName));

            $relativePath = $this->fileDirectoryResolver->resolve($prod, $release);
            $this->fileArchiveService->checkPath($relativePath);
            $filePath = $relativePath . $tosecName;

            $fileId = $fileDto->id;
            $archivePath = $this->fileArchiveService->getArchiveBasePath() . $filePath;

            if (!$this->fileArchiveService->fileExists($fileDto)) {
                $this->logger->debug("File $fileId (Prod $prod->id \"$prod->title\" / Release $release->id \"$release->title\") is missing, downloading");
                $zxArtUrl = "https://zxart.ee/zxfile/id:$release->id/fileId:$fileId/";
                $this->downloadService->downloadFile($zxArtUrl, $archivePath, $fileDto->md5);
            }

            $existingFile = $existingMap[$fileId];
            if ($existingFile->filePath !== $filePath) {
                $updated = new FileRecord(
                    id: $existingFile->id,
                    zxReleaseId: $existingFile->zxReleaseId,
                    md5: $existingFile->md5,
                    type: $existingFile->type,
                    originalFileName: $existingFile->originalFileName,
                    fileName: $tosecName,
                    filePath: $filePath
                );

                if ($existingFile->filePath !== null) {
                    $this->fileArchiveService->renameFile($existingFile, $filePath);
                    $this->logger->info("File {$existingFile->id} renamed: '{$existingFile->filePath}' -> '{$tosecName}'");
                }

                $this->filesRepository->update($updated);
            }

            unset($existingMap[$fileId]);
        }
    }

}
