<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * POST /api/v2/descriptions/:slug/proposals
 *
 * Queue proposed access points and dates for one description, for an archivist
 * to review in AI > Review (ahgAIPlugin), exactly like entities found by the NER
 * extractor. Nothing on the description changes until a person approves.
 *
 * Body: {"entities": [{"type": "PERSON|ORG|GPE|DATE", "value": "...", "confidence": 0.8,
 *                       "evidence": "...", "sources": ["https://..."],
 *                       "authority": {"dates_of_existence": "...", "history": "...", "places": "..."},
 *                       "actor_id": 123, "term_id": 456, "link_reason": "..."}, ...],
 *        "fields":   [{"field": "scope_and_content", "text": "...", "culture": "en", "evidence": "...", "sources": [...]}],
 *        "transcripts": [{"text": "...", "digital_object_id": 123, "evidence": "...", "sources": [...]}],
 *        "relationships": [{"subject_name": "...", "subject_actor_id": 123, "relation": "is the spouse of",
 *                            "object_name": "...", "object_actor_id": 456, "date": "1898",
 *                            "start_date": "1898-05-02", "end_date": "...", "evidence": "...", "sources": [...]}],
 *        "source": "optional label, e.g. glam-mcp"}
 *
 * Entities go to AI > NER review, field texts to AI > Suggest review,
 * relationships to AI > Relationship review; a field with a culture other than
 * the record's is a translation into that language. A transcript is the text of
 * the record's file (a page image with no text layer); on approval it is stored
 * where GET /descriptions/:slug/text and search read it. Both go to Suggest
 * review, a transcript with the page image beside it; the evidence and sources are
 * shown to the reviewer. A relationship reads subject-relation-object, the
 * relation being a term (name or id) of the Actor relation type taxonomy. Each
 * side is an existing authority record (*_actor_id) or a person or
 * organisation proposed for this record in the same or an earlier request.
 *
 * actor_id (PERSON, ORG) or term_id (GPE, a place) says which existing
 * authority record the name most likely is; Authority Resolution shows it as
 * the first candidate, marked as proposed, with link_reason.
 *
 * Staff only (framework scope rule) and AtoM's own update permission on the
 * record. Values already pending or linked for this record are skipped.
 */
class apiv2DescriptionProposalsAction extends AhgApiController
{
    private const TYPES = ['PERSON', 'ORG', 'GPE', 'DATE'];
    private const MAX = 50;
    private const PLACES_TAXONOMY_ID = 42;
    private const RELATION_TYPE_TAXONOMY_ID = 55;
    private const RELATION_TYPE_ROOT_ID = 110;
    private const MAX_RELATIONSHIPS = 20;
    /** information_object_i18n fields a proposal may target. */
    /** Authority details a person or organisation proposal may carry, with their length limits. */
    private const AUTHORITY_FIELDS = ['dates_of_existence' => 255, 'history' => 8000, 'places' => 1000];
    private const FIELDS = ['title', 'scope_and_content', 'archival_history', 'extent_and_medium', 'arrangement', 'access_conditions', 'reproduction_conditions'];

    private static function sources($list): array
    {
        $out = [];
        foreach (array_slice(is_array($list) ? $list : [], 0, 10) as $u) {
            $u = trim((string) $u);
            if (mb_strlen($u) <= 500 && preg_match('#^https?://#i', $u)) {
                $out[] = $u;
            }
        }

        return $out;
    }

    /** An authority record a proposal may name: exists, is a person/organisation/family and is not hidden. */
    private static function actorOk(int $id): bool
    {
        return $id > 1 && DB::table('actor')->where('id', $id)->whereNotNull('entity_type_id')->exists()
            && !(class_exists('\\AhgActorManage\\Services\\ActorVisibilityService')
                && \AhgActorManage\Services\ActorVisibilityService::isHiddenFromPublic($id));
    }

    /** A specific Actor relation type ("is the spouse of"), by id or name; the four categories are refused. */
    private static function relationType($relation): ?int
    {
        $q = DB::table('term as t')->where('t.taxonomy_id', self::RELATION_TYPE_TAXONOMY_ID)
            ->where('t.parent_id', '!=', self::RELATION_TYPE_ROOT_ID);
        if (is_int($relation) || ctype_digit((string) $relation)) {
            return $q->where('t.id', (int) $relation)->value('t.id');
        }

        return $q->join('term_i18n as ti', 'ti.id', '=', 't.id')
            ->whereRaw('LOWER(ti.name) = ?', [mb_strtolower(trim((string) $relation))])->value('t.id');
    }

    private static function isoDate($v): ?string
    {
        $v = trim((string) $v);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && checkdate((int) substr($v, 5, 2), (int) substr($v, 8, 2), (int) substr($v, 0, 4)) ? $v : null;
    }

    public function POST($request, $data = null)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $slug = (string) $request->getParameter('slug');
        $objectId = DB::table('slug')->where('slug', $slug)->value('object_id');
        $resource = $objectId ? \QubitInformationObject::getById((int) $objectId) : null;
        if (!$resource) {
            return $this->error(404, 'Not Found', 'Description not found');
        }
        if (!\AtomExtensions\Services\AclService::check($resource, 'update')) {
            return $this->error(403, 'Forbidden', 'Not authorised to edit this description');
        }

        $entities = is_array($data['entities'] ?? null) ? $data['entities'] : [];
        $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
        $relationships = is_array($data['relationships'] ?? null) ? $data['relationships'] : [];
        $transcripts = is_array($data['transcripts'] ?? null) ? $data['transcripts'] : [];
        if (!$entities && !$fields && !$relationships && !$transcripts) {
            return $this->error(400, 'Bad Request', 'entities (a list of {type, value}), fields (a list of {field, text}), transcripts or relationships is required');
        }
        if (count($relationships) > self::MAX_RELATIONSHIPS) {
            return $this->error(400, 'Bad Request', 'At most '.self::MAX_RELATIONSHIPS.' relationships per request');
        }
        $relRows = [];
        foreach ($relationships as $i => $r) {
            $row = ['type_id' => self::relationType($r['relation'] ?? '')];
            if (!$row['type_id']) {
                return $this->error(400, 'Bad Request', "Relationship $i: relation must be an Actor relation type such as \"is the spouse of\" or \"is the child of\" (see /api/v2/taxonomies/55/terms)");
            }
            foreach (['subject', 'object'] as $side) {
                $name = trim(preg_replace('/\s+/', ' ', (string) ($r[$side.'_name'] ?? '')));
                $actorId = (int) ($r[$side.'_actor_id'] ?? 0);
                if ($actorId && !self::actorOk($actorId)) {
                    return $this->error(400, 'Bad Request', "Relationship $i: {$side}_actor_id is not an authority record");
                }
                if ('' === $name && $actorId) {
                    $name = (string) DB::table('actor_i18n')->where('id', $actorId)->orderByRaw("culture = 'en' DESC")->value('authorized_form_of_name');
                }
                if ('' === $name || mb_strlen($name) > 500) {
                    return $this->error(400, 'Bad Request', "Relationship $i: {$side}_name (1-500 characters) or {$side}_actor_id is required");
                }
                $row[$side.'_name'] = $name;
                $row[$side.'_actor_id'] = $actorId ?: null;
            }
            $row += [
                'date_text' => mb_substr(trim((string) ($r['date'] ?? '')), 0, 255) ?: null,
                'start_date' => self::isoDate($r['start_date'] ?? ''), 'end_date' => self::isoDate($r['end_date'] ?? ''),
                'evidence' => mb_substr(trim((string) ($r['evidence'] ?? '')), 0, 4000) ?: null,
                'sources' => ($src = self::sources($r['sources'] ?? [])) ? json_encode($src) : null,
            ];
            $relRows[] = $row;
        }
        if ($relRows && !DB::schema()->hasTable('ahg_relationship_proposal')) {
            return $this->error(501, 'Not Implemented', 'Relationship proposals need the ahg_relationship_proposal table (ahgAIPlugin install.sql)');
        }
        if (count($fields) > 10) {
            return $this->error(400, 'Bad Request', 'At most 10 field proposals per request');
        }
        $fieldRows = [];
        foreach ($fields as $i => $f) {
            $field = (string) ($f['field'] ?? '');
            $text = trim((string) ($f['text'] ?? ''));
            if (!in_array($field, self::FIELDS, true) || '' === $text || mb_strlen($text) > ('title' === $field ? 1024 : 20000)) {
                return $this->error(400, 'Bad Request', "Field $i: field must be one of ".implode(', ', self::FIELDS).', text 1-20000 characters (title 1-1024)');
            }
            // A culture other than the record's makes it a translation into that language.
            $culture = trim((string) ($f['culture'] ?? ''));
            if ('' !== $culture && !preg_match('/^[a-z]{2,3}(_[A-Za-z]{2,4})?$/', $culture)) {
                return $this->error(400, 'Bad Request', "Field $i: culture must be a language code such as en, af or nl");
            }
            $fieldRows[] = ['field' => $field, 'text' => $text, 'culture' => $culture ?: null, 'evidence' => mb_substr(trim((string) ($f['evidence'] ?? '')), 0, 4000), 'sources' => self::sources($f['sources'] ?? [])];
        }
        if (count($transcripts) > 5) {
            return $this->error(400, 'Bad Request', 'At most 5 transcripts per request');
        }
        foreach ($transcripts as $i => $t) {
            $text = trim((string) ($t['text'] ?? ''));
            // ahg_description_suggestion.suggested_text is a TEXT column (64 KB).
            if ('' === $text || strlen($text) > 65000) {
                return $this->error(400, 'Bad Request', "Transcript $i: text is required, at most 65,000 bytes; propose a long document page by page");
            }
            $doId = DB::table('digital_object')->where('object_id', (int) $objectId)->whereNull('parent_id')
                ->when(!empty($t['digital_object_id']), fn ($q) => $q->where('id', (int) $t['digital_object_id']))
                ->orderBy('id')->value('id');
            if (!$doId) {
                return $this->error(400, 'Bad Request', "Transcript $i: ".(empty($t['digital_object_id']) ? 'this record has no file to transcribe' : 'digital_object_id is not a file of this record'));
            }
            $fieldRows[] = ['field' => 'transcript', 'text' => $text, 'digital_object_id' => (int) $doId,
                'evidence' => mb_substr(trim((string) ($t['evidence'] ?? '')), 0, 4000), 'sources' => self::sources($t['sources'] ?? [])];
        }
        // The review queues belong to ahgAIPlugin; without it there is nowhere to propose to.
        if (($entities || $fieldRows) && !DB::schema()->hasTable('ahg_ner_entity')) {
            return $this->error(501, 'Not Implemented', 'Proposals need the AI plugin (ahgAIPlugin), whose review screens they go to');
        }
        if (count($entities) > self::MAX) {
            return $this->error(400, 'Bad Request', 'At most '.self::MAX.' entities per request');
        }

        $clean = [];
        foreach ($entities as $i => $e) {
            $type = strtoupper(trim((string) ($e['type'] ?? '')));
            $value = trim(preg_replace('/\s+/', ' ', (string) ($e['value'] ?? '')));
            if (!in_array($type, self::TYPES, true) || '' === $value || mb_strlen($value) > 500) {
                return $this->error(400, 'Bad Request', "Entity $i: type must be one of ".implode(', ', self::TYPES).', value 1-500 characters');
            }
            $confidence = isset($e['confidence']) ? max(0.0, min(1.0, (float) $e['confidence'])) : null;
            // People and organisations may carry what a new authority record
            // needs (ISAAR dates of existence, history, places): the Authority
            // Resolution create form is pre-filled from it.
            $authority = [];
            if (in_array($type, ['PERSON', 'ORG'], true) && is_array($e['authority'] ?? null)) {
                foreach (self::AUTHORITY_FIELDS as $k => $max) {
                    $v = trim((string) ($e['authority'][$k] ?? ''));
                    if ('' !== $v) {
                        $authority[$k] = mb_substr($v, 0, $max);
                    }
                }
            }
            // A proposed link to an existing authority record, checked here so a
            // wrong id fails the request instead of reaching the reviewer.
            $linkId = (int) ('GPE' === $type ? ($e['term_id'] ?? 0) : ($e['actor_id'] ?? 0));
            if ($linkId > 0) {
                if ('DATE' === $type) {
                    return $this->error(400, 'Bad Request', "Entity $i: a date cannot link to an authority record");
                }
                $exists = 'GPE' === $type
                    ? DB::table('term')->where('id', $linkId)->where('taxonomy_id', self::PLACES_TAXONOMY_ID)->exists()
                    : self::actorOk($linkId);
                if (!$exists) {
                    return $this->error(400, 'Bad Request', "Entity $i: ".('GPE' === $type ? 'term_id is not a place' : 'actor_id is not an authority record'));
                }
                $authority['link'] = ['kind' => 'GPE' === $type ? 'term' : 'actor', 'id' => $linkId,
                    'reason' => mb_substr(trim((string) ($e['link_reason'] ?? '')), 0, 1000)];
            }
            $clean[$type."\0".mb_strtolower($value)] = ['type' => $type, 'value' => $value, 'confidence' => $confidence,
                'evidence' => mb_substr(trim((string) ($e['evidence'] ?? '')), 0, 4000), 'sources' => self::sources($e['sources'] ?? []),
                'authority' => $authority];
        }

        // Skip what this record already has waiting for review or already linked.
        $existing = DB::table('ahg_ner_entity')->where('object_id', (int) $objectId)
            ->whereIn('status', ['pending', 'linked', 'approved'])
            ->get(['entity_type', 'entity_value'])
            ->map(fn ($r) => strtoupper($r->entity_type)."\0".mb_strtolower(trim($r->entity_value)))->flip()->all();
        $new = array_diff_key($clean, $existing);

        $queued = [];
        $newEntityIds = [];
        $userId = (int) $this->getUser()->getAttribute('user_id');
        $label = preg_replace('/[^a-z0-9_-]/i', '', (string) ($data['source'] ?? 'api')) ?: 'api';
        $now = date('Y-m-d H:i:s');
        // ponytail: the authority column is added by install.sql on new installs and
        // by hand on existing ones; until then authority details are dropped.
        $hasAuthority = DB::schema()->hasColumn('ahg_ner_entity_evidence', 'authority');
        if ($new) {
            DB::transaction(function () use ($new, $objectId, $userId, $label, $now, $hasAuthority, &$queued) {
                $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
                    'object_id' => (int) $objectId,
                    // Who proposed it, for the audit: e.g. "glam-mcp:u900148".
                    'backend_used' => mb_substr($label.':u'.$userId, 0, 50),
                    'status' => 'completed',
                    'entity_count' => count($new),
                    'extracted_at' => $now,
                ]);
                foreach ($new as $e) {
                    $entityId = DB::table('ahg_ner_entity')->insertGetId([
                        'extraction_id' => $extractionId,
                        'object_id' => (int) $objectId,
                        'entity_type' => $e['type'],
                        'entity_value' => $e['value'],
                        'confidence' => $e['confidence'],
                        'status' => 'pending',
                        'created_at' => $now,
                    ]);
                    $authority = $hasAuthority && $e['authority'] ? $e['authority'] : [];
                    if ('' !== $e['evidence'] || $e['sources'] || $authority) {
                        DB::table('ahg_ner_entity_evidence')->insert([
                            'entity_id' => $entityId, 'evidence' => $e['evidence'] ?: null,
                            'sources' => $e['sources'] ? json_encode($e['sources']) : null,
                            'created_by' => $userId, 'created_at' => $now,
                        ] + ($authority ? ['authority' => json_encode($authority, JSON_UNESCAPED_UNICODE)] : []));
                    }
                    $newEntityIds[] = $entityId;
                    $queued[] = ['type' => $e['type'], 'value' => $e['value']] + (isset($authority['link']) ? ['linked_to' => $authority['link']['kind'].':'.$authority['link']['id']] : []);
                }
            });
        }

        // With the Authority Resolution plugin on, NER review's Review button opens
        // the resolution queue, which lists "mentions", not NER entities; the AI
        // pipeline promotes each entity it extracts. Do the same so proposals are
        // reviewable. Idempotent, covers anything still unpromoted on the record;
        // the promoter handles people, organisations and places (dates stay in
        // the NER dialog).
        $promoted = 0;
        $arLib = \sfConfig::get('sf_plugins_dir').'/ahgAuthorityResolutionPlugin/lib/Services/';
        // Same test the NER review page uses to send its Review button to the queue.
        if (in_array('authorityResolution', (array) \sfConfig::get('sf_enabled_modules', []), true)
            && is_file($arLib.'PromoteToMentionService.php')) {
            // Not autoloaded; the plugin's own actions require them the same way.
            require_once $arLib.'ContextDerivationService.php';
            require_once $arLib.'PromoteToMentionService.php';
            require_once $arLib.'Adapters/CandidateAdapterInterface.php';
            require_once $arLib.'Adapters/MysqlActorAdapter.php';
            require_once $arLib.'Adapters/MysqlTermAdapter.php';
            require_once $arLib.'CandidateGeneratorService.php';
            try {
                $promoted = (new \AtomFramework\Services\AuthorityResolution\PromoteToMentionService(
                    new \AtomFramework\Services\AuthorityResolution\ContextDerivationService()
                ))->promoteAllForObject((int) $objectId);
                // Candidates now, from the local authority records only, so the
                // reviewer does not open a mention with "no candidates" and a
                // proposed link is already first. The scheduled reprocess adds
                // Fuseki candidates and evidence scores later.
                if ($newEntityIds) {
                    $generator = new \AtomFramework\Services\AuthorityResolution\CandidateGeneratorService([
                        new \AtomFramework\Services\AuthorityResolution\Adapters\MysqlActorAdapter(),
                        new \AtomFramework\Services\AuthorityResolution\Adapters\MysqlTermAdapter(),
                    ]);
                    foreach (DB::table('ahg_mention')->whereIn('ner_entity_id', $newEntityIds)->pluck('id') as $mentionId) {
                        $generator->generate((int) $mentionId);
                    }
                }
            } catch (\Throwable $e) {
                error_log('apiv2 proposals: promotion to authority resolution failed for object '.$objectId.': '.$e->getMessage());
            }
        }

        // Relationships go to AI > Relationship review. A side without an authority
        // record must be a person or organisation proposed for this record (now or
        // earlier): the archivist links that name first, then approves.
        $relQueued = [];
        $relSkipped = 0;
        foreach ($relRows as $i => $row) {
            foreach (['subject', 'object'] as $side) {
                $row[$side.'_entity_id'] = $row[$side.'_actor_id'] ? null : DB::table('ahg_ner_entity')
                    ->where('object_id', (int) $objectId)->whereIn('entity_type', ['PERSON', 'ORG'])->where('status', '!=', 'rejected')
                    ->whereRaw('LOWER(entity_value) = ?', [mb_strtolower($row[$side.'_name'])])->orderByDesc('id')->value('id');
                if (!$row[$side.'_actor_id'] && !$row[$side.'_entity_id']) {
                    $relQueued[] = ['subject' => $row['subject_name'], 'object' => $row['object_name'], 'queued' => false,
                        'error' => "\"{$row[$side.'_name']}\" is neither an authority record ({$side}_actor_id) nor a person or organisation proposed for this record: propose it as an entity too"];

                    continue 2;
                }
            }
            $dup = DB::table('ahg_relationship_proposal')->where('object_id', (int) $objectId)->where('status', '!=', 'rejected')
                ->where('type_id', $row['type_id'])->whereRaw('LOWER(subject_name) = ?', [mb_strtolower($row['subject_name'])])
                ->whereRaw('LOWER(object_name) = ?', [mb_strtolower($row['object_name'])])->exists();
            if ($dup) {
                ++$relSkipped;

                continue;
            }
            DB::table('ahg_relationship_proposal')->insert($row + [
                'object_id' => (int) $objectId, 'status' => 'pending', 'proposed_via' => $label,
                'created_by' => $userId ?: null, 'created_at' => $now,
            ]);
            $relQueued[] = ['subject' => $row['subject_name'], 'object' => $row['object_name'], 'queued' => true];
        }

        // Field texts go to AI > Suggest review. Approval writes the field named in
        // source_data; an identical text already pending for that field is skipped.
        $fieldsQueued = [];
        $fieldsSkipped = 0;
        if ($fieldRows) {
            $current = (array) DB::table('information_object_i18n')->where('id', (int) $objectId)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())->first(self::FIELDS);
            foreach ($fieldRows as $f) {
                $dup = DB::table('ahg_description_suggestion')->where('object_id', (int) $objectId)->where('status', 'pending')
                    ->where('suggested_text', $f['text'])->exists();
                if ($dup) {
                    ++$fieldsSkipped;

                    continue;
                }
                if ('transcript' === $f['field']) {
                    $existingText = DB::table('property as p')->join('property_i18n as pi', 'pi.id', '=', 'p.id')
                        ->where('p.object_id', $f['digital_object_id'])->where('p.name', 'transcript')->value('pi.value');
                    // The page image for the reviewer: the reference copy, else the master if it is an image.
                    $img = DB::table('digital_object')->where('parent_id', $f['digital_object_id'])->where('usage_id', \QubitTerm::REFERENCE_ID)->first(['path', 'name'])
                        ?: DB::table('digital_object')->where('id', $f['digital_object_id'])->where('mime_type', 'like', 'image/%')->first(['path', 'name']);
                    $meta = ['kind' => 'transcript', 'digital_object_id' => $f['digital_object_id'], 'image' => $img ? $img->path.$img->name : null];
                } elseif ($f['culture']) {
                    $existingText = DB::table('information_object_i18n')->where('id', (int) $objectId)->where('culture', $f['culture'])->value($f['field']);
                    $meta = ['kind' => 'translation', 'field' => $f['field'], 'culture' => $f['culture']];
                } else {
                    $existingText = $current[$f['field']] ?? null;
                    $meta = ['field' => $f['field']];
                }
                DB::table('ahg_description_suggestion')->insert([
                    'object_id' => (int) $objectId,
                    'suggested_text' => $f['text'],
                    'existing_text' => null === $existingText ? null : mb_strcut((string) $existingText, 0, 65000),
                    'source_data' => json_encode($meta + ['evidence' => $f['evidence'], 'sources' => $f['sources'], 'proposed_via' => $label]),
                    'status' => 'pending',
                    'model_used' => mb_substr($label, 0, 100),
                    'created_by' => $userId ?: null,
                    'created_at' => $now,
                ]);
                $fieldsQueued[] = ['field' => $f['field']] + ($f['culture'] ?? null ? ['culture' => $f['culture']] : []);
            }
        }

        $base = rtrim((string) \sfConfig::get('app_siteBaseUrl', ''), '/');

        return $this->success([
            'slug' => $slug,
            'queued' => $queued,
            'skipped_existing' => count($clean) - count($new),
            'promoted_for_resolution' => $promoted,
            'fields_queued' => $fieldsQueued,
            'fields_skipped_existing' => $fieldsSkipped,
            'relationships' => $relQueued,
            'relationships_skipped_existing' => $relSkipped,
            'relationship_review_url' => ($base ?: '').'/ai/relationship/review',
            'review_url' => ($base ?: '').'/ai/ner/review',
            'description_review_url' => ($base ?: '').'/ai/suggest/review',
        ], ($queued || $fieldsQueued || array_filter(array_column($relQueued, 'queued'))) ? 201 : 200);
    }
}
