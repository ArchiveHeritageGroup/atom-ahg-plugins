<?php

/**
 * ahgChatbotPlugin - "Ask the Archive", a public assistant over the catalogue.
 *
 * Self-contained: retrieval, the visibility filter, limits, the launcher and its
 * log all live in this plugin. It depends only on the framework (the AI gateway
 * client, SearchAccessFilterService, AhgSettingsService) and touches no base
 * AtoM file. The launcher is injected from here, so it works with or without
 * the AHG theme.
 */
class ahgChatbotPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Ask the Archive - public chatbot over the published catalogue';
    public static $version = '1.2.2';

    public function initialize()
    {
        if (class_exists('AhgNav')) {
            AhgNav::register('admin', 'chatbot', [
                'url' => '/index.php/chatbot/admin',
                'label' => 'Ask the Archive',
                'credentials' => ['administrator'],
                'weight' => 60,
            ]);
        }

        require_once __DIR__.'/../lib/Listeners/LauncherInjector.php';
        $this->dispatcher->connect('response.filter_content', ['\AhgChatbotPlugin\Listeners\LauncherInjector', 'filter']);

        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'chatbot';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
