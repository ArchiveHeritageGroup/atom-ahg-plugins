# Fix: enabling ahgMultiTenantPlugin must not 404 the primary site

**Date:** 2026-07-17
**Repo:** atom-ahg-plugins v3.79.101
**Trigger:** clean-room review - enabling `ahgMultiTenantPlugin` with no config
returned "Tenant Not Found" 404 on every host except localhost/127.0.0.1.

## Problem (footgun)

`ahgMultiTenantPluginConfiguration::handleUnknownDomain()` enforced host-based
tenant routing as soon as the plugin was enabled, regardless of whether any
tenant existed. For a bare IP/host it treats the first label as a tenant
subdomain, finds no match, and shows a 404 "Tenant Not Found". So an admin who
simply toggled the plugin on via the UI would take their entire site down, with
no guidance. The default excluded-domains list (`localhost,127.0.0.1`) did not
include the instance's own host.

## Fix (two guards)

1. **No enforcement until a routable tenant exists.**
   New `Tenant::hasRoutableTenant()` - true only if an **active** `heritage_tenant`
   row has a `domain` or `subdomain`. `handleUnknownDomain()` returns early when
   false. Enabling the plugin with zero tenants is now a no-op; enforcement
   begins once real tenants are configured.

2. **Always exclude the instance's own site host.**
   New `TenantResolver::getSiteHost()` parses the host from AtoM's `siteBaseUrl`
   (`sfConfig app_siteBaseUrl`, falling back to `setting`/`setting_i18n`).
   `isExcludedDomain()` now always treats that host (and its `www.`) as the
   base/admin site, so the primary site keeps working even after tenants are
   added.

## Verified (VM atom210, no residue)

| Scenario | Host | Result |
|---|---|---|
| 0 tenants (fresh enable) | any host | 200 (no enforcement) |
| tenant configured | siteBaseUrl host | 200 (excluded) |
| tenant configured | unknown host | 404 Tenant-Not-Found (enforced) |
| tenant configured | tenant domain | 200 (resolves) |

Test tenant created + deleted; no demo data left. The previously-needed manual
`ahg_settings` config (`multi_tenant_excluded_domains`/`_base_domain`) is now
optional - the code fix makes a fresh enable safe by default.
