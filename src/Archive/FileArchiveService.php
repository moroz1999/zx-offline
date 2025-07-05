<?php
declare(strict_types=1);


namespace App\Archive;

use App\Files\FileRecord;

final class FileArchiveService
{
    public function __construct(
        private string $archiveBasePath,
    )
    {
        if (!is_dir($this->archiveBasePath)) {
            mkdir($this->archiveBasePath, 0777, true);
        }
        $this->archiveBasePath = realpath($this->archiveBasePath);
        $this->archiveBasePath = rtrim($this->archiveBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function checkPath(string $path): void
    {
        $filePath = $this->archiveBasePath . $path;
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
    }

    public function deleteFile(FileRecord $file): void
    {
        if ($file->filePath === null) {
            return;
        }
        $filePath = $this->getFilePath($file);
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    public function renameFile(FileRecord $file, string $newFileName): void
    {
        $currentFilePath = $this->getFilePath($file);
        $newFullPath = $this->archiveBasePath . $newFileName;

        if (is_file($currentFilePath)) {
            rename($currentFilePath, $newFullPath);
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