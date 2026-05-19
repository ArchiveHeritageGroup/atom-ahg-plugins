# Authority Resolution - Creating a New Authority Record

When none of the ranked candidates fits a mention, you click **Create new** on the review screen. The engine opens a pre-fill wizard that queries every enabled external authority source, merges the results by precedence, and presents a form pre-populated with values that you can accept, override, or skip. This article covers the wizard's behaviour on the AtoM Heratio side; Laravel Heratio renders the same form with Tailwind 4 styling instead of Bootstrap 5.

## Route

```
GET  /admin/authorityResolution/:id/create-new          (the wizard)
POST /admin/authorityResolution/:id/create-new-submit   (commit)
```

Both require the `editor` credential.

## What gets pre-filled

The pre-fill engine runs every adapter you have enabled on the lookup settings screen against the mention's surface form and entity type. Results are merged by precedence (configurable via `authority_resolution.lookup.precedence`). The first non-empty value for each field wins.

PERSON / ORG fields:

- `authorized_form_of_name`
- `dates_of_existence`
- `history`
- `places`
- `mandates`
- `functions`
- `legal_status`
- `descriptive_standard` (defaults to ISAAR-CPF; not provenance-tracked)
- `source_culture` (defaults to `en`; not provenance-tracked)

PLACE fields:

- `name`
- `latitude`
- `longitude`

Each pre-filled field that came from an external source carries a coloured provenance badge next to the label, showing the source, licence, and retrieval timestamp.

## The seven external adapters

| Source | API | Licence | Status |
|---|---|---|---|
| VIAF | VIAF AutoSuggest | CC0-1.0 | live |
| Wikidata | wbsearchentities + wbgetentities | CC0-1.0 | live |
| GeoNames | searchJSON | CC BY 4.0 | live (requires username) |
| TGN | Getty TGN SPARQL | ODbL 1.0 | stub |
| GND | lobid (DNB GND) | CC0-1.0 | stub |
| ISNI | ISNI SRU | ISNI ToU | stub |
| SAGNC | South African Geographical Names Council | TBD | stub |

All adapters are disabled by default. The engine never makes outbound HTTP calls until you opt in via `/admin/authorityResolution/settings/lookup`. With every source disabled, the form pre-fills only from the mention itself and from the mention-context derivations (e.g. `places` from the nearby_places packet).

## Settings page

`/admin/authorityResolution/settings/lookup` (admin-only) lets you:

- Toggle each source on or off (37 settings rows seeded by `database/seed_lookup_settings.sql`)
- Set the rate limit per source (read but not yet enforced; the abstract adapter relies on cache + caller frequency for now)
- Set the cache TTL per source
- Set the licence note and licence URL per source (auto-attributed in the provenance graph)
- Set the GeoNames username (mandatory for GeoNames)
- Set the precedence order as a JSON array

## Filling out the form

Each form field renders via the `_prefillField.php` partial with:

- The visible input (text, textarea, or number)
- The provenance badge (if the value came from an external source)
- Hidden `_provenance[<field>][source|uri|license|license_url|at]` inputs that replay the original attribution into Fuseki on submit

You can:

- **Accept** the pre-filled value (leave it as-is and submit)
- **Override** the value (type a new one; the hidden provenance inputs are rewritten to mark the override)
- **Skip** the field (clear it; no triple is written for that field)

ISAAR-CPF mandatory fields are enforced for persons and orgs:

- `authorized_form_of_name`
- `dates_of_existence`
- `history`

For places, the name is mandatory. If you provide one of (latitude, longitude) you must provide the other; both are stored as a `lat,lng` pair on `term_i18n.description`, matching the parser used by the review screen's coordinate resolver.

## What happens on submit

`executeCreateNewSubmit` performs the following, all inside a single database transaction so a partial failure leaves no orphaned object or slug row:

1. Validate POST and load the mention.
2. Normalise `entity_type` (GPE / LOC / ISAD_PLACE collapse to PLACE).
3. Collect form fields and per-field provenance from the hidden inputs.
4. `AuthorityCreator::createPerson()`, `createOrg()`, or `createPlace()` inserts the rows via Qubit class-table inheritance (object -> actor -> actor_i18n -> slug, or object -> term -> term_i18n -> slug) and returns the new authority id.
5. `FieldProvenanceWriter::writeForCreation()` writes one reified RDF-Star assertion per accepted pre-filled field to the field-provenance named graph in Fuseki.
6. `DecisionRecorder::record($mentionId, 'create_new', $userId, ['authority_id' => $newId])` inserts the decision audit row, advances `ahg_mention.state` to `new_record_created`, and fires the decision provenance writer.
7. Flash a success message and redirect to the next pending mention (or back to the queue index).

## Qubit class-table inheritance

The AtoM data model splits a single authority across multiple tables. The creator uses these constants:

- `actor.entity_type_id`: 132 (PERSON) or 131 (CORPORATE_BODY)
- `actor.parent_id`: 3 (QubitActor::ROOT_ID)
- `term.taxonomy_id`: 42 (places taxonomy)
- `term.parent_id`: 110 (QubitTerm::ROOT_ID)
- `actor.source_standard`: defaults to `ISAAR-CPF`
- `actor_i18n.culture` and `term_i18n.culture`: default to `en`

Slug generation is hand-rolled: `iconv()` to ASCII, lowercase, replace non-alphanumeric with `-`, length-cap at 240 chars, numeric suffix on collision, random hex fallback after 1000 attempts. The slug table has a UNIQUE index so a collision at insert time surfaces as a SQL error.

## Field-provenance graph

Each accepted pre-filled field becomes one reified RDF-Star triple in `urn:atom:auth-res:graph:field-provenance`:

```turtle
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX auth_res: <https://psis.theahg.co.za/ontology/auth-res#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

INSERT DATA {
  GRAPH <urn:atom:auth-res:graph:field-provenance> {
    << <https://psis.theahg.co.za/actor/912515> auth_res:hasField "authorized_form_of_name" >>
        auth_res:fieldValue "Frederick Douglass, 1818-1895" ;
        prov:wasDerivedFrom <https://viaf.org/viaf/10088> ;
        prov:generatedAtTime "2026-05-19T17:42:31Z"^^xsd:dateTime ;
        auth_res:source "viaf" ;
        auth_res:licence "CC0-1.0" ;
        auth_res:licenceUrl <https://creativecommons.org/publicdomain/zero/1.0/> .
  }
}
```

This shape is described in detail in `authority-resolution-provenance`.

## Smoke test results (mention 138, "Frederick Douglass")

- **All sources disabled:** 0 external hits; merged fields populated only from `mention` (authorized_form_of_name) and `mention_context` (places).
- **VIAF + Wikidata on:** 4 + 10 hits. VIAF id 10088 wins precedence; name resolves to "Frederick Douglass, 1818-1895".
- **VIAF off, Wikidata on:** name source falls back to wikidata; merged fields still populated.
- **Full create:** new `actor.id = 912515`, slug `frederick-douglass-1818-1895`, 8 reified field assertions emitted; 38 metadata triples in `urn:atom:auth-res:graph:field-provenance`; Fuseki returns HTTP 204 on INSERT. Decision row id 5 written with state `new_record_created`.

## Limitations (current build)

- The `rate_limit` setting per adapter is read but not yet enforced.
- No preview step on the create form; the flow goes straight from pre-fill to commit.
- The TGN, GND, ISNI, and SAGNC adapters are stubs; they return empty result sets until their adapters are completed.

## See also

- `authority-resolution-user-guide` - the high-level overview
- `authority-resolution-review-screen` - where the Create-new button lives
- `authority-resolution-provenance` - the RDF-Star audit trail for accepted pre-fills
- `ahgauthorityresolutionplugin` - full technical reference
