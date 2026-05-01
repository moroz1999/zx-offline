# Release Model

## Role

`ZxReleaseRecord` is the local model of a concrete release variant for a product. It carries release-specific metadata that changes naming, hardware placement, and file synchronization behavior.

## Relations

- many `release` belong to one `prod`
- one `release` has many `file`
- `prod.id <- release.prodId`
- `file.zxReleaseId -> release.id`

## Fields

### `id: int`

ZX-Art release identifier. Primary key in `zx_releases`.

### `prodId: int`

Foreign key to the parent product. Binds the release to product-level title/category/legal metadata.

### `title: string`

Release title from the API. Present as metadata, but the current TOSEC naming base is built from product title, not release title.

### `dateModified: int`

Upstream modification timestamp. Used to decide whether the release needs updating and whether file checks should be requeued.

### `languages: ?string`

Comma-separated release language list. Used in translation detection and file naming language resolution.

### `publishers: ?string`

Comma-separated release-level credits. Used in dump flag payload generation.

### `year: ?int`

Release-specific year. Used in dump flag payload rather than base product year.

### `releaseType: string`

One of the most important fields in the model. Influences:

- demo marker
- translation flag behavior
- dump flag selection such as `[h]`, `[tr]`, `[b]`
- language resolution branch

### `version: ?string`

Version string used in final filename extras and in media-group matching for multi-file releases.

### `hardware: ?array`

Hardware requirement list from the API. Used by hardware resolution to:

- choose target platform folders
- append hardware markers to the filename

## Where it is used

- `ZxReleasesSyncService` maps API data into `ZxReleaseRecord`
- `ZxReleaseFilesChecker` uses it for file sync
- `TosecNameResolver` uses release type, version, languages, publishers, year, hardware
- `FileDirectoryResolver` uses it through hardware platform resolution
