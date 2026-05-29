<?php

use AtomFramework\Http\Controllers\AhgController;

class tradingPartnerIndexAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        $db = \Illuminate\Database\Capsule\Manager::connection();

        $this->search   = trim((string) $request->getParameter('search', ''));
        $this->ediType  = trim((string) $request->getParameter('edi_type', ''));
        $this->active   = (string) $request->getParameter('active', '');

        $query = $db->table('library_trading_partner');
        if ($this->search !== '') {
            $query->where('edi_partner_code', 'LIKE', '%' . $this->search . '%');
        }
        if ($this->ediType !== '') {
            $query->where('edi_type', $this->ediType);
        }
        if ($this->active === '1') {
            $query->where('is_active', 1);
        } elseif ($this->active === '0') {
            $query->where('is_active', 0);
        }

        $this->partners = $query->orderBy('edi_partner_code')->get()->all();

        $this->stats = [
            'total'  => $db->table('library_trading_partner')->count(),
            'active' => $db->table('library_trading_partner')->where('is_active', 1)->count(),
            'errors' => $db->table('library_trading_partner')->whereNotNull('last_error_at')->count(),
            'sftp'   => $db->table('library_trading_partner')->where('endpoint_type', 'SFTP')->count(),
            'as2'    => $db->table('library_trading_partner')->where('endpoint_type', 'AS2')->count(),
        ];
    }
}
