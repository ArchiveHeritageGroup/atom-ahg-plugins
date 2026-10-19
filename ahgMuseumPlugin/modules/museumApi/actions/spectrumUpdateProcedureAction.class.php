<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Spectrum Procedure Update API
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

class museumApiSpectrumUpdateProcedureAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);
            echo json_encode(['error' => 'Unauthorized']);
            return sfView::NONE;
        }

        if (!$request->isMethod('post')) {
            $this->getResponse()->setStatusCode(405);
            echo json_encode(['error' => 'Method not allowed']);
            return sfView::NONE;
        }

        $objectId = $request->getParameter('objectId');
        $procedureId = $request->getParameter('procedureId');
        $status = $request->getParameter('status');
        $notes = $request->getParameter('notes');
        $dueDate = $request->getParameter('dueDate');
        $assignedTo = $request->getParameter('assignedTo');

        if (!$objectId || !$procedureId || !$status) {
            $this->getResponse()->setStatusCode(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return sfView::NONE;
        }

        // The workflow service belongs to the collections-procedures plugin and
        // is not autoloaded here; without it the call died as a bare HTML 500.
        $service = sfConfig::get('sf_plugins_dir').'/ahgSpectrumPlugin/lib/ahgSpectrumWorkflowService.class.php';
        if (!is_file($service)) {
            $this->getResponse()->setStatusCode(501);
            echo json_encode(['error' => 'Collections procedures plugin is not installed']);
            return sfView::NONE;
        }
        require_once $service;

        try {
            $result = ahgSpectrumWorkflowService::updateProcedureStatus(
                $objectId,
                $procedureId,
                $status,
                $notes,
                $this->context->user->getAttribute('user_id')
            );

            echo json_encode([
                'success' => true,
                'procedure' => $procedureId,
                'status' => $status,
                'updated' => $result
            ]);

        } catch (\Throwable $e) {
            $this->getResponse()->setStatusCode(500);
            echo json_encode([
                'error' => 'Update failed',
                'message' => $e->getMessage()
            ]);
        }

        return sfView::NONE;
    }
}
