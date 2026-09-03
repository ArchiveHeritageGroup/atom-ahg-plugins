# Catalogue currency, a silent worker outage, and an inherited entity bug

**Date:** 2 September 2026
**Releases:** plugins v3.106.66, v3.106.67; catalog v1.12.16

## The published catalogue had drifted for six months

Code was never the problem. `PluginFetcher` pulls single plugins straight from
the plugins repository, so what a user installs is current by construction. The
documentation *about* that code is what goes stale, and nothing makes the gap
visible.

| | Before | After |
|---|---|---|
| `catalog.json` plugins | 37 (`updated_at` 14 Feb) | 120 |
| categories | 20 | 46 |
| plugins with a README | 25 / 120 | 119 / 119 |
| `INSTALLATION.md` | 377 lines, 28 Feb | 509 lines |

`ahgCorePlugin` - the one genuinely required plugin - was not catalogued at all.
`catalog.json` is now generated from the `extension.json` manifests, so it cannot
diverge from disk without bypassing the generator.

### Symlinks: right for one install route, wrong for the other

The first reading was that the guide's symlink instructions were wrong. They are
not. The full-stack install uses symlinks and works, because the AHG
`ProjectConfiguration` reads `atom_plugin` and never needs the plugin admin
screen.

The failure is specific to **standalone single-plugin installs on stock AtoM**.
`pluginsAction.class.php:51` tests that a plugin path *begins with*
`sf_plugins_dir`. A symlink resolves to the checkout directory, fails that prefix
test, and the plugin disappears from the stock admin screen - which on a stock
instance is how it gets enabled. It loads correctly; it simply cannot be seen to
be switched on, and nothing reports an error.

The guide now documents both routes. Every generated README carries the
standalone recipe, so a single-plugin installer needs no other document.

## A job worker down for two days, silently

Reported as an EAD import failure. It was not an import fault:
`Net_Gearman_Exception: No Gearman worker available that can handle the job
arFileImportJob`. Gearman was running the whole time, which is why the message
blamed a missing worker rather than a dead one.

1. The worker exited on `SQLSTATE[HY000]: General error: 2006 MySQL server has
   gone away` - a long-running process losing its connection.
2. Every restart then fataled: the routing cache holds serialised custom route
   objects, and those classes are not defined before `unserialize()` runs in the
   CLI context, so they return as `__PHP_Incomplete_Class` and die in
   `setDefaultParameters()`.
3. After three attempts systemd hit `StartLimitBurst` and stopped trying.

**No background job of any kind ran for two days** - imports, derivative
generation, finding aids, CSV exports. Nobody noticed, because a queued job that
never runs looks identical to one still queued.

Fixed with `reset-failed` plus a cache clear; the cache clear is the actual fix.
Verified by ability registration in the worker log, not by the unit reporting
`active`.

It will recur: the worker cannot survive a database timeout and cannot restart
itself afterwards. `Restart=always` with a longer `StartLimitIntervalSec`, or a
healthcheck, is outstanding.

## An HTML entity in a dropdown, inherited from base

`QubitTerm::getIndentedChildTree()` defaults its tree indent to the HTML entity
`'&nbsp;'`, and base AtoM passes it explicitly too. That works only where option
labels render as raw HTML. The Bootstrap 5 theme escapes them, so the entity was
displayed literally in front of every indented container type.

Corrected on the plugin side by passing a real `\u{00A0}`, which survives
`htmlspecialchars` unchanged and renders correctly whether escaped or not. Base
left untouched.

## Lessons

**"Is it up to date?" has two answers.** Fetched code is current by construction;
documentation about it is not. Generate the documentation from the same source
the code comes from, and the question stops needing to be asked.

**A guard can be accurate and still mislead.** A 501 reported that the plugin
rendering `isad` was not installed. True as far as it went - but ISAD has no
module-map entry and falls through to `ioManage`, so the missing piece was a
different plugin's registration entirely.

**Check which list governs before reading one.** An instance has two plugin
lists and only one is authoritative; `grep -c loadPluginsFromDatabase` decides
which, and costs nothing to run first.

**HTTP 200 is not a rendered page.** An add form returned 200 anonymously; it was
the login page. AtoM serves login as 200 at the same URL.
