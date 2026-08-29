# Calm migration support, and two checks that could not see what they were asked about

Date: 2026-08-29
Releases: atom-ahg-plugins v3.106.52

## Axiell Calm as a migration source

The data migration plugin gained a Calm source profile: a detector signature and
an ISAD(G) field mapping for catalogue, authority and accession records, plus a
level map and helpers for parent references and multi-value fields.

Calm is easier than the museum-shaped sources already supported, because it is
ISAD(G)-native - most of the work is renaming rather than restructuring. What is
not easy is everything the field list does not contain.

**Hierarchy lives only in the reference code.** "GB 123 ABC/1/2" is the child of
"GB 123 ABC/1" and nothing else in the export carries the tree. The helper added
here resolves a single reference and is documented as explicitly NOT a hierarchy
builder, because a real load also has to sort parents before children, synthesise
missing intermediate levels where a site skipped one, and reject cycles. Resolving
per row as it imports produces records that exist, return 200, and are in no tree
at all.

**Authority records are links, not text.** Calm keeps people, organisations and
places as separate record types with real relationships to catalogue entries.
Flattened into access-point strings they cannot be restored, which is a loss this
estate has already taken once on another migration and did not notice for months.

**One level has no target.** Calm's Piece sits below Item and has no equivalent
here. Folding it into Item never loses a record but does flatten a distinction the
depositor drew, so it is marked in the code as a decision to override rather than
a fact.

Detection was tested on the cases that matter rather than only the happy one: a
full export detects at 100%, a sparse one carrying only the two reference columns
at 50%, a generic CSV is not falsely claimed, and the existing CSV signature is
not stolen by the new one.

## An OAI-PMH endpoint that returns 500 on its own base URL

Diagnosed from source for an enquiry on the users list. Two unrelated faults that
looked like one.

The 500 on the bare endpoint is a genuine plugin bug and fires only when the verb
argument is missing entirely. The whole validation block sits inside a test for
the verb being set, so a request without one skips every error path, falls to the
else branch, sets the verb to `badVerb`, and renders the normal template - which
asks for a component by that name. The partial exists; the component class does
not, and every other verb has one. An INVALID verb is handled correctly and
returns proper error XML. Only the empty request breaks, which is why it has
survived: no harvester ever fetches a base URL without a verb.

The second fault, a dissemination error on ListRecords, is EAD requested without
a cache. EAD is never generated on the fly, only served from files written
earlier, so the fix is the XML caching setting plus a run of the cache task for
records that predate it. Dublin Core needs none of it.

Worth recording that the error message itself reads "is available for item X"
where it means "is NOT available", which is what made the reporter think the two
faults were connected.

## Two checks that could not see the thing they were checking

Both surfaced through cross-session review, in opposite directions.

A warning was carried into a handover as a flat statement - that certain write
services leave the nested-set column null - when it is true of this tree and not
of the one it was sent to. Stated without scope it would have sent someone
hunting a defect that was not there. The peer's own evidence was better than the
original: records sitting outside the tree entirely, put there by a seeder that
bypassed the write services. So the hazard is any writer that inserts rows
directly, which a bulk migration importer certainly is, rather than a service
that forgets a column.

The second was worse because it was confidently reported. Two lists that
duplicate each other across a language boundary were compared, found to agree,
and a test was recommended to keep them agreeing. The test already existed, and
was the reason they agreed. Having answered "do these match today" the safeguard
question was treated as still open, when it was one file away.

The general form is the estate's most expensive recurring mistake wearing new
clothes: reading the shape and concluding the content. A first extraction in that
same comparison also swept in a type declaration and reported drift that did not
exist - caught before it was reported, which is the only reason it is a footnote
rather than a third example.

## Not recorded here

Findings about another project's data were handed to the session that owns it
rather than written up here. Reading and querying elsewhere is fine; publishing
another project's contents is not.
