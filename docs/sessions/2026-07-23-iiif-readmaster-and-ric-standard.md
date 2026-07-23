# 2026-07-23 - IIIF readMaster access control + RiC descriptive standard (plugins v3.82.0)

Two features shipped together in `atom-ahg-plugins` v3.82.0.

## 1. ahgIiifPlugin - IIIF manifests enforce the `readMaster` ACL

**Problem:** the IIIF manifest always built the Cantaloupe image from the **master** digital
object (usage 140), so anonymous users saw and could download the full-resolution original
through the viewer, "open in new window", fullscreen, and the download button.

**Rule (Johan):** not-logged-in users - and authenticated users without permission - must be
served the reference JPG/thumbnail, **not** the original.

**The rule is base AtoM's own `readMaster` ACL**, mirrored from
`QubitObject::getDigitalObjectPublicUrl` / `getDigitalObjectUrl` (`lib/model/QubitObject.php`):
the master is served only when `masterAccessibleViaUrl()` **and**
`QubitAcl::check($resource, 'readMaster')`; otherwise the reference. `readMaster` is granted to
editors/contributors, so **authentication alone is not enough** - a plain authenticated
researcher without `readMaster` correctly gets the reference.

**Implementation** (`modules/iiif/actions/actions.class.php`, both `generateManifest` v2 and
`generateManifestV3`):
- `userCanReadMaster(int $objectId)` - loads the record, checks `masterAccessibleViaUrl()` +
  `QubitAcl::check(..., 'readMaster')`; **fails closed** (serve reference) on any error.
- `applyMasterAccess($digitalObjects, $canMaster)` - when the user can't read the master, swaps
  each master for its reference child (usage 141, thumbnail 142 fallback); a master with no
  derivative is **dropped**, never exposed.
- All four surfaces (viewer, open-in-new-window, fullscreen, download) derive from that one
  manifest, so the single fix covers them all.
- Manifests are cached **per access tier** - version keys `v3-ref`/`v3-master` (and
  `v2-ref`/`v2-master`) → distinct `iiif_manifest_cache.culture` values (`en:v3-ref`, ...) - and
  the response is marked non-cacheable, so a public request can never receive a staff-cached
  master manifest.

**Deploy note:** purge the legacy untiered rows once -
`DELETE FROM iiif_manifest_cache WHERE culture IN ('en','en:v3')`.

**Verified** on both instances: sf230 (archaeology) and engelbrecht-family-bible (PSIS) anonymous
manifests serve `_141.jpg`, zero master leakage.

## 2. ahgRicManagePlugin - Records in Context (RiC-O) as a selectable descriptive standard (PSIS)

Makes **"Records in Context (RiC)"** selectable in the descriptive-standard picker alongside
ISAD(G)/RAD/DACS, and captures RiC data - on PSIS/archive (Symfony). Australian client: one MySQL
DB, no Fuseki/OpenRiC service. Record-centric now, extensible later.

**Architecture (leaner than the original plan - no base patch, no template clone, no
locked-plugin edits):**
- `php symfony ric:install` seeds a `ric` term in taxonomy 70 (information-object template) via
  the AtoM API. `InformationObjectCrudService::getDisplayStandards()` reads taxonomy 70
  dynamically, so the term alone makes RiC appear in the selector. A RiC record renders through
  the normal ISAD theme template (full parity); the route falls back to isad for the `ric` code.
- RiC capture is a **display panel** (`extension.json` `display_panels`, context
  informationobject) - the theme's ISAD `indexSuccess.php` already calls
  `ahg_render_display_panels()`, so the panel renders on RiC records automatically. Gated to show
  only for RiC-standard records (or records that already have RiC metadata).
- Captures RiC-O **entity type** + record-centric **properties** (own table `ric_record_meta`),
  displays **typed RiC relations** (existing `relation` + `ric_relation_meta`; the
  `ric_relation_type` dropdown = 30 RiC-O predicates), and exports **RiC-O JSON-LD**
  (`/ricManage/export/:id`, MySQL-sourced). Inline edit via AJAX `/ricManage/save` (editor
  credential; the theme CSRF fetch-shim supplies the token). Relation *creation* from the panel
  is the documented v1.1 extension.

**`ric:install` gotchas:** seeding a term via `QubitTerm::createTerm()` in a task needs
`sfContext::createInstance($this->configuration)` (cli env, prod cache untouched) or it throws
"default context does not exist"; and `QubitSearch::disable()` before save (cli has no search
config → arOpenSearch fatals on a null language list). Same pattern as the ahgLibrary/ahgIngest
tasks.

**Enabled on PSIS/archive only** (atom_plugin row + symlink + term id 912688 + `ric_record_meta`).
Export verified HTTP 200 `application/ld+json`. **Archaeology intentionally excluded** ("ignore
Archaeological"); the plugin stays inert anywhere it is not enabled + seeded.

## Release
- `atom-ahg-plugins` v3.82.0 (both features).
