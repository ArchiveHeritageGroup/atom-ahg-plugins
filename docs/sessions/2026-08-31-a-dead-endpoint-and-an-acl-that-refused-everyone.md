# A dead endpoint, an ACL that refused everyone, and a fix that broke what it fixed

Date: 2026-08-31
Releases: atom-framework v2.18.29, v2.18.30, v2.18.31; atom-ahg-plugins v3.106.55

## An access-control service that denied every anonymous visitor

A published record rendered at its own slug and returned 403 at a plugin
module's route for the same record. Nothing in the access data explained it:
anonymous held a blanket read grant, there were no per-object rules, the
classification tables were empty, and the record carried a single publication
status row saying published.

The framework's own `AclService::check()` did this:

```php
if ($sfUser && $sfUser->isAuthenticated() && isset($sfUser->user)) { $user = ...; }
if (!$user) {
    return false;          // anonymous always landed here
}
```

No authenticated user means ANONYMOUS, not "denied". The anonymous group's own
grants were never consulted. **92 files call that method**, so every module built
on it refused the public. Pages appeared to work only because base AtoM's own ACL
handles anonymous separately - which is exactly why one route worked and another
did not for the same record.

A second defect sat beside it. `grant_deny` is stored 0 = deny, 1 = grant. The
constant used for the comparison is 2. `$perm->grant_deny == self::GRANT` could
never be true, so every explicit permission row read as a denial for anyone who
was not an administrator, editor or contributor. Found by checking the constant
against the stored rows rather than by reading it: every row in the table was 1.

## The fix shipped a regression, and the error log caught it

`check()` accepts its action as a string OR an array, and the array branch runs
LATER in the method than the point where the anonymous call was inserted. So an
array reached a string-typed parameter and threw on live record views. Two real
page views hit it before it was noticed.

It was noticed only because an unexpected 404 during unrelated testing prompted a
look at the error log, where the two 500s underneath it were sitting. The 404 was
a red herring; the errors were the finding.

**When inserting an early return into an existing method, read what the code
after the insertion point does with the same arguments.** A guard placed above a
normalising branch never sees the normalisation.

## Every QR code in the system was a broken image

The feature already existed. Two call sites generated QR images from a hosted
chart endpoint that has since been deprecated and now returns 404, so every code
rendered as a broken image - silently, for as long as that service has been off.
The same approach sent the URL of every record to a third party on each render
and could never work in the offline export product. One site also fell back to a
hardcoded hostname, so labels could point at a different institution entirely.

Replaced with an encoder in the framework: QR model 2, byte mode, error
correction level M, versions 1 to 10, SVG and PNG.

**Written rather than installed, for a specific reason.** `vendor/` is gitignored
in that repository, so a package dependency does not arrive with a pull - every
instance would need a separate install step, and one that skipped it would fatal
at the point of use while looking perfectly deployed. A self-contained encoder in
`src/` travels with the framework.

**And only defensible because the output could be decoded.** A decoder on the
host read six generated codes, from 14 to 198 bytes including UTF-8, back to
their exact input. A hand-written Reed-Solomon implementation that has not been
decoded is a guess, and the entire purpose of a QR code is that a stranger's
phone can read it.

## A published catalogue that was 99.5% invisible

On another instance, 637 of 640 published records returned "you do not have
permission". Access control was innocent throughout. The records carried a
display standard whose renderer does not resolve on that version, and the
resulting page is the same one a genuinely secured page produces.

**The discriminator, in one request:** ask the module for a nonexistent slug. A
404 means the module is enabled and its action ran. A 403 means the module is
disabled or the action is secured. The status code alone cannot separate them.

## Also fixed

Digital object writes now refuse when the object store is absent, and a failed
write no longer reports success - `file_put_contents` returned into nothing, so a
short or failed write still updated the database row and left a record pointing
at a file that was never created.
