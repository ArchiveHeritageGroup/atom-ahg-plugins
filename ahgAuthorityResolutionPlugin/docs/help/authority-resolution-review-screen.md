# Authority Resolution - Review Screen Reference

The review screen is where archivists make decisions on promoted mentions. On the AtoM Heratio side it lives at `/admin/authorityResolution/:id/review`; on Laravel Heratio it is `/admin/authority-resolution/review/:id`. Both render the same three regions and expose the same five action buttons.

## Layout

The screen is a three-region admin page rendered with the central Bootstrap 5 theme on AtoM (`layout_1col` from `ahgThemeB5Plugin`). The three regions are:

### 1. Source mention (top card)

- The surface form NER extracted (the bold heading)
- The source information object (link to the parent archival description, opens in a new tab)
- The NER confidence score
- The mention ID (small, top-right)

### 2. Context window (middle card)

- A monospaced 150-character window centred on the mention
- The mention itself is highlighted with a yellow `<mark>` tag
- Character offset range (e.g. `730-748`) and paragraph offset (e.g. `12-846`)
- For PLACE mentions, a small Leaflet map preview if at least one candidate has resolvable coordinates

### 3. Ranked candidates (main column)

A vertical list of candidate cards. Each card shows:

- Rank position (1, 2, 3, ...)
- Candidate display name and source (`mysql_actor`, `fuseki_agent`, `mysql_term`, `fuseki_place`)
- Name similarity score (Jaro-Winkler base)
- Composite score (clamped to [0.0, 1.0])
- Per-dimension evidence badges (one row per applicable evaluator)
- A "view authority" link to the underlying actor or term, when one exists
- Action buttons for that candidate (Link, Link different)

For Person / Org mentions the dimension rows are: `temporal`, `geographic`, `relational`, `role`, `conflict`.

For Place mentions: `hierarchical`, `document_prior`, `co_occurring`, `conflict`, `scale`.

Each dimension shows one of four signals:

- **Match** (green) - evidence supports this candidate
- **Conflict** (red) - evidence contradicts this candidate
- **Silent** (grey) - both sides had data but the comparison was inconclusive
- **Absent** (light) - this dimension had nothing to evaluate

Silent and absent are intentionally distinct. Silent says "we looked and it was inconclusive"; absent says "the data was not populated to look at". The split is preserved in the snapshot so audit can tell the two apart.

## The five action buttons

### Link

One click commits the top-ranked candidate. Records `decision_type=link`, writes the immutable audit row with the frozen evidence + candidate-list snapshot, transitions the mention to `linked`, and back-updates `ahg_ner_entity.linked_actor_id` for the existing discovery consumers.

### Link different

Opens a candidate-picker modal (typeahead against `/admin/authorityResolution/lookup`). You can pick any candidate from the list or search beyond the visible top-N. Records `decision_type=link_different`. The recorded `original_system_top_score` is rank-1's score (not the picked candidate's) so the audit preserves "what the system thought was best, that the archivist overrode".

### Create new

Goes to the create-new pre-fill wizard (`/admin/authorityResolution/:id/create-new`). The engine queries every enabled external authority source (VIAF, Wikidata, GeoNames, ...) and pre-fills the form. Each pre-filled field carries a coloured provenance badge with source, licence, and retrieval timestamp. See `authority-resolution-create-new-authority` for the wizard's behaviour.

### Park

Opens a reason modal. The reason is required. The mention moves to `parked` state and a row is added to `ahg_mention_park`. The background scan job (`auth-res:scan-parked`) re-checks the candidate set on a schedule and flips `new_candidate_available=1` when it changes. See `authority-resolution-park-queue`.

### Reject

Opens a reason modal. The reason is required. The mention moves to `rejected` state. A row is captured in `ahg_ner_feedback` (full source text, span offsets, archivist user, rejection reason) for later JSONL export and NER retraining. The rejection reason is embedded in the decision row's `evidence_snapshot` JSON because the table has no dedicated column.

## What the snapshot preserves

Every decision row freezes three things into the audit trail:

- `candidates_visible_snapshot` - the full visible candidate list, with rank, display name, and authority id
- `evidence_snapshot` - the chosen candidate's `evidence_signals` and `evidence_data` exactly as they were on screen
- `original_system_top_score` - the rank-1 composite score at the moment of decision

This means "what did the archivist actually see" is answerable from the decision row alone, even if the underlying candidate generator, scorer, or external lookup is changed later.

## Concurrency

Two archivists can open the same review screen at the same time. The screen does not lock the mention. The first decision wins for the `state` column; the second decision is still recorded in `ahg_mention_decision` (decisions are append-only) and appears in the audit trail. The most recent decision wins for the state column. In practice this is rare; the queue list shows the assignee column to coordinate.

## Keyboard shortcuts

The review screen ships with the central theme's keyboard layer. Use Tab to walk between candidate cards; Enter on the focused action button commits.

## Permissions

Review queue index, review page, and the typeahead lookup require `editor` credential or admin. All POST decision handlers (link, link different, create new, park, reject) require `editor` credential. The settings page (external lookup configuration) is admin-only.

## See also

- `authority-resolution-user-guide` - the high-level overview and the five outcomes
- `authority-resolution-park-queue` - the dedicated park queue + bulk re-review
- `authority-resolution-create-new-authority` - the create-new pre-fill wizard
- `authority-resolution-evidence-scoring` - the ten evaluators in detail
- `authority-resolution-provenance` - the RDF-Star audit trail
