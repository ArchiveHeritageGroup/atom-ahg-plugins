<?php

class ahgGraphQLPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'GraphQL API';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'graphql';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('graphql');

        // GraphQL endpoint (POST for queries/mutations, GET for playground)
        $router->post('graphql_index_post', '/api/graphql', 'index');
        $router->get('graphql_index_get', '/api/graphql', 'index');

        // GraphQL Playground (development only)
        $router->get('graphql_playground', '/api/graphql/playground', 'playground');

        $router->register($event->getSubject());
    }
}
