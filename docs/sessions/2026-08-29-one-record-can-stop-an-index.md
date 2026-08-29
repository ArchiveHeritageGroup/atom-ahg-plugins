# Seeding an excavation between instances, and one record that stopped the index

Date: 2026-08-29
Releases: none - operational work on two instances

## What was done

An archaeological excavation dataset was seeded from one AtoM instance into
another: 33 descriptions, 1 site, 20 stratigraphic contexts, 6 finds and 44
context relationships, with the Harris Matrix rendering from the result.

The plugin did not need installing. Its Harris Matrix is a feature of the
archaeology plugin rather than a plugin of its own, and the plugin was already
enabled - the confusion came from checking the wrong list. On an instance that
loads plugins from `atom_plugin`, the serialized `setting_i18n` row is not
authoritative, and on a stock instance it is the other way round. The plugin
looked absent in one list and was enabled in the other.

## A table dump would have produced silent nonsense

The plugin tables are not standalone. Site, context and find rows each carry an
`information_object_id`, and the site table has no name column at all - its
identity lives entirely on the linked description. Three id spaces had to be
remapped: descriptions, terms, and the plugin tables' own ids, the last of these
twice over because relationship rows reference contexts on both sides.

**Terms must be matched by taxonomy AND name.** Matching on name alone resolved
"Deposit" to an accession acquisition type, and two more to unrelated base terms.
All three would have imported without error and been quietly wrong, which is the
worst available outcome because nothing surfaces it later.

Descriptions land at `lft=0` until the nested set is rebuilt, so the import
asserts afterwards that no seeded record remains at `lft=0` - records outside the
tree render perfectly and are invisible to browse. Relationship edges whose
endpoints failed to map are skipped and counted, never invented.

## One record aborted the reindex, and blamed the wrong field

`search:populate` stopped with:

```
Couldn't find ancestors, please make sure parent_id values are correct
Couldn't find information object (id: N)
```

Every `parent_id` was correct. The parent existed, the nested set was intact,
nothing sat outside the root span, and no `parent_id` pointed at a missing row. I
rebuilt the nested set twice before reading the indexer.

Its loader selects with INNER JOINs to `object`, `slug` and `status`. A
description missing any one of them returns no row, so `parent_id` is never
populated on the object, the ancestor query is skipped because the property is
not set, and the exception fires naming a field that was never wrong. The three
affected records were missing a publication status.

One bad record aborts the entire run, so the instance keeps whatever partial
index it had.

**One of the three was mine.** The importer had copied a source record that
carried no status rows and faithfully reproduced the gap on the target - a record
that would have been invisible to search and would have broken every future
reindex on that instance. Anything writing descriptions must create four rows
plus a slug, not just the description.

Also cleared, all predating this work by months: six `object` rows claiming to be
descriptions with no description row, and two demo records left statusless. Raw
SQL deletes are what produce that mirror-image orphan, so the deletions here went
children-first with the base row last, and only after measuring the real
reference set across twelve candidate tables.

## A drift measurement that was measuring six instances

A first pass reported the target's index carrying two to five times the terms its
database held. That was wrong. The host runs six AtoM instances against one
Elasticsearch cluster, and the query named no index, so it counted the whole
estate against one instance's database. Scoped correctly, the counts matched
exactly and no reindex was needed.

The same run also probed `/index.php/...` and read the resulting redirect as a
result. Twice in one session, on two unrelated tasks, the answer was to ask for
the URL the application actually generates.
