<?php
declare(strict_types=1);

namespace App\Archive;

/**
 * Provides canonical 2-letter language codes and common 3-letter aliases.
 */
final class LanguageCodeRegistry
{
    /** @return string[] list of canonical 2-letter codes (lowercase) */
    public function canonicalTwoLetterCodes(): array
    {
        return [
            'be','bs','by','ca','cs','da','de','el','en','eo','es','eu','fi','fr','gl','hr',
            'hu','is','it','la','lt','lv','nl','no','pl','pt','ro','ru','sh','sk','sl','sr',
            'sv','tr','ua','he',
        ];
    }

    /**
     * Map of common 3-letter (and legacy) aliases to canonical 2-letter codes.
     * Extend as needed.
     *
     * @return array<string,string> alias(3) -> canonical(2)
     */
    public function threeToTwoAliases(): array
    {
        return [
            'eng' => 'en',
            'rus' => 'ru',
            'spa' => 'es',
            'esp' => 'es',
            'fra' => 'fr',
            'fre' => 'fr',
            'deu' => 'de',
            'ger' => 'de',
            'por' => 'pt',
            'pol' => 'pl',
            'ita' => 'it',
            'nld' => 'nl',
            'dut' => 'nl',
            'swe' => 'sv',
            'nor' => 'no',
            'dan' => 'da',
            'fin' => 'fi',
            'hun' => 'hu',
            'rom' => 'ro',
            'scr' => 'sh',
            'srp' => 'sr',
            'hrv' => 'hr',
            'slk' => 'sk',
            'slv' => 'sl',
            'lit' => 'lt',
            'lav' => 'lv',
            'eus' => 'eu',
            'cat' => 'ca',
            'glg' => 'gl',
            'ell' => 'el',
            'hbr' => 'he',
        ];
    }

    /**
     * Normalize token to canonical 2-letter code if supported; null otherwise.
     */
    public function normalize(string $token): ?string
    {
        $t = strtolower($token);

        // Accept exact 2-letter canonical
        if (in_array($t, $this->canonicalTwoLetterCodes(), true)) {
            return $t;
        }

        // Map 3-letter aliases to canonical 2-letter
        $aliases = $this->threeToTwoAliases();
        if (isset($aliases[$t])) {
            return $aliases[$t];
        }

        return null;
    }
}
