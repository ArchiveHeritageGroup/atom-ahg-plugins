# Authority Resolution - CLI Tasks

The AtoM Heratio side ships the engine as a set of Symfony 1.4 tasks under the `auth-res:` namespace. They live in `atom-ahg-plugins/ahgAuthorityResolutionPlugin/lib/task/` and are run with:

```bash
sudo -u www-data php symfony auth-res:<task> [args] [--option=value]
```

The Laravel Heratio side ships the same set as artisan commands. AtoM speaks of "tasks", Laravel of "commands"; the underlying behaviour is identical.

## Task inventory

| Task | Class file | Purpose |
|---|---|---|
| `auth-res:promote-sample` | `authResPromoteSampleTask.class.php` | Promote PERSON/ORG/GPE entities for an information object into the mention workflow |
| `auth-res:generate-candidates` | `authResGenerateCandidatesTask.class.php` | Generate ranked authority candidates for an `ahg_mention` |
| `auth-res:score-evidence` | `authResScoreEvidenceTask.class.php` | Score evidence signals + composite for each candidate of a mention. Re-ranks by composite. |
| `auth-res:scan-parked` | `authResScanParkedTask.class.php` | Flag parked mentions whose candidate set has changed since parking |
| `auth-res:write-provenance` | `authResWriteProvenanceTask.class.php` | Write RDF-Star provenance for a decision to Fuseki |
| `auth-res:export-ner-feedback` | `authResExportNerFeedbackTask.class.php` | Export rejected-mention feedback as a training corpus (JSONL or CoNLL) |
| `auth-res:status` | `authResStatusTask.class.php` | Aggregate snapshot of the working set |
| `auth-res:reprocess` | `authResReprocessTask.class.php` | Re-run candidate generation + scoring for one or all pending mentions |
| `auth-res:reprocess-parked` | `authResReprocessParkedTask.class.php` | Bulk un-park + re-review every parked mention since a given date |
| `auth-res:cache-stats` | `authResCacheStatsTask.class.php` | Per-source breakdown of `ahg_authority_lookup_cache` |
| `auth-res:cache-clear` | `authResCacheClearTask.class.php` | Delete rows from `ahg_authority_lookup_cache` (with safety gate) |

Eleven tasks. The `auth-res:write-provenance` task exists on the AtoM side; on the Laravel side the equivalent write happens synchronously inside the recorder and there is no separate command.

## auth-res:status

Aggregates the authority-resolution working set:

- `ahg_mention` rows by `state` (pending / linked / parked / rejected / new_record_created)
- `ahg_mention` rows by `entity_type` (PERSON / ORG / GPE / PLACE)
- `ahg_mention_candidate` row count + avg per mention + mentions-with-candidates
- `ahg_mention_decision` rows by `decision_type`
- `ahg_mention_park` rows + `new_candidate_available` count
- `ahg_ner_feedback` rows + unexported count
- `ahg_authority_lookup_cache` rows total + by source
- Fuseki named-graph triple counts (decisions + field-provenance)

```bash
sudo -u www-data php symfony auth-res:status
sudo -u www-data php symfony auth-res:status --json
```

Example output (live, 2026-05-19):

```
Authority Resolution status @ 2026-05-19 11:17:08
============================================================
ahg_mention rows by state:
    pending: 1008
    linked: 2
    new_record_created: 1
    rejected: 1

ahg_mention rows by entity_type:
    GPE: 421
    PERSON: 312
    ORG: 279

ahg_mention_candidate rows: 9 (avg 1.50 per mention, across 6 mention(s) with candidates)

ahg_mention_decision rows by type:
    link: 3
    create_new: 1
    park: 1
    reject: 1

ahg_mention_park rows: 0 (new_candidate_available: 0)
ahg_ner_feedback rows: 1 (unexported: 0)
ahg_authority_lookup_cache rows: 2 (by source: viaf=1, wikidata=1)

Fuseki named-graph triple counts:
    decisions (urn:atom:auth-res:graph:decisions): 79 triples
    field-provenance (urn:atom:auth-res:graph:field-provenance): 38 triples
```

`--json` emits a machine-readable payload (mirrors the human render structure 1:1) for piping into dashboards.

## auth-res:reprocess

Re-runs candidate generation then evidence scoring for either a single mention or every mention with `state = 'pending'`:

```bash
sudo -u www-data php symfony auth-res:reprocess --mention-id=138
sudo -u www-data php symfony auth-res:reprocess --all-pending
sudo -u www-data php symfony auth-res:reprocess --all-pending --limit=100
```

`--limit=N` caps the `--all-pending` sweep. Per-mention failures are logged to stderr but the bulk task exits 0 so scheduled wrappers do not break on a single bad row. The single-mention mode (`--mention-id`) exits non-zero on failure.

Example:

```
Mention 42: regenerated 4 candidates, rescored 4.

Reprocessing 200 pending mention(s)...
...
Done. 200 reprocessed, 0 failed.
```

## auth-res:reprocess-parked

Bulk un-park + re-review every park row whose `parked_at >= --since 00:00:00`:

```bash
sudo -u www-data php symfony auth-res:reprocess-parked --since=2026-05-01
sudo -u www-data php symfony auth-res:reprocess-parked --since=2026-05-01 --dry-run
sudo -u www-data php symfony auth-res:reprocess-parked --since=2026-05-01 --user-id=42
```

For each row the task:

1. Deletes the `ahg_mention_park` row.
2. Flips `ahg_mention.state` from `parked` to `pending`.
3. Regenerates candidates.
4. Re-scores them.

`--user-id` attributes the bulk un-park to a specific archivist; default is `0` ("CLI bulk").

`--dry-run` previews which park rows would be touched without acting.

The task treats "is not parked" and "not found" results as idempotent skips (counted but not errored), so a repeat run of the same `--since` window is harmless.

## auth-res:cache-stats

Pure SELECT report of `ahg_authority_lookup_cache`. Per source:

- row count
- oldest `retrieved_at`
- newest `retrieved_at`
- entity-type breakdown (PERSON / ORG / PLACE)

```bash
sudo -u www-data php symfony auth-res:cache-stats
sudo -u www-data php symfony auth-res:cache-stats --json
```

## auth-res:cache-clear

DELETEs from `ahg_authority_lookup_cache` scoped to one source or every row. Safety first: without `--force` the task prints the count it would delete and exits 2 (non-zero).

```bash
sudo -u www-data php symfony auth-res:cache-clear --source=viaf            # PREVIEW only, exit 2
sudo -u www-data php symfony auth-res:cache-clear --source=viaf --force    # actual delete
sudo -u www-data php symfony auth-res:cache-clear --all --force            # nuke everything
```

No interactive STDIN prompt. Symfony 1.4 readline + sudo + cron is a footgun. The `--force` flag is the gate.

## auth-res:export-ner-feedback

Drains every `ahg_ner_feedback` row with `training_exported = 0`, writes a dated file under `/usr/share/nginx/archive/uploads/auth-res/ner-feedback/`, flips `training_exported = 1` + `exported_at = NOW()` on the shipped rows. If that path is not writable, falls back to `/tmp/ahg-auth-res-ner-feedback/`.

```bash
sudo -u www-data php symfony auth-res:export-ner-feedback --format=jsonl
sudo -u www-data php symfony auth-res:export-ner-feedback --format=conll
```

JSONL line shape:

```json
{
  "feedback_id": 1,
  "mention_id": 168,
  "decision_id": 7,
  "text": "<source_text>",
  "spans": [
    {
      "type": "PERSON",
      "value": "Mark Twain",
      "rejection_reason": "NER mis-typed; this is a date, not a place",
      "archivist_user_id": 1,
      "ner_model_version": null,
      "start": 42,
      "end": 52
    }
  ]
}
```

CoNLL-2003: one `token TAG` line per token, blank line between examples. Rejected span tagged `B-<TYPE>` / `I-<TYPE>`; outside tokens tagged `O`. Each example is preceded by a `# feedback_id=... reason=...` comment line.

The exported file is consumed by the NER retraining job. Once a file is exported, source rows are flagged `training_exported = 1` so subsequent exports never double-count.

## Cron wiring

None of the eleven tasks require a cron schedule out of the box. Suggested ops patterns:

```cron
# Background scan of parked mentions (recommended)
0 2 * * * cd /usr/share/nginx/archive && sudo -u www-data php symfony auth-res:scan-parked >/dev/null 2>&1

# Weekly bulk re-review of pending mentions
0 3 * * 0 cd /usr/share/nginx/archive && sudo -u www-data php symfony auth-res:reprocess --all-pending --limit=200 >/dev/null 2>&1

# Weekly bulk re-review of parked mentions
30 3 * * 0 cd /usr/share/nginx/archive && sudo -u www-data php symfony auth-res:reprocess-parked --since=$(date -d '7 days ago' +%F) >/dev/null 2>&1

# Daily NER feedback export
0 4 * * * cd /usr/share/nginx/archive && sudo -u www-data php symfony auth-res:export-ner-feedback --format=jsonl >/dev/null 2>&1
```

If you are running AtoM Heratio and Laravel Heratio side-by-side against the same database, schedule the periodic tasks on ONE codebase only. The Laravel side ships its cron under `/etc/cron.d/heratio` already; keep the AtoM tasks for ad-hoc runs.

## Symfony 1.4 implementation notes

- All tasks use the SF1.4 explicit `require_once` chain because the plugin classes live under `lib/Services/` and SF1.4 has no PSR-4 autoloader for them.
- Tasks initialise via `sfContext::createInstance($this->configuration)` then `\AhgCore\Core\AhgDb::init()` to get the Capsule database handle that the services depend on.
- Run as `sudo -u www-data` to avoid leaving root-owned files in `storage/` or `uploads/`. Running as root can also create the daily log file owned by `root:root`, breaking subsequent www-data writes.

## Fuseki query endpoint derivation

Both `auth-res:status` and `auth-res:write-provenance` need the SPARQL query endpoint, not the update endpoint. Resolution falls back through:

1. `ahg_settings.fuseki_query_endpoint` (explicit)
2. `ahg_settings.fuseki_update_endpoint` with `/update` swapped for `/sparql`
3. `ahg_settings.fuseki_endpoint` + `/sparql`

On the live server (`openric-model` dataset) both `/sparql` and `/query` respond identically, so step 2's `/sparql` derivation is what is used.

## See also

- `authority-resolution-user-guide` - the high-level overview
- `authority-resolution-park-queue` - park queue + bulk re-review
- `authority-resolution-evidence-scoring` - the ten evaluators
- `authority-resolution-provenance` - the RDF-Star audit trail
- `ahgauthorityresolutionplugin` - file inventory and namespace map
