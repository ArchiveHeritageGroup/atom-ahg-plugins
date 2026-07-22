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

## Follow-ups (v3.80.1)

- **Email notifications** on every workflow transition (best-effort via AhgCore EmailService,
  fallback `mail()` with the **default-culture** siteTitle sender - reusing the culture lesson):
  submit -> supervisor; endorse/reject -> applicant; lodge -> SAHRA reviewers + applicant;
  issue -> applicant + supervisor; decline -> applicant. Toggleable via `email_notifications`
  config. Wrapped so email never breaks the workflow.
- **Per-instance settings gate** (`sahra_enabled`, default OFF). The plugin ships in the shared
  AtoM/Heratio codebase, so instances outside SA (e.g. Australia) get the code but not the
  feature. Gate enforced by `preExecute()` in the actions (404 unless enabled; config/reviewer
  admin pages exempt so it can be switched on) and by `setFeatureEnabled()` which adds/removes
  the nav links via nested-set surgery (integrity-checked). Master toggle on `/sahra/config`.
  `sahra:install-menu` now delegates to `setFeatureEnabled(true)`.

## Deployment state (2026-07-22)

- **Wits (archaeology):** plugin enabled, feature ON, 2 nav links (Manage + user menu). Live.
- **PSIS (archive):** plugin enabled, feature ON, 1 nav link (Manage only - PSIS has no
  `quickLinks` menu; researcher hub reachable at `/sahra/my-applications`). Live.
- **Other jurisdictions:** feature OFF by default; nothing shows until an admin ticks the
  switch in `/sahra/config`.

Verified in rollback: `setFeatureEnabled(true/false)` adds/removes menu links with nested-set
integrity preserved (net-zero). Releases: v3.80.0 (plugin), v3.80.1 (email + gate).

## Round 2 (v3.80.3 -> v3.81.0): entry point, sites, documents

- **Menu security fix (v3.80.3):** added `modules/sahra/config/security.yml` - the theme's
  `_mainMenu.php` renders a menu child only if `$child->checkUserAccess()` ->
  `myUser::checkModuleActionAccess()` loads `modules/<mod>/config/security.yml`; with no
  file, `$this->security` carries over from the previously-checked item -> non-deterministic
  nav visibility. Now explicit (index: administrator; myapplications: any authed; all: secure).
- **Entry point moved to the Research dashboard (v3.81.0):** researchers apply from `/research`
  ("Heritage Permits" quick action, gated on the feature). The two top-nav links (Manage +
  quickLinks) were removed; `setFeatureEnabled()` no longer manages menu; `removeMenuLinks()`
  cleans up legacy rows. Fixed a pre-existing duplicate "Book Visit" button on the research
  dashboard. (Edits ahgResearchPlugin - a stable plugin - per Johan's direction.)
- **Site + dig areas:** permit links to ONE site (`information_object`, `linked_object_id`,
  type-ahead) + MANY dig areas (its child records, `sahra_permit_area`). New JSON routes
  `/sahra/apply/search-sites` + `/sahra/apply/site-areas`; JS loads the site's descendants as
  checkboxes. Shown on the permit view with a link to the site record (via `slug` table).
- **Documents:** `sahra_permit_document` + upload/download/delete. Files stored under
  `sf_upload_dir/sahra/<permit_id>/` (uploads/ is in php-fpm ReadWritePaths + writable),
  random stored names, allowed-ext + size cap (`max_upload_mb`, default 25). Auth-checked
  streaming download; applicant/admin delete. Upload on the application form (multipart) and
  the permit page. `storeUploadedDocuments()` normalises the PHP `$_FILES` multi-file shape.
- **Label:** "Supervising professor" -> "Supervisor" everywhere user-facing.

Tables now (7): sahra_permit, sahra_permit_area, sahra_permit_document, sahra_permit_log,
sahra_permit_report, sahra_reviewer, sahra_config. Verified: site search + dig-area load +
create-with-areas (rollback), uploads writable, routes respond, lint clean. Live Wits + PSIS.

## Round 3 (v3.81.2-4): assets, reviewer accounts, create-crash fix

- **Assets (v3.81.2/3):** the site type-ahead did nothing because (a) the plugin config loaded
  JS from `/plugins/<p>/js/x.js` (404 - must be `/plugins/<p>/web/js/x.js`) AND (b) this theme
  NEVER calls `include_javascripts()`, so `$response->addJavascript()` is silently dropped.
  Fix: include `sahra.js`/`.css` with a direct `<script>/<link>` in the templates that need
  them (applicationCreateSuccess, permitViewSuccess), after the form. Also added a "+ Add
  another document" button (multiple upload rows).
- **Create white-screen (v3.81.4):** `executeCreate` called
  `storeUploadedDocuments($id, $files, ...)` but the signature is `($files, $permitId, ...)` -
  args swapped -> TypeError on EVERY application submit (the record was already inserted, so
  attempts show up in My applications; only the confirmation was lost). Fixed the arg order.
- **Create SAHRA reviewer accounts (v3.81.4):** new form in Settings ("Create a new SAHRA
  reviewer account": full name/username/email/password/authority) -> `executeReviewerCreate`
  reuses `AhgUserManage\Services\UserCrudService::create()` (object/actor/i18n/slug + Argon2id
  password + group 99 authenticated ONLY - no admin/editor) then `addReviewer()`. Least
  privilege. Also fixed `executePermitView` to allow SAHRA reviewers to view a permit (they
  could see /sahra/review but were denied opening the permit to decide).

Route `/sahra/config/reviewer/create` (in the `preExecute` whitelist so it's reachable to
enable). Verified: create-flow no longer TypeErrors; UserCrudService path confirmed (CLI test
only blocked by QubitActor not being in the bare-bootstrap autoloader - fine in web context).

## Round 4 (v3.81.5): in-app supervisor/reviewer notification

Email notifications confirmed working (msmtp -> Gmail relay at OS level; SAHRA's mail()
fallback uses it since EmailService::isEnabled()/smtp_enabled is unset). Added an IN-APP
indicator in the theme user menu (`ahgThemeB5Plugin/modules/menu/templates/_userMenu.mod_standard.php`,
following the existing access-request/research/spectrum badge pattern):
- Top-bar badge on the user-menu toggle = count of heritage-permit actions awaiting the user
  (`$sahraTotal` = pending endorsements as nominated supervisor + submitted_to_sahra reviews
  for reviewers/admins).
- Profile dropdown "Heritage permits" section: "To endorse (N)" -> /sahra/approvals,
  "SAHRA review (N)" -> /sahra/review. Only shown when N>0. Gated on `isFeatureEnabled()` and
  wrapped in try/catch so it never breaks the (site-wide) user menu.
