# PSIS error alerting to CallHub (11 September 2026)

atom-ahg-plugins v3.106.93 through v3.106.96. Errors in `ahg_error_log` on PSIS now
raise CallHub tickets, one per fault per ISO week, with priority declared by PSIS and
escalation to critical for a short list of data-integrity code paths.

Supersedes the earlier version of this file, which covered only v3.106.93.

## The path an error takes

`php symfony callhub:alerts` (hourly, `/etc/cron.d/psis-callhub-alerts`, as www-data)
selects `ahg_error_log` rows where `resolved_at IS NULL`, `signature IS NOT NULL` and
`level IN ('error')`, then writes a file to `/var/spool/workbench/notifications`.

From there the workbench carries it: `notificationInboxWatcher` -> `NotificationService`
(notifications.ts:61) -> `enqueueTicket` -> `callhub_ticket_outbox` -> `POST /api/v1/alerts`.
`eventType: 'alert'` is in the workbench's `TICKETABLE_EVENTS`, which is what makes an
error a ticket rather than only a bell entry.

A CLI drain rather than an inline call on the error path: rendering a page is never
coupled to CallHub being reachable. Off unless `callhub_alerts_enabled` is on.

Errors only. Warnings and notices stay in the log and are never ticketed.

## Duplicate prevention, and how it was proven

`ahg_error_alert` has `UNIQUE KEY (signature, period)`. The drain claims a fault there
BEFORE writing the spool file, so a concurrent run or a re-run after a crash loses the
insert and sends nothing. A failed spool write releases the claim so the next tick
retries rather than losing the fault.

Proven against the live table through the real service code, 14 checks passing, table
left at zero rows. The check that matters distinguishes the two candidate keys: same
`(signature, period)` but a deliberately different `external_ref` was still rejected,
and MySQL named `uniq_signature_period`. So the composite key does the work; it is not
a coincidence of how the reference is formatted. A duplicate claim from a different
`error_log_id` is also refused, because the fault is what gets ticketed, not the row.

Both cases that must NOT be blocked pass: the same fault next week raises a fresh
ticket (deliberate - a timeless reference would swallow a recurrence months later), and
a different fault in the same week is unaffected.

Three layers end up guarding this, in order of what actually fires: the unique key; the
workbench outbox being `ON CONFLICT (entity_id) DO NOTHING`; and CallHub correlating
server-side on the same value. `entityId` is a deterministic uuid derived from
signature plus ISO week, so all three land on one identifier.

## Priority (Johan, 11 September 2026: not everything is high)

A spool file has no priority field - the watcher drops unknown keys - so the literal
`Priority: x` line in the body is the entire channel, matched by the workbench with an
anchored per-line regex. PSIS emits it as the first line.

Default medium. An unrecognised value is coerced to medium rather than passed through,
so a typo in a setting cannot emit a priority nobody chose. PSIS is set to `high`,
being a live production archive.

The test asserts against the workbench's actual regex rather than the string, so a
change at their end fails this suite instead of silently sending tickets at their
default - the failure nobody would have noticed for weeks.

## The critical-path list, and why it is six and not twenty-nine

A fatal in one of six paths escalates to critical regardless of the setting. Escalation
only; a configured critical is never demoted.

The list keys on the code path, never on the words in a message. A message containing
"delete" proves nothing; a fatal raised inside a chunked delete loop can genuinely
leave a half-pruned table.

Johan asked for a count before the list was built, which changed the answer:

| filter | files |
| --- | --- |
| mentions purge / prune / retention / truncate / derivative regen | 29 |
| actually issues DELETE, TRUNCATE or unlink | 8 |
| destroys archival data rather than caches, settings or demo seed | 3 |

The three: `ExpireDataCommand` (retention expiry), `PhysicalObjectDeleteUnlinkedCommand`,
`versionPruneTask`. Plus three write paths that corrupt rather than delete if they die
mid-operation: `PreservationService`, `FixityCommand`, `IntegrityService`. Six.

A grep for "purge" would have listed all eight and been wrong about five - it would have
called demo seeding, a facet cache refresh, a settings command and slug generation
critical. The counting step is the reason the list is trustworthy.

Deliberately NOT extended to the second axis, records exposed to an unauthenticated
request. That needs a list over roughly 150 public browse and view actions, which would
rot on the next route added. Recorded as rejected with the reason so it does not return
as an apparent oversight.

`criticalPathsMissing()` asserts every listed file still exists, so a rename fails the
suite rather than silently demoting a path. Confirmed the guard discriminates: against
a bogus root it reports 6 of 6 missing; against the real root, zero.

## Two things I had wrong

**projectId is not a client.** I had PSIS sending `projectId: 'AHG Internal'`. That is a
CallHub CLIENT, which CallHub assigns itself; `projectId` is a workbench `projects.id`
uuid. PSIS matches no workbench project (it is not AtoM ANC, whose instance is no longer
served, and not the Constitution Hill archive), so it now sends no `projectId` at all -
a wrong uuid sends whoever clicks through into the wrong project tree. Implemented as a
uuid-shaped guard so it cannot regress to a string.

One of my own assertions had been asserting the mistaken value and passing. Replaced
rather than deleted, with a note of what it used to claim: a green suite asserting the
wrong thing is worse than a red one.

**central:sync-errors does not duplicate.** Recorded here because it nearly caused data
loss. I claimed it duplicates and was asked to fix it. It posts
`['errors' => ..., 'replace' => true]` - an idempotent full-state replace. Adding
sent-tracking so it only posted unsent rows would have handed AHG Central a near-empty
list with `replace => true`, dropping every error it held. Before adding "only send new
rows" logic to any sync task, read what it posts; a `replace` flag means the payload IS
the desired end state.

## Environment notes worth keeping

`php symfony <anything>` fails as a non-www-data user on PSIS: `log/` is
`drwxr-xr-x www-data:www-data` and `sfFileLogger` throws on `!is_writable($dir)`.
`central:sync-errors` fails identically. This is why the cron entry specifies www-data,
and it is a pre-existing condition, not caused by this work.

The cron entry is shipped as a versioned artefact at
`ahgCorePlugin/config/cron.d/psis-callhub-alerts`, following the precedent set by
`ahgSemanticSearchPlugin/config/cron.d/`. Nothing in this repo auto-installs `cron.d`
files, so a new instance still needs one `sudo install`. `bin/install` is a protected
file and was not touched.

The root is spelled out in the cron command rather than carried in `$ATOM_ROOT`. The
variable does survive into flock's inner shell, because cron exports it and the single
quotes defer expansion - but two levels of quoting holding up a path breaks silently on
the next edit.

## Workbench-side changes this prompted

The workbench session fixed a false positive I reported: `isTicketable` was refusing any
title matching `\b(warning|notice|deprecated|debug|info)\b`, which would have silently
dropped real fatals reading `Undefined array key "info"` or
`Call to undefined method Notice::load()`. Now requires an UPPERCASE label at the start
of the title or after a short `source:` prefix, and refusals are logged via
`refusalReason()`. A silent filter is unfalsifiable - a wrongly-dropped error looked
exactly like one that never happened.

## Checks

`ahgCorePlugin/tests/callhub_alert_test.php`, 68 assertions. All five suites in the CI
glob pass: callhub_alert 68, error_trap_level 21, pii_scoring 68, redaction_filter 12,
visual_redaction_coords 9 - 178 total.
