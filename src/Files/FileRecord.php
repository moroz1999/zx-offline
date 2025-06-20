<?php
declare(strict_types=1);

namespace App\Files;

final readonly class FileRecord
{
    public function __construct(
        public int $id,
        public int $zxReleaseId,
        public string $md5,
        public string $type,
        public string $filePath,
    ) {}
}
