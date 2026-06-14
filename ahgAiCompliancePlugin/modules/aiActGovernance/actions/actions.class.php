<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * EU AI Act governance admin UI — AI system inventory, model registry,
 * Article 9 risk register, and conformity / oversight attestations.
 *
 * Complements the Article 12 inference receipt chain (aiCompliance module).
 */
class aiActGovernanceActions extends AhgController
{
    protected function requireAdmin(): void
    {
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !$user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }
    }

    protected function svc(): AiActGovernanceService
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/AiActGovernanceService.php';

        return new AiActGovernanceService();
    }

    // ---- Dashboard --------------------------------------------------------

    public function executeIndex($request)
    {
        $this->requireAdmin();
        $this->summary = $this->svc()->dashboardSummary();

        return sfView::SUCCESS;
    }

    // ---- AI system inventory ---------------------------------------------

    public function executeSystems($request)
    {
        $this->requireAdmin();
        $svc = $this->svc();

        if ($request->isMethod('post') && 'delete' === $request->getParameter('form_action')) {
            $svc->deleteSystem((int) $request->getParameter('id'));
            $this->redirect(['module' => 'aiActGovernance', 'action' => 'systems']);

            return;
        }

        $this->systems = $svc->listSystems();
        $this->svcRef = $svc;

        return sfView::SUCCESS;
    }

    public function executeSystemEdit($request)
    {
        $this->requireAdmin();
        $svc = $this->svc();

        if ($request->isMethod('post')) {
            $id = (int) $request->getParameter('id');
            $svc->saveSystem($request->getParameterHolder()->getAll(), $id ?: null);
            $this->redirect(['module' => 'aiActGovernance', 'action' => 'systems']);

            return;
        }

        $id = (int) $request->getParameter('id');
        $this->record = $id ? $svc->getSystem($id) : null;
        $this->svcRef = $svc;

        return sfView::SUCCESS;
    }

    // ---- Model registry ---------------------------------------------------

    public function executeModels($request)
    {
        $this->requireAdmin();
        $svc = $this->svc();

        if ($request->isMethod('post') && 'delete' === $request->getParameter('form_action')) {
            $svc->deleteModel((int) $request->getParameter('id'));
            $this->redirect(['module' => 'aiActGovernance', 'action' => 'models']);

            return;
        }

        $this->models = $svc->listModels();

        return sfView::SUCCESS;
    }

    public function executeModelEdit($request)
    {
        $this->requireAdmin();
        $svc = $this->svc();

        if ($request->isMethod('post')) {
            $id = (int) $request->getParameter('id');
            $svc->saveModel($request->getParameterHolder()->getAll(), $id ?: null);
            $this->redirect(['module' => 'aiActGovernance', 'action' => 'models']);

            return;
        }

        $id = (int) $request->getParameter('id');
        $this->record = $id ? $svc->getModel($id) : null;
        $this->systemOptions = $svc->systemOptions();
        $this->svcRef = $svc;

        return sfView::SUCCESS;
    }

    // ---- Risk register ----------------------------------------------------

    public function executeRisks($request)
    {
        $this->requireAdmin();
        $svc = $this->svc();

        if ($request->isMethod('post') && 'delete' === $request->getParameter('form_action')) {
            $svc->deleteRisk((int) $request->getParameter('id'));
            $this->redirect(['module' => 'aiActGovernance', 'action' => 'risks']);

            return;
        }

        $this->risks = $svc->listRisks();
        $this->svcRef = $svc;

        return sfView::SUCCESS;
    }

    public function executeRiskEdit($request)
    {
        $this->requireAdmin();
        $svc = $this->svc();

        if ($request->isMethod('post')) {
            $id = (int) $request->getParameter('id');
            $svc->saveRisk($request->getParameterHolder()->getAll(), $id ?: null);
            $this->redirect(['module' => 'aiActGovernance', 'action' => 'risks']);

            return;
        }

        $id = (int) $request->getParameter('id');
        $this->record = $id ? $svc->getRisk($id) : null;
        $this->systemOptions = $svc->systemOptions();
        $this->svcRef = $svc;

        return sfView::SUCCESS;
    }

    // ---- Attestations -----------------------------------------------------

    public function executeAttestations($request)
    {
        $this->requireAdmin();
        $svc = $this->svc();

        if ($request->isMethod('post') && 'delete' === $request->getParameter('form_action')) {
            $svc->deleteAttestation((int) $request->getParameter('id'));
            $this->redirect(['module' => 'aiActGovernance', 'action' => 'attestations']);

            return;
        }

        $this->attestations = $svc->listAttestations();
        $this->svcRef = $svc;

        return sfView::SUCCESS;
    }

    public function executeAttestationEdit($request)
    {
        $this->requireAdmin();
        $svc = $this->svc();

        if ($request->isMethod('post')) {
            $id = (int) $request->getParameter('id');
            $svc->saveAttestation($request->getParameterHolder()->getAll(), $id ?: null);
            $this->redirect(['module' => 'aiActGovernance', 'action' => 'attestations']);

            return;
        }

        $id = (int) $request->getParameter('id');
        $this->record = $id ? $svc->getAttestation($id) : null;
        $this->systemOptions = $svc->systemOptions();
        $this->svcRef = $svc;

        return sfView::SUCCESS;
    }
}
