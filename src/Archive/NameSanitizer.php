<?php
declare(strict_types=1);

namespace App\Archive;

use App\Utils\Transliterator;

final readonly class NameSanitizer
{
    private const BASE_NAME_LIMIT = 64;

    public function __construct(
        private Transliterator $transliterator,
    )
    {
    }

    public function sanitize(string $raw): string
    {
        $raw = $this->transliterator->transliterate($raw);

        $raw = mb_strlen($raw) > self::BASE_NAME_LIMIT
            ? mb_substr($raw, 0, self::BASE_NAME_LIMIT)
            : $raw;

        return trim(preg_replace('#[\/\\\\:*?"<>|]#', '', $raw));
    }

    public function sanitizeWithArticleHandling(string $raw): string
    {
        $sanitized = $this->sanitize($raw);
        if (preg_match('/^(The|A|Le|La|Les|Los|Die|De)\s+(.*)$/i', $sanitized, $m)) {
            return "$m[2], $m[1]";
        }
        return $sanitized;
    }
}
