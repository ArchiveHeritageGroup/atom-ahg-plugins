# Deploy TODO — pending per-instance steps

Running checklist of deploy steps that are **done on PSIS but still pending on other instances**.
Tick each instance as it is deployed. Each step runs **once per instance database**.

---

## STANDING STEP — run on EVERY deploy, first, right after `git pull`

```bash
cd <instance-root>            # e.g. /usr/share/nginx/atom
sudo atom-ahg-plugins/bin/link-plugins.sh
```

AtoM discovers plugins only via `plugins/<name>` symlinks → `atom-ahg-plugins/<name>`.
New plugins added to the repo have NO symlink until this runs. Enabling a plugin
(`atom_plugin.is_enabled=1`) **without its symlink fatals the whole site** on every
request (`InvalidArgumentException: The plugin "X" does not exist`) — this took WDB
down on 2026-06-08. The script is idempotent and only creates missing symlinks; it
does **not** enable/load anything (loading is driven by `atom_plugin.is_enabled`),
so it is always safe to run. Run it before enabling plugins or applying migrations.

---

## 2026-06-04 — Security MFA + audit chaining + AI chatbot

Shipped in `atom-ahg-plugins` (ahgSecurityClearancePlugin, ahgAuditTrailPlugin, ahgAIPlugin)
and `atom-framework` (web-auth/webauthn-lib). See
`docs/sessions/2026-06-04-security-mfa-audit-ai-parity.md`.

### Per-instance status

| Instance | Path | Database | Status |
|----------|------|----------|--------|
| PSIS / Archive | `/usr/share/nginx/archive` | `archive` | ✅ Done (migrations applied via PDO; live) |
| ANC | `/usr/share/nginx/atom` | `atom` | ☐ **Pending** |
| WDB (client, 41.162.30.249) | client root | client db | ☐ Pending (stash local theme mods before pull) |

### Steps to run on each pending instance (after `git pull`)

```bash
cd <instance-root>            # e.g. /usr/share/nginx/atom  (ANC)

# 1. framework dependency
(cd atom-framework && composer install --no-dev)

# 2. database migrations — run against THIS instance's AtoM db
#    Find the db name + creds in config/config.php (propel dsn), e.g. ANC = `atom`.
#    NB: some instances reject CLI `mysql` as root (PSIS does) — if so, apply via a
#    short PDO script loading creds from config/config.php (as was done on PSIS).
mysql <db> < atom-ahg-plugins/ahgSecurityClearancePlugin/database/add_webauthn_credential_table.sql
mysql <db> < atom-ahg-plugins/ahgSecurityClearancePlugin/database/add_audit_chain_columns.sql
mysql <db> < atom-ahg-plugins/ahgAuditTrailPlugin/database/add_audit_chain.sql

# 3. AI tables + Collection assistant nav link (idempotent)
php symfony ai:install

# 4. cache + restart
rm -rf cache/qubit/prod/* && sudo systemctl restart php8.3-fpm
```

### Notes
- The three migrations are guarded (`IF NOT EXISTS` / existence checks) — safe to re-run.
- `add_audit_chain.sql` **seal-forwards** the chain from the current `MAX(ahg_audit_log.id)` on
  that instance; historical rows stay unchained by design.
- After deploy, verify: `php symfony audit:chain` and `php symfony security:audit-verify`
  should both report the chain intact.
- Optional WebAuthn activation: passkeys work out of the box; per-role MFA is opt-in at
  `/security/2fa/policy`.
