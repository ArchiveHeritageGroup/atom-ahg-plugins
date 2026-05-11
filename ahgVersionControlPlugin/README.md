# ahgVersionControlPlugin

Version history with diff and restore for `information_object` and `actor` entities. Captures a full JSON snapshot on every save and exposes a "Versions" tab on the entity view/edit pages with side-by-side diff and a restore action.

## Status

Phase A — schema only. Service and UI phases land in subsequent build phases.

## Architecture

- `database/install.sql` — schema: two version tables, one per entity type, with `snapshot JSON + version_number + change_summary + is_restore + restored_from_version`.
- `lib/Services/` (later phases) — SnapshotBuilder, VersionWriter, DiffComputer, RestoreService.
- `lib/Listeners/` (later phases) — sfEventDispatcher listeners on `information_object.save` and `actor.save`.
- `modules/versionControl/` (later phases) — list, show, diff and restore actions + templates.

## Base AtoM impact

**Zero schema changes to base AtoM tables.** The version tables reference `information_object.id` and `actor.id` via FK as a read-only relationship; the base tables themselves are not altered.

## Reference

Build plan: `/usr/share/nginx/archive/uploads/F2 Build Plan - Version Control.md`
Design doc: `/usr/share/nginx/archive/uploads/AHG Feature Development Plan - Records Management.md`
