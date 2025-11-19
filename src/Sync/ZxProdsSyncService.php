<?php
declare(strict_types=1);

namespace App\Sync;

use App\Api\ZxArtApiProdsRequester;
use App\Api\ZxProdApiDto;
use App\Archive\NameSanitizer;
use App\Tasks\TasksRepository;
use App\Tasks\TaskTypes;
use App\ZxProds\ZxProdRecord;
use App\ZxProds\ZxProdsRepository;
use App\ZxReleases\ZxReleaseRecord;
use App\ZxReleases\ZxReleasesRepository;
use Psr\Log\LoggerInterface;

final readonly class ZxProdsSyncService
{
    public function __construct(
        private ZxArtApiProdsRequester $zxArtApiProdsRequester,
        private ZxProdsRepository      $zxProdsRepository,
        private TasksRepository        $tasksRepository,
        private LoggerInterface        $logger,
        private ZxReleasesRepository   $zxReleasesRepository,
        private ZxReleasesSyncService  $zxReleasesSyncService,
        private NameSanitizer          $nameSanitizer,
    )
    {
    }

    public function sync(): void
    {
        $existingIds = array_flip($this->zxProdsRepository->getAllIds());

        foreach ($this->zxArtApiProdsRequester->getAll() as $apiProd) {
            $record = $this->mapToRecord($apiProd);

            $existing = $this->zxProdsRepository->getById($record->id);
            unset($existingIds[$record->id]);

            if (!$existing) {
                $this->zxProdsRepository->create($record);
                $this->logger->info("Prod $record->id created");
                continue;
            }

            if ($record->dateModified > $existing->dateModified) {
                $this->zxProdsRepository->update($record);
                $this->tasksRepository->addTask(TaskTypes::check_prod_releases, (string)$record->id);
                $this->logger->info("Prod $record->id updated");
            }
        }

        foreach (array_keys($existingIds) as $obsoleteId) {
            $this->deleteProd($obsoleteId);
            $this->logger->info("Prod $obsoleteId deleted as removed from API");
        }
    }

    public function deleteProd(int $id): void
    {
        $releases = $this->zxReleasesRepository->getByProdId($id);
        array_map(fn(ZxReleaseRecord $release) => $this->zxReleasesSyncService->deleteRelease($release->id), $releases);

        $this->zxProdsRepository->delete($id);
        $this->logger->info("Prod $id deleted");
    }

    private function mapToRecord(ZxProdApiDto $dto): ZxProdRecord
    {
        $cat = $dto->categories[0] ?? null;

        return new ZxProdRecord(
            id: $dto->id,
            title: $dto->title,
            sanitizedTitle: $this->nameSanitizer->sanitizeWithArticleHandling($dto->title),
            dateModified: $dto->dateModified,
            languages: $dto->languages,
            publishers: $dto->publishers,
            legalStatus: $dto->legalStatus,
            categoryId: $cat?->id,
            categoryTitle: $cat?->title,
            year: $dto->year,
        );
    }
}
