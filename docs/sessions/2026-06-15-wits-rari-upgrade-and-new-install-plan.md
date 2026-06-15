# Wits engagements — RARI upgrade + new VM install (scoping) — 2026-06-15

Two Wits engagements, both **blocked on Wits SSH access** (user obtaining). Stack confirmed by Johan: **Symfony AtoM + atom-framework + atom-ahg-plugins** (the PSIS/WDB-style modernization), NOT the standalone Laravel Heratio app.

## Engagement A — Upgrade RARI to AtoM/Heratio
RARI = Wits **Rock Art Research Institute** AtoM instance. Today it offers only "researcher register + request images".

### Findings from the import copy `/usr/share/nginx/rari` (root-owned snapshot, 2026-05-01; NOT served on this host — live RARI is at Wits)
- **Base AtoM ≈ 2.6/2.7** (webpack.config.js + package.json present → webpack era = 2.6+; not the old gulp/grunt era). Confirm exact version on the live box: `php symfony tools:get-version`.
- **Heavily customized — 5 bespoke `qt*` plugins** (Wits custom, not stock AtoM):
  - `qtResearcherPlugin` → `researcher` module = researcher registration (the "register" feature).
  - `qtSwordPlugin` → SWORD deposit protocol (likely the image deposit/request path).
  - `qtRegistryPlugin`, `qtServiceProviderPlugin`, `qtAccessionPlugin` → usage TBD (confirm against live).
- **Standalone `rock_forms/` PHP mini-app** (index.php, edit_data_rock_image.php, digital_site_record.php + rock_image_records.sql / site_records.sql) — bespoke rock-art image/site capture OUTSIDE AtoM's plugin system. Framework-agnostic.
- DB: copy's `databases.yml` points at `dbname=atom` (the ANC DB on this host = stale/wrong) — **no RARI DB on this host**; real data is at Wits.

### STRATEGY (Johan, 2026-06-15): full install + hide most, full researcher module active
**NOT** a feature-by-feature port of the qt* plugins. Instead: deploy the COMPLETE AtoM/Heratio platform (base 2.10.1 + atom-framework + ALL atom-ahg-plugins, enabled), keep **ahgResearchPlugin fully active + exposed**, and HIDE almost everything else at the UI layer (reversible — Wits can unhide later without reinstalling). The completed capability map confirms ahgResearchPlugin already delivers BOTH RARI features: public self-registration (`/research/register-researcher`, action publicRegister, creates user + research_researcher status=pending + admin approve/reject) AND the reproduction/image-request workflow (`/research/reproduction/new` → research_reproduction_request/_item/_file; staff review at /research/reproductions; secure download tokens). So no qt* port is needed.

Steps:
1. Upgrade base AtoM 2.9 → **2.10.1** (run AtoM upgrade + DB migrations + ES reindex).
2. Layer atom-framework + atom-ahg-plugins via bin/install (symlinks, install.sql, ProjectConfiguration template); enable the full plugin set.
3. **Hide-the-surface layer** (the "hide most of it"):
   - **Menu** (`ahgMenuManagePlugin`, DB `menu` table via MenuService): strip main nav to the researcher journey — Browse + "Register as researcher" + "Request images" (research portal).
   - **Landing** (`ahgLandingPagePlugin`): set landing to the researcher portal.
   - **ACL/groups**: anonymous + researcher see only Browse + Research; staff/admin features behind login.
   - Keep other plugins enabled but unlinked from nav/admin sidebar (capability present, hidden).
   - Theme/branding for RARI (arDominionB5 → ahgThemeB5Plugin).
4. **Data migration:** archival descriptions + digital objects/images migrate via the standard AtoM 2.9→2.10 upgrade (not re-import). Custom bit = legacy `researcher` table (QubitResearcher) → `research_researcher` (+ research_researcher_type, research_verification). rock_forms/ rock-art data: decide migrate-into-records vs keep sidecar (finalize on live DB).
5. Preserve researcher accounts + rock-art records through the jump.

### On-access runbook (WDB-pattern, to finalize once version/DB confirmed)
snapshot → `chown -R www-data` both repos → upgrade base AtoM to 2.10.1 + migrations → add atom-framework + atom-ahg-plugins → enable plugin profile (locked core + researcher/access-request subset) → migrate custom researcher/image-request data → ES reindex → verify → cutover. ⚠️ Reuse WDB gotchas: chown first, stash local theme mods, `mysql --force` enabled-plugins-only migrations.

## Engagement B — New Wits install from scratch
- Target: **new Wits VM** (Johan provisioning). Same-site as RARI.
- Clean framework+plugins deployment: base AtoM 2.10.1 + atom-framework + atom-ahg-plugins via `bin/install`.
- Open: URL/domain, full-GLAM vs researcher-subset profile, DB location (Wits-side).

## Blocking / awaiting Johan
1. **Wits SSH access** (host/user) for live RARI + new VM. [blocks all remote execution]
2. Confirm exact live AtoM version (`tools:get-version`).
3. Decide custom-plugin handling (port to ahg vs carry qt* forward) — see A.3.
4. New VM: URL, profile (full vs subset), DB location.

## What runs the moment access lands
- RARI: SSH in → `tools:get-version` + dump live DB → rebuild the local workbench on the REAL DB → execute the upgrade dry-run locally → produce the tested cutover runbook.
- New VM: `bin/install` clean stack → enable profile → verify.
