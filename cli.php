<?php


require __DIR__ . '/vendor/autoload.php';

$container = ContainerFactory::create();

$container->get(DatabaseServiceProvider::class);

use App\DB\DatabaseServiceProvider;
use App\Commands\RunTaskCommand;
use App\Commands\RunWorkerCommand;
use Symfony\Component\Console\Application;

$app = new Application('Archive CLI');

$app->add($container->get(RunWorkerCommand::class));
$app->add($container->get(RunTaskCommand::class));

$app->run();
