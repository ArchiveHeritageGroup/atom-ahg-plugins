<?php

/**
 * ahgCDPAPlugin Configuration
 *
 * Zimbabwe Cyber and Data Protection Act [Chapter 12:07] compliance.
 * Regulated by POTRAZ (Postal and Telecommunications Regulatory Authority).
 */
class ahgCDPAPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Zimbabwe CDPA Compliance: Data protection under Cyber and Data Protection Act [Chapter 12:07]';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addStylesheet('/plugins/ahgCDPAPlugin/web/css/cdpa.css', 'last');
        $context->response->addJavascript('/plugins/ahgCDPAPlugin/web/js/cdpa.js', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'cdpa';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('cdpa');

        // Dashboard
        $router->any('ahg_cdpa_index', '/admin/cdpa', 'index');

        // License Management
        $router->any('ahg_cdpa_license', '/admin/cdpa/license', 'license');
        $router->any('ahg_cdpa_license_edit', '/admin/cdpa/license/edit', 'licenseEdit');

        // DPO Management
        $router->any('ahg_cdpa_dpo', '/admin/cdpa/dpo', 'dpo');
        $router->any('ahg_cdpa_dpo_edit', '/admin/cdpa/dpo/edit', 'dpoEdit');

        // Data Subject Requests
        $router->any('ahg_cdpa_requests', '/admin/cdpa/requests', 'requests');
        $router->any('ahg_cdpa_request_view', '/admin/cdpa/request/:id', 'requestView', ['id' => '\d+']);
        $router->any('ahg_cdpa_request_create', '/admin/cdpa/request/create', 'requestCreate');

        // Processing Register
        $router->any('ahg_cdpa_processing', '/admin/cdpa/processing', 'processing');
        $router->any('ahg_cdpa_processing_create', '/admin/cdpa/processing/create', 'processingCreate');
        $router->any('ahg_cdpa_processing_edit', '/admin/cdpa/processing/:id/edit', 'processingEdit', ['id' => '\d+']);

        // DPIA
        $router->any('ahg_cdpa_dpia', '/admin/cdpa/dpia', 'dpia');
        $router->any('ahg_cdpa_dpia_create', '/admin/cdpa/dpia/create', 'dpiaCreate');
        $router->any('ahg_cdpa_dpia_view', '/admin/cdpa/dpia/:id', 'dpiaView', ['id' => '\d+']);

        // Consent Management
        $router->any('ahg_cdpa_consent', '/admin/cdpa/consent', 'consent');

        // Breach Register
        $router->any('ahg_cdpa_breaches', '/admin/cdpa/breaches', 'breaches');
        $router->any('ahg_cdpa_breach_create', '/admin/cdpa/breach/create', 'breachCreate');
        $router->any('ahg_cdpa_breach_view', '/admin/cdpa/breach/:id', 'breachView', ['id' => '\d+']);

        // Reports
        $router->any('ahg_cdpa_reports', '/admin/cdpa/reports', 'reports');

        // Configuration
        $router->any('ahg_cdpa_config', '/admin/cdpa/config', 'config');

        $router->register($event->getSubject());
    }
}
