# Authority Resolution - Evidence Scoring

The evidence layer is what makes the engine evidence-based instead of confidence-based. Every candidate gets scored against the same neighbourhood context packet, and the per-dimension signals are exposed on the review screen so the archivist sees a fair comparison. This article covers the signal vocabulary, the composite formula, and the ten evaluators.

## The signal vocabulary

Four enum values:

| Signal | Composite delta | Meaning |
|---|---|---|
| MATCH | +0.10 | Evidence supports this candidate |
| CONFLICT | -0.30 | Evidence contradicts this candidate |
| SILENT | +0.00 | Dimension had data on both sides, but the comparison was inconclusive |
| ABSENT | +0.00 | Dimension had no data to evaluate (no nearby dates, no event records, etc.) |

`silent` is intentionally distinct from `absent` so the review UI can show "we looked, both sides had something, the comparison was inconclusive" separately from "this dimension simply is not populated".

## The composite formula

```
composite = name_similarity_score
for each dimension signal:
   match    => composite += 0.10
   conflict => composite -= 0.30
   silent   => composite += 0
   absent   => composite += 0
composite = clamp(composite, 0.0, 1.0)
```

Clamped to `[0.0, 1.0]`, rounded to 4 decimal places. Candidates are then re-ranked by `composite_score` (descending), tie-broken by `name_similarity_score`, then by display name.

The base `name_similarity_score` is Jaro-Winkler between the candidate display name and the mention surface form.

## The ten evaluators

The orchestrator (`EvidenceScorer`) dispatches by `supports($entity_type)`. Person/Org mentions never see Place evaluators and vice versa, so the `evidence_signals` JSON has the right five keys per row.

### Person / Org evaluators

For mentions where `entity_type IN ('PERSON', 'ORG')`:

| Slug | Class | Reads |
|---|---|---|
| temporal | `TemporalEvaluator` | `event.start_date/end_date` for `actor_id = candidate`; falls back to year-scan of `actor_i18n.dates_of_existence`. Compares against years extracted from `ahg_mention_context.nearby_dates`. |
| geographic | `GeographicEvaluator` | `actor_i18n.places` + `actor_i18n.history` substring-scan against `ahg_mention_context.nearby_places`. |
| relational | `RelationalEvaluator` | `relation` table both sides -> related actors' `actor_i18n.authorized_form_of_name` vs co-occurring PERSON/ORG entities in `ahg_mention_context.co_occurring_entities`. |
| role | `RoleEvaluator` | `actor_i18n.history/functions/mandates/general_context` substring-scan for role-language tokens captured in `ahg_mention_context.role_language_tokens`. |
| conflict | `ConflictEvaluator` | `actor.entity_type_id` (132 = Person, 131 = Corporate body). Emits CONFLICT on type mismatch with mention.entity_type. |

### Place evaluators

For mentions where `entity_type IN ('GPE', 'PLACE', 'LOC')`:

| Slug | Class | Reads |
|---|---|---|
| hierarchical | `HierarchicalEvaluator` | Walks `term.parent_id` chain (taxonomy 42) against `nearby_places` (excluding the mention itself). |
| document_prior | `PriorEvaluator` + `DocumentPriorService` | Top-3 most-resolved place authorities for the mention's fonds. Cache in `ahg_settings.authority_resolution.prior.<fonds_id>` (24h TTL). |
| co_occurring | `CoOccurringPersonEvaluator` | Candidate term's `relation`-graph actors vs co-occurring PERSON/ORG entities. |
| conflict | `PlaceConflictEvaluator` | Emits CONFLICT if the candidate term is not in the Places taxonomy (id=42). |
| scale | `ScaleEvaluator` | Compares parent-chain depth of the candidate against the median depth of other context places. |

## Worked example - mention 138 ("Frederick Douglass", PERSON)

```
rank 1  Frederick Douglass  name_sim=1.0000  composite=1.0000
  all dimensions absent except conflict=silent
  (entity_type_id 132 matches mention type PERSON)
```

Composite ends up equal to name similarity because no signal fired. The single non-absent signal is `conflict=silent`: we looked at the candidate actor's `entity_type_id` and confirmed no mismatch, but a confirmation is not a positive match.

## Worked example - mention 25 ("London", GPE)

```
rank 1  London  name_sim=1.0000  composite=1.0000 (clamped from 1.10)
  scale=match  conflict=silent  co_occurring=absent  hierarchical=absent  document_prior=silent
```

`scale=match` because the document chatter is also at top-level place granularity (depth 0), so the candidate's depth matches the median context-place depth. The `+0.10` is silently clamped away because name_similarity was already 1.0.

## What "absent" vs "silent" looks like on a real database

From the demo run on 2026-05-19 (9 candidate rows scored):

| Dimension | match | conflict | silent | absent | n/a (entity-type) |
|---|---|---|---|---|---|
| temporal | 0 | 0 | 0 | 7 | 2 (place) |
| geographic | 0 | 0 | 0 | 7 | 2 (place) |
| relational | 0 | 0 | 0 | 7 | 2 (place) |
| role | 0 | 0 | 0 | 7 | 2 (place) |
| conflict (P/O) | 0 | 0 | 7 | 0 | 2 (place) |
| hierarchical | 0 | 0 | 0 | 2 | 7 (person/org) |
| document_prior | 0 | 0 | 2 | 0 | 7 (person/org) |
| co_occurring | 0 | 0 | 0 | 2 | 7 (person/org) |
| conflict (place) | 0 | 0 | 2 | 0 | 7 (person/org) |
| scale | 2 | 0 | 0 | 0 | 7 (person/org) |

Why the row of `absent` everywhere on the dev set:

- `temporal = absent`: `actor_i18n.dates_of_existence` is NULL on every candidate actor in the dev set, and the NER pipeline did not emit any DATE entities into `ahg_mention_context.nearby_dates` for these objects.
- `geographic = absent`: `actor_i18n.places` is NULL on every candidate; the history field is also empty.
- `relational = absent`: candidate actors do have inbound relations, but the relation peers do not appear in the document paragraph.
- `role = absent`: there are no role-language tokens in the captured paragraph for these mentions.
- `conflict = silent`: we did have an actor row to inspect and positively confirmed no type mismatch. This is the SILENT case, not ABSENT.
- `document_prior = silent`: candidate was not in the top-3 for its fonds.
- `scale = match`: both place candidates and the other context places sit directly under term 110 (Places root), so depth = 0 = median.

These distinctions are exactly what the SILENT/ABSENT split is for. It proves the evaluators actually touched the right tables rather than short-circuiting on missing data.

## Running the scorer manually

```bash
sudo -u www-data php symfony auth-res:score-evidence <mention_id> --show
```

The `--show` flag prints per-candidate signals so you can see what the review screen will display. Output mirrors the Laravel side byte-for-byte (same evaluator slugs, same signal enums, same composite formula).

```bash
sudo -u www-data php symfony auth-res:score-evidence 138 --show

Mention #138 (PERSON, "Frederick Douglass", object=901990) - 1 candidate
  rank 1  Frederick Douglass  name_sim=1.0000  composite=1.0000
    all dimensions absent except conflict=silent
```

## Designing new evaluators

The contract is `EvaluatorInterface`:

```php
public function slug(): string;
public function supports(string $entityType): bool;
public function evaluate(Mention $mention, Candidate $candidate, MentionContext $ctx): EvidenceSignal;
public function snapshot(): array;   // used for evidence_data
```

Three signals only: `match`, `conflict`, `silent`, `absent`. The composite deltas are global; do not invent per-evaluator weights. If you need a stronger negative signal, that is what `conflict` is for - it is already -0.30, three times the magnitude of a positive match.

## See also

- `authority-resolution-user-guide` - the high-level overview
- `authority-resolution-review-screen` - where the signals are rendered
- `authority-resolution-cli-tasks` - the `auth-res:score-evidence` task
- `ahgauthorityresolutionplugin` - file inventory and namespace map
