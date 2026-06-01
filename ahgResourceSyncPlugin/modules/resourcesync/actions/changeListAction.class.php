<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * ResourceSync ChangeList endpoint.
 *
 * GET /resourcesync/changelist.xml — records updated or tombstoned within the
 * configured horizon (default 30 days), paginated via ?page=N. Each entry
 * carries change="created"|"updated"|"deleted".
 *
 * @package    ahgResourceSyncPlugin
 * @subpackage resourcesync
 * @author     The Archive and Heritage Group (Pty) Ltd
 */
class resourcesyncChangeListAction extends AhgController
{
    protected bool $csrfProtection = false;

    public function executeChangeList($request)
    {
        require_once dirname(__FILE__, 4).'/lib/Services/ResourceSyncService.php';

        $page = (int) $request->getParameter('page', 1);

        $service = new \AhgResourceSync\Services\ResourceSyncService();
        $xml = $service->changeList($page);

        return $this->emitXml($xml);
    }

    private function emitXml(string $xml)
    {
        if (isset($this->response) && is_object($this->response)
            && method_exists($this->response, 'setContent')) {
            $this->response->setContentType('application/xml; charset=UTF-8');
            $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');
            $this->response->setHttpHeader('Cache-Control', 'public, max-age=900');
            $this->response->setContent($xml);

            return class_exists('sfView', false) ? sfView::NONE : null;
        }

        return new \Symfony\Component\HttpFoundation\Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}
