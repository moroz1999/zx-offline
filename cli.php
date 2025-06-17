<?php


require __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\ContainerFactory;
use App\Commands\RunTaskCommand;
use App\Commands\RunDaemonCommand;
use App\Commands\UpdateCommand;
use Symfony\Component\Console\Application;

$container = ContainerFactory::create();

$app = new Application('Archive CLI');

$app->add($container->get(UpdateCommand::class));
$app->add($container->get(RunDaemonCommand::class));
$app->add($container->get(RunTaskCommand::class));

$app->run();
