# Database

## Storage engine

- SQLite via Doctrine DBAL
- file path: `storage/database.sqlite`

## Schema ownership

`SchemaService` creates and drops tables programmatically. There is no ORM migration flow actively used in the code path shown by the CLI commands.

## Main tables

### `tasks`

Queue table for pending and completed work.

### `zx_prods`

Cached ZX-Art products:

- id
- title
- sanitizedTitle
- date_modified
- languages
- publishers
- year
- legal_status
- category_id
- category_title

### `zx_releases`

Cached releases:

- id
- prod_id
- title
- release_type
- languages
- publishers
- hardware
- year
- version
- date_modified

### `files`

Release file metadata:

- id
- zx_release_id
- md5
- type
- original_file_name
- file_name
- file_path

### `file_paths`

Normalized one-to-many table for final archive paths:

- id
- file_id
- file_path

## Repository split

- `ZxProdsRepository`
- `ZxReleasesRepository`
- `FilesRepository`
- `FilePathsRepository`
- `TasksRepository`

The repositories use DBAL directly and map rows into record objects.

## Important observation

The schema still defines `files.file_path`, while the runtime code also uses the separate `file_paths` table and `FileRecord::filePaths`. Documentation should treat `file_paths` as the current multi-path model and `files.file_path` as legacy or transitional storage unless the code is simplified later.
