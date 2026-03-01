<?php

/**
 * Authority module actions.
 *
 * Handles: Dashboard, Workqueue, Identifiers, Completeness, Graph,
 *          Merge/Split, Occupations, Functions, Contact, EAC-CPF Export, Config.
 */
class authorityActions extends sfActions
{
    // =========================================================================
    // SERVICE LOADERS (lazy, avoids autoload conflicts)
    // =========================================================================

    protected function identifierService(): \AhgAuthority\Services\AuthorityIdentifierService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/AuthorityIdentifierService.php';

        return new \AhgAuthority\Services\AuthorityIdentifierService();
    }

    protected function lookupService(): \AhgAuthority\Services\AuthorityLookupService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/AuthorityLookupService.php';

        return new \AhgAuthority\Services\AuthorityLookupService();
    }

    protected function completenessService(): \AhgAuthority\Services\AuthorityCompletenessService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/AuthorityCompletenessService.php';

        return new \AhgAuthority\Services\AuthorityCompletenessService();
    }

    protected function graphService(): \AhgAuthority\Services\AuthorityGraphService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/AuthorityGraphService.php';

        return new \AhgAuthority\Services\AuthorityGraphService();
    }

    protected function eacService(): \AhgAuthority\Services\AuthorityEacExportService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/AuthorityEacExportService.php';

        return new \AhgAuthority\Services\AuthorityEacExportService();
    }

    protected function mergeService(): \AhgAuthority\Services\AuthorityMergeService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/AuthorityMergeService.php';

        return new \AhgAuthority\Services\AuthorityMergeService();
    }

    protected function occupationService(): \AhgAuthority\Services\AuthorityOccupationService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/AuthorityOccupationService.php';

        return new \AhgAuthority\Services\AuthorityOccupationService();
    }

    protected function functionService(): \AhgAuthority\Services\AuthorityFunctionService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/AuthorityFunctionService.php';

        return new \AhgAuthority\Services\AuthorityFunctionService();
    }

    // =========================================================================
    // AUTH HELPERS
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

    protected function requireAdmin(): int
    {
        $userId = $this->requireAuth();
        if (!$this->context->user->isAdministrator()) {
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
    // DASHBOARD & WORKQUEUE (#206)
    // =========================================================================

    public function executeDashboard(sfWebRequest $request)
    {
        $this->requireAuth();

        $service = $this->completenessService();
        $this->stats = $service->getDashboardStats();
        $this->identifierStats = $this->identifierService()->getStats();

        // Use dashboardSuccess.php template
    }

    public function executeWorkqueue(sfWebRequest $request)
    {
        $this->requireAuth();

        $service = $this->completenessService();

        $this->filters = [
            'level'       => $request->getParameter('level', ''),
            'assigned_to' => $request->getParameter('assigned_to', ''),
            'unassigned'  => $request->getParameter('unassigned', ''),
            'min_score'   => $request->getParameter('min_score', ''),
            'max_score'   => $request->getParameter('max_score', ''),
            'sort'        => $request->getParameter('sort', 'completeness_score'),
            'sortDir'     => $request->getParameter('sortDir', 'asc'),
            'page'        => $request->getParameter('page', 1),
            'limit'       => $request->getParameter('limit', 50),
        ];

        $this->workqueue = $service->getWorkqueue($this->filters);
        $this->levels = array_keys(\AhgAuthority\Services\AuthorityCompletenessService::LEVELS);

        // Get users for assignment dropdown
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')
            ->join('actor_i18n', function ($j) {
                $j->on('user.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('user.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get()
            ->all();

        // Use workqueueSuccess.php template
    }

    // =========================================================================
    // EXTERNAL IDENTIFIERS (#202)
    // =========================================================================

    public function executeIdentifiers(sfWebRequest $request)
    {
        $this->requireAuth();

        $actorId = (int) $request->getParameter('actorId');
        $service = $this->identifierService();

        $this->actor = $this->getActorOrForward($actorId);
        $this->identifiers = $service->getIdentifiers($actorId);
        $this->uriPatterns = \AhgAuthority\Services\AuthorityIdentifierService::URI_PATTERNS;

        // Use identifiersSuccess.php template
    }

    public function executeApiIdentifierSave(sfWebRequest $request)
    {
        $this->requireEditor();

        $actorId = (int) $request->getParameter('actor_id');
        $data = [
            'identifier_type'  => $request->getParameter('identifier_type', ''),
            'identifier_value' => $request->getParameter('identifier_value', ''),
            'uri'              => $request->getParameter('uri') ?: null,
            'label'            => $request->getParameter('label') ?: null,
            'source'           => $request->getParameter('source', 'manual'),
        ];

        $id = $this->identifierService()->save($actorId, $data);

        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    public function executeApiIdentifierDelete(sfWebRequest $request)
    {
        $this->requireEditor();

        $id = (int) $request->getParameter('id');
        $result = $this->identifierService()->delete($id);

        return $this->jsonResponse(['success' => $result]);
    }

    public function executeApiIdentifierVerify(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $id = (int) $request->getParameter('id');
        $result = $this->identifierService()->verify($id, $userId);

        return $this->jsonResponse(['success' => $result]);
    }

    // =========================================================================
    // EXTERNAL LOOKUP (#202)
    // =========================================================================

    public function executeApiWikidataSearch(sfWebRequest $request)
    {
        $this->requireAuth();

        $query = $request->getParameter('q', '');
        $result = $this->lookupService()->searchWikidata($query);

        return $this->jsonResponse($result);
    }

    public function executeApiViafSearch(sfWebRequest $request)
    {
        $this->requireAuth();

        $query = $request->getParameter('q', '');
        $result = $this->lookupService()->searchViaf($query);

        return $this->jsonResponse($result);
    }

    public function executeApiUlanSearch(sfWebRequest $request)
    {
        $this->requireAuth();

        $query = $request->getParameter('q', '');
        $result = $this->lookupService()->searchUlan($query);

        return $this->jsonResponse($result);
    }

    public function executeApiLcnafSearch(sfWebRequest $request)
    {
        $this->requireAuth();

        $query = $request->getParameter('q', '');
        $result = $this->lookupService()->searchLcnaf($query);

        return $this->jsonResponse($result);
    }

    // =========================================================================
    // COMPLETENESS (#206)
    // =========================================================================

    public function executeApiCompletenessRecalc(sfWebRequest $request)
    {
        $this->requireAuth();

        $actorId = (int) $request->getParameter('actorId');
        $result = $this->completenessService()->calculateScore($actorId);

        return $this->jsonResponse(['success' => true, 'result' => $result]);
    }

    public function executeApiCompletenessBatchAssign(sfWebRequest $request)
    {
        $this->requireEditor();

        $actorIds = $request->getParameter('actor_ids', []);
        $assigneeId = (int) $request->getParameter('assignee_id');

        if (!is_array($actorIds)) {
            $actorIds = explode(',', $actorIds);
        }
        $actorIds = array_map('intval', $actorIds);

        $count = $this->completenessService()->batchAssign($actorIds, $assigneeId);

        return $this->jsonResponse(['success' => true, 'count' => $count]);
    }

    // =========================================================================
    // GRAPH (#203)
    // =========================================================================

    public function executeApiGraphData(sfWebRequest $request)
    {
        $this->requireAuth();

        $actorId = (int) $request->getParameter('actorId');
        $depth = (int) $request->getParameter('depth', 1);
        $depth = min($depth, 3); // Cap at 3 levels

        $data = $this->graphService()->getGraphData($actorId, $depth);

        return $this->jsonResponse($data);
    }

    // =========================================================================
    // MERGE / SPLIT (#207)
    // =========================================================================

    public function executeMerge(sfWebRequest $request)
    {
        $this->requireEditor();

        $primaryId = (int) $request->getParameter('id');
        $this->actor = $this->getActorOrForward($primaryId);
        $this->mergeHistory = $this->mergeService()->getMergeHistory($primaryId);

        // Use mergeSuccess.php template
    }

    public function executeSplit(sfWebRequest $request)
    {
        $this->requireEditor();

        $actorId = (int) $request->getParameter('id');
        $this->actor = $this->getActorOrForward($actorId);

        // Use splitSuccess.php template
    }

    public function executeApiMergePreview(sfWebRequest $request)
    {
        $this->requireEditor();

        $primaryId = (int) $request->getParameter('primary_id');
        $secondaryId = (int) $request->getParameter('secondary_id');

        $comparison = $this->mergeService()->compareActors($primaryId, $secondaryId);

        return $this->jsonResponse(['success' => true, 'comparison' => $comparison]);
    }

    public function executeApiMergeExecute(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $primaryId = (int) $request->getParameter('primary_id');
        $secondaryIds = $request->getParameter('secondary_ids', []);
        $fieldChoices = $request->getParameter('field_choices', []);
        $notes = $request->getParameter('notes', '');

        if (!is_array($secondaryIds)) {
            $secondaryIds = explode(',', $secondaryIds);
        }
        $secondaryIds = array_map('intval', $secondaryIds);

        $mergeId = $this->mergeService()->createMergeRequest(
            $primaryId,
            $secondaryIds,
            is_array($fieldChoices) ? $fieldChoices : [],
            $userId,
            $notes
        );

        return $this->jsonResponse(['success' => true, 'merge_id' => $mergeId]);
    }

    public function executeApiSplitExecute(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $sourceId = (int) $request->getParameter('source_id');
        $fieldsToMove = $request->getParameter('fields_to_move', []);
        $relationsToMove = $request->getParameter('relations_to_move', []);
        $notes = $request->getParameter('notes', '');

        $splitId = $this->mergeService()->createSplitRequest(
            $sourceId,
            is_array($fieldsToMove) ? $fieldsToMove : [],
            is_array($relationsToMove) ? $relationsToMove : [],
            $userId,
            $notes
        );

        return $this->jsonResponse(['success' => true, 'split_id' => $splitId]);
    }

    // =========================================================================
    // OCCUPATIONS (#205)
    // =========================================================================

    public function executeOccupations(sfWebRequest $request)
    {
        $this->requireAuth();

        $actorId = (int) $request->getParameter('actorId');
        $this->actor = $this->getActorOrForward($actorId);
        $this->occupations = $this->occupationService()->getOccupations($actorId);

        // Use occupationsSuccess.php template
    }

    public function executeApiOccupationSave(sfWebRequest $request)
    {
        $this->requireEditor();

        $actorId = (int) $request->getParameter('actor_id');
        $occupationId = (int) $request->getParameter('occupation_id', 0);

        $data = [
            'term_id'         => $request->getParameter('term_id') ?: null,
            'occupation_text' => $request->getParameter('occupation_text', ''),
            'date_from'       => $request->getParameter('date_from') ?: null,
            'date_to'         => $request->getParameter('date_to') ?: null,
            'notes'           => $request->getParameter('notes', ''),
            'sort_order'      => (int) $request->getParameter('sort_order', 0),
        ];

        $id = $this->occupationService()->save($actorId, $data, $occupationId);

        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    public function executeApiOccupationDelete(sfWebRequest $request)
    {
        $this->requireEditor();

        $id = (int) $request->getParameter('id');
        $result = $this->occupationService()->delete($id);

        return $this->jsonResponse(['success' => $result]);
    }

    // =========================================================================
    // FUNCTIONS (#201)
    // =========================================================================

    public function executeFunctions(sfWebRequest $request)
    {
        $this->requireAuth();

        $actorId = (int) $request->getParameter('actorId');
        $this->actor = $this->getActorOrForward($actorId);
        $this->functionLinks = $this->functionService()->getFunctionLinks($actorId);
        $this->relationTypes = \AhgAuthority\Services\AuthorityFunctionService::RELATION_TYPES;

        // Use functionsSuccess.php template
    }

    public function executeFunctionBrowse(sfWebRequest $request)
    {
        $this->requireAuth();

        $this->functions = $this->functionService()->browseFunctions();

        // Use functionBrowseSuccess.php template
    }

    public function executeApiFunctionSave(sfWebRequest $request)
    {
        $this->requireEditor();

        $actorId = (int) $request->getParameter('actor_id');
        $linkId = (int) $request->getParameter('link_id', 0);

        $data = [
            'function_id'   => $request->getParameter('function_id'),
            'relation_type' => $request->getParameter('relation_type', 'responsible'),
            'date_from'     => $request->getParameter('date_from') ?: null,
            'date_to'       => $request->getParameter('date_to') ?: null,
            'notes'         => $request->getParameter('notes', ''),
            'sort_order'    => (int) $request->getParameter('sort_order', 0),
        ];

        $id = $this->functionService()->save($actorId, $data, $linkId);

        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    public function executeApiFunctionDelete(sfWebRequest $request)
    {
        $this->requireEditor();

        $id = (int) $request->getParameter('id');
        $result = $this->functionService()->delete($id);

        return $this->jsonResponse(['success' => $result]);
    }

    // =========================================================================
    // CONTACT (#210)
    // =========================================================================

    public function executeContact(sfWebRequest $request)
    {
        $this->requireAuth();

        $actorId = (int) $request->getParameter('actorId');
        $this->actor = $this->getActorOrForward($actorId);

        // Delegate to ahgContactPlugin if available
        $this->contacts = [];
        $contactServiceFile = \sfConfig::get('sf_root_dir') .
            '/atom-ahg-plugins/ahgContactPlugin/lib/Extensions/Contact/Services/ContactInformationService.php';

        if (file_exists($contactServiceFile)) {
            require_once $contactServiceFile;
            if (class_exists('\\AhgContact\\Services\\ContactInformationService')) {
                $svc = new \AhgContact\Services\ContactInformationService();
                $this->contacts = $svc->getContactsForActor($actorId);
            }
        }

        // Fallback: load from base AtoM contact_information table
        if (empty($this->contacts)) {
            $this->contacts = \Illuminate\Database\Capsule\Manager::table('contact_information')
                ->leftJoin('contact_information_i18n as ci', function ($j) {
                    $j->on('contact_information.id', '=', 'ci.id')
                        ->where('ci.culture', '=', 'en');
                })
                ->where('contact_information.actor_id', $actorId)
                ->select('contact_information.*', 'ci.*')
                ->get()
                ->all();
        }

        // Use contactSuccess.php template
    }

    // =========================================================================
    // EAC-CPF EXPORT (#209)
    // =========================================================================

    public function executeApiEacExport(sfWebRequest $request)
    {
        $this->requireAuth();

        $actorId = (int) $request->getParameter('actorId');
        $service = $this->eacService();

        $xml = $service->exportActor($actorId);

        if ($xml) {
            $this->getResponse()->setContentType('application/xml');

            return $this->renderText($xml);
        }

        // Fallback: return identifier data as JSON
        $data = $service->getEacIdentifiers($actorId);

        return $this->jsonResponse(['success' => true, 'eac_data' => $data]);
    }

    // =========================================================================
    // CONFIG
    // =========================================================================

    public function executeConfig(sfWebRequest $request)
    {
        $this->requireAdmin();

        if ($request->isMethod('post')) {
            $settings = $request->getParameter('config', []);
            foreach ($settings as $key => $value) {
                \Illuminate\Database\Capsule\Manager::table('ahg_authority_config')
                    ->updateOrInsert(
                        ['config_key' => $key],
                        ['config_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
                    );
            }
            $this->getUser()->setFlash('notice', 'Configuration saved.');
            $this->redirect('@ahg_authority_config');
        }

        $this->config = \Illuminate\Database\Capsule\Manager::table('ahg_authority_config')
            ->get()
            ->keyBy('config_key')
            ->all();

        // Use configSuccess.php template
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    protected function getActorOrForward(int $actorId): object
    {
        $actor = \Illuminate\Database\Capsule\Manager::table('actor as a')
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->where('a.id', $actorId)
            ->select('a.id', 'a.entity_type_id', 'ai.authorized_form_of_name as name', 'slug.slug')
            ->first();

        if (!$actor) {
            $this->forward404();
        }

        return $actor;
    }
}
