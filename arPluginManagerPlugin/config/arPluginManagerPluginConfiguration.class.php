<?php

class arPluginManagerPluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();
        $routing->prependRoute('pluginAdmin', new sfRoute(
            '/admin/plugins',
            ['module' => 'pluginAdmin', 'action' => 'index']
        ));
        $routing->prependRoute('pluginAdmin_toggle', new sfRoute(
            '/admin/plugins/toggle',
            ['module' => 'pluginAdmin', 'action' => 'toggle']
        ));
        $routing->prependRoute('pluginAdmin_audit', new sfRoute(
            '/admin/plugins/audit',
            ['module' => 'pluginAdmin', 'action' => 'auditLog']
        ));
    }
}
