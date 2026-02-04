<?php

class ahgGraphQLPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'GraphQL API';
    public static $version = '1.0.0';

    protected $routing;

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'graphql';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $this->routing = $event->getSubject();

        // GraphQL endpoint (POST for queries/mutations, GET for playground)
        $this->addRoute('POST', '/api/graphql', ['module' => 'graphql', 'action' => 'index']);
        $this->addRoute('GET', '/api/graphql', ['module' => 'graphql', 'action' => 'index']);

        // GraphQL Playground (development only)
        $this->addRoute('GET', '/api/graphql/playground', ['module' => 'graphql', 'action' => 'playground']);
    }

    protected function addRoute($method, $pattern, array $options = [])
    {
        $defaults = $requirements = [];

        if ('*' != $method) {
            $requirements['sf_method'] = explode(',', $method);
        }

        if (isset($options['module'])) {
            $defaults['module'] = $options['module'];
        }

        if (isset($options['action'])) {
            $defaults['action'] = $options['action'];
            $name = 'graphql_' . $options['action'];
        } else {
            $name = 'graphql_' . str_replace(['/', ':'], '_', $pattern);
        }

        if (isset($options['params'])) {
            foreach ($options['params'] as $field => $regex) {
                $requirements[$field] = $regex;
            }
        }

        $this->routing->prependRoute($name, new sfRequestRoute($pattern, $defaults, $requirements));
    }
}
