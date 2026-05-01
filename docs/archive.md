# Archive and Naming

## Archive layout

Final file placement is derived from:

- hardware platform
- product category
- title bucket
- sanitized base title

`FileDirectoryResolver` produces paths shaped like:

`<platform>/<category>/<bucket>/<baseName>/`

One release file may be copied into multiple platform folders if hardware resolution returns multiple targets.

## Filename generation

`TosecNameResolver` builds a structured DTO, and `TosecNameFormatter` turns it into the final filename.

Inputs include:

- product title and metadata
- release metadata
- file extension
- detected filename languages
- hardware extras
- media part numbering
- legal status and release type
- duplicate index

## Naming rules visible in code

- Titles and publishers are sanitized.
- Version is appended when present.
- Demo releases are marked.
- Product legal status and release type map to dump flags.
- Translation/adaptation releases may get `[tr]` with language codes.
- Multi-file releases can produce `(Disk 1 of 2)`, `(Tape 1 of 2)`, `Part`, and `Side` suffixes.
- Additional hardware markers can be appended, for example `(128K)` or `(ULAPlus)`.

## Download and integrity

`DownloadService`:

- downloads via Guzzle streaming
- verifies non-zero size
- verifies md5 when available
- copies the primary downloaded file to all resolved target paths

## Extraction

`ArchiveExtractionService` currently supports:

- zip
- tar and tar-like compressed variants through the tar extractor

Behavior:

- select extractor by extension
- guard against path traversal
- extract into directory named after archive basename
- flatten single nested subdirectory
- delete original archive after successful extraction

## Filesystem management

`FileArchiveService` is responsible for:

- ensuring directories exist
- checking if stored file paths exist
- renaming existing file paths when generated name changes
- deleting files or extracted directories
- cleaning empty parent directories
