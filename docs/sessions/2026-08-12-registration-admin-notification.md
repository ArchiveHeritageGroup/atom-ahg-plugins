# Registration admin notification, approval default, and hyphen sweep

**Date:** 2026-08-12
**Release:** atom-ahg-plugins v3.99.20
**Instances:** developed in `/usr/share/nginx/archive` (PSIS), verified on VM 192.168.0.131 (atom210, stock AtoM 2.10, no AHG theme)

## Administrators were never told a registration was waiting

The pending-registration banner on PSIS is rendered by the theme:
`ahgThemeB5Plugin/templates/_adminNotifications.php:92` queries
`ahg_registration_request` directly. That is the only surface, so on any instance
without that theme no administrator learned of a pending registration - a request
could sit indefinitely with the applicant waiting and nobody aware.

`ahgUserRegistrationPlugin` contributed no administrator navigation at all; it
registered a single anonymous entry (`user_register`).

Fixed by declaring an `AhgNav` entry with a badge callback counting requests in
status `pending` or `verified`. The theme renders whatever is registered and never
needs to know this plugin's schema.

### Gotcha: only `manage` and `browse` are rendered

`AhgNav::register('admin', ...)` appears in the `AhgNav` docblock as an example, but
nothing renders an `admin` group. The sole consumer is
`ahgCorePluginConfiguration.class.php:374`, which iterates exactly two groups:

    'manage' => 'quick-links-menu'
    'browse' => 'browse-menu'

`ahgThemeB5Plugin` uses only `AhgNav::safeUrl()`, never `AhgNav::resolved()`. A
registration under `admin` or `user` renders nowhere. Group counts across the repo:
`manage` 61, `user` 6, `admin` 2 (one of which was the docblock), `anonymous` 1.

**Use `manage`.** The badge then appears in AtoM's own quick-links menu via
ahgCorePlugin on an instance with no theme at all.

## Approval defaulted to contributor, and authenticated was not offerable

Two faults, the second worse than the first:

1. `RegistrationService::getDefaultGroupId()` defaulted to `102` (contributor), and
   `pendingSuccess.php` preselected 102. Approving on the defaults handed every
   self-service applicant edit rights over descriptions - an access grant nobody
   asked for and which the approving administrator was never shown.
2. The picker query (`modules/userRegistration/actions/actions.class.php:127`)
   filtered `acl_group.id > 99`, so authenticated (99) was excluded from the list
   entirely. Contributor was the *lowest* access an administrator could assign.

Fixed: `>= 99`, default `99`, template preselects 99. Nothing is written to
`acl_user_group` for 99 - `QubitUser::getAclGroups()` prepends it to every
authenticated user already, and an explicit row is the duplicate-role fault removed
earlier this cycle. `assigned_group_id` is still recorded as 99, so the approval
email names the access level correctly.

Verified end to end on 131: register -> verify email -> approve through the modal
without touching the picker -> `assigned_group_id=99`, `user.active=1`, zero
`acl_user_group` rows, account signs in, refused the add-record form. Badge
decremented 2 -> 1. Invisible to anonymous visitors and to approved non-admin users.

## ahgLibraryPlugin: 321 long dashes, not 4

An earlier count of "4 em dashes" was of user-visible strings spotted by eye and was
wrong by two orders of magnitude. Actual: 318 em, 1 en, 2 `&mdash;` across 100 files.

Contexts were checked before replacing rather than running a blind `sed`, because a
dash that is *matched* rather than *displayed* breaks silently. None were: code
comments, display strings, `?:` fallback placeholders (`$patron['patron_barcode'] ?:
'-'`), and MySQL column comments (28 of 30 SQL hits on comment lines; the other two
are `COMMENT` clauses inside `ALTER TABLE ... ADD COLUMN`).

### The real risk was i18n

`__('Acquisitions - Purchase Orders')` uses the string as the lookup key into
`i18n/template/messages.xml`. Changing the PHP without the XML would have left 11
strings untranslatable. Both sides were changed together; there is only the one
catalogue, so no per-language file could fall out of sync. Verified afterwards that
all 15 hyphenated `__()` strings still resolve to a `<source>`.

295 insertions against 295 deletions - a pure substitution, no lines added or lost.
92 changed PHP files lint clean; `messages.xml` still well-formed.

### Configurable defaults outlive a code fix

`OverdueNoticeService.php:443` reads
`$options['subject'] ?? 'Overdue Notice - {{library_name}} ({{today}})'`. The dash
was only a *default*: an administrator who had saved their own subject would keep
the old character regardless. Queried PSIS `ahg_settings` for values containing an em
or en dash - zero rows, so the default was the only source and nothing needs
migrating. Worth repeating the check on any instance where the notice was customised.

## Also in this release

- `ahgCorePlugin/lib/Services/EmailService.php` - `decryptSetting()` applied to
  `smtp_password` at both read sites, so the stored SMTP credential is no longer
  read as plaintext. `CharSet = UTF-8` and `isHTML(false)` on the PHPMailer path.
- `ahgSpectrumPlugin` - plain hyphen in the workflow notification subject.

## Files

- `ahgUserRegistrationPlugin/config/ahgUserRegistrationPluginConfiguration.class.php`
- `ahgUserRegistrationPlugin/lib/Services/RegistrationService.php`
- `ahgUserRegistrationPlugin/modules/userRegistration/actions/actions.class.php`
- `ahgUserRegistrationPlugin/modules/userRegistration/templates/pendingSuccess.php`
- `ahgCorePlugin/lib/Services/EmailService.php`
- `ahgSpectrumPlugin/modules/spectrum/actions/actions.class.php`
- 100 files in `ahgLibraryPlugin/`

## Verification notes

- The verification token column is `email_token`, not `verification_token`, and the
  route is `/register/verify/:token` (path segment, not a query parameter).
- Suppressing stderr on a `mysql` call hid an "Unknown column" error and produced an
  empty token that read as a legitimately empty value. Do not `2>/dev/null` a query
  whose result you are about to act on.

---

# Addendum (v3.99.21): the banner, and "error but it approves"

## A menu entry is not a notification

The AhgNav entry shipped in v3.99.20 rendered correctly and carried the right
count - and was still not seen. It sits inside the "Quick links" dropdown, closed
until somebody opens it, and it was the only item there. It reaches an
administrator who was already going to look, which is the one who did not need
telling.

The verification at the time reported the entry as present with badge "1" and also
reported `visible: false` with the dropdown toggle click timing out. Both were
explained away as "just a collapsed dropdown" rather than checked. **A DOM presence
assertion is not a reachability assertion.** If a check reports an element as not
visible, that is the result, not an inconvenience to reason past.

Added `lib/Listeners/PendingRegistrationBanner.php`, a `response.filter_content`
listener anchored on `</header>`, rendering a Bootstrap alert with a Review button.
Bootstrap classes only - a CSP nonce covers `<style>` and `<script>` elements but
never a `style=""` attribute, so an inline style is dropped on exactly the
instances configured correctly.

Gated: administrators only, GET only, HTML only, suppressed on the queue page
itself, and wrapped so a fault cannot take down a page it renders on every page of.
It counts `pending` alongside `verified` - a request stuck because the confirmation
mail never arrived is precisely the one somebody needs to see, and the queue offers
a manual verify for it.

## "Error: Only email-verified registrations can be approved" - on an approval that worked

Reported as "error but it approves". Both halves were true.

**Fault 1 - one test answering for every state.** `approve()` guarded with
`status !== 'verified'` and returned "Only email-verified registrations can be
approved" for everything else, including `approved`. A repeat submission of an
approval that had already succeeded was told the applicant had not confirmed their
email: untrue, and it reads as "the approval failed" when the account exists and is
active. `markVerified()` had the same lumping.

**Fault 2 - nothing stopped the second submission.** `#confirm-approve` was never
disabled. Approving creates the account and sends mail, so there is a visible pause
with no sign the click registered. Click again: the first POST succeeds and updates
the row, the second is answered with an error about the state the first just
created. Reproduced - three clicks sent three POSTs.

Fixed on both sides, because either alone leaves a hole: the button guard does not
help two browser tabs, and the server fix alone still fires redundant work.

- `approved` now returns `{"success":true,"already":true,"user_id":N}`. A second
  click means the administrator could not tell the first worked; the outcome they
  wanted holds, so saying so is the accurate answer. `rejected` and `expired` get
  their own messages. The early return happens **before** the transaction.
- `#confirm-approve` / `#confirm-reject` disable while in flight ("Approving...",
  "Rejecting...") and re-enable on failure so a genuine retry still works.
  `.btn-verify` already did this.

Verified on 131: three clicks -> 1 POST, no alert; re-approving an already-approved
request returns success with `reviewed_at` untouched, no duplicate user, no extra
ACL row; a genuinely `pending` request is still refused with the real message, so
the guard is not weakened.

### Notes for anyone testing this

- Registration is rate-limited to **5 per IP per hour**
  (`RegistrationService::isRateLimited()`). A signup that silently fails during a
  test run is probably this, not a bug.
- Synthetic POSTs to the admin endpoints are rejected by CSRF
  (`{"error":"CSRF token validation failed"}`). Drive them in-page via
  `page.evaluate` so they carry the real token - which also proves CSRF is live.
- The approve/reject success handlers index `td:nth-child(6)` for Status and
  `td:last-child` for Actions. The table has 8 columns; adding one before Status
  breaks the row update silently, and a row that does not update is what invites
  the second click.
