# Shared IO — related material descriptions (type 173) for DACS/RAD

**Date:** 2026-06-14
**Repo:** ArchiveHeritageGroup/atom-ahg-plugins
**Targets:** v3.62.11

## What
Adds management of **related material descriptions** (IO→IO `relation`, `type_id = 173`) to the DACS and RAD descriptive-standard edit forms — closing the dacs (2) + rad (related-materials) parity gaps in one shared change. Fully AHG-side (dacs/rad edit actions are `extends AhgController` + `\IoFormHelper`; **no base AtoM touched**).

## Files
- `ahgInformationObjectManagePlugin/lib/Services/InformationObjectCrudService.php` — `const RELATION_RELATED_MATERIAL = 173`; `getById` reads existing type-173 relations into `relatedMaterials`; `create`/`update` sync them; `saveRelatedMaterials()` helper (mirrors `saveNameAccessPoints`).
- `ahgInformationObjectManagePlugin/lib/IoFormHelper.php` — `extractFormData()` reads `_relatedMaterialsIncluded` (presence flag) + `relatedMaterialDescriptionIds[]`; `getNewDefaults()` seeds `relatedMaterials => []`.
- `ahgDacsManagePlugin/.../editSuccess.php`, `ahgRadManagePlugin/.../editSuccess.php` — "Related material descriptions" section: lists linked descriptions with Keep/remove checkboxes + the `_relatedMaterialsIncluded` marker.

## Safety design (no regression)
The `_relatedMaterialsIncluded` marker is present **only** in the dacs/rad templates. `create`/`update` run the type-173 sync **only when that flag is set**, so every other IO save (IO-manage ISAD, mods, etc.) leaves type-173 relations untouched — existing IO editing is byte-identical. Relation create/delete reuse the already-proven `RelationService` calls.

## Verification
- All files PHP-lint clean.
- **Live smoke-test passed** (operator): (a) DACS/RAD edit shows linked descriptions, uncheck+save removes one and keeps the rest; (b) **regression check** — editing a normal IO via the ISAD form still saves correctly with relations untouched.

## Follow-up
- "Add new" related material via IO autocomplete (current form preserves/removes existing — matches Heratio's read-only-preserve behavior). 
- mods-manage event/publisher/date gaps (separate shared-IO sub-item).
