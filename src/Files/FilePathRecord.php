<?php
declare(strict_types=1);


namespace App\Files;

use Ramsey\Uuid\UuidInterface;

final readonly class FilePathRecord
{
    public function __construct(
        public UuidInterface $id,
        public int           $fileId,
        public string        $filePath,
    )
    {
    }
}