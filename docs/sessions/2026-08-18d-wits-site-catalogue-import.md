# Wits site catalogue import into archaeology (131)

**Date:** 18 August 2026
**Release:** atom-ahg-plugins v3.103.26
**Instance:** 192.168.0.131, now serving archaeology.theahg.co.za

## What was imported

`Wits Archaeology Collection catalogue.xlsx`, sheet **Site Catalogue WITS**, 4,708
rows. Each row is a site. Result on 131:

| | |
|---|---|
| Site records created | 4,682 (4,683 total) |
| Authority records created | 4,682, all with slugs and i18n |
| Carrying validated coordinates | 3,221 |
| Coordinate withheld - conflicts with its own Map No. | 16 |
| Repeated Map+Site keys, matched not duplicated | 26 |
| Orphans, missing slugs, impossible coordinates | 0 |

Every site record is `locality_sensitive = 1`, so positions are gated by
`LocalityVisibilityService`. Verified on a live record: anonymous sees
`-11.100000, 14.800000` marked approximate with the 11 km note; an administrator
sees `-11.083333, 14.750000 (WGS84)` plus map sheet 1114.

## The column that does not contain what its header says

**For 1,804 rows the `Longitude` column holds BOTH coordinates in one cell** -
`'22 50 54         29 54 21'` - alongside `?` and `Location unknown` placeholders.

Reading the two columns as labelled yields 1,523 positions. Parsing the combined
form as well yields **3,239**. A naive import would have silently lost ~1,700
positions and written junk strings as coordinates for others. Nothing in the file
signals this; only profiling the values does.

## Map No. validates the coordinate

Map No. encodes the degree square exactly as RARI's 1:50,000 sheet references do
(`1114` = 11 S, 14 E), so it is an independent witness to every parsed position:

| Verdict | Count |
|---|---|
| Confirmed - inside its own degree square | 3,237 |
| Transposed lat/lon, corrected | 2 |
| Conflict - withheld, needs a decision | 16 |

Same technique as [the RARI coordinate recovery](2026-08-18c-rari-coordinates-parity-and-report-fixes.md).

## Naming the 744 sites with no Site Name

Titles are drawn **only from data on the site's own row**:

- **513** from Farm name + site number, e.g. `2229 AA 63` -> "Den Staat 27 MS AA 63".
  Farm alone is not a site name - several sites share a farm - so the site number
  stays, which also keeps the identifier visible.
- **229** keep the Map + Site key where there is nothing else, e.g. `1729 BB 1`.
  An honest identifier beats an invented name.
- **0** blank.

⛔ **A naming source that looked good and was not.** The phase sheets (Zhizo, K2,
Khami, Mapungubwe, Leokwe, Transitional K2, Leopards Kopje) carry real site names
and would have named 47 of the 744 - but they key on `Site No.` **alone**, with no
Map No., and Site No. is **not unique**: `DA 1` is used by 34 different sites
across map sheets 1821, 2030, 2227, 2229, 2230, 2328. Joining on it attaches one
site's name to another and leaves no trace that it is wrong. Source dropped.

Also caught: three `Farm name` cells hold cataloguer's annotations rather than
farms - "3 sites marked 19", "pencil mark on map, no card or number or info".
Those fall back to the key rather than becoming names.

**283 titles repeat, and that is the source, not the import.** "Den Staat" appears
78 times in the original Site Name column; the spreadsheet has 3,006 distinct names
across 3,964 named rows.

## Elasticsearch makes bulk actor writes fail in two different ways

Both are base AtoM and neither mentions search in its error.

- **On create:** `QubitActor::save()` calls arElasticSearchPlugin, and
  `serializeI18ns()` receives null for a fresh actor -> `in_array(): Argument #2
  must be of type array, null given`. **The row is inserted before this throws**,
  so a failed batch leaves orphan actors with no site record. Observed: a 5-row
  test reported 5 errors and 0 created, while creating 5 orphans - visible only
  because the retry minted `ebo-2`, `mua-2` slugs. Fix: `QubitSearch::disable()`,
  which is what AtoM's own `importBulkTask` does; reindex afterwards.
- **On update:** `getDocument() on null`, and here the exception lands **before**
  the i18n write, so 513 renames reported as errors and none persisted. Disabling
  search does not help. Fix: write `actor_i18n.authorized_form_of_name` directly -
  it is a plain translated column with no nested set, slug or object row
  implications. Slugs deliberately left as minted so existing URLs keep working.

**The general lesson: check whether the write landed rather than trusting the
error count, in both directions.** One path reported failure and had written; the
other reported failure and had not.

## Tooling

`ahgSiteRecordPlugin/bin/import-site-catalogue` (v3.103.26) - dry run by default,
`--apply` to write, `--limit=N` to test. Matches on `site_number` so a re-run
updates rather than duplicates. Runs standalone because 131, like RARI, would
otherwise need a registered symfony task - see
[stock ProjectConfiguration](2026-08-18c-rari-coordinates-parity-and-report-fixes.md).

Expects: `title, site_number, map_sheet, latitude, longitude, period, site_type,
culture, farm, check`. Rows whose `check` is not confirmed/transposed get a site
record but no coordinate.

## Hostname move

`archaeology.theahg.co.za` now proxies to 192.168.0.131 (SSL terminated on 112,
`Host` and `X-Forwarded-*` passed so AtoM builds public URLs). The original
archaeology instance on 112 at `/usr/share/nginx/archeology` is untouched and
still holds its 133 descriptions; it simply no longer answers to that name.
Rollback: `/root/archaeology-enabled.conf.bak-2026-08-18`.

⚠️ Backing up `sites-available` would have restored the **wrong** file - the live
config is a real 7,938-byte file in `sites-enabled`, and `sites-available` held a
stale 5,684-byte copy.

⚠️ 131 was a LAN-only test VM and is now publicly reachable with 4,682 site
records and 3,221 coordinates behind a throwaway password (`Atom210@test`, user
`johanpiet`). Rotate before wider release.

## Open

- **16 coordinate conflicts** need a person to say whether the coordinate or the
  Map No. is right. In `/root/wits_site_import.csv` on 131, `check=conflict`.
- Search reindex was still running at hand-over; direct URLs and browse are
  current, search lags until it completes.
- The 4,708 rows are **sites**. The collection holdings themselves are not
  imported.
