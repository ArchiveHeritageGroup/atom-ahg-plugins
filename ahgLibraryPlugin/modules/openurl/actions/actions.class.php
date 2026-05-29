<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * OpenURL link resolver endpoint (#110). /openurl?rft.isbn=…&rft.title=…
 * Single match → redirect to the catalogue record; multiple → results list.
 */
class openurlActions extends AhgController
{
    public function executeIndex($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/OpenUrlResolver.php';

        $this->results = (new OpenUrlResolver())->resolve($request->getParameterHolder()->getAll());
        $this->query = $request->getParameter('rft.title', $request->getParameter('rft.isbn', $request->getParameter('rft.issn', '')));

        // Single unambiguous hit → go straight to the record.
        if (count($this->results) === 1 && !empty($this->results[0]->slug)) {
            $this->redirect(['module' => 'library', 'action' => 'index', 'slug' => $this->results[0]->slug]);
        }
        // 0 or many → render the results template.
    }
}
