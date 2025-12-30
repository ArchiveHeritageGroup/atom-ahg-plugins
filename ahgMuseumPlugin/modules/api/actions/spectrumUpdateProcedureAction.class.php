<?php
/**
 * Spectrum Procedure Update API
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

class apiSpectrumUpdateProcedureAction extends sfAction
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

        try {
            $result = arSpectrumWorkflowService::updateProcedureStatus(
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

        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);
            echo json_encode([
                'error' => 'Update failed',
                'message' => $e->getMessage()
            ]);
        }

        return sfView::NONE;
    }
}
