# CSP: allowing style attributes without weakening style-src

Date: 2026-08-24
Instance: archaeology.theahg.co.za (192.168.0.131) - ENFORCING CSP
Release: atom-ahg-plugins v3.106.18 (the plugin half; the CSP change is per-server config)

## The finding

Reported by a parallel workbench session and verified independently here. An
authenticated crawl of archaeology produced 246 failures across 3,377 steps, of
which 241 were CSP violations - 239 "Applying inline style violates ...
style-src" and 2 inline event handlers. Independent spot check here: 23
inline-style violations across 7 pages, concentrated in /help (16) and /reports/
(6). Same mechanism, smaller sample.

Live policy confirmed by reading the response header, not the config: CSP is
ENFORCING on archaeology (PSIS is report-only), emitted by the app per request,
with `style-src 'self' 'nonce-<per-request>' https://fonts.googleapis.com`.

## The trap

**Adding 'unsafe-inline' to style-src would have changed NOTHING.** CSP Level 3
ignores 'unsafe-inline' in any directive that also carries a nonce-source or
hash-source. It would have deployed cleanly, reviewed as correct, and left all 239
violations in place.

## The fix

    style-src-attr 'unsafe-inline';

Added to the `csp: directives:` block in `/usr/share/nginx/atom/config/app.yml` on
.131 (backed up to /var/tmp/plugin-backup/ first). The directives are a free-form
string emitted essentially verbatim, so no code change was involved; per project
rules that file is per-server and safe to modify per deployment.

Style ATTRIBUTES are governed by style-src-attr, which falls back to style-src
when absent. A nonce cannot apply to an attribute - there is nowhere to put one -
so allowing them is the only option short of removing them. `<style>` ELEMENTS
keep their nonce, which is where the real XSS risk lives. This narrows what is
allowed rather than disabling the protection.

Degrades safely: style-src-attr is CSP3; a browser that does not know it falls
back to style-src, where violations continue but nothing breaks.

Result on the same 7 pages: **23 violations -> 0**.

## Deliberately NOT done

`script-src-attr 'unsafe-inline'` was proposed for the 2 inline event handlers.
Declined: 2 handlers is cheap to fix properly in the templates, and the directive
would trade away more than it buys for two occurrences. They belong to #303.

Archaeology only. PSIS is report-only, so this does not apply there.

The clean fix remains removing the inline style attributes - 2,348 across 661
files, tracked as #298 and #303. style-src-attr is the honest interim, not a
substitute.

## Also in this release: ahgExportPlugin cleanup

Removed the `file_exists`-guarded `require` of ahgCorePlugin's `AhgDb.php` from
`exportActions::boot()`. `AhgDb::` was never called anywhere in the plugin - dead
code. boot() now contains only the access guard, and the plugin has NO code
reference to any other plugin (the two remaining ahgIngestPlugin mentions are a
comment and a sentence of UI text about round-trip CSV compatibility).

Verified after: export screens render for admins, and /export, /export/csv and
/export/accessionCsv still return the login page anonymously.
