# 2026-09-28 - Installing four sector plugins on a clean AtoM found the same faults every time

Recording the DAM, Rights/Privacy/Security and Library flagship videos meant
installing `ahgDAMPlugin`, `ahgDisplayPlugin`, `ahgPrivacyPlugin`,
`ahgSecurityClearancePlugin`, `ahgRightsPlugin`, `ahgExtendedRightsPlugin`,
`ahgLibraryPlugin` and `ahgCartPlugin` on a stock AtoM 2.10 that had never had
them. Every single one failed, and mostly for the same four reasons.

**This is the headline: our plugins are not tested against a clean install.**
They work on instances that grew into their current schema. A fresh deployment -
which is what a customer gets - hits these immediately.

## The four recurring faults

### 1. install.sql aborts, so most of the schema is never created

MySQL stops at the first error, so one bad statement means every table after it
is missing - and the installer reports only the one error, not the 40 tables that
silently did not appear.

- **`ahgDAMPlugin`** seeded `watermark_type` at line 14 and created it at line
  148. The file header says "Ordered by dependency; each table is followed by its
  own seed data". The correctly-placed seed already existed at line 163, so the
  leading block was a duplicate in the wrong place.
- **`ahgSecurityClearancePlugin`** ran a retrofit `ALTER TABLE security_access_log
  ADD COLUMN compartment_id ...` for four columns and an index that the `CREATE
  TABLE` above it already defines, then two `DROP FOREIGN KEY` for constraints the
  `CREATE` no longer declares.
- **`ahgLibraryPlugin`** is the worst case. The file guards 117 statements with
  `INFORMATION_SCHEMA` checks, but four `ALTER TABLE` and three `CREATE INDEX`
  were bare - and the bare block was a later **duplicate** of work already done,
  guarded, earlier in the same file. The duplicate reintroduced the columns
  unguarded AND as an `ENUM`, against the house standard that the earlier block's
  own comment cites ("VARCHAR not ENUM per AHG standards").

**Fix pattern.** Never delete a retrofit - it is the upgrade path for older
instances. Guard it. Two idioms are already in the codebase: the
`INFORMATION_SCHEMA` + `PREPARE` idiom (`ahgLibraryPlugin`, 117 uses) and the
stored-procedure idiom (`ahgAuditTrailPlugin`, now `ahgSecurityClearancePlugin`).
MySQL has no `ADD COLUMN IF NOT EXISTS`, which is why one of these is needed.

**Test for it:** run the file twice. `mysql <db> < install.sql` must be clean on
the second pass. All four are now.

### 2. Pinned collations

Every one of these plugins pinned `utf8mb4_unicode_ci` while the database default
on MySQL 8 is `utf8mb4_0900_ai_ci`, so any join to a base AtoM table fails with
"Illegal mix of collations". This is the defect that took out
`ahgPreservationPlugin`'s Format Conversion screen earlier.

Stripped this session: DAM 12, Display 17, Privacy 48, SecurityClearance 27,
Rights 131, ExtendedRights 53, Library 188, Cart 17. **493 in total.**

A plugin table should declare no `COLLATE` at all and inherit the database
default. The repair for an already-installed instance is in the note at the head
of each fixed file.

### 3. Undeclared dependencies

`extension.json` lists `dependencies: ["ahgCorePlugin"]` and that is usually a
lie.

- `ahgDAMPlugin` needs `ahgDisplayPlugin` (`display_object_config`),
  `ahgPrivacyPlugin` (`digital_object_metadata`) and `ahgUiOverridesPlugin`
  (`AhgLaravelHelper`), and declares none of them.
- `ahgLibraryPlugin` needs `ahgCartPlugin` - its item template queries the `cart`
  table directly - and a framework migration
  (`2026_03_08_dropdown_column_map.sql`) that no plugin install runs.

### 4. Route/action and namespace mismatches

- `ahgDAMPlugin`'s `executeBrowse` redirected to module `ahgDisplay`. The module
  is `display`; the plugin name is not the module name.
- **The whole `ahgLibraryPlugin\` namespace was unloadable.** `lib/Repository` and
  `lib/Service` declare it, but nothing mapped the prefix: the framework
  bootstrap registers only `AtomFramework\` and `AtomExtensions\`, and no AHG
  plugin registers its own. Ten classes unreachable; the item view died with
  `Class "ahgLibraryPlugin\Repository\IsbnLookupRepository" not found`.

  **A wrong fix here breaks every page.** Registering it via
  `require sfConfig::get('sf_root_dir').'/vendor/autoload.php'` fatals on a stock
  AtoM 2.10, which has no such file. Use a plain `spl_autoload_register`. I made
  exactly that mistake and caught it on the next check; nothing was committed
  broken and nothing reached PSIS.

## Seeded-by-nothing tables

`display_collection_type` shipped empty, so Admin > Display > Bulk Set Object
Types rendered a required "Object Type *" label **with no control at all** and no
record could be typed as museum, gallery, library or DAM. Its `AUTO_INCREMENT=8`
shows the source dump held 7 rows whose INSERTs were not carried across. PSIS had
zero rows too - the screen had never worked anywhere.

The seed was NOT invented: the values are the `$typeConfig` map the plugin's own
browse templates already use for each type's icon, colour and label, identical in
`browseSuccess.php` and `browseEmbeddedSuccess.php`. "Audiovisual" appears in that
screen's help text but not in `$typeConfig`, so it is deliberately not seeded -
seeding it would offer a type the browse cannot render.

Same class: `spectrum_evidence` and `spectrum_outcome_proposal` (see
`2026-09-28-collections-procedures-filming-defects.md`), and
`/var/lib/ahg-evidence`, which no install creates and which **PSIS still needs**.

## Theme coupling

`/library/add` and `/dam/create` both 500 on a missing
`informationobject/identifierGenerator` component, which lives in
`ahgThemeB5Plugin`. A cataloguing form depending on a component from the THEME
plugin is the defect. Not fixed: installing that plugin on the recording VM would
change the entire look and break visual continuity with 47 existing videos.

## Dead files that mislead

`ahgSecurityClearancePlugin/config/routing.yml.php` lists routes
(`/admin/security`, `/admin/security/clearances`, `/admin/security/audit`) that
do not exist. The file is never loaded; the live routes are registered in
`addRoutes()` at different paths (`/security/clearances`,
`/admin/security/compliance`). I probed the dead file's URLs and concluded three
screens were broken when they were simply elsewhere. Worth deleting.

## Released

- v3.112.14 DAM (7 fixes) + display collection type seed + privacy collation
- v3.112.15 Security clearance install guards + rights collations
- v3.112.16 Library duplicate ALTER block + PSR-4 registration + cart collation
- Videos: `access-to-memory-digital-asset-management.mp4`,
  `access-to-memory-rights-privacy-security.mp4`, `access-to-memory-library.mp4`
  (catalogue v1.13.5, v1.13.6)

## What should happen next

1. **A clean-install test.** Every one of these would have been caught by
   installing each plugin into an empty database in CI. That test does not exist.
2. **Make `extension.json` dependencies true**, and have the installer refuse
   rather than half-complete when one is missing.
3. **PSIS needs `/var/lib/ahg-evidence`** creating, and will pick up the
   `display_collection_type` seed on its next `install.sql` run.
