# File Model

## Role

`FileRecord` is the local model of a downloadable archive payload attached to a release. It is the object that eventually becomes a file or extracted directory inside `files/`.

## Relations

- many `file` belong to one `release`
- one `file` can have many `file_path` rows
- `release.id <- file.zxReleaseId`
- `file.id <- file_path.fileId`

This many-path design exists because one logical ZX-Art file may be materialized in multiple archive locations.

## Fields

### `id: int`

ZX-Art file identifier. Primary key in `files`.

### `zxReleaseId: int`

Foreign key to parent release.

### `md5: string`

Expected checksum from the API. Used by `DownloadService` to validate downloaded content.

### `type: string`

File type / effective format marker from the API. Used by naming logic for media grouping.

### `originalFileName: string`

Original ZX-Art filename. Used for:

- extension extraction
- language detection from filename
- side/part detection for multi-file media labels

### `fileName: ?string`

Generated final TOSEC-style filename currently assigned to the file. This is local derived data, not the upstream source name.

### `filePaths: FilePathRecord[]`

Current resolved archive locations for this file. Can contain several paths when the same file is copied to multiple hardware folders.

`getFilePaths()` returns only the string path list.

## File path model

`FilePathRecord` fields:

- `id`: UUID primary key
- `fileId`: parent file id
- `filePath`: relative archive path

These are relative to the archive base path configured in DI, currently `files/`.

## Where it is used

- `ZxReleasesSyncService` creates and updates file metadata rows
- `ZxReleaseFilesChecker` decides final name and paths for each file
- `DownloadService` downloads and validates actual content
- `ArchiveExtractionService` may replace an archive file path with an extracted directory path
- `FileArchiveService` checks existence, renames paths, deletes content, and cleans directories
