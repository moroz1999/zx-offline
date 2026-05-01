# Runtime and Commands

## Runtime model

- Execution mode: CLI only
- Language/runtime: PHP 8.2+
- Dependencies: Composer
- Optional containerized workflow: Docker Compose

## Important paths

- entry point: `cli.php`
- archive root: `files/`
- sqlite database: `storage/database.sqlite`
- log file: `logs/app.log`
- DI config: `src/config/di.php`

## Main commands

### `php cli.php sync`

Queues `sync_prods` and starts queue processing. This is the main end-to-end synchronization entry point.

### `php cli.php sync:releases`

Queues `sync_releases` directly and starts queue processing.

### `php cli.php retry`

Queues retry processing for files that have no final stored path.

### `php cli.php resume`

Continues processing tasks already present in the queue.

### `php cli.php run:daemon`

Drains the queue by repeatedly taking the oldest `todo` task.

### `php cli.php run:task <id>`

Executes one specific task.

### `php cli.php reset`

Drops and recreates the database schema.

## Startup wiring

`cli.php` resolves command objects from the DI container and registers them into a Symfony Console application named `Archive CLI`.

## Logging

- Monolog writes warnings and above to `logs/app.log`
- `LoggerHolder` can swap in `IoLogger` so commands also print to console during active CLI runs
