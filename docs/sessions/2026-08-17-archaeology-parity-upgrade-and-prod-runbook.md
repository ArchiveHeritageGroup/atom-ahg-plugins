# Archaeology brought to parity with RARI, three defects found, production runbook written

**Date:** 2026-08-17
**Releases:** atom-ahg-plugins v3.103.5 / v3.103.6 / v3.103.7, atom-framework v2.18.2
**Instances:** archaeology development (112, `/usr/share/nginx/archeology`), RARI development (192.168.0.133)

## Why

Both instances are development copies. The production server for archaeology now exists,
so the development instance was brought to a fully working state deliberately, as a
rehearsal, on the principle that whatever breaks here is what would break on production.
Requirement from Johan: everything on RARI must be on archaeology, all the same plugins,
and every zip must be installable on the new production server.

## The upgrade

Archaeology was 186 plugin commits and 54 framework commits behind: plugins v3.88.39 to
v3.103.4, framework v2.13.59 to v2.18.1.

Only two plugins were actually missing - `ahgRuntimePlugin` and `ahgSiteRecordPlugin`.
The other ten RARI plugins were already present. But they could not simply be dropped in:
`ahgSiteRecordPlugin` calls `AhgNav`, which does not exist in v3.88.39, and is guarded by
`class_exists()` - so it would have installed, run, and silently had no menu entry.

Before touching anything, the 86 locally modified files were checked rather than assumed.
They were **not** archaeology-specific work: they were an intermediate mirror of upstream
left by the auto-mirror revoked on 2026-08-06. Compared file by file against current
PSIS, 13 were identical, 25 differed, 2 were absent - and every path unique to archaeology
(the IoFormHelper move, the ahgIoForm module, io-form.js) exists upstream in newer form.
A clean checkout therefore lost nothing. That check is the whole reason the upgrade was
safe to do.

Backups: both code trees, a 4.4M database dump, and the three untracked files moved aside
rather than deleted, all under `/var/backups/archeology-preupgrade-20260817/`.

`ahgRuntimePlugin` is not in the repository - it is generated from the framework by
`bin/build-runtime-plugin`, which is how the framework ships for installs that want
everything shaped as ordinary plugins. Built there at 2.18.1, 487 PHP files.

## Three defects, all of the same shape

Every one was a guard that turned a fault into silence.

**1. The schema installer created nothing and reported success.**

`--plugin=ahgSiteRecordPlugin` resolved to `<atom root>/ahgSiteRecordPlugin`, which does
not exist, because the script assumed it was running from inside a shipped plugin where
`dirname(__DIR__)` twice lands on `plugins/`. Run from `atom-framework/bin/` it does not.
A missing `install.sql` then hit `continue`, and the run printed "Schema loaded."

This was masked further: `ahg_dropdown` appeared to have been created, but its
`create_time` was 28 June - it pre-existed. Both runs had done nothing at all.

Fixed in framework v2.18.2. Candidate roots are searched, a named plugin that matches
none is a hard error listing every path tried, a missing schema file sets a failure exit
rather than skipping quietly, and a run that applies nothing exits 2 with "Nothing was
applied". The zip layout was never affected - shipped inside a plugin the original logic
was correct - and the bundled copy was verified to carry both fixes.

**2. The site record panel suppressed itself for a renderer that never renders it.**

`SiteRecordPanelInjector` stood down whenever `ahgThemeB5Plugin` or `ahgDisplayPlugin`
was **enabled**, assuming one of them would draw the panel from its extension.json
declaration. The theme renders display panels only in
`sfIsadPlugin/templates/indexSuccess.php` - the information object view. Nothing renders
panels on the ISAAR actor view, which is where a site record panel belongs.

The enabled-plugin heuristic was removed. The marker test that follows it answers the
same question exactly: if the panel is already in the response, do not add it again.

**3. Nothing linked to the add route.**

`/site-record/add/:actorId` had existed since the plugin shipped with no link anywhere.
The panel renders nothing when an authority record has no site record - correct, since
most are not sites - so on an instance with no site records yet there was no path to the
first one short of typing the URL with an actor id. The panel now renders an "Add site
record" button for editors and administrators, and still nothing for everyone else.

That fix needed a second correction: it first tested `$sf_user`, which only exists when a
template renders through the view layer. This partial is included from
`response.filter_content`, where it is not defined - the same reason the file already
loads `esc_specialchars` by hand. The user now comes from the context.

## Corrections made during the work

Worth recording because both were wrong turns taken confidently.

The missing panel on archaeology was **not** caused by the injector bug. The panel
correctly hides when an actor has no site record, and archaeology had none. The injector
defect was real but latent - it would have hidden the panel once records existed.

The `/uploads/r/` media exposure noted in memory as still open on archaeology is not
reproducible there: both a draft and a published master return 404 anonymously while
existing on disk, so the nginx block is in place. Testing only the draft would have
proved nothing - the published control is what makes the result meaningful.

## Registration

Requirement: both instances offer "Register as a researcher" and an ordinary account, and
an ordinary user has the same rights, only without the researcher look and feel.

RARI's login page offered only the researcher route. `LoginRegisterLinkInjector` offers a
link only when a **named** route exists, and `user_register` is registered by
ahgUserRegistrationPlugin, which RARI does not have - while ahgCorePlugin's own
`/user/register` works, served through the default module/action route with no name. It
now falls back to that, and only when the named route is absent, so archaeology still
shows one link pointing at `/register`.

On rights, the requirement already held: neither path writes an ACL group, so both users
hold only the implicit AUTHENTICATED role. An explicit group 99 row must never be added -
`QubitUser::getAclGroups()` prepends it already and the duplicate makes Zend's ACL
registry throw on the second registration.

One difference remains and is a decision, not a defect: `/user/register` activates
immediately with no verification, `/research/register-researcher` creates an inactive
account pending approval, and ahgUserRegistrationPlugin's `/register` does email
verification plus an approval queue. Where the ordinary route is unverified it becomes
the path of least resistance. Installing ahgUserRegistrationPlugin on RARI would make
both instances consistent.

## Verified end state

Archaeology, signed in as an administrator: browse, login, actor and `/site-record` all
200, no errors in `ahg_error_log` or php-fpm, "Add site record" leads to a working form at
`/site-record/add/924`. Anonymously: nothing of the panel is visible, and `/site-record`
serves the login page.

RARI, signed in: the Back to Record button on the site record view and edit screens lands
on the authority record, and the coordinate datum label and hint render - 12 of 12 checks
passing.

## Output

`docs/Archaeology_Production_Install_Runbook.md`, written from the run rather than from
intention, including the two items that need a decision before go-live: which
ordinary-account route production uses, and the `/uploads/r/` nginx block, which lives
outside git and will not exist on a fresh server unless deliberately applied.
