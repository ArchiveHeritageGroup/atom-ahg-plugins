# AtoM Heratio — User Registration Plugin

## Feature Overview

### Purpose

The User Registration Plugin enables public self-registration for AtoM Heratio instances, allowing external researchers, scholars, and institutional users to request access to the system. All registrations go through a structured email verification and administrator approval workflow before any account is created.

### Key Features

- **Public Registration Form** — Collects name, email, username, password, institution, research interest, and reason for access
- **Email Verification** — Sends verification link (48-hour expiry) to confirm email ownership
- **Administrator Approval Queue** — Admin dashboard at `/admin/registrations` to review, approve, or reject requests
- **Role Assignment** — Administrator selects the user group/role (contributor, editor, translator, etc.) on approval
- **Email Notifications** — Automated emails for verification, admin notification, approval, and rejection
- **Rate Limiting** — Configurable limit on registrations per IP address per hour
- **Security** — Passwords hashed with AtoM's dual-layer approach (SHA1 + Argon2); no plaintext stored

### Compliance

The plugin supports GLAM institution access control policies:
- **POPIA/GDPR** — Collects only necessary personal data; no account created until approved
- **NARSSA** — Supports researcher access request patterns for archives
- **Audit Trail** — All approvals/rejections recorded with admin ID, timestamp, and notes

### Workflow

```
User → Register → Verify Email → Admin Notified → Admin Reviews → Approve/Reject → User Notified
```

### Technical Requirements

- AtoM Heratio Framework v2.8.0+
- PHP 8.1+
- Email transport configured (SMTP, PHP mail, or sendmail)
- MySQL 8.0+

### Administration

- **CLI Cleanup** — `php symfony registration:cleanup` — removes expired unverified requests
- **Settings** — Configurable via `ahg_settings` table (enable/disable, rate limit, default group)
- **No base AtoM modifications required**

---

*AtoM Heratio is developed by The Archive and Heritage Group (Pty) Ltd for GLAM and DAM institutions worldwide.*
