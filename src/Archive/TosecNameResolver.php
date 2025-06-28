<?php
declare(strict_types=1);

namespace App\Archive;

use App\Files\FileRecord;
use App\Utils\Transliterator;
use App\ZxProds\ZxProdRecord;
use App\ZxReleases\ZxReleaseRecord;

final class TosecNameResolver
{
    public function __construct(
        private Transliterator $transliterator,
    )
    {

    }

    private const FORMAT_GROUPS = [
        'disk' => ['dsk', 'trd', 'scl', 'fdi', 'udi', 'td0', 'd80', 'mgt', 'opd', 'mbd', 'img'],
        'tape' => ['tzx', 'tap', 'mdr', 'p', 'o'],
        'rom' => ['bin', 'rom', 'spg', 'nex', 'snx', 'tar'],
        'snapshot' => ['sna', 'szx', 'dck', 'z80', 'slt'],
    ];

    public function generateTosecName(
        ZxProdRecord    $prod,
        ZxReleaseRecord $release,
        array           $allFiles,
        FileRecord      $fileDto,
        int             $duplicateIndex = 0
    ): string
    {
        $baseName = $this->buildBaseName($prod, $release, $allFiles, $fileDto);
        $dumpFlag = $this->buildDumpFlag($prod, $release, $duplicateIndex);
        $ext = strtolower($fileDto->type);

        return $baseName . $dumpFlag . '.' . $ext;
    }

    private function buildBaseName(
        ZxProdRecord    $prod,
        ZxReleaseRecord $release,
        array           $files,
        FileRecord      $fileDto
    ): string
    {
        $parts = [];

        $parts[] = $this->makeTitle($prod->title);
        if ($release->version) {
            $parts[] = 'v' . $release->version;
        }
        if ($release->releaseType === 'demoversion') {
            $parts[] = '(demo)';
        }
        $parts[] = $this->makeProdYear($prod);
        $parts[] = $this->makePublisher($prod);
        $languagesFlag = $this->makeLanguages($prod, $release);
        if ($languagesFlag) {
            $parts[] = $languagesFlag;
        }
        if (count($files) > 1) {
            $parts[] = $this->makeMediaPart($files, $fileDto);
        }
        $copyright = $this->makeCopyright($prod);
        if ($copyright) {
            $parts[] = $copyright;
        }

        return implode('', $parts);
    }

    private function buildDumpFlag(ZxProdRecord $prod, ZxReleaseRecord $release, int $index): string
    {
        $flag = '';
        $indexString = $index > 1 ? $index : '';
        if (in_array($prod->legalStatus, ['forbidden', 'forbiddenzxart', 'insales'], true)) {
            $flag = '[p' . $indexString . ']';
        } elseif (in_array($prod->legalStatus, ['mia', 'recovered', 'unreleased'], true)) {
            $flag = '[a' . $indexString . ']';
        } elseif (in_array($release->releaseType, ['crack', 'mod', 'adaptation'], true)) {
            $sub = [];
            if ($release->year) {
                $sub[] = $release->year;
            }
            if ($release->publishers) {
                $sub[] = $release->publishers;
            }
            $flag = '[h' . $indexString . ($sub ? ' ' . implode(' ', $sub) : '') . ']';
        } elseif ($release->releaseType === 'localization') {
            $langs = trim($release->languages ?: $prod->languages ?: '');
            if ($langs) {
                $flag = '[tr' . $indexString . ' ' . $langs . ']';
            }
        } elseif ($release->releaseType === 'rerelease') {
            $flag = '[a' . $indexString . ']';
        } elseif (in_array($release->releaseType, ['mia', 'corrupted', 'incomplete'], true)) {
            $flag = '[b' . $indexString . ']';
        }
        if ($flag === '' && $index > 0) {
            $flag = '[a' . $indexString . ']';
        }
        return $flag;
    }

    private function makeTitle(string $title): string
    {
        $title = $this->transliterator->transliterate($title);
        $title = trim(preg_replace('/[\/\\\\:*?"<>|]/', '', $title));
        if (preg_match('/^(The|A|Le|La|Les|Die|De)\s+(.*)$/i', $title, $m)) {
            return "{$m[2]}, {$m[1]} ";
        }
        return $title . ' ';
    }

    private function makeProdYear(ZxProdRecord $prod): string
    {
        return $prod->year ? "({$prod->year})" : '(19xx)';
    }

    private function makePublisher(ZxProdRecord $prod): string
    {
        $publisher = trim($prod->publishers ?: '-');
        return "({$publisher})";
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
        return "({$langs})";
    }

    private function makeCopyright(ZxProdRecord $prod): ?string
    {
        return match ($prod->legalStatus) {
            'allowed', 'allowedzxart' => '(PD)',
            default => null,
        };
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
            throw new \RuntimeException("Current file not found in files list");
        }

        $group = $this->detectMediaType($currentFile->type);
        $label = match ($group) {
            'disk' => 'Disk',
            'tape' => 'Tape',
            default => 'File',
        };

        $mediaPart = $total < 10
            ? "({$label} {$position} of {$total})"
            : sprintf('(%s %02d of %02d)', $label, $position, $total);

        $sideInfo = $this->detectSideInfoFromOriginalFileName($currentFile->originalFileName);
        if ($sideInfo !== null) {
            $mediaPart .= " {$sideInfo}";
        }

        return $mediaPart;
    }

    private function detectSideInfoFromOriginalFileName(?string $originalFileName): ?string
    {
        if (!$originalFileName) {
            return null;
        }

        // Side A/B
        if (preg_match('/Side\s*([A-Z])/i', $originalFileName, $m)) {
            return "Side " . strtoupper($m[1]);
        }

        // Side 1/2
        if (preg_match('/Side\s*(\d+)/i', $originalFileName, $m)) {
            return "Side {$m[1]}";
        }

        // Part 1/2
        if (preg_match('/Part\s*(\d+)/i', $originalFileName, $m)) {
            return "Part {$m[1]}";
        }

        return null;
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
}
