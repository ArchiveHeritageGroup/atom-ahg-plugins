<?php

/**
 * authorityResolution module actions.
 *
 * Review UI for the AHG Authority Resolution Engine (Task 5). Backs:
 *   - GET  /admin/authorityResolution             - queue listing of pending mentions
 *   - GET  /admin/authorityResolution/:id/review  - three-region review screen
 *   - POST /admin/authorityResolution/:id/link
 *   - POST /admin/authorityResolution/:id/link-different
 *   - POST /admin/authorityResolution/:id/create-new   (Task 6 stub)
 *   - POST /admin/authorityResolution/:id/park
 *   - POST /admin/authorityResolution/:id/reject
 *   - GET  /admin/authorityResolution/lookup           - JSON typeahead for "link different"
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class authorityResolutionActions extends sfActions
{
    // =========================================================================
    // SERVICE LOADERS (explicit require_once — SF1.4 + AHG plugin convention)
    // =========================================================================

    protected function decisionRecorder(): \AtomFramework\Services\AuthorityResolution\DecisionRecorder
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/DecisionRecorder.php';

        return new \AtomFramework\Services\AuthorityResolution\DecisionRecorder();
    }

    protected function parkQueueService(): \AtomFramework\Services\AuthorityResolution\ParkQueueService
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/ParkQueueService.php';

        return new \AtomFramework\Services\AuthorityResolution\ParkQueueService();
    }

    protected function nerFeedbackService(): \AtomFramework\Services\AuthorityResolution\NerFeedbackService
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/NerFeedbackService.php';

        return new \AtomFramework\Services\AuthorityResolution\NerFeedbackService();
    }

    protected function actorAdapter(): \AtomFramework\Services\AuthorityResolution\Adapters\MysqlActorAdapter
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/Adapters/CandidateAdapterInterface.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Adapters/MysqlActorAdapter.php';

        return new \AtomFramework\Services\AuthorityResolution\Adapters\MysqlActorAdapter();
    }

    protected function termAdapter(): \AtomFramework\Services\AuthorityResolution\Adapters\MysqlTermAdapter
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/Adapters/CandidateAdapterInterface.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Adapters/MysqlTermAdapter.php';

        return new \AtomFramework\Services\AuthorityResolution\Adapters\MysqlTermAdapter();
    }

    protected function authorityCreator(): \AtomFramework\Services\AuthorityResolution\AuthorityCreator
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/AuthorityCreator.php';

        return new \AtomFramework\Services\AuthorityResolution\AuthorityCreator();
    }

    protected function fieldProvenanceWriter(): \AtomFramework\Services\AuthorityResolution\FieldProvenanceWriter
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/FusekiUpdateService.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/FieldProvenanceWriter.php';

        return new \AtomFramework\Services\AuthorityResolution\FieldProvenanceWriter(
            new \AtomFramework\Services\AuthorityResolution\FusekiUpdateService()
        );
    }

    protected function prefillEngine(): \AtomFramework\Services\AuthorityResolution\Lookup\PrefillEngine
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/LookupAdapterInterface.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/AbstractLookupAdapter.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/Adapters/ViafAdapter.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/Adapters/WikidataAdapter.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/Adapters/GeoNamesAdapter.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/Adapters/TgnAdapter.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/Adapters/GndAdapter.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/Adapters/IsniAdapter.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/Adapters/SagncAdapter.php';
        require_once dirname(__FILE__) . '/../../../lib/Services/Lookup/PrefillEngine.php';

        $adapters = [
            new \AtomFramework\Services\AuthorityResolution\Lookup\Adapters\ViafAdapter(),
            new \AtomFramework\Services\AuthorityResolution\Lookup\Adapters\WikidataAdapter(),
            new \AtomFramework\Services\AuthorityResolution\Lookup\Adapters\GeoNamesAdapter(),
            new \AtomFramework\Services\AuthorityResolution\Lookup\Adapters\TgnAdapter(),
            new \AtomFramework\Services\AuthorityResolution\Lookup\Adapters\GndAdapter(),
            new \AtomFramework\Services\AuthorityResolution\Lookup\Adapters\IsniAdapter(),
            new \AtomFramework\Services\AuthorityResolution\Lookup\Adapters\SagncAdapter(),
        ];
        return new \AtomFramework\Services\AuthorityResolution\Lookup\PrefillEngine($adapters);
    }

    // =========================================================================
    // ACL — reuse AtoM's user credential check (matches ahgAuthorityPlugin)
    // =========================================================================

    protected function requireAuth(): int
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        return (int) $this->context->user->getAttribute('user_id');
    }

    protected function requireEditor(): int
    {
        $userId = $this->requireAuth();
        if (!$this->context->user->hasCredential('editor') && !$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        return $userId;
    }

    protected function jsonResponse(array $data): string
    {
        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($data));
    }

    // =========================================================================
    // INDEX — pending queue
    // =========================================================================

    public function executeIndex(sfWebRequest $request)
    {
        $this->requireAuth();

        $filters = [
            'entity_type' => trim((string) $request->getParameter('entity_type', '')),
            'object_id' => (int) $request->getParameter('object_id', 0),
            'state' => trim((string) $request->getParameter('state', 'pending')),
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => max(10, min(200, (int) $request->getParameter('limit', 50))),
        ];

        $q = DB::table('ahg_mention as m')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'm.object_id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'm.object_id')
            ->leftJoin(DB::raw('(SELECT mention_id, COUNT(*) as c FROM ahg_mention_candidate GROUP BY mention_id) as cc'),
                'cc.mention_id', '=', 'm.id')
            ->select(
                'm.id',
                'm.object_id',
                'm.entity_type',
                'm.state',
                'm.promoted_at',
                'n.entity_value',
                'ioi.title as io_title',
                's.slug as io_slug',
                DB::raw('COALESCE(cc.c, 0) as candidate_count')
            );

        if ($filters['state'] !== '' && $filters['state'] !== 'any') {
            $q->where('m.state', '=', $filters['state']);
        }
        if ($filters['entity_type'] !== '') {
            $q->where('m.entity_type', '=', $filters['entity_type']);
        }
        if ($filters['object_id'] > 0) {
            $q->where('m.object_id', '=', $filters['object_id']);
        }

        $total = (clone $q)->count();
        $rows = $q->orderByRaw('candidate_count DESC, m.id ASC')
            ->offset(($filters['page'] - 1) * $filters['limit'])
            ->limit($filters['limit'])
            ->get();

        $this->rows = $rows;
        $this->total = $total;
        $this->filters = $filters;
        $this->lastPage = max(1, (int) ceil($total / max(1, $filters['limit'])));
        $this->stateCounts = DB::table('ahg_mention')
            ->select('state', DB::raw('COUNT(*) as c'))
            ->groupBy('state')
            ->get();
        $this->typeCounts = DB::table('ahg_mention')
            ->where('state', '=', 'pending')
            ->select('entity_type', DB::raw('COUNT(*) as c'))
            ->groupBy('entity_type')
            ->get();
    }

    // =========================================================================
    // REVIEW — three-region screen for one mention
    // =========================================================================

    public function executeReview(sfWebRequest $request)
    {
        $this->requireAuth();

        $mentionId = (int) $request->getParameter('id', 0);
        $mention = $this->loadMention($mentionId);
        if (!$mention) {
            $this->forward404("Mention #{$mentionId} not found");
        }

        $this->mention = $mention;

        $this->context_row = DB::table('ahg_mention_context')
            ->where('mention_id', $mentionId)
            ->first();

        $this->candidates = DB::table('ahg_mention_candidate as c')
            ->leftJoin('slug as s', function ($j) {
                $j->on('s.object_id', '=', 'c.candidate_authority_id');
            })
            ->where('c.mention_id', $mentionId)
            ->orderByRaw('c.composite_score DESC, c.rank_position ASC')
            ->select([
                'c.id',
                'c.mention_id',
                'c.rank_position',
                'c.candidate_source',
                'c.candidate_authority_id',
                'c.candidate_fuseki_uri',
                'c.candidate_display_name',
                'c.name_similarity_score',
                'c.evidence_signals',
                'c.evidence_data',
                'c.composite_score',
                's.slug as authority_slug',
            ])
            ->get();

        // Next pending mention id (for the post-decision redirect).
        $this->next_pending_id = DB::table('ahg_mention')
            ->where('state', '=', 'pending')
            ->where('id', '>', $mentionId)
            ->orderBy('id', 'asc')
            ->value('id');

        // ahg_mention_park row (if any)
        $this->park_row = DB::table('ahg_mention_park')
            ->where('mention_id', $mentionId)
            ->first();

        // Most-recent decision (if any) for review of historical actions.
        $this->latest_decision = DB::table('ahg_mention_decision')
            ->where('mention_id', $mentionId)
            ->orderBy('decided_at', 'desc')
            ->first();

        // Place coordinates for map preview (term -> term_relation -> geocoord shape varies in AtoM).
        // Best-effort: if any candidate is a term, try to pull lat/lng from term_i18n description JSON
        // OR from object property where appropriate. Empty array if not resolvable — view degrades gracefully.
        $this->place_coords = [];
        if (in_array($mention->entity_type, ['GPE', 'LOC', 'PLACE', 'ISAD_PLACE'], true)) {
            foreach ($this->candidates as $c) {
                if ($c->candidate_authority_id) {
                    $coord = $this->resolvePlaceCoord((int) $c->candidate_authority_id);
                    if ($coord !== null) {
                        $this->place_coords[(int) $c->id] = $coord;
                    }
                }
            }
        }
    }

    // =========================================================================
    // DECISION ACTIONS
    // =========================================================================

    public function executeLink(sfWebRequest $request)
    {
        return $this->handleDecision($request, 'link');
    }

    public function executeLinkDifferent(sfWebRequest $request)
    {
        return $this->handleDecision($request, 'link_different');
    }

    /**
     * Task 6: GET form for creating a new authority record. Loads the mention,
     * runs PrefillEngine to merge external + context fields, and renders the
     * createNewSuccess template.
     */
    public function executeCreateNew(sfWebRequest $request)
    {
        $this->requireEditor();

        $mentionId = (int) $request->getParameter('id', 0);
        $mention = $this->loadMention($mentionId);
        if (!$mention) {
            $this->forward404("Mention #{$mentionId} not found");
        }

        try {
            $this->prefill = $this->prefillEngine()->prefill($mentionId);
        } catch (\Throwable $e) {
            $this->prefill = [
                'mention' => $mention,
                'context' => null,
                'lookup_results' => [],
                'merged_fields' => [],
                'error' => $e->getMessage(),
            ];
        }
        $this->mention = $mention;

        $this->context_row = DB::table('ahg_mention_context')
            ->where('mention_id', $mentionId)
            ->first();

        $this->entity_type = $this->normaliseEntityType((string) $mention->entity_type);
    }

    /**
     * Task 6: POST submit handler for the create-new form. Validates input,
     * dispatches to AuthorityCreator, writes field provenance to Fuseki, and
     * records a `create_new` decision (with the new authority_id) via the
     * existing DecisionRecorder. Redirects to the next pending mention.
     */
    public function executeCreateNewSubmit(sfWebRequest $request)
    {
        $userId = $this->requireEditor();
        $mentionId = (int) $request->getParameter('id', 0);

        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }

        $mention = $this->loadMention($mentionId);
        if (!$mention) {
            $this->forward404("Mention #{$mentionId} not found");
        }

        $entityType = $this->normaliseEntityType((string) $mention->entity_type);
        $form = $this->collectCreateForm($request, $entityType);
        $prefillProvenance = $this->collectPrefillProvenance($request);

        try {
            if ($entityType === 'PERSON') {
                $newId = $this->authorityCreator()->createPerson($form, $userId);
                $authorityType = 'actor';
            } elseif ($entityType === 'ORG') {
                $newId = $this->authorityCreator()->createOrg($form, $userId);
                $authorityType = 'actor';
            } elseif ($entityType === 'PLACE') {
                $newId = $this->authorityCreator()->createPlace($form, $userId);
                $authorityType = 'term';
            } else {
                $this->getUser()->setFlash('error', "Unsupported entity type for create-new: {$entityType}");
                $this->redirect('@ar_auth_res_review?id=' . $mentionId);
            }
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', 'Create failed: ' . $e->getMessage());
            $this->redirect('@ar_auth_res_create_new?id=' . $mentionId);
        }

        // Build merged-fields shape FieldProvenanceWriter consumes.
        $mergedForWriter = [];
        foreach ($form as $key => $value) {
            if (in_array($key, ['descriptive_standard', 'source_culture', 'parent_id'], true)) {
                continue;
            }
            $mergedForWriter[$key] = $value;
        }

        $provResult = $this->fieldProvenanceWriter()->writeForCreation(
            (int) $newId,
            $authorityType,
            $mergedForWriter,
            $prefillProvenance
        );

        // Record the create_new decision through DecisionRecorder. authority_id
        // is now known so the audit row carries it.
        $decisionResult = $this->decisionRecorder()->record(
            $mentionId,
            \AtomFramework\Services\AuthorityResolution\DecisionRecorder::DECISION_CREATE_NEW,
            $userId,
            ['authority_id' => (int) $newId]
        );

        $flash = sprintf(
            'New %s #%d created. Field provenance: %s (%d triples). Decision #%d recorded.',
            $authorityType,
            (int) $newId,
            !empty($provResult['ok']) ? 'ok' : ('failed: ' . ($provResult['error'] ?? 'unknown')),
            (int) ($provResult['triple_count'] ?? 0),
            (int) ($decisionResult['decision_id'] ?? 0)
        );
        $this->getUser()->setFlash('notice', $flash);

        $next = DB::table('ahg_mention')
            ->where('state', '=', 'pending')
            ->where('id', '>', $mentionId)
            ->orderBy('id', 'asc')
            ->value('id');
        if ($next) {
            $this->redirect('@ar_auth_res_review?id=' . (int) $next);
        }
        $this->redirect('@ar_auth_res_index');
    }

    /**
     * Task 6: GET admin settings page for the seven external lookup adapters.
     * Reads current values from ahg_settings and hands them to the template.
     */
    public function executeLookupSettings(sfWebRequest $request)
    {
        $userId = $this->requireAuth();
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->sources = ['viaf', 'wikidata', 'geonames', 'tgn', 'gnd', 'isni', 'sagnc'];
        $this->settings = $this->loadLookupSettings($this->sources);
        $this->precedence = $this->loadPrecedenceRaw();
        $this->geonamesUsername = (string) (DB::table('ahg_settings')
            ->where('setting_key', 'authority_resolution.lookup.geonames.username')
            ->value('setting_value') ?? '');
    }

    /**
     * Task 6: POST settings save handler. Iterates the seven sources and
     * upserts enabled / rate_limit / cache_ttl / license_note / license_url
     * plus the global precedence array (JSON) and GeoNames username.
     */
    public function executeLookupSettingsSave(sfWebRequest $request)
    {
        $userId = $this->requireAuth();
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }

        $sources = ['viaf', 'wikidata', 'geonames', 'tgn', 'gnd', 'isni', 'sagnc'];
        $updated = 0;
        foreach ($sources as $src) {
            $enabled = $request->getParameter("sources[{$src}][enabled]") ? '1' : '0';
            $rate = trim((string) $request->getParameter("sources[{$src}][rate_limit]", ''));
            $ttl = trim((string) $request->getParameter("sources[{$src}][cache_ttl]", ''));
            $licenceNote = trim((string) $request->getParameter("sources[{$src}][license_note]", ''));
            $licenceUrl = trim((string) $request->getParameter("sources[{$src}][license_url]", ''));

            $updated += $this->upsertSetting("authority_resolution.lookup.{$src}.enabled", $enabled, 'boolean');
            if ($rate !== '' && ctype_digit($rate)) {
                $updated += $this->upsertSetting("authority_resolution.lookup.{$src}.rate_limit", $rate, 'integer');
            }
            if ($ttl !== '' && ctype_digit($ttl)) {
                $updated += $this->upsertSetting("authority_resolution.lookup.{$src}.cache_ttl", $ttl, 'integer');
            }
            if ($licenceNote !== '') {
                $updated += $this->upsertSetting("authority_resolution.lookup.{$src}.license_note", $licenceNote, 'string');
            }
            if ($licenceUrl !== '') {
                $updated += $this->upsertSetting("authority_resolution.lookup.{$src}.license_url", $licenceUrl, 'string');
            }
        }

        $precedenceRaw = trim((string) $request->getParameter('precedence', ''));
        if ($precedenceRaw !== '') {
            $parts = array_values(array_filter(
                array_map('trim', explode(',', $precedenceRaw)),
                function ($s) { return $s !== ''; }
            ));
            $this->upsertSetting(
                'authority_resolution.lookup.precedence',
                json_encode($parts),
                'json'
            );
        }
        $geonamesUsername = trim((string) $request->getParameter('geonames_username', ''));
        $this->upsertSetting(
            'authority_resolution.lookup.geonames.username',
            $geonamesUsername,
            'string'
        );

        $this->getUser()->setFlash('notice', "Lookup settings saved ({$updated} rows updated).");
        $this->redirect('@ar_auth_res_lookup_settings');
    }

    public function executePark(sfWebRequest $request)
    {
        return $this->handleDecision($request, 'park');
    }

    public function executeReject(sfWebRequest $request)
    {
        return $this->handleDecision($request, 'reject');
    }

    private function handleDecision(sfWebRequest $request, string $decisionType)
    {
        $userId = $this->requireEditor();
        $mentionId = (int) $request->getParameter('id', 0);

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['ok' => false, 'error' => 'POST required']);
        }

        $mention = DB::table('ahg_mention')->where('id', $mentionId)->first();
        if (!$mention) {
            return $this->jsonResponse(['ok' => false, 'error' => "mention #{$mentionId} not found"]);
        }

        $opts = [];

        if ($decisionType === 'link') {
            $opts['candidate_id'] = (int) $request->getParameter('candidate_id', 0);
            if (!$opts['candidate_id']) {
                return $this->jsonResponse(['ok' => false, 'error' => 'candidate_id required for link']);
            }
        } elseif ($decisionType === 'link_different') {
            $opts['authority_id'] = (int) $request->getParameter('authority_id', 0);
            $candidateId = (int) $request->getParameter('candidate_id', 0);
            if ($candidateId) {
                $opts['candidate_id'] = $candidateId;
            }
            if (!$opts['authority_id'] && !$candidateId) {
                return $this->jsonResponse(['ok' => false, 'error' => 'authority_id or candidate_id required for link_different']);
            }
        } elseif ($decisionType === 'create_new') {
            // Task 6 will populate authority_id post-create. Decision row still
            // freezes the candidate slate seen at decision time.
            $opts['authority_id'] = (int) $request->getParameter('authority_id', 0) ?: null;
        } elseif ($decisionType === 'park') {
            $opts['reason'] = trim((string) $request->getParameter('reason', ''));
            if ($opts['reason'] === '') {
                return $this->jsonResponse(['ok' => false, 'error' => 'reason required for park']);
            }
        } elseif ($decisionType === 'reject') {
            // Task 9: rejection reason feeds the NER feedback capture path.
            // It is OPTIONAL on existing rejects so the legacy submit-from-form
            // path doesn't suddenly hard-fail; but the new reject modal POSTs
            // it as a required field and the captured row carries it through.
            $opts['reason'] = trim((string) $request->getParameter('reason', ''));
        }

        $result = $this->decisionRecorder()->record($mentionId, $decisionType, $userId, $opts);

        if ($request->isXmlHttpRequest() || $request->getParameter('format') === 'json') {
            return $this->jsonResponse($result);
        }

        if (!empty($result['ok'])) {
            $this->getUser()->setFlash('notice', sprintf('Decision recorded: %s (#%d)', $decisionType, (int) $result['decision_id']));
            // Next pending mention or back to index.
            $next = DB::table('ahg_mention')
                ->where('state', '=', 'pending')
                ->where('id', '>', $mentionId)
                ->orderBy('id', 'asc')
                ->value('id');
            if ($next) {
                $this->redirect('@ar_auth_res_review?id=' . (int) $next);
            }
            $this->redirect('@ar_auth_res_index');
        }

        $this->getUser()->setFlash('error', 'Decision failed: ' . ($result['error'] ?? 'unknown'));
        $this->redirect('@ar_auth_res_review?id=' . $mentionId);
    }

    // =========================================================================
    // TASK 7 — PARK QUEUE
    // =========================================================================

    /**
     * GET /admin/authorityResolution/park
     * Dedicated park-queue screen with filters (parked_by user, entity_type,
     * new_candidate_only, since_parked date).
     */
    public function executeParkList(sfWebRequest $request)
    {
        $this->requireAuth();

        $filters = [
            'parked_by' => (int) $request->getParameter('parked_by', 0),
            'entity_type' => trim((string) $request->getParameter('entity_type', '')),
            'new_candidate_only' => (bool) $request->getParameter('new_candidate_only', false),
            'since_parked' => trim((string) $request->getParameter('since_parked', '')),
            'limit' => max(10, min(200, (int) $request->getParameter('limit', 50))),
        ];

        $rows = $this->parkQueueService()->listFor(
            $filters['parked_by'] > 0 ? $filters['parked_by'] : null,
            $filters['entity_type'] !== '' ? $filters['entity_type'] : null,
            $filters['new_candidate_only'] ? true : null,
            $filters['since_parked'] !== '' ? $filters['since_parked'] : null,
            $filters['limit']
        );

        $userOptions = DB::table('ahg_mention_park as p')
            ->leftJoin('user as u', 'u.id', '=', 'p.parked_by_user_id')
            ->select('p.parked_by_user_id', 'u.username', DB::raw('COUNT(*) as c'))
            ->groupBy('p.parked_by_user_id', 'u.username')
            ->orderBy('c', 'desc')
            ->get();

        $totalParked = (int) DB::table('ahg_mention_park')->count();
        $newCandidateFlagged = (int) DB::table('ahg_mention_park')
            ->where('new_candidate_available', '=', 1)
            ->count();

        $this->rows = $rows;
        $this->filters = $filters;
        $this->userOptions = $userOptions;
        $this->totalParked = $totalParked;
        $this->newCandidateFlagged = $newCandidateFlagged;
    }

    /**
     * POST /admin/authorityResolution/park/:id/unpark
     * Un-park a mention: delete park row, flip state -> pending, regenerate
     * candidates + re-score evidence. Redirects to the mention review screen.
     */
    public function executeUnpark(sfWebRequest $request)
    {
        $userId = $this->requireEditor();
        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['ok' => false, 'error' => 'POST required']);
        }
        $mentionId = (int) $request->getParameter('id', 0);

        $result = $this->parkQueueService()->unparkAndRereview($mentionId, $userId);

        if ($request->isXmlHttpRequest() || $request->getParameter('format') === 'json') {
            return $this->jsonResponse($result);
        }

        if (!empty($result['ok'])) {
            $this->getUser()->setFlash('notice', sprintf(
                'Mention #%d un-parked. %d candidate(s) generated, %d scored.',
                $mentionId,
                count($result['candidate_ids']),
                (int) $result['scored']
            ));
            if (!empty($result['error'])) {
                $this->getUser()->setFlash('warning', $result['error']);
            }
            $this->redirect('@ar_auth_res_review?id=' . $mentionId);
        }
        $this->getUser()->setFlash('error', 'Un-park failed: ' . ($result['error'] ?? 'unknown'));
        $this->redirect('@ar_auth_res_park_list');
    }

    /**
     * GET /admin/authorityResolution/park/dashboard.json
     * JSON {archivist_user_id => parked_count}. Powers the dashboard widget.
     */
    public function executeParkDashboardJson(sfWebRequest $request)
    {
        $this->requireAuth();
        $map = $this->parkQueueService()->dashboardByUser();
        // Decorate with usernames for the widget.
        $userIds = array_keys($map);
        $users = [];
        if (!empty($userIds)) {
            $rows = DB::table('user')->whereIn('id', $userIds)->get(['id', 'username']);
            foreach ($rows as $u) {
                $users[(int) $u->id] = (string) $u->username;
            }
        }
        $out = [];
        foreach ($map as $uid => $count) {
            $out[] = [
                'archivist_user_id' => $uid,
                'username' => $users[$uid] ?? null,
                'parked_count' => $count,
            ];
        }
        return $this->jsonResponse([
            'total' => array_sum($map),
            'by_user' => $out,
        ]);
    }

    // =========================================================================
    // LOOKUP — JSON typeahead for "link different"
    // =========================================================================

    public function executeLookup(sfWebRequest $request)
    {
        $this->requireAuth();

        $q = trim((string) $request->getParameter('q', ''));
        $type = trim((string) $request->getParameter('type', 'PERSON'));
        $limit = max(1, min(50, (int) $request->getParameter('limit', 10)));

        if ($q === '') {
            return $this->jsonResponse(['results' => []]);
        }

        $adapter = in_array($type, ['GPE', 'LOC', 'PLACE', 'ISAD_PLACE'], true)
            ? $this->termAdapter()
            : $this->actorAdapter();

        $rows = $adapter->search($q, $type, $limit);

        return $this->jsonResponse(['results' => $rows]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function loadMention(int $mentionId): ?object
    {
        $row = DB::table('ahg_mention as m')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->leftJoin('ahg_ner_extraction as e', 'e.id', '=', 'n.extraction_id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'm.object_id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'm.object_id')
            ->where('m.id', $mentionId)
            ->first([
                'm.id',
                'm.ner_entity_id',
                'm.object_id',
                'm.entity_type',
                'm.state',
                'm.promoted_at',
                'm.updated_at',
                'n.entity_value',
                'n.original_value',
                'n.confidence',
                'n.linked_actor_id',
                'ioi.title as io_title',
                'ioi.scope_and_content as io_scope_and_content',
                's.slug as io_slug',
            ]);

        return $row ?: null;
    }

    /**
     * Normalise entity_type to one of PERSON / ORG / PLACE (the three the
     * AuthorityCreator + PrefillEngine understand).
     */
    private function normaliseEntityType(string $type): string
    {
        $upper = strtoupper($type);
        if (in_array($upper, ['GPE', 'LOC', 'ISAD_PLACE'], true)) {
            return 'PLACE';
        }
        return $upper;
    }

    /**
     * Read the form fields the createNewSuccess template POSTs back.
     * Returns the keys AuthorityCreator::create{Person,Org,Place} expects.
     */
    private function collectCreateForm(sfWebRequest $request, string $entityType): array
    {
        if ($entityType === 'PLACE') {
            return [
                'name' => trim((string) $request->getParameter('name', '')),
                'latitude' => trim((string) $request->getParameter('latitude', '')),
                'longitude' => trim((string) $request->getParameter('longitude', '')),
                'source_culture' => trim((string) $request->getParameter('source_culture', 'en')) ?: 'en',
                'parent_id' => (int) $request->getParameter('parent_id', 0) ?: null,
            ];
        }
        return [
            'authorized_form_of_name' => trim((string) $request->getParameter('authorized_form_of_name', '')),
            'dates_of_existence' => trim((string) $request->getParameter('dates_of_existence', '')),
            'history' => trim((string) $request->getParameter('history', '')),
            'places' => trim((string) $request->getParameter('places', '')),
            'mandates' => trim((string) $request->getParameter('mandates', '')),
            'functions' => trim((string) $request->getParameter('functions', '')),
            'legal_status' => trim((string) $request->getParameter('legal_status', '')),
            'descriptive_standard' => trim((string) $request->getParameter('descriptive_standard', 'ISAAR-CPF')) ?: 'ISAAR-CPF',
            'source_culture' => trim((string) $request->getParameter('source_culture', 'en')) ?: 'en',
            'parent_id' => (int) $request->getParameter('parent_id', 0) ?: null,
        ];
    }

    /**
     * Rehydrate the per-field provenance the prefill form embedded as hidden
     * inputs `_provenance[<field>][source|uri|license|license_url|at]`.
     */
    private function collectPrefillProvenance(sfWebRequest $request): array
    {
        $raw = $request->getParameter('_provenance', []);
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $field => $row) {
            if (!is_array($row)) {
                continue;
            }
            $entry = [];
            foreach (['source', 'uri', 'license', 'license_url', 'at'] as $k) {
                if (isset($row[$k]) && $row[$k] !== '') {
                    $entry[$k] = (string) $row[$k];
                }
            }
            if (!empty($entry)) {
                $out[$field] = $entry;
            }
        }
        return $out;
    }

    /**
     * Load the lookup settings rows keyed [source][param] => value.
     */
    private function loadLookupSettings(array $sources): array
    {
        $prefix = 'authority_resolution.lookup.';
        $rows = DB::table('ahg_settings')
            ->where('setting_key', 'like', $prefix . '%')
            ->get(['setting_key', 'setting_value']);

        $out = [];
        foreach ($sources as $src) {
            $out[$src] = [
                'enabled' => '0',
                'rate_limit' => '',
                'cache_ttl' => '',
                'license_note' => '',
                'license_url' => '',
            ];
        }
        foreach ($rows as $r) {
            $tail = substr((string) $r->setting_key, strlen($prefix));
            $bits = explode('.', $tail, 2);
            if (count($bits) !== 2) {
                continue;
            }
            [$src, $param] = $bits;
            if (!isset($out[$src])) {
                continue;
            }
            $out[$src][$param] = (string) $r->setting_value;
        }
        return $out;
    }

    private function loadPrecedenceRaw(): string
    {
        $raw = DB::table('ahg_settings')
            ->where('setting_key', 'authority_resolution.lookup.precedence')
            ->value('setting_value');
        if (!is_string($raw) || $raw === '') {
            return 'viaf,wikidata,geonames,tgn,gnd,isni,sagnc';
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return implode(',', array_map('strval', $decoded));
        }
        return $raw;
    }

    private function upsertSetting(string $key, string $value, string $type): int
    {
        $now = date('Y-m-d H:i:s');
        $exists = DB::table('ahg_settings')->where('setting_key', $key)->exists();
        if ($exists) {
            DB::table('ahg_settings')
                ->where('setting_key', $key)
                ->update([
                    'setting_value' => $value,
                    'updated_at' => $now,
                ]);
            return 1;
        }
        DB::table('ahg_settings')->insert([
            'setting_key' => $key,
            'setting_group' => 'authority_resolution',
            'setting_type' => $type,
            'setting_value' => $value,
            'description' => null,
            'is_sensitive' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return 1;
    }

    /**
     * Best-effort place coordinate resolution. Returns ['lat'=>float, 'lng'=>float]
     * or null when no coordinates are recorded.
     *
     * AtoM-side place terms typically don't carry geocoords on the term row
     * itself; some installs store them on a linked information_object (place
     * homepage) or in term_i18n.description JSON. We probe a few cheap paths
     * and accept that for many places we get back null — the UI degrades
     * gracefully.
     */
    private function resolvePlaceCoord(int $termId): ?array
    {
        // Probe term_i18n.description for a "lat,lng" pattern or JSON fragment.
        $row = DB::table('term_i18n')
            ->where('id', $termId)
            ->where('culture', 'en')
            ->first(['description']);

        if (!$row || empty($row->description)) {
            return null;
        }

        $desc = (string) $row->description;
        if (preg_match('/(-?\d{1,3}\.\d+)\s*[,;]\s*(-?\d{1,3}\.\d+)/', $desc, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return ['lat' => $lat, 'lng' => $lng];
            }
        }
        return null;
    }
}
