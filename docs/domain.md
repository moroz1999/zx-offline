# Domain

## What the project does

`zx-offline` builds and maintains a local offline copy of ZX Spectrum related releases sourced from the ZX-Art API. The result is not just a dump of downloaded files. The project normalizes metadata, generates TOSEC-like names, lays out files into a stable folder tree, and keeps local state in sync with upstream changes.

## Core domain entities

### Product

Product (`zx_prod`) is the top-level work from ZX-Art: game, demo, utility, or another catalog item. Stored fields include title, sanitized title, modification timestamp, languages, publishers, legal status, category, and year.

### Release

Release (`zx_release`) is a concrete publication or variant of a product. It carries release-specific metadata such as release type, version, hardware requirements, languages, publishers, and year.

### File

File is a downloadable payload attached to a release. The system tracks the ZX-Art file id, md5 checksum, file type, original filename, generated archive name, and one or more final archive paths.

### Task

Task is a queued unit of work stored in SQLite. Tasks drive synchronization, file checks, retries, title bucket building, and cleanup of deleted releases/files.

## Business rules encoded in the project

- Product and release metadata are mirrored locally and updated by `dateModified`.
- Removed products/releases/files on ZX-Art are treated as obsolete and deleted locally.
- Final filenames are generated in a TOSEC-like format, not copied directly from ZX-Art.
- Legal status and release type influence dump flags such as `[p]`, `[a]`, `[h]`, `[tr]`, `[b]`.
- Hardware requirements influence both folder placement and additional filename markers like `(128K)` or `(ULAPlus)`.
- One file can be materialized into multiple archive locations when a release belongs to multiple hardware platforms.
- Supported archives are extracted after download; the original archive is removed on successful extraction.

## Boundaries

- This is a CLI-only project. There is no web UI or API server.
- The local database is SQLite, embedded in the workspace.
- The external system of record is ZX-Art.
- Filesystem layout under `files/` is part of the domain output, not just a cache.
