<?php
declare(strict_types=1);

namespace App\Sync;

use App\Files\FileRecord;
use App\ZxProds\ZxProdRecord;
use App\ZxReleases\ZxReleaseRecord;

final class TosecNameResolver
{
    private const FORMAT_GROUPS = [
        'disk' => ['dsk', 'trd', 'scl', 'fdi', 'udi', 'td0', 'd80', 'mgt', 'opd', 'mbd', 'img'],
        'tape' => ['tzx', 'tap', 'mdr', 'p', 'o'],
        'rom' => ['bin', 'rom', 'spg', 'nex', 'snx', 'tar'],
        'snapshot' => ['sna', 'szx', 'dck', 'z80', 'slt'],
    ];

    public function generateFileName(
        ZxProdRecord    $prod,
        ZxReleaseRecord $release,
        array           $files,
        FileRecord      $fileDto
    ): string
    {
        $parts = [];

        $parts[] = $this->makeTitle($prod->title);
        if ($release->version) {
            $parts[] = "v{$release->version}";
        }
        if ($release->releaseType === 'demoversion') {
            $parts[] = '(demo)';
        }
        $parts[] = $this->makeProdYear($prod);
        $parts[] = $this->makePublisher($prod, $release);

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

        $dumpFlags = $this->makeDumpFlags($prod, $release);
        $name = implode('', $parts);
        if (!empty($dumpFlags)) {
            $name .= implode('', $dumpFlags);
        }

        $ext = strtolower($fileDto->type);
        return $name . '.' . $ext;
    }

    private function makeTitle(string $title): string
    {
        $title = trim(preg_replace('/[\/\\\:\*\?"<>\|]/', '', $title));
        if (preg_match('/^(The|A|Le|La|Les|Die|De)\s+(.*)$/i', $title, $m)) {
            return "{$m[2]}, {$m[1]} ";
        }
        return $title . ' ';
    }

    private function makeProdYear(ZxProdRecord $prod): string
    {
        return $prod->year ? "({$prod->year})" : '(19xx)';
    }

    private function makePublisher(ZxProdRecord $prod, ZxReleaseRecord $release): string
    {
        $publisher = trim($release->publishers ?: $prod->publishers ?: '-');
        return "({$publisher})";
    }

    private function makeLanguages(ZxProdRecord $prod, ZxReleaseRecord $release): ?string
    {
        $langs = trim($release->languages ?: $prod->languages ?: '');
        if (!$langs) {
            return null;
        }

        if (in_array($release->releaseType, ['localization', 'mod', 'adaptation', 'crack'], true)) {
            return null; // языки идут в [tr], не в ()
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

    private function makeDumpFlags(ZxProdRecord $prod, ZxReleaseRecord $release): array
    {
        $flags = [];

        if (in_array($prod->legalStatus, ['forbidden', 'forbiddenzxart', 'insales'], true)) {
            $flags[] = '[p]';
        }

        if (in_array($prod->legalStatus, ['mia', 'recovered', 'unreleased'], true)) {
            $flags[] = '[a]';
        }

        if (in_array($release->releaseType, ['crack', 'mod', 'adaptation'], true)) {
            $sub = [];
            if ($release->year) {
                $sub[] = $release->year;
            }
            if ($release->publishers) {
                $sub[] = $release->publishers;
            }
            $flags[] = '[h ' . implode(' ', $sub) . ']';
        }

        if ($release->releaseType === 'localization') {
            $langs = trim($release->languages ?: $prod->languages ?: '');
            if ($langs) {
                $flags[] = '[tr ' . $langs . ']';
            }
        }

        if ($release->releaseType === 'rerelease') {
            $flags[] = '[a]';
        }

        if (in_array($release->releaseType, ['mia', 'corrupted', 'incomplete'], true)) {
            $flags[] = '[b]';
        }

        return $flags;
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

        if ($total < 10) {
            return "({$label} {$position} of {$total})";
        }
        return sprintf('(%s %02d of %02d)', $label, $position, $total);
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
