# Standalone plugin installs, proven on a bare machine

**Date:** 2026-08-18
**Releases:** atom-ahg-plugins v3.103.10 to v3.103.14, atom-framework v2.18.3 and v2.18.4
**Machine:** atom210 (192.168.0.131), rebuilt twice from nothing

## Why this ran

External users are told the AHG plugins install on a stock AtoM. That claim had never been
tested on a machine that did not already have everything. The archaeology production
install failed step after step, and the "rehearsal" that was supposed to prevent it had run
on an instance carrying composer, the PHP extension set and a populated `vendor/`
directory. It passed and told us nothing.

## What a real clean room found

atom210 was rebuilt from the Ubuntu 24.04 cloud image with no PHP, no composer, no MySQL,
no nginx and no third-party apt repositories, then taken through the official AtoM 2.10
installation and the AHG layer.

**`php8.3-gd` is required by the framework and is absent from AtoM's documented package
list.** On a machine installed exactly per the AtoM instructions, `composer install` in
atom-framework stops with "the requested PHP extension gd is missing" and the AHG install
goes no further. This is the single most common cause of a failed install and it is one
apt line.

## The finding that mattered

`install.sql` had stopped being the schema.

**86 SQL files across 30 plugins were never run by any installer.** Each was a feature
added after the plugin first shipped, applied by hand to PSIS or a development box and
never folded back in. Nothing ever failed, because those machines already had the tables.

The manifests had drifted at the same rate, which is why nothing caught it:

| Plugin | Declared in extension.json | Created by its SQL |
|---|---|---|
| ahgLibraryPlugin | 9 | 92 |
| ahgMuseumPlugin | 1 | 36 |
| ahgIiifPlugin | 0 | 26 |
| ahgResearchPlugin | 76 | 162 |
| ahgRequestToPublishPlugin | 1 | 4 |

The installer's verification step - added precisely so a half install could not report
success - was checking against a list that was itself incomplete. It confirmed one table
of four and printed "verified all 1 declared tables exist".

## Three real bugs, all of the same shape

- **`rtp_workflow`** - the request-to-publish inbox returned HTTP 500 on any fresh install.
  The table lives in `database/workflow.sql`, which the installer never read.
- **`level_of_description_sector`** - queried by five plugins, written by ahgSettingsPlugin,
  created by no `install.sql` anywhere in the repository. Pages returned 200 while sector
  level lists silently fell back to empty and the error went to nginx, where nobody looks.
- **`ahg_audit_chain_state`** - same class, in a stray file.

## What was done

Every plugin now has exactly one `install.sql` and a manifest generated from it.

| | Before | After |
|---|---|---|
| Stray SQL files never run | 86 | 0 |
| Plugins with schema outside install.sql | 30 | 0 |
| Tables declared across all manifests | ~450 | 1,007 |

Unguarded `INSERT`s were rewritten to `INSERT IGNORE` during the merge, including a 387-row
CCO taxonomy seed, so `install.sql` stays safe to re-run. Seed directories were merged too:
ahgSiteRecordPlugin's `rock_art_panel_template.sql` was the reason the condition suite could
not find its template.

## A mistake worth recording

Regenerating manifests from `CREATE TABLE` statements initially claimed **base AtoM tables**
- `acl_group`, `acl_group_i18n`, `acl_user_group`, `acl_permission` - because
ahgSecurityClearancePlugin creates them defensively with `IF NOT EXISTS`. A drop-and-rebuild
test then deleted AtoM's ACL data and every page died with
`addRole() expects $role to be of type Zend_Acl_Role_Interface`.

A manifest is read as "tables this plugin owns", and tooling acts on it. The generator now
excludes core tables explicitly; the sweep later caught the same claim in ahgSearchPlugin
and ahgRightsHolderManagePlugin.

## CLI tasks do not exist on a stock AtoM

With base AtoM unmodified, `php symfony` sees no AHG tasks at all. Stock
`config/ProjectConfiguration.class.php` builds a hardcoded plugin array and calls
`enablePlugins()` on it; it contains **zero** references to the `plugins` setting, which is
read later by the application configuration at web-request time. Symfony discovers tasks at
project level, so plugin tasks are invisible.

The README told users to run `php symfony display:auto-detect` and
`php symfony ahg:refresh-facet-cache` after installing, and claimed browse would render
empty without them. Both produce no output whatsoever on a stock install. Corrected -
plugin management is `php atom-framework/bin/atom`.

## Installer changes (framework v2.18.3, v2.18.4)

- `--socket` and an empty `--host` for socket-only MySQL, plus a 10 second connect timeout.
  On RARI the installer connected far enough to appear in `information_schema.processlist`
  and then hung with no output at all.
- **Refuses** to install when a declared dependency is absent or only partly installed,
  naming it: `ahgCorePlugin (schema incomplete: 3 of 5 tables absent, e.g. ahg_dropdown)`.
  `--force` overrides. Requiring every declared table, not merely one, was the second
  attempt; the first was too lenient to catch anything.
- A plugin with no schema and no declared tables now says `no schema - nothing to install`
  rather than failing.

## Verified end state

Bare Ubuntu 24.04 to a working site, unattended, exit 0. All 253 tables of the standalone
set dropped and rebuilt from zero, every plugin verifying its complete declared set.

| Suite | Result |
|---|---|
| Provenance | 17/17 |
| Cart | 12/12 |
| Condition | 20/20 |
| Researcher | 15/15 |
| Request to publish | 13/14 |

The one remaining failure expects request rows that an empty archive does not have. Every
failure seen during the day traced to a missing table, an un-run seed, or a test invocation
missing its environment - none to a code defect.

The researcher journey is verified properly: registration reachable anonymously, the account
inert until approved, approval by an administrator, login succeeding afterwards, and an
approved researcher seeing neither the map sheet nor the raw locality that an administrator
sees.

## Two claims of mine the clean room disproved

- **`ProtectSystem=full`** is not universal. Stock Ubuntu 24.04 ships `ProtectSystem=no`.
  It is specific to how server 112 is configured, and it was documented as general.
- **`--prefer-dist`** is not required. Composer produced 53M with zero `.git` directories
  when not throttled; the 548M seen on production came from GitHub HTTP 429 responses
  forcing source clones.

Both were stated with more confidence than the evidence supported, in a runbook whose
opening line claimed it was "written from an actual run". It was written from a run that
skipped every step that later failed. Those documents now carry banners naming what they
got wrong.

## Standing distinction

**19 plugins are tested** - installed from bare metal and exercised end to end.
**All plugins are installable** - schema verified onto a live database. That is not the
same claim, and should not be made as though it were.
