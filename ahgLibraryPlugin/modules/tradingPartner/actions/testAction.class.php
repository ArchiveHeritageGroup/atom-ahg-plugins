<?php

use AtomFramework\Http\Controllers\AhgController;

class tradingPartnerTestAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once dirname(__FILE__, 4).'/lib/Service/EdiAdapter.php';

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $id = (int) $request->getParameter('id');

        $partner = $id ? $db->table('library_trading_partner')->where('id', $id)->first() : null;
        if (!$partner) {
            return $this->renderJson(['ok' => false, 'message' => 'Trading partner not found.', 'details' => []], 404);
        }

        $adapter = new EdiAdapter($partner);

        return $this->renderJson($adapter->testConnection());
    }
}
