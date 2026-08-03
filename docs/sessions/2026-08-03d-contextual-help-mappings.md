# Contextual help mappings for heritage, IPSAS, label and Spectrum pages

**Date:** 2026-08-03
**Release:** atom-ahg-plugins v3.88.37
**Follows:** the F1 contextual help panel shipped earlier this session

## Why

The context map had 16 URL patterns. None covered the pages built or changed
today, so F1 on the heritage accounting dashboard, on any label or Spectrum
page, fell through to "no mapping" and opened the full help centre in a new
tab. That was the symptom reported earlier on `/provenance/test-ric/edit` - not
a one-off, but the default for every page outside those 16.

No new articles were needed. All six were already published and simply
unmapped: `heritage-accounting-user-guide`, `heritage-sites-user-guide`,
`ipsas-accounting-user-guide`, `label-printing-user-guide`,
`spectrum-user-guide`, plus `barcode-user-guide` (left unmapped, see below).

## Three things that made this non-mechanical

**`/heritage` is shared by two unrelated plugins.** ahgHeritageAccountingPlugin
owns `/heritage/dashboard`, `/browse`, `/add`, `/settings`, `/:id`,
`/object/:slug`. ahgHeritagePlugin (discovery platform) owns bare `/heritage`,
`/heritage/admin/*`, `/heritage/access/*`. One `/heritage` entry would have
served the accounting guide on discovery pages, or the reverse.

**Spectrum pages are slug-first** - `/:slug/spectrum/label`,
`/:slug/spectrum/condition-photos`. The matcher was prefix-only
(`path.indexOf(pattern) === 0`), so a `/spectrum` entry would **never have
fired**. It would have sat in the map looking correct while doing nothing.

**Bare `/heritage` could not be expressed.** The matcher breaks on first hit and
its final clause is a plain prefix, so `/heritage` inevitably swallowed
`/heritage/12`.

## What changed

`ahgHelpPlugin/js/help-context.js` - two optional matcher modes:

| `match` | Behaviour | Needed for |
|---------|-----------|-----------|
| `exact` | `path === pattern` | `/heritage` landing vs `/heritage/12` |
| `contains` | `path.indexOf(pattern) !== -1` | `/:slug/spectrum/*` |
| *(omitted)* | prefix, as before | everything existing |

`ahgHelpPlugin/modules/help/actions/actions.class.php` - 13 new entries.
**Order is load-bearing**: first match wins, so specific precedes general and
the exact-match landing page goes last.

`ahgThemeB5Plugin/templates/_layout_end.php` - cache bust to `v=1.2.0`.
**Mandatory, not cosmetic**: a cached copy of the old matcher combined with the
new map is worse than shipping nothing, because the old matcher treats `exact`
as an ordinary prefix and `/heritage` would swallow every accounting page.

## Verified

- 21 paths resolved against the map parsed out of the live PHP file, including
  both collision cases and five pre-existing mappings as regression checks.
  `/informationobject/browse` correctly matches nothing.
- `/help/api/context-map` serves 29 mappings with `match` keys intact through
  the server-side `$visibleSlugs` filter.
- The served JS carries both new matcher branches; the rendered tag is
  `...?v=1.2.0" nonce="..."` with the attributes properly separated.

## Gotchas worth keeping

- ⚠️ **Editing that script line nearly broke CSP.** The trailing space before
  `<?php` is load-bearing - without it the tag renders `...v=1.2.0"nonce="..."`,
  the nonce is not recognised as an attribute and CSP blocks the script. That
  is the "page appears frozen, JS silently blocked" signature from CLAUDE.md.
  Caught before release; check `cat -A` after any edit to those lines.
- `/` redirects to `/heritage`, so the site landing page is the discovery
  platform. A bare-prefix `/heritage` mapping would therefore have mislabelled
  the front door of the site.

## Deliberately not mapped

`barcode-user-guide` has no page of its own - barcodes render inside label
pages, which now map to label printing. `spectrum-compliance-dashboard`
describes a dashboard with no route I could find; pointing F1 at an unconfirmed
URL would produce a mapping that silently never fires. Both remain searchable.
