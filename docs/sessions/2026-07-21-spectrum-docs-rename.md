# 2026-07-21 - Spectrum -> Collections Procedures, documentation pass

**Releases:** atom-ahg-plugins v3.79.108 | atom-framework v2.13.35 | atom-extensions-catalog v1.12.12
**Follows:** the labels pass in v3.79.106 (see `2026-07-21-spectrum-label-rename.md`)

## Result

62 files, 184 replacements:

| Repo | Files | Replacements |
|---|---|---|
| atom-extensions-catalog | 37 | 125 |
| atom-ahg-plugins | 20 | 49 |
| atom-framework | 5 | 10 |

Plugin + framework docs mirrored to archaeology (25 files). Verified afterwards:
**0 residual prose mentions** of `Spectrum` outside code fences and the deliberate
exclusions below.

## What was NOT renamed, and why

Each of these was found by a review pass that flagged suspect output before applying.

**Fenced code blocks** - technical docs quote real code. The first dry-run produced
`public const COLLECTIONS PROCEDURES = 'spectrum';` in `ahgCorePlugin.md`, which is
not valid PHP and describes code that does not exist. The renamer now tracks
` ``` `/`~~~` state and skips fenced content entirely. Identifiers inside prose are
still protected by the standalone guard.

**`/docs/api/`** - generated phpDocumentor output. Regenerated from source, which
keeps the Spectrum identifiers by design; editing it would be undone on next build.

**`/_inventory/`** - captured snapshots of live `symfony task-list` output. Editing
a capture falsifies it.

**`/docs/sessions/`** - historical record of work actually done. The label-rename
session log in particular must keep saying Spectrum to stay coherent.

**Three files excluded outright:**

| File | Why |
|---|---|
| `atom-extensions-catalog/docs/spectrum-procedure-pack.md` | Contains "Spectrum 5.1 is © UK Collections Trust". Renaming would assert that OUR name is their copyright - false, and legally worse than the original. |
| `atom-extensions-catalog/docs/samab/SAMAB_2026_AI_Collections_Steward.md` | Academic bibliography - a citation must match the work it cites. |
| `ahgMuseumPlugin/docs/LOAN_MODULE_COMPARISON.md` | Cites its source URL on collectionstrust.org.uk. |

## Rules added for prose

Beyond the label-pass rules, prose needed collision handling that UI labels did not:

```
UK Collections Trust Spectrum 5.x procedures  -> Collections Procedures
Spectrum 5.x (UK Collections Trust)           -> Collections Procedures
UK Collections Trust museum procedures        -> Collections Procedures for museums
UK Collections Trust procedures               -> Collections Procedures
 (UK Collections Trust standard) | (UK Collections Trust)  -> (removed)
Spectrum 5.x Procedures                       -> Collections Procedures
```

That last one matters: without it, `Spectrum 5.0 Procedures` collapsed to
**"Collections Procedures Procedures"** (caught in `FUNCTIONS.md`). Any future pass
must put version+noun rules ahead of the bare version rule.

## Known cosmetic drift

"Collections Procedures" is 15 characters longer than "Spectrum", so a handful of
markdown table rows and ASCII box diagrams that survived outside code fences now
have ragged alignment. Content is correct; only column padding is off. Most box art
lives inside fences and was untouched.

## Open

**The 31 `.docx` deliverables still say Spectrum** - including
`AtoM_Heratio_Complete_User_Manual.docx`, `AtoM_Heratio_Admin_Manual.docx`,
`AtoM_Heratio_User_Manual.docx` and `spectrum-user-guide.docx`. Per CLAUDE.md the
`.docx` is the distributable deliverable, so the rename is not finished for
client-facing purposes. `bin/build` only builds the MkDocs site; the `.docx` were
generated ad hoc and committed, so rebuilding means a pandoc run per file against
the AHG house reference template - which rewrites their formatting wholesale.
Johan's call on 2026-07-21: **defer**, release the markdown only.

**`ahgWorkflowPlugin/database/spectrum_procedures.json`** still holds 21 procedures
paraphrased from Spectrum 5.1 with a Collections Trust disclaimer. Unchanged - the
disclaimer is protective, not incidental.

**archaeology's `atom-extensions-catalog` clone** is a separate checkout and needs a
`git pull` to pick up v1.12.12. (It also has a git "dubious ownership" warning.)
