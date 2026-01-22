<?php

class ahgPrivacyPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Privacy Compliance Management (POPIA, GDPR, PIPEDA, CCPA)';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'ahgPrivacy';
        $enabledModules[] = 'privacyAdmin';
        sfConfig::set('sf_enabled_modules', $enabledModules);

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
    <script src="/plugins/ahgThemeB5Plugin/js/fabric.min.js"></script>
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

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('privacy_dashboard', new sfRoute(
            '/privacy',
            ['module' => 'ahgPrivacy', 'action' => 'dashboard']
        ));

        // DSAR Management
        $routing->prependRoute('privacy_dsar_index', new sfRoute(
            '/privacy/dsar',
            ['module' => 'ahgPrivacy', 'action' => 'dsarIndex']
        ));
        $routing->prependRoute('privacy_dsar_new', new sfRoute(
            '/privacy/dsar/new',
            ['module' => 'ahgPrivacy', 'action' => 'dsarNew']
        ));
        $routing->prependRoute('privacy_dsar_view', new sfRoute(
            '/privacy/dsar/:id',
            ['module' => 'ahgPrivacy', 'action' => 'dsarView']
        ));
        $routing->prependRoute('privacy_dsar_update', new sfRoute(
            '/privacy/dsar/:id/update',
            ['module' => 'ahgPrivacy', 'action' => 'dsarUpdate']
        ));

        // Breach Register
        $routing->prependRoute('privacy_breach_index', new sfRoute(
            '/privacy/breaches',
            ['module' => 'ahgPrivacy', 'action' => 'breachIndex']
        ));
        $routing->prependRoute('privacy_breach_new', new sfRoute(
            '/privacy/breach/new',
            ['module' => 'ahgPrivacy', 'action' => 'breachNew']
        ));
        $routing->prependRoute('privacy_breach_view', new sfRoute(
            '/privacy/breach/:id',
            ['module' => 'ahgPrivacy', 'action' => 'breachView']
        ));

        // Consent Management
        $routing->prependRoute('privacy_consent_index', new sfRoute(
            '/privacy/consent',
            ['module' => 'ahgPrivacy', 'action' => 'consentIndex']
        ));

        // Processing Activities (ROPA)
        $routing->prependRoute('privacy_ropa', new sfRoute(
            '/privacy/ropa',
            ['module' => 'ahgPrivacy', 'action' => 'ropa']
        ));

        // Admin
        $routing->prependRoute('privacy_admin', new sfRoute(
            '/admin/privacy',
            ['module' => 'privacyAdmin', 'action' => 'index']
        ));
        $routing->prependRoute('privacy_admin_config', new sfRoute(
            '/admin/privacy/config',
            ['module' => 'privacyAdmin', 'action' => 'config']
        ));

        // Visual Redaction Editor
        $routing->prependRoute('privacy_visual_redaction', new sfRoute(
            '/admin/privacy/redaction/:id',
            ['module' => 'privacyAdmin', 'action' => 'visualRedactionEditor']
        ));

        // Visual Redaction AJAX endpoints
        $routing->prependRoute('privacy_get_visual_redactions', new sfRoute(
            '/privacyAdmin/getVisualRedactions',
            ['module' => 'privacyAdmin', 'action' => 'getVisualRedactions']
        ));
        $routing->prependRoute('privacy_save_visual_redaction', new sfRoute(
            '/privacyAdmin/saveVisualRedaction',
            ['module' => 'privacyAdmin', 'action' => 'saveVisualRedaction']
        ));
        $routing->prependRoute('privacy_delete_visual_redaction', new sfRoute(
            '/privacyAdmin/deleteVisualRedaction',
            ['module' => 'privacyAdmin', 'action' => 'deleteVisualRedaction']
        ));
        $routing->prependRoute('privacy_get_ner_entities', new sfRoute(
            '/privacyAdmin/getNerEntitiesForPage',
            ['module' => 'privacyAdmin', 'action' => 'getNerEntitiesForPage']
        ));
        $routing->prependRoute('privacy_apply_visual_redactions', new sfRoute(
            '/privacyAdmin/applyVisualRedactions',
            ['module' => 'privacyAdmin', 'action' => 'applyVisualRedactions']
        ));
        $routing->prependRoute('privacy_get_document_info', new sfRoute(
            '/privacyAdmin/getDocumentInfo',
            ['module' => 'privacyAdmin', 'action' => 'getDocumentInfo']
        ));
        $routing->prependRoute('privacy_download_redacted', new sfRoute(
            '/privacyAdmin/downloadRedactedFile',
            ['module' => 'privacyAdmin', 'action' => 'downloadRedactedFile']
        ));
    }
}
