<?php


require __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\ContainerFactory;
use App\Commands\ResetCommand;
use App\Commands\ResumeCommand;
use App\Commands\RetryCommand;
use App\Commands\RunTaskCommand;
use App\Commands\RunDaemonCommand;
use App\Commands\SyncCommand;
use App\Commands\SyncReleasesCommand;
use Symfony\Component\Console\Application;

$container = ContainerFactory::create();

$app = new Application('Archive CLI');

$app->add($container->get(ResumeCommand::class));
$app->add($container->get(RetryCommand::class));
$app->add($container->get(SyncCommand::class));
$app->add($container->get(RunDaemonCommand::class));
$app->add($container->get(RunTaskCommand::class));
$app->add($container->get(ResetCommand::class));
$app->add($container->get(SyncReleasesCommand::class));

$app->run();
