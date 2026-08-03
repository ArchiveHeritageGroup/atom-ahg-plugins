# Path traversal (#246), unserialize hardening (#245), security.yml sweep (#263)

Date: 2026-08-03 (later same day)
Instance: PSIS / archive
Releases: atom-framework v2.13.56/57, atom-ahg-plugins v3.88.29/30
Issues: #246 partially done, #245 closed, #263 open (2 parts remain), #258 open (phase 2)

Continues from `2026-08-03-security-sweep-barcodes-heritage.md`.

## 1. #246 - path traversal in file handling

New `AtomExtensions\Services\PathGuard` (fw v2.13.56):

- `within($path, $roots)` - `realpath()` then a separator-boundary prefix check.
  realpath does the real work: it collapses `..`, resolves symlinks and returns
  false for anything non-existent, so a traversal is normalised *before*
  comparison. Rejecting the literal string `..` is not sufficient.
- `withinForWrite()` - for destinations the worker creates on first use;
  validates the parent and passes the leaf through `basename()`.
- Both fail closed. Roots default to the upload dir plus the resolved
  digital-object store, extensible via a `file_access_roots` setting so an
  institution with a different drop folder is not broken.

Tested against nine cases including `..` traversal, dot segments, the
`uploads-evil` prefix trick, empty, missing, and new-leaf destinations.

**Four call sites constrained** (v3.88.29): ingest server-directory, ingest
watched-folder, preservation `verifyBackup`, and scan folder config. All were
admin-gated but unscoped - **administrator is not the same as "may read any file
on the host"**, which matters on a hosted or multi-tenant deployment.

Two audit items checked and left alone as already correct: `preservica` uses
`basename()`, and `portableExport` already does realpath + prefix + `..` rejection.

### The serious find: ahgScanPlugin/scanManage was fully public

Plugin enabled, module routed, **no security.yml and no code guard**, so it
inherited `default: is_secure: false`. `/admin/scan/create` and
`/admin/scan/:id/run` were anonymously reachable, and a scan folder's `path`,
`processed_path` and `failed_path` feed a worker that reads from one and **moves
files into** the others.

Not a read oracle like the other three - unauthenticated arbitrary file
movement. Found on the first look after filing #263, which had predicted exactly
this class.

## 2. #245 - unserialize hardening

Three sites still unhardened, all in `ahgCorePlugin`: the OAI plugin-list check
and both inventory-levels settings readers. Now pass
`['allowed_classes' => false]`, with `?: []` where a corrupt value would fatal
`in_array()`.

**LESSON:** these are older copies of code that already exists hardened in
`ahgSettingsPlugin` and `themesAction`. When a module is refactored into a new
plugin the original stays reachable and the fix does not follow it. Worth
grepping for siblings whenever a security fix lands in a refactored module.

Two sites remain unhardened **by design**, now documented in code
(`CacheService`, `sfParameterHolder`): both store arbitrary objects, so refusing
instantiation returns `__PHP_Incomplete_Class`; both take application-written
input. Each comment names the condition that should trigger a revisit. An
undocumented gap reads as an oversight - which is how the June audit's
considered decision got re-flagged.

## 3. #263 - security.yml sweep

Filed this session after the heritage accounting exposure. `apps/qubit/config/security.yml`
sets `default: is_secure: false`, so a module **without** its own file is public
- the *absence* of a file is the bug, invisible in review.

Scan of 235 modules with actions: 203 have no `security.yml`. Most are
legitimately public; the filters that matter:

- skip modules that already carry `modules/<name>/config/security.yml`
- skip modules with a code-level guard (`isAdministrator`, `isAuthenticated`,
  `forward('admin','secure')`, `AclService::`, `hasGroup`, `checkAdminAccess`,
  `requireAuth`) - heritageAdmin and preservation gate this way
- **skip plugin modules whose name matches a base module carrying its own
  security.yml** - `physicalobject` overrides in three plugins inherit
  `apps/qubit/modules/physicalobject/config/security.yml`. Without this the scan
  produces false positives.

**Four modules fixed:** `scanManage` (above), plus ahgLibrary's `kbartVendor`
(delete/toggle/fetch public), `z3950` (edits search targets and issues outbound
queries, so also a request proxy) and `copyCataloguing`.

### copyCataloguing - a POST check is not an access control

`executeImport` creates information objects from posted MARC and begins with
`if (!$request->isMethod('post')) { $this->forward404(); }`. That reads as
protective and is not. Anyone could POST base64 MARC to
`/library/copy-cataloguing/import` and inject catalogue records, attributed to
nobody because `currentUserId()` is empty for anonymous.

It did **not** appear in the destructive-verb scan - only when the filter was
broadened to write actions. Same shape as the heritage accounting `executeAdd`.

Both sweeps now return zero. Still open on #263: the autocomplete/export/API
**read** sweep (the 2026-07 Artefactual advisory shape, which a write-action
filter cannot catch), and the decision on flipping the app default to
`is_secure: true` with explicit public opt-outs - that removes the class rather
than instances of it.

## 4. Corrections to earlier claims in this session

**There was no "other session" doing rate limiting.** psis.conf changed from 20
www-socket refs (my 12:07 backup) to 20 atom-socket refs. I inferred a
concurrent session from that plus the flood and then repeated it as fact. It was
a guess presented as a finding. The `atom` pool itself is not new - `atom.conf`
dates from 2026-07-05; what changed was psis.conf being pointed at it.

**Every `StatusText` reading quoted during the incident was the wrong pool.**
`pm.status_path` exists only on `[www]`. PSIS now runs on `[atom]`:

| pool | serves | max_children |
|---|---|---|
| `[atom]` | PSIS main routes | **15** |
| `[www]` | everything else, **plus PSIS IIIF/media/extension routes** | 100 |

So "100 active, slow: 10793" described the shared pool, not PSIS. PSIS has had
15 workers since the switch, which fits the timeouts far better than the earlier
reading. Check `pm.status_path` per pool before quoting fpm status on a
multi-pool host.

## 5. Docker migration risks (migration is being done separately)

**#258's nginx half is not in git.** The `/uploads/r/` and `/private/` blocks
live only in `psis.theahg.co.za.conf` on the host. `atom-framework/docker/nginx.conf`
had neither, **plus a blanket `location ~* \.(png|jpg|...)$`** that would serve
image masters statically - so migrating would have silently reopened the ACL
bypass. Fixed in fw v2.13.56: blocks added, static rule scoped to theme/vendor
directories, with a comment explaining why it must stay scoped.

**`extensions.conf` and `heratio.conf` hardcode a socket path** (34
`fastcgi_pass` directives, all `/run/php/php8.3-fpm.sock`). These *are* in git
and will follow to Docker, where the socket differs again. They also mean PSIS's
IIIF and media routes currently run on the **shared** pool, undoing half the
isolation. Worth a variable-based refactor as part of the migration.

## 6. #264 filed - /heritage/search cost

The endpoint behind two outages in three days, previously untracked.

`AtomFramework\Heritage\Discovery\SearchOrchestrator::search()` runs up to four
independent strategies (keyword, entity, date-range, synonym-expanded), merges
every result set, fuses and ranks **the whole set**, deduplicates **the whole
set**, and only then paginates in PHP:

```php
$total = $uniqueResults->count();
$pagedResults = $uniqueResults->slice($offset, $limit);
```

So page 1 with `limit=20` costs the same as materialising and ranking every
match. The limit is applied after the expensive work rather than pushed into the
queries, and there is no result cache. Cost scales with corpus size, not page size.

That fits the observed traffic better than "slow endpoint": **499 distinct IPs
across 500 requests**, one each, with **91 of 200 returning 499** (client aborted
mid-flight). Per-IP `limit_req` cannot touch one-request-per-IP, and a 499 does
not reclaim a worker that has already started. With PSIS on the 15-child `[atom]`
pool, a handful of concurrent requests exhausts it.

#264 puts **measure first** as step one - which strategy dominates is not
obvious from reading, and the fix depends on the answer. Also folded in the
`HeritageAssetService::browse()` OR-join (same anti-pattern as the 2026-08-01
outage, harmless at 6 rows today) since it is the same subsystem.

## 7. KM ingest was failing silently

⚠️ **Three session-log payloads dropped into `/var/spool/km-ingest/` had all been
rejected** - 2026-08-02's and both of 2026-08-03's - and were reported as "KM
updated" without checking.

The watcher accepts **markdown with a `#` heading**, or JSON with **both `title`
and `body`**. The payloads were JSON with `title` plus `summary`/`lessons`/
`follow_ups` and no `body`, so each returned
`HTTP 400 {"error":"title and body are required"}`.

Ingested files move to `archive/<YYYY-MM-DD>/`; rejected ones land in `failed/`
beside a `.json.err`. Writing the file and validating its JSON proves nothing -
**the archive/failed split is the only confirmation that matters.**

Re-dropped all three as markdown (the session logs already are markdown, so they
went in unchanged) and confirmed ingestion. The failed JSON copies were removed.

### The full extent: four weeks, 37 payloads, all mine (#265 filed)

Investigating the rest of `failed/` showed it was not "other sources" - it was
the same bug, all the way back:

- **74 files = 37 payloads + 37 `.err`**, every one `title and body are required`
- Date range **2026-07-04 to 2026-07-31**
- All carry `title`, `project`, `instance`, `date`, `tags`, `summary` - the exact
  shape used today, no `body`

So **every KM drop from this project since 4 July was rejected**, and each was
reported as "KM updated". The verification being done - file exists, JSON parses -
confirms the write, never the system's response.

**Impact.** 29 of 37 also had an in-repo session log, so were recoverable from
git. **Eight dates had no session log** - including four on 2026-07-29
(archeology reconciliation, RiC typed relations #237, security classification
#236, tracking-issues backlog) and two on 2026-07-28. For those the rejected
payload was the only record outside per-project memory: invisible to any other
project, host or person. Exactly the gap KM exists to close.

**Recovery (done).** All 37 converted to markdown and re-dropped - session log
where one existed, otherwise generated from the payload's own fields and marked
as recovered. Verified in `archive/` with `failed/` empty of this project's
payloads. Average recovered summary ~1,300 chars, so content was intact
throughout; it simply never arrived. 47 `psis-*` files now in KM.

**#265 filed** on the failure mode rather than the backlog. A drop-folder with no
feedback channel will fail this way again - the format contract is the proximate
cause, silent rejection is the root one. Proposed, in order: ship the
`km_ingest_doc` MCP tool (a direct call returns the 400 to the caller); accept
`summary` as `body`; **surface a non-empty `failed/`** (the workbench
notification drop folder would do it); update publishing guidance. Item 3 matters
most after item 1 - with the format fixed, the next incompatibility fails
identically.

Cross-project: the standing rule directs every agent on this host to publish this
way, so anyone sending the same JSON shape has been failing the same.

## Outstanding

- #246: live verification of the four call sites (needs a healthy instance).
- #258 phase 2: route derivatives through PHP. Deliberately deferred - it adds
  PHP load and PSIS is on 15 workers; measure against a stable post-migration
  baseline.
- #263: autocomplete/export/API read sweep; decide on the default-deny flip.
- **Nothing in today's security.yml work is verified for authenticated users** -
  anonymous is confirmed blocked across seven modules, but that editors and
  administrators still get through has never been tested.
- `/heritage/search` cost - two outages in three days, still untracked.
