# Wits Archaeological Collection — new instance seeded (session start)

Date: 2026-06-28
Instance: archeology (NEW) — `/usr/share/nginx/archeology`
URL (pending DNS/cert): https://archaeology.theahg.co.za
Stack: PSIS-style Symfony AtoM 2.10.0 + atom-framework + atom-ahg-plugins (NOT standalone Laravel Heratio)

## Purpose
Start of the **Wits Archaeological Collection** workstream — a standards-based
archaeological research archive on the Heratio/AtoM platform, following the proven
**RARI / ARADA** Wits model. AtoM provides the ISAD(G) archival + discovery layer;
the Heratio framework provides the researcher self-description portal, archival
review workflow, richer discovery, and look-and-feel.

Design principle: **provenance-first hierarchy** (researcher → project →
excavation/survey → series → file/item). Lithics, ceramics, faunal remains, maps,
field photographs are **subjects / object classes / formats / facets — never
top-level archival hierarchy**. This is an archaeological research archive with
controlled archival description, not an artefact inventory in AtoM.

Researcher workflow target: Draft → Submitted → Under Archival Review →
Returned for Revision / Approved → Published in AtoM.

## Done this session (seed milestone — LIVE)
- Cloned the PSIS `archive` **code tree** (code only, uploads excluded) into
  `/usr/share/nginx/archeology`; rewrote absolute symlinks; fixed anchored rsync
  excludes (an unanchored `log/`/`cache/` exclude had dropped 137 deep vendor files).
- Created an empty `archeology` database and **seeded it by a CLEAN AtoM install with
  ZERO reads of the production `archive` DB** (operator preference): `tools:install`
  (base schema + fixtures + OpenSearch index + admin) → `atom-framework/bin/install`
  (ahg tables + MVP plugins enabled) → `search:populate`. 344 tables.
- Per-instance isolation: own `archeology` DB, own `archeology_*` OpenSearch index
  prefix (config/app.yml `elasticsearch_index` + config/search.yml `name`).
- nginx vhost + php-fpm `ProtectSystem=full` storage drop-in installed; php-fpm
  restarted (PSIS prod verified healthy afterwards).
- Smoke test: homepage / browse / login all HTTP 200, ahgThemeB5 (Heratio) theme
  active, no fatal errors. Site title "Wits Archaeological Collection".
- MVP plugins enabled: ahgCore, ahgThemeB5, ahgSettings, ahgSecurityClearance,
  ahgDisplay (locked-core) + ahgAuditTrail, ahgBackup.

## Framework bug found + fixed (affects all fresh installs)
`atom-framework/database/install.sql` and several plugin `install.sql` files declared
~172 VARCHAR-ex-ENUM columns with an inline `COMMENT '...'` placed BEFORE
`CHARACTER SET / COLLATE / NOT NULL / DEFAULT`, which MySQL 8 rejects (error 1064).
The documentation-only column COMMENTs were stripped to let the install proceed.
**The same bug is present in the PSIS `archive` copy of install.sql — it needs an
upstream fix in the canonical atom-framework repo** before the next clean deploy.
Also: the base installer `arInstall::configureSearch` hardcodes the legacy
`arElasticSearchPlugin/config/search.yml` path while the stack now ships
`arOpenSearchPlugin`; bridged with a temporary config-only symlink during install.

## Pending (operator / infra)
- DNS A record for `archaeology.theahg.co.za`.
- `certbot --expand -d archaeology.theahg.co.za` (theahg.co.za SAN cert lacks it).
- Change the admin web password to a value distinct from the DB root password.

## Next (configuration layer, per brief)
Enable ahgResearchPlugin + browse/manage + landing/menu plugins; build the
archaeology IA/menu (Collections, Digital Objects, Sites & Places, People &
Researchers, Institutions, Excavations & Research Projects, Subjects, Functions &
Activities, Researcher Portal); Wits branding + conservative homepage copy;
ISAD(G)-mapped researcher submission types; controlled vocab as facets; reviewer
dashboard + review workflow before opening submissions; one pilot excavation
archive. Local-build-then-cutover-to-Wits pattern.
