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
    }
}
