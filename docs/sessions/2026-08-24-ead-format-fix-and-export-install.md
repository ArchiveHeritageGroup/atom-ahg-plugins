# The EAD "outage" that wasn't, and installing ahgExportPlugin

Date: 2026-08-24
Instances: archaeology.theahg.co.za (.131), psis.theahg.co.za
Release: atom-ahg-plugins v3.106.16

## Starting point: "Export = no entries" on /reports/

The Export card on the reports dashboard rendered a header and nothing. Cause was
not a bug: every entry is gated on a plugin - ahgExportPlugin (CSV, EAD),
ahgSpectrumPlugin, ahgHeritageAccountingPlugin (GRAP 103) - and none was installed
on archaeology. The gates are deliberate; a comment in the template records that
ungated links 404'd, found by crawling this dashboard on 2026-08-18.

Two cards render empty on a lean install: Export and Sector Dashboards. Everything
else on the dashboard is populated.

## A wrong diagnosis, corrected

Probing exports, `/:slug;ead` returned HTTP 500 on archaeology AND PSIS, and
`;dc` returned HTML rather than XML. Reported as a fleet-wide EAD outage. **Both
claims were wrong.**

- `QubitInformationObject::urlForEadExport()` generates the URL with
  `sf_format=xml`. The record page's own link carries it. Probing without it
  tested a URL nothing generates.
  `/:slug;ead?sf_format=xml` returns 200 `text/xml` with valid EAD 2002 on both
  instances. EAD export was never broken.
- `;dc`, `;isad`, `;mods`, `;rad`, `;dacs` return HTML **correctly** - they are
  display templates rendering the record in each descriptive standard, not
  exports. Only `;ead` and `;eac` produce XML.

Lesson: when a whole class of URLs looks broken, check how the application itself
generates them before concluding anything.

## The real defect, and the fix

A bare `;ead` with no `sf_format` genuinely returned 500. sfEadPlugin ships only
`indexSuccess.xml.php`; with the format defaulting to html symfony finds no
template and raises *The template "indexSuccess.php" does not exist or is
unreadable in ""*. Recurring in `ahg_error_log` - crawler traffic, not users.

Unfixable where it breaks: the template is in `plugins/sfEadPlugin`, the routing in
`lib/`, both base AtoM. Fixed on our side with a `controller.change_action`
listener in ahgCorePlugin, `defaultFormatForXmlOnlyAction()`: when no format was
requested and the module/action has no HTML template but does have an XML one, set
the format to xml.

It is a deliberate sibling of the existing `refuseUnavailableFormat()`, which
handles the opposite case (a format WAS requested and no such template exists) and
returns early when the format is empty or html - so the two can never both act on
one request. Verified:

    ;ead                  500 -> 200 text/xml, valid EAD
    ;ead?sf_format=xml    200 xml   (unchanged)
    ;dc                   200 html  (unchanged)
    record page           200 html  (unchanged)
    ;ead?sf_format=json   404       (the other guard still fires)

No new exposure: `;ead?sf_format=xml` already served that content anonymously, so
this only removes a 500 on a variant URL.

## Installing ahgExportPlugin on archaeology

No schema to install - the plugin has no `database/` directory at all. Install was
a symlink plus enablement:

- `plugins/ahgExportPlugin` -> `../atom-ahg-plugins/ahgExportPlugin`
- appended to the serialized plugin list in `setting_i18n` id=1 (42 -> 43), which
  is what actually loads plugins on a stock instance
- `atom_plugin` row added too, because the help-article filter and the reports
  gate helper read that table

Backed the `setting_i18n` value up to `/var/tmp/plugin-backup/` before editing it,
and did the edit by unserialize/append/serialize rather than string surgery.

Result: the Export card now carries CSV Export and EAD Export, both routes render.

## Security posture of the newly exposed module

`modules/export` shipped with **no security.yml**, which fails open. It was not a
live hole - `exportActions::boot()` on `AhgController` runs for every action and
enforces authenticated + (administrator OR editor), with a comment noting that
export dumps unpublished records and repository contact PII. Confirmed
empirically: all seven actions return the login page anonymously.

Added `modules/export/config/security.yml` declaring the same rule for all 7
actions plus a secured `all:` fallback, so the posture no longer rests entirely on
boot() continuing to run for every action, including any added later.

## Is ahgExportPlugin standalone? Yes

- No schema, no tables to install.
- All 21 tables it queries are base AtoM.
- No theme dependency, registers its own 11 routes, now has its own security.yml.
- Three references to other AHG plugins, none of them code: two mentions of
  ahgIngestPlugin in a comment and in UI text about round-trip CSV compatibility,
  and a `file_exists`-guarded `require` of ahgCorePlugin's `AhgDb.php` in boot()
  where **`AhgDb::` is never called anywhere in the plugin** - dead code.

Real dependencies: atom-framework (a hard dependency for every AHG plugin) and
base AtoM.

Open: the vestigial AhgDb require could be deleted, leaving zero cross-plugin
references. Sector Dashboards still renders as an empty card; the fix is to not
render a card whose every entry is gated off.
