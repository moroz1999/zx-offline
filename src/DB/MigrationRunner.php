<?php
declare(strict_types=1);

namespace App\DB;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Version;
use App\DB\Migrations\Version1;

readonly class MigrationRunner
{
    public function __construct(
        private DependencyFactory $factory
    )
    {
    }

    public function migrateIfNeeded(): void
    {
        $migratorConfiguration = $this->factory->getConfiguration();
//        $plan = $this->factory
//            ->getMigrationPlanCalculator()
//            ->getPlanForVersions([], 'up');
//
//        if ($plan->getItems()) {
        // 4. Получение мигатора и планировщика
        $migrator = $this->factory->getMigrator();

        $planCalculator = $this->factory->getMigrationPlanCalculator();
        $versions = [Version1::class];

        $migrationsPlan = $planCalculator->getPlanForVersions(
            array_map(static fn(string $version): Version => new Version($version), $versions),
            'up'
        );

        $migratorConfiguration = (new MigratorConfiguration())
            ->setDryRun(false) // Отключаем пробный запуск
            ->setAllOrNothing(true); // Транзакционная миграция

        try {
            $migrator->migrate($migrationsPlan, $migratorConfiguration);
            echo "Миграции успешно выполнены!\n";
        } catch (\Exception $e) {
            echo "Ошибка при выполнении миграций: " . $e->getMessage() . "\n";
        }
//        }
    }
}
