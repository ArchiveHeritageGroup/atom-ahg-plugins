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
