<?php

use AtomFramework\Http\Controllers\AhgController;

class serialBinderyAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/SerialService.php';

        $svc = new SerialService();

        if ($request->isMethod('post')) {
            $op = $request->getParameter('op');

            if ($op === 'send') {
                $ids = array_filter((array) $request->getParameter('issue_ids', []));
                if (empty($ids)) {
                    $this->getUser()->setFlash('error', __('Select at least one issue to send to bindery.'));
                } else {
                    $svc->createBinderyBatch(
                        $ids,
                        $request->getParameter('vendor_id') ?: null,
                        $request->getParameter('notes') ?: null,
                        (int) $this->getUser()->getAttribute('user_id') ?: null
                    );
                    $this->getUser()->setFlash('notice', __('Bindery batch created and sent.'));
                }
            } elseif ($op === 'receive') {
                $svc->receiveBinderyBatch((int) $request->getParameter('batch_id'));
                $this->getUser()->setFlash('notice', __('Bindery batch received; issues marked bound.'));
            }

            $this->redirect(['module' => 'serial', 'action' => 'bindery']);
        }

        $this->batches  = $svc->listBinderyBatches();
        $this->bindable = $svc->getBindableIssues();
    }
}
