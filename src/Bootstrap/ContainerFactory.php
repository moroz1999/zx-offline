<?php
declare(strict_types=1);

namespace App\Bootstrap;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

final readonly class ContainerFactory
{
    public static function create(): ContainerInterface
    {
        $builder = new ContainerBuilder();

        $builder->addDefinitions(__DIR__ . '/../config/di.php');

        return $builder->build();
    }
}
