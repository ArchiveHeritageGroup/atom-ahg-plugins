# Security sweep (#258/#261/#262), local barcodes (#260), heritage navigation

Date: 2026-08-03
Instance: PSIS / archive
Releases: atom-framework v2.13.51 - v2.13.55, atom-ahg-plugins v3.88.17 - v3.88.27
Issues: #258 fixed (open for phase 2), #259 closed, #260 closed, #261 closed, #262 closed

Continues from `2026-08-02-landing-filter-outage-and-help-f1.md`.

## 1. #258 - digital object masters bypassed all access control

**The exposure.** A draft record's page correctly returned 403 to an anonymous
user while its master PDF downloaded in full (252,732 bytes) from the raw
`/uploads/r/...` URL. ACL, draft status, embargo and security clearance were all
bypassed because nginx served the file and the request never reached PHP.

`apps/qubit/config/routing.yml:102` routes `/uploads/r/*` to `digitalobject/view`
on purpose - AtoM intends the ACL check. Serving it statically was simply a
misconfiguration. **No instance on this host had the `/private/` block**, so the
`X-Accel-Redirect` target did not exist anywhere.

Deleting the `/uploads/` location was not sufficient: `location /` ends in
`try_files $uri /index.php?$args`, so `try_files` finds the file and serves it
regardless. An explicit rule was required.

**Three parts to the fix:**

1. **nginx** - `/uploads/r/` masters fall through to `index.php`; added
   `location /private/ { internal; alias /usr/share/nginx/archive/; }`.
2. **The text/PDF ACL bypass (fw v2.13.54)** - closing nginx alone was not
   enough: the request then reached PHP and PHP granted it.
   `QubitInformationObjectAcl::isDigitalObjectActionAllowed()` returned true for
   `readMaster` on any TEXT media object, *before* both the standard ACL check
   and `QubitGrantedRight::checkPremis()` - which is also why PREMIS Rights could
   never restrict a PDF (upstream artefactual/atom#1724). Patched both
   `QubitInformationObjectAcl` and `QubitActorAcl` behind a new
   `allow_public_text_masters` setting, default off, failing closed.
3. **An orphaned-derivative crash (plugins v3.88.26)** - routing through PHP
   exposed a latent fatal. Digital object 904027 is a thumbnail with
   `parent_id` NULL, so `getObjAndAction()` returns a null owner and
   `QubitAcl::check()` dies on `get_class(null)`.

| anonymous request | before | after |
|---|---|---|
| draft record's master PDF | 200, 252,732 b | **404** |
| draft record's master image | 200 | **404** |
| `/private/` direct | 200 | **404** |
| public pages, thumbnails, IIIF | 200 | 200 |

### Accepted behaviour change

The ACL grants `readMaster` to **editor and contributor only**. Anonymous and
authenticated have `readReference` and `readThumbnail`. So anonymous visitors can
no longer download originals from **published** records either. That is the ACL
working as configured; nginx was previously ignoring it. To restore public
master downloads, grant `readMaster` to the anonymous group - do not reopen nginx.

### LESSON - phase 1 heuristic was wrong

The plan assumed derivatives are reliably suffixed `_141`/`_142`. Of 1122
derivative rows: 594 carry that suffix, 54 use `_thumbnail`/`_reference`, and
**474 have no distinguishing pattern** (`.mp3`, `.glb`, `.zip`). Roughly half of
derivative requests therefore go through PHP. More correct, but more load than
predicted. Generalised from four sampled rows - should have counted first.

### LESSON - ahgCorePlugin's digitalobject/view is the copy that runs

A stack trace proved `atom-ahg-plugins/ahgCorePlugin/modules/digitalobject/actions/viewAction.class.php`
executes, **not** `apps/qubit/...`. An earlier session note claimed the opposite.
The fix therefore lives in our own plugin and needed no base patch; the base file
was reverted to original.

## 2. #259 - malformed export URLs fatal instead of 404 (plugins v3.88.19)

Got this wrong twice before getting it right, both corrections recorded on the issue.

- **Wrong #1:** "sector objects have no export serialisation". Not sector-specific.
- **Wrong #2:** "XML export is broken catalogue-wide". Only the query-string form.

**Actual cause.** `template` is a **route** parameter from the semicolon syntax
(`/:slug;:template` in routing.yml), never from the query string. So
`?template=ead` leaves `$parameters['template']` unset, `getActionParameter()`
falls back to the record's display standard, and the module resolves to
`sfIsadPlugin` - which ships no `.xml.php` template. `sfPHPView::renderFile()`
then runs `require('')`. An `ob_start()` above it discards the buffer, which is
why some paths returned a blank 200 and others a 500.

Export was never broken: `/word-document;ead?sf_format=xml` returns valid EAD,
and museum and gallery records serialise fine.

**Fix:** `refuseUnavailableFormat()` listener on `controller.change_action` in
ahgCorePlugin - checks whether the resolved module ships any template for the
requested format, resets the format to HTML, then raises 404. Fails open.

**LESSON:** resetting the format before raising matters. The 404 page renders
through the same view layer, so leaving the format as `xml` made the error
template lookup fail identically and still returned a blank 200.

## 3. #260 - barcodes and QR codes came from third parties

Label screens pointed `<img src>` at `barcodeapi.org` and `api.qrserver.com`,
sending record identifiers and public URLs to parties we do not control, and
producing nothing offline. Neither host was in the CSP `img-src` allowance, so
both would have broken outright once CSP is enforced (#248).

**The premise in the issue was wrong.** "The capability already exists, it is
mostly wiring" - it did not. `BarcodeGenerator`'s fallback (used because
tc-lib-barcode was not installed) had three defects:

1. **The QR generator was decorative** - finder patterns plus cells filled from
   `md5($data)`. No encoding, no error correction, no masking. Would never scan.
2. **Code 128 silently dropped characters** outside a ~40-entry table, so
   `ENG_FONDS_001` printed with underscores but encoded `ENGFONDS001`.
3. **The check symbol was usually omitted** (103 possible values, ~40 in the
   table) and weighted by input position rather than symbol position.

Shipping that would have been worse than the external dependency: a label that
looks right and does not scan fails silently in a physical workflow.

**Fix:** added `tecnickcom/tc-lib-barcode` to **`atom-framework/composer.json`**
(fw v2.13.51), new `AtomExtensions\Services\BarcodeService` returning data URIs
(fw v2.13.52), all 11 references replaced across ahgSpectrum and ahgLabel
(plugins v3.88.20), dead `BarcodeGenerator` deleted (v3.88.21).

**Placement mattered.** The AtoM root `composer.json` is in a non-git directory.
A dependency added there would work on PSIS and silently not exist on archaeology,
Wits or a fresh deploy - those instances would keep printing unscannable labels.
`atom-framework/composer.json` is in git and `composer install` runs from
`bin/release` and all three installer scripts.

Verified real: QR module count now scales with payload (233 / 442 / 1042 modules
for 2 / 27 / 117 characters) instead of a fixed 21x21 grid.

**Also:** the three JS call sites mattered as much as the markup - the
barcode-source dropdown rewrote `img.src` client-side, so fixing only the
server-rendered tags would have left the leak intact. Every dropdown option is
now pre-rendered into a `data-barcode-uris` attribute.

## 4. Heritage accounting

**Assets link (v3.88.22).** The sidebar link went straight to the add form, so
every visit offered to create a new asset regardless of what existed. Now routes
via `/heritage/object/<slug>`: one asset opens it, several list them, none offers
creation. `viewByObject` used `->first()`, hiding all but one asset on records
with several, and checked only `information_object_id` when the table also
carries `object_id`.

**Navigation (v3.88.23, v3.88.24).** Ported from the Heratio build at
heratio.org. Accounting, GRAP compliance and reporting live in separate modules
with no links between them - each was a dead end. Shared `_accountingMenu`
partial now on all 16 pages.

Worth noting the Heratio page was **worse** in other respects: its `browse.blade.php`
renders a `$stats` block the controller never populates, and its table body
iterates every DB column against a six-column header. PSIS already had working
filters and stat cards. Only the navigation was worth taking.

**SECURITY (v3.88.25).** While checking whether to route `heritageAdmin`, found
that `heritageAccounting`, `grapCompliance` and `heritageReport` had **no
`security.yml` at all**. `apps/qubit/config/security.yml` sets
`default: is_secure: false`, so every action was public: `/heritage/settings`,
`/heritage/add`, `/heritage/<id>/edit` all returned 200 anonymously, and
`executeAdd` writes to `heritage_asset` on POST with no credential check.

Added four `security.yml` files (editor/administrator; administrator only for
settings), using the **double-bracket OR form** - `credentials: [editor]` as a
single-item list 403s administrators.

## 5. #261 - bin/install Step 7

Step 7 patched `QubitMetadataRoute` with four `sed -i` inserts guarded by
`grep -q "ahgLibraryPlugin"`. The file never contains that string, so the guard
was always false and every run re-inserted the entries. Proven: 1 -> 2 -> 3
occurrences across two runs. Replaced with `cp -f` of the patch file; stable at 1.

**The worse half.** `patches/qbAclPlugin/lib/QubitAcl.class.php` was **stale
relative to live base** - base carried a `viewDraft` permission block and a
different Role 99 fix the patch lacked. Step 11 already copied that file
unconditionally, so the next `bin/install` would have **overwritten working
production fixes with older code**, silently. Synced patch to base.

Step 11 now also reapplies the two #258 ACL patches, with an explicit warning if
either is missing.

## 6. #262 - heritageAdmin hardened and routed (v3.88.27)

16 actions and 16 templates, never routed. `boot()` already required
`isAdministrator()` (the issue's claim of no credential checks was wrong), but
seven destructive actions deleted from GET parameters with no CSRF - an
administrator following a crafted link would have triggered a deletion.

`requireSafePost()` (POST + valid CSRF, failing closed) on all seven; 12
destructive links converted to POST forms via a `_postAction` partial;
confirmation moved from inline `onclick` to `data-confirm` bound by a nonce'd
script, so it survives CSP enforcement. Routes registered with `post()` for state
changes.

**Route collision caught in testing:** `/heritage/admin/region/uninstall` returned
200 because `/heritage/admin/region/:region` matched it as a region code and
dispatched `regionInfo` - the POST route did not match a GET so the router fell
through. Verbs moved under `/heritage/admin/regions/`. Standard and rule routes
were unaffected because their `:id` requires `\d+`.

## Cross-cutting lessons

### The theme silently drops registered assets

Three separate bugs today traced to this:

- `$response->addJavascript()` / `addStylesheet()` are **no-ops** - ahgThemeB5Plugin
  never calls `include_javascripts()` / `include_stylesheets()`. This killed the
  entire ahgHelpPlugin client side.
- The layout emitted only the webpack bundle, so **custom.css never loaded** -
  every override in it was dead, including record-view carousel styling shipped in
  v3.88.10 that had never once applied. Fixed in v3.88.19.

Any plugin registering assets the conventional way is still shipping dead code.
Worth a sweep.

### A missing security.yml fails open

`default: is_secure: false` means a module without its own file is public. The
**absence** of a file is the bug pattern, which makes it invisible in review -
nothing looks wrong because nothing is there.

### AtoM serves the login page with HTTP 200

Nearly reported the security fix as failed because every URL still returned 200.
Testing on `<body class>` showed `user login`. **Status codes are the wrong signal
for access control on this stack** - test on content.

### Fallbacks that fail silently are worse than none

The fake QR would have produced unscannable labels indefinitely because it
*looked* right. Same shape as the stale patch file. Both fail without a trace.

## Outstanding

- **#258 phase 2** - route derivatives through PHP so readReference/readThumbnail
  are enforced for all of them (roughly half go that way incidentally now).
- **Public master downloads** - decide whether to grant `readMaster` to anonymous.
- **Verification requiring a browser** - authenticated heritage pages, the new
  heritageAdmin POST buttons, and a printed label scanned with a real scanner.
- **`composer audit`** reports 24 advisories across 8 pre-existing framework packages.
- **Soft-delete for standards and rules** - reference data compliance results
  depend on is still hard-deleted.
- **`bin/install` sweep** for other `sed -i` calls guarded by a string the edit
  does not itself introduce.
- Unreproduced: a JSON SyntaxError on a record page reported for an authenticated
  session; all three endpoints that page calls return clean JSON anonymously.
