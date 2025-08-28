<?php
declare(strict_types=1);

namespace App\Archive;

/**
 * Builds final filename from a DTO.
 */
final class TosecNameFormatter
{
    public function toFilename(TosecNameDto $dto): string
    {
        $parts = [];

        $parts[] = $dto->title . ' ';

        if ($dto->version) {
            $parts[] = $dto->version;
        }

        if ($dto->isDemo) {
            $parts[] = '(demo)';
        }

        $parts[] = '(' . ($dto->productYear !== null ? (string)$dto->productYear : '19xx') . ')';
        $parts[] = '(' . $dto->publisher . ')';

        if ($dto->languages) {
            $parts[] = '(' . $dto->languages . ')';
        }

        if ($dto->hardwareExtras) {
            $parts[] = $dto->hardwareExtras;
        }

        if ($dto->mediaPart) {
            $parts[] = $dto->mediaPart;
        }

        if ($dto->isPublicDomain) {
            $parts[] = '(PD)';
        }

        $base = implode('', $parts);
        $dumpFlag = $this->buildDumpFlag($dto);

        return $base . $dumpFlag . '.' . $dto->extension;
    }

    private function buildDumpFlag(TosecNameDto $dto): string
    {
        if ($dto->dumpFlagCode === null) {
            return '';
        }

        $indexString = $dto->duplicateIndex > 1 ? (string)$dto->duplicateIndex : '';

        $parts = [];
        if ($dto->dumpFlagCode === 'tr' && $dto->dumpLanguages) {
            $parts[] = $dto->dumpLanguages;
        }
        if ($dto->dumpYear !== null) {
            $parts[] = (string)$dto->dumpYear;
        }
        if ($dto->dumpPublisher !== null) {
            $parts[] = $dto->dumpPublisher;
        }

        $tail = $parts ? ' ' . implode(' ', $parts) : '';
        return '[' . $dto->dumpFlagCode . $indexString . $tail . ']';
    }
}
