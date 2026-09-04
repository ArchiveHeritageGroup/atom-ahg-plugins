# HTTPS remote and CI for the plugins repository

**Date:** 4 September 2026
**Releases:** plugins v3.106.87; framework v2.18.37, v2.18.38

## CI

`.github/workflows/tests.yml` now runs the three ahgPrivacyPlugin suites on push,
pull request and manual dispatch: PII scoring (68 assertions), the redaction filter
(12) and visual redaction coordinates (9).

They need no database, no Symfony bootstrap, no composer install and no fixtures -
each file requires the class under test and nothing else. That is the whole reason
they can run in CI at all, and it is not accidental: the decisions they cover were
deliberately extracted as pure statics so they could be tested without a harness
this repository does not have.

None of the steps carries `|| true`. A failing assertion fails the build. Worth
noting because the framework's neighbouring lint step does carry it, so that job
cannot fail however broken the code is.

## The remote

Switched from SSH with a per-repository deploy key to HTTPS with the GitHub CLI as
a per-repository credential helper:

    git config --local remote.origin.url https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git
    git config --local credential.helper '!gh auth git-credential'

Unlike atom-framework, this repository never needed it. Its deploy key pushed
`tests.yml` without complaint, while the identical kind of change to
atom-framework's workflow directory was refused with "refusing to allow an OAuth App
to create or update workflow ... without `workflow` scope". Why the two behave
differently is not visible from this host, and no explanation is offered here
because the first one given was wrong.

The switch was made for consistency, so `bin/release` behaves identically in both
repositories rather than working in one and failing in the other depending on which
files a release happens to touch.

`core.sshcommand` is left in place. It is inert while the URL is HTTPS and makes
reverting a one-line change.

## Consequence

Pushes now authenticate as the user's GitHub account rather than a repository-scoped
deploy key. That is a wider credential, and `bin/release` will now also create
GitHub Releases, since its `gh release create` step only ran when `gh auth status`
succeeded and that had not been the case for this user before today.
