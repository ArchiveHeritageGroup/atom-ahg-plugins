<?php

/**
 * ahgTranslationPlugin
 *
 * Standalone Symfony 1 plugin for AtoM that calls a local MT endpoint and writes translations
 * into information_object_i18n (via drafts).
 */
class ahgTranslationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'AHG Translation Plugin';
    public static $version = '1.0.0';
    public static $category = 'ai';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', array($this, 'routingLoadConfiguration'));
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('translation');

        $router->any('ahg_translation_health', '/translation/health', 'health');
        $router->any('ahg_translation_settings', '/translation/settings', 'settings');
        $router->any('ahg_translation_translate', '/translation/translate/:id', 'translate', ['id' => '\d+']);
        $router->any('ahg_translation_apply', '/translation/apply/:draftId', 'apply', ['draftId' => '\d+']);

        $router->register($event->getSubject());
    }
}
