<?php
declare(strict_types=1);

namespace App\Api;

final readonly class FileApiDto
{
    public function __construct(
        public int $id,
        public string $md5,
        public string $type,
        public string $fileName,
    ) {}
}
