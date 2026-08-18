# Wits holdings, period access points, and the cross-plugin table problem

**Date:** 18 August 2026
**Releases:** atom-ahg-plugins v3.103.33 - v3.103.35, atom-framework v2.18.6
**Issues filed:** #301, #302

## Holdings: a row is a holding, the site is its creator

The site catalogue had already been imported as **site records on authority
records** (locality, gated). That was only half the model - nothing was browsable,
because a site is not a holding.

`bin/import-site-holdings` (v3.103.34) creates one archival description per row and
links it to its site through a creation event:

    description  <- information_object, one per row
       creator   <- event(type = creation, actor_id = the site)

Result on Wits: **4,682 descriptions, every one linked to a site**, 31 skipped as
already present.

Field mapping:

| Spreadsheet | AtoM |
|---|---|
| Site Name, else Map No. + Site No. | `title` |
| Map No. + Site No. | `identifier` |
| Period, Industry/Culture, Site Type, Artefacts, Organic remains, Samples, Rock art, C14, Slides/reports, Permits | `scopeAndContent` |
| Total boxes | `extentAndMedium` |
| Donor, Excavator, Accession date | `acquisition` |
| Collection location, Location | `locationOfOriginals` |
| Latitude / Longitude / Map No. | `ahg_site_record` on the ACTOR, gated |

⚠️ **Coordinates are deliberately NOT on the holding.** They live on the site, which
is where the gating lives. Two copies of a disclosure-sensitive value is exactly what
`LocalityVisibilityService` exists to prevent.

## Period as access points - and why only period

Free text in scope and content is searchable as prose but cannot be faceted or
counted. Splitting the four candidate columns on `, ; /`:

| Column | Distinct terms | Top 10 cover | Verdict |
|---|---|---|---|
| **period** | 44 | **97%** | converted |
| culture | 193 | 63% | needs a merge pass |
| artefacts | 439 | 64% | long tail |
| site_type | 560 | 49% | not a vocabulary |

`bin/import-holding-periods` (v3.103.35) normalised 44 raw values to **28 subject
terms** and created **4,022 access points** across 3,693 rows. Additive: scope and
content untouched, so the terms can be dropped without touching a description.

Normalisation, and its limits: multi-value cells split; a trailing `?` stripped
(`LIA?` indexes under LIA - the uncertainty stays in scope and content, but a facet
containing both is a worse facet); case and typo variants merged
(`historic`/`Hist`/`Historis`/`Historical` -> `Historic`). **Abbreviations are NOT
expanded** - LIA stays LIA. That is the cataloguers' vocabulary, and renaming a term
later is cheap where re-mapping records is not.

⛔ **Site type was left alone deliberately.** 560 distinct values for 2,423 rows is
free text with common words in it, not a vocabulary. Converting it would create 560
terms, most used once, and a facet nobody can use.

## Donor: stopped, because the column does not contain donors

2,705 rows, 603 distinct values. Categorised:

| What it holds | Rows | Examples |
|---|---|---|
| Initials + year | 637 | `ARM 92`, `BLW 87` |
| Uncategorisable codes | 560 | `06/95`, `3rd Yr 09` |
| **Possible real names** | **483** | `Alex Schoeman`, `Barnard` |
| Student cohorts | 451 | `H03`, `Honours 2004` |
| Initials only | 324 | `AJ`, `ARM` |
| Bare years | 140 | `1925` |
| Collection numbers | 110 | `Coll 35` |

Only ~18% look like a person. Building authority records from this would produce 603
actors called `JL`, `H05`, `Coll 35` and `1931`, sitting in Sites and People looking
authoritative and meaning nothing. **`JL` means someone specific to whoever wrote it;
inventing that authority record is worse than leaving text.**

Parked pending Wits. Review sheet prepared at
`/usr/share/nginx/archive/stuff/wits_donor_codes_for_review.csv` - all 603 codes with
counts and blank columns for *what it means* / *is a donor* / *authority record name*.

## Cross-plugin table queries: issue #302

Three 500s on three unrelated pages, all the same shape - a plugin querying a
DIFFERENT, optional plugin's tables with no existence check:

| Missing table | Owned by | Queried by | Page killed |
|---|---|---|---|
| `watermark_type` | ahgDAMPlugin | ahgCorePlugin | `/informationobject/add` |
| `object_rights_holder` | ahgExtendedRightsPlugin | framework AccessFilterService | any description view |
| `favorites_folder` | ahgFavoritesPlugin | ahgResearchPlugin | `/research/mobile/` |

The failure is total, not partial: an institution installing the MVP set and never
wanting DAM gets an SQL error where cataloguing should be.

Added `AhgCore\Core\AhgDb::hasOptionalTable()` - per-request cached - and guarded the
three that failed. ⚠️ **The sweep is NOT done**: roughly 77 call sites across six
tables remain (`spectrum_condition_check` alone has 28). See #302.

⚠️ **Choose the fallback per call site.** A feature list returns empty; an ACCESS or
RIGHTS check must fail OPEN, or an absent optional plugin silently hides published
material - which is what framework v2.18.5 already does correctly.

## ahgRuntimePlugin staleness: issue #301

Hit **three times today**. `ahgRuntimePlugin` is generated from the framework and
carries its own copy of the source; that copy is what PHP loads. After a framework
change both repos report the new tag, the file on disk has the change, `grep` finds
it - and the running code is the old copy. Presents as `Call to undefined method` on
a class that visibly has the method, and as bugs already fixed hours earlier.

Fix on an affected box:

    cd atom-framework && sudo bin/build-runtime-plugin

## Heritage: featured stories are now admin-driven

The config page said *"Stories are managed via the database. Contact your
administrator"* - a work order, not a feature. Added story CRUD to
`DiscoveryService` (framework v2.18.6) and an admin screen at
`/heritage/admin/stories` (v3.103.33), linked from the heritage sidebar. Create,
edit, enable/disable and delete verified end to end.

⚠️ The **filters** section still carries the same "managed via the database" message.
⚠️ The heritage module has **no `security.yml`** - every action is declaratively
public and the admin screens rely solely on imperative checks inside the action.

## Layout and menu fixes

- **Related people and organizations** rendered in the body, putting it under the
  main block. `layout_3col` puts `slot('sidebar')` left and `slot('context-menu')`
  right; the creator events now render in the context menu and the body copy is
  gone.
- **Duplicate Provenance.** ahgProvenancePlugin's injector guards on an
  `ahg-collections-management` marker, but the theme rendered the same block without
  it, so the injector could not tell and added a second. Marker added to the theme.

## State of the Wits box

37 plugins, `atom_plugin` reconciled at 36, ahgThemeB5 theme with the archaeology
palette (`#043673` / `#487393` / `#f4f1ea`) and Wits logo, heritage landing with the
Sterkfontein hero, branding in English and Afrikaans, 4,682 sites, 4,682 holdings,
28 period terms.

⚠️ **`search:populate` has NOT been run** since the holdings and period imports.
Indexing was disabled during both. Nothing appears in search or facets until it runs.

**Favorites and Feedback are not enabled** - neither is on 131, so cloning it
reproduced the gap. RARI has both. ahgFavoritesPlugin's *schema* was installed to
stop the `/research/mobile/` 500, but the plugin itself is off.

## Open

- Donor codes - parked pending Wits.
- 16 site coordinates conflicting with their own Map No.
- #302 sweep: ~77 call sites.
- #301: runtime plugin rebuild in deployment.
- Filters section still database-only; heritage `security.yml` missing.
