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
        $routing = $event->getSubject();

        $routing->prependRoute('ahg_translation_health', new sfRoute(
            '/translation/health',
            array('module' => 'ahgTranslation', 'action' => 'health')
        ));

        $routing->prependRoute('ahg_translation_settings', new sfRoute(
            '/translation/settings',
            array('module' => 'ahgTranslation', 'action' => 'settings')
        ));

        $routing->prependRoute('ahg_translation_translate', new sfRoute(
            '/translation/translate/:id',
            array('module' => 'ahgTranslation', 'action' => 'translate'),
            array('id' => '\d+')
        ));

        $routing->prependRoute('ahg_translation_apply', new sfRoute(
            '/translation/apply/:draftId',
            array('module' => 'ahgTranslation', 'action' => 'apply'),
            array('draftId' => '\d+')
        ));
    }
}
