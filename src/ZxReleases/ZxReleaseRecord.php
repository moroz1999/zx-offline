<?php
declare(strict_types=1);

namespace App\ZxReleases;

final readonly class ZxReleaseRecord
{
    public function __construct(
        public int     $id,
        public string  $title,
        public int     $dateModified,
    )
    {
    }
}
