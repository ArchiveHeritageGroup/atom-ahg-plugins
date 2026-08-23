# Fix: descriptions created without a publication status are unviewable by everyone

**Date:** 2026-08-23
**Issue:** atom-ahg-plugins#190
**Found by:** Johan, opening a seeded context on archaeology.theahg.co.za

## Symptom

```
sfException: No publication status set for information object id: 5664
QubitInformationObjectAcl.class.php:89
GET /index.php/context-2011-blb-2026
```

Logged twice in `ahg_error_log` on the archaeology instance.

## Cause

`QubitInformationObjectAcl::isReadAllowed()` does not tolerate a missing publication
status. It does not fall back to a default and it does not deny access - it **throws**:

```php
if (null === $resource->getPublicationStatus()) {
    throw new sfException('No publication status set for information object id: '.$resource->id);
}
```

So a description with no row in `status` (type_id 158, `STATUS_TYPE_PUBLICATION_ID`) is
unviewable by **everyone, including administrators**. It is not a permissions problem that
degrades; it is a hard failure before any permission is evaluated.

`StandaloneInformationObjectWriteService::createInformationObject()` does not create that
row - the same class of omission as it not setting `lft`/`rgt`. It builds
`object` -> `information_object` -> i18n and a slug, and stops there.

**This is the third thing that write service leaves undone.** The pattern is now clear
enough to state as a rule: a description created through the framework write service is not
a usable AtoM record until something adds the nested-set position, and the publication
status. Assume nothing else is done for you.

## Fix

`ArchaeologyService::setPublicationStatus()`, called from `createDescription()` inside the
same transaction as the insert and the nested-set splice. It follows the instance's own
`app_defaultPubStatus` and falls back to `PUBLICATION_STATUS_DRAFT_ID`, which is exactly
what AtoM's own `editAction` and `multiFileUploadAction` do.

Draft is also the right default on its own merits: a record created by a background process
should not become publicly visible because nobody said otherwise.

`ArchaeologyService::backfillPublicationStatus()` repairs existing records across
`archaeology_site`, `archaeology_context` and `archaeology_object`. Idempotent.

## Verified

- Backfilled **21** descriptions on archaeology (20 contexts + 1 site). Now 0 missing on
  both, all at status 159 (draft), matching that instance's `defaultPubStatus`.
- `GET /index.php/context-2011-blb-2026` anonymously: **403**, not a 500 exception. The ACL
  now evaluates instead of throwing, and correctly hides a draft record from an anonymous
  visitor. Signed in as an editor or administrator the record renders.
- archive/PSIS had no descriptions created yet, so nothing to backfill there; the code fix
  is in place for when it does.

## Note

The seeded `BLB-2026` scenario is **draft**, so it is invisible to anonymous visitors on a
public domain. That is deliberate. Publishing it is a separate, explicit decision.
