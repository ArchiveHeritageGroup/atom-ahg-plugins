# Researcher Offline + Portable Export — full session summary (final state)

**Date:** 2026-07-03 → 2026-07-04
**Repos:** atom-ahg-plugins (v3.79.55 → v3.79.64) + atom-extensions-catalog (v1.12.9 → v1.12.11)
**Instances:** archive/PSIS + archaeology (all changes mirrored, md5 MATCH)
**Scope rule:** all work in the archive folder (this session's own instance) + archaeology mirror only — NOT heratio/heratio-dev/dam.

## A. Researcher Offline (ahgResearchPlugin — user-unlocked)
A researcher takes THEIR OWN collected records offline, browses/annotates with no
internet on any device, and syncs their work back. Distinct from Portable Export
(not a reuse). Extends the plugin's existing offline sync engine (OfflineSyncService).

### Model = downloadable package + upload-to-sync (chosen after a PWA version was rejected)
`/research/mobile` ("Work Offline"):
- **Take offline (select sources):** tick Collections / Projects / Favourites folders
  (grouped nav-style with count badges) + **Search & add records** (catalogue search,
  ACL-scoped) + include-notes toggle → **Download offline package** (synchronous build,
  no queue → can't hang). `ResearchOfflinePackageService` builds a self-contained ZIP:
  `index.html` viewer (data inlined as `data.js` so it runs from file://), thumbnails in
  `objects/`, and a `README.txt` with instructions.
  - Project records come from BOTH `research_project_resource` AND
    `research_clipboard_project` (the "add to project" table) — counting only the former
    showed "(0)". Fixed.
  - ACL: `SearchAccessFilterService::getRestrictedObjectIds($userId)` (fail-closed) +
    published-only. Only records the researcher may see.
- **Offline viewer:** left list **grouped by source** (Collections/Projects/Favourites/
  Search → group name + count), live search, per-record thumbnail + key ISAD fields, and
  capture tabs **Note / Source / Suggestion / File** (file ≤ 5 MB). "Save for sync" →
  `researcher-sync.json` (a `queue[]`; attachments embedded base64 so it's self-contained).
- **Bring back:** one-click **Upload & sync** (button opens the .json picker, auto-submits)
  → `OfflineSyncService::applyQueue` kinds: annotation(note)/source→research_annotation,
  metadata_suggestion→research_metadata_suggestion (review queue), file→research_offline_attachment
  (uploads/research-offline/<rid>/), collection_item_note→research_collection_item.notes.
  Server-resolved researcher_id, never the payload. Skip reasons surfaced on the page.
- **Curator review queue:** admin-only `/research/metadata-suggestions`
  (Research → Administration → Metadata Suggestions) — Open/Accepted/Rejected tabs,
  Accept/Reject records reviewer; never a live catalogue edit.
- **Guard:** file applier checks the attachment folder is writable and throws a clear,
  actionable error (naming the chown fix) instead of a silent skip. (A stray root-owned
  uploads/research-offline — created by a CLI test run as root — had blocked one upload;
  chowned to www-data.)
- **DB:** `migration_researcher_offline.sql` (research_metadata_suggestion +
  research_offline_attachment), applied to both DBs.
- **Docs:** Researcher User Guide §45 rewritten for this model (+ Search + grouping);
  in-app help re-ingested (ahgHelpPlugin help:import).

### Scope clarification (recorded)
The researcher package is autonomous but PERSONAL-scoped (their selection + notes,
thumbnails only, grouped-by-source, for offline annotation + sync-back). It is NOT a
full-archive app. The fully-autonomous whole-archive offline viewer is **Portable Export**
(viewer mode: hierarchy tree, FlexSearch index, digital objects). Left the researcher
package as is.

## B. Portable Export fixes (ahgPortableExportPlugin)
- **Hang:** launched into `ahg_queue_job` (QueueService) which has NO worker on these
  instances (the running `symfony jobs:worker` drains the legacy `job` table) → stuck
  `pending`. Fixed: `launchBackground`/`launchImportBackground` use `nohup php symfony
  portable:export` directly (no worker). Portable exports aren't AtoM jobs → they show on
  the /portable-export list, not /jobs.
- **Archive-mode crash:** `ArchiveExtractor` queried non-existent columns —
  `rights.object_id`/`act_id` (rights link via `relation` type 168; i18n cols are
  identifier_value/type/role, statute_* on `rights`) and `accession.source_of_acquisition_id`/
  `location_information` (those are on accession_i18n). Fixed both to the real schema +
  wrapped each entity extraction in try/catch so one bad table can't abort the export.
  Verified fonds archive export completes (691 desc/318 auth/650 DO/224 events/619 rel/
  4 accession/61 tax; rights=0 legit).

## Releases
atom-ahg-plugins: v3.79.55 (accession redirect) · .56 (researcher offline v1) · .57 (curator
queue) · .58 (portable-export hang+archive fix, researcher rebuild) · .59 (project-count/
thumbnails/upload-filter) · .60 (grouped selector) · .61 (grouped viewer) · .62 (attachment
guard) · .63 (Search & add) · .64 (one-click upload + README).
atom-extensions-catalog: v1.12.9–1.12.11 (portable-export + researcher-offline user guides).
