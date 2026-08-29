# A pull that returned zero and did nothing

Date: 2026-08-29
Instances: instance B (deployed, behind a proxy), plus its development tree
Releases: none - deployment and cleanup only

## What was done

The upload round-trip test data was removed from instance B: one researcher
(id 8), one project, one project resource, and the 193-byte PDF behind it. The
`uploads/research/project-documents/` directory and researchers 1-7 were left
in place. The file was deleted before the rows, while the row still named its
path; the other order leaves an unreferenced file that nothing points at, which
is invisible and accumulates. All three deletes ran in one transaction.

Both trees were then brought to v3.106.49.

## The pull that silently did nothing

`git fetch && git reset --hard origin/main`, run as `www-data` on the local
tree, left it on v3.106.42. The command looked like it had worked.

`www-data` has no GitHub key on the build host. The *fetch* fails with
`Permission denied (publickey)`; the *reset* then succeeds against the stale
local ref and exits 0. Only the fetch's stderr says anything is wrong, and it
scrolls past.

Re-running the fetch as root, which holds the key, moved the tag. The tree then
needs `chown -R www-data:www-data`, or `ProtectSystem=full` leaves php-fpm
unable to write what root just created.

**Print the tag before and after every pull.** A zero exit from `reset --hard`
says nothing about whether the remote was ever reached.

## A cleanliness check whose answer depends on who runs it

`bin/deploy-check` reported `atom-framework(1 uncommitted)` against a tree that
was clean by three separate measures: `git status --short` empty, no diff
against `origin/main`, nothing untracked.

Both readings were correct. deploy-check runs as `www-data`; the verification
ran as root. Root's global gitconfig sets a `core.excludesFile` that ignores
`.claude/`, so `.claude/settings.local.json` is untracked-and-dirty for one
user and invisible to the other:

```
git status --porcelain as www-data -> 1   ?? .claude/settings.local.json
git status --porcelain as root     -> 0
```

Nothing had drifted. Worth adding `.claude/` to the repo `.gitignore` so the
answer stops depending on who asks.

## Enablement is not code

Pulling the plugins repo onto instance B brings 120 plugin directories onto
disk. The instance loads 43, of which 32 are `ahg*`, from the serialized
`setting_i18n` id=1 row. A `git reset` cannot write a database row, and the
newest symlink in `plugins/` still dates from 2026-08-24 - five days before the
pull - so nothing was enabled by deploying. The other 88 plugin directories sit
dormant and load nothing.

## Verified

Both instances on plugins v3.106.49, framework v2.18.24.
Homepage 200, `ahg_error_log` 0 rows, research tables empty, researchers 1-7
intact, upload directory retained as `www-data:www-data 755`. The runtime plugin
needed no rebuild: the .48-to-.49 span changed one file, a session log, and no
PHP at all.

The development tree is not served - the vhost in front of it is a
`proxy_pass` to the deployed host - so updating it changed nothing user-facing
and only keeps the development copy in step.
