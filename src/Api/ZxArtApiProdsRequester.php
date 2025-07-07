<?php
declare(strict_types=1);

namespace App\Api;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final readonly class ZxArtApiProdsRequester
{
//    private const BASE_URL = 'https://zxart.ee/api/language:eng/export:zxProd/preset:offline/sortParameter:id/sortOrder:asc';
    private const PAGE_SIZE = 1000;
    private const BASE_URL = 'https://zxart.ee/api/language:eng/export:zxProd/preset:offline/sortParameter:id/sortOrder:asc/filter:zxReleaseHardware=tsconf';
//    private const BASE_URL = 'https://zxart.ee/api/language:eng/export:zxProd/preset:offline/sortParameter:id/sortOrder:asc/filter:zxProdId=416165';
//    private const PAGE_SIZE = 10;

    public function __construct(
        private Client $client = new Client()
    )
    {
    }

    /**
     * @return Generator<ZxProdApiDto>
     * @throws ZxArtApiException
     */
    public function getAll(): Generator
    {
        $start = 0;
        $fetched = 0;
        $total = null;
        $debugLimit = null;
//        $debugLimit = 1000;

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

            $prods = $data['responseData']['zxProd'] ?? [];
            $total ??= $data['totalAmount'] ?? null;
            foreach ($prods as $item) {
                $fetched++;
                $categories = [];

                foreach ($item['rootCategoriesInfo'] ?? [] as $cat) {
                    $categories[] = new ZxCategoryDto(
                        id: (int)$cat['id'],
                        title: $cat['title'],
                    );
                }
                $groups = array_map(static fn(array $group) => $group['title'], $item['groupsInfo'] ?? []);
                $publishers = array_map(static fn(array $publisher) => $publisher['title'], $item['publishersInfo'] ?? []);

                $dtoPublishers = $publishers === [] ? $groups : [];

                yield new ZxProdApiDto(
                    id: (int)$item['id'],
                    title: $item['title'],
                    dateModified: (int)$item['dateModified'],
                    languages: isset($item['language']) ? implode(', ', $item['language']) : null,
                    publishers: $dtoPublishers !== [] ? implode(', ', $dtoPublishers) : null,
                    year: isset($item['year']) ? (int)$item['year'] : null,
                    legalStatus: $item['legalStatus'] ?? null,
                    categories: $categories,
                );
                if ($fetched === $debugLimit) {
                    return;
                }
            }

            $start += self::PAGE_SIZE;
        } while ($total !== null && $fetched < $total);
    }
}
