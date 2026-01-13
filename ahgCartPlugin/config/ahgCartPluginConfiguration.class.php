<?php

/**
 * ahgCartPlugin configuration
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Shopping cart for reproduction requests';
    public static $version = '1.0.0';

    public function routingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();
        $routing->prependRoute('ahg_cart_browse', new sfRoute(
            '/cart',
            ['module' => 'ahgCart', 'action' => 'browse']
        ));
        $routing->prependRoute('ahg_cart_add', new sfRoute(
            '/cart/add/:slug',
            ['module' => 'ahgCart', 'action' => 'add']
        ));
        $routing->prependRoute('ahg_cart_remove', new sfRoute(
            '/cart/remove/:id',
            ['module' => 'ahgCart', 'action' => 'remove']
        ));
        $routing->prependRoute('ahg_cart_clear', new sfRoute(
            '/cart/clear',
            ['module' => 'ahgCart', 'action' => 'clear']
        ));
        $routing->prependRoute('ahg_cart_checkout', new sfRoute(
            '/cart/checkout',
            ['module' => 'ahgCart', 'action' => 'checkout']
        ));
        $routing->prependRoute('ahg_cart_confirmation', new sfRoute(
            '/cart/confirmation/:id',
            ['module' => 'ahgCart', 'action' => 'confirmation']
        ));
    }

    public function contextLoadFactories(sfEvent $event)
    {
        // Plugin initialization
    }

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'ahgCart';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
