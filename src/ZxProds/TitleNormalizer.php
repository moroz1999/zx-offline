<?php
declare(strict_types=1);

namespace App\ZxProds;

final class TitleNormalizer
{
    /**
     * Normalize title to lowercase ASCII [a-z0-9] only.
     */
    public function normalize(string $title): string
    {
        $lower = mb_strtolower($title, 'UTF-8');
        $normalized = preg_replace('/[^a-z0-9]+/', '', $lower);

        if ($normalized === null) {
            return '';
        }

        return $normalized;
    }
}
