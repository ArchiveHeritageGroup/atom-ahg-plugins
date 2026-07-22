# 2026-07-22 - ahgSAHRAPlugin: SAHRA / NHRA heritage permit workflow

**Repo:** atom-ahg-plugins. **New plugin:** ahgSAHRAPlugin v1.0.0. Live on **archaeology (Wits)**.

## Why

Wits needed a heritage-permit workflow under the **National Heritage Resources Act, 1999
(Act 25 of 1999)** - the SA equivalent of the Zimbabwe NAZ permit. Requested flow:
**researcher applies -> supervising professor endorses -> submitted to SAHRA -> SAHRA
records the outcome**, and (added mid-build) **SAHRA reviewers approve from their side** on
the Wits instance directly.

## Workflow / roles

| Stage | Role | Entry |
|---|---|---|
| Apply | Researcher (applicant) | `/sahra/apply`, `/sahra/my-applications` |
| Endorse / return | Supervising professor (nominated on the form) | `/sahra/approvals` |
| Lodge with SAHRA | Heritage coordinator / admin | `/sahra/queue` |
| Issue / decline permit | **SAHRA reviewer (own account on Wits)** | `/sahra/review` |
| Dashboard, all permits, settings | Admin | `/sahra`, `/sahra/permits`, `/sahra/config` |

Status lifecycle: `pending_supervisor -> supervisor_approved -> submitted_to_sahra ->
active`; branches `supervisor_rejected`, `sahra_rejected`, `revoked`, `expired`, `closed`.

**SAHRA-side approval:** admins designate SAHRA officials as **SAHRA reviewers** (Settings
page). Reviewers (or admins) get `/sahra/review` and may issue (permit number, validity,
conditions) or decline lodged applications in-system. Gated by `sahra_reviewer` table +
`isSahraReviewer()`; the decision action uses `checkDecider()` (reviewer OR admin).

## Scope covered (NHRA)

s.35 archaeology / palaeontology / meteorites, s.32 heritage-object export, s.34 structures,
s.36 burial grounds & graves. Issuing authority = SAHRA or a PHRA (Heritage Western Cape,
Amafa, etc.) - configurable list. Reporting obligations (interim/final/annual/fieldwork),
auto-expiry (`php symfony sahra:permit-expiry --process`), full per-permit workflow log.

## Structure (19 files + 1 task)

- `extension.json`, `config/ahgSAHRAPluginConfiguration.class.php` (autoloader `AhgSAHRA\`,
  module `sahra`, RouteLoader routes, asset loading)
- `database/install.sql` - `sahra_permit`, `sahra_permit_log`, `sahra_permit_report`,
  `sahra_reviewer`, `sahra_config` (VARCHAR+COMMENT not ENUM; seeds config only, no
  `atom_plugin` insert)
- `lib/Services/SahraPermitService.php` - full workflow (create/endorse/reject/submit/
  recordDecision/revoke/cancel, reviewers, reports, expiry, log)
- `modules/sahra/actions/actions.class.php` (AhgController) + 10 templates + `_statusBadge`
- `lib/task/sahraPermitExpiryTask.class.php` (`sahra:permit-expiry`)
- `lib/task/sahraInstallMenuTask.class.php` (`sahra:install-menu`, idempotent nested-set)
- `web/css/sahra.css`, `web/js/sahra.js` (decision issued/declined toggle)

## Deployment (archaeology / Wits)

1. Schema installed (5 tables + 5 config rows) on archive + archeology.
2. Plugin synced to `/usr/share/nginx/archeology/atom-ahg-plugins/ahgSAHRAPlugin` + symlink
   in `plugins/` on both instances.
3. Enabled via `atom_plugin` row on archeology (is_enabled=1).
4. Menu wired with `sahra:install-menu`: "SAHRA permits" under **Manage** (-> `sahra/index`),
   "Heritage permits" under **quickLinks** (-> `sahra/my-applications`). Nested-set integrity
   verified (n=73, 0 bad). Labels in `menu_i18n` (editable via Admin -> Menus).

Archive/PSIS has the files + schema + symlink but is **not enabled** (SAHRA permits are
archaeology-specific).

## Verification

Full workflow smoke-tested in a rollback transaction: apply -> endorse -> submit -> SAHRA
reviewer designated + issues permit (number + validity recorded) -> status `active`, 4 log
entries, dashboard stats correct. All routes dispatch (302 to login unauth). All PHP lint
clean.

## Notes / follow-ups

- `AhgController::redirect()` under Symfony = `sfActions::redirect()` (throws + stops); the
  no-throw override is standalone-only. Routes named via `RouteLoader::prependRoute`, so
  `url_for('@sahra_*')` resolves.
- Not yet done: fold `sahra:install-menu` into `bin/install`; email notifications on stage
  transitions; PSIS enablement (if wanted).
