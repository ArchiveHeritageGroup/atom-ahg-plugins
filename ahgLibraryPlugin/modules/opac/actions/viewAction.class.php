<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

/**
 * OPAC view — single catalog item detail.
 *
 * @package    ahgLibraryPlugin
 * @subpackage opac
 */
class opacViewAction extends AhgController
{
    public function execute($request)
    {
        // Initialize database
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load OpacService
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/OpacService.php';

        $id = (int) $request->getParameter('id', 0);

        if ($id <= 0) {
            $this->forward404(__('No catalog item specified.'));
        }

        $service = OpacService::getInstance();
        $this->detail = $service->getItemDetail($id);

        if (!$this->detail) {
            $this->forward404(__('Catalog item not found.'));
        }
    }
}
