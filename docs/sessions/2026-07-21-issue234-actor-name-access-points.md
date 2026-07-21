# 2026-07-21 - Issue #234: actor name-access-point resolver (ahgFormsPlugin)

**Release:** atom-ahg-plugins v3.79.104
**Instances:** PSIS (`/usr/share/nginx/archive`) + Wits Archaeology (`/usr/share/nginx/archeology`) - both live
**Issue:** [#234](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/234)

## Context

#234 asked for four resolvers so `FormSubmitService` could write AtoM's structural
fields, not just direct columns / properties / notes. Three shipped in v3.79.95
(`term_id` FK columns, `event` creation rows, `object_term_relation` subjects).
The fourth - actor **name access points** - was left open, so `contributor`
(Dublin Core Simple) and `personsDepicted` (Photo Collection Item) rendered and
validated but persisted nothing; they came back in the submission's `unmapped`
list and had to be completed on the normal edit page.

## What shipped

New `actor_relation` target table in the mapping schema:

- Resolves each posted value to an actor - numeric id, then existing
  `actor_i18n.authorized_form_of_name`, else create-or-link via
  `WriteServiceFactory::actor()`.
- Writes `relation` rows in AtoM's own direction: `subject_id` = description,
  `object_id` = actor, `type_id` = 161 (`QubitTerm::NAME_ACCESS_POINT_ID`).
  `target_type_id` carries the relation type, so other actor relation types can
  reuse the same target.
- Multi-value aware (pipe/comma lists and posted arrays), de-duplicated on value.
- **Mirrors base AtoM:** an actor already attached to the description through an
  event (creator, accumulator, ...) is not duplicated as a name access point -
  same rule as `QubitInformationObject::addName()`.
- Re-submitting onto an existing record does not create duplicate relations.
- `transformation_config` `{"create":false}` restricts a field to existing
  authority records rather than minting new ones.

Also fixed alongside it: unresolvable **term** and **actor** values are now
reported in the submission's `unmapped[]` instead of being dropped silently.
`writeTermRelations()` / `writeActorRelations()` return the offending field names
and `submit()` merges them. This is what #234's acceptance criteria asked for
("unresolvable values fall back gracefully - reported, base record still created").

`resolveOrCreateActor()` became `resolveActor(..., bool $create = true)`.

## Files

| File | Change |
|---|---|
| `ahgFormsPlugin/lib/Services/FormSubmitService.php` | `actor_relation` handling, `writeActorRelations()`, `uniqueByValue()`, unresolved reporting, `resolveActor()` |
| `ahgFormsPlugin/database/install.sql` | 2 seed mappings (contributor, personsDepicted) |
| `ahgFormsPlugin/database/migrations/003_actor_name_access_points.sql` | NEW - idempotent equivalent for existing installs |

Mapping count 69 -> 71.

## Verification

Transactional-rollback harness (same pattern used for v3.79.94/95), 13/13 pass,
database confirmed unchanged afterwards:

- migration 003 parses to 2 statements, inserts 2 mappings, is idempotent
- DC submit: contributor becomes a name access point; creator stays on the
  creation event and is **not** duplicated as an access point; nothing unmapped
- Photo submit: 2 `personsDepicted` values become 2 access points; photographer
  not duplicated; nothing unmapped
- re-submit onto the same record creates no duplicate relations
- `{"create":false}` reports the field unmapped and creates no actor

Harness note: bootstrap via a narrow `Qubit*`/`Zend_*` autoloader over
`lib/model` + `qbAclPlugin/lib/vendor`, **not** a Symfony app bootstrap - the
write services only need `QubitInformationObject::ROOT_ID`, and building an app
configuration from CLI risks the shared compiled cache.

## Still open on #234

Deferred by design, unchanged from the v3.79.95 note - these report as unmapped
and are completed on the edit page:

- `acquisitionType` / `processingPriority` / `receivedExtentUnit` - taxonomies
  not populated on these installs; `term_id` resolves once they are
- `repository` FK (different resolution path)
- `language` / `script` (serialized property, not a term FK)

With the actor resolver done, all four resolvers proposed in #234 now exist.
