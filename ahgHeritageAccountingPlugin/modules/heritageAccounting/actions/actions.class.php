<?php
/**
 * Heritage Accounting Actions
 */
class heritageAccountingActions extends sfActions
{
    /**
     * Dashboard
     */
    public function executeDashboard(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        
        $this->standards = $service->getAccountingStandards();
        $this->classes = $service->getAssetClasses();
        $this->stats = $service->getDashboardStats(
            $request->getParameter('repository_id'),
            $request->getParameter('standard_id')
        );
        
        // Recent assets
        $result = $service->browse([], 10, 0);
        $this->recentAssets = $result['items'];
    }

    /**
     * Browse assets
     */
    public function executeBrowse(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        
        $this->standards = $service->getAccountingStandards();
        $this->classes = $service->getAssetClasses();
        
        $this->limit = sfConfig::get('app_hits_per_page', 25);
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $this->offset = ($this->page - 1) * $this->limit;
        
        $filters = [
            'standard_id' => $request->getParameter('standard_id'),
            'class_id' => $request->getParameter('class_id'),
            'recognition_status' => $request->getParameter('status'),
            'repository_id' => $request->getParameter('repository_id'),
            'search' => $request->getParameter('sq')
        ];
        
        $result = $service->browse(array_filter($filters), $this->limit, $this->offset);
        
        $this->assets = $result['items'];
        $this->total = $result['total'];
        $this->filters = $filters;
    }

    /**
     * View asset
     */
    public function executeView(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        
        $this->asset = $service->getAsset($request->getParameter('id'));
        if (!$this->asset) {
            $this->forward404('Heritage asset not found');
        }
        
        $this->valuations = $service->getValuationHistory($this->asset->id);
        // Fetch linked information object slug
        $this->objectSlug = null;
        $ioId = $this->asset->object_id ?? $this->asset->information_object_id;
        if ($ioId) {
            $slugRow = \Illuminate\Database\Capsule\Manager::table('slug')
                ->where('object_id', $ioId)
                ->first();
            $this->objectSlug = $slugRow ? $slugRow->slug : null;
        }
        $this->impairments = $service->getImpairmentAssessments($this->asset->id);
        $this->movements = $service->getMovements($this->asset->id);
        $this->journals = $service->getJournalEntries($this->asset->id);
    }

    /**
     * Add asset
     */
    public function executeAdd(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        $this->standards = $service->getAccountingStandards();
        $this->classes = $service->getAssetClasses();
        
        // Handle linking to information object
        $this->io = null;
        $this->ioId = $request->getParameter('io_id');
        if ($this->ioId) {
            $this->io = \Illuminate\Database\Capsule\Manager::table('information_object')
                ->leftJoin('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                ->where('information_object.id', $this->ioId)
                ->leftJoin('slug', function($join) { $join->on('information_object.id', '=', 'slug.object_id')->where('slug.slug', '!=', ''); })->select('information_object.*', 'information_object_i18n.title', 'slug.slug')
                ->first();
        }
        
        if ($request->isMethod('post')) {
            $data = $this->processFormData($request);
            $data['created_by'] = sfContext::getInstance()->getUser()->getAttribute('user_id');
            if ($request->getParameter('information_object_id')) {
                $data['information_object_id'] = (int) $request->getParameter('information_object_id');
            }
            try {
                $id = $service->create($data);
                $this->redirect(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $id]);
            } catch (Exception $e) {
                $this->error = $e->getMessage();
                $this->formData = $data;
            }
        }
    }

    /**
     * Edit asset
     */
    public function executeEdit(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        $this->asset = $service->getAsset($request->getParameter('id'));
        if (!$this->asset) {
            $this->forward404('Heritage asset not found');
        }
        // Fetch linked information object title
        $this->objectTitle = "Not linked";
        if ($this->asset->object_id || $this->asset->information_object_id) {
            $io = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
                ->where('id', $this->asset->object_id ?? $this->asset->information_object_id)
                ->where('culture', 'en')
                ->first();
            $this->objectTitle = $io ? $io->title : "No title found";
        }
        
        $this->standards = $service->getAccountingStandards();
        $this->classes = $service->getAssetClasses();
        if ($request->isMethod('post')) {
            $data = $this->processFormData($request);
            $data['updated_by'] = sfContext::getInstance()->getUser()->getAttribute('user_id');
            try {
                $service->update($this->asset->id, $data);
                $this->redirect(url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $this->asset->id]));
            } catch (Exception $e) {
                $this->error = $e->getMessage();
            }
        }
    }

    /**
     * Add valuation
     */
    public function executeAddValuation(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        
        $this->asset = $service->getAsset($request->getParameter('id'));
        if (!$this->asset) {
            $this->forward404('Heritage asset not found');
        }
        
        if ($request->isMethod('post')) {
            $data = [
                'valuation_date' => $request->getParameter('valuation_date'),
                'new_value' => (float) $request->getParameter('new_value'),
                'valuation_method' => $request->getParameter('valuation_method'),
                'valuer_name' => $request->getParameter('valuer_name'),
                'valuer_credentials' => $request->getParameter('valuer_credentials'),
                'valuer_organization' => $request->getParameter('valuer_organization'),
                'valuation_report_reference' => $request->getParameter('valuation_report_reference'),
                'notes' => $request->getParameter('notes'),
                'created_by' => sfContext::getInstance()->getUser()->getAttribute('user_id')
            ];
            
            try {
                $service->addValuation($this->asset->id, $data);
                $this->redirect(url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $this->asset->id]));
            } catch (Exception $e) {
                $this->error = $e->getMessage();
            }
        }
    }

    /**
     * Add impairment assessment
     */
    public function executeAddImpairment(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        
        $this->asset = $service->getAsset($request->getParameter('id'));
        if (!$this->asset) {
            $this->forward404('Heritage asset not found');
        }
        
        if ($request->isMethod('post')) {
            $data = [
                'assessment_date' => $request->getParameter('assessment_date'),
                'physical_damage' => $request->getParameter('physical_damage') ? 1 : 0,
                'physical_damage_details' => $request->getParameter('physical_damage_details'),
                'obsolescence' => $request->getParameter('obsolescence') ? 1 : 0,
                'obsolescence_details' => $request->getParameter('obsolescence_details'),
                'change_in_use' => $request->getParameter('change_in_use') ? 1 : 0,
                'change_in_use_details' => $request->getParameter('change_in_use_details'),
                'external_factors' => $request->getParameter('external_factors') ? 1 : 0,
                'external_factors_details' => $request->getParameter('external_factors_details'),
                'impairment_identified' => $request->getParameter('impairment_identified') ? 1 : 0,
                'recoverable_amount' => $request->getParameter('recoverable_amount') ?: null,
                'impairment_loss' => $request->getParameter('impairment_loss') ?: null,
                'assessor_name' => $request->getParameter('assessor_name'),
                'notes' => $request->getParameter('notes'),
                'created_by' => sfContext::getInstance()->getUser()->getAttribute('user_id')
            ];
            
            try {
                $service->addImpairment($this->asset->id, $data);
                $this->redirect(url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $this->asset->id]));
            } catch (Exception $e) {
                $this->error = $e->getMessage();
            }
        }
    }

    /**
     * Add movement
     */
    public function executeAddMovement(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        
        $this->asset = $service->getAsset($request->getParameter('id'));
        if (!$this->asset) {
            $this->forward404('Heritage asset not found');
        }
        
        if ($request->isMethod('post')) {
            $data = [
                'movement_date' => $request->getParameter('movement_date'),
                'movement_type' => $request->getParameter('movement_type'),
                'from_location' => $request->getParameter('from_location') ?: $this->asset->current_location,
                'to_location' => $request->getParameter('to_location'),
                'reason' => $request->getParameter('reason'),
                'authorized_by' => $request->getParameter('authorized_by'),
                'authorization_date' => $request->getParameter('authorization_date') ?: null,
                'expected_return_date' => $request->getParameter('expected_return_date') ?: null,
                'condition_on_departure' => $request->getParameter('condition_on_departure'),
                'insurance_confirmed' => $request->getParameter('insurance_confirmed') ? 1 : 0,
                'insurance_value' => $request->getParameter('insurance_value') ?: null,
                'created_by' => sfContext::getInstance()->getUser()->getAttribute('user_id')
            ];
            
            try {
                $service->addMovement($this->asset->id, $data);
                $this->redirect(url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $this->asset->id]));
            } catch (Exception $e) {
                $this->error = $e->getMessage();
            }
        }
    }

    /**
     * Add journal entry
     */
    public function executeAddJournal(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        
        $this->asset = $service->getAsset($request->getParameter('id'));
        if (!$this->asset) {
            $this->forward404('Heritage asset not found');
        }
        
        if ($request->isMethod('post')) {
            $data = [
                'journal_date' => $request->getParameter('journal_date'),
                'journal_number' => $request->getParameter('journal_number'),
                'journal_type' => $request->getParameter('journal_type'),
                'debit_account' => $request->getParameter('debit_account'),
                'debit_amount' => (float) $request->getParameter('debit_amount'),
                'credit_account' => $request->getParameter('credit_account'),
                'credit_amount' => (float) $request->getParameter('credit_amount'),
                'description' => $request->getParameter('description'),
                'reference_document' => $request->getParameter('reference_document'),
                'fiscal_year' => $request->getParameter('fiscal_year') ?: date('Y'),
                'fiscal_period' => $request->getParameter('fiscal_period'),
                'created_by' => sfContext::getInstance()->getUser()->getAttribute('user_id')
            ];
            
            try {
                $service->addJournal($this->asset->id, $data);
                $this->redirect(url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $this->asset->id]));
            } catch (Exception $e) {
                $this->error = $e->getMessage();
            }
        }
    }

    /**
     * Process form data
     */
    protected function processFormData(sfWebRequest $request): array
    {
        return [
            'object_id' => $request->getParameter('object_id') ? (int) $request->getParameter('object_id') : null,
            'accounting_standard_id' => $request->getParameter('accounting_standard_id') ?: null,
            'recognition_status' => $request->getParameter('recognition_status'),
            'recognition_status_reason' => $request->getParameter('recognition_status_reason'),
            'recognition_date' => $request->getParameter('recognition_date') ?: null,
            'asset_class_id' => $request->getParameter('asset_class_id') ?: null,
            'asset_sub_class' => $request->getParameter('asset_sub_class'),
            'measurement_basis' => $request->getParameter('measurement_basis'),
            'acquisition_method' => $request->getParameter('acquisition_method'),
            'acquisition_date' => $request->getParameter('acquisition_date') ?: null,
            'acquisition_cost' => (float) $request->getParameter('acquisition_cost', 0),
            'fair_value_at_acquisition' => $request->getParameter('fair_value_at_acquisition') ?: null,
            'nominal_value' => (float) $request->getParameter('nominal_value', 1),
            'donor_name' => $request->getParameter('donor_name'),
            'donor_restrictions' => $request->getParameter('donor_restrictions'),
            'initial_carrying_amount' => (float) $request->getParameter('initial_carrying_amount', 0),
            'current_carrying_amount' => (float) $request->getParameter('current_carrying_amount', 0),
            'heritage_significance' => $request->getParameter('heritage_significance'),
            'significance_statement' => $request->getParameter('significance_statement'),
            'restrictions_on_use' => $request->getParameter('restrictions_on_use'),
            'restrictions_on_disposal' => $request->getParameter('restrictions_on_disposal'),
            'conservation_requirements' => $request->getParameter('conservation_requirements'),
            'insurance_required' => $request->getParameter('insurance_required') ? 1 : 0,
            'insurance_value' => $request->getParameter('insurance_value') ?: null,
            'insurance_policy_number' => $request->getParameter('insurance_policy_number'),
            'insurance_provider' => $request->getParameter('insurance_provider'),
            'insurance_expiry_date' => $request->getParameter('insurance_expiry_date') ?: null,
            'current_location' => $request->getParameter('current_location'),
            'storage_conditions' => $request->getParameter('storage_conditions'),
            'condition_rating' => $request->getParameter('condition_rating'),
            'notes' => $request->getParameter('notes')
        ];
    }

    /**
     * Settings - enable/disable accounting standards
     */
    public function executeSettings(sfWebRequest $request)
    {
        $this->standards = \Illuminate\Database\Capsule\Manager::table('heritage_accounting_standard')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
        
        if ($request->isMethod('post')) {
            $enabled = $request->getParameter('enabled', []);
            
            \Illuminate\Database\Capsule\Manager::table('heritage_accounting_standard')
                ->update(['is_active' => 0]);
            
            if (!empty($enabled)) {
                \Illuminate\Database\Capsule\Manager::table('heritage_accounting_standard')
                    ->whereIn('id', $enabled)
                    ->update(['is_active' => 1]);
            }
            
            $this->getUser()->setFlash('notice', 'Settings saved successfully.');
            $this->redirect(['module' => 'heritageAccounting', 'action' => 'settings']);
        }
    }

    /**
     * View heritage asset linked to information object
     */
    public function executeViewByObject(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        
        // Get information object
        $io = \Illuminate\Database\Capsule\Manager::table('slug')
            ->join('information_object', 'slug.object_id', '=', 'information_object.id')
            ->where('slug.slug', $slug)
            ->select('information_object.*')
            ->first();
        
        if (!$io) {
            $this->forward404('Record not found');
        }
        
        // Find linked heritage asset
        $asset = \Illuminate\Database\Capsule\Manager::table('heritage_asset')
            ->where('information_object_id', $io->id)
            ->first();
        
        if ($asset) {
            $this->redirect(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]);
        }
        
        // No asset yet - offer to create
        $this->io = $io;
        $this->slug = $slug;
    }

    /**
     * Edit heritage asset linked to information object
     */
    public function executeEditByObject(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        
        // Get information object
        $io = \Illuminate\Database\Capsule\Manager::table('slug')
            ->join('information_object', 'slug.object_id', '=', 'information_object.id')
            ->where('slug.slug', $slug)
            ->select('information_object.*')
            ->first();
        
        if (!$io) {
            $this->forward404('Record not found');
        }
        
        // Find linked heritage asset
        $asset = \Illuminate\Database\Capsule\Manager::table('heritage_asset')
            ->where('information_object_id', $io->id)
            ->first();
        
        if ($asset) {
            $this->redirect(['module' => 'heritageAccounting', 'action' => 'edit', 'id' => $asset->id]);
        }
        
        // No asset yet - redirect to create with object link
        $this->redirect(['module' => 'heritageAccounting', 'action' => 'add', 'io_id' => $io->id]);
    }
}
