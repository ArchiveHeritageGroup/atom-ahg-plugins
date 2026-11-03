<?php

/**
 * /donor/dashboard when base AtoM's /:module/:action route resolves it before
 * this plugin's donor_dashboard route (it does on stock AtoM sites such as RARI,
 * where that URL otherwise reads as module "donor", action "dashboard" and 404s).
 */
class donorDashboardAction extends sfAction
{
    public function execute($request)
    {
        $this->forward('donorAgreement', 'dashboard');
    }
}
