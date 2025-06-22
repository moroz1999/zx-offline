<?php
declare(strict_types=1);

namespace App\Api;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final readonly class ZxArtApiProdsRequester
{
    private const BASE_URL = 'https://zxart.ee/api/language:eng/export:zxProd/preset:offline/sortParameter:id/sortOrder:asc';
//    private const PAGE_SIZE = 1000;
    private const PAGE_SIZE = 10;

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
            $total = 30;
            foreach ($prods as $item) {
                $categories = [];

                foreach ($item['categoriesInfo'] ?? [] as $cat) {
                    $categories[] = new ZxCategoryDto(
                        id: (int)$cat['id'],
                        title: $cat['title'],
                    );
                }

                yield new ZxProdApiDto(
                    id: (int)$item['id'],
                    title: $item['title'],
                    dateModified: (int)$item['dateModified'],
                    year: isset($item['year']) ? (int)$item['year'] : null,
                    legalStatus: $item['legalStatus'] ?? null,
                    categories: $categories,
                );

            }
            $fetched += count($prods);

            $start += self::PAGE_SIZE;
        } while ($total !== null && $fetched < $total);
    }
}
