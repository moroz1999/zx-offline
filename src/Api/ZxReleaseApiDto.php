<?php
declare(strict_types=1);

namespace App\Api;

final readonly class ZxReleaseApiDto
{
    /** @param FileApiDto[] $files */
    public function __construct(
        public int     $id,
        public string  $title,
        public int     $dateModified,
        public ?string $languages,
        public ?string $publishers,
        public ?int    $year,
        public string  $releaseType,
        public string  $version,
        public int     $prodId,
        public array   $files = [],
        public array   $authors = [],
    )
    {
    }
}
