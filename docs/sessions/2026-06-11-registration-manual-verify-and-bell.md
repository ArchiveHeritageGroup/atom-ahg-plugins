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

## Note (separate lever)
The AHG mail settings page (`ahgSettings/email` → `email_setting` table → `\AhgCore\Services\EmailService`)
is already seeded with the gmail SMTP relay (host/port/user/from); deliverability is a
separate fix. The manual-verify path above unblocks onboarding regardless of mail state.
