# IIIF V3 manifest crash — namespaced `sfConfig` fix (ahgIiifPlugin)

- **Date:** 2026-05-29
- **Repo / release:** `ArchiveHeritageGroup/atom-ahg-plugins` — fix **live on PSIS (local)**, release pending (`./bin/release patch` → v3.46.2).
- **Instance:** PSIS — `https://psis.theahg.co.za` (`/usr/share/nginx/archive`)
- **Trigger:** Admin error log (`/ahgSettings/errorLog`) — "fix the errors".

## Root cause
`ahgIiifPlugin/lib/Services/IiifManifestV3Service.php` declares `namespace AhgIiif\Services;` but referenced the global Symfony class as a bare `sfConfig::`. PHP resolves an unqualified class name inside a namespace to `AhgIiif\Services\sfConfig`, which does not exist:

```
Error: Class "AhgIiif\Services\sfConfig" not found
  at IiifManifestV3Service.php:208
```

`enrichManifestWithEmbeddedMetadata()` runs on **every** IIIF manifest request (IPTC/XMP enrichment from the first image digital object), so any `/iiif/manifest/<slug>` view fataled. This was the dominant entry in the log — **11 of 18 unresolved errors** across multiple manifests (png/tiff viewers, museum vase, marble statue, etc.).

A second, latent instance hid behind a broken guard on line 221:
```php
$webDir = defined('sfConfig::get(\'sf_web_dir\')')   // string is never a defined constant → always false
    ? sfConfig::get('sf_web_dir')                    // dead code (also bare sfConfig)
    : (\defined('SF_WEB_DIR') ? SF_WEB_DIR : dirname(__DIR__, 5) . '/web');
```
The bogus `defined(...)` made the ternary always take the else branch, so line 222's bare `sfConfig` never executed — which is why the fatal surfaced at 208, not 222.

## Fix
Documented `\`-prefix gotcha for global classes in namespaced AtoM plugin files:
- L208: `sfConfig::get('sf_plugins_dir')` → `\sfConfig::get('sf_plugins_dir')`
- L221–222: replaced the broken `defined('sfConfig::get(...)')` guard with `class_exists('\sfConfig')` + `\sfConfig::get('sf_web_dir')`

No functional/layout change — pure namespace-resolution correctness.

## Verification
- `php -l IiifManifestV3Service.php` → clean; no remaining bare `sfConfig::`.
- Restarted `php8.3-fpm` (mandatory on this host — `opcache.validate_timestamps=0`).
- `GET /iiif/manifest/png-hrt-png` (previously fataled) → **HTTP 200**, valid IIIF Presentation 3 JSON.

## Other log entries (not code-fixable here)
- **5× export 500s** — `/<slug>;skos` (term/actor SKOS RDF) and `?sf_format=xml&template=dc`. Base AtoM `lib/` + compiled cache, bot-crawled. Locked upstream — "resolve and ignore."
- **2× IPTC fallback** — `info` level, normal logging, not errors.

## Log table
After the fix verified, `TRUNCATE TABLE ahg_error_log` (700 rows → 0) at the user's request.

## Follow-up
- PSIS-parity twin filed: verify the Heratio (Laravel) IIIF manifest service — Laravel uses `config()` not `sfConfig`, so the exact bug is not expected there, but the parallel enrichment path should be confirmed.
- Release commands handed to user (not auto-pushed per project rules).
