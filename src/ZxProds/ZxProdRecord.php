<?php
declare(strict_types=1);

namespace App\ZxProds;

final readonly class ZxProdRecord
{
    public function __construct(
        public int     $id,
        public string  $title,
        public string  $sanitizedTitle,
        public int     $dateModified,
        public ?string $languages,
        public ?string $publishers,
        public ?string $legalStatus,
        public ?int    $categoryId,
        public ?string $categoryTitle,
        public ?int    $year,
    )
    {
    }
}
