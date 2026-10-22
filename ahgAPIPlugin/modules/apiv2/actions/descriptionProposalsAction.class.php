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
 *                       "evidence": "...", "sources": ["https://..."]}, ...],
 *        "fields":   [{"field": "scope_and_content", "text": "...", "evidence": "...", "sources": [...]}],
 *        "source": "optional label, e.g. glam-mcp"}
 *
 * Entities go to AI > NER review, field texts to AI > Suggest review; the
 * evidence and sources are shown to the reviewer.
 *
 * Staff only (framework scope rule) and AtoM's own update permission on the
 * record. Values already pending or linked for this record are skipped.
 */
class apiv2DescriptionProposalsAction extends AhgApiController
{
    private const TYPES = ['PERSON', 'ORG', 'GPE', 'DATE'];
    private const MAX = 50;
    /** information_object_i18n fields a proposal may target. */
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
        if (!$entities && !$fields) {
            return $this->error(400, 'Bad Request', 'entities (a list of {type, value}) or fields (a list of {field, text}) is required');
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
            $fieldRows[] = ['field' => $field, 'text' => $text, 'evidence' => mb_substr(trim((string) ($f['evidence'] ?? '')), 0, 4000), 'sources' => self::sources($f['sources'] ?? [])];
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
            $clean[$type."\0".mb_strtolower($value)] = ['type' => $type, 'value' => $value, 'confidence' => $confidence,
                'evidence' => mb_substr(trim((string) ($e['evidence'] ?? '')), 0, 4000), 'sources' => self::sources($e['sources'] ?? [])];
        }

        // Skip what this record already has waiting for review or already linked.
        $existing = DB::table('ahg_ner_entity')->where('object_id', (int) $objectId)
            ->whereIn('status', ['pending', 'linked', 'approved'])
            ->get(['entity_type', 'entity_value'])
            ->map(fn ($r) => strtoupper($r->entity_type)."\0".mb_strtolower(trim($r->entity_value)))->flip()->all();
        $new = array_diff_key($clean, $existing);

        $queued = [];
        $userId = (int) $this->getUser()->getAttribute('user_id');
        $label = preg_replace('/[^a-z0-9_-]/i', '', (string) ($data['source'] ?? 'api')) ?: 'api';
        $now = date('Y-m-d H:i:s');
        if ($new) {
            DB::transaction(function () use ($new, $objectId, $userId, $label, $now, &$queued) {
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
                    if ('' !== $e['evidence'] || $e['sources']) {
                        DB::table('ahg_ner_entity_evidence')->insert([
                            'entity_id' => $entityId, 'evidence' => $e['evidence'] ?: null,
                            'sources' => $e['sources'] ? json_encode($e['sources']) : null,
                            'created_by' => $userId, 'created_at' => $now,
                        ]);
                    }
                    $queued[] = ['type' => $e['type'], 'value' => $e['value']];
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
            try {
                $promoted = (new \AtomFramework\Services\AuthorityResolution\PromoteToMentionService(
                    new \AtomFramework\Services\AuthorityResolution\ContextDerivationService()
                ))->promoteAllForObject((int) $objectId);
            } catch (\Throwable $e) {
                error_log('apiv2 proposals: promotion to authority resolution failed for object '.$objectId.': '.$e->getMessage());
            }
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
                DB::table('ahg_description_suggestion')->insert([
                    'object_id' => (int) $objectId,
                    'suggested_text' => $f['text'],
                    'existing_text' => $current[$f['field']] ?? null,
                    'source_data' => json_encode(['field' => $f['field'], 'evidence' => $f['evidence'], 'sources' => $f['sources'], 'proposed_via' => $label]),
                    'status' => 'pending',
                    'model_used' => mb_substr($label, 0, 100),
                    'created_by' => $userId ?: null,
                    'created_at' => $now,
                ]);
                $fieldsQueued[] = ['field' => $f['field']];
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
            'review_url' => ($base ?: '').'/ai/ner/review',
            'description_review_url' => ($base ?: '').'/ai/suggest/review',
        ], ($queued || $fieldsQueued) ? 201 : 200);
    }
}
