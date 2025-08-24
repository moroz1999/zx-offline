<?php
declare(strict_types=1);


namespace App\Archive;

use App\Files\FileRecord;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

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
            // delete file if exists
            if (is_file($filePath)) {
                unlink($filePath);
            }

            if (is_dir($filePath)) {
                $this->rrmdir($filePath);
            }
            $this->cleanupDirIfEmpty(dirname($filePath));
        }
    }

    /**
     * @param FileRecord $file
     * @param string $currentFullPath
     * @param string $newFileName
     * @return void
     */
    public function renameFilePaths(FileRecord $file, string $newFileName): void
    {
        $currentFilePaths = $this->getFilePaths($file);

        foreach ($currentFilePaths as $currentFullPath) {
            $pi = pathinfo($currentFullPath);
            $newRelativePath = ($pi['dirname'] !== '.' ? $pi['dirname'] . DIRECTORY_SEPARATOR : '') . $newFileName;
            $newFullPath = $this->archiveBasePath . $newRelativePath;

            // rename file if exists
            if (is_file($currentFullPath)) {
                rename($currentFullPath, $newFullPath);
            }
            if (is_dir($currentFullPath)) {
                $newDir = $this->pathWithoutExtension($newFullPath);
                rename($currentFullPath, $newDir);
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
            if (is_file($filePath)) {
                continue;
            }
            if (is_dir($filePath)) {
                continue;
            }
            return false;
        }
        return true;
    }

    public function getFilePaths(FileRecord $file): array
    {
        return array_map(fn($filePath) => $this->archiveBasePath . $filePath, $file->getFilePaths());
    }

    public function getArchiveBasePath(): string
    {
        return $this->archiveBasePath;
    }

    private function cleanupDirIfEmpty(string $dir): void
    {
        if (is_dir($dir) && $this->isDirEmpty($dir)) {
            rmdir($dir);
        }
    }

    private function isDirEmpty(string $dir): bool
    {
        try {
            $it = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
            return !$it->valid();
        } catch (UnexpectedValueException) {
            return false;
        }
    }


    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($ri as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }

    private function pathWithoutExtension(string $path): string
    {
        $pi = pathinfo($path);
        return ($pi['dirname'] !== '.' ? $pi['dirname'] . DIRECTORY_SEPARATOR : '')
            . $pi['filename'];
    }
}
