<?php
declare(strict_types=1);

namespace App\ZxProds;

final readonly class ZxProdRecord
{
    public function __construct(
        public int     $id,
        public string  $title,
        public int     $dateModified,
        public ?string $legalStatus,
        public ?int    $categoryId,
        public ?string $categoryTitle,
        public ?int    $year,
    )
    {
    }
}
