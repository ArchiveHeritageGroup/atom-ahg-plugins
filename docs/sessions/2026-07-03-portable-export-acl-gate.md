# Portable Export — role/security-scoped disclosure gate (per-user ACL)

**Date:** 2026-07-03
**Repo:** atom-ahg-plugins · **Release:** v3.79.50 (patch)
**Plugin:** ahgPortableExportPlugin
**Instances:** archive/PSIS + archaeology (both synced, md5 MATCH)

## Requirement
"The portable export must be role/security based. A user can only export what they
can see / have view rights to — like master copy exclusion."

## What was built
Layered a **per-user ACL gate** on top of the existing #1389 confidentiality
`DisclosureGate` (which already withheld unpublished / ICIP / ODRL / redacted).

- `DisclosureGate` gained a 4th `acl` withheld bucket + a `?int $aclUserId` ctor arg.
- `filter()` now also subtracts the exporting user's deny-set:
  `\AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()->getRestrictedObjectIds($userId)`
  — the same method that scopes that user's live search/browse results:
  security classification above clearance, donor-closed items
  (closure/permission-only/time-embargo/POPIA/legal-hold), and active **full**
  embargoes (honouring that user's embargo exceptions). Administrators (acl group
  100) get an empty deny-set → unrestricted.
- Runs against the export row's **stored `user_id`**, so it is correct even though
  the export executes in a **session-less background task**.
- **Fails closed**: if a scoped user's ACL lookup throws, an `aclFailed` flag causes
  the entire scope to be withheld — nothing leaks into an offline package (which,
  once written, is unrecoverable).
- ctor semantics: positive id = that user; `0` = anonymous/public baseline
  (`getRestrictedObjectIds(null)`); `null` (zero-arg) = per-user gating disabled
  (legacy path only).

### Wiring
- `CatalogueExtractor` (viewer mode) and `ArchiveExtractor` (archive mode) both take
  a 3rd `?int $aclUserId`, forwarded to `new DisclosureGate($aclUserId)`.
- `ExportPipelineService` passes `(int) $export->user_id` when constructing each
  extractor (both the viewer path and the archive path).
- Disclosure summary now includes `exported_by_user_id` + `withheld_total`; the
  export-list shield badge + tooltip surface the `acl` count.

### No DB migration
The `acl` count rides inside the existing `disclosure_summary` JSON column
(added earlier by `migration_disclosure_summary.sql`). No schema change.

## Verification
- `php -l` clean on all 4 services + template.
- Live gate run on archive: 738 → 691 kept (47 unpublished; `acl=0` because the
  archive DB currently has 0 active classifications / 0 full embargoes / 0 donor
  restrictions — nothing to withhold). Mechanism proven wired via the same
  `SearchAccessFilterService` that powers live search/browse ACL filtering; it will
  withhold the moment a record is classified/embargoed above the exporter's rights.
- Both instances: php-fpm restarted, `/portableExport` returns 301/302 auth-gate
  (no 500).

## Files
- ahgPortableExportPlugin/lib/Services/DisclosureGate.php
- ahgPortableExportPlugin/lib/Services/CatalogueExtractor.php
- ahgPortableExportPlugin/lib/Services/ArchiveExtractor.php
- ahgPortableExportPlugin/lib/Services/ExportPipelineService.php
- ahgPortableExportPlugin/modules/portableExport/templates/indexSuccess.php

## Open follow-up
"Folder/drive destination should also land on the operator's local PC/laptop"
(current folder mode writes to a server-side path/mount) — needs File System Access
API (Chrome/Edge only) or a plain ZIP download. Not yet done.
