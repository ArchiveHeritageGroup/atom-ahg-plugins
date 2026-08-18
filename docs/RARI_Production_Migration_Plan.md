# RARI production migration

> **Note on the install steps referenced here.** The AtoM and plugin installation
> procedure has been verified from bare metal since this plan was written; see
> [AtoM_2.10_Clean_Install_Log.md](AtoM_2.10_Clean_Install_Log.md) and the README.
> In particular `php8.3-gd` is required and is missing from AtoM's own package list,
> and base AtoM is not modified at any point.

**Rock Art Research Institute, University of the Witwatersrand**
**Target: within two weeks of 16 August 2026**
**Prepared by:** Dr Johan Pieterse, The Archive and Heritage Group (Pty) Ltd

## What this covers

RARI's production instance moves to the AHG plugin stack on AtoM 2.10. The work has
been built and proven on a development copy carrying RARI's full production data, so
what follows is a migration of something already working rather than a plan to write it.

Everything below was verified on that copy: 292,278 archival descriptions, 8,436
authority records, 30 authority-record site records and the real user accounts.

## Modules being introduced

| Module | What it gives RARI | State |
|---|---|---|
| **Feedback** | Public correspondence against records, with staff triage | Installed, 9 legacy entries carried over |
| **Favorites** | Saved records per user | Installed, 27 legacy entries carried over |
| **Researcher** | Researcher registry, approval and expiry | Installed, 22 accounts migrated |
| **Register as a Wits user** | Self-service account request for institutional users | Registration path in place |
| **ahgSiteRecord** | Site recording with role-gated locality | New. 7,585 site records seeded |
| **Request to Publish** | Publication requests for archival images | Installed, 79 legacy records carried over |
| **Cart** | Reproduction and image requests | Installed, legacy cart data present |
| **Access request and approval** | Requests for access to restricted material, with an approval queue | Installed |

All of these are managed from the staff interface: browse, approve, deny, expire and
report, rather than by editing the database.

## Modules being retired

**Service Provider** and **Registry**, both bespoke RARI plugins, are removed. This is
not a judgement call, it is what their data says: both tables are **empty in
production**. The researcher registry has never held a record either, which is why the
replacement is the Researcher module rather than a port of the old one.

The standalone `rock_forms` field-recording application is also retired. Its two tables
are empty, so nothing is migrated. Its functionality is rebuilt as ahgSiteRecord, tracked
as issue #299.

⛔ **Before cutover, `rock_forms/` must be blocked and its credentials rotated.** It sits
in the public web root with no authentication of any kind on create, edit or delete, it
deletes on a bare GET with no token, and it exposes two database passwords in
world-readable files. One of those passwords is still in use elsewhere. This is
independent of the migration and should not wait for it.

## Users

Thirty accounts, handled by domain:

- **Wits addresses stay staff.** Six accounts.
- **Non-Wits become researchers.** Twenty-two accounts, migrated and approved, since they
  are existing active accounts rather than new applications.
- Two AHG accounts stay staff.

AtoM never captured real names for these accounts, so names were derived from the
username and, where that gave nothing, from the email address. Six accounts keep their
handle rather than an invented name. Two accounts appear to be the same person and were
deliberately not merged.

## Site locality

This is the part that changes most, and it is worth being explicit about why.

RARI records where a site is in the ISAAR "Internal structures/genealogy" field as free
text. On the production instance that field is commented out of the templates, so nobody
can see it, staff included. That is a blunt instrument: it protects the data by making it
useless.

Measured on the development copy, that field held:

- **5,456** records with a 1:50,000 map sheet reference, across 481 distinct sheets
- **842** records with "How to find the site:" followed by turn-by-turn directions

The directions matter more than the map sheets. A sheet reference locates a site to
roughly seven kilometres. Directions take somebody to it.

After migration the locality lives in the site record module and is governed by one rule
in one place. Staff with editor or administrator rights see the exact position. Everybody
else, including a logged-in researcher, sees a position rounded to about eleven
kilometres, with the map sheet and the original text withheld entirely. A record whose
sensitivity has never been set is treated as sensitive. The rule applies to the record
view, the panel, browse, exports and reports, not just the page it was written for.

The ISAAR field itself is cleared during migration, so there is one copy rather than two,
and every original string is preserved verbatim inside the site record.

⚠️ **One decision is outstanding.** Site codes such as `2929CD40` appear in the Parallel
form of name field on **877 records**, and those encode the map sheet. They are also how
RARI identifies a site. Gating them would hide part of a record's name from the public;
leaving them means the sheet stays publicly derivable for those sites. This needs a
decision before cutover.

## Approach

The development copy was rebuilt from a clean AtoM 2.10 install, their database restored,
and the schema upgraded through AtoM's documented path. That rehearsal found several
things worth knowing:

- Their database already contains tables from an earlier AHG deployment, so the schema
  must be reconciled rather than installed. A standard install skips tables that already
  exist, and the mismatch stays silent until something queries a column that was never
  added.
- Their audit trail is a modification to the database library itself, inside the vendor
  directory, with 31.6 million rows. It cannot be carried forward, and the replacement
  starts clean at cutover with the old table retained read-only for history.
- Records inherited without their translation rows cause staff pages to fail rather than
  degrade. Fixed, but it is the shape of problem to expect from legacy data.

## Duplicate authority records

An earlier import created authority records instead of matching the ones already there,
and then attached that run's descriptions to the copies. This needs resolving at
cutover, not after: every day it stays, more descriptions attach to the wrong record.

What is in the development copy, measured 17 August 2026:

| | |
|---|---|
| Authority records | 8,443 |
| Surplus by exact name | 125, across 63 repeated names |
| Surplus allowing for case, spacing and punctuation | 143 |
| Loaded in the original pass, 30 April 2025 | 8,243 |
| Created after it | 186 |
| Of those, exact duplicates of an existing record | 58 |
| **Duplicates carrying descriptions** | **52** |

The clearest case is Chentcherere II. The real record holds 84 descriptions. Nineteen
copies made in a single run on 21 August 2025 hold 43 more between them, slugged
`chentcherere-ii-2` through `-20`. The copies have no entity type where the original has
one, which is the fingerprint of the importer creating rather than matching.

Two consequences shape the work:

- **It is a merge, not a delete.** 52 of the duplicates are live creator links. Removing
  them would take their descriptions with them. Each merge repoints `event.actor_id` to
  the surviving record, then removes the empty shell.
- **Name is the only key available.** `description_identifier` is empty for every actor,
  so there is no site code to match on. Site codes exist only as parallel names, which
  is the same field covered by the locality decision above. Exact and normalised name
  matching finds 143; the 120 later records with no exact twin still need checking for
  near-duplicates before anything is merged.

Sequence:

1. Produce a dry-run report - every proposed pairing, which record survives, how many
   descriptions move, and anything ambiguous held back for a person to decide. No writes.
2. RARI reviews it. Some pairs will be genuinely different people or sites with the same
   name, and only they can say which. Nothing is assumed.
3. Apply the approved merges on the development copy, verify description counts land on
   the surviving records, then repeat on production during cutover.
4. Re-run the detection afterwards, so anything the first pass missed is visible rather
   than assumed absent.

The importer itself must be corrected before any further load, or the next run recreates
the problem. Matching on name alone is what caused this; the fix is to match against
existing authority records and report near-misses instead of silently creating.

The existing deduplication tooling does not cover this. It scans archival descriptions
and digital objects, and has no handling for authority records at all, so the detection
and merge described here is new work rather than a matter of pointing an existing tool at
a different table.

## Not included

The 30 TB image store is not part of this work. On the development copy every digital
object resolves to a marked placeholder; production keeps its own files.

No coordinates, visit dates, regions or condition assessments exist in RARI's current
data. Those fields are available in the new modules and stay empty until fieldwork fills
them.
