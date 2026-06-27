# #182 — provenance read-leak fix (completes #182)

**Date:** 2026-06-27 · ahgProvenancePlugin 1.2.2

Completes #182 (writes were fixed in v3.79.4). Closes the Class-1 read leak where
`view` / `timeline` / `provenanceDisplay` rendered provenance without respecting
`provenance_record.is_public` for anonymous/non-staff users (leaking Nazi-era /
cultural-property / POPIA notes).

## Fix
- New `maySeeNonPublicProvenance()` (auth + editor/administrator) + `provenanceHidden($prov)` helpers (mirror the `apiTrace` `is_public` check already in the plugin).
- `executeView` / `executeTimeline` → `forward404` when the record is non-public and the viewer isn't staff.
- `executeProvenanceDisplay` (embedded IO-view component) → blanks the provenance (`exists=false`) rather than 404, so the host page still renders.

## Verified
php -l clean; cache + php-fpm restart; `/provenance/trace/902722` (apiTrace) → 200, no 500s on the read paths. The gate reuses the same `record->is_public` field + staff predicate as the already-correct JSON trace endpoint.

#182 now fully resolved (writes v3.79.4 + reads here).
