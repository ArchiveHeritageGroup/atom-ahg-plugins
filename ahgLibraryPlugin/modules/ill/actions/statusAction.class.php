<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Update ILL request status (POST).
 *
 * Expects: id, new_status, notes.
 * Redirects to ill/view.
 */
class illStatusAction extends AhgController
{
    public function execute($request)
    {
        
        // POST only
        if ('POST' !== $request->getMethod()) {
            $this->redirect(['module' => 'ill', 'action' => 'index']);
        }

        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load ILLService
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgLibraryPlugin/lib/Service/ILLService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $id = (int) $request->getParameter('id');
        $newStatus = $request->getParameter('new_status', '');
        $notes = $request->getParameter('notes', '');

        if (!$id || empty($newStatus)) {
            $this->getUser()->setFlash('error', __('Request ID and new status are required.'));
            $this->redirect(['module' => 'ill', 'action' => 'index']);
        }

        try {
            if (!class_exists('ILLService')) {
                throw new \RuntimeException('ILLService not available.');
            }

            $service = ILLService::getInstance();
            $result = $service->updateStatus($id, $newStatus, $notes ?: null);

            if ($result) {
                $this->getUser()->setFlash('notice', __('Status updated to "%1%".', ['%1%' => ucfirst($newStatus)]));
            } else {
                $this->getUser()->setFlash('error', __('Could not update status.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Status update error: %1%', ['%1%' => $e->getMessage()]));
        }

        $this->redirect(['module' => 'ill', 'action' => 'view', 'id' => $id]);
    }
}
