<?php

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__).'/DescriptionService.php';
require_once dirname(__FILE__).'/LlmService.php';
require_once dirname(__FILE__).'/NerService.php';
require_once dirname(__FILE__).'/AhgEmbeddedMetadataContextService.php';

/**
 * CatalogerService — AI cataloguer (#149 strand: AI-assisted cataloguing).
 *
 * Orchestrates the existing AI plumbing (record context + OCR + #113 embedded
 * metadata + NER entities) into a single LLM pass that drafts a *full*
 * ISAD(G)/Dublin Core record, which an archivist reviews field-by-field and
 * applies. Unlike DescriptionService (single scope-and-content field), this
 * produces a structured multi-field draft.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */
class CatalogerService
{
    /** ISAD(G)/DC text fields we let the archivist apply straight to the record. */
    public const TEXT_FIELDS = [
        'title' => 'Title',
        'scope_and_content' => 'Scope and content',
        'extent_and_medium' => 'Extent and medium',
        'archival_history' => 'Archival history',
        'arrangement' => 'Arrangement',
        'appraisal' => 'Appraisal',
    ];

    private function getCulture(): string
    {
        try {
            return \sfContext::getInstance()->getUser()->getCulture() ?: 'en';
        } catch (\Throwable $e) {
            return 'en';
        }
    }

    /**
     * Generate a full-record draft for an information object and persist it.
     *
     * @return array ['success'=>bool, 'draft_id'=>int, 'draft'=>array, 'current'=>array, 'entities'=>array, ...]
     */
    public function generateDraft(int $objectId, ?int $userId = null): array
    {
        $desc = new DescriptionService();
        $ctx = $desc->gatherContext($objectId);
        if (empty($ctx['success'])) {
            return ['success' => false, 'error' => $ctx['error'] ?? 'Could not gather record context'];
        }
        $data = $ctx['data'] ?? [];

        // #113 embedded metadata hints (camera, GPS, capture dates, software …).
        $embedded = '';
        try {
            $hints = (new AhgEmbeddedMetadataContextService())->forInformationObject($objectId);
            if (is_object($hints) && method_exists($hints, 'toPromptPrefix')
                && (!method_exists($hints, 'isEmpty') || !$hints->isEmpty())) {
                $embedded = trim((string) $hints->toPromptPrefix());
            }
        } catch (\Throwable $e) {
            // embedded metadata optional
        }

        $entities = $this->gatherEntities($objectId, $data);

        [$system, $user, $sources] = $this->buildPrompt($data, $embedded, $entities);

        $llm = new LlmService();
        $res = $llm->complete($system, $user, null, [
            'purpose' => 'cataloguing',
            'data_scope' => 'internal',
            'context_sources' => $sources,
        ]);
        if (empty($res['success']) || empty($res['text'])) {
            return ['success' => false, 'error' => $res['error'] ?? 'The AI service did not return a draft.'];
        }

        $draft = $this->parseDraft((string) $res['text']);
        if ($draft === null) {
            return ['success' => false, 'error' => 'The AI draft could not be parsed as structured fields.'];
        }

        $draftId = DB::table('ahg_catalog_draft')->insertGetId([
            'object_id' => $objectId,
            'draft_json' => json_encode($draft, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'model' => $res['model'] ?? null,
            'tokens_used' => (int) ($res['tokens_used'] ?? 0),
            'status' => 'draft',
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'draft_id' => (int) $draftId,
            'draft' => $draft,
            'current' => $this->currentValues($objectId),
            'entities' => $entities,
            'model' => $res['model'] ?? null,
        ];
    }

    public function getLatestDraft(int $objectId): ?array
    {
        $row = DB::table('ahg_catalog_draft')
            ->where('object_id', $objectId)
            ->orderByDesc('id')->first();
        if (!$row) {
            return null;
        }
        return [
            'draft_id' => (int) $row->id,
            'status' => $row->status,
            'model' => $row->model,
            'created_at' => $row->created_at,
            'draft' => json_decode($row->draft_json, true) ?: [],
            'current' => $this->currentValues($objectId),
            'entities' => $this->gatherEntities($objectId, []),
        ];
    }

    /**
     * Apply the accepted fields of the latest draft to the record.
     *
     * @param array $accepted e.g. ['title'=>1,'scope_and_content'=>1]
     * @return array ['success'=>bool,'applied'=>string[]]
     */
    public function applyDraft(int $objectId, array $accepted, int $userId): array
    {
        $row = DB::table('ahg_catalog_draft')
            ->where('object_id', $objectId)->orderByDesc('id')->first();
        if (!$row) {
            return ['success' => false, 'error' => 'No draft to apply.'];
        }
        $draft = json_decode($row->draft_json, true) ?: [];

        $i18n = [];
        foreach (array_keys(self::TEXT_FIELDS) as $field) {
            if (!empty($accepted[$field]) && isset($draft[$field]) && trim((string) $draft[$field]) !== '') {
                $i18n[$field] = (string) $draft[$field];
            }
        }

        if (empty($i18n)) {
            return ['success' => false, 'error' => 'No fields were selected to apply.'];
        }

        // Write the i18n text fields directly. (We avoid the framework write
        // service here because its I18N_FIELDS allow-list omits some i18n columns
        // such as extent_and_medium, which would be misrouted to the core table.)
        $culture = $this->getCulture();
        $exists = DB::table('information_object_i18n')
            ->where('id', $objectId)->where('culture', $culture)->exists();
        if ($exists) {
            DB::table('information_object_i18n')
                ->where('id', $objectId)->where('culture', $culture)->update($i18n);
        } else {
            DB::table('information_object_i18n')
                ->insert(array_merge($i18n, ['id' => $objectId, 'culture' => $culture]));
        }
        DB::table('object')->where('id', $objectId)->update(['updated_at' => date('Y-m-d H:i:s')]);

        DB::table('ahg_catalog_draft')->where('id', $row->id)->update([
            'status' => 'applied',
            'applied_fields' => json_encode(array_keys($i18n)),
            'applied_by' => $userId,
            'applied_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'applied' => array_keys($i18n)];
    }

    // ── internals ────────────────────────────────────────────────────────────

    /** Current record values for the side-by-side review. */
    private function currentValues(int $objectId): array
    {
        $culture = $this->getCulture();
        $i18n = DB::table('information_object_i18n')
            ->where('id', $objectId)->where('culture', $culture)->first()
            ?: DB::table('information_object_i18n')->where('id', $objectId)->where('culture', 'en')->first();
        $out = [];
        foreach (array_keys(self::TEXT_FIELDS) as $f) {
            $out[$f] = $i18n->$f ?? '';
        }
        return $out;
    }

    /** Pull already-extracted NER entities, grouped by type, for reference. */
    private function gatherEntities(int $objectId, array $data): array
    {
        $rows = DB::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->whereIn('status', ['pending', 'approved', 'linked'])
            ->select('entity_type', 'entity_value')
            ->distinct()->limit(200)->get();
        $grouped = ['persons' => [], 'organizations' => [], 'places' => [], 'dates' => []];
        $map = ['PERSON' => 'persons', 'PER' => 'persons', 'ORG' => 'organizations',
            'ORGANIZATION' => 'organizations', 'GPE' => 'places', 'LOC' => 'places',
            'PLACE' => 'places', 'DATE' => 'dates'];
        foreach ($rows as $r) {
            $bucket = $map[strtoupper((string) $r->entity_type)] ?? null;
            if ($bucket && trim((string) $r->entity_value) !== '') {
                $grouped[$bucket][] = $r->entity_value;
            }
        }
        foreach ($grouped as $k => $v) {
            $grouped[$k] = array_values(array_unique($v));
        }
        return $grouped;
    }

    /** Build the structured cataloguing prompt; returns [system, user, sources]. */
    private function buildPrompt(array $data, string $embedded, array $entities): array
    {
        $get = fn ($k) => trim((string) ($data[$k] ?? ''));
        $sources = [];
        foreach ($data as $v) {
            if (is_string($v) && trim($v) !== '') {
                $sources[] = $v;
            }
        }
        if ($embedded !== '') {
            $sources[] = $embedded;
        }

        $entityLines = [];
        foreach ($entities as $type => $vals) {
            if (!empty($vals)) {
                $entityLines[] = ucfirst($type).': '.implode(', ', array_slice($vals, 0, 40));
            }
        }

        $system = "You are an expert archival cataloguer working to the ISAD(G) standard. "
            ."Draft a catalogue record STRICTLY from the supplied source material (existing fields, OCR text, "
            ."embedded technical metadata, and named entities). Do NOT invent facts: if a field cannot be "
            ."supported by the sources, return an empty string for it. Write in a neutral, professional archival register. "
            ."Respond with a SINGLE JSON object and nothing else, using exactly these keys: "
            ."title, level_of_description, scope_and_content, extent_and_medium, archival_history, arrangement, appraisal, "
            ."date_display, date_start, date_end, creators (array), subjects (array), places (array), languages (array).";

        $parts = [];
        $parts[] = '# Existing record';
        foreach (['title' => 'Title', 'level' => 'Level', 'repository' => 'Repository',
            'creator' => 'Known creator', 'scope_and_content' => 'Existing scope and content',
            'extent_and_medium' => 'Existing extent'] as $k => $label) {
            if ($get($k) !== '') {
                $parts[] = "$label: ".$get($k);
            }
        }
        if ($get('ocr_text') !== '') {
            $parts[] = "\n# OCR / transcribed text\n".mb_substr($get('ocr_text'), 0, 6000);
        }
        if ($embedded !== '') {
            $parts[] = "\n# Embedded technical metadata\n".mb_substr($embedded, 0, 1500);
        }
        if (!empty($entityLines)) {
            $parts[] = "\n# Named entities already extracted\n".implode("\n", $entityLines);
        }
        $parts[] = "\nDraft the catalogue record now as the JSON object described.";

        return [$system, implode("\n", $parts), $sources];
    }

    /** Extract the first JSON object from an LLM response (tolerates code fences). */
    private function parseDraft(string $text): ?array
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?/i', '', $t);
        $t = preg_replace('/```$/', '', trim($t));
        $decoded = json_decode($t, true);
        if (!is_array($decoded)) {
            // fall back to the first {...} block
            if (preg_match('/\{.*\}/s', $t, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }
        if (!is_array($decoded)) {
            return null;
        }

        // Models sometimes emit placeholder text instead of leaving a field blank.
        $placeholders = ['n/a', 'na', 'none', 'unknown', 'not specified', 'not available',
            'not applicable', 'not provided', 'unspecified', 'null', '-'];
        $clean = function ($v) use ($placeholders) {
            $v = is_string($v) ? trim($v) : '';
            return in_array(mb_strtolower($v), $placeholders, true) ? '' : $v;
        };

        $norm = [];
        foreach (['title', 'level_of_description', 'scope_and_content', 'extent_and_medium',
            'archival_history', 'arrangement', 'appraisal', 'date_display', 'date_start', 'date_end'] as $k) {
            $norm[$k] = $clean($decoded[$k] ?? null);
        }
        foreach (['creators', 'subjects', 'places', 'languages'] as $k) {
            $v = $decoded[$k] ?? [];
            $norm[$k] = is_array($v)
                ? array_values(array_filter(array_map($clean, $v)))
                : [];
        }
        return $norm;
    }
}
