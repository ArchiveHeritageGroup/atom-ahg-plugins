# Heritage discovery dedup, and making KM ingest failures visible

**Date:** 2026-08-10
**Releases:** framework v2.15.1, v2.15.2
**Issues:** [#265](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/265) items 3-4, [#264](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/264)

## KM ingest failed silently by construction (#265)

A drop-folder with no feedback channel cannot report rejection. The publisher's
`cp` always succeeds, so the only evidence was a file in a directory nobody
opens - which is how 37 payloads were lost for four weeks.

**Rejections now surface.** `/opt/ai/km/spool_watcher.py` raises a Workbench
notification when it moves a payload to `failed/`, carrying the actual error. It
uses the notification spool the watcher's own module docstring already pointed at.

    title    KM ingest rejected a payload
    message  km265-probe.json was refused and moved to failed/.
             HTTP 400 {"error":"title and body are required"}

Confirmed it does **not** fire on success - a failure alarm that also fires on
success gets muted within a week. Best effort by design: a notification that
cannot be written is logged and dropped, because a broken bell must never stop
the sweep. Backup at `spool_watcher.py.bak-2026-08-10`.

**Publishing verifies instead of trusting the write.** `atom-framework/bin/km-publish`
drops the file, waits for the watcher, and reports which folder it landed in. It
refuses the two shapes that caused the incident before dropping anything, and
exits non-zero on anything that is not `archive/` so a release script can fail on
it. All four outcomes exercised, including a stalled watcher.

**Correction to the issue:** it claimed `failed/` was empty. All 37 originals
were still there - the recovery re-dropped markdown copies and never cleared
them. Verified all 37 have counterparts in `archive/` and moved them to
`failed/recovered-2026-08-10/`. This mattered: `failed/` is now the signal, and
37 stale entries would have made any future rejection indistinguishable from the
backlog.

## Heritage search: the cost was deduplication (#264)

The original issue blamed four search strategies and paginate-after-fuse. Its own
correction measured whole-page wall clock and concluded the endpoint was cheap.
Both missed it, because per-page render dominates the total.

Instrumenting the five stages separately:

    case             total  cands  parse  strat  fuse   dedup
    empty query        101    500    0ms   35ms   3ms   133ms
    very broad (a)     107    511    3ms   39ms   8ms   149ms
    common keyword      24     30    6ms   39ms   0ms     1ms

`deduplicate()` compared every candidate against every kept title with
`similar_text()` - up to ~125,000 calls of a superlinear function on 500
candidates.

**The fix cannot change results.** `similar_text()` reports
`2 * matched / (len(a) + len(b))`, and matched cannot exceed the shorter string,
so a pair can only clear the threshold when `max < min * (2 - t) / t` - at t=0.9,
lengths within ~1.22x. A necessary condition, not a heuristic. Titles are
bucketed by length; exact matches (including the empty title, which the old code
caught via `$a === $b`) go through a hash.

Verified identical output, same order, on eight real queries. Expensive cases
123ms -> 17ms and 143ms -> 18ms. Live: `q=a` 1.33s against 1.67s recorded
earlier.

**The caveat matters more than the speedup.** On a synthetic corpus of
uniform-length titles the gain drops to ~2.7x and both versions stay quadratic:

    items    old ms   new ms
     1000      3776   1395
     2000     14540   5449
     4000     56773  22002

The length prune cuts the constant, not the complexity class. It works on real
archival titles because their lengths vary. **Headroom bought, not a scalability
fix.** Capping candidates before fusion is what would bound it, and that changes
`total` from exact to "at least", so it is a product decision.

## Lesson

Whole-page timing hid a 10x difference between stages. Two people, including me,
read this code and reasoned about which part was expensive; the measurement
disagreed with both readings. Instrument the stage, not the page.

## Left alone deliberately

`HeritageAssetService::browse()` OR-join - four occurrences, not one. On PSIS all
6 rows have `information_object_id` set and `object_id` null, so COALESCE would
be equivalent *here* and indexable. It is not equivalent in general: where both
are set and differ, the OR matches two rows and COALESCE picks one. Needs a
decision on which column wins.
