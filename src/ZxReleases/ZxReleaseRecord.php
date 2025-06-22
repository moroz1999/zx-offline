<?php
declare(strict_types=1);

namespace App\ZxReleases;

final readonly class ZxReleaseRecord
{
    public function __construct(
        public int     $id,
        public int     $prodId,
        public string  $title,
        public int     $dateModified,
        public ?int    $year,
        public string  $releaseType,
        public ?string $version,
    )
    {
    }
}
