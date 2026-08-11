# Collections Procedures: evidence per step, outcomes per procedure

**Date:** 2026-08-11
**Releases:** plugins v3.97.0 - v3.99.1, framework v2.15.1 - v2.16.1
**Issues:** [#297](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/297), [#298](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/298) opened; #247, #256, #239, #272 closed; #296 filed

## What was wrong

The 15 procedure tiles at `/<slug>/spectrum/` sit on a good foundation:
`spectrum_workflow_config` holds steps, states, transitions and initial state as
versioned JSON per procedure. It was half-built.

- **No procedure recorded proof.** Nothing could be attached to a step.
  `spectrum_loan_document` had been declared years earlier and no code was ever
  written against it.
- **No procedure produced anything.** Approving a valuation moved a state and
  wrote history. It touched nothing else.
- **The step checklist could not work for 19 of 21 flows.** Only `acquisition`
  and `cataloguing` gave steps a `key`, and the checklist indexes `$step['key']`.

## What was built

**Step keys** for all 21 configs, migration plus seeds. Verified by loading
install.sql into a scratch schema: 21 configs, 0 keyless steps.

**`spectrum_evidence`**, keyed `(procedure_type, record_id, step_key)` - the same
triple as the checklist, so it works across all 21 flows with no per-procedure
code. Write path reuses `AccessionIntakeService::addAttachment()`'s sequence:
validate, move, verify magic bytes, unlink on failure, unlink again if the row
insert fails.

**Outcomes.** A flow declares what it produces:

    "outcomes": [{"on_state": "approved", "handler": "heritage_revaluation"}]

Dispatch fires after the transition's own writes, wrapped so a failing handler
cannot undo a transition that happened. Nothing is posted: a proposal is raised
in `spectrum_outcome_proposal`, and a human accepting it is what writes.

Two handlers: `heritage_revaluation` (valuation approved -> propose a carrying
amount) and `conservation_record` (treatment completed -> propose condition
rating and assessment date). The second is the point - it shows the mechanism is
not accounting-specific.

**Gated on the owning plugin**, not on catching failures. `heritage_revaluation`
registers only when `ahgHeritageAccountingPlugin` is enabled, and the log
distinguishes "declared but its plugin is not enabled" from "not a known
handler". Spectrum already wraps a GRAP read in `catch { // Table may not
exist }`, and a swallowed exception is indistinguishable from a feature that is
switched off.

**21 procedures surfaced.** `risk_management`, `audit`, `rights_management`,
`reproduction`, `documentation_planning`, `retrospective_documentation` had
working configs and were counted by compliance reporting, but were missing from
`getProcedures()` - no tile, no link, reachable only by typing a URL.

**Vocabularies aliased.** `ahgWorkflowPlugin` and `ahgSpectrumPlugin` name the
same procedures differently and both have live data (21 rows each), so neither
could be renamed. `canonical()` maps to the config codes, `synonyms()` queries
across both, `normalize()` accepts either. Previously `condition_checking` was
rejected outright by the catalogue.

## Lessons

**Storage took three attempts, and the first two failed silently.**

    sf_upload_dir   nginx maps it to /uploads/ - anonymous GET returned the file
    sf_data_dir     also inside the AtoM directory, which nginx roots.
                    /data/<file> returned 69 bytes of actual PDF,
                    content-type application/pdf
    beside the app  mkdir failed: php-fpm runs ProtectSystem=full with
                    ReadWritePaths=/usr/share/nginx/atom, so /usr is read-only

Now `/var/lib/ahg-evidence/<instance>`. **Found only by fetching the file rather
than trusting the design** - I had already written a comment claiming the files
were unreachable while that was false.

**A guard that turns a fault into silence is worse than the fault.** Three
defects this session shared that shape: `class_exists()` on a wrong namespace
made #236's classification save a no-op since it shipped;
`method_exists('regenerateForObject')` skipped a method actually called
`regenerateDerivatives`; and `catch { // Table may not exist }` is
indistinguishable from a disabled feature.

**Schema drift is invisible until you have two instances.**
`HeritageAssetService::addValuation()` writes `valuation_change`, a column that
reached live databases through a migration and was never added to install.sql.
It works on PSIS and **cannot work on any fresh install**. `CREATE TABLE IF NOT
EXISTS` skips the statement, so the schemas never converge.

**A stylesheet nothing loads is worse than no stylesheet.** Fixing #298 I wrote
`public/css/ahg-media.css`, and neither the runtime build nor any page loaded it.
Every check still passed while the viewers would have rendered unstyled. The
theme never calls `include_stylesheets()` - registered assets are dropped - so
the helpers now carry their own rules in a nonced `<style>` element, emitted once
per request.

**Wrong syntax, same problem.** The first fix converted varying heights to
`style="--ahg-frame-height:..."`. A custom property in a style attribute is
dropped by CSP exactly like any other declaration.

## Verified on a clean AtoM 2.10

    step checkboxes on Valuation   4 (were inert)
    evidence uploaded              attached to the Appraisal step
    .php upload                    refused
    file at 4 guessed URLs         404 / no file
    download as editor             200, application/pdf
    download as anonymous          login page, not the file
    valuation recorded             ZAR 485,000.00 - first row that table has held
    flow pending -> approved       proposal raised, asset UNCHANGED
    proposal accepted              carrying 250,000 -> 485,000, history row written
    revaluation_surplus            still 0.00 - pre-existing gap, stated in the result
    5xx across 10 screens          0

PSIS ran the same code throughout with the migrations deliberately not applied,
exercising the guarded paths: all pages 200, zero new rows in `ahg_error_log`.

## Addendum: the migrations never ran

`/heritage/1/` on the clean instance returned "Oops! An Error Occurred" -
`Unknown column 'journal_date' in 'order clause'`. Two independent defects behind
one symptom, and the schema-drift lesson above turned out to be far larger than
one column.

`SchemaInstallCommand` collected files with `glob($dir.'/*.sql')` against
`<plugin>/database`, and never descended into `<plugin>/database/migrations/`.
That hid **52 migrations across 18 plugins** - 43 `.sql` and 9 `.php`
(ahgResearch 12, ahgPrivacy 8, ahgHeritageAccounting 7). Separately,
`MigrationRunner` handles plugin migrations perfectly well and **nothing calls it
on install or enable**; it is reachable only from `atom migrate up`, which an
operator has to know to type. On the clean instance `atom_framework_migrations`
did not exist at all - the runner had never run there once.

So install.sql creates a table, `database/migrations/` reshapes it as the code
moves on, and a fresh install stops at step one. `CREATE TABLE IF NOT EXISTS`
cannot close that gap, because the table already exists.

`ahgHeritageAccountingPlugin` is the worked example. Migration `005` renames
`entry_date` to `journal_date` and adds eleven columns; every query orders by
`journal_date`. install.sql still declared the pre-005 shape, so PSIS was fine
(`ahgHeritageAccountingPlugin:005_...` is recorded there) and any clean install
died on the first heritage asset opened. `heritage_movement_register` was the
same story - twelve columns fresh against eighteen live, and the seven columns
005 adds matched the seven missing exactly.

Fixed in `SchemaInstallCommand::migrate()`: the command now runs
`MigrationRunner::migrate()` after its SQL passes, names each failed migration
individually rather than reporting a count, and returns non-zero. Both heritage
tables in install.sql were rewritten to match **what 005 produces, column for
column and type for type** - 005 CHANGEs the old names rather than keeping them,
so there are no aliases to retain and fresh now converges exactly on migrated.

**And with that fixed the runner still said "Nothing outstanding".** A third
defect, found only by running it rather than reading it.
`MigrationRunner::__construct()` derived the root with `dirname(__DIR__, 3)`,
which assumes this file lives at `atom-framework/src/Database`. Under the
generated `ahgRuntimePlugin` - which is how the clean instance runs - the same
class sits at `plugins/ahgRuntimePlugin/src/Database`, so the walk landed on
`plugins/`, `plugins/atom-ahg-plugins` did not exist, and `pluginsPath` was set
to `''`. Zero plugin migrations discovered, reported with the identical sentence
the runner prints when everything genuinely has been applied.

It now resolves through `sfConfig::get('sf_root_dir')` first - what the
application itself considers the root - falls back to both directory walks, and
scans `atom-ahg-plugins/` and `plugins/` alike, de-duplicating a plugin present
in both.

Then it ran: **48 migrations applied, 1 failed**. The failure is
`ahgWorkflowPlugin:005_visual_workflow`, a foreign key onto `ahg_workflow` which
that plugin's own install.sql never created there - reported by name, which is
the behaviour wanted. `/heritage/1/` returns 200 and the error log has been
clean since.

The diagnostic worth reusing: diff `information_schema.columns` between an
upgraded instance and a fresh one, per table, **before** reading any code. Every
one of these looked like a code bug and none of them were. And the third one
would have stayed hidden behind a reassuring message if the fix had been
shipped without being run.

## Left open

- Accepting produces the same incomplete accounting record the manual form does:
  no surplus split, no journal, no movement-register row. Pre-existing, wants its
  own issue.
- #298: 2,269 inline style attributes across the suite (not 2,348 - the first
  count included vendor). `ahgRuntimePlugin` is now 0.
- `ahgHeritageAccountingPlugin` still has 11 and cannot be packaged without
  `AHG_ALLOW_INLINE_STYLES=1`.
- The packager's detector matches `style="` inside PHP comments, so a comment
  explaining why not to use one trips it.
