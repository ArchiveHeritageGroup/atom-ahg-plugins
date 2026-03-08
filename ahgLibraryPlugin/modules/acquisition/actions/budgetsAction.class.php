<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Budget management — list and create budgets.
 */
class acquisitionBudgetsAction extends AhgController
{
    public function execute($request)
    {

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AcquisitionService.php';

        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        $service = AcquisitionService::getInstance();

        // Handle POST — create budget
        if ('POST' === $request->getMethod()) {
            $this->createBudget($request, $service);

            return;
        }

        // Fiscal year filter
        $this->fiscalYear = $request->getParameter('fiscal_year', date('Y'));

        try {
            $this->budgets = $service->getBudgets($this->fiscalYear);
        } catch (\Exception $e) {
            $this->budgets = [];
        }
    }

    protected function createBudget($request, AcquisitionService $service): void
    {
        $budgetName = trim($request->getParameter('budget_name', ''));
        if (empty($budgetName)) {
            $this->getUser()->setFlash('error', __('Budget name is required.'));
            $this->redirect(['module' => 'acquisition', 'action' => 'budgets']);
        }

        try {
            $service->createBudget([
                'budget_name' => $budgetName,
                'budget_code' => trim($request->getParameter('budget_code', '')) ?: null,
                'fiscal_year' => $request->getParameter('fiscal_year', date('Y')),
                'allocated_amount' => (float) $request->getParameter('allocated_amount', 0),
                'currency' => $request->getParameter('currency', 'USD'),
                'category' => trim($request->getParameter('category', 'general')),
                'notes' => trim($request->getParameter('notes', '')) ?: null,
            ]);

            $this->getUser()->setFlash('notice', __('Budget created successfully.'));
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Failed to create budget: %1%', ['%1%' => $e->getMessage()]));
        }

        $this->redirect(['module' => 'acquisition', 'action' => 'budgets']);
    }
}
