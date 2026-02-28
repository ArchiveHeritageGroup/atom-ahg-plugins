<?php

class accessionAppraisalActions extends sfActions
{
    protected function getAppraisalService(): \AhgAccessionManage\Services\AccessionAppraisalService
    {
        $intakeService = new \AhgAccessionManage\Services\AccessionIntakeService();

        return new \AhgAccessionManage\Services\AccessionAppraisalService(null, $intakeService);
    }

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

    // =========================================================================
    // APPRAISAL
    // =========================================================================

    public function executeAppraisal(sfWebRequest $request)
    {
        $this->requireAuth();

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getAppraisalService();

        $this->accession = \AhgAccessionManage\Services\AccessionCrudService::getById($accessionId);
        if (!$this->accession) {
            $this->forward404();
        }

        $this->appraisals = $service->getAppraisalsForAccession($accessionId);
        $this->templates = $service->listTemplates();

        // If editing an existing appraisal
        $appraisalId = (int) $request->getParameter('appraisal_id', 0);
        $this->currentAppraisal = null;
        if ($appraisalId) {
            $this->currentAppraisal = $service->getAppraisal($appraisalId);
        }

        // Use Success.php template (Symfony default)
    }

    public function executeAppraisalSave(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getAppraisalService();

        $data = [
            'appraiser_id' => $request->getParameter('appraiser_id') ?: $userId,
            'appraisal_type' => $request->getParameter('appraisal_type', 'archival'),
            'monetary_value' => $request->getParameter('monetary_value') ?: null,
            'currency' => $request->getParameter('currency', 'ZAR'),
            'significance' => $request->getParameter('significance') ?: null,
            'recommendation' => $request->getParameter('recommendation', 'pending'),
            'summary' => $request->getParameter('summary', ''),
            'detailed_notes' => $request->getParameter('detailed_notes', ''),
            'appraised_at' => $request->getParameter('appraised_at') ?: date('Y-m-d H:i:s'),
        ];

        $appraisalId = (int) $request->getParameter('appraisal_id', 0);

        if ($appraisalId) {
            $service->updateAppraisal($appraisalId, $data, $userId);

            // Update criteria scores
            $scores = $request->getParameter('scores', []);
            foreach ($scores as $criterionId => $score) {
                $service->updateCriterion((int) $criterionId, ['score' => (int) $score]);
            }

            $this->getUser()->setFlash('notice', 'Appraisal updated.');
        } else {
            $appraisalId = $service->createAppraisal($accessionId, $data, $userId);

            // Apply template if specified
            $templateId = (int) $request->getParameter('template_id', 0);
            if ($templateId) {
                $service->applyTemplate($appraisalId, $templateId);
            }

            $this->getUser()->setFlash('notice', 'Appraisal created.');
        }

        $this->redirect('@accession_appraisal_form?id=' . $accessionId . '&appraisal_id=' . $appraisalId);
    }

    public function executeApiAppraisalScore(sfWebRequest $request)
    {
        $this->requireEditor();

        $criterionId = (int) $request->getParameter('id');
        $score = (int) $request->getParameter('score');
        $service = $this->getAppraisalService();
        $result = $service->updateCriterion($criterionId, ['score' => $score]);

        // Recalculate weighted score
        $criterion = \Illuminate\Database\Capsule\Manager::table('accession_appraisal_criterion')
            ->where('id', $criterionId)
            ->first();

        $weightedScore = null;
        if ($criterion) {
            $weightedScore = $service->calculateWeightedScore($criterion->appraisal_id);
        }

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode([
            'success' => $result,
            'weighted_score' => $weightedScore,
        ]));
    }

    // =========================================================================
    // VALUATION
    // =========================================================================

    public function executeValuation(sfWebRequest $request)
    {
        $this->requireAuth();

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getAppraisalService();

        $this->accession = \AhgAccessionManage\Services\AccessionCrudService::getById($accessionId);
        if (!$this->accession) {
            $this->forward404();
        }

        $this->valuations = $service->getValuationHistory($accessionId);
        $this->currentValuation = $service->getCurrentValuation($accessionId);

        // Use Success.php template (Symfony default)
    }

    public function executeValuationAdd(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getAppraisalService();

        $data = [
            'valuation_type' => $request->getParameter('valuation_type', 'initial'),
            'monetary_value' => (float) $request->getParameter('monetary_value'),
            'currency' => $request->getParameter('currency', 'ZAR'),
            'valuation_date' => $request->getParameter('valuation_date') ?: date('Y-m-d'),
            'valuer' => $request->getParameter('valuer', ''),
            'method' => $request->getParameter('method', ''),
            'reference_document' => $request->getParameter('reference_document', ''),
            'notes' => $request->getParameter('notes', ''),
        ];

        $service->recordValuation($accessionId, $data, $userId);
        $this->getUser()->setFlash('notice', 'Valuation recorded.');
        $this->redirect('@accession_valuation_view?id=' . $accessionId);
    }

    // =========================================================================
    // TEMPLATES
    // =========================================================================

    public function executeAppraisalTemplates(sfWebRequest $request)
    {
        $this->requireEditor();

        $service = $this->getAppraisalService();

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action', '');

            if ($action === 'create') {
                $criteria = [];
                $names = $request->getParameter('criterion_names', []);
                $weights = $request->getParameter('criterion_weights', []);
                $descriptions = $request->getParameter('criterion_descriptions', []);
                foreach ($names as $i => $name) {
                    if (!empty(trim($name))) {
                        $criteria[] = [
                            'criterion_name' => $name,
                            'weight' => (float) ($weights[$i] ?? 1.0),
                            'description' => $descriptions[$i] ?? '',
                        ];
                    }
                }

                \Illuminate\Database\Capsule\Manager::table('accession_appraisal_template')->insert([
                    'name' => $request->getParameter('name'),
                    'description' => $request->getParameter('description', ''),
                    'criteria' => json_encode($criteria),
                    'sector' => $request->getParameter('sector') ?: null,
                    'is_default' => $request->getParameter('is_default') ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $this->getUser()->setFlash('notice', 'Template created.');
            } elseif ($action === 'delete') {
                $templateId = (int) $request->getParameter('template_id');
                \Illuminate\Database\Capsule\Manager::table('accession_appraisal_template')
                    ->where('id', $templateId)
                    ->delete();
                $this->getUser()->setFlash('notice', 'Template deleted.');
            }

            $this->redirect('@accession_appraisal_templates');
        }

        $this->templates = $service->listTemplates();

        // Use Success.php template (Symfony default)
    }

    // =========================================================================
    // VALUATION REPORT
    // =========================================================================

    public function executeValuationReport(sfWebRequest $request)
    {
        $this->requireAuth();

        $service = $this->getAppraisalService();
        $this->report = $service->getValuationReport();

        // Get recent valuations
        $this->recentValuations = \Illuminate\Database\Capsule\Manager::table('accession_valuation_history as vh')
            ->join('accession as a', 'vh.accession_id', '=', 'a.id')
            ->leftJoin('accession_i18n as ai', function ($j) {
                $j->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->select(
                'vh.*',
                'a.identifier',
                'ai.title as accession_title',
                'slug.slug'
            )
            ->orderBy('vh.created_at', 'desc')
            ->limit(50)
            ->get()
            ->all();

        // Use Success.php template (Symfony default)
    }
}
