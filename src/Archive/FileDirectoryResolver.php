<?php
declare(strict_types=1);

namespace App\Archive;

use App\ZxProds\ZxProdRecord;

final readonly class FileDirectoryResolver
{
    public function __construct(
    )
    {
    }

    public function resolve(ZxProdRecord $zxProdRecord, string $fileName): string
    {
        $category = $zxProdRecord->categoryTitle ?: 'Misc';

        $firstChar = mb_strtoupper(mb_substr($fileName, 0, 1));

        if (preg_match('/[A-Z]/', $firstChar)) {
            $letter = $firstChar;
        } elseif (preg_match('/[0-9]/', $firstChar)) {
            $letter = '0-9';
        } else {
            $letter = 'other';
        }

        return $category . DIRECTORY_SEPARATOR . $letter . DIRECTORY_SEPARATOR;
    }
}
