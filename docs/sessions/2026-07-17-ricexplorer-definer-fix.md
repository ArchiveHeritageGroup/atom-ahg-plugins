# ahgRicExplorerPlugin - views to SQL SECURITY INVOKER (v3.79.98)

**Date:** 2026-07-17
**Repo:** atom-ahg-plugins
**Release:** v3.79.98
**Trigger:** enabling every AHG plugin (except ahgFederationPlugin) on the fresh
AtoM 2.10 VM `atom210` - RicExplorer was the only plugin that would not install.

## Problem

`ahgRicExplorerPlugin/database/install.sql` was committed as a **mysqldump**
containing 3 views (`ric_queue_status`, `ric_recent_operations`,
`ric_sync_summary`) with hardcoded:

```
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
```

`extension:install` runs plugin SQL under the application DB user (`atom`), not
root. Creating a view with `DEFINER=root` requires the `SYSTEM_USER` privilege,
so it fails:

```
SQLSTATE[42000]: 1227 Access denied; you need (at least one of) the
SYSTEM_USER privilege(s) for this operation
```

It only "worked" when piped as the MySQL root user - which is not how the
framework installs plugins. So RicExplorer was broken on every standard
(non-root) install.

## Fix

The 3 `DEFINER=\`root\`@\`localhost\` SQL SECURITY DEFINER` clauses changed to
`SQL SECURITY INVOKER` (no DEFINER). The views then create under the invoking
app user with no special privilege, and run with the querying user's rights -
correct for read-only reporting views.

## Gotcha (operational)

Running a mysqldump-style install.sql **as root** first creates root-owned
views; the app user then cannot `DROP VIEW IF EXISTS` / replace them (same 1227,
because touching a root-owned object needs SYSTEM_USER). Recovery on the VM:
drop the root-owned views as root, then re-run `extension:install` as the app
user - clean.

## Result

- RicExplorer installs + enables cleanly as the app user.
- VM atom210: **110 of 111 plugins enabled** (all except ahgFederationPlugin),
  site healthy (no 500s).
