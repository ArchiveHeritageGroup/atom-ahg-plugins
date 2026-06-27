# #181–#183 — HIGH security fixes: exhibition / provenance / condition

**Date:** 2026-06-27 · From the #178 audit.

## #181 ahgExhibitionPlugin — FIXED
**Was:** `exhibition` module had no auth on any mutating action (actor forged as
user 1) → anon CRUD of exhibitions/objects/sections/storylines/stops/events/checklists.
**Fix:** `preExecute()` gate over a deny-list of the 21 write actions (add/edit/
transition/*Object/*Section/*Storyline/*Stop/*Event/checklist*) → auth +
`hasCredential(['editor','administrator'])` else `forward('admin','secure')`. Public
reads (index/show + read-list/AJAX helpers) left open for visitors.
**Verified:** `/exhibition/add` + `/exhibition/1/edit` → 403 anon; `/exhibitions` +
`/exhibition/1` → 200 (public read preserved).
**Remaining (lesser):** `exhibitionSpace` module mutations are login-only (no ACL) —
any authed user can edit any space layout/sensor readings. Lower severity; noted on #181.

## #182 ahgProvenancePlugin — WRITES FIXED (read-leak remaining)
**Was:** `deleteDocument` fully unauth (DELETE + file unlink); edit/addEvent/deleteEvent
login-only (no ACL) despite POPIA/Nazi-era/cultural-property writes.
**Fix:** `preExecute()` gate on write actions (edit/deleteDocument/addEvent/deleteEvent)
→ editor/administrator. **Verified:** `/provenance/deleteDocument/1` → 403 anon.
**Remaining:** the Class-1 read leak — `view`/`timeline`/`provenanceDisplay` don't
respect `provenance_record.is_public` for anon (the JSON `apiTrace` does). Needs a
per-record public filter in those reads; #182 kept OPEN for it.

## #183 ahgConditionPlugin — FIXED
**Was:** unauth condition-check INSERT (photos?id=new) + login-only IDOR on
save/upload/delete/updateMeta/aiAssess (deletePhoto ignored userId) + unauth reads.
**Fix:** condition assessment is internal collection-care (no public intent) →
`preExecute()` whole-module gate to editor/administrator. Closes the unauth creates,
the login-only IDORs, and the read leaks in one point.
**Verified:** `/condition/listPhotos` + `/condition/photos` → 403 anon.

## Common
All gates: `parent::preExecute()` then credential check → `forward('admin','secure')`.
php -l clean; cache cleared + php-fpm restarted. Versions: ahgExhibitionPlugin 1.0.1,
ahgProvenancePlugin 1.2.1, ahgConditionPlugin 1.2.7.

Remaining #178 backlog: #182 read-leak (open), #184 (incl. LOCKED ahgLibraryPlugin),
#185 (3d-model/spectrum/marketplace).
