<?php
declare(strict_types=1);

namespace App\Archive;

use App\ZxProds\ZxProdRecord;
use App\ZxReleases\ZxReleaseRecord;

final class HardwarePlatformResolver
{
    private const PLATFORM_MAP = [
        'ZX Spectrum' => [
            'zx48', 'zx16', 'zx128', 'zx128+2', 'zx128+2b', 'zx128+3',
            'timex2048', 'timex2068', 'pentagon128', 'pentagon512', 'pentagon1024', 'pentagon2666',
            'profi', 'scorpion', 'scorpion1024', 'byte', 'zxmphoenix', 'zxuno',
            'alf', 'didaktik80',
        ],
        'Sprinter' => [
            'sprinter',
        ],
        'ZX Spectrum Next' => [
            'zxnext',
        ],
        'ATM' => [
            'atm', 'atm2', 'baseconf',
        ],
        'TS-Config' => [
            'tsconf',
        ],
        'ZX80' => [
            'zx80',
        ],
        'ZX81' => [
            'zx8116', 'zx811', 'zx812', 'zx8132', 'zx8164', 'lambda8300',
        ],
        'Sinclair QL' => [
            'sinclairql',
        ],
        'Sam Coupe' => [
            'samcoupe',
        ],
        'Element ZX' => [
            'elementzxmb',
        ],
    ];

    public function resolvePlatformFolder(ZxReleaseRecord $release): string
    {
        $hw = $release->hardware ?? [];

        foreach (self::PLATFORM_MAP as $platform => $hardwareSet) {
            foreach ($hw as $flag) {
                if (in_array($flag, $hardwareSet, true)) {
                    return $platform;
                }
            }
        }

        return 'ZX Spectrum';
    }

    public function getAdditionalHardwareString(ZxReleaseRecord $release): string
    {
        $hardware = $release->hardware ?? [];
        if (empty($hardware)) {
            return '';
        }

        $result = [];

        if ($this->isGs($hardware)) {
            $result[] = 'GS';
        }
        if ($this->isUlaPlus($hardware)) {
            $result[] = 'ULAPlus';
        }
        if ($this->isKempston8b($hardware)) {
            $result[] = 'KJ8b';
        }
        if ($this->is128k($hardware)) {
            $result[] = '128K';
        }
        if ($this->isPlusD($hardware)) {
            $result[] = '+D';
        }
        if ($this->isGmx($hardware)) {
            $result[] = 'GMX';
        }
        if ($this->isTurboSound($hardware)) {
            $result[] = 'TS';
        }
        if ($this->isKMouse($hardware)) {
            $result[] = 'KM';
        }

        return $result ? '(' . implode(', ', $result) . ')' : '';
    }

    private function isGs(array $hardware): bool
    {
        return in_array('gs', $hardware, true);
    }

    private function isUlaPlus(array $hardware): bool
    {
        return in_array('ulaplus', $hardware, true);
    }

    private function isKempston8b(array $hardware): bool
    {
        return in_array('kempston8b', $hardware, true);
    }

    private function is128k(array $hardware): bool
    {
        return array_intersect($hardware, [
                'zx128', 'zx128+2', 'zx128+2b', 'zx128+3',
                'pentagon128', 'pentagon512', 'pentagon1024',
                'scorpion1024',
            ]) !== [];
    }

    private function isPlusD(array $hardware): bool
    {
        return in_array('opd', $hardware, true);
    }

    private function isGmx(array $hardware): bool
    {
        return in_array('gmx', $hardware, true);
    }

    private function isTurboSound(array $hardware): bool
    {
        return in_array('ts', $hardware, true);
    }

    private function isKMouse(array $hardware): bool
    {
        return in_array('kempstonmouse', $hardware, true);
    }

    private function hasHardwareFlag(array $flags, ZxProdRecord $prod, ZxReleaseRecord $release): bool
    {
        $hw = $release->hardware ?? [];
        return (bool)array_intersect($flags, $hw);
    }
}
