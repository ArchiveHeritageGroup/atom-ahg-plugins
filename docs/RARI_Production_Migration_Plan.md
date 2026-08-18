# RARI production migration

> **Note on the install steps referenced here.** The AtoM and plugin installation
> procedure has been verified from bare metal since this plan was written; see
> [AtoM_2.10_Clean_Install_Log.md](AtoM_2.10_Clean_Install_Log.md) and the README.
> In particular `php8.3-gd` is required and is missing from AtoM's own package list,
> and base AtoM is not modified at any point.

**Rock Art Research Institute, University of the Witwatersrand**
**Target: within two weeks of 16 August 2026**
**Prepared by:** Dr Johan Pieterse, The Archive and Heritage Group (Pty) Ltd

## What this covers

RARI's production instance moves to the AHG plugin stack on AtoM 2.10. The work has
been built and proven on a development copy carrying RARI's full production data, so
what follows is a migration of something already working rather than a plan to write it.

Everything below was verified on that copy: 292,278 archival descriptions, 8,436
authority records, 30 authority-record site records and the real user accounts.

## Modules being introduced

| Module | What it gives RARI | State |
|---|---|---|
| **Feedback** | Public correspondence against records, with staff triage | Installed, 9 legacy entries carried over |
| **Favorites** | Saved records per user | Installed, 27 legacy entries carried over |
| **Researcher** | Researcher registry, approval and expiry | Installed, 22 accounts migrated |
| **Register as a Wits user** | Self-service account request for institutional users | Registration path in place |
| **ahgSiteRecord** | Site recording with role-gated locality | New. 7,585 site records seeded |
| **Request to Publish** | Publication requests for archival images | Installed, 79 legacy records carried over |
| **Cart** | Reproduction and image requests | Installed, legacy cart data present |
| **Access request and approval** | Requests for access to restricted material, with an approval queue | Installed |

All of these are managed from the staff interface: browse, approve, deny, expire and
report, rather than by editing the database.

## Modules being retired

**Service Provider** and **Registry**, both bespoke RARI plugins, are removed. This is
not a judgement call, it is what their data says: both tables are **empty in
production**. The researcher registry has never held a record either, which is why the
replacement is the Researcher module rather than a port of the old one.

The standalone `rock_forms` field-recording application is also retired. Its two tables
are empty, so nothing is migrated. Its functionality is rebuilt as ahgSiteRecord, tracked
as issue #299.

⛔ **Before cutover, `rock_forms/` must be blocked and its credentials rotated.** It sits
in the public web root with no authentication of any kind on create, edit or delete, it
deletes on a bare GET with no token, and it exposes two database passwords in
world-readable files. One of those passwords is still in use elsewhere. This is
independent of the migration and should not wait for it.

## Users

Thirty accounts, handled by domain:

- **Wits addresses stay staff.** Six accounts.
- **Non-Wits become researchers.** Twenty-two accounts, migrated and approved, since they
  are existing active accounts rather than new applications.
- Two AHG accounts stay staff.

AtoM never captured real names for these accounts, so names were derived from the
username and, where that gave nothing, from the email address. Six accounts keep their
handle rather than an invented name. Two accounts appear to be the same person and were
deliberately not merged.

## Site locality

This is the part that changes most, and it is worth being explicit about why.

RARI records where a site is in the ISAAR "Internal structures/genealogy" field as free
text. On the production instance that field is commented out of the templates, so nobody
can see it, staff included. That is a blunt instrument: it protects the data by making it
useless.

Measured on the development copy, that field held:

- **5,456** records with a 1:50,000 map sheet reference, across 481 distinct sheets
- **842** records with "How to find the site:" followed by turn-by-turn directions

The directions matter more than the map sheets. A sheet reference locates a site to
roughly seven kilometres. Directions take somebody to it.

After migration the locality lives in the site record module and is governed by one rule
in one place. Staff with editor or administrator rights see the exact position. Everybody
else, including a logged-in researcher, sees a position rounded to about eleven
kilometres, with the map sheet and the original text withheld entirely. A record whose
sensitivity has never been set is treated as sensitive. The rule applies to the record
view, the panel, browse, exports and reports, not just the page it was written for.

The ISAAR field itself is cleared during migration, so there is one copy rather than two,
and every original string is preserved verbatim inside the site record.

⚠️ **One decision is outstanding.** Site codes such as `2929CD40` appear in the Parallel
form of name field on **877 records**, and those encode the map sheet. They are also how
RARI identifies a site. Gating them would hide part of a record's name from the public;
leaving them means the sheet stays publicly derivable for those sites. This needs a
decision before cutover.

## Approach

The development copy was rebuilt from a clean AtoM 2.10 install, their database restored,
and the schema upgraded through AtoM's documented path. That rehearsal found several
things worth knowing:

- Their database already contains tables from an earlier AHG deployment, so the schema
  must be reconciled rather than installed. A standard install skips tables that already
  exist, and the mismatch stays silent until something queries a column that was never
  added.
- Their audit trail is a modification to the database library itself, inside the vendor
  directory, with 31.6 million rows. It cannot be carried forward, and the replacement
  starts clean at cutover with the old table retained read-only for history.
- Records inherited without their translation rows cause staff pages to fail rather than
  degrade. Fixed, but it is the shape of problem to expect from legacy data.

## Duplicate authority records

An earlier import created authority records instead of matching the ones already there,
and then attached that run's descriptions to the copies. This needs resolving at
cutover, not after: every day it stays, more descriptions attach to the wrong record.

What is in the development copy, measured 17 August 2026:

| | |
|---|---|
| Authority records | 8,443 |
| Surplus by exact name | 125, across 63 repeated names |
| Surplus allowing for case, spacing and punctuation | 143 |
| Loaded in the original pass, 30 April 2025 | 8,243 |
| Created after it | 186 |
| Of those, exact duplicates of an existing record | 58 |
| **Duplicates carrying descriptions** | **52** |

The clearest case is Chentcherere II. The real record holds 84 descriptions. Nineteen
copies made in a single run on 21 August 2025 hold 43 more between them, slugged
`chentcherere-ii-2` through `-20`. The copies have no entity type where the original has
one, which is the fingerprint of the importer creating rather than matching.

Two consequences shape the work:

- **It is a merge, not a delete.** 52 of the duplicates are live creator links. Removing
  them would take their descriptions with them. Each merge repoints `event.actor_id` to
  the surviving record, then removes the empty shell.
- **Name is the only key available.** `description_identifier` is empty for every actor,
  so there is no site code to match on. Site codes exist only as parallel names, which
  is the same field covered by the locality decision above. Exact and normalised name
  matching finds 143; the 120 later records with no exact twin still need checking for
  near-duplicates before anything is merged.

Sequence:

1. Produce a dry-run report - every proposed pairing, which record survives, how many
   descriptions move, and anything ambiguous held back for a person to decide. No writes.
2. RARI reviews it. Some pairs will be genuinely different people or sites with the same
   name, and only they can say which. Nothing is assumed.
3. Apply the approved merges on the development copy, verify description counts land on
   the surviving records, then repeat on production during cutover.
4. Re-run the detection afterwards, so anything the first pass missed is visible rather
   than assumed absent.

The importer itself must be corrected before any further load, or the next run recreates
the problem. Matching on name alone is what caused this; the fix is to match against
existing authority records and report near-misses instead of silently creating.

The existing deduplication tooling does not cover this. It scans archival descriptions
and digital objects, and has no handling for authority records at all, so the detection
and merge described here is new work rather than a matter of pointing an existing tool at
a different table.

## Not included

The 30 TB image store is not part of this work. On the development copy every digital
object resolves to a marked placeholder; production keeps its own files.

~~No coordinates exist in RARI's current data.~~ **Corrected 18 August 2026: 3,062 do.**
See "Coordinates" below. Visit dates, regions and condition assessments genuinely are
empty and stay so until fieldwork fills them.

---

# Cutover plan, revised 18 August 2026

Everything below was established by doing it on the development copy. It supersedes
the estimates above where the two disagree.

## Coordinates - 3,062 of them, and the plan said there were none

RARI's pre-AtoM export (`fulldump-orig.xml`, 519MB RMXML) carries
`site_coordinates_latitude` and `site_coordinates_longtitude` on its 7,633 site
records. **3,062 sites have both, and none of them reached AtoM** - the original
migration carried the map sheet reference across but not the position.

Two reasons this was missed, and both produce a confident wrong answer rather than
an error:

- the field is misspelt **longtitude** in the source, so a search on the correct
  spelling matches nothing;
- plain `grep` treats the file as binary (it is iso-8859-1) and returns **no matches
  and exit 0**. `grep -a` is required.

**Validate against the map sheet rather than trusting the parse.** Most records also
carry a 1:50,000 sheet reference, which encodes its own position and is therefore an
independent witness. Measured on the development copy:

| Verdict | Count | Action |
|---|---|---|
| Confirmed - inside its own sheet cell | 2,515 | import |
| Transposed lat/lon, corrected | 41 | import |
| No sheet to check against | 416 | import, flagged |
| Near-miss (outside by under 0.3 deg) | 64 | **hold** |
| Conflicts outright | 24 | **hold** |
| Ambiguous name | 2 | **hold** |

**2,972 imported on the development copy, 90 held for RARI to decide.**

Three ways this data is silently wrong if parsed naively: decimal commas
(`25 12' 13,64''` - splitting on the comma drops the fractional seconds, ~20m);
**Libya and Ethiopia are north of the equator** and mostly carry no N/S letter, so
defaulting to south puts them ~5,500km out in the Kalahari; and 721 values have no
hemisphere letter at all.

Tooling: `siterecord:import-coordinates`, dry-run by default, plus
`ahgSiteRecordPlugin/bin/import-coordinates`.

⚠️ **Sequence matters.** The 2 ambiguous names collide with the duplicate authority
records. **Run the duplicate merge before the coordinate import**, or positions
attach to records that are then merged away.

## What production needs that the development copy did not have

### Plugins

The development copy went from 24 to 67 plugins to match archaeology. Production
needs the same set. Three things that cost time:

- **A stale `extension.json` blocks installs that are actually fine.**
  ahgSecurityClearancePlugin's manifest declared 29 tables including four watermark
  ones owned by ahgDAMPlugin. The installer refuses when a dependency's tables are
  absent, so that false claim blocked security clearance, which blocked workflow,
  which blocked authority resolution. **Sync manifests from the repo before
  reaching for `--force`** - forcing would have overridden a correct refusal driven
  by wrong metadata.
- **Three of four dependency declarations are wrong, one is right.** ahgAuthorityResolution,
  ahgExtendedRights, ahgRdm and ahgThemeB5 declare dependencies archaeology does not
  have. For three, `--force` is correct. For ahgAuthorityResolutionPlugin it is
  genuine: `ahg_mention` has a foreign key to `ahg_ner_entity` (ahgAIPlugin).
  Install that schema and leave the plugin disabled, as archaeology has it.
- **Enabling the set white-screened the site**, from two causes - see below.

### The two white-screen causes

- **Fatal:** several plugins build `sf_root_dir . '/atom-framework/bootstrap.php'`.
  RARI ships the framework as ahgRuntimePlugin, so the path does not exist and the
  require is fatal - a blank page, not an error page. ahgRuntimePlugin does provide
  `bootstrap.php`; symlink `atom-framework` to it at the AtoM root. The durable fix
  is for those plugins to resolve the framework relative to themselves.
- **Missing theme assets:** ahgThemeB5Plugin looks for bundles in the web root's
  `dist/`, but they ship in the plugin's `web/dist/`. Nothing publishes them on
  enable. Copy them, and check before declaring the theme working.

### CLI tasks do not exist on production either

RARI runs **stock** `config/ProjectConfiguration.class.php`: a hardcoded ten-plugin
array that never reads the `plugins` setting. AHG plugins are enabled by the
*application* configuration, but AtoM discovers CLI tasks at *project* level - so
`php symfony siterecord:*` does not exist, while the task file sits present and
correct. Base AtoM is locked, so use the runners in each plugin's `bin/`.

Two traps when driving an `sfBaseTask` directly, both of which look like success:
the constructor does not set `$this->configuration` (dies on
`sfContext::createInstance(null)`), and `sfTask::log()` publishes to `command.log`
with no listener outside `sfCommandApplication` - so the task **runs to completion
and prints nothing**, indistinguishable from finding no data.

### Elasticsearch breaks bulk actor writes, in two opposite ways

- **On create:** `QubitActor::save()` throws `in_array(): Argument #2 must be of type
  array, null given` from arElasticSearchPlugin. **The row is already inserted**, so
  a failed batch leaves orphan actors. Fix: `QubitSearch::disable()` (what AtoM's own
  `importBulkTask` does), then `search:populate`.
- **On update:** `getDocument() on null`, and here the exception lands **before** the
  write, so nothing persists and disabling search does not help. Fix: write
  `actor_i18n.authorized_form_of_name` directly.

⚠️ **Check whether the write landed, in both directions. An error count is not a
statement about the database.**

## Gate the heritage landing page before go-live

ahgHeritagePlugin redirects unauthenticated visitors from the homepage to its landing
page, and that page does not scale:

| | Descriptions | Heritage page |
|---|---|---|
| archaeology | 133 | 1.2s |
| RARI | 292,278 | **112s** |

An instance large enough for it to be slow is exactly an instance where every
anonymous visitor is sent to it. Set `heritage_homepage_redirect` to `0` on
production (gated from v3.103.24; default stays on so other instances are
unaffected). `/reports` at 24s and browse at 6s are the same problem, unaddressed.

## Menus and the public landing page

- ahgCorePlugin overrode AtoM's quick links component and blanked it, on the
  assumption of a hardcoded theme template that only ahgThemeB5Plugin ships. On any
  other theme, Home / About / Privacy Policy / Help / General Feedback vanish while
  the menu rows sit in the database untouched. Fixed v3.103.24.
- Menu rows outlive their plugins: **Service Provider** and **Registers** rendered as
  dead links after those plugins were retired, and **Reports** pointed at the picker
  rather than the dashboard. Correct these through `QubitMenu` - the table is a
  **nested set** and raw DELETEs leave holes.

## The researcher deliverable

The spatial analysis export was returning far fewer records than ARADA shows, and
the cause was not one bug:

- the report defaults to **top-level records only**, and RARI's 5,703 top-level
  records carry **zero** subject tags - every technique tag is at item level;
- it read technique from taxonomy 35 (Subjects) only, while **taxonomy 78 (Genre)
  holds most of it** - 34,247 descriptions against 7,216;
- the place filter pre-selected four countries, and on RARI the coordinate-bearing
  and country-tagged populations **do not overlap at all**;
- `Country` emitted site names when no country matched.

All fixed by v3.103.28. Technique is now classified by **term id**, not substring:
the shipped substring list contained `Khoekhoen`, `Khoi` and `geometric`, which are
a people and a design description rather than techniques, and `Khoekhoen` has 267
uses in a taxonomy the report reads.

⚠️ **At 111km cells, Lesotho and South Africa are not separable** - Lesotho is ~220km
across and entirely enclosed by ZA. Any Lesotho-versus-ZA comparison needs the
15-minute cell (~28km), which is a disclosure decision.

## Structural finding for RARI to consider

Technique is not modelled as its own thing. Taxonomy 78 "Genre" mixes medium (Slide,
183,186 uses; Negative; Hasselblad) with technique (Rock Painting 21,926; Rock
Engraving 12,139). Taxonomy 35 "Subjects" mixes provenance (RARI Main Slide
Collection), people (Benjamin Smith), culture (San) and technique (Brush painted
4,713; Finger painted 1,314). *Painting* exists as three separate terms.

A dedicated Technique taxonomy - Genre for format, Subjects for provenance - would
let any report ask "what technique" without guessing. That is a data migration and
RARI's vocabulary decision, not a code change.

## Revised cutover sequence

1. Block `rock_forms/` and rotate its credentials. Independent of everything else.
2. Install plugins to parity; sync manifests first; symlink `atom-framework`;
   publish theme assets. Verify no white screen before proceeding.
3. Set `heritage_homepage_redirect = 0`.
4. Correct the menu rows through `QubitMenu`.
5. **Merge duplicate authority records** - RARI reviews the dry run first.
6. Import map sheet localities, then coordinates. Both dry-run first; 90 held rows
   go back to RARI.
7. Clear the ISAAR `internal_structures` field only after 6 verifies.
8. `search:populate`, then check `ahg_error_log` is quiet.
9. Decide the 877 parallel-name site codes (still open, see above).
10. Rotate credentials; confirm `/uploads/r/` is blocked for unpublished masters.

## Still open

- **90 held coordinate rows** need RARI's decision.
- **877 parallel-name site codes** - the original open decision, unchanged.
- The duplicate merge itself has only ever been a dry run.

## Technique and tradition - enrich, do not replace

**The structure already exists in the raw data. The migration lost it, and it can be
put back additively.**

The dump's site records carry four separate, controlled fields. These are not free
text - the whole vocabulary is a handful of values:

| Source field | Sites | Distinct values | Values |
|---|---|---|---|
| `<technique>` | 7,011 | **3** | Painting 6,262 · Engraving 706 · Painted engraving 43 |
| `<painting>` | 5,820 | 4 | Brush painted 4,713 · Finger painted 1,322 · Handprint 101 · Handprints 4 |
| `<engraving>` | 733 | 3 | Pecking 601 · Scratching 100 · Incision 92 |
| `<tradition>` | 5,544 | 14 | San 4,335 · Pygmy (Forest Hunter Gatherers, Batwa) 564 · KhoeKhoen (Khoi) 267 · Bantu 220 · Chewa 105 · Sahara pastoralists 75 |

AtoM flattened all four into generic Subject and Genre access points, where they now
sit beside collection names, institutions and people. That is why the spatial report
had to guess technique by matching word fragments, and why `KhoeKhoen (Khoi)` ended
up in an *engraved* term list: **the source files it correctly as a tradition, and
those 267 records are the same 267 that the substring matcher was about to
misclassify.** The distinction was never missing from the data - only from what
survived the import.

### Why this is additive

`ahg_site_attribute` already exists and is the right home:
`site_record_id, taxonomy, code, note`. It is empty on both instances (7 rows), so
nothing is overwritten and no existing access point, description or authority record
is touched. Records catalogued since the original import keep whatever they have;
the enrichment only adds rows that were not there.

    ahg_site_attribute
      taxonomy = 'technique'            code = Painting | Engraving | Painted engraving
      taxonomy = 'painting_technique'   code = Brush painted | Finger painted | Handprint
      taxonomy = 'engraving_technique'  code = Pecking | Scratching | Incision
      taxonomy = 'tradition'            code = San | KhoeKhoen (Khoi) | Bantu | ...

### Sequence

1. **Seed the vocabularies** into `ahg_dropdown` under the four taxonomies above, so
   they are editable in the Dropdown Manager rather than hardcoded. Normalise the
   obvious variants at this point - `Handprint`/`Handprints` are one term, and
   `KhoeKhoen (Khoi)` should agree with however RARI writes it elsewhere. **Ask RARI
   before merging any two values**; they are the vocabulary owners.
2. **Dry run the enrichment**, matching dump site -> `ahg_site_record` by the same
   name key the coordinate import uses (3,060 of 3,062 resolved to exactly one
   actor, so the join is known-good). Report unmatched rather than guessing.
3. **Apply.** Insert only; never update or delete an existing attribute row. A
   re-run must be a no-op, so match on
   `(site_record_id, taxonomy, code)` before inserting.
4. **Leave the access points alone.** The Subject and Genre terms stay exactly as
   they are. Nothing that currently works stops working, and the two can be
   reconciled later once RARI has seen the structured version.

### What this buys

- The spatial report can ask for technique directly instead of matching word
  fragments across two taxonomies - no `Khoekhoen`-as-engraving, no
  `Paintshop Shelter` as painted rock art.
- Andrew's question - brush painted versus finger painted versus engraved - becomes
  a field lookup rather than a text search, and `Painted engraving` (43 sites) stops
  having to be forced into one of two booleans.
- Tradition becomes queryable in its own right, which it currently is not.

### What it does not do

It does not fix descriptions catalogued after the original import, which have no
counterpart in the dump. Those keep only their access points until someone
catalogues technique on them. Coverage is therefore the 7,633 sites in the dump, not
the whole catalogue - state that plainly in any report built on it.
