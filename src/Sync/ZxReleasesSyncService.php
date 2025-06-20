<?php
declare(strict_types=1);


namespace App\Sync;

use App\ZxReleases\ZxReleasesRepository;

final class ZxReleasesSyncService
{
    public function __construct(
        private ZxReleasesRepository $repository,
    )
    {
    }

    public function sync(): void
    {
        echo "ReleasesSyncService::sync() executed\n";
    }

    public function syncByProdId(int $id): void
    {

    }
}