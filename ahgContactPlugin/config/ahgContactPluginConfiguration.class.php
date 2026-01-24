<?php

/**
 * ahgContactPlugin configuration.
 */
class ahgContactPluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        // Register Contact extension classes
        $this->dispatcher->connect('context.load_factories', [$this, 'loadContact']);
    }

    public function loadContact(sfEvent $event)
    {
        // Load contact services
        $libPath = sfConfig::get('sf_plugins_dir') . '/ahgContactPlugin/lib';

        if (file_exists($libPath . '/Extensions/Contact/Services/ContactService.php')) {
            require_once $libPath . '/Extensions/Contact/Services/ContactService.php';
        }

        if (file_exists($libPath . '/Extensions/Contact/Repositories/ContactInformationRepository.php')) {
            require_once $libPath . '/Extensions/Contact/Repositories/ContactInformationRepository.php';
        }
    }
}
