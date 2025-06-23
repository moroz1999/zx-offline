<?php
declare(strict_types=1);


namespace App\Archive;

use App\Files\FileRecord;

final class FileArchiveService
{
    public function __construct(
        private string            $archiveBasePath,
    )
    {
    }

    public function deleteFile(FileRecord $file): void
    {
        $filePath = $this->getFilePath($file);
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    public function renameFile(FileRecord $file, string $newFileName): void
    {
        $filePath = $this->getFilePath($file);
        if (is_file($filePath)) {
            rename($filePath, $newFileName);
        }
    }

    public function fileExists(FileRecord $file): bool
    {
        if ($file->filePath === null) {
            return false;
        }
        $filePath = $this->getFilePath($file);
        return is_file($filePath);
    }

    public function getFilePath(FileRecord $file): ?string
    {
        if ($file->filePath === null) {
            return null;
        }
        return $this->archiveBasePath . $file->filePath;
    }

    public function getArchiveBasePath(): string
    {
        return $this->archiveBasePath;
    }
}