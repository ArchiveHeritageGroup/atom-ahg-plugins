<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * ResourceSync SourceDescription endpoint.
 *
 * GET /.well-known/resourcesync — the discovery file every ResourceSync
 * aggregator looks up first. Points to the CapabilityList.
 *
 * @package    ahgResourceSyncPlugin
 * @subpackage resourcesync
 * @author     The Archive and Heritage Group (Pty) Ltd
 */
class resourcesyncSourceDescriptionAction extends AhgController
{
    // Public read-only XML endpoint — no CSRF token round-trip.
    protected bool $csrfProtection = false;

    public function executeSourceDescription($request)
    {
        require_once dirname(__FILE__, 4).'/lib/Services/ResourceSyncService.php';

        $service = new \AhgResourceSync\Services\ResourceSyncService();
        $xml = $service->sourceDescription();

        return $this->emitXml($xml);
    }

    /**
     * Emit a sitemap-formatted XML response in both standalone (Symfony
     * Response) and dual-stack (sfView::NONE) modes.
     */
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

        // Standalone fallback.
        return new \Symfony\Component\HttpFoundation\Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
