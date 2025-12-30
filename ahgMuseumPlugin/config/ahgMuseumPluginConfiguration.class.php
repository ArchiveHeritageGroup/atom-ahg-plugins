<?php
class ahgMuseumPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Museum Cataloging Plugin with CCO/CDWA support';
    public static $version = '1.0.0';
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }
    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // === REPORTS ROUTE ===
        $routing->prependRoute('reports_index', new sfRoute(
            '/reports',
            ['module' => 'reports', 'action' => 'index']
        ));

        // === PRIVACY ROUTES ===
        $routing->prependRoute('privacy_template_download', new sfRoute(
            '/admin/privacy/templates/download',
            ['module' => 'spectrum', 'action' => 'privacyTemplateDownload']
        ));
        $routing->prependRoute('privacy_template_delete', new sfRoute(
            '/admin/privacy/templates/delete',
            ['module' => 'spectrum', 'action' => 'privacyTemplateDelete']
        ));
        $routing->prependRoute('privacy_templates', new sfRoute(
            '/admin/privacy/templates',
            ['module' => 'spectrum', 'action' => 'privacyTemplates']
        ));
        $routing->prependRoute('privacy_admin', new sfRoute(
            '/admin/privacy/manage',
            ['module' => 'spectrum', 'action' => 'privacyAdmin']
        ));
        $routing->prependRoute('privacy_compliance', new sfRoute(
            '/admin/privacy',
            ['module' => 'spectrum', 'action' => 'privacyCompliance']
        ));
        $routing->prependRoute('privacy_ropa', new sfRoute(
            '/admin/privacy/ropa',
            ['module' => 'spectrum', 'action' => 'privacyRopa']
        ));
        $routing->prependRoute('privacy_dsar', new sfRoute(
            '/admin/privacy/dsar',
            ['module' => 'spectrum', 'action' => 'privacyDsar']
        ));
        $routing->prependRoute('privacy_dsar_update', new sfRoute(
            '/admin/privacy/dsar/update',
            ['module' => 'spectrum', 'action' => 'privacyDsarUpdate']
        ));
        $routing->prependRoute('privacy_breaches', new sfRoute(
            '/admin/privacy/breaches',
            ['module' => 'spectrum', 'action' => 'privacyBreaches']
        ));
        $routing->prependRoute('privacy_breach_update', new sfRoute(
            '/admin/privacy/breaches/update',
            ['module' => 'spectrum', 'action' => 'privacyBreachUpdate']
        ));

        // === SPECTRUM EXPORT ===
        $routing->prependRoute('spectrum_export', new sfRoute(
            '/spectrum/export',
            ['module' => 'spectrum', 'action' => 'spectrumExport']
        ));

        // === GRAP-SPECTRUM LINKING ===
        $routing->prependRoute('grap_spectrum_link', new sfRoute(
            '/grap/spectrum/link',
            ['module' => 'spectrum', 'action' => 'grapSpectrumLink']
        ));

        // === SECURITY ROUTES ===
        $routing->prependRoute('security_compliance', new sfRoute(
            '/admin/security/compliance',
            ['module' => 'spectrum', 'action' => 'securityCompliance']
        ));
        
        // === CONDITION ROUTES ===
        $routing->prependRoute('condition_admin', new sfRoute(
            '/admin/condition',
            ['module' => 'spectrum', 'action' => 'conditionAdmin']
        ));
        $routing->prependRoute('condition_risk', new sfRoute(
            '/admin/condition/risk',
            ['module' => 'spectrum', 'action' => 'conditionRisk']
        ));

        // === SPECTRUM ROUTES ===
        $routing->prependRoute('spectrum_annotation_save', new sfRoute(
            '/spectrum/annotation/save',
            ['module' => 'spectrum', 'action' => 'annotationSave']
        ));
        $routing->prependRoute('spectrum_annotation_get', new sfRoute(
            '/spectrum/annotation/get',
            ['module' => 'spectrum', 'action' => 'annotationGet']
        ));
        $routing->prependRoute('spectrum_photo_delete', new sfRoute(
            '/spectrum/photo/delete',
            ['module' => 'spectrum', 'action' => 'photoDelete']
        ));
        $routing->prependRoute('spectrum_photo_rotate', new sfRoute(
            '/spectrum/photo/rotate',
            ['module' => 'spectrum', 'action' => 'photoRotate']
        ));
        $routing->prependRoute('spectrum_photo_set_primary', new sfRoute(
            '/spectrum/photo/setPrimary',
            ['module' => 'spectrum', 'action' => 'photoSetPrimary']
        ));
        $routing->prependRoute('spectrum_index', new sfRoute(
            '/:slug/spectrum',
            ['module' => 'spectrum', 'action' => 'index']
        ));
        $routing->prependRoute('spectrum_workflow', new sfRoute(
            '/:slug/spectrum/workflow',
            ['module' => 'spectrum', 'action' => 'workflow']
        ));
        $routing->prependRoute('spectrum_label', new sfRoute(
            '/:slug/spectrum/label',
            ['module' => 'spectrum', 'action' => 'label']
        ));
        $routing->prependRoute('spectrum_condition_photos', new sfRoute(
            '/:slug/spectrum/conditionPhotos',
            ['module' => 'spectrum', 'action' => 'conditionPhotos']
        ));
        $routing->prependRoute('spectrum_dashboard', new sfRoute(
            '/spectrum/dashboard',
            ['module' => 'spectrum', 'action' => 'dashboard']
        ));
        $routing->prependRoute('cco_provenance', new sfRoute(
            '/:slug/cco/provenance',
            ['module' => 'cco', 'action' => 'provenance']
        ));
        $routing->prependRoute('museum_provenance', new sfRoute(
            '/:slug/cco/provenance',
            ['module' => 'ahgMuseumPlugin', 'action' => 'provenance']
        ));
        $routing->prependRoute('museum_view', new sfRoute(
            '/museum/:slug',
            ['module' => 'ahgMuseumPlugin', 'action' => 'index'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
		// === MUSEUM CRUD ROUTES ===
        $routing->prependRoute('museum_browse', new sfRoute(
            '/museum/browse',
            ['module' => 'ahgMuseumPlugin', 'action' => 'browse']
        ));
        $routing->prependRoute('museum_add', new sfRoute(
            '/museum/add',
            ['module' => 'ahgMuseumPlugin', 'action' => 'add']
        ));
        $routing->prependRoute('museum_edit', new sfRoute(
            '/museum/edit/:slug',
            ['module' => 'ahgMuseumPlugin', 'action' => 'edit'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
    }
}
