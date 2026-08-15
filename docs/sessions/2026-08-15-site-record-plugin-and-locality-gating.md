# Site record plugin, locality gating, and three PSIS fixes

**Date:** 2026-08-15
**Releases:** atom-ahg-plugins v3.99.31 - v3.100.0
**Instances:** PSIS (production), rari-dev (192.168.0.133)

## ahgSiteRecordPlugin (new, v3.100.0)

Replaces RARI's standalone `rock_forms` application - issue #299. Both its tables are
empty in production, so there was nothing to migrate and no compatibility to preserve.
That made it a redesign rather than a port, which matters, because a faithful port would
have carried real defects forward. Measured in the legacy code: no authentication of any
kind on create, edit or delete; delete performed on a bare GET with no token, so anything
following the link destroyed a record; three checkbox values ("Overhang", "Crayon",
"Silcrete") present in the form but missing from the processing map, discarded on save
without an error; checkbox groups stored as JSON blobs in text columns; and no
created/updated columns anywhere, so nothing recorded who changed a site's coordinates.

**Shape.** The authority record *is* the site: `ahg_site_record` extends an actor
`UNIQUE (actor_id)` rather than restating it. The legacy table carried `site_name` and
`alternative_name`, which the actor already holds - two copies of a name drift apart and
then neither is trusted. Recorded attributes are rows in `ahg_site_attribute` backed by
`ahg_dropdown`, not JSON, which is also what makes the silent-drop bug impossible to
reproduce.

**Condition assessment is not new code.** ahgConditionPlugin already carries a
data-driven template system keyed by `material_type`, with photos and Spectrum
reporting, so the rock art panel assessment ships as seed data - one template, four
sections, 28 fields. Nothing in that plugin was modified. The codebase already holds two
overlapping condition schemas; a third would have compounded that.

**Locality is the point of the plugin.** Precise coordinates are what enable looting and
vandalism at a rock art site. RARI's current instance handles that bluntly, by commenting
the field out so nobody sees it, staff included. Here nothing reads a coordinate
directly: `SiteRecordService::present()` removes the raw properties and substitutes a
resolved structure, so a template cannot print what is not there. Exact position requires
editor or administrator - deliberately not "any authenticated user", since most RARI
accounts are self-registered researchers. Everyone else receives coordinates rounded to
roughly 11 km, with map sheet, original locality text and altitude withheld outright,
because text cannot be coarsened the way a number can. A record whose sensitivity was
never set counts as sensitive. Rounding rather than offsetting, so repeated reads cannot
be averaged back to the true point. No session at all - CLI, jobs - counts as no
clearance, so a scripted export coarsens by default.

The reason it is a service rather than template code: the equivalent logic already
existed inline in exactly one display template, opt-in per call site, with no shared
resolver. That leaks nothing today only because custom fields have no export or API
surface. It also means there is no enforcement to inherit, so the first person to write
an export has to remember the rule.

**Verified on rari-dev against real RARI data.** Anonymous sees a generalised position
and an "approximate" badge; administrator sees the exact position, map sheet, altitude
and a clearance note. Schema installs idempotently, seeds re-run without duplicating,
foreign keys cascade, one record per actor is enforced, a GET on the delete route returns
404 and deletes nothing, and the plugin packages without the inline-style escape hatch.
31 locality assertions run as a plain PHP script with no framework.

## Two things found during the build

**The panel rendered nowhere.** `display_panels` is only collected by an AHG theme or
ahgDisplayPlugin, and rari-dev has neither. The plugin now contributes its own
`response.filter_content` injector and stands down when either is present, which also
keeps it sellable on its own.

**A wrong call hidden by a catch.** `DropdownService::resolveLabel()` takes
`(table, column, code)`; the taxonomy variant is `resolveLabelForTaxonomy()`. Calling the
wrong one threw, and the surrounding catch turned that into labels rendering as raw
codes - which reads as a missing vocabulary rather than a wrong call.

## Error log stopped recording 404s (v3.99.31, v3.99.32)

`ahg_error_log` was recording every crawler probe as an error and raising an alert email
for each. `ahg_error_log` has two independent writers, and the first fix went to the
wrong one: 404s arrive through `set_exception_handler`, not the
`application.throw_exception` listener. The `status_code` column distinguishes them - the
handler path leaves it NULL. Both now skip `sfError404Exception`, matching the policy the
shutdown handler already applied to 4xx.

## Blank 200 on format probes (v3.99.33)

Chasing that turned up a live defect: every non-HTML format on a museum record returned
HTTP 200 with a zero-byte body. The format guard was refusing symfony's own 404 page -
`error_404_module` is `admin`, which ships no non-HTML template - and throwing from
inside the output buffer above the renderer, discarding it. The guard was destroying the
404 it existed to produce. It now never refuses symfony's internal error, secure, login
and disabled actions, matched on module and action so other admin routes keep their
formats, and refuses at most once per request.

## White screen on a non-symlink install (v3.100.0)

`/security/approvers/add` on rari-dev returned HTTP 200 with no body.
`ahgAccessRequestPlugin` built a require path from
`sf_root_dir . '/atom-ahg-plugins/...'`, which resolves only in the symlink layout PSIS
happens to use; installed as a real directory the require fails after headers are sent,
so there is no error page and nothing in the log. Fixed to a path relative to the file,
plus a resolver for the framework service, which lives in `atom-framework/` on PSIS and
inside `ahgRuntimePlugin` on rari-dev.

**This pattern remains in roughly 113 places across 20 plugins** - worst are
ahgMarketplacePlugin (57) and ahgLibraryPlugin (23). Each is a white screen waiting on
any non-symlink install, and each fails silently. Not yet swept.

## Menu entries that rendered nowhere (v3.100.0)

Researchers and Pending Requests were registered in the `user` nav group, and only
`manage` and `browse` render without ahgThemeB5Plugin - so on a stock-theme instance
neither appeared anywhere and the researcher registry was reachable only by typing its
URL. Both moved to `manage`. Gating is unchanged: Researchers stays administrator-only,
and Pending Requests keeps its closure allowing administrators or a designated approver,
rather than a credentials list that would have cut out the non-admin approver the queue
exists for. The personal "My Workspace" and "My Access Requests" entries stay in `user`.

Note for PSIS: its theme renders both groups, so these two items move from the user
dropdown into Manage there.

## Issue filed

**#300** - on PSIS, object 553 returns HTTP 403 for its HTML view but 200 with full
metadata for `;ead` and `;dc` export. Twelve other sampled records enforce correctly and
`ahg_io_security` is empty, so it is neither a general bypass nor classification.
`sfEadPlugin` and `sfDcPlugin` carry no `security.yml` and no imperative ACL check.

## Also

22 non-Wits RARI accounts migrated to approved researchers; Wits and AHG accounts left as
staff. AtoM never captured real names for them, so names were derived from username and,
where that gave nothing, from the email local part - guarded against role addresses,
which would otherwise have produced "Com Jca" from `com.jca@`. Six accounts keep their
handle rather than an invented name. `search:populate` on rari-dev indexed 309,877
documents in 81 minutes.
