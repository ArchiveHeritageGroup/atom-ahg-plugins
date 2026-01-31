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
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('ahg_cdpa_index', new sfRoute(
            '/admin/cdpa',
            ['module' => 'cdpa', 'action' => 'index']
        ));

        // License Management
        $routing->prependRoute('ahg_cdpa_license', new sfRoute(
            '/admin/cdpa/license',
            ['module' => 'cdpa', 'action' => 'license']
        ));

        $routing->prependRoute('ahg_cdpa_license_edit', new sfRoute(
            '/admin/cdpa/license/edit',
            ['module' => 'cdpa', 'action' => 'licenseEdit']
        ));

        // DPO Management
        $routing->prependRoute('ahg_cdpa_dpo', new sfRoute(
            '/admin/cdpa/dpo',
            ['module' => 'cdpa', 'action' => 'dpo']
        ));

        $routing->prependRoute('ahg_cdpa_dpo_edit', new sfRoute(
            '/admin/cdpa/dpo/edit',
            ['module' => 'cdpa', 'action' => 'dpoEdit']
        ));

        // Data Subject Requests
        $routing->prependRoute('ahg_cdpa_requests', new sfRoute(
            '/admin/cdpa/requests',
            ['module' => 'cdpa', 'action' => 'requests']
        ));

        $routing->prependRoute('ahg_cdpa_request_view', new sfRoute(
            '/admin/cdpa/request/:id',
            ['module' => 'cdpa', 'action' => 'requestView'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_cdpa_request_create', new sfRoute(
            '/admin/cdpa/request/create',
            ['module' => 'cdpa', 'action' => 'requestCreate']
        ));

        // Processing Register
        $routing->prependRoute('ahg_cdpa_processing', new sfRoute(
            '/admin/cdpa/processing',
            ['module' => 'cdpa', 'action' => 'processing']
        ));

        $routing->prependRoute('ahg_cdpa_processing_create', new sfRoute(
            '/admin/cdpa/processing/create',
            ['module' => 'cdpa', 'action' => 'processingCreate']
        ));

        $routing->prependRoute('ahg_cdpa_processing_edit', new sfRoute(
            '/admin/cdpa/processing/:id/edit',
            ['module' => 'cdpa', 'action' => 'processingEdit'],
            ['id' => '\d+']
        ));

        // DPIA
        $routing->prependRoute('ahg_cdpa_dpia', new sfRoute(
            '/admin/cdpa/dpia',
            ['module' => 'cdpa', 'action' => 'dpia']
        ));

        $routing->prependRoute('ahg_cdpa_dpia_create', new sfRoute(
            '/admin/cdpa/dpia/create',
            ['module' => 'cdpa', 'action' => 'dpiaCreate']
        ));

        $routing->prependRoute('ahg_cdpa_dpia_view', new sfRoute(
            '/admin/cdpa/dpia/:id',
            ['module' => 'cdpa', 'action' => 'dpiaView'],
            ['id' => '\d+']
        ));

        // Consent Management
        $routing->prependRoute('ahg_cdpa_consent', new sfRoute(
            '/admin/cdpa/consent',
            ['module' => 'cdpa', 'action' => 'consent']
        ));

        // Breach Register
        $routing->prependRoute('ahg_cdpa_breaches', new sfRoute(
            '/admin/cdpa/breaches',
            ['module' => 'cdpa', 'action' => 'breaches']
        ));

        $routing->prependRoute('ahg_cdpa_breach_create', new sfRoute(
            '/admin/cdpa/breach/create',
            ['module' => 'cdpa', 'action' => 'breachCreate']
        ));

        $routing->prependRoute('ahg_cdpa_breach_view', new sfRoute(
            '/admin/cdpa/breach/:id',
            ['module' => 'cdpa', 'action' => 'breachView'],
            ['id' => '\d+']
        ));

        // Reports
        $routing->prependRoute('ahg_cdpa_reports', new sfRoute(
            '/admin/cdpa/reports',
            ['module' => 'cdpa', 'action' => 'reports']
        ));

        // Configuration
        $routing->prependRoute('ahg_cdpa_config', new sfRoute(
            '/admin/cdpa/config',
            ['module' => 'cdpa', 'action' => 'config']
        ));
    }
}
