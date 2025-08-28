<?php
declare(strict_types=1);

namespace App\Archive;

/**
 * Detects one or multiple language codes from a filename using strict token matching.
 *
 * Rules:
 * - Lowercase, replace any non [a-z0-9] with spaces, split by whitespace.
 * - Only full-token matches, so "Crust" will NOT match "rus".
 * - Supports 2-letter canonical codes and 3-letter aliases mapped to 2-letter.
 * - Returns canonical 2-letter codes in UPPERCASE.
 * - Order of appearance preserved, duplicates removed.
 */
final class FilenameLanguageDetector
{
    public function __construct(
        private readonly LanguageCodeRegistry $registry
    ) {}

    /**
     * Returns all detected languages (UPPERCASE 2-letter), ordered, unique.
     *
     * @return list<string>
     */
    public function detectAll(string $originalFileName): array
    {
        $lower = strtolower($originalFileName);
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $lower) ?? $lower;
        $tokens = preg_split('/\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $result = [];

        foreach ($tokens as $token) {
            $code = $this->registry->normalize($token);
            if ($code === null) {
                continue;
            }
            $upper = strtoupper($code);
            if (isset($seen[$upper])) {
                continue;
            }
            $result[] = $upper;
        }

        return array_unique($result);
    }
}
