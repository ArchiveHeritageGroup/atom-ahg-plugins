# Wave 8 — Help System Map + Workflow API Content-Type (2026-06-14)

**Repo:** atom-ahg-plugins · **Status:** built + verified live, unreleased (post-v3.68.1).

## Verify-first result for Wave 8
Mostly already present (audit over-counts hardest here). False gaps: Workflow REST API (real, not stubs — delegates to getDashboardStats/getMyTasks/getPoolTasks + computeForTask/getOverview), static-page, jobs, dropdown-manage all PRESENT. Genuinely-missing-but-not-clean: ACL enforcement/plugin-grants (scaffold, no consumer — checkPermission intentionally unwired), access-request SLA+email + Europeana/EDM + union catalogue (external/deferred). The one clean+complete buildable unit = the help System Map.

## Built — help System Map (ahgHelpPlugin)
- `helpActions::executeApiSystemMap` (`/help/api/system-map`) — Cytoscape graph JSON (root → category → plugin) from `atom_plugin` WHERE is_enabled=1 (nodes typed root/category/plugin, core flag). Admin-only; anon gets empty JSON at HTTP 200 (avoids the 4xx themed-page trap).
- `helpActions::executeSystemMap` (`/help/system-map`) — admin-gated view (`forward('admin','secure')`).
- `systemMapSuccess.php` — Cytoscape (vendored `ahgThemeB5Plugin/web/js/cytoscape.min.js`, same-origin/CSP-safe), breadthfirst tree layout, node-tap detail panel; CSP-nonce'd inline style + script.
- Routes added to config.

## Built — workflow API Content-Type fix (ahgWorkflowPlugin)
`executeApiStats` + `executeApiTasks` now `setContentType('application/json')` (they returned JSON bodies without the header; `executeApiSlaStatus` already did).

## Verified
- All `php -l` clean; cytoscape.min.js present (360KB, same-origin).
- `/help/system-map` → 403 (admin gate); `/help/api/system-map` (anon) → 200 `application/json` `{nodes:[],edges:[],count:0}`. Workflow endpoints still 200. No DDL (reads atom_plugin). fpm restarted.
- Authed graph render not visually confirmed (admin login) — API logic builds from atom_plugin.

## Wave 8 conclusion
Clean buildable backlog now exhausted. Remaining Wave-8 items are deferred-enforcement scaffolds (ACL) or external integrations (email/Europeana).
