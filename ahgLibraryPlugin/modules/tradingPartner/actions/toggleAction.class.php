<?php

use AtomFramework\Http\Controllers\AhgController;

class tradingPartnerToggleAction extends AhgController
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

        $partner = $db->table('library_trading_partner')->where('id', $id)->first();
        if (!$partner) {
            $this->forward404();
        }

        $newState = empty($partner->is_active) ? 1 : 0;
        $db->table('library_trading_partner')->where('id', $id)->update([
            'is_active'  => $newState,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->getUser()->setFlash('notice', $newState ? __('Partner activated.') : __('Partner deactivated.'));
        $this->redirect(['module' => 'tradingPartner', 'action' => 'index']);
    }
}
