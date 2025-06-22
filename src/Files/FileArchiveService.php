<?php
declare(strict_types=1);


namespace App\Files;

final class FileArchiveService
{
    public function __construct()
    {

    }

    public function deleteFile(FileRecord $file)
    {
        if (is_file($file->filePath)) {
            unlink($file->filePath);
        }
    }

    public function renameFile(FileRecord $file)
    {
        if (is_file($file->filePath)) {

        }
    }
}