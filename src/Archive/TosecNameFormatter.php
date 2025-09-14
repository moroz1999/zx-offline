<?php
declare(strict_types=1);

namespace App\Archive;

/**
 * Builds final filename from a DTO.
 */
final class TosecNameFormatter
{
    private const BASE_NAME_LIMIT = 64;

    public function toFilename(TosecNameDto $dto): string
    {
        return $this->toBaseName($dto) . $this->toExtras($dto);
    }

    public function toBaseName(TosecNameDto $dto): string
    {
        $parts = $this->buildBaseParts($dto);
        return implode('', $parts);
    }

    public function toExtras(TosecNameDto $dto): string
    {
        $parts = $this->buildExtraParts($dto);

        $dumpFlag = $this->buildDumpFlag($dto);
        if ($dumpFlag !== '') {
            $parts[] = $dumpFlag;
        }

        $parts[] = '.' . $dto->extension;

        return implode('', $parts);
    }

    private function buildBaseParts(TosecNameDto $dto): array
    {
        $parts = [];

        $title = mb_strlen($dto->title) > self::BASE_NAME_LIMIT
            ? mb_substr($dto->title, 0, self::BASE_NAME_LIMIT)
            : $dto->title;

        // keep original spacing contract: title with trailing space
        $parts[] = $title . ' ';

        if ($dto->version) {
            $parts[] = $dto->version;
        }

        if ($dto->isDemo) {
            $parts[] = '(demo)';
        }

        $parts[] = '(' . ($dto->productYear !== null ? (string)$dto->productYear : '19xx') . ')';
        $parts[] = '(' . $dto->publisher . ')';

        return $parts;
    }

    private function buildExtraParts(TosecNameDto $dto): array
    {
        $parts = [];

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

        return $parts;
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
