<?php
declare(strict_types=1);

namespace App\Api;

final readonly class ZxReleaseApiDto
{
    /** @param FileApiDto[] $files */
    public function __construct(
        public int $id,
        public string $title,
        public int $dateModified,
        public array $files = [],
    ) {}
}
