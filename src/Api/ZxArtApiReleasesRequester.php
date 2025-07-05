<?php
declare(strict_types=1);

namespace App\Api;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final readonly class ZxArtApiReleasesRequester
{
//    private const BASE_URL = 'https://zxart.ee/api/language:eng/export:zxRelease/preset:offline/sortParameter:id/sortOrder:asc';
    private const PAGE_SIZE = 1000;
    private const BASE_URL = 'https://zxart.ee/api/language:eng/export:zxRelease/preset:offline/sortParameter:id/sortOrder:asc/filter:zxReleaseHardware=zx811';
//    private const BASE_URL = 'https://zxart.ee/api/language:eng/export:zxRelease/preset:offline/sortParameter:id/sortOrder:asc/filter:zxProdId=416165';

    public function __construct(
        private Client $client = new Client()
    )
    {
    }

    /**
     * @return Generator<ZxReleaseApiDto>
     * @throws ZxArtApiException
     */
    public function getAll(): Generator
    {
        $start = 0;
        $fetched = 0;
        $total = null;
        $debugLimit = null;
        $debugLimit = 1000;

        do {
            $url = self::BASE_URL . '/limit:' . self::PAGE_SIZE . '/start:' . $start;

            try {
                $response = $this->client->get($url);
                $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (GuzzleException|\JsonException $e) {
                throw new ZxArtApiException("API error: " . $e->getMessage(), previous: $e);
            }

            if (($data['responseStatus'] ?? '') !== 'success') {
                throw new ZxArtApiException('Unexpected API response status');
            }

            $releases = $data['responseData']['zxRelease'] ?? [];
            $total ??= $data['totalAmount'] ?? null;

            foreach ($releases as $item) {
                $fetched++;
                $files = [];
                foreach ($item['archiveFiles'] ?? [] as $file) {
                    $files[] = new FileApiDto(
                        id: (int)$file['id'],
                        md5: $file['md5'],
                        type: $file['type'],
                        fileName: $file['fileName'],
                    );
                }

                if (empty($files)) {
                    continue;
                }
                $publisherNames = array_map(
                    static fn(array $publisher) => $publisher['title'],
                    $item['publishersInfo'] ?? []
                );

                $allowedRoles = ['unknown', 'localization', 'release', 'restoring', 'adaptation'];
                $authorNames = array_map(
                    static fn(array $author) => $author['title'],
                    array_filter($item['authorsInfoShort'] ?? [], static function (array $author) use ($allowedRoles): bool {
                        $intersection = array_intersect($allowedRoles, $author['roles'] ?? []);
                        return !empty($intersection);
                    })
                );

                $allCredits = array_unique(array_merge($publisherNames, $authorNames));

                $publishers = $allCredits !== [] ? implode(', ', $allCredits) : null;
                yield new ZxReleaseApiDto(
                    id: (int)$item['id'],
                    title: $item['title'],
                    dateModified: (int)$item['dateModified'],
                    languages: isset($item['language']) ? implode(', ', $item['language']) : null,
                    publishers: $publishers,
                    year: isset($item['year']) ? (int)$item['year'] : null,
                    releaseType: (string)$item['releaseType'],
                    version: (string)($item['version'] ?? ''),
                    prodId: (int)$item['prodId'],
                    hardware: $item['hardwareRequired'] ?? null,
                    files: $files,
                );

                if ($fetched === $debugLimit) {
                    return;
                }
            }


            $start += self::PAGE_SIZE;
        } while ($total !== null && $fetched < $total);
    }
}
