# Enabling an AHG plugin on a stock-ProjectConfiguration instance

**Date:** 2026-08-23
**Applies to:** archaeology (atom210, 192.168.0.131) and, later, RARI
**Standing constraint (Johan, 2026-08-23):** on archaeology and RARI we do **not** change
ANY base AtoM file. `config/ProjectConfiguration.class.php` is a base file. It stays stock.

## The rule

There are two different plugin-enabling mechanisms in this estate, and using the wrong
one silently does nothing.

**Patched instances (archive / PSIS).** `ProjectConfiguration::setup()` calls
`loadPluginsFromDatabase()`, so the **`atom_plugin` table is the source of truth**. Insert
a row with `is_enabled = 1` and the plugin loads.

**Stock instances (archaeology / atom210, and RARI).** `setup()` enables a hardcoded list
of ten base plugins and never reads `atom_plugin`. The rest of the runtime list comes from
the classic AtoM mechanism: the **serialized PHP array in `setting_i18n` id=1, `setting.name = 'plugins'`**.
On atom210 that array holds 38 entries which, with the 10 base ones, give the 48 plugins
`getApplicationConfiguration('qubit','prod',false)->getPlugins()` reports.

So on a stock instance:

- Writing to `atom_plugin` **has no effect at all.** The row exists, `is_enabled` is 1, and
  the plugin still does not load. This is the trap - it looks done.
- The plugin must be appended to the serialized `plugins` setting instead.
- Modify it by `unserialize` -> append -> `serialize`, never by string surgery on the blob.
  A serialized PHP array carries byte-length prefixes (`s:19:"ahgArchaeologyPlugin"`), so a
  textual edit that gets a length wrong corrupts the whole setting and disables every
  plugin at once.
- Back the old value up first. It is one row and it is the entire plugin configuration.

## The second-order consequence: CLI tasks

AtoM discovers CLI tasks from plugins enabled at **project** level. On a stock instance the
AHG plugins are enabled at **application** level, so `php symfony <ahg-namespace>:*` does
not exist even though the plugin runs correctly in the web app. `php symfony list` shows no
AHG namespace.

The fix is **not** to patch ProjectConfiguration. It is a runner in the plugin's own `bin/`
that boots the application configuration and drives the task directly - see
`ahgSiteRecordPlugin/bin/import-coordinates` and, new here,
`ahgArchaeologyPlugin/bin/seed-vocabularies`.

Three traps in writing such a runner, all of which look like success:

1. `getApplicationConfiguration('qubit', $env, **false**)` - the third argument must stay
   false. `true` rebuilds the configuration cache from the CLI and corrupts the cache the
   web app is serving from.
2. The `sfBaseTask` constructor does **not** set `$this->configuration`. Without
   `$task->setConfiguration($cfg)` it dies inside `sfContext::createInstance(null)`.
3. `sfTask::log()` publishes to the `command.log` event, and `sfCommandApplication` normally
   supplies the listener. Driving a task directly there is none, so **the task runs to
   completion and prints nothing** - indistinguishable from a task that found no data.
   Connect a `command.log` listener before the task can log.

## Also true of the task class itself

- `execute()` must be `public` and carry **no return type**. `protected function execute(...): void`
  is signature-incompatible with `sfBaseTask` and fails at load.
- `getApplicationConfiguration()` does not exist on `sfBaseTask`. Bootstrap the query
  builder with `new sfDatabaseManager($this->configuration)`.

## Verified on atom210, 2026-08-23

Plugin files rsynced and byte-verified, symlinked, schema installed (4 tables), row added to
`atom_plugin` with `is_enabled = 1`, cache cleared, php-fpm restarted. Result:
`ahgArchaeologyPlugin: NOT ENABLED`, `service autoloads: NO`. The `atom_plugin` write was
inert, exactly as this rule predicts. The vocabulary seeder nonetheless ran fine through the
`bin/` runner, because that runner boots the application configuration itself and the task
does not reference the plugin namespace.

## Correction to prior memory

The existing note that "`setting_i18n` (id=1) is LEGACY - only used by the sfPluginAdminPlugin
UI" is true **only on patched instances**. On a stock instance it is the live, load-bearing
plugin configuration.
