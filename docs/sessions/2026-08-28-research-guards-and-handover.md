# Optional tables, a colliding autoloader, and where things stand

Date: 2026-08-28
Releases: atom-ahg-plugins v3.106.41 (framework unchanged at v2.18.24)

## Three fixes, three different shapes

**Two optional tables queried unguarded.** `researcher_orcid_credential` and
`ahg_api_key` ship with plugin migrations that had not run on one instance, and
the code queried them anyway - a hard 500 on `/research/orcid` and
`/research/profile/api-keys`. Both now go through
`AhgCore\Core\AhgDb::hasOptionalTable()`, which already existed for exactly this.
The write path on API keys was guarded too: fixing only the read would have moved
the 500 to the first POST rather than removing it.

These failed **only when signed in**. An anonymous request gets the login page at
HTTP 200, so a status-code check reads them as healthy. That is why they survived.

**A colliding composer autoloader.** Five call sites did
`require_once atom-framework/vendor/autoload.php` blind. On an instance running
the generated `ahgRuntimePlugin`, a second copy of that vendor tree is already
registered, built from the same lock file - so the generated
`ComposerAutoloaderInit<hash>` class has the same name in both, and the second
require fatals with "Cannot declare class ... because the name is already in
use". `require_once` cannot prevent it: the file paths differ, only the class
inside collides. Every journal PDF and DOCX export died on those instances while
working fine elsewhere. Replaced with a guard that asks whether the class is
*reachable* and loads nothing when the runtime plugin already provided it.

**A dimension field with no validation.** Values went to the database raw. The
same field therefore behaved three ways: rejected with a 500 under MySQL strict
mode, silently coerced to `0.00` without it, and on an instance lacking
`physical_object_extended` entirely, accepted and **discarded** - the form
reports success and stores nothing. `numericOrNull()` now applies to eleven
numeric columns.

## What the verification taught

Testing on the instance where the bug cannot occur proves nothing. Two of these
three could not fail on the instance with both tables and no runtime plugin, so a
clean result there meant "no regression", not "fixed". Confirmation had to come
from the instance that was actually broken: all four routes 200, error log empty.

The guard itself was wrong first. The namespace was guessed as
`ahgCorePlugin\Core\AhgDb` when it is `AhgCore\Core\AhgDb` - and the pages
returned **200 while logging "Class not found"**, so a status check would have
called it fixed. The correct form was already in the same file, a few thousand
lines down. Then a `sed` correction silently failed to apply and nearly got
reported as done.

## Housekeeping

Nineteen crawler test artefacts removed - 13 terms, 5 static pages, 1 physical
object - inside a transaction, with the two access-point relations they held.
The terms were nested-set nodes, so the tree was rebuilt afterwards: max `rgt`
808 to 782, closing the 26 gaps, 0 integrity violations. Deleting nested-set rows
without rebuilding leaves a tree that traverses but miscounts.

## Still open

- Two base-AtoM security issues remain unfixed upstream in 2.10.2 (an XXE among
  them), so any move to a stock base reopens them.
- One instance carries 29 changed base files against an authorised four. The
  mechanism that did it is a documented installer flag, and it will do it again
  while it exists.
- A contact-information lock timeout that has never once succeeded in creating a
  record. Undiagnosable without the `PROCESS` grant on the application database
  user.
- A form field that accepts input and stores it nowhere is invisible from the
  browser. Only the schema shows it, which means UI testing alone cannot find
  that class of fault.
