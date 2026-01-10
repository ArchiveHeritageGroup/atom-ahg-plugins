<?php

class ahgAPIPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Enhanced REST API v2';
    public static $version = '1.0.0';

    protected $routing;

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'apiv2';
        sfConfig::set('sf_enabled_modules', $enabledModules);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $this->routing = $event->getSubject();

        // API v2 endpoints
        $this->addRoute('GET', '/api/v2', ['module' => 'apiv2', 'action' => 'index']);
        
        // Descriptions
        $this->addRoute('GET', '/api/v2/descriptions', ['module' => 'apiv2', 'action' => 'descriptionsBrowse']);
        $this->addRoute('POST', '/api/v2/descriptions', ['module' => 'apiv2', 'action' => 'descriptionsCreate']);
        $this->addRoute('GET', '/api/v2/descriptions/:slug', ['module' => 'apiv2', 'action' => 'descriptionsRead', 'params' => ['slug' => '[a-z0-9_-]+']]);
        $this->addRoute('PUT,PATCH', '/api/v2/descriptions/:slug', ['module' => 'apiv2', 'action' => 'descriptionsUpdate', 'params' => ['slug' => '[a-z0-9_-]+']]);
        $this->addRoute('DELETE', '/api/v2/descriptions/:slug', ['module' => 'apiv2', 'action' => 'descriptionsDelete', 'params' => ['slug' => '[a-z0-9_-]+']]);

        // Authorities
        $this->addRoute('GET', '/api/v2/authorities', ['module' => 'apiv2', 'action' => 'authoritiesBrowse']);
        $this->addRoute('GET', '/api/v2/authorities/:slug', ['module' => 'apiv2', 'action' => 'authoritiesRead', 'params' => ['slug' => '[a-z0-9_-]+']]);

        // Repositories
        $this->addRoute('GET', '/api/v2/repositories', ['module' => 'apiv2', 'action' => 'repositoriesBrowse']);

        // Taxonomies
        $this->addRoute('GET', '/api/v2/taxonomies', ['module' => 'apiv2', 'action' => 'taxonomiesBrowse']);
        $this->addRoute('GET', '/api/v2/taxonomies/:id/terms', ['module' => 'apiv2', 'action' => 'taxonomyTerms', 'params' => ['id' => '\d+']]);

        // Search
        $this->addRoute('GET,POST', '/api/v2/search', ['module' => 'apiv2', 'action' => 'search']);

        // Batch
        $this->addRoute('POST', '/api/v2/batch', ['module' => 'apiv2', 'action' => 'batch']);

        // API Keys management
        $this->addRoute('GET', '/api/v2/keys', ['module' => 'apiv2', 'action' => 'keysBrowse']);
        $this->addRoute('POST', '/api/v2/keys', ['module' => 'apiv2', 'action' => 'keysCreate']);
        $this->addRoute('DELETE', '/api/v2/keys/:id', ['module' => 'apiv2', 'action' => 'keysDelete', 'params' => ['id' => '\d+']]);
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
            $name = 'apiv2_' . $options['action'];
        } else {
            $name = 'apiv2_' . str_replace(['/', ':'], '_', $pattern);
        }

        if (isset($options['params'])) {
            foreach ($options['params'] as $field => $regex) {
                $requirements[$field] = $regex;
            }
        }

        // Use prependRoute - always works regardless of load order
        $this->routing->prependRoute($name, new sfRequestRoute($pattern, $defaults, $requirements));
    }
}
