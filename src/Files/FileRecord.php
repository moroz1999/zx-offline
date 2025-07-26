<?php
declare(strict_types=1);

namespace App\Files;

final readonly class FileRecord
{
    public function __construct(
        public int     $id,
        public int     $zxReleaseId,
        public string  $md5,
        public string  $type,
        public string  $originalFileName,
        public ?string $fileName,
        /** @var FilePathRecord[] */
        public array   $filePaths = [],
    )
    {
    }

    public function getFilePaths(): array
    {
        return array_map(static fn(FilePathRecord $filePathRecord) => $filePathRecord->filePath, $this->filePaths);
    }
}
