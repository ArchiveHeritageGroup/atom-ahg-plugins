# 2026-06-29 — Archaeology fixes: CCO provenance link + duplicate Share button (v3.79.19, v3.79.20)

Scope: archaeology instance (`/usr/share/nginx/archeology`) + archive canonical repo.

## 1. CCO/museum record-view provenance link pointed to legacy museum (v3.79.19)
Symptom: on a CCO-rendered record, the actions-menu "Provenance" link went to
`/:slug/cco/provenance` (museum) which 404s, instead of the unified sector-neutral
provenance.

Root cause: the provenance sector-unification (v3.79.13–15) added unified provenance to
the mods/dc/rad/dacs/gallery standard templates and added a redirect to the museum cco
*action* — but it never updated the cco record-view's actions-menu LINK in
`ahgMuseumPlugin/modules/cco/templates/_actions.php`. That link still built the museum URL
via `cco_url($resource,'cco','provenance')`. Live: `/<slug>/cco/provenance` → 404,
`/provenance/<slug>` → 200. (Latent in archive canonical too, not just archaeology.)

Fix: point the link at the unified route:
```php
href="<?php echo !empty($resource->slug)
    ? url_for(['module'=>'provenance','action'=>'view','slug'=>$resource->slug])
    : cco_url($resource,'cco','provenance'); ?>"
```

## 2. "Share this record" button rendered twice (v3.79.20)
Symptom: duplicate "Share this record" button on the legacy information_object view.

Root cause: `ahgTimeLimitedShareLinkPlugin/lib/Listeners/ViewLinkInjector.php` injects the
share button/modal by hooking `response.filter_content` (preg_replace after the first
content div, limit 1). The listener is connected once, but `response.filter_content` can
fire more than once per request in Symfony 1.x — and `maybeInject()` had no idempotency
guard, so it injected a second button on the second fire.

Fix: idempotency guard at the top of `maybeInject()`:
```php
if (str_contains($content, 'ahgShareLinkModal')) {
    return null; // never inject twice
}
```

## Verification & deploy
Both lint-clean, mirrored archive→archaeology, cache cleared + php-fpm restarted on
archaeology (opcache.validate_timestamps=0 → restart mandatory). Released from archive
canonical: provenance link = v3.79.19, share guard = v3.79.20. Both pushed to origin/main
+ tags. archaeology runs its own checkout (v3.79.11 + local mods); files mirrored directly
rather than via git pull.
