# Audit trail, admin error page, help search, and FTP batch upload

**Date:** 2026-08-14
**Releases:** atom-ahg-plugins v3.99.24, v3.99.25; atom-framework v2.16.6
**Instance:** developed and deployed on `/usr/share/nginx/archive` (PSIS); queue logic proven in isolation

## FTP upload: 532 files timed out because most had never started

`/ftp-upload` with 532 files timed out and retried endlessly. The cause was one line:

    for (var i = 0; i < files.length; i++) { chunkedUpload(files[i]); }

532 XMLHttpRequests opened at once. A browser runs ~6 per host, so the other 526 sat
in the browser's own queue with their 2-minute `xhr.timeout` already counting down.
They timed out **before being sent**, each timeout entered retry-with-backoff, and
that restarted the pile-up. Nothing was slow; the work had not begun.

Now a queue with `MAX_CONCURRENT = 3`, and the slot is released when a file
finishes, fails or is cancelled.

### Pause only set a flag the NEXT chunk read

On a single-chunk file - most of a 532-file batch - the transfer ran to completion
and the pause applied to a chunk that never came, so Pause appeared to do nothing.
There was also no global control, only one button per file (532 of them).

Pause now **aborts the request in flight**, and a "Pause all" halts the queue and
every active transfer. Aborting is safe: `currentChunk` only advances after the
server confirms a chunk, so resume re-sends the same one. An abort sets a flag so it
is not mistaken for a network failure and does not burn a retry.

### Skip-existing: size must match, not just the name

New `POST /ftp-upload/exists` answers for the whole batch in one request (asking per
file would be 532 round trips before the first byte - the delay this removes).

**A file counts as present only when the size matches too.** Name alone would skip a
half-written file from the interrupted run and call the collection complete - silent,
and only discovered when someone opens the record. Any failure of the check skips
NOTHING: uploading a file that turns out to be there is recoverable; omitting one is
not.

### Bug introduced and caught during the work

The first draft passed a subdirectory to `ftpListFiles()` / `sftpListFiles()`, which
take no parameter. **PHP silently ignores extra arguments to userland functions**, so
folder uploads would have compared against the root listing and never matched - no
error, just skip-existing quietly not working for subfolders. Both now take the
directory.

### Verification

- 532-file simulation with a mid-run global pause and 61 fail-then-manual-retry
  cycles: concurrency never exceeded 3, the slot counter never went negative, queue
  drained completely, every file accounted for.
- `existing()` against a real directory: present, **truncated**, missing, subfolder
  and missing-subfolder all resolved correctly.
- Not tested end to end in a browser - `/ftp-upload` needs an admin login. Worth
  watching the first real run.

## Audit trail

### username filter killed the php-fpm worker

`ahg_audit_log` has 3.19M rows and **no index on `username`**, and the filter used
`LIKE '%name%'` - a leading wildcard cannot use an index anyway. One page of 50 took
**16 seconds** warm and exceeded the 90s worker timeout cold, killing the process.
nginx logged `recv() failed (104: Connection reset by peer)`; **nothing reached
`ahg_error_log`, because the worker died before it could log**. That absence is
itself diagnostic - a fatal that leaves no application log is usually a dead worker.

Fixed: exact match (the control is a `<select>` of whole usernames, so `louise` was
also matching `louise2` - other people's activity folded into one person's trail),
plus `INDEX (username, created_at)`. 16s -> **0.00s**, no filesort.

### Filter dropdowns described a table that does not exist

Hardcoded lists offered create/update/delete/download/export/import/publish. The log
stores `edit` not `update`, `version_created` not `create`. Only `view` overlapped,
so every other choice returned 0 against 3.2M rows - reading as "no such activity"
rather than "this filter cannot match". The entity filter offered three types absent
from the data while omitting **HeritageAsset (930,437 rows)**, the second most common.

All four dropdowns now read from the table (163 actions, 186 entity types), as the
username one always did.

### security_classification was never once populated

`AuditService` only stored `$options['security_classification']`, and **no caller
anywhere passes it**. Now resolved from `ahg_io_security` - a PK lookup, 0.13ms,
cached per request. Note `ahg_io_security` has **0 rows**: nothing on PSIS is
classified, so the column stays empty until it is. That is data, not code.

### GOTCHA: audit timestamps are 9 hours out

`apps/qubit/config/settings.yml: default_timezone: America/Vancouver` (AtoM's stock
default) and `ChainedAuditWriter` stamps rows with PHP `date()`. MySQL says
`08:28 SAST`; the audit row says `2026-08-13 23:28`. **Every one of 3.19M audit
timestamps is 9 hours behind local time**, and the from/to date filters inherit it.
Not fixed: `apps/` is locked, and changing it gives new rows correct times while the
existing series stays in Vancouver time - a discontinuity to decide deliberately.

## Administrators now see what broke

The stock page says "Oops! An Error Occurred" and nothing else, by design - it is
also what the public sees. Administrators now get class, message, file:line, URL,
`Caused by` and trace.

Hooked on `application.throw_exception`, which `sfException::printStackTrace()`
dispatches via `notifyUntil` immediately before rendering
`vendor/symfony/lib/exception/data/error.html.php`; **a listener returning true sets
`isProcessed()` and symfony returns without rendering**. A documented extension
point, so the locked vendor template is untouched.

**Ordering matters and was tested, not assumed:** returning true STOPS the listener
chain, so registering before the logger would have silently stopped recording errors.
Registered after `logThrownException`; verified the log went 2 -> 4 rows on the admin
path with `user_id` recorded. Anonymous still gets the 1,117-byte generic page.

## Help search could not find any identifier

`HelpMarkdownParser::htmlToText()` stripped inline `<code>` spans from `body_text`
with the comment "they add noise to search". But `body_text` is exactly what
`MATCH(title, body_text)` reads, and inline code is where table names, classes,
routes and settings live. Measured on live data:

    "ahg_registration_request" -> 0 hits
    "RegistrationService"      -> 0 hits
    "registration"             -> 44 hits

Zero across all 360 articles; 320 have code spans, 2 retained any. Fenced `<pre>`
listings are still stripped (genuinely noise). After re-import: 361 articles,
`informationobject` 34 hits, `ahg_settings` 29.

## migrate:up had never run a migration

`bin/atom` calls `$migrate->run(['up'])` in three places; `MigrateCommand::run()`
handled only `'run'` and `'status'`, so `'up'` fell through to the usage text and
exited 1. **The advertised command for running migrations was a no-op.** Fixed in
framework v2.16.6; `'run'` kept as an alias. Ledger then recorded all four pending
migrations on PSIS (66 -> 70 rows, 0 pending).

**Correction worth carrying:** `migrate:up` DOES `git pull` both repos first -
`migrate()` calls `pullUpdates()`. I had said it did not, having checked `run()` and
not `migrate()`. On a box behind origin that deploys code as a side effect of
recording a ledger.

---

# Addendum: deploying one plugin to a far-behind instance carries its security changes

WDB (41.162.30.249, client production) runs plugins **v3.59.3**; PSIS is at v3.99.25.
Only `ahgFtpPlugin` was synced, deliberately - the rest of the sync is scheduled for
later.

Syncing the whole plugin directory rather than the four changed files was the right
call (WDB's older index action does not set every variable the new template reads),
but it also carried **every other change made to that plugin in 9 commits**, including
a security fix from v3.79.73 that gated the entire ftpUpload module to administrators
because `clearAll` mass-deletes the upload folder.

The hole it closed was real. The side effect was that the cataloguer doing the
532-file upload - an editor/contributor, not an administrator - was locked out of the
page entirely. Reported as "user does not have permission to access".

**LESSON: a single-plugin sync to an instance N versions behind is not a single
change.** Before syncing one plugin to a lagging instance, check what else moved in
it: `git log v<their-version>..HEAD -- <plugin>` and read the security-flavoured
commits. The failure mode is not a crash - it is a permission or behaviour change
nobody was expecting.

## Fix: gate on consequence, not on module

    deleteFile, clearAll                        administrator
    index, upload, uploadChunk, exists,
    listFiles, importAsUpload                   editor / contributor / administrator

The original hole stays shut - a plain authenticated account with no edit rights still
gets nothing - but uploading no longer requires the same privilege as wiping the store.
Implemented in `boot()` via `getActionName()`, the pattern already proven in
`ahgUserRegistrationPlugin` and `ahgPrivacyPlugin`. Comparison is lowercased on both
sides so `deleteFile` / `clearAll` match whatever case convention returns.

## GOTCHA repeated: a 403 becoming a 200 is not a regression

After the change, anonymous `/ftp-upload/` returned **200 instead of 403**, which
looks like the guard failing open. It is not: `requireAdmin()` forwards to
`admin/secure` (403) while `requireAuth()` redirects to the login page, and **AtoM
serves its login page with HTTP 200**.

Verified by reading the body, not the status: `body class="... user login"`, and no
`drop-zone`, `Upload Files`, `pause-all-btn` or `path-prefix` anywhere in the 35KB
response. Test access control on page content, never on status code.

Confirmed working on WDB by the user.
