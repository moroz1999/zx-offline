# Documentation Index

## Sections

- [Domain](./domain.md)
- [Architecture](./architecture.md)
- [Runtime and Commands](./runtime.md)
- [Sync Pipeline](./sync-pipeline.md)
- [Task Queue](./tasks.md)
- [Database](./database.md)
- [Archive and Naming](./archive.md)
- [TOSEC Name Resolver](./tosec-name-resolver.md)
- [Prod Model](./prod.md)
- [Release Model](./release.md)
- [File Model](./file.md)

## Most Important

- The project is a CLI tool that builds a local offline archive from ZX-Art data and files.
- Main runtime stack: PHP 8.2, Symfony Console, PHP-DI, Doctrine DBAL, Guzzle, Monolog, SQLite.
- Entry point: `cli.php`.
- Main persistent state:
  - database: `storage/database.sqlite`
  - archive files: `files/`
  - logs: `logs/app.log`
- Main flow:
  - `sync` queues product sync
  - product sync updates local products and queues release sync
  - release sync updates releases and file metadata
  - file check/download builds final archive paths, downloads files, extracts supported archives, and stores file paths
