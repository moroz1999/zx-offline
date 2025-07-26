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
        $filePaths = $this->getFilePaths($file);
        foreach ($filePaths as $filePath) {
            if (is_file($filePath)) {
                unlink($filePath);

                $dir = dirname($filePath);
                if (is_dir($dir) && $this->isDirEmpty($dir)) {
                    rmdir($dir);
                }
            }
        }
    }

    private function isDirEmpty(string $dir): bool
    {
        return is_readable($dir) && count(scandir($dir)) === 2;
    }


    public function renameFile(FileRecord $file, string $newFileName): void
    {
        $currentFilePaths = $this->getFilePaths($file);
        $newFullPath = $this->archiveBasePath . $newFileName;
        foreach ($currentFilePaths as $currentFilePath) {
            if (is_file($currentFilePath)) {
                rename($currentFilePath, $newFullPath);
            }
        }
    }

    public function fileExists(FileRecord $file): bool
    {
        if ($file->getFilePaths() === []) {
            return false;
        }
        $filePaths = $this->getFilePaths($file);
        foreach ($filePaths as $filePath) {
            if (!is_file($filePath)) {
                return false;
            }
        }
        return true;
    }

    public function getFilePaths(FileRecord $file): ?array
    {
        return array_map(fn($filePath) => $this->archiveBasePath . $filePath, $file->getFilePaths());
    }

    public function getArchiveBasePath(): string
    {
        return $this->archiveBasePath;
    }
}