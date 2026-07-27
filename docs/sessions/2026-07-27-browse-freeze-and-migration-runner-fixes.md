# 2026-07-27 - GLAM browse freeze + migration-runner overhaul

**Status:** SHIPPED. Instances: **archive/PSIS** + **archaeology** (code synced to both).
**Releases:** `atom-ahg-plugins` v3.88.5; `atom-framework` v2.13.46, v2.13.47, v2.13.48.

Three issues were reported: GLAM browse pages loading then becoming unresponsive, an `ahgCartPlugin`
migration not being picked up, and a `depreciation_policy` column missing from `heritage_asset`. The
first is a CSP bug; the other two turned out to be symptoms of a migration system that had never run
plugin migrations at all.

## Issue 1 - Browse pages load then go completely unresponsive

**Root cause.** `ahgDisplayPlugin/modules/display/templates/browseSuccess.php` hid the semantic-search
modal backdrop with an inline attribute:

```html
<div class="modal-backdrop fade" id="semanticSearchBackdrop" style="display:none;"></div>
```

The site's CSP (`style-src` without `unsafe-inline`) drops inline `style` attributes, so the
`.modal-backdrop` rendered as a transparent, full-screen `position:fixed` layer at `z-index:1050`.
The page loaded fine but every click landed on the invisible overlay - "completely unresponsive."
Both browse pages share the template, so both were affected, on both instances.

**Fix (v3.88.5).**
- Hide the backdrop from the nonce'd `<style>` block instead: `#semanticSearchBackdrop { display: none; }`.
  The open/close JS still toggles `element.style.display`, which is a CSSOM write and is *not* blocked
  by CSP (only static HTML `style=` attributes and `<style>` tags are).
- Converted the three `onclick="openSemanticModal()" / closeSemanticModal()` handlers (the CSP errors
  seen in the console) to `addEventListener`, so the modal buttons work and the console is clean.

**Lesson.** Under this CSP, never hide or show an overlay via an inline `style=` attribute - it silently
stays visible and freezes the page. Use a nonce'd stylesheet rule or JS `element.style`.

## Issue 2 - Cart migration never picked up

Two independent causes:

1. **The runner scanned the wrong directory.** `MigrationRunner` looked in `{plugin}/data/migrations`,
   but all 18 plugins use `{plugin}/database/migrations`. So *no plugin migration had ever been
   discovered* by `bin/atom migrate run` - cart's was just the one that got noticed.
2. **The migration's SQL was MySQL-incompatible.** It used `ADD COLUMN IF NOT EXISTS` /
   `CREATE INDEX IF NOT EXISTS`, which is MariaDB-only; MySQL 8 rejects it with a syntax error (and it
   is on the framework's forbidden list). Rewrote it as plain `ADD COLUMN` / `CREATE INDEX`; the runner
   already treats "duplicate column/key" (1060/1061) as safe, so it stays idempotent.

## Issue 3 - `heritage_asset.depreciation_policy` missing

`heritage_asset` (owned by `ahgHeritageAccountingPlugin`, not `ahgHeritagePlugin`) has a CREATE that
defines only 5 of the 9 columns that the framework migration `2026_03_08_enum_to_varchar` tries to
MODIFY. Missing: `depreciation_policy`, `derecognition_reason`, `revaluation_frequency`,
`valuation_method`. On a fresh install those columns do not exist, so `enum_to_varchar` hit
"Unknown column" and - with the runner's original halt-on-first-failure - blocked every later migration,
including cart's. Issues 2 and 3 compounded.

**Fix.** New framework migration `2026_03_07_heritage_asset_missing_columns.sql` (sorts before
`2026_03_08`) adds the four columns idempotently, and they were added to the CREATE in
`core.sql`/`install.sql` so fresh installs are complete.

## The migration runner overhaul (atom-framework)

The investigation showed the runner had never successfully run plugin migrations, so 63 were pending,
in three SQL styles the naive PDO executor could not all handle. The runner was rebuilt across three
releases:

- **v2.13.46**
  - Scan `database/migrations` (was `data/migrations`).
  - Continue past a failed migration instead of `break` - a single broken plugin migration no longer
    blocks all the others; failures are reported and left unrecorded so they retry once fixed.
  - Added the `heritage_asset` missing-columns migration.
- **v2.13.47 - hybrid execution.** A `.sql` file containing `DELIMITER`, `CREATE PROCEDURE/FUNCTION/
  TRIGGER`, or `PREPARE ... FROM` cannot run through PDO's single-statement `DB::statement()` (PDO does
  not understand the `DELIMITER` client directive, and splitting on `;` shreds a routine body). Such
  files now run through the `mysql` client via `proc_open` (password in `MYSQL_PWD`, never on the command
  line; all connection params shell-escaped; `--default-character-set=utf8mb4`; the DROP/TRUNCATE guard
  still applied). Plain-DDL migrations keep the PDO + safe-skip path so their "already exists" tolerance
  keeps them idempotent. Detection classifies every migration correctly (8 procedure/prepared -> client,
  the rest -> PDO).
- **v2.13.48 - schema-absent tolerance.** Extended both paths to tolerate "schema absent" errors (table
  1146, unknown column 1054, key column 1072, can't-drop 1091, `42S02`) the same way "already exists"
  (1050/1060/1061) is tolerated. The client path now runs with `--force` (every statement executes) and
  inspects `ERROR <code>` lines in stderr, throwing only on an untolerable code or an unattributable
  non-zero exit (for example, cannot connect). This lets a cross-cutting migration like
  `enum_to_varchar` - which MODIFYs columns across many optional-plugin tables - complete on an instance
  that has not installed all of them, rather than failing the whole run. Trade-off (accepted): a
  genuinely mistyped table/column is silently skipped rather than surfaced; real errors (syntax 1064,
  connection 2002) still fail loudly.

## The `IF NOT EXISTS` false alarm

An early grep suggested 11 plugin migrations used the broken `IF NOT EXISTS` DDL. On inspection this was
a false positive: the string appeared only in comments and in valid constructs (`CREATE TABLE IF NOT
EXISTS`, which MySQL 8 supports, and `IF NOT EXISTS (SELECT ...)` information_schema guards). Only the
cart migration was genuinely broken. The other "10" use valid idempotency patterns - prepared-statement
guards (Exhibition), information_schema stored procedures (Forms, Museum, Research), or plain DDL. Their
real obstacle was the runner's inability to execute procedure/DELIMITER SQL, which v2.13.47 fixed.

## The full sweep on archive/PSIS

`atom_framework_migrations` was empty - migrate had never run here; archive's schema came entirely from
each plugin's `install.sql`. Ran `MigrationRunner->migrate()` directly (skipping the `bin/atom migrate
run` git-pull). First pass: 61 of 63 succeeded; the two failures were archive-specific schema gaps
(`add_missing_indexes` indexing a `condition_status` column absent here; `enum_to_varchar` modifying
`viewer_3d_settings`, a 3D-plugin table not installed here). After the v2.13.48 tolerance, both pass -
**63/63 recorded, `migrate run` completely clean.** Verified: `cart.session_id` (+ `idx_cart_session`)
and `heritage_asset.depreciation_policy` (`varchar(76)`) present.

## Files touched

- `atom-ahg-plugins/ahgDisplayPlugin/modules/display/templates/browseSuccess.php`
- `atom-ahg-plugins/ahgCartPlugin/database/migrations/2026_01_14_cart_session_id.sql`
- `atom-ahg-plugins/ahgHeritageAccountingPlugin/database/{core,install}.sql`
- `atom-framework/src/Database/MigrationRunner.php`
- `atom-framework/database/migrations/2026_03_07_heritage_asset_missing_columns.sql`
