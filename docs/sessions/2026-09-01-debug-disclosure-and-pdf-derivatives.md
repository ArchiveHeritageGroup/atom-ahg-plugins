# Debug-mode disclosure, a committed credential, and the PDF derivative chain

**Date:** 1 September 2026
**Instance:** a client production instance
**Releases:** `atom-ahg-plugins` v3.106.63 (+ two changes staged, unreleased)

Started as "a museum record does not show when logged out". Ended in three
security findings and one root cause that had been latent since July.

## 1. Production was serving its whole config on every 404

`index.php` had `getApplicationConfiguration('qubit', 'prod', true)` - debug on.
Any anonymous request to a nonexistent URL rendered the Symfony exception page,
which dumps the entire `sfConfig` array: absolute paths, `sf_csrf_secret`,
third-party client secrets, and the password hashing parameters. No login needed.

`curl http://<host>/index.php/anything-that-does-not-exist` was the whole exploit.

Fixed by setting the third argument to `false` and reloading php-fpm (opcache
holds the old file otherwise). Verified externally: themed 404, zero occurrences
of `sf_csrf_secret` or `sf_app_cache_dir`.

PSIS was already correct, with `qubit_dev.php` separately on `'dev', true`. This
drifts per box - check `grep -n getApplicationConfiguration <root>/index.php` on
every instance.

**Still outstanding:** both exposed secrets need rotating. The flag fix stops the
leak; it does not undo it. `index.php` was dated November 2025.

## 2. A credential committed to a tracked config file

A live third-party client secret was found committed in plaintext to a tracked
`config/app.yml`. Details are deliberately not recorded here - see the internal
incident note. Removing the line does not remediate it; the credential must be
revoked and reissued, then sourced from `sfConfig`/`ahg_settings`.

## 3. 2045 text masters anonymously downloadable

Upstream `qbAclPlugin` returns true for `readMaster` on any TEXT media object
**before** both the ACL check and the PREMIS granted-rights check
(artefactual/atom#1724). Instances that decline base patches are wide open on
every PDF master, drafts and embargoed records included.

Measured on the affected instance: **2045** text masters (`usage_id=140, media_type_id=137`).

`patches/qbAclPlugin/` closes this, but base patches stay off. The fix instead
goes in `ahgCorePlugin/modules/digitalobject/actions/viewAction.class.php` - that
action is ours, so no base file is touched. It re-tests text masters after
`QubitAcl::check()`, mirroring the patch: off unless `allow_public_text_masters`
is set, failing closed, and able only to deny.

Verified on PSIS (patched, so the gate is a no-op there): master 404 anonymously,
reference 200, no new `ahg_error_log` entries.

## 4. Root cause: a NULL `media_type_id`

The original symptom. Chain, in order of discovery, with four wrong hypotheses
before the data was looked at:

- The record page rendered fine anonymously; the **digital object** 404'd.
- Not missing from disk - `digitalobject/view` was denying and forwarding to 404.
- The viewer linked the **master**; the reference copy had no row.
- `regen-derivatives` printed `Done!` in **0.02s** and produced nothing.
- Not ImageMagick policy (PDF unrestricted), not ghostscript (`gs` present,
  `convert` exit 0), not adapter detection (probe passed, same as PSIS).
- **`digital_object.media_type_id` was NULL.** `createRepresentations()` switches
  on it; no case matched, so it returned instantly having done nothing.

`UPDATE ... SET media_type_id = 137` (TEXT), then regen produced `_141.jpg` and
`_142.jpg` immediately.

**Where the NULL came from:** `TiffPdfMergeJob::attachToRecord()` inserts the
`digital_object` row without `media_type_id`. Every PDF the merge attaches is born
unable to generate derivatives. Fixed by adding `'media_type_id' => 137`.

Note the interaction: correcting the NULL *activated* finding 3 for that record -
the master went from 404 to 200. Correct data and an unpatched base are in direct
conflict, which is why the gate must land before the merge-job fix deploys.

## 5. Also found, not yet actioned

- `TiffPdfMergeService.php:478` inserts into `digital_object` using
  `information_object_id`, `created_at`, `updated_at` - **none of which exist** -
  with `usage_id => 166` (external URI, commented "Master") and
  `media_type_id => 183` ("In progress", not a media type). It cannot succeed
  against this schema. Establish whether anything still calls it.
- The merge job cannot report a partial run. `processed_files` is the output PDF's
  **page count**, all file rows are marked `processed` unconditionally, and
  `completed` is set regardless. Job 346: 532 registered, 317 merged, reported
  complete with `error_message` NULL. The destructive half is already fixed
  (v3.59.15 quarantines instead of unlinking); the count guard is not.
- `qubit_dev.php` present on the instance, returning 502 - unblocked, not absent.

## Lessons

**A check that cannot see the distinction it depends on.** `canThumbnail()` on a
NULL media type, a task reporting `Done!` having done nothing, an availability
probe reading only `$stdout[0]`, a bare `continue` on a missing input. Each turns
a fault into silence.

**Look at the row before theorising about the stack.** Four hypotheses - policy,
ghostscript, adapter, mime type - all plausible, all wrong, all avoidable by
selecting the two columns the code actually branches on.

**0.02s is evidence.** A task that finishes far too quickly did not do the work,
whatever it printed.
