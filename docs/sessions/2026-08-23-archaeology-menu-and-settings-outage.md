# Archaeology outage: deleted base menus, and a settings screen that zeroed itself

Date: 2026-08-23
Instance: archaeology.theahg.co.za (192.168.0.131, db `atom`)
Releases: atom-framework v2.18.7, v2.18.8, v2.18.10 - atom-ahg-plugins v3.106.1, v3.106.2

## Reported

Three symptoms, reported as separate faults: "the site is totally broken, no menus", "the logo is
missing in the top nav", "my login profile = page not found", and later "i see no images" /
"the carousel images is missing".

They were two unrelated causes, not four.

## Cause 1 - a plugin enable deleted 42 base AtoM menu rows

`ExtensionManager::syncMenuEntries()` looked for an existing menu row matching a manifest entry by
name and path, and recorded the plugin as its owner so a re-enable would not duplicate it. But
adoption is not authorship. Base AtoM's own entries match plugin manifests often enough that the
plugin then owned rows it never created, and `releaseMenuRow()` deletes what a plugin owns.

The binary log showed 42 `DELETE FROM atom.menu` between 15:55:47 and 16:12:57, zero inserts, with
9,513 accompanying renumbering UPDATEs - the signature of one-row-at-a-time nested set maintenance.
67 rows before, 25 after. Losses: the entire `browse` and `add` menus, 3 import entries, 3 manage,
5 admin, 9 user/group ACL entries, 4 quick links, 3 clipboard, 2 institution-browse.

Recovery: ids, parents, names and paths came back out of `binlog.000012`; labels from PSIS (38 of
42, all cultures) and base AtoM's `data/fixtures/menus.yml` (the remaining 4). Note that InnoDB
cascade deletes are never written to the binlog, so `menu_i18n` could not be recovered from it.

Fix (v2.18.7): new `created_by_plugin` column separates created from adopted. `releaseMenuRow()`
deletes only what the plugin created, and where authorship cannot be proven it declines to delete.
Failing that way round is deliberate - a stray menu entry is cosmetic, a deleted base menu is an
outage.

PSIS was never exposed: it has no `atom_plugin_menu` table at all.

## Cause 2 - the settings screen zeroed the page elements

At 15:14:58 six toggles went `1 -> 0` in a single transaction: toggleDescription, toggleLogo,
toggleIoSlider, toggleLanguageMenu, toggleCopyrightFilter, toggleMaterialFilter. Twenty-eight
seconds earlier the AHG settings screens threw fatals. The form rendered with its checkboxes
unchecked because its own read path was failing, and saving wrote 0 across the board.

`toggleLogo=0` skips the whole `navbar-brand` block in `_header.php`. `toggleIoSlider=0` makes base
AtoM's imageflow component return `sfView::NONE` on its first line. One bug, two reported symptoms.

The underlying fatals, all now fixed:

- `SettingService::findAndSave()` did not exist. The port from `QubitSetting` kept the call sites
  verbatim but never carried the method across, so every screen that saved this way fataled on
  submit. Restored with QubitSetting's semantics (scope / createNew / sourceCulture / editable).
- `findingAidAction::getSetting()` declared `: string` and returned `getValue()`, which is null when
  the row has no value for that culture.
- `inventoryAction` - a multi-select returns a bare string on single selection; `?? []` guards only
  null, so `saveSerialized(array $value)` was a TypeError.
- `HtmlPurifierService` called `sfConfig::` inside a namespace, resolving to
  `AtomExtensions\Services\sfConfig`. A sweep found 9 more live instances across MigrateCommand,
  PageIndexService, ThreeDAutoConfigService, Model3DService and WatermarkService.

## `setting` is NOT object-derived - and the delete that proved it dangerous

v2.18.8 "fixed" the duplicate-key error by allocating setting ids from the `object` table, on the
evidence that `object` 5692 carried `class_name = QubitSetting`. That was wrong, and the row was an
artefact of the very bug being chased.

`setting` is standalone: `schema.yml` declares no object parent, `BaseSetting` does not extend the
object base, `setting.id` has no foreign key to `object`, and base AtoM writes no object row when
saving a setting. Of the settings whose id matches an object, 96 are terms and 50 taxonomies - pure
numeric coincidence.

Four sites had the inverted assumption. The worst was pre-existing:
`StandaloneSettingsWriteService::delete()` also deleted the `object` row sharing the setting's id -
destroying an unrelated term, taxonomy or description. `SectorIdentifierService::bootstrapCounter()`
asserted "CTI: object('QubitSetting') -> setting" in its docblock and is the likely origin of
setting ids in the 5000s here and past 900,000 on PSIS.

Fixed in v2.18.10; verified by CLI probe inside a rolled-back transaction: create allocated id 5701
(= MAX+1 from setting's own sequence), `setting` grew 222->223 while `object` stayed at 910.

## Gotchas worth keeping

- `information_schema.TABLES.AUTO_INCREMENT` and `SHOW TABLE STATUS` are both served from a
  24-hour cache (`information_schema_stats_expiry=86400`). Both reported 199 against MAX(id)=5700,
  which looked exactly like counter drift. `SET SESSION information_schema_stats_expiry=0` gave the
  true value, 5701. There was no drift, and the ALTER proposed to "repair" it would have been a
  no-op against a base AtoM table - which is forbidden anyway.
- AtoM ships `America/Vancouver`, so `ahg_error_log` timestamps run 7 hours behind the DB server.
  Errors that read as "08:14 this morning" were 15:14 UTC - the same minute as the settings damage.
  Correlating an application log with a binlog requires converting first.
- `setting_i18n` row images in the binlog carry only value/id/culture, never the setting name.
  Grepping the binlog for `toggleLogo` finds nothing even when that setting was just written.
- AtoM serves its login page as HTTP 200. Six settings URLs returning 200 proved nothing; the body
  class was `user login` and the code under test never ran.
- A single-plugin sync carries every change to that plugin. Syncing ahgAccessRequestPlugin to
  archaeology would have shipped `data-ahg-confirm` shims whose binder (ahgCorePlugin) is not
  deployed there, silently disabling a destructive-action confirm.

## Still open

- `approversSuccess.php` on archaeology keeps the old `user/<slug>` breadcrumb, deliberately not
  synced for the reason above. Resolves when archaeology takes v3.106.x with ahgCorePlugin.
- Six stray `object` rows on archaeology (four on PSIS) claim `class_name = QubitSetting`. Inert;
  left in place.
