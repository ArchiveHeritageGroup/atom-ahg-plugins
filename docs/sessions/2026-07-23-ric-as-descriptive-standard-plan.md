# 2026-07-23 - PLAN: RiC as a selectable descriptive standard in AtoM (PSIS)

**Status:** PLAN ONLY (not built). Target: **PSIS / archive (Symfony)** - archaeology explicitly excluded.
Relates to `atom-ahg-plugins#189` (OpenRiC side) / sibling `heratio#1425` (`ahg-ric-manage`).

## Goal

A cataloguer editing an information object in PSIS can select **"Records in Context (RiC)"**
as the descriptive standard (alongside ISAD(G)/RAD/DACS/MODS/DC) and capture the record with
RiC-oriented fields + typed RiC relations. The record stays a normal `information_object`;
graph view + RiC-O export derive from MySQL.

## Locked decisions

1. **One MySQL DB, Fuseki optional, no OpenRiC service.** For the Australian client: single
   deployment, single DB. Fuseki/triplestore push (`RicSyncService`) stays gated off unless
   configured.
2. **Record-centric now, extensible later.** An AtoM `information_object` **is** a RiC Record;
   Agents/Places/Functions = existing `actor`/`term`/`function`, linked by typed RiC relations.
   Plugin structured (entity-type registry) so more RiC entity types add later without rework.
3. New plugin **`ahgRicManagePlugin`** (distinct from `ahgRicExplorerPlugin`: Explorer = graph/
   viz; Manage = capture/standard). Render via a new `sfRicPlugin` module.

## Pre-build check RESULTS

- **RiC relation infra already in archive MySQL:** `ric_relation_meta` (EXISTS), `ric_sync_config`
  (EXISTS), `ric_relation_type` = `ahg_dropdown` taxonomy with **30 terms**. -> Phase-2 relation
  storage is ready, one-DB. (No `ric_relation_type`/`ric_entity`/`ric_node`/`ric_edge` tables -
  relations live in `relation` + `ric_relation_meta`.)
- **Standards = `term` rows.** `IoFormHelper::detectStandard($displayStandardId)` maps a term id
  -> standard code via `$idToStandard`, falling back to the global `default_template` setting.
  `InformationObjectCrudService::getDisplayStandards()` builds the selector from the `term` table.
- **Render dispatch = base file.** `lib/routing/QubitMetadataRoute.class.php` holds a static
  `template -> module` map (`isad=>sfIsadPlugin, dc=>sfDcPlugin, rad=>sfRadPlugin,
  mods=>sfModsPlugin, dacs=>arDacsPlugin, ...`). Adding `ric=>sfRicPlugin` = a **NEW base patch**
  via the `atom-framework/patches/` convention (patches/ mirrors base; `bin/install` Step 11
  cp -f's onto base). NOT yet patched.

## File-by-file task list

### A. Base patch (sanctioned)
- `atom-framework/patches/lib/routing/QubitMetadataRoute.class.php` - copy base + add
  `'ric' => 'sfRicPlugin'` to the template->module map. `bin/install` applies it.

### B. New plugin `ahgRicManagePlugin`
- `extension.json`
- `config/ahgRicManagePluginConfiguration.class.php` - autoloader, enable `sfRicPlugin` module,
  routing, register the RiC standard on boot.
- `database/install.sql` - seed the **RiC standard `term`** (in the standards taxonomy) + i18n
  "Records in Context (RiC)"; seed any RiC entity-type dropdown; NO new relation table
  (`ric_relation_meta` already present).
- `lib/Services/RicManageService.php` - IO<->RiC Record field mapping; typed-relation capture via
  `relation` + `ric_relation_meta` + the `ric_relation_type` dropdown; **entity-type registry**
  (default `Record`) for extensibility; RiC-O JSON-LD export (MySQL-sourced, reuse OpenRiC
  serializer shape).
- `modules/sfRicPlugin/actions/actions.class.php` - `indexAction` (render a ric record),
  `editAction`/hooks for the RiC edit fields.
- `modules/sfRicPlugin/templates/indexSuccess.php` - RiC view: clone ISAD structure (header/
  elements/related-material/provenance/embargo gate) + RiC panel (entity type, RiC properties,
  typed relations from `ric_relation_meta`, "View in graph" -> `ahgRicExplorer`).

### C. Standard registration (edit + settings)
- `ahgInformationObjectManagePlugin/lib/IoFormHelper.php` - extend `$idToStandard` to map the new
  RiC term id -> `ric`.
- `ahgInformationObjectManagePlugin/lib/Services/InformationObjectCrudService.php` -
  `getDisplayStandards()` include the RiC term.
- RiC edit field set: when `displayStandard = ric`, render RiC fields + typed-relation editor
  (reuse the IO edit form + relation widgets, backed by `ric_relation_type` dropdown).
- `ahgSettingsPlugin` (`section.blade.php` / `sectionSuccess.php`, `NumberingFilter`) +
  `ahgThemeB5Plugin/.../multiFileUploadAction.class.php` - add `'ric'` to the standards arrays.

### D. Graph + export (MySQL-only)
- Reuse `ahgRicExplorer` `KnowledgeGraphService` (reads MySQL relations) - verify MySQL-only mode
  (Fuseki truly optional).
- RiC-O JSON-LD export from MySQL via `RicManageService`; link from record actions.

## Notes / risks
- The `sfRicPlugin` module is housed INSIDE `ahgRicManagePlugin/modules/sfRicPlugin/` (Symfony
  discovers module dirs from enabled plugins) - no new base plugin needed; only the route-map
  base patch (A) is required to dispatch `ric` records to it.
- `ahgThemeB5Plugin` template additions are new module dirs (additive) - but it is a stable/locked
  plugin, so flag before touching.
- Effort ~ adding RAD/DACS + the relation editor. Heavy infra (IO CRUD, graph, serializer, sync,
  relation tables) already exists; this adds the capture + standard-selection layer.
