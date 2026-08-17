# Security posture for marketplace, privacy and RiC; anonymous draft disclosure closed

**Date:** 2026-08-17
**Release:** atom-ahg-plugins v3.103.1
**Instance:** PSIS (`/usr/share/nginx/archive`, psis.theahg.co.za)

## Why this ran

Continuation of the per-action `security.yml` audit. The audit reports every module
that has actions but declares no security posture, because a module without
`modules/<name>/config/security.yml` inherits `default: is_secure: false` from
`apps/qubit/config/security.yml` and is therefore public. 31 flagged modules were
probed anonymously against PSIS; three answered.

## The finding

`/ricExplorer/getData?id=<n>` had no access check of any kind. Anonymous requests
returned the title, slug and relationship graph of **unpublished draft
descriptions**. Confirmed against three live PSIS drafts (ids 902793, 902798,
905977) before the fix and refused after it. Same class of defect as #270, where
media was served to anonymous callers, and as the museum module's `AclService`
misuse corrected on 2026-08-15.

`buildGraphData()` selects straight from `information_object` with no publication
or ACL condition. The endpoint's only caller is `_ricPanel.php`, which is not
registered on any page, and both graph screens (`knowledgeGraph`,
`provenanceGraph`) already tested `isAuthenticated()`. Closing it broke nothing.

Fixed in two layers:

- `modules/ricExplorer/config/security.yml` closes the module to anonymous callers.
- `getDataAction.class.php` additionally calls `QubitAcl::check($resource, 'read')`,
  so an ordinary authenticated account cannot enumerate unpublished titles through
  the same URL. `QubitAcl::check()` and not `AclService::check()`: the latter
  returns false for every anonymous user, which is the right answer here by
  accident and the wrong one elsewhere.

## What the app-level security.yml actually does

The reason the defect was visible at all is worth recording, because it changes how
these audits should be read.

`apps/qubit/config/security.yml` is not only a default. It carries entries keyed by
**action name** which apply to that action in **every** module lacking its own
security.yml:

```yaml
default:
  is_secure: false

autocomplete:
  credentials: [[ editor, administrator ]]
  is_secure: true
```

In ricExplorer, with no security.yml and no code guards, `autocomplete` was refused
while `getData` answered anyone. Same module, same base class, neither action
testing the user. The difference was entirely that file.

Two consequences. A plugin action can be protected purely by its name -
ricExplorer's `autocomplete` selects from `information_object` with no publication
filter, and is not a public draft-title search only because of this entry. And an
anonymous probe returning the login page does **not** establish that a module is
guarded, which affects how earlier reachability sweeps should be interpreted.

Verified on PSIS that a module-level `security.yml` **merges** with these entries
rather than replacing them: after adding a file naming only `all`, autocomplete was
still refused. The inherited rule is restated in the module file regardless, so the
protection is visible where the action lives.

## Postures declared

| Module | Posture | Reasoning |
|---|---|---|
| `marketplace` | 10 shopfront actions public, `all: is_secure: true` | 47 of 57 action files already guard themselves. The remainder are browse, search, category, featured, sector, auctionBrowse and four read-only API lookups. A marketplace nobody can view without an account is not a marketplace. |
| `privacy` | 7 data-subject actions public, `all: is_secure: true` | Public by necessity. Someone exercising a POPIA or GDPR right has no account, and requiring one would mean handing over more personal information to ask what is held. `dashboard` publishes the Information Officer details POPIA requires published. `dsarStatus` matches reference number **and** requestor email as a pair in one query, so it cannot be enumerated - that is the only reason the module can safely be public. Staff screens live in `privacyAdmin`, which is separate and checks the user. |
| `ricSemanticSearch` | index, widget, examples public; `proxy` secured | The live index action is eleven lines handing a configured URL to a template. No database read, nothing disclosed. |
| `ricExplorer` | `all: is_secure: true` | See above. |
| `ricDashboard` | administrators only | `/admin/ric/*`, including AJAX endpoints that resync, clear queue items and clean up orphans. Those change state. |
| `ricShacl` | administrators only | Validation reports name records that fail shape constraints, published or not. |

## Noted, not fixed

`ricSemanticSearch`'s `executeProxy` appends a caller-supplied `endpoint` parameter
to the configured API base and curls the result back. The host is fixed by
configuration so this is not open SSRF, but the path is the caller's, which would
let a visitor reach arbitrary paths on an internal service. It is dead code today:
it lives in `semanticSearchActions.class.php`, which symfony never loads for this
module because `actions.class.php` wins, so the URL 404s. It was given an explicit
secured entry anyway so it cannot open later without a decision.

## Verification

- Anonymous on PSIS after the change: `getData` for a draft and for id 553 both
  refused; `autocomplete` still refused; `ricSemanticSearch`, `privacy`,
  `privacy/dashboard`, `privacy/dsarRequest` and `marketplace/browse` all still
  public, serving their own modules.
- `php -l` clean on the edited action.
- `bin/audit-security-yml`: 249 unguarded actions before, **233** after.
- `bin/audit-security-yml-baseline` re-run so the CI ratchet falls to 233. The
  remaining backlog is 120 ahgCorePlugin overrides and 56 base AtoM modules.

Also carried in this release: `AdminErrorDetail::handle()` now returns early for
`sfError404Exception`, so administrators get a normal 404 page instead of a stack
trace for crawler probes such as `?sf_format=xml` and `?template=ead`. Deployed and
verified on PSIS before release.
