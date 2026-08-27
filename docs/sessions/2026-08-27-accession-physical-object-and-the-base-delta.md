# Five fixes, and finding out the base was forked in 29 places

Date: 2026-08-27
Releases: atom-ahg-plugins v3.106.35 - v3.106.39, atom-framework v2.18.24

## What was fixed

**Provenance panel emptiness guard** (v3.106.35). The chain was guarded with
`!empty($provenance['timeline'])`. AtoM wraps template arrays in
`sfOutputEscaperArrayDecorator` - an object - so `!empty()` is always true while
`count()` is 0. On an instance with six entries this looked fine; on one with an
empty chain it rendered "0 in chain of custody" under a heading with nothing
beneath. Counting, not testing for emptiness, is the fix.

**PDF redaction failed open** (v3.106.36). `executeDownloadPdf` is a public
action - `boot()` exempts it from authentication - and reaches the redaction step
only when the caller may NOT bypass redaction. If `getRedactedPdf()` failed it
logged the error and served **the original unredacted file** to that caller.
Silently, on the paths most likely to break: missing tool, unwritable cache,
damaged PDF. Now 503. `RedactionAccess` is fail-closed by design and says so in
its own docblock; this had to match it.

**Two accession 500s** (v3.106.37). The availability check called
`QubitAcl::check($this->resource, ...)` with `$this->resource` never populated,
so every check was a 500 for everyone. And `/accession/add?accession=<url>` fed a
value straight into `routing->parse()->resource` and dereferenced it blind.

**Physical object delete and add** (v3.106.39, framework v2.18.24).
`executeDelete` ran the base action on a *separate instance*, leaving `form`,
`resource` and `informationObjects` in that object's varHolder while the template
rendered from this one's - null `$form`, dead on `renderGlobalErrors()`, on every
delete-confirmation page. The same file already had `delegateToBaseAction()` for
its siblings, with a comment describing exactly this trap. Separately, the
framework queried `physical_object_extended` unconditionally, a table owned by
the optional `ahgStorageManagePlugin`, so any install without it 500'd.

**Annotation studio** (v3.106.38). Saving preferred the canvas's *active* object
over the shape just drawn. Clicking an annotation in the sidebar calls
`setActiveObject()`, so every annotation after the first stored the first one's
region and they rendered stacked in one place.

## The lesson that cost the most

A module override that can never run. Base `qtAccessionPlugin` sits in the
hardcoded `$corePlugins` list, and `loadPluginsFromDatabase()` does
`array_merge($corePlugins, $dbPlugins)` - core ALWAYS first. So its
`modules/accession` wins resolution and **`load_order` cannot change that**. I
changed load_order to prove it, watched nothing happen, and reverted it. The
working answer is to put the action in a module base does not ship
(`accessionManage`) and point the route there.

The general shape, which recurred all day: a check that cannot see the
distinction it depends on. `!empty()` on a decorator. A 30-day mtime window used
to measure a 7-month delta. Two directories grepped in place of a repo. A 404 on
a guessed URL shape read as absence.

## What the day actually turned up

Base AtoM on this instance is changed in **29 files**, not the four that were
authorised. Seven months, nine sittings, every sizeable batch a security event,
all of it applied through `bin/install --with-base-patches` - a documented,
supported flag whose help text reads "For AHG-operated servers only".

The rule said base was locked with zero tolerance. The tooling offered a one-flag
fix in seconds. The tooling won, nine times, and no single batch looked like 29
files.

Of the 29, **zero are required for the framework to run** - the autoloader moved
into `ahgCorePlugin` on 5 August. 22 contain no reference to our code at all. The
other 7 are base calling *into* us for a feature.

One of those seven bites on the way out: `QubitUser` routes password checks
through `PasswordService`, guarded by `class_exists()`. Stock does not fatal - it
falls back to `sha1(salt.plaintext)`, which cannot verify an Argon2id account.
Those carry an **empty salt**, and fail as "invalid password" with nothing
logged. Two of forty accounts here. Both disabled rather than deleted.
