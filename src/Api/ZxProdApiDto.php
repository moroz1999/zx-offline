<?php
declare(strict_types=1);


namespace App\Api;

final readonly class ZxProdApiDto
{
    /**
     * @param ZxCategoryDto[] $categories
     */
    public function __construct(
        public int     $id,
        public string  $title,
        public int     $dateModified,
        public ?int     $year,
        public ?string $legalStatus,
        public array   $categories = [],
    )
    {
    }
}