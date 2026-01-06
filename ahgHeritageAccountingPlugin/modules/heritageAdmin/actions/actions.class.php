<?php
use Illuminate\Database\Capsule\Manager as DB;

class heritageAdminActions extends sfActions
{
    public function preExecute()
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    // =====================
    // Dashboard
    // =====================
    public function executeIndex(sfWebRequest $request)
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
                ->get()
        ];
    }

    // =====================
    // Standards Management
    // =====================
    public function executeStandardList(sfWebRequest $request)
    {
        $this->standards = DB::table('heritage_accounting_standard')
            ->orderBy('sort_order')
            ->get();
    }

    public function executeStandardAdd(sfWebRequest $request)
    {
        $this->standard = null;
        $this->valuationMethods = $this->getValuationMethods();
        
        if ($request->isMethod('post')) {
            $this->saveStandard($request);
            $this->getUser()->setFlash('success', 'Accounting standard added successfully');
            $this->redirect(['module' => 'heritageAdmin', 'action' => 'standardList']);
        }
    }

    public function executeStandardEdit(sfWebRequest $request)
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

    public function executeStandardToggle(sfWebRequest $request)
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

    public function executeStandardDelete(sfWebRequest $request)
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
}
