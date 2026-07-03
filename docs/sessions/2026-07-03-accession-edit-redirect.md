# Accession edit — redirect to the view page after save

**Date:** 2026-07-03
**Repo:** atom-ahg-plugins · **Release:** v3.79.55 (patch)
**Plugin:** ahgAccessionManagePlugin
**Instances:** archive/PSIS + archaeology (synced, md5 MATCH)

## Symptom
Saving `/accession/:slug/edit` saved the record but did NOT route back to the
accession view page `/accession/:slug` — the user was left on / bounced around the
edit URL. Reported on archaeology (`/accession/2026-07-01-1/edit`).

## Cause
`ahgAccessionManagePlugin/modules/accession/actions/editAction.class.php` redirected
after save with the OBJECT form and **no explicit action**:

```php
$this->redirect([$this->resource, 'module' => 'accession']);
```

The plugin's `config/routing.yml` registers THREE `QubitResourceRoute`s for
`module=accession`: `index` (`/accession/:slug` = the view), `edit`
(`/accession/:slug/edit`) and `delete`. With no `action` specified, the object form
is ambiguous across those three routes, and from *inside* the edit action it could
regenerate the `/edit` URL instead of the view — so the user never landed on the
view page. This is exactly the `[$resource, 'module'=>'x']` pattern CLAUDE.md flags
as wrong.

## Fix
Redirect explicitly to the view route by slug (the CLAUDE.md-sanctioned form), with
the object form kept only as a fallback:

```php
$slug = $this->resource->slug ?? null;
if ($slug) {
    $this->redirect(['module' => 'accession', 'action' => 'index', 'slug' => $slug]);
}
$this->redirect([$this->resource, 'module' => 'accession']);
```

This matches `accession_view_override` (`url: /accession/:slug` → action index)
unambiguously, so save always returns to `/accession/:slug`.

## Deploy / verify
- Edited archive canonical, `cp` to archaeology (md5 MATCH), `rm -rf cache/qubit/prod/config`
  + `symfony cc` on both roots, `php-fpm` restart.
- Post-fix: `/accession/2026-07-01-1` → 403 (auth gate for anon, not 500),
  `/accession/2026-07-01-1/edit` → 200. Redirect fires on the next authenticated save.

## Files
- ahgAccessionManagePlugin/modules/accession/actions/editAction.class.php
