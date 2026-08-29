# Testing a feature end to end, and finding it had never worked

Date: 2026-08-29
Releases: atom-ahg-plugins v3.106.44 - v3.106.48

When the project-documents feature shipped, the note said the round trip was
untested and that whoever picked it up should start there. Running it found four
bugs in a row, each hidden behind the one in front of it. The feature had never
worked on any instance.

## What the four were

**Wrong namespace, twice.** `src/Services` carries two namespaces - 38 classes under
`AtomExtensions\Services`, 30 under `AtomFramework\Services` - so it has to be read
per class. Two references named the wrong one, and it failed in two different ways
depending on where it ran. `class_exists()` returned false, so the admin-configurable
size limit was never read and the hardcoded default was always used, silently. And on
an instance running the generated runtime plugin, the autoload attempt re-declared a
class that plugin already had, fataling the page.

The second of those is the one to remember: it fired **after** the uploaded file was
moved into place and **before** the row was written. An orphaned file with no record
of it - exactly the failure the code's own comments warned about, caused by the code
those comments sit in.

**array_filter on an escaper decorator.** Symfony hands templates an
`sfOutputEscaperArrayDecorator`. It iterates and counts, so a foreach reads normally,
but `array_filter()` type-errors on it - a 500 on the whole page rather than a missing
badge. Unescape before any `array_*` call.

**A slash path in url_for.** `url_for('research/project/1/document')` is read as
module/action; everything after the second segment is dropped. All three forms posted
to a URL with no id. Nothing was written, nothing was logged, and it looked exactly
like a silently failing save. Thirteen redirects in the same file had the same shape,
so even after the write succeeded the user was bounced to a page that could not show
it.

## Why none of this showed up before

Every one of these needed conditions the other instance did not have. Two needed the
generated runtime plugin, which only one instance runs. One needed a project to exist,
and there were none. The instance that looked like the safe place to verify was
structurally incapable of failing.

That is the third time this week that testing on the healthy instance proved nothing.
A clean result there means "no regression", never "fixed".

## The check that would have caught it in one step

Look at the database and the filesystem, not the page. The upload that reported
success wrote a file and no row; the upload that reported nothing had in fact
written both. In neither case did the rendered page say what had happened.

## Also today

The schema those columns needed was applied through the framework's own
`schema_upgrades.sql`, which uses an idempotent add-column procedure. The plugin
installer would not have done it: it uses `CREATE TABLE IF NOT EXISTS`, which never
alters a table that already exists. That distinction is the whole reason the columns
were missing in the first place.
