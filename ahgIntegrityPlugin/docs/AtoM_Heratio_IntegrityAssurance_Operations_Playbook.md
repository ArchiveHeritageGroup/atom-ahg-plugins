# AtoM Heratio - Integrity Assurance Operations Playbook

**Version:** 1.2.0
**Author:** The Archive and Heritage Group (Pty) Ltd
**Plugin:** ahgIntegrityPlugin
**Last Updated:** 2026-03-01

---

## Table of Contents

1. [Alert Types and Response](#1-alert-types-and-response)
2. [Common Failure Causes](#2-common-failure-causes)
3. [Dead Letter Triage Procedure](#3-dead-letter-triage-procedure)
4. [Escalation Matrix](#4-escalation-matrix)
5. [Remediation Playbooks](#5-remediation-playbooks)
6. [Monitoring Best Practices](#6-monitoring-best-practices)

---

## 1. Alert Types and Response

The Integrity Assurance plugin evaluates alert thresholds after every verification run via `IntegrityAlertService::checkThresholds()`. Alerts are configured in the `integrity_alert_config` table and can fire via email, webhook, or both. Each alert type is described below with its meaning, typical causes, and recommended response steps.

### 1.1 pass_rate_below

**What it means:** The percentage of objects that passed fixity verification in a run has dropped below the configured threshold. The pass rate is calculated as `(objects_passed / objects_scanned) * 100`. A healthy system should maintain a pass rate above 99%.

**Typical causes:**

- Storage failure: a disk, RAID array, or NAS volume has developed bad sectors or gone offline, causing widespread file corruption or inaccessibility.
- NAS mount offline: the TrueNAS mount at `/mnt/nas/heratio/archive` (or `/mnt/nas/heratio/atom`) has disconnected. All files served from the mount will appear as missing, dragging the pass rate down sharply.
- Bulk file migration or reorganisation performed without updating the `digital_object.path` column.
- Environmental damage (e.g., power failure during disk write) affecting multiple files simultaneously.

**Response steps:**

1. Check mount status immediately:
   ```bash
   mount | grep /mnt/nas
   df -h /mnt/nas/heratio/archive
   ls -la /usr/share/nginx/archive/uploads/r/
   ```
2. If the mount is offline, re-mount and re-run the schedule:
   ```bash
   sudo mount -a
   # Verify mount is back
   df -h /mnt/nas/heratio/archive
   # Re-run the schedule that triggered the alert
   php symfony integrity:schedule --run-id=<SCHEDULE_ID>
   ```
3. If the mount is online, check the run detail in the web UI (Admin > Integrity > Run History) to identify which objects failed. Filter the ledger by outcome to determine whether failures are `mismatch`, `missing`, or `unreadable`.
4. Review the dead letter queue for patterns -- are failures concentrated in a specific repository or directory path?
5. If failures are legitimate mismatches (files genuinely corrupted), proceed to the remediation playbook for `mismatch` in Section 5.

### 1.2 failure_count_above

**What it means:** The total number of non-passing outcomes in a single run (failed + missing + error) has exceeded the configured threshold. Unlike `pass_rate_below`, this is an absolute count -- useful for detecting problems even in large collections where a low percentage still represents many objects.

**Typical causes:**

- Same root causes as `pass_rate_below` (storage failure, mount offline) but measured in absolute terms.
- A batch of recently ingested files that were corrupted during upload.
- Permission changes on a directory subtree rendering files unreadable.

**Response steps:**

1. Check the dead letter queue for new entries:
   ```bash
   php symfony integrity:report --dead-letter
   ```
2. Investigate the file system for the affected objects. The run detail page shows the digital object IDs and file paths that failed.
3. Cross-reference with system logs for I/O errors:
   ```bash
   dmesg | grep -i "error\|fault\|i/o"
   journalctl -u smbd --since "1 hour ago"   # If using SMB mount
   ```
4. If failures are concentrated in a single repository or path, check that specific storage location for hardware issues.
5. Acknowledge or triage dead letter entries as described in Section 3.

### 1.3 dead_letter_count_above

**What it means:** The number of unresolved dead letter entries (status `open`, `acknowledged`, or `investigating`) has exceeded the configured threshold. Dead letters accumulate when objects fail verification repeatedly. A growing dead letter queue indicates systemic issues that are not self-resolving.

**Typical causes:**

- Persistent storage issues that have not been addressed.
- Files that were intentionally deleted but whose database records were not cleaned up.
- Permission problems that persist across runs.
- Stale NAS mounts that intermittently go offline.

**Response steps:**

1. View the dead letter queue in the web UI (Admin > Integrity > Dead Letter Queue) or via CLI:
   ```bash
   php symfony integrity:report --dead-letter --format=json
   ```
2. Triage the dead letter queue following the procedure in Section 3.
3. Filter by `failure_type` to identify the dominant failure category:
   - If mostly `missing`: investigate file system / mount issues.
   - If mostly `mismatch`: investigate data corruption or intentional modifications.
   - If mostly `unreadable` or `permission_error`: investigate file permissions.
4. Bulk acknowledge entries that are under investigation to prevent re-alerting:
   - Use the web UI dead letter action buttons (Acknowledge, Investigate, Resolve, Ignore).
5. Set a target to reduce the dead letter count below the alert threshold within a defined timeframe.

### 1.4 backlog_above

**What it means:** The number of master digital objects that have never been verified (not present in the `integrity_ledger` at all) exceeds the configured threshold. The backlog is calculated by `IntegrityService::calculateBacklog()` as the count of `digital_object` rows with `usage_id = 140` that have no corresponding ledger entry.

**Typical causes:**

- Verification schedules are disabled or not running frequently enough.
- Batch size is too small relative to the ingest rate -- new objects are being added faster than they can be verified.
- The cron job for `integrity:schedule --run-due` is not configured or has stopped running.
- A large bulk ingest has added many new objects that have not yet been reached by the scheduler.

**Response steps:**

1. Check if schedules are enabled and running:
   ```bash
   php symfony integrity:schedule --status
   php symfony integrity:schedule --list
   ```
2. If no schedules are enabled, enable the appropriate schedule:
   ```bash
   php symfony integrity:schedule --enable=<SCHEDULE_ID>
   ```
3. Check that the cron job is properly configured:
   ```bash
   crontab -l | grep integrity
   ```
   The recommended cron entry is:
   ```
   */15 * * * * cd /usr/share/nginx/archive && php symfony integrity:schedule --run-due >> /tmp/integrity-scheduler.log 2>&1
   ```
4. To reduce the backlog quickly, consider:
   - Increasing the `batch_size` on the active schedule (e.g., from 200 to 1000).
   - Adding a temporary daily schedule with a large batch size.
   - Running an ad-hoc verification manually:
     ```bash
     php symfony integrity:verify --limit=5000 --stale-days=0
     ```
5. Monitor the backlog reduction over subsequent runs:
   ```bash
   php symfony integrity:verify --status
   ```

### 1.5 run_failure

**What it means:** A verification run ended with a status of `failed`, `timeout`, or `partial` instead of `completed`. This indicates the run itself had a problem, not just individual objects.

**Run failure statuses:**

| Status | Meaning |
|--------|---------|
| `failed` | An unhandled exception terminated the run. |
| `timeout` | The run exceeded `max_runtime_minutes`. |
| `partial` | The run exceeded `max_memory_mb` and stopped early. |

**Typical causes:**

- **failed:** PHP fatal error, database connection lost, `PreservationService.php` not found (missing ahgPreservationPlugin dependency), or lock acquisition failure.
- **timeout:** Too many objects to verify within the configured time limit, especially for `batch_size = 0` (unlimited) schedules.
- **partial:** Insufficient memory allocation. Each verified object retains some memory for ledger inserts and result tracking.

**Response steps:**

1. Check PHP error logs:
   ```bash
   tail -50 /var/log/php8.3-fpm.log
   tail -50 /var/log/nginx/error.log
   ```
2. Check available disk space (the lock directory is in `cache/integrity_locks/`):
   ```bash
   df -h /usr/share/nginx/archive
   df -h /tmp
   ```
3. Check PHP memory limits:
   ```bash
   php -i | grep memory_limit
   ```
4. For `timeout` runs, either increase `max_runtime_minutes` on the schedule or reduce `batch_size` to process fewer objects per run.
5. For `partial` runs, increase `max_memory_mb` on the schedule, but ensure it does not exceed the system's PHP `memory_limit`.
6. For `failed` runs, examine the `error_message` field on the `integrity_run` record:
   ```bash
   php symfony integrity:report --summary --format=json | python3 -m json.tool
   ```
7. Check for stale lock files if the error mentions lock acquisition:
   ```bash
   ls -la /usr/share/nginx/archive/cache/integrity_locks/
   # Remove stale locks if the process is dead
   ```

---

## 2. Common Failure Causes

Each verification outcome recorded in the `integrity_ledger` table indicates a specific condition. The `outcome` column uses the following values: `pass`, `mismatch`, `missing`, `unreadable`, `permission_error`, `path_drift`, `no_baseline`, and `error`. Non-pass outcomes are escalated to the dead letter queue after repeated occurrence.

### 2.1 mismatch

**What it means:** The computed hash of the file on disk does not match the stored baseline hash in the `preservation_checksum` table. The file exists, is readable, and a baseline hash is available, but the content has changed.

**Intentional vs. unintentional modification:**

- **Intentional:** An administrator or process deliberately modified the file (e.g., format migration, watermark application, re-digitisation). In this case, the baseline checksum should be updated.
- **Unintentional:** The file has been corrupted by storage failure, bit rot, incomplete write, or malicious tampering. This requires investigation and likely restoration from backup.

**How to distinguish:**

1. Check the audit trail (ahgAuditTrailPlugin) for recent activity on the information object.
2. Check if a preservation event (format migration, normalisation) was recently run for the object.
3. Check file modification timestamps:
   ```bash
   stat <FILE_PATH>
   ```
4. Compare the file size to the expected size recorded in `digital_object.byte_count`.

**How to restore from backup:**

1. Identify the backup containing the correct version of the file:
   ```bash
   # If using ahgBackupPlugin
   ls -la /path/to/backup/uploads/r/
   ```
2. Restore the file to its original location.
3. Re-run verification to confirm the restored file matches the baseline:
   ```bash
   php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
   ```

**How to update the baseline (intentional modification):**

1. Generate a new checksum for the modified file via the Preservation plugin:
   ```bash
   php symfony preservation:fixity --object-id=<DIGITAL_OBJECT_ID> --algorithm=sha256 --update
   ```
2. Re-run verification to confirm the updated baseline matches:
   ```bash
   php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
   ```
3. Resolve the dead letter entry with a note explaining the baseline update:
   - In the web UI: Dead Letter Queue > select entry > Resolve > add note.

### 2.2 missing

**What it means:** The file referenced by the `digital_object` record does not exist at the resolved path on disk. The path is constructed from `digital_object.path` + `digital_object.name` relative to the AtoM root directory.

**Typical causes:**

- **NAS mount offline:** The most common cause. The uploads directory is symlinked to `/mnt/nas/heratio/archive`. If the NAS is unreachable, all files appear missing.
  ```bash
  # Check mount
  mount | grep /mnt/nas
  ls -la /usr/share/nginx/archive/uploads/r/
  ```
- **Symlink broken:** The `uploads/r` symlink may have been removed or points to the wrong location.
  ```bash
  ls -la /usr/share/nginx/archive/uploads/r
  # Should show: uploads/r -> /mnt/nas/heratio/archive
  ```
- **File moved or deleted:** The file was manually moved or deleted from the file system without updating the database record.
- **Ingest failure:** The ingest process recorded the database entry but failed to copy the file to the final location.

**Response steps:**

1. Verify the mount and symlink as shown above.
2. If the mount is offline, re-mount and re-verify.
3. If the file was deliberately deleted, either:
   - Delete the `digital_object` database record (requires DBA involvement).
   - Mark the dead letter entry as `ignored` with an explanatory note.
4. If the file was moved, update the `digital_object.path` column to reflect the new location.

### 2.3 unreadable

**What it means:** The file exists on disk but PHP cannot read it. The `is_readable()` check failed.

**Typical causes:**

- **File permissions:** The file does not have read permission for the `www-data` user (the PHP-FPM process user).
  ```bash
  ls -la <FILE_PATH>
  # Expected: at least -r--r--r-- with www-data as owner or group
  ```
- **PHP user (www-data) access:** The PHP-FPM worker runs as `www-data`. If files are owned by `root` or another user without group read access, verification will fail.
- **File system corruption:** The file's inode is damaged but the directory entry still exists.

**Response steps:**

1. Check and fix file permissions:
   ```bash
   # Check current permissions
   ls -la <FILE_PATH>

   # Fix ownership
   sudo chown www-data:www-data <FILE_PATH>

   # Or fix permissions
   sudo chmod 644 <FILE_PATH>
   ```
2. For bulk permission fixes on a directory:
   ```bash
   sudo find /mnt/nas/heratio/archive/<REPO_PATH>/ -type f -exec chmod 644 {} \;
   sudo find /mnt/nas/heratio/archive/<REPO_PATH>/ -type d -exec chmod 755 {} \;
   ```
3. Re-verify after fixing:
   ```bash
   php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
   ```

### 2.4 permission_error

**What it means:** A permission-related error occurred that is distinct from a simple unreadable file. This typically indicates directory-level access restrictions rather than file-level.

**Typical causes:**

- **Directory permissions:** The parent directory does not have execute (traverse) permission for `www-data`.
- **SELinux/AppArmor:** Security modules may block access even when POSIX permissions appear correct.
  ```bash
  # Check SELinux status (if applicable)
  getenforce

  # Check AppArmor status
  sudo aa-status
  ```
- **ACL restrictions:** Extended ACLs may deny access.
  ```bash
  getfacl <FILE_PATH>
  getfacl <DIRECTORY_PATH>
  ```

**Response steps:**

1. Check directory permissions along the full path:
   ```bash
   namei -l <FILE_PATH>
   ```
2. Ensure every directory in the path has at least `r-x` for `www-data` or its group.
3. If SELinux is enforcing, check for denials:
   ```bash
   sudo ausearch -m avc --start recent
   ```
4. If AppArmor is active, check the PHP-FPM profile for allowed paths.

### 2.5 path_drift

**What it means:** The `digital_object.path` column in the database has been changed (e.g., by a data migration or manual update) but the actual file was not moved to match. The resolved path does not correspond to where the file physically resides.

**Typical causes:**

- A data migration script updated database paths without moving files on disk.
- A repository restructuring changed the path convention.
- Manual database edits to the `digital_object` table.

**Response steps:**

1. Determine the correct file location. Check both the old path (from previous ledger entries) and the new path (from the current `digital_object` record).
2. Either move the file to the new path or correct the database path to match the file's actual location.
3. Re-verify to confirm resolution:
   ```bash
   php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
   ```

---

## 3. Dead Letter Triage Procedure

The dead letter queue (`integrity_dead_letter` table) collects objects that have failed verification repeatedly. Each entry tracks the `digital_object_id`, `failure_type`, `consecutive_failures` count, and transitions through the following statuses: `open` -> `acknowledged` -> `investigating` -> `resolved` (or `ignored`). A resolved entry that fails again is reopened to `open`.

### Step-by-Step Triage Workflow

#### Step 1: Assess the Queue

Get an overview of the current dead letter queue state:

```bash
php symfony integrity:report --dead-letter
```

Or for machine-readable output:

```bash
php symfony integrity:report --dead-letter --format=json
```

In the web UI, navigate to Admin > Integrity > Dead Letter Queue. The status summary at the top shows counts by status.

#### Step 2: Filter by Failure Type

Identify the dominant failure type. In the web UI, use the status filter dropdown. Via the database:

```sql
SELECT failure_type, status, COUNT(*) as cnt
FROM integrity_dead_letter
WHERE status IN ('open', 'acknowledged', 'investigating')
GROUP BY failure_type, status
ORDER BY cnt DESC;
```

Prioritise by failure type:
- **mismatch:** Highest priority -- potential data corruption.
- **missing:** High priority -- likely infrastructure issue.
- **unreadable / permission_error:** Medium priority -- usually fixable with permission changes.
- **error:** Variable -- requires individual investigation.

#### Step 3: Bulk Acknowledge

For entries that share a common root cause (e.g., all `missing` entries from the same repository during a known NAS outage), bulk acknowledge them via the web UI:

1. Filter the dead letter queue to the relevant failure type and repository.
2. Use the action button on each entry to set status to `acknowledged`.
3. Add a note describing the known root cause (e.g., "NAS outage 2026-02-28 15:00-17:00").

#### Step 4: Investigate

For each distinct root cause:

1. Set the status of related entries to `investigating`.
2. Follow the appropriate remediation playbook from Section 5.
3. Document findings in the `resolution_notes` field.

#### Step 5: Resolve

After the root cause is fixed:

1. Re-run verification for the affected objects:
   ```bash
   # For a specific object
   php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>

   # For a repository
   php symfony integrity:verify --repository-id=<REPO_ID> --limit=500
   ```
2. If verification passes, the dead letter entry is automatically resolved by `IntegrityService::clearDeadLetter()`.
3. For entries that cannot be fixed (e.g., intentionally deleted files), set status to `ignored` with a note explaining why.

#### Step 6: Review Resolved and Ignored

Periodically review resolved and ignored entries to ensure:
- Resolved entries are not reopening (which would indicate the fix was not permanent).
- Ignored entries are still justified.

---

## 4. Escalation Matrix

### Level 1: System Administrator (AtoM Admin)

**Access:** Admin > Integrity in the web UI, CLI commands.

**Responsibilities:**

- Monitor the integrity dashboard for alerts and trends.
- Acknowledge and triage new dead letter entries.
- Investigate simple issues: single-file mismatches, known mount outages.
- Adjust schedule parameters (batch size, frequency, throttle).
- Export reports for auditors.
- Place and release legal holds.
- Review disposition queue entries.

**Escalate to Level 2 when:**

- Multiple objects in the same directory path are failing.
- Mount or symlink issues persist after basic troubleshooting.
- File permission issues affect entire directory trees.
- Disk space is critically low.

### Level 2: System Administrator (Server/Infrastructure)

**Access:** SSH to server 112, root or sudo access, NAS administration.

**Responsibilities:**

- Diagnose and resolve file system issues (mounts, symlinks, permissions).
- Investigate storage hardware problems (disk health, RAID status, NAS connectivity).
- Fix ownership and permission issues across directory trees.
- Manage cron jobs for integrity scheduling.
- Monitor and manage PHP-FPM resource limits (memory, processes).
- Review system logs (`dmesg`, `syslog`, `journalctl`) for I/O errors.
- Manage TrueNAS mount availability and health.

**Escalate to Level 3 when:**

- Mismatches suggest database-level corruption (e.g., `preservation_checksum` values are wrong).
- The `digital_object.path` column is systematically incorrect.
- Dead letter entries cannot be resolved through file system fixes alone.
- The integrity plugin itself is throwing PHP exceptions.
- Schema migration issues are preventing the plugin from operating.

### Level 3: DBA / Developer

**Access:** MySQL root access, codebase access to `atom-ahg-plugins/ahgIntegrityPlugin`.

**Responsibilities:**

- Investigate and correct database inconsistencies in `digital_object`, `preservation_checksum`, or `information_object` tables.
- Fix `digital_object.path` discrepancies through targeted UPDATE queries.
- Regenerate baseline checksums via the Preservation plugin.
- Debug and fix plugin code issues (PHP exceptions, schema migration failures).
- Investigate chain-of-custody (`previous_hash`) integrity in the append-only ledger.
- Perform data-level diagnosis using direct database queries.
- Manage retention policies and disposition queue at the database level when the UI is insufficient.

**Escalation contacts:**

| Level | Role | Contact |
|-------|------|---------|
| 1 | AtoM Admin | As configured in schedule `notify_email` |
| 2 | System Admin | Server 112 admin team |
| 3 | DBA/Developer | johan@theahg.co.za |

---

## 5. Remediation Playbooks

### 5.1 Remediating: mismatch

**Diagnosis:**

```bash
# Get details of the mismatched object
php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>

# Check the file modification time
stat <FILE_PATH>

# Check the baseline checksum
mysql -u root archive -e "
  SELECT digital_object_id, algorithm, checksum_value, created_at
  FROM preservation_checksum
  WHERE digital_object_id = <DIGITAL_OBJECT_ID>;
"

# Check audit trail for recent changes
mysql -u root archive -e "
  SELECT action_type, object_type, object_id, created_at, user_name
  FROM audit_log
  WHERE object_id = <INFORMATION_OBJECT_ID>
  ORDER BY created_at DESC
  LIMIT 10;
"
```

**Fix (unintentional corruption -- restore from backup):**

```bash
# 1. Locate the backup copy
ls -la /path/to/backup/<FILE_PATH>

# 2. Restore the file
cp /path/to/backup/<FILE_PATH> <FILE_PATH>
chown www-data:www-data <FILE_PATH>
chmod 644 <FILE_PATH>

# 3. Re-verify
php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
```

**Fix (intentional modification -- update baseline):**

```bash
# 1. Regenerate checksum via preservation plugin
php symfony preservation:fixity --object-id=<DIGITAL_OBJECT_ID> --algorithm=sha256 --update

# 2. Re-verify to confirm new baseline
php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
```

### 5.2 Remediating: missing

**Diagnosis:**

```bash
# Check what path the system expects
mysql -u root archive -e "
  SELECT id, object_id, path, name, byte_count
  FROM digital_object
  WHERE id = <DIGITAL_OBJECT_ID>;
"

# Check if the path exists
ls -la <RESOLVED_PATH>

# Check the mount
mount | grep /mnt/nas
df -h /mnt/nas/heratio/archive

# Check the symlink
ls -la /usr/share/nginx/archive/uploads/r
```

**Fix (mount offline):**

```bash
sudo mount -a
# Verify
df -h /mnt/nas/heratio/archive
# Re-verify affected objects
php symfony integrity:verify --repository-id=<REPO_ID> --limit=1000
```

**Fix (symlink broken):**

```bash
# Recreate the symlink
cd /usr/share/nginx/archive/uploads
ln -sf /mnt/nas/heratio/archive r
# Verify
ls -la /usr/share/nginx/archive/uploads/r/
```

**Fix (file genuinely deleted):**

If the file was deliberately removed and will not be restored, clean up by marking the dead letter as `ignored` and optionally removing the orphan `digital_object` record (Level 3 DBA task).

### 5.3 Remediating: unreadable

**Diagnosis:**

```bash
# Check file permissions
ls -la <FILE_PATH>

# Check who PHP runs as
ps aux | grep php-fpm | head -5

# Check if www-data can read the file
sudo -u www-data cat <FILE_PATH> > /dev/null
echo $?  # 0 = success, 1 = permission denied
```

**Fix:**

```bash
# Fix individual file
sudo chmod 644 <FILE_PATH>
sudo chown www-data:www-data <FILE_PATH>

# Fix entire directory (if widespread)
sudo find /mnt/nas/heratio/archive/<REPO_PATH>/ -type f ! -perm -444 -exec chmod 644 {} \;

# Re-verify
php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
```

### 5.4 Remediating: permission_error

**Diagnosis:**

```bash
# Check full path permissions
namei -l <FILE_PATH>

# Check for ACLs
getfacl <FILE_PATH>
getfacl $(dirname <FILE_PATH>)

# Check AppArmor
sudo aa-status 2>/dev/null
```

**Fix:**

```bash
# Fix directory traversal permissions
sudo chmod 755 <EACH_DIRECTORY_IN_PATH>

# If ACLs are blocking access
sudo setfacl -m u:www-data:rx <DIRECTORY_PATH>

# Re-verify
php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
```

### 5.5 Remediating: path_drift

**Diagnosis:**

```bash
# Get the database path
mysql -u root archive -e "
  SELECT id, object_id, path, name
  FROM digital_object
  WHERE id = <DIGITAL_OBJECT_ID>;
"

# Check previous successful verifications for the old path
mysql -u root archive -e "
  SELECT file_path, outcome, verified_at
  FROM integrity_ledger
  WHERE digital_object_id = <DIGITAL_OBJECT_ID>
  ORDER BY verified_at DESC
  LIMIT 5;
"

# Search for the file at the old and new paths
find /mnt/nas/heratio/archive -name '<FILENAME>' 2>/dev/null
```

**Fix (move file to match database):**

```bash
# Move the file to the location the database expects
sudo mv <OLD_LOCATION> <NEW_LOCATION>
sudo chown www-data:www-data <NEW_LOCATION>

# Re-verify
php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
```

**Fix (update database to match file -- Level 3 DBA):**

```sql
-- Caution: modifying the digital_object table directly
UPDATE digital_object
SET path = '/uploads/r/<CORRECT_PATH>/'
WHERE id = <DIGITAL_OBJECT_ID>;
```

Then re-verify:

```bash
php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
```

### 5.6 Remediating: no_baseline

**What it means:** The object has no checksum in the `preservation_checksum` table and the system was unable to generate one automatically. This is not a failure per se but the object cannot be verified until a baseline is established.

**Fix:**

```bash
# Generate checksums via the preservation plugin
php symfony preservation:fixity --object-id=<DIGITAL_OBJECT_ID> --algorithm=sha256

# Verify the baseline was created
mysql -u root archive -e "
  SELECT * FROM preservation_checksum
  WHERE digital_object_id = <DIGITAL_OBJECT_ID>;
"

# Now verify the object
php symfony integrity:verify --object-id=<DIGITAL_OBJECT_ID>
```

For bulk baseline generation:

```bash
# Generate checksums for all objects missing baselines
php symfony preservation:fixity --missing --algorithm=sha256
```

### 5.7 Remediating: error

**What it means:** An unexpected error occurred during verification -- typically a PHP exception in `hash_file()`, a database connectivity issue, or a missing dependency.

**Diagnosis:**

```bash
# Check the error detail in the ledger
mysql -u root archive -e "
  SELECT digital_object_id, error_detail, verified_at
  FROM integrity_ledger
  WHERE outcome = 'error'
  ORDER BY verified_at DESC
  LIMIT 10;
"

# Check PHP error logs
tail -50 /var/log/php8.3-fpm.log

# Check if PreservationService is available
ls -la /usr/share/nginx/archive/atom-ahg-plugins/ahgPreservationPlugin/lib/PreservationService.php
```

**Fix:**

The fix depends on the specific error. Common errors and their solutions:

| Error Detail | Solution |
|-------------|----------|
| `hash_file() failed` | Check file system health, verify file is not locked by another process. |
| `PreservationService.php not found` | Ensure ahgPreservationPlugin is installed and enabled. |
| `Database connection lost` | Restart MySQL and PHP-FPM. |
| `Memory exhausted` | Increase `memory_limit` in PHP config or reduce schedule `batch_size`. |

---

## 6. Monitoring Best Practices

### 6.1 Recommended Alert Thresholds

Configure these alerts in Admin > Integrity > Alert Configuration:

| Alert Type | Comparison | Threshold | Rationale |
|------------|------------|-----------|-----------|
| `pass_rate_below` | lt | 95 | A pass rate below 95% indicates a systemic issue requiring immediate attention. For mature collections, consider raising to 99%. |
| `failure_count_above` | gt | 50 | More than 50 failures in a single run warrants investigation regardless of collection size. Adjust based on your total object count. |
| `dead_letter_count_above` | gt | 20 | A growing dead letter queue should not be ignored. Triage regularly to keep this below 20. |
| `backlog_above` | gt | 1000 | If more than 1000 objects have never been verified, schedules need to be more aggressive. Adjust based on ingest rate. |
| `run_failure` | (triggers on any non-completed status) | N/A | Always enable this alert. Any run failure needs immediate investigation. |

### 6.2 Recommended Cron Schedule

Add the following cron entries for the `www-data` user (or the user running PHP-FPM):

```cron
# Run due integrity verification schedules every 15 minutes
*/15 * * * * cd /usr/share/nginx/archive && php symfony integrity:schedule --run-due >> /var/log/integrity-scheduler.log 2>&1

# Daily retention scan for eligible disposition candidates (6:00 AM)
0 6 * * * cd /usr/share/nginx/archive && php symfony integrity:retention --scan-eligible >> /var/log/integrity-retention.log 2>&1
```

**Schedule configuration recommendations:**

| Schedule | Frequency | Batch Size | Use Case |
|----------|-----------|------------|----------|
| Daily Sample Check | Daily | 200 | Early warning. Catches problems within 24 hours. |
| Weekly Full Scan | Weekly | 0 (unlimited) | Comprehensive coverage. Set `max_runtime_minutes` to 480 (8 hours) for large collections. |
| Repository-Scoped | Weekly | 500 | If you have high-value repositories that need more frequent checking. |

### 6.3 Retention Policy Review Cadence

Retention policies and the disposition queue should be reviewed on a regular schedule:

| Activity | Frequency | Responsible |
|----------|-----------|-------------|
| Review disposition queue | Monthly | Level 1 (AtoM Admin) |
| Audit retention policies | Quarterly | Level 1 + Level 3 (Admin + DBA) |
| Review legal holds | Quarterly | Level 1 (AtoM Admin) |
| Review and clean dead letter queue | Weekly | Level 1 (AtoM Admin) |
| Export auditor pack | Quarterly or as required | Level 1 (AtoM Admin) |
| Verify cron job operation | Monthly | Level 2 (System Admin) |

**Retention policy review checklist:**

1. Are all active policies still valid and required?
2. Are retention periods appropriate for current legal and regulatory requirements?
3. Are scope filters (repository, hierarchy, format) correctly configured?
4. Are there eligible items in the disposition queue awaiting review?
5. Are any legal holds expired and ready for release?

### 6.4 Log Management

Monitor the following log files for integrity-related issues:

| Log File | Content |
|----------|---------|
| `/var/log/integrity-scheduler.log` | Cron-driven schedule execution output |
| `/var/log/integrity-retention.log` | Retention scan output |
| `/var/log/php8.3-fpm.log` | PHP errors during verification |
| `/var/log/nginx/error.log` | Nginx errors affecting web UI access |
| `/var/log/syslog` | System-level I/O errors, mount failures |

Rotate logs to prevent disk exhaustion:

```bash
# Add to /etc/logrotate.d/integrity
/var/log/integrity-*.log {
    weekly
    rotate 12
    compress
    missingok
    notifempty
}
```

### 6.5 Key Performance Indicators

Track these KPIs over time to assess the health of your integrity assurance programme:

| KPI | Target | How to Measure |
|-----|--------|----------------|
| Overall pass rate | > 99% | Dashboard or `php symfony integrity:report --summary` |
| Backlog (never-verified objects) | < 1% of total | Dashboard backlog count / total master objects |
| Dead letter queue size (open) | < 20 | Dashboard or `php symfony integrity:report --dead-letter` |
| Verification throughput | > 500 objects/hour | Dashboard throughput metric |
| Mean time to triage dead letters | < 48 hours | Track time from `first_failure_at` to `acknowledged_at` |
| Mean time to resolve dead letters | < 7 days | Track time from `first_failure_at` to `resolved_at` |
| Schedule uptime | 100% of scheduled runs execute | Compare `total_runs` against expected run count for the period |

### 6.6 Periodic Health Check Script

Run this script periodically (e.g., weekly) to get a quick health assessment:

```bash
#!/bin/bash
# Integrity Health Check
cd /usr/share/nginx/archive

echo "=== Integrity Health Check ==="
echo "Date: $(date)"
echo ""

echo "--- Verification Status ---"
php symfony integrity:verify --status

echo ""
echo "--- Schedule Status ---"
php symfony integrity:schedule --status

echo ""
echo "--- Dead Letter Summary ---"
php symfony integrity:report --dead-letter

echo ""
echo "--- Retention Status ---"
php symfony integrity:retention --status

echo ""
echo "--- Mount Status ---"
mount | grep /mnt/nas
df -h /mnt/nas/heratio/archive 2>/dev/null || echo "WARNING: NAS mount not available"

echo ""
echo "--- Disk Space ---"
df -h /usr/share/nginx/archive

echo ""
echo "=== End Health Check ==="
```

---

*AtoM Heratio - Integrity Assurance Plugin v1.2.0*
*The Archive and Heritage Group (Pty) Ltd*
