<?php

class ahgPrivacyPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Privacy Compliance Management (POPIA, GDPR, PIPEDA, CCPA)';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'privacy';
        $enabledModules[] = 'privacyAdmin';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register PII redaction provider with framework
        $this->registerProviders();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        $this->dispatcher->connect('response.filter_content', [$this, 'addAssets']);
    }

    /**
     * Add CSS/JS assets for the visual redaction editor
     */
    public function addAssets(sfEvent $event, $content)
    {
        $response = $event->getSubject();
        $request = sfContext::getInstance()->getRequest();

        // Only add assets for visual redaction editor page
        $module = $request->getParameter('module');
        $action = $request->getParameter('action');

        if ($module === 'privacyAdmin' && $action === 'visualRedactionEditor') {
            $pluginPath = '/plugins/ahgPrivacyPlugin';

            // Add CSS before </head>
            $css = <<<HTML
    <!-- Visual Redaction Editor Assets -->
    <link rel="stylesheet" href="{$pluginPath}/css/redaction-annotator.css">
    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <!-- Fabric.js -->
    <script src="/plugins/ahgCorePlugin/web/js/vendor/fabric.min.js"></script>
HTML;
            $content = str_replace('</head>', $css . "\n</head>", $content);

            // Add JS before </body>
            $js = <<<HTML
    <script src="{$pluginPath}/js/redaction-annotator.js"></script>
HTML;
            $content = str_replace('</body>', $js . "\n</body>", $content);
        }

        return $content;
    }

    /**
     * Register providers with the framework.
     */
    protected function registerProviders(): void
    {
        // Only register if framework is loaded
        if (!class_exists('AtomFramework\\Providers')) {
            return;
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Provider/PiiRedactionProvider.php';

        \AtomFramework\Providers::register(
            'pii_redaction',
            new \ahgPrivacyPlugin\Provider\PiiRedactionProvider()
        );
    }

    public function addRoutes(sfEvent $event)
    {
        $privacy = new \AtomFramework\Routing\RouteLoader('privacy');

        // Dashboard
        $privacy->any('privacy_dashboard', '/privacy', 'dashboard');

        // DSAR Management
        $privacy->any('privacy_dsar_index', '/privacy/dsar', 'dsarIndex');
        $privacy->any('privacy_dsar_new', '/privacy/dsar/new', 'dsarNew');
        $privacy->any('privacy_dsar_view', '/privacy/dsar/:id', 'dsarView');
        $privacy->any('privacy_dsar_update', '/privacy/dsar/:id/update', 'dsarUpdate');

        // Breach Register
        $privacy->any('privacy_breach_index', '/privacy/breaches', 'breachIndex');
        $privacy->any('privacy_breach_new', '/privacy/breach/new', 'breachNew');
        $privacy->any('privacy_breach_view', '/privacy/breach/:id', 'breachView');

        // Consent Management
        $privacy->any('privacy_consent_index', '/privacy/consent', 'consentIndex');

        // Processing Activities (ROPA)
        $privacy->any('privacy_ropa', '/privacy/ropa', 'ropa');

        $privacy->register($event->getSubject());

        // Admin
        $admin = new \AtomFramework\Routing\RouteLoader('privacyAdmin');

        $admin->any('privacy_admin', '/admin/privacy', 'index');
        $admin->any('privacy_admin_config', '/admin/privacy/config', 'config');

        // Visual Redaction Editor
        $admin->any('privacy_visual_redaction', '/admin/privacy/redaction/:id', 'visualRedactionEditor');

        // Visual Redaction AJAX endpoints
        $admin->any('privacy_get_visual_redactions', '/privacyAdmin/getVisualRedactions', 'getVisualRedactions');
        $admin->any('privacy_save_visual_redaction', '/privacyAdmin/saveVisualRedaction', 'saveVisualRedaction');
        $admin->any('privacy_delete_visual_redaction', '/privacyAdmin/deleteVisualRedaction', 'deleteVisualRedaction');
        $admin->any('privacy_get_ner_entities', '/privacyAdmin/getNerEntitiesForPage', 'getNerEntitiesForPage');
        $admin->any('privacy_apply_visual_redactions', '/privacyAdmin/applyVisualRedactions', 'applyVisualRedactions');
        $admin->any('privacy_get_document_info', '/privacyAdmin/getDocumentInfo', 'getDocumentInfo');
        $admin->any('privacy_download_redacted', '/privacyAdmin/downloadRedactedFile', 'downloadRedactedFile');

        $admin->register($event->getSubject());
    }
}
