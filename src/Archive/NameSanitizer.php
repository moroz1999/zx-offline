<?php
declare(strict_types=1);

namespace App\Archive;

use App\Utils\Transliterator;

final class NameSanitizer
{
    public function __construct(
        private readonly Transliterator $transliterator,
    ) {
    }

    public function sanitize(string $raw): string
    {
        $raw = $this->transliterator->transliterate($raw);
        $raw = trim(preg_replace('/[\/\\\\:*?"<>|]/', '', $raw));
        return $raw;
    }

    public function sanitizeWithArticleHandling(string $raw): string
    {
        $sanitized = $this->sanitize($raw);
        if (preg_match('/^(The|A|Le|La|Les|Die|De)\s+(.*)$/i', $sanitized, $m)) {
            return "$m[2], $m[1]";
        }
        return $sanitized;
    }
}
