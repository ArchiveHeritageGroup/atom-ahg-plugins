# AHG IIIF Plugin

IIIF for AtoM 2.9 and 2.10. Manifests, collections, annotations, search, authorisation, OCR export and media handling.

Nothing in base AtoM is modified. No file under `apps/`, `lib/`, `vendor/` or `config/` is touched, and `ProjectConfiguration.class.php` stays as upstream ships it.

**Current version: 1.1.1**

## Specifications implemented

| Specification | Version | Notes |
|---|---|---|
| Presentation API | **3.0** | with a 2.1 path also served |
| Image API | **2 and 3** | both advertised on every canvas - `ImageService2` and `ImageService3`, `profile: level2` |
| Content Search | **2.0** | `SearchService2`, with autocomplete |
| Authorisation | **1.0 and 2.0** | `AuthAccessService2`, `AuthProbeService2` |
| Change Discovery | **1.0** | `OrderedCollection` activity stream |

Manifests carry both `@id`/`@type` and `id`/`type`, so a Presentation 2 client can read a Presentation 3 manifest.

## What a manifest contains

Emitted always: `@context`, `id`, `type`, `label`, `metadata`, `items`, `service`.

Emitted when the data exists:

| Property | Source |
|---|---|
| `summary` | scope and content, multi-language |
| `rights` | the rights table, mapped to a licence URI |
| `requiredStatement` | institution attribution, enriched with IPTC/XMP creator and copyright |
| `provider` | the repository, with logo and homepage |
| `homepage` | the AtoM description |
| `seeAlso` | the description as a machine-readable alternative |
| `thumbnail` | with dimensions |
| `structures` | ranges - a table of contents, grouped by file |
| `start` | which canvas to open on |
| `behavior` | `paged` for a multi-page file, `individuals` for separate images |
| `viewingDirection` | `right-to-left` for Arabic, Hebrew, Persian and Urdu |
| `navDate` | the creation date, for timeline browsers |
| `partOf` | the parent description |

`structures` is grouped by digital object: a multi-page TIFF becomes one range holding its pages, so a twelve-page letter and the envelope beside it are distinguishable. It is suppressed for a single image, and for a set of separate images where every range would hold exactly one canvas.

Manifests are cached for 24 hours, keyed on the object's digital objects, the culture **and the requesting host** - a manifest embeds absolute URLs, so one built for a given hostname is wrong for every other.

## Viewers

The plugin does not hardcode a viewer. `RendererRegistry` auto-discovers renderers from any enabled plugin's `lib/Renderers/*.php`, and the highest-priority match wins. Install [Seadragon](../ahgSeadragonPlugin), [Mirador](../ahgMiradorPlugin), both or neither; removing one leaves the rest working.

Where no viewer plugin is installed, the built-in renderer shows a flat image, and a manifest with no image service is still valid IIIF.

Viewer defaults are set at **Admin > Settings > Image viewers** rather than in code - navigator, rotation, flip, navigator position, cross-origin policy, zoom per click, tile retries, height, and the Mirador window and workspace options. A per-record option overrides them.

## Collections

Curated sets of items, published as IIIF Collection manifests. Managed at **Manage > IIIF collections** (`/manifest-collections`): create, reorder, add and remove items, and serve `manifest.json` for the set. A collection can be featured on the site homepage as a carousel.

## Annotations

Web Annotation bodies stored against a canvas and served with the manifest, with create, modify and list endpoints. OCR text is held per block and exported on request.

## Authorisation

The IIIF Auth flow, with access and probe services, tokens, per-repository and per-resource rules, and an access log.

**An image server needs its own check.** It reads files from disk and knows nothing about AtoM's access control, so every master under `uploads/r/` is retrievable through the IIIF endpoint by anyone who can form the path - and those paths appear in every manifest. The Cantaloupe delegate that closes this ships at `config/cantaloupe/delegates.rb`, with `config/cantaloupe/README.md` covering three ways it can look configured while doing nothing.

## Media

Audio and video handling beside the image work: a processing queue, transcription with speakers and chapters, snippets, coverage reporting and a settings screen at **Admin > Media settings**. Uses `ffmpeg` and `ffprobe` when present; the features stand aside when they are not.

## 3D

Model viewing through Google `model-viewer`, with reporting.

## Requirements

    AtoM              2.9 or 2.10
    PHP               8.1 or later
    MySQL             8.0
    ahgRuntimePlugin  2.14.1 or later, installed separately

Optional:

    An IIIF image server   deep zoom and tiling. Cantaloupe is what we run.
                           Manifests, viewers and OCR export work without one.
    ffmpeg, ffprobe        audio and video
    exiftool               embedded metadata
    tesseract              OCR

Setup guides, deliberately kept out of the download because your web server and image server are yours:

- [Setting up an IIIF image server](../docs/infrastructure/iiif-image-server.md)
- [nginx](../docs/infrastructure/nginx.md)

## Tables

`iiif_annotation`, `iiif_annotation_body`, `iiif_auth_access_log`, `iiif_auth_repository`, `iiif_auth_resource`, `iiif_auth_service`, `iiif_auth_token`, `iiif_collection`, `iiif_collection_i18n`, `iiif_collection_item`, `iiif_manifest_cache`, `iiif_ocr_block`, `iiif_ocr_text`, `iiif_viewer_settings`, `media_chapters`, `media_metadata`, `media_processing_queue`, `media_processor_settings`, `media_snippets`, `media_speakers`, `media_transcription`

## Not implemented

Stated because a gap you know about is cheaper than one you discover:

- `rendering` - the PDF or download alternative - is the last unemitted Presentation 3 property
- Nothing checks at runtime that an image server is reachable, so a misconfigured Cantaloupe is indistinguishable from none: manifests build, viewers load, images are flat, and nothing says why
- A conditional property that does not appear on a record and one that cannot be produced look identical from outside
- The right-to-left branch is unexercised by any material we hold

Licence: AGPL-3.0-or-later.
