# 2026-07-21 - Rename user-facing "Spectrum" labels to "Collections Procedures"

**Release:** atom-ahg-plugins v3.79.106
**Instances:** PSIS (`/usr/share/nginx/archive`) + Wits Archaeology (`/usr/share/nginx/archeology`) - both live
**Driver:** Johan - "we are not allowed to use the Spectrum standard. Rename it everywhere. Only rename."

## Scope decision

Two narrowing instructions shaped this: **visible text only**, then **labels only**.
So the pass covers what a user reads on screen, and nothing else.

**Renamed** - `Spectrum` / `SPECTRUM` -> `Collections Procedures` / `COLLECTIONS PROCEDURES`:

- `__()` translation labels and rendered HTML text inside `modules/*/templates/`
- `extension.json` `name` / `description` (the Extensions admin display strings)
- DB-seeded label strings in `install.sql`
- the matching rows already populated in both live databases

**Deliberately NOT renamed** - every identifier:

| Kept | Count (archive) |
|---|---|
| `'module' => 'spectrum'` route refs | 98 |
| `spectrum_*` table/column refs | 636 |
| `Spectrum*` class names (`SpectrumWorkflowService`, `SpectrumProcedureCatalog`, ...) | 256 |
| `ahgSpectrumPlugin` paths + `machine_name` | - |
| `SPECTRUM_PROCEDURE_STATUS` constants, `'spectrum'` capability/setting key | - |
| `$hasSpectrum` template variables | - |
| Code comments (not labels) | 14 skipped |

## Method

Scripted rename with ordered rules, longest phrase first so no
"Collections Procedures Procedures" could be produced:

```
SPECTRUM PROCEDURES / Spectrum Procedures / Spectrum procedures -> Collections Procedures
Spectrum Procedure  / Spectrum procedure                        -> Collections Procedure
Spectrum 5.0|5.1 (space or hyphen)                              -> Collections Procedures
Spectrum standard                                               -> Collections Procedures framework
SPECTRUM-compliant                                              -> Collections Procedures-compliant
standalone Spectrum / SPECTRUM (guarded)                        -> Collections Procedures
```

Standalone guard: `(?<![A-Za-z0-9_\/$])Spectrum(?![A-Za-z0-9_])` - the lookbehind
protects `ahgSpectrumPlugin`, the lookahead protects `SpectrumWorkflowService` and
`SPECTRUM_PROCEDURE_STATUS`.

**Guard side-effect worth remembering:** the `/` in the lookbehind also blocked
`CCO/Spectrum`, which is a legitimate label. Two such cases were found by a
residual grep and fixed by hand. Any future guarded rename should grep for
`/Spectrum` afterwards.

Dry-run produced a line-level preview that was audited for identifier damage
before applying (`'module' => 'Collections...'`, `Collections Procedures_`,
`ahgCollections...` - all zero).

## Result

74 files, 138 lines. All PHP files pass `php -l`. Files md5-identical on both
instances. 122 of the replacements were in 65 templates; the rest were the two
`CCO/Spectrum` fixes, 3 `extension.json` files and 4 `install.sql` seeds.

Top plugins by hit count: ahgSpectrum 47, ahgWorkflow 20, ahgSettings 16,
ahgReports 13, ahgDataMigration 9.

## Live database labels

The seed-file edits only affect fresh installs, so 9 already-populated rows per
instance were updated (18 rows total), each inside a transaction that asserts a
9-row count before committing:

| Table | Column | Rows |
|---|---|---|
| `display_profile_i18n` | `name` ("Spectrum full/card/catalog") | 3 |
| `ahg_dropdown` | `taxonomy_label` ("Spectrum Procedure Status") | 4 |
| `numbering_scheme` | `description` | 1 |
| `term_i18n` (taxonomy 70) | `name` ("Gallery (Spectrum 5.0)") | 1 |

Keys verified untouched afterwards - `ahg_dropdown.taxonomy` is still
`spectrum_procedure_status` on all 4 rows.

Rollback, if ever needed, is the same statements inverted:

```sql
UPDATE display_profile_i18n SET name='Spectrum full'    WHERE name='Collections Procedures full';
UPDATE display_profile_i18n SET name='Spectrum card'    WHERE name='Collections Procedures card';
UPDATE display_profile_i18n SET name='Spectrum catalog' WHERE name='Collections Procedures catalog';
UPDATE ahg_dropdown    SET taxonomy_label='Spectrum Procedure Status'   WHERE taxonomy_label='Collections Procedure Status';
UPDATE numbering_scheme SET description='Spectrum standard object numbering' WHERE description='Collections Procedures object numbering';
UPDATE term_i18n ti JOIN term t ON t.id=ti.id SET ti.name='Gallery (Spectrum 5.0)'
 WHERE ti.name='Gallery (Collections Procedures)' AND t.taxonomy_id=70;
```

## Open - flagged to Johan, not actioned

1. **Documentation still says Spectrum**: 494 mentions across 61 files in
   `atom-extensions-catalog`, including both distributable user manuals, which
   describe the product as implementing Spectrum 5.1.
2. **The procedure content itself**: `ahgWorkflowPlugin/database/spectrum_procedures.json`
   holds 21 procedures paraphrased from Spectrum 5.1 and carries a disclaimer
   referencing a Collections Trust subscription.
3. **Four files excluded on principle** from any wider pass, because renaming
   would make them false rather than compliant: the SAMAB paper's bibliography,
   the `spectrum_procedures.json` provenance disclaimer, the standards-registry
   row in `ahgRegistryPlugin/database/migrate_standards.sql` (publisher/URL/version
   are factual data), and `ahgMuseumPlugin/docs/LOAN_MODULE_COMPARISON.md`.

If the restriction is contractual, a label rename does not discharge it - items
1 and 2 are the actual exposure.
