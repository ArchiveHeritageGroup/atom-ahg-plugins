# ICIP no-leak matrix (issue #232)

A test script for a **VM with the same nginx config as PSIS**. Do not run it against a live site. None of it has been run yet; the offline check (`php ahgICIPPlugin/testing/policy-check.php`) covers the policy and the listeners only.

## Setup

Run `ahgICIPPlugin/database/install.sql` on the VM, then clear the cache and restart php-fpm so the new classes load.

Fixtures:

- One published record with a digital object for each restriction type: `community_permission_required`, `gender_restricted_male`, `gender_restricted_female`, `initiated_only`, `seasonal`, `mourning_period`, `repatriation_pending`, `under_consultation`, `elder_approval_required`, `custom`. All name community A.
- One record per notice combination: acknowledgement only, `blocks_access`, `display_public=0`, `display_staff=0`.
- A parent with `applies_to_descendants=1`, a child and a grandchild.
- One expired restriction and one starting tomorrow.
- A control record with no ICIP data, and a slug that does not exist.

Users: anonymous, researcher, editor, steward of community A, steward of community B, administrator.

Expected result per fixture and user:

| Type | Anonymous, researcher | Steward A | Steward B | Editor | Admin |
| --- | --- | --- | --- | --- | --- |
| Hard types, `seasonal`, `mourning_period`, `repatriation_pending`, `custom` | 404 | Allow | 404 | Metadata only | Allow, logged |
| `under_consultation` | Metadata only | Allow | Metadata only | Allow | Allow |
| `blocks_access` notice | 404 | Allow | 404 | Metadata only | Allow, logged |
| Acknowledgement notice | Notice step | Allow | Notice step | Notice step | Allow |

: Expected decision per fixture and user

For each request record status and a body hash:

```bash
curl -s -o /tmp/body -w '%{http_code} ' -b "$COOKIES" "$BASE$URL"; sha256sum /tmp/body
```

Wait 60 seconds after changing a restriction before testing derivatives or tiles. The derivative gate and the Cantaloupe delegate each cache for a minute.

## Matrix

Status: **Closed** = enforced by this build. **Filter** = relies on the existing list filter, which already hides every active restriction from non-administrators. **Open** = this build does not close it, and no reference to the access filter was found in that code, so expect a leak until it is tested and fixed in its own plugin.

| # | Surface | How to test | Status |
| --- | --- | --- | --- |
| 1 | Record page by slug on all five sector modules | `/<slug>`, `/museum/<slug>`, `/library/<slug>`, `/gallery/<slug>`, `/dam/<slug>` | Closed |
| 2 | Print, finding aid, EAD, DC, CSV by slug | `/<slug>;ead?sf_format=xml`, `;dc?sf_format=xml`, print and CSV routes | Closed for slug routes. **Open:** already generated finding aid files under `/downloads/` |
| 3 | Master file URL | `/uploads/r/.../file.tif`, also with an `/index.php` prefix and doubled slashes | Closed |
| 4 | `_141` and `_142`, fast path and fallback | Request each as anonymous and steward. Check `X-Accel` behaviour | Closed for restricted and blocking records. **Open by decision:** an acknowledge-only record's derivatives are served before acknowledgement (D6) |
| 5 | IIIF manifest, image, content search | `/iiif/manifest/<slug>`, `/iiif/manifest/id/<id>`, `/iiif/v3/manifest/<slug>/search`, `/iiif/2/<identifier>/info.json`, `/archive/iiif/2/...`, `/iiif/viewer/<id>`, `/iiif/ocr/object/<id>` | Closed. **Open:** `/iiif/3d/` and manifest collections not reviewed. A steward without `readMaster` gets no tiles |
| 6 | REST v2 read, search, children, hierarchy | `/api/v2/...` with a key for each role | Filter. API-key steward stays hidden (Phase 2) |
| 7 | GraphQL | Query by id and by list | Filter |
| 8 | OAI-PMH | `ListRecords`, `GetRecord` for each fixture | **Open** (`arOaiPlugin`, base) |
| 9 | Search results, facets, counts | Search a unique word in each title. Compare facet counts | Filter for results. Facet counts not reviewed. Acknowledge-only records are listed by design |
| 10 | Treeview and autocomplete | Parent's tree, `hierarchyData`, description autocomplete | **Open** (`ahgCorePlugin`, base) |
| 11 | Browse, repository holdings, term pages | Browse with each fixture present | Filter |
| 12 | Clipboard and portable export | Add by slug, export | Portable: Filter. Clipboard: **Open** |
| 13 | Chatbot, discovery, semantic search | Ask for a fixture's unique word | Chatbot and discovery: Filter. Semantic search: **Open** |
| 14 | C2PA public pages and manifests | Public verify page for a fixture's file | **Open** (depends on #152) |
| 15 | Knowledge graph and RiC explorer | Graph and entity pages for a fixture | **Open** in `ahgRicExplorerPlugin` |
| 16 | Public story pages | A story containing a fixture | **Open** (depends on #155) |
| 17 | Sitemap | Generate, then grep for fixture slugs. Also the ResourceSync lists | **Open** |
| 18 | Denied response identical to a true 404 | Compare status and body hash of a denied slug and a missing slug, ignoring the request path | Closed, hash comparison still to run |
| 19 | Inheritance to a grandchild | Page, file and tile of the grandchild | Closed |
| 20 | Expired reopens, future not yet applied | Both fixtures, then wait for the 60 second caches | Closed |
| 21 | GET acknowledgement refused, POST without CSRF refused | `curl /icip/acknowledge/<id>` = 405. POST with no token = 403. Neither writes a row | Closed |
| 22 | One user's acknowledgement does not unlock another | Two users, and two anonymous sessions | Closed |
| 23 | Filter exception results in denial | On the VM only, rename `icip_cultural_notice_type`, request a governed record, restore | Closed |

: No-leak surfaces and their status after this build

## Browser checks

On all five sector pages: banner severity colours, TK/BC chips with working Local Contexts links, the acknowledgement form returning to the record, and no broken image box on a metadata-only record. Check the console for CSP errors.
