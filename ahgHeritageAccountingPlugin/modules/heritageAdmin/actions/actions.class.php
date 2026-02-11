<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class heritageAdminActions extends AhgController
{
    public function boot(): void
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    // =====================
    // Dashboard
    // =====================
    public function executeIndex($request)
    {
        $this->standards = DB::table('heritage_accounting_standard')
            ->orderBy('sort_order')
            ->get();

        $this->stats = [
            'total_assets' => DB::table('heritage_asset')->count(),
            'by_standard' => DB::table('heritage_asset as ha')
                ->join('heritage_accounting_standard as hs', 'ha.accounting_standard_id', '=', 'hs.id')
                ->select('hs.code', 'hs.name', DB::raw('COUNT(*) as count'))
                ->groupBy('hs.id', 'hs.code', 'hs.name')
                ->get(),
        ];

        // Get regions summary
        $this->regions = DB::table('heritage_regional_config')
            ->orderBy('region_name')
            ->get()
            ->map(function ($row) {
                $row->countries = json_decode($row->countries, true) ?? [];

                return $row;
            });

        // Get active region
        $this->activeConfig = DB::table('heritage_institution_config')
            ->whereNull('repository_id')
            ->first();
    }

    // =====================
    // Regions Management
    // =====================
    public function executeRegions($request)
    {
        $this->regions = DB::table('heritage_regional_config')
            ->orderBy('region_name')
            ->get()
            ->map(function ($row) {
                $row->countries = json_decode($row->countries, true) ?? [];

                return $row;
            });

        // Get installed standards count per region
        $this->standardsByRegion = DB::table('heritage_accounting_standard')
            ->whereNotNull('region_code')
            ->select('region_code', DB::raw('COUNT(*) as count'))
            ->groupBy('region_code')
            ->pluck('count', 'region_code')
            ->toArray();

        // Get compliance rules count per region
        $this->rulesByRegion = DB::table('heritage_compliance_rule as r')
            ->join('heritage_accounting_standard as s', 'r.standard_id', '=', 's.id')
            ->whereNotNull('s.region_code')
            ->select('s.region_code', DB::raw('COUNT(*) as count'))
            ->groupBy('s.region_code')
            ->pluck('count', 'region_code')
            ->toArray();

        // Get active region config
        $this->activeConfig = DB::table('heritage_institution_config')
            ->whereNull('repository_id')
            ->first();
    }

    public function executeRegionInstall($request)
    {
        $regionCode = $request->getParameter('region');

        if (!$regionCode) {
            $this->getUser()->setFlash('error', 'No region specified');
            $this->redirect(['module' => 'heritageAdmin', 'action' => 'regions']);
        }

        // Load RegionManager
        require_once $this->config('sf_root_dir') . '/plugins/ahgHeritageAccountingPlugin/lib/Regions/RegionManager.php';
        $manager = \RegionManager::getInstance();

        $result = $manager->installRegion($regionCode);

        if ($result['success']) {
            if (!empty($result['already_installed'])) {
                $this->getUser()->setFlash('notice', $result['message']);
            } else {
                $msg = "Region '{$regionCode}' installed successfully. ";
                $msg .= "Standard: {$result['standard_code']}. ";
                $msg .= "Compliance rules: {$result['compliance_rules_installed']}";
                $this->getUser()->setFlash('success', $msg);
            }
        } else {
            $this->getUser()->setFlash('error', 'Installation failed: ' . $result['error']);
        }

        $this->redirect(['module' => 'heritageAdmin', 'action' => 'regions']);
    }

    public function executeRegionUninstall($request)
    {
        $regionCode = $request->getParameter('region');

        if (!$regionCode) {
            $this->getUser()->setFlash('error', 'No region specified');
            $this->redirect(['module' => 'heritageAdmin', 'action' => 'regions']);
        }

        // Load RegionManager
        require_once $this->config('sf_root_dir') . '/plugins/ahgHeritageAccountingPlugin/lib/Regions/RegionManager.php';
        $manager = \RegionManager::getInstance();

        $result = $manager->uninstallRegion($regionCode);

        if ($result['success']) {
            $this->getUser()->setFlash('success', $result['message']);
        } else {
            $this->getUser()->setFlash('error', 'Uninstall failed: ' . $result['error']);
        }

        $this->redirect(['module' => 'heritageAdmin', 'action' => 'regions']);
    }

    public function executeRegionSetActive($request)
    {
        $regionCode = $request->getParameter('region');

        if (!$regionCode) {
            $this->getUser()->setFlash('error', 'No region specified');
            $this->redirect(['module' => 'heritageAdmin', 'action' => 'regions']);
        }

        // Load RegionManager
        require_once $this->config('sf_root_dir') . '/plugins/ahgHeritageAccountingPlugin/lib/Regions/RegionManager.php';
        $manager = \RegionManager::getInstance();

        $result = $manager->setActiveRegion($regionCode);

        if ($result['success']) {
            $this->getUser()->setFlash('success', "Active region set to '{$regionCode}' ({$result['standard_code']})");
        } else {
            $this->getUser()->setFlash('error', 'Activation failed: ' . $result['error']);
        }

        $this->redirect(['module' => 'heritageAdmin', 'action' => 'regions']);
    }

    public function executeRegionInfo($request)
    {
        $regionCode = $request->getParameter('region');

        $this->region = DB::table('heritage_regional_config')
            ->where('region_code', $regionCode)
            ->first();

        if (!$this->region) {
            $this->forward404();
        }

        $this->region->countries = json_decode($this->region->countries, true) ?? [];

        // Get standard if installed
        $this->standard = DB::table('heritage_accounting_standard')
            ->where('region_code', $regionCode)
            ->first();

        // Get compliance rules if installed
        $this->rules = [];
        if ($this->standard) {
            $this->rules = DB::table('heritage_compliance_rule')
                ->where('standard_id', $this->standard->id)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get();
        }

        // Check if active
        $activeConfig = DB::table('heritage_institution_config')
            ->whereNull('repository_id')
            ->first();
        $this->isActive = $activeConfig && $activeConfig->region_code === $regionCode;
    }

    // =====================
    // Standards Management
    // =====================
    public function executeStandardList($request)
    {
        $this->standards = DB::table('heritage_accounting_standard')
            ->orderBy('sort_order')
            ->get();
    }

    public function executeStandardAdd($request)
    {
        $this->standard = null;
        $this->valuationMethods = $this->getValuationMethods();
        
        if ($request->isMethod('post')) {
            $this->saveStandard($request);
            $this->getUser()->setFlash('success', 'Accounting standard added successfully');
            $this->redirect(['module' => 'heritageAdmin', 'action' => 'standardList']);
        }
    }

    public function executeStandardEdit($request)
    {
        $this->standard = DB::table('heritage_accounting_standard')
            ->where('id', $request->getParameter('id'))
            ->first();
        
        if (!$this->standard) {
            $this->forward404();
        }
        
        $this->valuationMethods = $this->getValuationMethods();
        
        if ($request->isMethod('post')) {
            $this->saveStandard($request, $this->standard->id);
            $this->getUser()->setFlash('success', 'Accounting standard updated successfully');
            $this->redirect(['module' => 'heritageAdmin', 'action' => 'standardList']);
        }
    }

    public function executeStandardToggle($request)
    {
        $id = $request->getParameter('id');
        $standard = DB::table('heritage_accounting_standard')->where('id', $id)->first();
        
        if ($standard) {
            DB::table('heritage_accounting_standard')
                ->where('id', $id)
                ->update(['is_active' => !$standard->is_active]);
        }
        
        $this->getUser()->setFlash('success', 'Standard status updated');
        $this->redirect(['module' => 'heritageAdmin', 'action' => 'standardList']);
    }

    public function executeStandardDelete($request)
    {
        $id = $request->getParameter('id');
        
        // Check if standard is in use
        $inUse = DB::table('heritage_asset')
            ->where('accounting_standard_id', $id)
            ->exists();
        
        if ($inUse) {
            $this->getUser()->setFlash('error', 'Cannot delete - standard is in use by heritage assets');
        } else {
            DB::table('heritage_accounting_standard')->where('id', $id)->delete();
            $this->getUser()->setFlash('success', 'Accounting standard deleted');
        }
        
        $this->redirect(['module' => 'heritageAdmin', 'action' => 'standardList']);
    }

    // =====================
    // Helpers
    // =====================
    protected function saveStandard(sfWebRequest $request, $id = null)
    {
        $data = [
            'code' => strtoupper($request->getParameter('code')),
            'name' => $request->getParameter('name'),
            'country' => $request->getParameter('country'),
            'description' => $request->getParameter('description'),
            'capitalisation_required' => $request->getParameter('capitalisation_required') ? 1 : 0,
            'valuation_methods' => json_encode($request->getParameter('valuation_methods', [])),
            'disclosure_requirements' => json_encode(array_filter(explode("\n", $request->getParameter('disclosure_requirements', '')))),
            'is_active' => $request->getParameter('is_active') ? 1 : 0,
            'sort_order' => (int)$request->getParameter('sort_order', 99)
        ];
        
        if ($id) {
            DB::table('heritage_accounting_standard')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            DB::table('heritage_accounting_standard')->insert($data);
        }
    }

    protected function getValuationMethods()
    {
        return [
            'cost' => 'Historical Cost',
            'fair_value' => 'Fair Value',
            'deemed_cost' => 'Deemed Cost',
            'revaluation' => 'Revaluation Model',
            'nominal' => 'Nominal Value (R1)',
            'insurance' => 'Insurance Value',
            'replacement' => 'Replacement Cost'
        ];
    }

    // =====================
    // Compliance Rules Management
    // =====================

    public function executeRuleList($request)
    {
        $this->standardId = $request->getParameter('standard_id');
        
        $this->standards = \Illuminate\Database\Capsule\Manager::table('heritage_accounting_standard')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        $query = \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule as r')
            ->join('heritage_accounting_standard as s', 'r.standard_id', '=', 's.id')
            ->select('r.*', 's.code as standard_code', 's.name as standard_name');

        if ($this->standardId) {
            $query->where('r.standard_id', $this->standardId);
        }

        $this->rules = $query->orderBy('s.sort_order')->orderBy('r.category')->orderBy('r.sort_order')->get();
    }

    public function executeRuleAdd($request)
    {
        $this->rule = null;
        $this->standards = \Illuminate\Database\Capsule\Manager::table('heritage_accounting_standard')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
        $this->categories = ['recognition', 'measurement', 'disclosure'];
        $this->checkTypes = ['required_field', 'value_check', 'date_check', 'custom'];
        $this->severities = ['error', 'warning', 'info'];
        $this->preselectedStandard = $request->getParameter('standard_id');

        if ($request->isMethod('post')) {
            $this->saveRule($request);
            $this->getUser()->setFlash('success', 'Compliance rule added');
            $this->redirect(['module' => 'heritageAdmin', 'action' => 'ruleList', 'standard_id' => $request->getParameter('standard_id')]);
        }
    }

    public function executeRuleEdit($request)
    {
        $this->rule = \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule')
            ->where('id', $request->getParameter('id'))
            ->first();

        if (!$this->rule) {
            $this->forward404();
        }

        $this->standards = \Illuminate\Database\Capsule\Manager::table('heritage_accounting_standard')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
        $this->categories = ['recognition', 'measurement', 'disclosure'];
        $this->checkTypes = ['required_field', 'value_check', 'date_check', 'custom'];
        $this->severities = ['error', 'warning', 'info'];

        if ($request->isMethod('post')) {
            $this->saveRule($request, $this->rule->id);
            $this->getUser()->setFlash('success', 'Compliance rule updated');
            $this->redirect(['module' => 'heritageAdmin', 'action' => 'ruleList', 'standard_id' => $this->rule->standard_id]);
        }
    }

    public function executeRuleToggle($request)
    {
        $id = $request->getParameter('id');
        $rule = \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule')->where('id', $id)->first();

        if ($rule) {
            \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule')
                ->where('id', $id)
                ->update(['is_active' => !$rule->is_active]);
        }

        $this->getUser()->setFlash('success', 'Rule status updated');
        $this->redirect(['module' => 'heritageAdmin', 'action' => 'ruleList', 'standard_id' => $rule->standard_id ?? null]);
    }

    public function executeRuleDelete($request)
    {
        $id = $request->getParameter('id');
        $rule = \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule')->where('id', $id)->first();
        $standardId = $rule->standard_id ?? null;

        \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule')->where('id', $id)->delete();

        $this->getUser()->setFlash('success', 'Compliance rule deleted');
        $this->redirect(['module' => 'heritageAdmin', 'action' => 'ruleList', 'standard_id' => $standardId]);
    }

    protected function saveRule(sfWebRequest $request, $id = null)
    {
        $data = [
            'standard_id' => (int)$request->getParameter('standard_id'),
            'category' => $request->getParameter('category'),
            'code' => strtoupper($request->getParameter('code')),
            'name' => $request->getParameter('name'),
            'description' => $request->getParameter('description'),
            'check_type' => $request->getParameter('check_type'),
            'field_name' => $request->getParameter('field_name'),
            'condition' => $request->getParameter('condition'),
            'error_message' => $request->getParameter('error_message'),
            'reference' => $request->getParameter('reference'),
            'severity' => $request->getParameter('severity'),
            'is_active' => $request->getParameter('is_active') ? 1 : 0,
            'sort_order' => (int)$request->getParameter('sort_order', 0)
        ];

        if ($id) {
            \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule')->insert($data);
        }
    }
}