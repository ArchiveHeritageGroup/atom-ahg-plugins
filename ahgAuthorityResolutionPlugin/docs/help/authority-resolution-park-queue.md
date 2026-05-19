# Authority Resolution - Park Queue

The park queue is the engine's "I cannot decide yet" outlet. Mentions that you park stay alive, get re-scanned for new candidates by a background job, and re-surface when the upstream authority store changes. This article covers the dedicated park-queue screen, the parking decision itself, and the bulk re-review tooling.

## When to park

Park a mention when any of the following is true:

- The mention is real but the right authority record does not exist yet (an authority import is pending).
- The mention is real but the document context is ambiguous and needs off-line research.
- The mention is real but the candidate scoring is unsafe (close ties, or the wrong candidate is highest).
- The mention may be a false positive but you are not confident enough to reject it. Rejection writes NER training data, so we want rejection decisions to be intentional.

Do not park to "save for later" just because the queue is long. Park only when there is a defined reason. The reason is required and is later used by the bulk re-review job and audit reports.

## Park screen

- AtoM Heratio: `/admin/authorityResolution/park`
- Laravel Heratio: `/admin/authority-resolution/park`

The screen lists every active park row (`ahg_mention_park` UNIQUE on `mention_id`). Filters available as GET query string:

| Parameter | Type | Notes |
|---|---|---|
| `parked_by` | integer | Filter by archivist user id (dropdown populated from existing rows) |
| `entity_type` | string | One of PERSON / ORG / GPE / LOC / PLACE |
| `new_candidate_only` | checkbox | Only rows where `new_candidate_available = 1` |
| `since_parked` | YYYY-MM-DD | Lower bound on `parked_at` |
| `limit` | integer | Page size; clamped to [10, 200] |

Rows are sorted by `new_candidate_available DESC, parked_at DESC` so newly flagged rows surface first.

## Per-row actions

- **Unpark and review** - deletes the park row, sets `ahg_mention.state = 'pending'`, regenerates candidates, re-scores them, and redirects you to the review screen with a fresh slate.
- **Show context** - expands the surrounding text and the original NER entity row inline.
- **Discard** - permanent reject. Writes `decision_type=reject` and removes the park row in the same transaction.

## Parking from the review screen

The act of parking happens on the review screen, not on the park queue. Click **Park** on the review screen; the reason modal appears; submit. Behind the scenes:

- A row is added to `ahg_mention_park` with `mention_id`, `parked_by_user_id`, `parked_at`, `reason`, `new_candidate_available=0`, `new_candidate_check_at=NULL`.
- The mention's state flips to `parked`.
- A decision row is written to `ahg_mention_decision` with `decision_type=park` and the park reason in `evidence_snapshot.park_reason`.
- RDF-Star decision provenance is written to Fuseki.

Re-parking an already-parked mention is a no-op: the UNIQUE constraint on `mention_id` prevents duplicates; the existing row's `reason` is updated.

## Background scan: new candidate detection

The scan job (`auth-res:scan-parked`) walks every active park row and computes a fingerprint of the current candidate set against the live authority store. The fingerprint is the sorted CSV of `source|authority_id|fuseki_uri|display_name` tuples. If the fingerprint differs from what is persisted in `ahg_mention_candidate`, the job flips `new_candidate_available=1` and stamps `new_candidate_check_at = NOW()`.

The flag is sticky: once raised, it stays until the row is deleted (i.e. un-parked). A transient lookup-source outage will not lose a real signal.

The scan is cheap (one candidate-generator pass per parked mention) and idempotent. Suggested schedule: daily 02:00.

```cron
0 2 * * * cd /usr/share/nginx/archive && sudo -u www-data php symfony auth-res:scan-parked >/dev/null 2>&1
```

On Laravel Heratio the equivalent cron entry is:

```cron
0 2 * * * cd /usr/share/nginx/heratio && sudo -u www-data php artisan auth-res:scan-parked >/dev/null 2>&1
```

Schedule the periodic task on ONE codebase only. If you run both side-by-side against the same database, the second copy is just duplicate work.

## Bulk re-review

When a new external adapter goes live, a large MARC / EAD import lands, or you want to drain the park queue before a major release, use `auth-res:reprocess-parked`:

```bash
sudo -u www-data php symfony auth-res:reprocess-parked --since=2026-05-01
sudo -u www-data php symfony auth-res:reprocess-parked --since=2026-05-01 --dry-run
sudo -u www-data php symfony auth-res:reprocess-parked --since=2026-05-01 --user-id=42
```

For each park row whose `parked_at >= --since 00:00:00`, the task:

1. Deletes the park row.
2. Flips the mention back to `pending`.
3. Regenerates candidates (Task-3 service).
4. Re-scores the candidates (Task-4 service).

The `--user-id` flag attributes the bulk un-park to a specific archivist; the default is `0` ("CLI bulk", distinguishable in audit reports).

The `--dry-run` flag previews which rows would be touched without acting.

## Data model

`ahg_mention_park`:

| Column | Notes |
|---|---|
| `mention_id` | UNIQUE - one active park row per mention |
| `parked_by_user_id` | For "my parked" filter and per-archivist counts |
| `parked_at` | DEFAULT CURRENT_TIMESTAMP; sort key and `--since` filter |
| `reason` | Required; free text; LIKE-searchable |
| `new_candidate_available` | 0/1 flag set by `auth-res:scan-parked` |
| `new_candidate_check_at` | Timestamp of last scan |

## Dashboard widget

A JSON endpoint at `/admin/authorityResolution/park/dashboard.json` returns `[archivist_user_id => count]`. The dashboard widget renders this as a sortable list and "Mine" is wired to the logged-in user.

## Edge cases

- **Unpark with no candidates.** `unparkAndRereview` always returns; the candidate list may be empty. The review screen handles this with a "create new" prompt.
- **Park then reject elsewhere.** Rejecting from the review screen deletes the park row in the same transaction; you never end up with both an active park row and a terminal rejection.
- **Concurrent scan and unpark.** The scan job is idempotent; if a row is deleted between the scan's read and write, the write is silently a no-op.

## See also

- `authority-resolution-user-guide` - the high-level overview
- `authority-resolution-review-screen` - where parking is initiated
- `authority-resolution-cli-tasks` - all eleven Symfony 1.4 tasks
