# Archaeology production install - runbook

**Version:** atom-ahg-plugins v3.103.6, atom-framework v2.18.2
**Rehearsed on:** the archaeology development instance (112, `/usr/share/nginx/archeology`), 17 August 2026
**Status:** every step below was executed on the development instance and verified.

This is written from an actual run, not from intention. Where a step failed during the
rehearsal it is recorded here with the failure, because those are the steps most likely
to fail again.

## What the rehearsal changed

Three defects were found and fixed by doing this once on a real instance. They are
already in the versions named above; the point of listing them is that an older bundle
will still carry them.

| Fix | Symptom if you use an older build |
|---|---|
| Schema installer path resolution | `--plugin=X` from a checkout creates nothing and prints "Schema loaded" |
| Panel injector stood down for a theme that never renders the panel | Site record panel invisible on every authority record |
| Nothing linked to the site record add route | No way to create the first site record from the interface |

## Before starting

- PHP 8.3, MySQL 8, nginx.
- If the codebase lives under `/usr/share/nginx/`, php-fpm's `ProtectSystem=full` makes
  the application unable to write to its own `storage/`, cache or logs from a web
  request. Add the per-app systemd drop-in before anything else - the login page often
  still works without it, which masks the problem until a later page 500s.
- Confirm the database exists and you can connect **as the application user over TCP**,
  not only as root over the socket. The rehearsal tripped here: `mysql -u root` worked
  while the installer could not connect, because the CLI was using socket
  authentication. The installer needs real credentials.

## 1. Base AtoM and the single delta

Install stock AtoM 2.10. The only base file that differs is
`config/ProjectConfiguration.class.php`, which loads plugins from the `atom_plugin`
table. Everything else in `apps/`, `lib/`, `plugins/` and `vendor/` stays untouched.

## 2. Framework

Clone `atom-framework`, then load its schema. This creates `atom_plugin` and
`atom_plugin_menu`.

Do not skip `atom_plugin_menu`. It is declared in the framework's `install.sql` and is
absent on both PSIS and the archaeology development instance, so plugin installs there
emit `Base table or view not found: atom_plugin_menu` and carry on without updating menu
entries. It degrades to a warning rather than an error, which is exactly why it went
unnoticed on two instances.

Set the timezone explicitly. AtoM ships `America/Vancouver`, which skews every stored
timestamp by 9-10 hours. Override on the `context.load_factories` event, not in
`initialize()`.

## 3. Plugins - two supported routes

### From a checkout

```
php atom-framework/bin/atom extension:install <PluginName>
```

`extension:install` finds the plugin under `plugins/` or `atom-ahg-plugins/`, creates the
symlink itself, loads the schema and enables the plugin in one step. It is local-aware -
it does not fetch from GitHub when the directory is already present.

### From a zip

```
php <Plugin>/bin/install-plugin-schema.php --database=DB --user=USER --password=PW
```

Bundles are built with `atom-framework/bin/build-plugin-bundle <PluginName>` and carry
their own copy of the installer under `<Plugin>/bin/`. Verified on
`ahgSiteRecordPlugin-1.0.0.zip`: the shipped installer contains both fixes.

**Never use `mysql < install.sql`.** The client stops at the first error and exits, so
one failing statement abandons every statement after it - measured on
ahgProvenancePlugin, a foreign key that could not resolve aborted the file and left 7 of
its 9 tables created, with no summary and an exit status most people never check.

### Reading the installer's output

It now reports what it did:

```
ahgSiteRecordPlugin
  executed 3, already present 0, unresolved 0, passes 1
  verified all 3 declared tables exist

Schema loaded (1 plugin).
```

`verified all N declared tables exist` is the line that matters. A run that applies
nothing exits 2 with `Nothing was applied`, and a named plugin it cannot find is a hard
error listing every path searched. Neither is possible to mistake for success, which was
not true before 17 August 2026.

## 4. ahgRuntimePlugin is generated, not cloned

It is not in the plugins repository. Build it from the framework:

```
cd atom-framework && bin/build-runtime-plugin
```

This is how the framework installs on an instance that wants everything shaped as
ordinary plugins. Editing the framework is the only way to change it - the generated
plugin is output, never a source to edit.

## 5. After installing

- Check `actor` id 6, the root repository row. It has been missing on new instances and
  its absence surfaces later as unrelated-looking failures.
- Run the search populate, display auto-detect and facet cache refresh.
- Confirm the plugin migrations ran. 52 migrations across 18 plugins were never executed
  on existing instances. `CREATE TABLE IF NOT EXISTS` does not upgrade an existing
  table, so drift is silent - diff `information_schema.columns` against the plugin's
  schema rather than reading the code and assuming.

## 6. Registration

Two registration paths exist and they answer different questions:

- `/research/register-researcher` - account plus a researcher record with reading-room
  entitlements. Creates the account **inactive**, pending approval.
- an ordinary account - `/register` where ahgUserRegistrationPlugin is installed (email
  verification plus an admin approval queue), otherwise ahgCorePlugin's `/user/register`,
  which activates immediately with no verification.

Neither grants an ACL group. Both users hold only the implicit AUTHENTICATED role, so an
ordinary user has the same rights as a researcher and differs only in presentation. Do
not add an explicit group 99 row: `QubitUser::getAclGroups()` already prepends it, and
the duplicate makes Zend's ACL registry throw on the second registration, which is a 500
on every page that user opens.

Decide before go-live which ordinary-account route production should use. Without
ahgUserRegistrationPlugin, self-registration is instant and unverified, while researchers
wait for approval - the ordinary route becomes the path of least resistance.

## 7. Verification

Anonymously:

- browse, login and an authority record all return 200
- a master file of an **unpublished** record is not retrievable; block `/uploads/r/` at
  nginx and confirm with a file that exists on disk. Test a published master too, or a
  404 proves nothing
- staff URLs return the login page. AtoM answers refusals with HTTP 200, so assert on the
  body class, never the status code

Signed in as an administrator:

- no page shows "Oops" or an error
- `ahg_error_log` is quiet

## 8. Things that cost time in the rehearsal

- A module without `modules/<name>/config/security.yml` inherits
  `default: is_secure: false` and is public. The absence of the file is the bug, and it
  is invisible in review.
- `apps/qubit/config/security.yml` also secures actions **by name across every module** -
  `autocomplete` is one. So a plugin action can be protected purely by what it is called,
  and an anonymous probe returning the login page does not prove the module is guarded.
- A guard that turns a fault into silence is worse than the fault. All three defects
  found in this rehearsal were of that shape: a missing schema file skipped quietly, a
  panel suppressed for a renderer that never rendered, a route nothing linked to.
