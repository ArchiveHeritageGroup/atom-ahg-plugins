# AHG Authority Resolution - User Guide

The **AHG Authority Resolution Engine** turns named-entity mentions extracted from your archival descriptions into archivally-defensible authority links. The archivist is always in the loop: the engine assembles the evidence, ranks the candidates, and presents one of five outcomes. The engine never auto-links.

This guide walks you through the day-to-day workflow on the **AtoM Heratio** side. The Laravel Heratio side ships the same engine and the same five outcomes, so the workflow on either codebase is identical.

## What the engine does, in one sentence

For every name your NER pipeline finds in a description, the engine builds a context packet, queries every registered authority source for matching candidates, scores each candidate against the evidence, and shows you the result so you can decide.

## What the engine does NOT do

- It does not auto-link. Every authority link is the explicit result of an archivist click.
- It is not a NER service. NER runs upstream and writes rows into `ahg_ner_entity`. The engine *promotes* selected rows into the review workflow.
- It is not jurisdiction-specific. The seven external adapters (VIAF, Wikidata, GeoNames, TGN, GND, ISNI, SAGNC) cover most regions; the adapter set is pluggable. SAGNC is one regional adapter among many.

## The five outcomes

Every mention you review ends in exactly one of:

- **Link** - the top-ranked candidate is correct. One click commits it.
- **Link different** - a lower-ranked candidate is correct. You pick the right row from the candidate list.
- **Create new** - none of the candidates fit. A new authority record is created and pre-filled from external sources you have enabled.
- **Park** - the decision is not safe to make yet. The mention stays alive and re-surfaces if the candidate set changes.
- **Reject** - the NER model was wrong. This is not actually an entity of the claimed type. The rejection becomes negative NER training data.

Each click writes an immutable row to `ahg_mention_decision`. Decisions are append-only: to "change" a decision, record a new one. Both rows are visible in the audit trail and the most recent decision wins for the state column.

## The five-step flow

1. **Extract.** NER runs upstream and writes rows to `ahg_ner_entity` with the surface form, entity type (PERSON / ORG / GPE / LOC / PLACE), source object, and a confidence score.
2. **Promote.** Selected rows are copied into `ahg_mention` and a neighbourhood context packet is computed (character + paragraph offsets, 150 chars before / after, co-occurring entities, nearby dates, nearby places, role-language tokens).
3. **Generate candidates.** For each mention the engine queries every registered candidate adapter (local actor / term tables, Fuseki agent / place graphs) and ranks the results by name similarity.
4. **Score evidence.** The scorer runs every applicable evaluator over every candidate and writes per-dimension signals plus a composite score back to the candidate row. The ten evaluators are split between Person/Org dimensions (temporal, geographic, relational, role, conflict) and Place dimensions (hierarchical, document-prior, co-occurring, place-conflict, scale).
5. **Decide.** You open the review screen, see the mention in context with each candidate's per-evaluator badge, and click one of the five action buttons.

## Where to start

- Open the review queue: `/admin/authorityResolution`
- Review one mention: `/admin/authorityResolution/:id/review`
- Open the park queue: `/admin/authorityResolution/park`
- Configure external lookup sources: `/admin/authorityResolution/settings/lookup`

All four routes require the `editor` credential. The settings page is admin-only.

## When in doubt

- If the top candidate is right: **Link**.
- If a lower candidate is right: **Link different**.
- If none of the candidates are right but the mention is real: **Create new**.
- If the mention is real but you cannot decide yet: **Park** (with a reason).
- If the mention is not a real entity: **Reject** (with a reason).

The engine remembers everything you saw on screen at the moment of decision (the candidate slate, the evidence signals, the rank-1 score) by snapshotting them into the decision row, so the decision is defensible later even if the underlying authority store changes.

## Two implementations, one engine

The engine ships twice, once per codebase: as a Laravel package on Heratio, and as a Symfony 1.4 plugin on AtoM Heratio. Both write to the same MySQL tables and the same Fuseki dataset, isolated by named-graph URI. The UI layer differs (Bootstrap 5 in AtoM, Tailwind 4 in Heratio), but the data layer and the workflow converge.

## Further reading

- `authority-resolution-review-screen` - the three-region review UI explained
- `authority-resolution-park-queue` - parking and bulk re-review
- `authority-resolution-create-new-authority` - the create-new pre-fill wizard
- `authority-resolution-evidence-scoring` - what the per-evaluator badges mean
- `authority-resolution-provenance` - the RDF-Star audit trail in Fuseki
- `authority-resolution-cli-tasks` - the eleven Symfony 1.4 tasks
- `ahgauthorityresolutionplugin` - full technical reference
