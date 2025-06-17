<?php
declare(strict_types=1);

namespace App\Api;

final readonly class ZxCategoryDto
{
    public function __construct(
        public int    $id,
        public string $title,
    )
    {
    }
}
