# AtoM Heratio Security Hardening Playbook

**Version:** 1.0.0
**Author:** The Archive and Heritage Group (Pty) Ltd
**Date:** 2026-03-01
**Audience:** System administrators deploying AtoM Heratio in production environments

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [PHP Hardening (PHP 8.3)](#2-php-hardening-php-83)
3. [MySQL 8 Hardening](#3-mysql-8-hardening)
4. [Nginx Hardening](#4-nginx-hardening)
5. [File System Permissions](#5-file-system-permissions)
6. [Fail2Ban](#6-fail2ban)
7. [Log Rotation](#7-log-rotation)
8. [Backup Security](#8-backup-security)
9. [Verification Checklist](#9-verification-checklist)

---

## 1. Introduction

This playbook provides a comprehensive server hardening guide for production deployments of AtoM Heratio. It covers the full stack: PHP, MySQL, Nginx, file system, intrusion prevention, logging, and backup security.

All recommendations assume an Ubuntu 22.04 server running PHP 8.3, MySQL 8, and Nginx. Adjust paths and service names if your distribution differs.

**Important:** Apply changes incrementally and test after each section. Some settings (notably `open_basedir` and `disable_functions`) can break application functionality if configured incorrectly.

---

## 2. PHP Hardening (PHP 8.3)

### 2.1 Configuration File Locations

- FPM pool config: `/etc/php/8.3/fpm/pool.d/www.conf`
- FPM php.ini: `/etc/php/8.3/fpm/php.ini`
- CLI php.ini: `/etc/php/8.3/cli/php.ini`

Changes below apply to the **FPM php.ini** unless noted otherwise. The CLI php.ini has different requirements because CLI tasks (ingest, preservation, AI) legitimately need functions like `exec`.

### 2.2 Hide PHP Version

Prevent PHP from advertising its version in HTTP response headers.

```ini
; /etc/php/8.3/fpm/php.ini
expose_php = Off
```

### 2.3 Error Handling

Never display errors to end users in production. Log them instead.

```ini
; /etc/php/8.3/fpm/php.ini
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/php-fpm-error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

Create the log directory:

```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
sudo chmod 750 /var/log/php
```

### 2.4 Session Security

Harden session cookies against XSS, man-in-the-middle, and CSRF attacks.

```ini
; /etc/php/8.3/fpm/php.ini
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = Strict
session.use_strict_mode = 1
session.use_only_cookies = 1
session.name = ATOMSESSID
session.gc_maxlifetime = 3600
```

**Notes:**

- `cookie_secure = On` requires HTTPS. Do not enable this on plain HTTP deployments.
- `cookie_samesite = Strict` prevents the session cookie from being sent on cross-site requests. If your deployment integrates with external authentication (SSO, OAuth), you may need to use `Lax` instead.

### 2.5 Open Basedir Restriction

Restrict PHP file access to only the directories it needs.

```ini
; /etc/php/8.3/fpm/php.ini
open_basedir = /usr/share/nginx/archive:/mnt/nas/heratio:/tmp:/var/log/php
```

If running multiple AtoM instances, include all instance paths:

```ini
open_basedir = /usr/share/nginx/archive:/usr/share/nginx/atom:/mnt/nas/heratio:/tmp:/var/log/php
```

**Warning:** Test thoroughly after enabling `open_basedir`. Any file access outside these paths will fail silently or throw errors. Ensure all upload directories, cache directories, and NAS mounts are included.

### 2.6 Disable Dangerous Functions (FPM Only)

Disable shell execution functions in the FPM (web-facing) context. The CLI php.ini must **not** disable these, as CLI tasks (ingest, preservation, AI processing) require them.

```ini
; /etc/php/8.3/fpm/php.ini — web requests only
disable_functions = exec,system,passthru,shell_exec,proc_open,popen,pcntl_exec,pcntl_fork,pcntl_signal,pcntl_waitpid,pcntl_wexitstatus
```

**Do NOT add these to `/etc/php/8.3/cli/php.ini`.** The following CLI tasks depend on shell execution:

| CLI Task | Functions Required | Purpose |
|----------|--------------------|---------|
| `ingest:commit` | `exec`, `proc_open` | Background job launch, AI processing |
| `preservation:*` | `exec` | ClamAV, Siegfried, checksum tools |
| `ai:*` | `exec`, `proc_open` | Python script invocation (spaCy, Argos) |
| `digitalobject:regen-derivatives` | `exec` | ImageMagick, FFmpeg |

### 2.7 Additional PHP Hardening

```ini
; /etc/php/8.3/fpm/php.ini
; Limit upload and POST sizes
upload_max_filesize = 256M
post_max_size = 260M
max_file_uploads = 50

; Limit execution time for web requests
max_execution_time = 120
max_input_time = 60
memory_limit = 512M

; Disable remote file inclusion
allow_url_fopen = On
allow_url_include = Off

; Disable potentially dangerous functions
enable_dl = Off
```

### 2.8 Apply Changes

```bash
sudo systemctl restart php8.3-fpm
```

---

## 3. MySQL 8 Hardening

### 3.1 Run the Security Script

If not already done during initial setup, run the MySQL secure installation:

```bash
sudo mysql_secure_installation
```

This removes anonymous users, disables remote root login, removes the test database, and reloads privilege tables.

### 3.2 Manual Cleanup (if security script was skipped)

```sql
-- Remove anonymous users
DELETE FROM mysql.user WHERE User = '';

-- Remove test database
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db = 'test' OR Db = 'test\\_%';

-- Ensure root cannot connect remotely
DELETE FROM mysql.user WHERE User = 'root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

FLUSH PRIVILEGES;
```

### 3.3 Network Binding

Restrict MySQL to listen only on localhost. Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
bind-address = 127.0.0.1
skip-networking = OFF
```

If no remote database access is needed at all:

```ini
[mysqld]
skip-networking = ON
```

### 3.4 Integrity Ledger Append-Only Access

The `integrity_ledger` table is designed as an append-only audit log. Create a dedicated database user for the web application and restrict it from modifying or deleting ledger entries:

```sql
-- Create a dedicated web application user (if not already using one)
CREATE USER 'atom_web'@'localhost' IDENTIFIED BY '<strong_password_here>';

-- Grant general application permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON archive.* TO 'atom_web'@'localhost';

-- Revoke UPDATE and DELETE on the integrity ledger to enforce append-only
REVOKE UPDATE, DELETE ON archive.integrity_ledger TO 'atom_web'@'localhost';

-- The web application can only SELECT and INSERT into the ledger
-- Verification:
SHOW GRANTS FOR 'atom_web'@'localhost';
```

Expected effective permissions on `integrity_ledger`:

```
GRANT SELECT, INSERT ON archive.integrity_ledger TO 'atom_web'@'localhost';
```

**Note:** Administrative tasks that require ledger maintenance (e.g., archival purge after retention period) should use a separate privileged account, not the web application account.

### 3.5 Slow Query Log

Enable the slow query log to identify performance bottlenecks:

```ini
; /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2
log_queries_not_using_indexes = 1
```

### 3.6 Connection Tuning

```ini
[mysqld]
max_connections = 200
wait_timeout = 300
interactive_timeout = 300
max_allowed_packet = 256M
```

Adjust `max_connections` based on your expected concurrent users. A single AtoM instance typically needs 50-100 connections. Monitor with:

```sql
SHOW STATUS LIKE 'Max_used_connections';
SHOW STATUS LIKE 'Threads_connected';
```

### 3.7 Apply Changes

```bash
sudo systemctl restart mysql
```

---

## 4. Nginx Hardening

### 4.1 Hide Server Version

Edit `/etc/nginx/nginx.conf` (inside the `http` block):

```nginx
server_tokens off;
```

### 4.2 Security Headers

Add these headers to your server block or a shared include file:

```nginx
# /etc/nginx/snippets/security-headers.conf

# Prevent clickjacking
add_header X-Frame-Options "SAMEORIGIN" always;

# Prevent MIME type sniffing
add_header X-Content-Type-Options "nosniff" always;

# XSS protection (legacy browsers)
add_header X-XSS-Protection "1; mode=block" always;

# Referrer policy
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# Permissions policy
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;

# HSTS — enforce HTTPS for 1 year, include subdomains
# WARNING: Only enable after confirming HTTPS works correctly.
# Once browsers cache this header, HTTP access is impossible until expiry.
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

Include in your server block:

```nginx
server {
    ...
    include /etc/nginx/snippets/security-headers.conf;
    ...
}
```

**Note on CSP:** AtoM Heratio manages Content Security Policy through `config/app.yml` and the `QubitCSPFilter` (see CLAUDE.md). Do not add a duplicate CSP header in Nginx, as it will conflict with the application-level CSP. The application generates per-request nonces that Nginx cannot replicate.

### 4.3 Rate Limiting

Define rate limit zones in the `http` block of `/etc/nginx/nginx.conf`:

```nginx
http {
    # General rate limit: 10 requests/second per IP
    limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;

    # Strict rate limit for admin/login: 5 requests/second per IP
    limit_req_zone $binary_remote_addr zone=admin:10m rate=5r/s;

    # API rate limit: 20 requests/second per IP
    limit_req_zone $binary_remote_addr zone=api:10m rate=20r/s;

    ...
}
```

Apply rate limits in your server block:

```nginx
server {
    ...

    # Admin paths
    location ~ ^/(admin|user/login) {
        limit_req zone=admin burst=10 nodelay;
        limit_req_status 429;
        # ... proxy/fastcgi pass ...
    }

    # API endpoints
    location ~ ^/(api|graphql) {
        limit_req zone=api burst=30 nodelay;
        limit_req_status 429;
        # ... proxy/fastcgi pass ...
    }

    # General pages
    location / {
        limit_req zone=general burst=20 nodelay;
        # ... proxy/fastcgi pass ...
    }
}
```

### 4.4 Block Bad Bots

Create a map to block known bad user agents. Add to the `http` block:

```nginx
# /etc/nginx/snippets/bad-bots.conf
map $http_user_agent $bad_bot {
    default 0;
    ~*(?:AhrefsBot|MJ12bot|SemrushBot|DotBot|BLEXBot|MegaIndex) 1;
    ~*(?:Scrapy|curl/|python-requests|Go-http-client) 1;
    ~*(?:masscan|nikto|sqlmap|nmap) 1;
    "" 1;
}
```

In your server block:

```nginx
server {
    include /etc/nginx/snippets/bad-bots.conf;

    if ($bad_bot) {
        return 403;
    }

    ...
}
```

**Note:** Review and adjust the bot list for your environment. Some entries (e.g., `curl`, `python-requests`) may block legitimate integrations or monitoring tools.

### 4.5 SSL/TLS Configuration

```nginx
server {
    listen 443 ssl http2;

    ssl_certificate     /etc/ssl/certs/your-cert.pem;
    ssl_certificate_key /etc/ssl/private/your-key.pem;

    # TLS 1.2 and 1.3 only — disable older protocols
    ssl_protocols TLSv1.2 TLSv1.3;

    # Strong cipher suites
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305';
    ssl_prefer_server_ciphers on;

    # OCSP stapling
    ssl_stapling on;
    ssl_stapling_verify on;
    resolver 8.8.8.8 8.8.4.4 valid=300s;
    resolver_timeout 5s;

    # Session caching
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_session_tickets off;

    # Diffie-Hellman parameter (generate with: openssl dhparam -out /etc/ssl/dhparam.pem 4096)
    ssl_dhparam /etc/ssl/dhparam.pem;

    ...
}
```

Generate the DH parameter file (do this once, it takes several minutes):

```bash
sudo openssl dhparam -out /etc/ssl/dhparam.pem 4096
```

### 4.6 Disable Directory Listing

```nginx
server {
    autoindex off;
    ...
}
```

### 4.7 Restrict Access to Sensitive Paths

```nginx
server {
    # Block access to hidden files (except .well-known for Let's Encrypt)
    location ~ /\.(?!well-known) {
        deny all;
        return 404;
    }

    # Block access to configuration and data files
    location ~* \.(sql|yml|yaml|ini|log|env|bak|old|orig|swp)$ {
        deny all;
        return 404;
    }

    # Block access to version control directories
    location ~ /\.(git|svn|hg) {
        deny all;
        return 404;
    }

    ...
}
```

### 4.8 Apply Changes

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 5. File System Permissions

### 5.1 Ownership Model

| Path | Owner | Group | Purpose |
|------|-------|-------|---------|
| `/usr/share/nginx/archive/` | `root` | `www-data` | Application root |
| `/usr/share/nginx/archive/cache/` | `www-data` | `www-data` | Symfony cache (must be writable) |
| `/usr/share/nginx/archive/log/` | `www-data` | `www-data` | Application logs |
| `/usr/share/nginx/archive/uploads/` | `www-data` | `www-data` | User uploads |
| `/usr/share/nginx/archive/atom-framework/` | `root` | `www-data` | Framework (read-only for web) |
| `/usr/share/nginx/archive/atom-ahg-plugins/` | `root` | `www-data` | Plugins (read-only for web) |
| `/mnt/nas/heratio/archive/` | `www-data` | `www-data` | Digital object storage (NAS) |

### 5.2 Set Permissions

```bash
# Application root — owned by root, readable by www-data
sudo chown -R root:www-data /usr/share/nginx/archive
sudo chmod -R 755 /usr/share/nginx/archive

# Cache directory — writable by www-data
sudo chown -R www-data:www-data /usr/share/nginx/archive/cache
sudo chmod -R 770 /usr/share/nginx/archive/cache

# Log directory — writable by www-data
sudo mkdir -p /usr/share/nginx/archive/log
sudo chown -R www-data:www-data /usr/share/nginx/archive/log
sudo chmod -R 770 /usr/share/nginx/archive/log

# Upload directories — writable by www-data, not world-readable
sudo chown -R www-data:www-data /usr/share/nginx/archive/uploads
sudo chmod -R 750 /usr/share/nginx/archive/uploads

# NAS mount — writable by www-data
sudo chown -R www-data:www-data /mnt/nas/heratio/archive
sudo chmod -R 750 /mnt/nas/heratio/archive

# Plugin directories — read-only for web user
sudo chown -R root:www-data /usr/share/nginx/archive/atom-ahg-plugins
sudo chmod -R 755 /usr/share/nginx/archive/atom-ahg-plugins

# Framework — read-only for web user
sudo chown -R root:www-data /usr/share/nginx/archive/atom-framework
sudo chmod -R 755 /usr/share/nginx/archive/atom-framework

# Ensure no world-writable files exist
sudo find /usr/share/nginx/archive -type f -perm -o+w -exec chmod o-w {} \;
```

### 5.3 Sensitive File Protection

```bash
# Protect configuration files
sudo chmod 640 /usr/share/nginx/archive/config/app.yml
sudo chmod 640 /usr/share/nginx/archive/apps/qubit/config/config.php
sudo chmod 640 /usr/share/nginx/archive/config/databases.yml

# Protect the framework bin directory
sudo chmod 750 /usr/share/nginx/archive/atom-framework/bin
```

---

## 6. Fail2Ban

### 6.1 Install Fail2Ban

```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 6.2 AtoM Admin Brute Force Protection

Create a filter for repeated 401/403 responses on admin paths.

```ini
# /etc/fail2ban/filter.d/atom-admin.conf
[Definition]
failregex = ^<HOST> .* "(GET|POST) /(admin|user/login).* (401|403)
ignoreregex =
```

### 6.3 API Brute Force Protection

```ini
# /etc/fail2ban/filter.d/atom-api.conf
[Definition]
failregex = ^<HOST> .* "(GET|POST|PUT|DELETE) /(api|graphql).* (401|403|429)
ignoreregex =
```

### 6.4 Jail Configuration

Create a local jail override (never edit the main `jail.conf`):

```ini
# /etc/fail2ban/jail.d/atom.conf

[atom-admin]
enabled  = true
port     = http,https
filter   = atom-admin
logpath  = /var/log/nginx/access.log
maxretry = 5
findtime = 300
bantime  = 3600
action   = iptables-multiport[name=atom-admin, port="http,https", protocol=tcp]

[atom-api]
enabled  = true
port     = http,https
filter   = atom-api
logpath  = /var/log/nginx/access.log
maxretry = 10
findtime = 60
bantime  = 1800
action   = iptables-multiport[name=atom-api, port="http,https", protocol=tcp]

[sshd]
enabled  = true
port     = ssh
maxretry = 3
findtime = 600
bantime  = 86400
```

| Jail | Trigger | Ban Duration |
|------|---------|-------------|
| `atom-admin` | 5 failed admin/login attempts in 5 minutes | 1 hour |
| `atom-api` | 10 failed API attempts in 1 minute | 30 minutes |
| `sshd` | 3 failed SSH attempts in 10 minutes | 24 hours |

### 6.5 Apply and Verify

```bash
sudo systemctl restart fail2ban
sudo fail2ban-client status
sudo fail2ban-client status atom-admin
sudo fail2ban-client status atom-api
```

### 6.6 Unban an IP (if needed)

```bash
sudo fail2ban-client set atom-admin unbanip <IP_ADDRESS>
```

---

## 7. Log Rotation

### 7.1 AtoM Application Logs

```ini
# /etc/logrotate.d/atom
/var/log/atom/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php8.3-fpm > /dev/null 2>&1 || true
    endscript
}
```

### 7.2 Nginx Logs

```ini
# /etc/logrotate.d/nginx (usually exists by default, verify and adjust)
/var/log/nginx/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 640 www-data adm
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 $(cat /var/run/nginx.pid) || true
    endscript
}
```

### 7.3 PHP Error Logs

```ini
# /etc/logrotate.d/php-fpm
/var/log/php/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php8.3-fpm > /dev/null 2>&1 || true
    endscript
}
```

### 7.4 MySQL Slow Query Log

```ini
# /etc/logrotate.d/mysql-slow
/var/log/mysql/mysql-slow.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 640 mysql adm
    sharedscripts
    postrotate
        mysqladmin flush-logs > /dev/null 2>&1 || true
    endscript
}
```

### 7.5 Create Log Directories

```bash
sudo mkdir -p /var/log/atom
sudo chown www-data:www-data /var/log/atom
sudo chmod 750 /var/log/atom
```

### 7.6 Verify Rotation

```bash
# Test configuration without actually rotating
sudo logrotate --debug /etc/logrotate.d/atom
```

---

## 8. Backup Security

### 8.1 Encrypt Database Backups

Use `gpg` symmetric encryption to protect database dumps at rest.

```bash
#!/bin/bash
# /usr/local/bin/atom-backup-encrypted.sh

BACKUP_DIR="/var/backups/atom"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="archive"
PASSPHRASE_FILE="/root/.atom-backup-passphrase"

mkdir -p "$BACKUP_DIR"

# Dump and encrypt in a single pipeline (no unencrypted file on disk)
mysqldump -u root "$DB_NAME" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    | gpg --batch --yes --symmetric \
          --cipher-algo AES256 \
          --passphrase-file "$PASSPHRASE_FILE" \
          --output "$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gpg"

# Verify the encrypted file was created
if [ -f "$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gpg" ]; then
    echo "Backup created: ${DB_NAME}_${DATE}.sql.gpg ($(du -sh "$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gpg" | cut -f1))"
else
    echo "ERROR: Backup failed!" >&2
    exit 1
fi

# Remove backups older than 30 days
find "$BACKUP_DIR" -name "*.sql.gpg" -mtime +30 -delete

# Set restrictive permissions
chmod 600 "$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gpg"
```

Set up the passphrase file:

```bash
# Generate a strong passphrase
openssl rand -base64 48 > /root/.atom-backup-passphrase
chmod 600 /root/.atom-backup-passphrase
```

**Store a copy of the passphrase in a separate, secure location (e.g., a password manager or physical safe). If the passphrase is lost, encrypted backups cannot be recovered.**

### 8.2 Decrypt and Restore

```bash
# Decrypt
gpg --batch --yes --decrypt \
    --passphrase-file /root/.atom-backup-passphrase \
    --output /tmp/restore.sql \
    /var/backups/atom/archive_20260301_030000.sql.gpg

# Restore
mysql -u root archive < /tmp/restore.sql

# Clean up the unencrypted file immediately
shred -u /tmp/restore.sql
```

### 8.3 Offsite Backup Recommendations

| Method | Description | Frequency |
|--------|-------------|-----------|
| rsync over SSH | Sync encrypted backups to a remote server | Daily |
| S3-compatible storage | Upload to AWS S3, Wasabi, or MinIO with server-side encryption | Daily |
| NAS replication | TrueNAS snapshot replication to an offsite NAS | Hourly snapshots, daily replication |
| Physical media | Encrypted USB/tape for air-gapped archival | Monthly |

Example rsync to a remote server:

```bash
rsync -avz --delete \
    /var/backups/atom/ \
    backup-user@offsite-server:/backups/atom/ \
    -e "ssh -i /root/.ssh/backup_key -p 22"
```

Example upload to S3-compatible storage:

```bash
aws s3 sync /var/backups/atom/ s3://your-bucket/atom-backups/ \
    --sse AES256 \
    --storage-class STANDARD_IA
```

### 8.4 Test Restore Procedures

**Schedule quarterly restore tests.** A backup that has never been tested is not a backup.

Restore test procedure:

1. Provision a test environment (VM or container) matching the production stack.
2. Copy the latest encrypted backup to the test environment.
3. Decrypt and restore the database.
4. Deploy the same application version.
5. Verify application functionality: login, browse, search, digital object access.
6. Verify data integrity: compare record counts, spot-check specific records, validate integrity ledger checksums.
7. Document the test results: date, backup file used, time to restore, issues found.

```bash
# Quick record count verification after restore
mysql -u root archive -e "
SELECT 'information_object' AS entity, COUNT(*) AS count FROM information_object
UNION ALL
SELECT 'actor', COUNT(*) FROM actor
UNION ALL
SELECT 'digital_object', COUNT(*) FROM digital_object
UNION ALL
SELECT 'integrity_ledger', COUNT(*) FROM integrity_ledger;
"
```

### 8.5 Backup File Retention

| Location | Retention | Notes |
|----------|-----------|-------|
| Local (`/var/backups/atom/`) | 30 days | Automated cleanup in backup script |
| Offsite (remote server/S3) | 90 days | Configure lifecycle rules on S3 or cron on remote |
| Archival (physical/cold storage) | 1 year | Monthly snapshots for long-term retention |

---

## 9. Verification Checklist

Use this checklist to verify that all hardening measures have been applied.

### PHP

- [ ] `expose_php = Off` confirmed: `curl -sI https://your-site/ | grep -i x-powered-by` returns nothing
- [ ] `display_errors = Off` confirmed: trigger a 500 error and verify no stack trace is shown
- [ ] `session.cookie_httponly = On` confirmed: inspect Set-Cookie header for `HttpOnly` flag
- [ ] `session.cookie_secure = On` confirmed: inspect Set-Cookie header for `Secure` flag
- [ ] `session.cookie_samesite = Strict` confirmed: inspect Set-Cookie header
- [ ] `open_basedir` configured and application functions normally
- [ ] `disable_functions` set in FPM php.ini (not CLI php.ini)
- [ ] CLI tasks (`ingest:commit`, `preservation:*`, `ai:*`) still function correctly

### MySQL

- [ ] No anonymous users: `SELECT User, Host FROM mysql.user WHERE User = '';` returns empty
- [ ] No test database: `SHOW DATABASES LIKE 'test';` returns empty
- [ ] `bind-address = 127.0.0.1` confirmed
- [ ] Slow query log enabled and writing to `/var/log/mysql/mysql-slow.log`
- [ ] `integrity_ledger` append-only grants verified for web application user

### Nginx

- [ ] `server_tokens off` confirmed: response headers do not show Nginx version
- [ ] Security headers present: `curl -sI https://your-site/ | grep -iE "x-frame|x-content|strict-transport"`
- [ ] Rate limiting active on admin and API paths
- [ ] SSL Labs test grade A or A+: https://www.ssllabs.com/ssltest/
- [ ] Directory listing disabled
- [ ] Hidden files and sensitive extensions blocked

### File System

- [ ] Upload directories are 750, owned by `www-data`
- [ ] Plugin and framework directories are 755, owned by `root:www-data`
- [ ] Cache directory is 770, owned by `www-data`
- [ ] No world-writable files: `find /usr/share/nginx/archive -type f -perm -o+w | wc -l` returns 0
- [ ] Configuration files (app.yml, config.php, databases.yml) are 640

### Fail2Ban

- [ ] Service running: `sudo systemctl status fail2ban`
- [ ] Jails active: `sudo fail2ban-client status` shows `atom-admin`, `atom-api`, `sshd`
- [ ] Test: simulate failed logins and verify ban activates

### Logging

- [ ] Log directories exist with correct ownership
- [ ] Logrotate configs test successfully: `sudo logrotate --debug /etc/logrotate.d/atom`
- [ ] Logs are rotating and compressing (check `/var/log/atom/` for `.gz` files after a few days)

### Backups

- [ ] Encrypted backup script is scheduled in cron
- [ ] Passphrase stored securely in a separate location
- [ ] Offsite backup sync is operational
- [ ] Last restore test date: ______________ (must be within 90 days)

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2026-03-01 | The Archive and Heritage Group (Pty) Ltd | Initial release |
