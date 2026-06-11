# 2026-06-11 — Registration: manual "Mark verified" + notification bell

## Problem (Johan)
A pending account-registration request (johanpiet2) was invisible and un-actionable:
- The verification email never reached the applicant (host mailer points at a dead
  localhost SMTP; only `/usr/sbin/sendmail`→msmtp→gmail actually relays).
- The admin queue showed **no Approve option** for a `pending` request — only the text
  "Awaiting email verification" — and `approve()` itself rejects anything not `verified`.
- There was **no notification** of the request anywhere.

So when email is down, a researcher can't self-verify, phones the admin, and the admin
had no way to push the request through. Johan's ask: "I just want to set verified flag to true."

## Fix (ahgUserRegistrationPlugin + ahgThemeB5Plugin)
1. **`RegistrationService::markVerified($requestId)`** — sets `status='verified'` +
   `email_verified_at=NOW()` for a `pending` request (no-op otherwise).
2. **`executeMarkVerified`** admin AJAX action (admin-gated via `boot()`).
3. Route **`admin_registrations_verify` → `/admin/registrations/verify`** (runtime
   RouteLoader, same pattern as approve/reject).
4. **`pendingSuccess.php`** — for `status==='pending'`, a yellow **Mark verified** button
   (confirm dialog → POST → reload). Once verified, the existing Approve/Reject buttons show.
5. **`_adminNotifications.php`** (theme bell) — new source: counts
   `ahg_registration_request` where `status IN ('pending','verified')` →
   "N account registration request(s) awaiting review" → links to `/admin/registrations`.
   Own try/catch (table absent on non-registration instances).

Also: johanpiet2 (request id=1) manually flipped pending→verified so it's approvable now.

## Workflow after this change
researcher registers (pending) → ideally verifies email (verified) → admin approves.
If email is down: admin sees the bell count → opens the queue → **Mark verified** →
**Approve**. No email dependency to onboard a researcher.

## Verified
Lint clean (5 files); `markVerified($requestId)` present via reflection; cache cleared +
fpm restarted. Button/queue render is admin-gated (not CLI-clickable) but mirrors the
proven approve/reject AJAX path. NOT released yet.

## Email deliverability wired (settings-driven)
All four `RegistrationService` email methods (`sendVerificationEmail`,
`notifyAdminsNewRegistration`, `sendApprovalEmail`, `sendRejectionEmail`) were calling
AtoM's Swift mailer `\sfContext::getInstance()->getMailer()` — which targets a dead
localhost:25 SMTP, so nothing ever delivered. Re-routed all four to
**`\AhgCore\Services\EmailService::send()`**, which is driven by the mail settings page
(`email_setting` table). PHPMailer is not installed, so `EmailService` falls back to native
`mail()` → `/usr/sbin/sendmail` (symlink to **msmtp**) → `smtp.gmail.com:587` (auth as
`pieterse.johan3@gmail.com`, the same relay Heratio uses). msmtp rewrites the envelope to
the authenticated gmail account; gmail accepts (250 OK).

**Confirmed end-to-end:** `EmailService::send()` to johan@plainsailingisystems.co.za was
received (unique `uniqid()` marker matched). So the mail settings page now genuinely drives
registration email, exactly as asked ("settings page must work … not point somewhere else").

`email_setting` smtp group is seeded: smtp_enabled=1, host=smtp.gmail.com, port=587,
encryption=tls, username=pieterse.johan3@gmail.com, password set, from=johan@plainsailingisystems.co.za,
from_name="AtoM Archive". Adjustable on the `ahgSettings/email` page. (If PHPMailer is ever
installed, `EmailService` switches to direct gmail SMTP using these same values.)

Note: `/var/log/msmtp.log` did not always capture www-data sends in testing (a logging
quirk) — delivery itself is proven by receipt, not the log.
