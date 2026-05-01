# TOSEC Name Resolver

## Purpose

`TosecNameResolver` builds a structured `TosecNameDto` from product, release, and file metadata. It does not format the final string directly. Its job is to decide which naming components should exist. `TosecNameFormatter` then turns that DTO into the final filename.

## Inputs

Method: `generateDto(ZxProdRecord $prod, ZxReleaseRecord $release, array $allFiles, FileRecord $fileDto, int $duplicateIndex = 0)`

Inputs have different roles:

- `prod` provides the base title, product year, legal status, base languages, and main publisher string.
- `release` provides release-specific version, type, publishers, languages, year, and hardware.
- `allFiles` is needed to detect multi-file groups and media numbering.
- `fileDto` provides the original filename and extension for per-file decisions.
- `duplicateIndex` is used when the same generated name already exists and a fallback dump suffix is needed.

## Output DTO fields

`TosecNameDto` contains:

- base atoms: `title`, `version`, `isDemo`, `productYear`, `publisher`, `languages`, `hardwareExtras`, `mediaPart`, `isPublicDomain`
- dump flag atoms: `dumpFlagCode`, `duplicateIndex`, `dumpLanguages`, `dumpYear`, `dumpPublisher`
- file extension: `extension`

## Main logic

### 1. Title and publisher base

- `title` comes from `prod.sanitizedTitle`
- `publisher` comes from sanitized `prod.publishers`
- `productYear` comes from `prod.year`, else formatter will render `19xx`

This means the stable base name is product-centric, not release-title-centric.

### 2. Version and demo flag

- `version` comes from sanitized `release.version`
- `isDemo` is `true` when `release.releaseType === 'demoversion'`

### 3. Language resolution

Language logic is split into two branches:

- for `localization`, `mod`, `adaptation`, `crack`, the resolver prefers product/release language logic rather than filename inference
- for other release types, it first tries to detect languages from `file.originalFileName`, then falls back to product/release languages

Normalization passes through `LanguageCodeRegistry`, removes duplicates, and uppercases codes.

### 4. Translation language detection

For `adaptation` and `localization`, `resolveTranslationLanguages()` compares release languages with base product languages.

- if release languages are empty, there is no translation flag payload
- if release and product language sets are equal, there is no translation flag payload
- if they differ, the release language set becomes `dumpLanguages`

This is used only for `[tr ...]`.

### 5. Hardware extras

`hardwareExtras` comes from `HardwarePlatformResolver::getAdditionalHardwareString($release)`.

This is where markers like `(128K)`, `(ULAPlus)`, `(GS)` and similar additions come from.

### 6. Media-part logic

If a release has more than one file, `makeMediaPart()` groups files by:

- media type group
- resolved language set
- normalized version

Media type groups are hardcoded:

- `disk`: `dsk`, `trd`, `scl`, `fdi`, `udi`, `td0`, `d80`, `mgt`, `opd`, `mbd`, `img`
- `tape`: `tzx`, `tap`, `mdr`, `p`, `o`
- `rom`: `bin`, `rom`, `spg`, `nex`, `snx`, `tar`
- `snapshot`: `sna`, `szx`, `dck`, `z80`, `slt`

Then it derives:

- numbered labels like `(Disk 1 of 2)` or `(Tape 01 of 12)`
- optional `Part N`
- optional `Side A` / `Side 1`

If the logical group has a single file, numbering is omitted and only `Part` / `Side` extras may remain.

### 7. Public domain marker

`isPublicDomain` becomes `true` when `prod.legalStatus` is `allowed` or `allowedzxart`.

Formatter then appends `(PD)`.

### 8. Dump flag code

`resolveDumpFlagCode()` chooses one of:

- `p` for forbidden / in-sales legal statuses
- `a` for `mia`, `recovered`, `unreleased`
- `h` for release types `crack`, `mod`, `adaptation`
- `tr` for `adaptation` or `localization` when translation languages are present
- `b` for `mia`, `corrupted`, `incomplete`
- `a` as duplicate fallback when `duplicateIndex > 0`

Important precedence:

- legal-status rules run first
- translation rule runs before generic release-type lookup
- duplicate fallback is used only when earlier rules did not choose a flag

### 9. Dump flag payload

The resolver also prepares the content that may go inside the dump flag:

- `dumpLanguages` only for `[tr ...]`
- `dumpYear` from `release.year`
- `dumpPublisher` from sanitized `release.publishers`

Formatter builds suffixes like:

- `[p 1991 Publisher]`
- `[tr EN, RU 1993 Publisher]`
- `[a2 1990 Publisher]`

The numeric suffix appears when `duplicateIndex > 1`.

### 10. Extension

The final extension is always taken from `file.originalFileName` and lowercased.

## Formatter contract

`TosecNameFormatter` renders the DTO as:

`<title> (<year>)(<publisher>)<version><demo><languages><hardware><media><PD><dumpFlag>.<ext>`

Base part always starts from product title, product year, and product publisher. Everything else is conditional.

## Why this matters for the archive

`ZxReleaseFilesChecker` uses the generated DTO and formatted name to:

- detect duplicate filenames
- compute stable archive paths
- rename already downloaded files when metadata changes

So `TosecNameResolver` is not cosmetic. It directly affects deduplication, archive layout, and file identity in local storage.
