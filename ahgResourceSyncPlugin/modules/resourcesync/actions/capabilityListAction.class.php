<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * ResourceSync CapabilityList endpoint.
 *
 * GET /resourcesync/capabilitylist.xml — describes which ResourceSync
 * capabilities this source offers (ResourceList + ChangeList).
 *
 * @package    ahgResourceSyncPlugin
 * @subpackage resourcesync
 * @author     The Archive and Heritage Group (Pty) Ltd
 */
class resourcesyncCapabilityListAction extends AhgController
{
    protected bool $csrfProtection = false;

    public function executeCapabilityList($request)
    {
        require_once dirname(__FILE__, 4).'/lib/Services/ResourceSyncService.php';

        $service = new \AhgResourceSync\Services\ResourceSyncService();
        $xml = $service->capabilityList();

        return $this->emitXml($xml);
    }

    private function emitXml(string $xml)
    {
        if (isset($this->response) && is_object($this->response)
            && method_exists($this->response, 'setContent')) {
            $this->response->setContentType('application/xml; charset=UTF-8');
            $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');
            $this->response->setHttpHeader('Cache-Control', 'public, max-age=3600');
            $this->response->setContent($xml);

            return class_exists('sfView', false) ? sfView::NONE : null;
        }

        return new \Symfony\Component\HttpFoundation\Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
