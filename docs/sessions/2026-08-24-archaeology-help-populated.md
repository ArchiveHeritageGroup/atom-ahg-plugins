# Help centre populated on archaeology, and the stock-instance CLI gap

Date: 2026-08-24
Instance: archaeology.theahg.co.za (VM 192.168.0.131)
Release: atom-ahg-plugins v3.106.15

## Starting point

`ahgHelpPlugin` was enabled and `/help` rendered, but `help_article` held ZERO
rows - a working help centre with nothing in it. Two causes.

## 1. The importer did not know archaeology existed

No `archaeology`, `harris` or `stratigraph` key in either `$subcategoryMap` or
`$pluginMap`, so the new guide would have imported unattributed. Added:

    'archaeology' => 'ahgArchaeologyPlugin',                        // $pluginMap
    'Archaeology' => ['archaeology-', 'harris-', 'stratigraph'],    // $subcategoryMap

Matching is FIRST-SUBSTRING-WINS, so placement matters. Rather than reason about
it, all 352 docs were classified before and after: exactly two lines moved, both
intended, no collateral reclassification.

Technical docs need no map entry - `detectPlugin()` derives the plugin from an
`ahgXxxPlugin.md` filename directly.

Note `detectCategory` + a deliberate "promote subcategory to category for user
guides" step means the guide lands as `cat=Archaeology`, not `User Guide` with an
Archaeology subcategory. That is existing convention, not a bug.

## 2. The docs source was not on the VM

`help:import` reads `<root>/atom-extensions-catalog/docs`, absent on .131. Cloned
the public repo there (203 top-level + 151 technical docs), owned by www-data.

## 3. The task could not be run at all - the real finding

**`php symfony help:import` does not exist on archaeology.** Nor does any other
AHG task: `php symfony` lists base namespaces only. `ProjectConfiguration::setup()`
on a stock instance enables a hardcoded base plugin list, and AtoM discovers CLI
tasks at PROJECT level, so all 30 enabled AHG plugins are invisible to the CLI
while working perfectly in the web app. Patching that file is not available -
zero base-file changes is the premise of this instance.

Worked around by invoking the task class directly through a runner
(`/var/tmp/help-task-runner.php` on .131) that boots the application
configuration and connects a `command.log` listener. Both parts are load-bearing:
without the listener a symfony task driven this way runs to completion and prints
NOTHING, indistinguishable from finding no data.

This is a product gap, not a one-off - archaeology and RARI both. Every plugin
shipping a CLI task is affected on every stock deployment. The runner is a
workaround in /var/tmp, not a fix.

## Result

    349 articles, 9,107 sections, index rebuilt (349, 0 errors)
    Archaeology category on the landing page
    /help/article/archaeology-user-guide renders

## Reconciling 350 imported against 349 rows

The counts did not match, and the difference was real:

- 352 files, 2 skipped (both zero-byte: `request-to-publish-user-guide.md` and
  `MEETING-NOTES.md`) = 350 imported.
- `FUNCTIONS.md` and `functions.md` both slugify to `functions` - two genuinely
  different documents (83 KB "Complete Function Reference", 61 KB "PSIS Function
  Reference & Link Test"), one row. 351 distinct slugs from 352 files.
- 351 distinct - 2 skipped = 349 rows. Closes exactly.

The larger document currently survives, but that is incidental to processing
order rather than a decision. On a case-insensitive filesystem these would be one
file. `request-to-publish-user-guide.md` being empty is a genuine content gap - a
user guide that exists as a filename and nothing else.

## Not a bug: Plugin Reference 404s

`/help/article/<plugin>` returns 404 anonymously for every plugin-reference
article, on PSIS as well as archaeology. `HelpArticleService::ADMIN_CATEGORIES =
['Technical', 'Plugin Reference']` and `applyAdminFilter()` hides them from
non-admins. Correct by design; the anonymous probe was the wrong instrument.
`applyPluginFilter()` additionally hides help for plugins that are not enabled -
verified working here, since `atom_plugin` on archaeology carries 37 enabled rows
including `ahgArchaeologyPlugin`.
