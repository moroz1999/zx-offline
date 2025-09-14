<?php
declare(strict_types=1);

namespace App\Archive;

/**
 * DTO with every TOSEC filename component kept separately.
 */
final readonly class TosecNameDto
{
    public function __construct(
        // Base atoms
        public string  $title,           // sanitized, with article handling
        public ?string $version,         // sanitized or null
        public bool    $isDemo,
        public ?int    $productYear,     // null if unknown
        public ?string $publisher,       //
        public ?string $languages,       // uppercase or null
        public ?string $hardwareExtras,  // additional HW string or null
        public ?string $mediaPart,       // "(Disk 01 of 02)" etc., or null
        public bool    $isPublicDomain,  // PD flag

        // Dump flag atoms
        public ?string $dumpFlagCode,    // e.g. "p", "a", "h", "tr", "b" or null
        public int     $duplicateIndex,  // raw index passed in
        public ?string $dumpLanguages,   // uppercase langs used in flag (for 'tr') or null
        public ?int    $dumpYear,        // year included in flag or null
        public ?string $dumpPublisher,   // sanitized publisher included in flag or null

        // File extension (lowercase, may be "")
        public string  $extension,
    )
    {
    }
}
