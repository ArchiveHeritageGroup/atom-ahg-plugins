<?php

use AtomFramework\Http\Controllers\AhgController;

class tradingPartnerDeleteAction extends AhgController
{
    public function execute($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404();
        }

        $inUse = $db->table('library_ill_request')->where('trading_partner_id', $id)->exists();
        if ($inUse) {
            $this->getUser()->setFlash('error', __('Cannot delete: partner is linked to ILL requests. Deactivate instead.'));
            $this->redirect(['module' => 'tradingPartner', 'action' => 'index']);
        }

        $db->table('library_trading_partner')->where('id', $id)->delete();
        $this->getUser()->setFlash('notice', __('Trading partner deleted.'));
        $this->redirect(['module' => 'tradingPartner', 'action' => 'index']);
    }
}
