# AtoM Heratio — User Registration Plugin

## Overview

The User Registration Plugin provides public self-registration with email verification and administrator approval workflow.

## How It Works

1. **User registers** at `/register` — fills in name, email, username, password, institution, research interest, and reason
2. **Email verification** — user receives a verification email with a link (valid for 48 hours)
3. **Admin notification** — after email verification, administrators receive an email about the new request
4. **Admin reviews** at `/admin/registrations` — can approve or reject with notes
5. **On approval** — user account is created (active), assigned to a group/role, and notified by email
6. **On rejection** — user is notified by email with the rejection reason

## Key Design Decisions

- **No user account is created on registration** — only a request record is stored. The actual AtoM user (object → actor → user chain) is only created upon admin approval.
- **Password is hashed immediately** using AtoM's dual-layer approach (SHA1 + Argon2), so plaintext is never stored.
- **Rate limiting** — max 5 registrations per IP per hour (configurable via `ahg_settings`).
- **Email verification required** — admins only see requests that have been email-verified.
- **48-hour token expiry** — unverified requests expire automatically.

## Admin Interface

### Approval Queue (`/admin/registrations`)

- Filter by status: All, Awaiting Review, Approved, Rejected
- Approve with group/role selection (default: contributor)
- Reject with mandatory reason (sent to applicant)
- All actions are AJAX — no page reload

### Settings (via `ahg_settings`)

| Key | Default | Description |
|-----|---------|-------------|
| `registration_enabled` | `1` | Enable/disable public registration |
| `registration_max_per_hour` | `5` | Max registrations per IP per hour |
| `registration_default_group` | `102` | Default ACL group on approval (102 = contributor) |

## Routes

| URL | Access | Description |
|-----|--------|-------------|
| `/register` | Public | Registration form |
| `/register/verify/:token` | Public | Email verification |
| `/admin/registrations` | Admin | Approval queue |
| `/admin/registrations/approve` | Admin (AJAX) | Approve request |
| `/admin/registrations/reject` | Admin (AJAX) | Reject request |

## CLI — Cleanup Expired Requests

```bash
php symfony registration:cleanup
```

Marks unverified requests older than 48 hours as expired. Run via cron for automated cleanup.

## Database

### Table: `ahg_registration_request`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| email | VARCHAR(255) | Applicant email (unique) |
| username | VARCHAR(255) | Requested username |
| password_hash | VARCHAR(255) | Dual-layer hashed password |
| salt | VARCHAR(64) | Password salt |
| full_name | VARCHAR(255) | Full name |
| institution | VARCHAR(255) | Institution/organization |
| research_interest | TEXT | Research interests |
| reason | TEXT | Reason for registration |
| status | VARCHAR(20) | pending, verified, approved, rejected, expired |
| email_token | VARCHAR(64) | Verification token (unique) |
| email_verified_at | TIMESTAMP | When email was verified |
| admin_notes | TEXT | Admin notes on approval/rejection |
| reviewed_by | INT | Admin user ID who reviewed |
| reviewed_at | TIMESTAMP | When reviewed |
| assigned_group_id | INT | Group assigned on approval |
| ip_address | VARCHAR(45) | Submitter IP for rate limiting |

## Dependencies

- `ahgCorePlugin` — ObjectService, I18nService, AhgSettingsService
- Email: Uses `sfMailer` (base AtoM) — requires mailer configuration in `config/app.yml`

## Email Configuration

Ensure mailer is configured in `apps/qubit/config/factories.yml` or `config/app.yml`:

```yaml
mailer:
  class: sfMailer
  param:
    delivery_strategy: realtime
    transport:
      class: Swift_SmtpTransport
      param:
        host: smtp.example.com
        port: 587
        encryption: tls
        username: your-email@example.com
        password: your-password
```

If no mailer is configured, emails will fail silently (registration still works, but no notifications).
