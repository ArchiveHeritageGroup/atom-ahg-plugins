<?php

/**
 * ahgICIPPlugin - Indigenous Cultural and Intellectual Property Management
 *
 * Provides ICIP compliance features for Australian GLAM institutions:
 * - Community Registry (Aboriginal and Torres Strait Islander communities)
 * - Consent Management (consultation and consent tracking)
 * - Cultural Notices (sensitivity warnings and acknowledgements)
 * - Traditional Knowledge Labels (Local Contexts TK/BC labels)
 * - Consultation Log (community engagement tracking)
 * - Access Restrictions (ICIP-specific access controls)
 *
 * Legal Context:
 * - UN Declaration on the Rights of Indigenous Peoples (UNDRIP) Article 31
 * - Creative Australia's Protocols for First Nations Cultural and Intellectual Property
 * - AIATSIS Code of Ethics for Aboriginal and Torres Strait Islander Research
 *
 * @package    ahgICIPPlugin
 * @author     The Archive and Heritage Group
 * @copyright  2024 The Archive and Heritage Group (Pty) Ltd
 */
class ahgICIPPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Indigenous Cultural and Intellectual Property management for Australian GLAM institutions';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        // Register CSS
        $context = $event->getSubject();
        $context->getResponse()->addStylesheet('/plugins/ahgICIPPlugin/css/icip.css', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'icip';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }
}
