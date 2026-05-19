# Authority Resolution - Provenance Model

Every decision and every accepted authority-creation pre-fill emits provenance to a Fuseki triple store using RDF-Star reification. This article covers the data shape, the two named graphs, and the SPARQL recipes you need to read the provenance back.

## Why RDF-Star

A plain RDF triple says "actor 901990 has end date 1868". That is true but not auditable: who said so, when, on what evidence? RDF-Star wraps the triple itself as a subject so you can attach metadata to the *claim* without polluting the canonical subject graph.

This lets the same `ric:hasName` triple appear in:

1. The canonical RiC graph (clean, no provenance).
2. The field-provenance graph (with `wasDerivedFrom`, `acceptedByUser`, `retrievedAt`).
3. The decisions graph (with `supportedBy` -> `evidenceSnapshot`).

Each consumer queries the graph it cares about.

## Two named graphs, two codebase prefixes

Isolation is by **named graph URI**, not by dataset. The host runs a single Fuseki dataset (`/openric-model`). Two graphs are used per codebase:

| Graph URI | Holds |
|---|---|
| `urn:heratio:auth-res:graph:decisions` | Laravel Heratio decision provenance |
| `urn:atom:auth-res:graph:decisions` | AtoM Heratio decision provenance |
| `urn:heratio:auth-res:graph:field-provenance` | Laravel per-field create-new provenance |
| `urn:atom:auth-res:graph:field-provenance` | AtoM per-field create-new provenance |

The codebase prefix (`heratio:` vs `atom:`) lets a single Fuseki instance hold both codebases' provenance without bleed. Cross-codebase SPARQL queries do `FROM NAMED <urn:heratio:...> FROM NAMED <urn:atom:...>` and `UNION` the bindings.

Graph URIs are configurable, not hardcoded:

```sql
UPDATE ahg_settings
   SET setting_value = 'urn:custom:auth-res:graph:decisions'
 WHERE setting_key   = 'authority_resolution.decisions_graph_uri';
```

Use this for staging vs. production isolation on a shared Fuseki.

## Decisions graph

For every row in `ahg_mention_decision`, the engine writes one `prov:Activity`. The graph is the canonical answer to "who decided what, when, on what evidence".

### link / link_different

```turtle
ahg:decision/42 a prov:Activity ;
    ahg:decisionType         "link" ;          # or "link_different"
    prov:startedAtTime       "2026-05-19T09:13:44+02:00"^^xsd:dateTime ;
    prov:wasAssociatedWith   ahg:user/1 ;
    ahg:onMention            ahg:mention/24 ;
    ahg:chosenCandidate      ahg:candidate/77 ;
    ahg:chosenAuthority      ahg:actor/901990 ;
    ahg:topSystemScore       "0.7421"^^xsd:decimal ;
    ahg:codebase             "atom" .

<< ahg:mention/24  ahg:resolvedTo  ahg:actor/901990 >>
    ahg:supportedBy           ahg:decision/42 ;
    ahg:evidenceSnapshot      "[...JSON...]" ;
    ahg:candidatesVisible     "[...JSON...]" .
```

For `link_different`, `ahg:topSystemScore` is the **rank-1** candidate's composite score, not the picked candidate's. This is intentional: it preserves "what the system thought was best, that the archivist overrode".

### create_new

```turtle
ahg:decision/43 a prov:Activity ;
    ahg:decisionType         "create_new" ;
    prov:startedAtTime       "2026-05-19T09:14:01+02:00"^^xsd:dateTime ;
    prov:wasAssociatedWith   ahg:user/1 ;
    ahg:onMention            ahg:mention/25 ;
    ahg:newAuthorityCreated  ahg:actor/901999 ;
    ahg:topSystemScore       "0.4012"^^xsd:decimal ;
    ahg:codebase             "atom" .
```

No `ahg:resolvedTo` assertion - the mention did not resolve to any existing authority. The new authority's field-level provenance lives in the field-provenance graph.

### park

```turtle
ahg:decision/44 a prov:Activity ;
    ahg:decisionType         "park" ;
    prov:startedAtTime       "2026-05-19T09:14:33+02:00"^^xsd:dateTime ;
    prov:wasAssociatedWith   ahg:user/1 ;
    ahg:onMention            ahg:mention/26 ;
    ahg:topSystemScore       "0.6203"^^xsd:decimal ;
    ahg:parkReason           "Awaiting MARC import for early Zulu kings." ;
    ahg:codebase             "atom" .
```

### reject

```turtle
ahg:decision/45 a prov:Activity ;
    ahg:decisionType         "reject" ;
    prov:startedAtTime       "2026-05-19T09:15:11+02:00"^^xsd:dateTime ;
    prov:wasAssociatedWith   ahg:user/1 ;
    ahg:onMention            ahg:mention/27 ;
    ahg:rejectionReason      "Horse name, not a place." ;
    ahg:codebase             "atom" .
```

No `ahg:resolvedTo` assertion; the mention was not real. The rejection also writes a row to `ahg_ner_feedback` for NER retraining.

## Field-provenance graph

For every `decision_type=create_new` decision, the engine writes one reified RDF-Star assertion per pre-filled field that the archivist accepted:

```turtle
GRAPH <urn:atom:auth-res:graph:field-provenance> {

  << ahg:actor/901990  ric:hasBeginningDate  "1790"^^xsd:gYear >>
      prov:wasDerivedFrom    <https://viaf.org/viaf/123456789> ;
      ahg:lookupSource       "viaf" ;
      ahg:retrievedAt        "2026-05-19T09:12:01+02:00"^^xsd:dateTime ;
      ahg:acceptedByUser     ahg:user/1 ;
      ahg:fromDecision       ahg:decision/42 .

  << ahg:actor/901990  ric:hasEndDate  "1868"^^xsd:gYear >>
      prov:wasDerivedFrom    <https://www.wikidata.org/entity/Q1234567> ;
      ahg:lookupSource       "wikidata" ;
      ahg:retrievedAt        "2026-05-19T09:12:03+02:00"^^xsd:dateTime ;
      ahg:acceptedByUser     ahg:user/1 ;
      ahg:fromDecision       ahg:decision/42 .

  # An archivist override is captured as wasDerivedFrom an internal user URI.
  << ahg:actor/901990  ric:hasName  "Mzilikazi kaMashobane" >>
      prov:wasDerivedFrom    ahg:user/1 ;
      ahg:lookupSource       "archivist_override" ;
      ahg:originalValue      "Moselekatse" ;
      ahg:originalSource     "viaf" ;
      ahg:fromDecision       ahg:decision/42 .
}
```

## Common SPARQL recipes

### Decisions made today

```sparql
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX ahg:  <https://theahg.co.za/ns/auth-res#>
PREFIX xsd:  <http://www.w3.org/2001/XMLSchema#>

SELECT ?d ?type ?archivist ?mention WHERE {
  GRAPH <urn:atom:auth-res:graph:decisions> {
    ?d a prov:Activity ;
       ahg:decisionType        ?type ;
       prov:wasAssociatedWith  ?archivist ;
       ahg:onMention           ?mention ;
       prov:startedAtTime      ?when .
    FILTER ( ?when >= "2026-05-19T00:00:00"^^xsd:dateTime )
  }
}
ORDER BY DESC(?when)
```

### Override rate per archivist (link vs link_different)

```sparql
PREFIX ahg: <https://theahg.co.za/ns/auth-res#>

SELECT ?archivist
       (SUM(IF(?type = "link", 1, 0)) AS ?confirmed)
       (SUM(IF(?type = "link_different", 1, 0)) AS ?overridden)
WHERE {
  GRAPH <urn:atom:auth-res:graph:decisions> {
    ?d ahg:decisionType        ?type ;
       ahg:onMention            ?m ;
       <http://www.w3.org/ns/prov#wasAssociatedWith> ?archivist .
    FILTER (?type IN ("link", "link_different"))
  }
}
GROUP BY ?archivist
ORDER BY DESC(?overridden)
```

A high override rate is a quality signal for the scoring weights, not the archivist. If the system keeps surfacing the wrong top candidate, the evaluator weights need a look.

### What evidence did this decision rest on

```sparql
PREFIX ahg: <https://theahg.co.za/ns/auth-res#>

SELECT ?evidence ?candidates WHERE {
  GRAPH <urn:atom:auth-res:graph:decisions> {
    << ahg:mention/24  ahg:resolvedTo  ?actor >>
        ahg:supportedBy        ahg:decision/42 ;
        ahg:evidenceSnapshot   ?evidence ;
        ahg:candidatesVisible  ?candidates .
  }
}
```

Returns the frozen JSON snapshots from the decision row. Use this to defend the decision later: "this is exactly what the archivist saw on screen on 2026-05-19".

### Per-actor field-provenance audit (FOIA query)

```sparql
PREFIX auth_res: <https://psis.theahg.co.za/ontology/auth-res#>
PREFIX prov: <http://www.w3.org/ns/prov#>

SELECT ?field ?source ?sourceUri ?at WHERE {
  GRAPH <urn:atom:auth-res:graph:field-provenance> {
    << <https://psis.theahg.co.za/actor/912515> auth_res:hasField ?field >>
        auth_res:source ?source ;
        prov:generatedAtTime ?at .
    OPTIONAL {
      << <https://psis.theahg.co.za/actor/912515> auth_res:hasField ?field >>
          prov:wasDerivedFrom ?sourceUri .
    }
  }
}
```

## Append-only policy

Decisions provenance is append-only. The engine never deletes a decision triple. If a decision needs to be revised, record a new decision; both are visible in the audit.

To wipe a graph entirely (staging only):

```sparql
DROP GRAPH <urn:atom:auth-res:graph:decisions>
```

Do not do this in production. There are no backups inside Fuseki itself; the dataset has a host-level snapshot but rolling back loses every provenance write since the last snapshot.

## See also

- `authority-resolution-user-guide` - the high-level overview
- `authority-resolution-review-screen` - where decisions are made
- `authority-resolution-create-new-authority` - where field-provenance is created
- `authority-resolution-cli-tasks` - the `auth-res:write-provenance` and `auth-res:status` tasks
