<?php

/**
 * ahgOcflPlugin - OCFL v1.1 (Oxford Common File Layout) preservation storage.
 *
 * Storage-root management, content-addressed object versioning with
 * deterministic inventory.json + SHA-512 digests, per-version directories,
 * fixity verification and tar export. Ported from the Heratio ahg-ocfl
 * package into AtoM/Symfony 1.x conventions.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */
class ahgOcflPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'OCFL v1.1 preservation storage: storage-root management, content-addressed object versioning (inventory.json + SHA-512), version dirs, fixity verification, export';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->getConfiguration()->loadHelpers(['Asset', 'Url']);
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'ocfl';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();
        $r = new \AtomFramework\Routing\RouteLoader('ocfl');

        // Admin dashboard
        $r->any('ocfl_index', '/admin/ocfl', 'index');

        // API endpoints
        $r->any('ocfl_api_init', '/api/ocfl/init', 'apiInit');
        $r->any('ocfl_api_verify_all', '/api/ocfl/verify-all', 'apiVerifyAll');
        $r->any('ocfl_api_ingest', '/api/ocfl/ingest/:id', 'apiIngest', ['id' => '\d+']);
        $r->any('ocfl_api_verify', '/api/ocfl/verify/:id', 'apiVerify', ['id' => '\d+']);
        $r->any('ocfl_api_export', '/api/ocfl/export/:id', 'apiExport', ['id' => '\d+']);

        $r->register($routing);
    }
}
