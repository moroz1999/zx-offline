# Prod Model

## Role

`ZxProdRecord` is the local model of a ZX-Art product. It is the parent entity for releases and the source of the archive base identity: title, category, legal status, and core metadata.

## Relations

- one `prod` has many `release`
- one `prod` indirectly has many `file` through releases
- `release.prodId -> prod.id`

## Fields

### `id: int`

ZX-Art product identifier. Primary key in `zx_prods`.

### `title: string`

Original product title from the API.

### `sanitizedTitle: string`

Normalized title used for archive naming and folder bucketing. This is the title that `TosecNameResolver` uses as the base filename title.

### `dateModified: int`

Upstream modification timestamp. Used to decide whether local data must be updated.

### `languages: ?string`

Comma-separated product language list from the API. Used as base language metadata and as fallback for release/file naming decisions.

### `publishers: ?string`

Comma-separated publisher or group string. Used as the base publisher in TOSEC naming.

### `legalStatus: ?string`

Status that drives archive semantics:

- may mark a file as public domain
- may force dump flags like `[p]` or `[a]`

### `categoryId: ?int`

ZX-Art category id. Mostly useful as catalog metadata.

### `categoryTitle: ?string`

Human-readable category title. Used in final directory layout:

`<platform>/<category>/<bucket>/<baseName>/`

### `year: ?int`

Base product year. Used in the formatted TOSEC base name.

## Where it is used

- `ZxProdsSyncService` maps API data into `ZxProdRecord`
- `ZxReleaseFilesChecker` passes it into naming and directory resolution
- `FileDirectoryResolver` uses category/title context
- `TosecNameResolver` uses title, languages, publishers, legal status, and year
