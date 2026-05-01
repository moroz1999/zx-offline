# Architecture

## Overview

The codebase is organized as a CLI application with a small dependency injection container, a local SQLite storage layer, a task queue, synchronization services for ZX-Art entities, and an archive subsystem responsible for naming, folder resolution, download, extraction, and cleanup.

## Main layers

### CLI and bootstrap

- `cli.php` wires the Symfony Console application.
- `src/Bootstrap/ContainerFactory.php` builds the DI container.
- `src/config/di.php` configures paths, DB connection, logger, HTTP client, and key services.

### Commands

Symfony Console commands are thin entry points:

- `sync` queues product synchronization and starts the daemon
- `sync:releases` queues release synchronization and starts the daemon
- `retry` queues retries for failed files
- `resume` resumes pending queue work
- `run:daemon` drains the task queue
- `run:task` executes one task by id
- `reset` recreates the schema

### Task execution

- `TasksRepository` persists tasks in SQLite.
- `RunDaemonCommand` polls the next `todo` task and runs `php cli.php run:task <id>`.
- `TaskRunner` dispatches by task type into sync, retry, cleanup, and title-building services.

### Sync services

- `ZxProdsSyncService` syncs products and queues follow-up work.
- `ZxReleasesSyncService` syncs releases and file metadata, and handles deletions.
- `ZxReleaseFilesChecker` resolves final names/paths and ensures local archive files exist.

### Archive subsystem

- `TosecNameResolver` and `TosecNameFormatter` generate final filenames.
- `FileDirectoryResolver` computes folder paths by hardware, category, and title bucket.
- `DownloadService` downloads and verifies files.
- `ArchiveExtractionService` extracts supported archives safely.
- `FileArchiveService` manages filesystem paths, renames, deletions, and directory cleanup.

Detailed naming logic is documented separately in [TOSEC Name Resolver](./tosec-name-resolver.md).

### Persistence

- Doctrine DBAL is used directly, without ORM.
- Repositories exist for products, releases, files, file paths, and tasks.
- `SchemaService` creates and drops the SQLite schema.

## Data flow

1. Command adds a task.
2. Daemon reads the next task from SQLite.
3. `TaskRunner` executes the matching service method.
4. Sync services fetch ZX-Art pages through Guzzle requesters.
5. Repositories upsert metadata into SQLite.
6. File checker computes target names and directories.
7. Files are downloaded, optionally extracted, and registered in DB.

## Design characteristics

- No ORM abstractions: persistence is explicit and close to SQL.
- Queue is local and simple: single SQLite table with statuses.
- Naming and archive layout logic are isolated in dedicated services.
- Commands mostly orchestrate queue setup; heavy work is deferred to services.
- The output archive is deterministic relative to metadata and naming rules.

## Current technical notes from the code

- API requesters currently contain hardcoded `BASE_URL` filters for `zxProdId=109114`, which narrows sync scope. This is an implementation detail in the current code, not a domain requirement.
- `run:daemon` uses `passthru("php cli.php run:task ...")`, so task execution is delegated to child CLI invocations rather than handled in-process.
