# ZX Offline

CLI tool for building and maintaining a local offline archive of ZX Spectrum and related releases from the ZXArt API.

## Features

- **API Integration**: Loads releases and associated files from ZXArt.
- **Local Database Sync**: Stores products, releases, and file metadata in a local SQLite database with deduplication and normalization.
- **TOSEC-style Naming**:
    - Cleans and transliterates titles and publisher names (handles Cyrillic, Polish, Czech, Spanish, etc.).
    - Builds names with flags like `[h]`, `[p]`, `[tr]`, etc., based on legal status and release type.
    - Handles versioning, language tags, media parts (e.g. Tape 1 of 2), and duplicate indexing.
- **Hardware-based Folder Structure**:
    - Organizes files into folders by hardware platform (e.g. `ZX Spectrum`, `ATM`, `TS-Conf`, `Next`, `Sam Coupe`, `ZX80`, etc.).
    - Further subfolders based on category, starting letter, and product title.
- **Additional Hardware Flags**:
    - Detects and adds `(GS)`, `(ULAPlus)`, `(KM8B)`, `(128K)`, `(+D)` tags in TOSEC names.
- **Automatic File Path Resolution**:
    - Generates clean, consistent, and deduplicated storage paths.
    - Includes support for multi-part releases and different formats (tape, disk, rom, snapshot, etc.).

## Runtime

- CLI-only project
- No web server
- No external database server
- Local database: `storage/database.sqlite`

## Docker

Install dependencies:

```bash
docker compose run --rm composer install
```

List available commands:

```bash
docker compose run --rm cli list
```

Run synchronization:

```bash
docker compose run --rm cli sync
docker compose run --rm cli sync:releases
```

Reset local database:

```bash
docker compose run --rm cli reset
```

Run a queued task or daemon:

```bash
docker compose run --rm cli run:task <id>
docker compose run --rm cli run:daemon
```

## Local PHP Usage

If PHP 8.2+ is installed locally, commands can also be run without Docker:

```bash
composer install
php ./cli.php list
php ./cli.php sync
php ./cli.php sync:releases
php ./cli.php reset
```
