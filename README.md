# ZX Archive Processor

This project is a backend processor for organizing and maintaining a structured archive of ZX Spectrum (and related platforms) software releases, based on data imported from ZXArt API.

## Features

- **API Integration**: Loads releases and associated files from ZXArt.
- **Database Sync**: Stores products, releases, and file metadata locally with deduplication and normalization.
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

## CLI Usage

- php ./cli.php reset - Resets and reinitializes the database.
- php ./cli.php update - Starts synchronization and archive update from ZXArt API.