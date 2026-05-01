# Sync Pipeline

## End-to-end flow

### 1. Product sync

`ZxProdsSyncService::sync()` iterates products from `ZxArtApiProdsRequester`.

- creates missing products
- updates products when `dateModified` increased
- queues `check_prod_releases` for changed products
- deletes locally existing products that disappeared from the API

After full product sync, `TaskRunner` queues:

- `sync_releases`
- `build_titles`

### 2. Release sync

`ZxReleasesSyncService::sync()` iterates all releases from `ZxArtApiReleasesRequester`.

- creates missing releases
- updates changed releases
- queues `check_release_files` for new or changed releases
- syncs file metadata attached to each release
- queues deletion tasks for obsolete releases

There is also a targeted path `syncByProdId()` used when only one product changed.

### 3. File metadata sync

Within release sync, `syncFileRecords()` compares API files to local DB state.

- creates new file records
- updates md5/type changes
- deletes obsolete files through archive cleanup

### 4. File resolution and download

`ZxReleaseFilesChecker` handles each file of a release.

- resolves TOSEC DTO and final filename
- avoids duplicate names by incrementing duplicate index
- resolves one or more relative archive directories
- checks whether file already exists locally
- downloads from `https://zxart.ee/zxfile/id:<release>/fileId:<file>/`
- verifies md5
- extracts supported archives and removes source archive
- writes resulting file paths back to DB

## Deletion behavior

- obsolete products delete their releases
- obsolete releases delete their files
- obsolete files are removed from disk and from DB

## Failure and retry behavior

- `DownloadService` retries transient failures up to 5 times with a 3 second delay
- HTTP 404 and checksum mismatch are treated as fatal download failures
- `retryFailedFiles()` queues retries for files with empty stored path state
