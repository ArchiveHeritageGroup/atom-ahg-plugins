<?php

use AtomFramework\Http\Controllers\AhgController;

class tradingPartnerPreviewAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/EdiAdapter.php';

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $id = (int) $request->getParameter('id');

        $partner = $id ? $db->table('library_trading_partner')->where('id', $id)->first() : null;
        if (!$partner) {
            return $this->renderJson(['ok' => false, 'message' => 'Trading partner not found.'], 404);
        }

        $illId = (int) $request->getParameter('ill_request_id');
        if (!$illId) {
            return $this->renderJson(['ok' => false, 'message' => 'ill_request_id required.'], 422);
        }

        $ill = $db->table('library_ill_request')->where('id', $illId)->first();
        if (!$ill) {
            return $this->renderJson(['ok' => false, 'message' => 'ILL request not found.'], 404);
        }

        $adapter = new EdiAdapter($partner);
        $msg = $adapter->buildIllRequestMessage($ill);

        return $this->renderJson([
            'ok'       => true,
            'msg_ref'  => $msg['msg_ref'],
            'type'     => $msg['type'],
            'envelope' => $msg['envelope'],
            'preview'  => $msg['raw'],
        ]);
    }
}
