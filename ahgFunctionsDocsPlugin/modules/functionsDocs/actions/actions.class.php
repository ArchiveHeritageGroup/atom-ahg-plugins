<?php

/**
 * functionsDocs actions (#148) — catalogue browser.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use AtomFramework\Http\Controllers\AhgController;

class functionsDocsActions extends AhgController
{
    public function executeCatalogue($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
            return;
        }
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward404('Administrator access required');
        }

        require_once $this->config('sf_root_dir').'/plugins/ahgFunctionsDocsPlugin/lib/Services/CatalogueService.php';
        $svc = new CatalogueService();

        $this->routes = $svc->routes();
        $this->tasks = $svc->tasks();
        $this->services = $svc->services();
        $this->counts = $svc->counts($this->routes, $this->tasks, $this->services);
        $this->search = trim((string) $request->getParameter('q', ''));
    }
}
