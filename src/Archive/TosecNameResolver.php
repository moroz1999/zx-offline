<?php
declare(strict_types=1);

namespace App\Archive;

use App\Files\FileRecord;
use App\ZxProds\ZxProdRecord;
use App\ZxReleases\ZxReleaseRecord;
use RuntimeException;

final class TosecNameResolver
{
    public function __construct(
        private readonly NameSanitizer            $nameSanitizer,
        private readonly HardwarePlatformResolver $hardwarePlatformResolver,
        private readonly FilenameLanguageDetector $filenameLanguageDetector,
    )
    {
    }

    private const FORMAT_GROUPS = [
        'disk' => ['dsk', 'trd', 'scl', 'fdi', 'udi', 'td0', 'd80', 'mgt', 'opd', 'mbd', 'img'],
        'tape' => ['tzx', 'tap', 'mdr', 'p', 'o'],
        'rom' => ['bin', 'rom', 'spg', 'nex', 'snx', 'tar'],
        'snapshot' => ['sna', 'szx', 'dck', 'z80', 'slt'],
    ];

    /**
     * Builds a DTO with all filename components. No string concatenation beyond original per-part formatting.
     */
    public function generateDto(
        ZxProdRecord    $prod,
        ZxReleaseRecord $release,
        array           $allFiles,
        FileRecord      $fileDto,
        int             $duplicateIndex = 0
    ): TosecNameDto
    {
        // Detect languages from filename (may be multiple)
        $detectedList = $this->filenameLanguageDetector->detectAll($fileDto->originalFileName); // list of "EN","RU",...

        // Base atoms
        $title = $this->nameSanitizer->sanitizeWithArticleHandling($prod->title);
        $version = $release->version ? $this->nameSanitizer->sanitize($release->version) : null;
        $isDemo = $release->releaseType === 'demoversion';
        $productYear = $prod->year ?: null;
        $publisher = $this->nameSanitizer->sanitize(trim($prod->publishers ?: '-'));

        // If filename has languages -> override prod/release languages
        $languages = !empty($detectedList)
            ? implode(', ', $detectedList)
            : $this->makeLanguages($prod, $release);

        $hardwareExtras = $this->hardwarePlatformResolver->getAdditionalHardwareString($release) ?: null;
        $mediaPart = count($allFiles) > 1 ? $this->makeMediaPart($allFiles, $fileDto) : null;
        $isPublicDomain = in_array($prod->legalStatus, ['allowed', 'allowedzxart'], true);
        $extension = strtolower(pathinfo($fileDto->originalFileName, PATHINFO_EXTENSION));

        // Dump flag atoms
        $dumpFlagCode = $this->resolveDumpFlagCode($prod, $release, $duplicateIndex);

        // For 'tr' flag, use detected list if present; otherwise fallback to release/prod
        $dumpLanguages = $dumpFlagCode === 'tr'
            ? (!empty($detectedList)
                ? implode(', ', $detectedList)
                : $this->resolveDumpLanguagesForFlag($dumpFlagCode, $prod, $release))
            : null;

        $dumpYear = $this->buildDumpYear($release);
        $dumpPublisher = $this->buildDumpPublisher($release);

        return new TosecNameDto(
            title: $title,
            version: $version,
            isDemo: $isDemo,
            productYear: $productYear,
            publisher: $publisher,
            languages: $languages,
            hardwareExtras: $hardwareExtras,
            mediaPart: $mediaPart,
            isPublicDomain: $isPublicDomain,
            dumpFlagCode: $dumpFlagCode,
            duplicateIndex: $duplicateIndex,
            dumpLanguages: $dumpLanguages,
            dumpYear: $dumpYear,
            dumpPublisher: $dumpPublisher,
            extension: $extension,
        );
    }

    /** Returns 'p','a','h','tr','b', or null when no flag. */
    private function resolveDumpFlagCode(ZxProdRecord $prod, ZxReleaseRecord $release, int $index): ?string
    {
        // Legal-status driven
        if (in_array($prod->legalStatus, ['forbidden', 'forbiddenzxart', 'insales'], true)) {
            return 'p';
        }
        if (in_array($prod->legalStatus, ['mia', 'recovered', 'unreleased'], true)) {
            return 'a';
        }

        // Release-type driven
        $roleBasedFlags = [
            'crack' => 'h',
            'mod' => 'h',
            'adaptation' => 'h',
            'localization' => 'tr',
            'rerelease' => 'a',
            'mia' => 'b',
            'corrupted' => 'b',
            'incomplete' => 'b',
        ];
        $code = $roleBasedFlags[$release->releaseType] ?? null;
        if ($code) {
            return $code;
        }

        // Duplicate index fallback from original logic
        if ($index > 0) {
            return 'a';
        }

        return null;
    }

    /** Uppercase languages for 'tr' flag; null otherwise. */
    private function resolveDumpLanguagesForFlag(?string $flagCode, ZxProdRecord $prod, ZxReleaseRecord $release): ?string
    {
        if ($flagCode !== 'tr') {
            return null;
        }
        $langs = trim($release->languages ?: $prod->languages ?: '');
        return $langs ? strtoupper($langs) : null;
    }

    /** Year included in dump flag, or null. */
    private function buildDumpYear(ZxReleaseRecord $release): ?int
    {
        return $release->year ?: null;
    }

    /** Publisher included in dump flag (sanitized), or null. */
    private function buildDumpPublisher(ZxReleaseRecord $release): ?string
    {
        return $release->publishers ? $this->nameSanitizer->sanitize($release->publishers) : null;
    }

    private function makeLanguages(ZxProdRecord $prod, ZxReleaseRecord $release): ?string
    {
        $langs = trim($release->languages ?: $prod->languages ?: '');
        if (!$langs) {
            return null;
        }
        if (in_array($release->releaseType, ['localization', 'mod', 'adaptation', 'crack'], true)) {
            $langs = $prod->languages ?: '';
        }
        return strtoupper($langs);
    }

    private function makeMediaPart(array $files, FileRecord $currentFile): string
    {
        $total = count($files);
        $position = null;

        foreach ($files as $index => $file) {
            if ($file->id === $currentFile->id) {
                $position = $index + 1;
                break;
            }
        }
        if ($position === null) {
            throw new RuntimeException("Current file not found in files list");
        }

        $side = $this->detectSideFromOriginalFileName($currentFile->originalFileName);
        $part = $this->detectPartFromOriginalFileName($currentFile->originalFileName);

        $group = $this->detectMediaType($currentFile->type);
        $label = match ($group) {
            'disk' => 'Disk',
            'tape' => 'Tape',
            default => 'File',
        };

        $base = $total < 10
            ? sprintf('%s %d of %d', $label, $position, $total)
            : sprintf('%s %02d of %02d', $label, $position, $total);

        $extra = [];

        if ($part !== null && $part !== '') {
            $extra[] = 'Part ' . $part;
        }
        if ($side !== null && $side !== '') {
            $extra[] = 'Side ' . strtoupper((string)$side);
        }

        $full = $extra ? $base . ', ' . implode(', ', $extra) : $base;

        return sprintf('(%s)', $full);
    }

    private function detectMediaType(string $extension): string
    {
        $ext = strtolower($extension);
        foreach (self::FORMAT_GROUPS as $group => $formats) {
            if (in_array($ext, $formats, true)) {
                return $group;
            }
        }
        return 'unknown';
    }

    private function detectSideFromOriginalFileName(?string $originalFileName): ?string
    {
        if (!$originalFileName) {
            return null;
        }

        // Side A/B
        if (preg_match('/Side\s*([A-Z])/i', $originalFileName, $m)) {
            return strtoupper($m[1]);
        }

        // Side 1/2
        if (preg_match('/Side\s*(\d+)/i', $originalFileName, $m)) {
            return $m[1];
        }


        return null;
    }

    private function detectPartFromOriginalFileName(?string $originalFileName): ?string
    {
        if (!$originalFileName) {
            return null;
        }

        // Part 1/2
        if (preg_match('/Part\s*(\d+)/i', $originalFileName, $m)) {
            return $m[1];
        }

        return null;
    }

}
