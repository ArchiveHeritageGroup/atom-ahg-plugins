<?php

class ahgAPIPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Enhanced REST API v2';
    public static $version = '1.1.0';

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

        // Condition Assessment (Mobile)
        $this->addRoute('GET', '/api/v2/conditions', ['module' => 'apiv2', 'action' => 'conditionsBrowse']);
        $this->addRoute('POST', '/api/v2/conditions', ['module' => 'apiv2', 'action' => 'conditionsCreate']);
        $this->addRoute('GET', '/api/v2/conditions/:id', ['module' => 'apiv2', 'action' => 'conditionsRead', 'params' => ['id' => '\d+']]);
        $this->addRoute('PUT,PATCH', '/api/v2/conditions/:id', ['module' => 'apiv2', 'action' => 'conditionsUpdate', 'params' => ['id' => '\d+']]);
        $this->addRoute('DELETE', '/api/v2/conditions/:id', ['module' => 'apiv2', 'action' => 'conditionsDelete', 'params' => ['id' => '\d+']]);
        $this->addRoute('GET', '/api/v2/descriptions/:slug/conditions', ['module' => 'apiv2', 'action' => 'descriptionConditions', 'params' => ['slug' => '[a-z0-9_-]+']]);

        // Condition Photos (Mobile Upload)
        $this->addRoute('GET', '/api/v2/conditions/:id/photos', ['module' => 'apiv2', 'action' => 'conditionPhotos', 'params' => ['id' => '\d+']]);
        $this->addRoute('POST', '/api/v2/conditions/:id/photos', ['module' => 'apiv2', 'action' => 'conditionPhotoUpload', 'params' => ['id' => '\d+']]);
        $this->addRoute('DELETE', '/api/v2/conditions/:id/photos/:photoId', ['module' => 'apiv2', 'action' => 'conditionPhotoDelete', 'params' => ['id' => '\d+', 'photoId' => '\d+']]);

        // Heritage Assets (International Standards)
        $this->addRoute('GET', '/api/v2/assets', ['module' => 'apiv2', 'action' => 'assetsBrowse']);
        $this->addRoute('POST', '/api/v2/assets', ['module' => 'apiv2', 'action' => 'assetsCreate']);
        $this->addRoute('GET', '/api/v2/assets/:id', ['module' => 'apiv2', 'action' => 'assetsRead', 'params' => ['id' => '\d+']]);
        $this->addRoute('PUT,PATCH', '/api/v2/assets/:id', ['module' => 'apiv2', 'action' => 'assetsUpdate', 'params' => ['id' => '\d+']]);
        $this->addRoute('GET', '/api/v2/descriptions/:slug/asset', ['module' => 'apiv2', 'action' => 'descriptionAsset', 'params' => ['slug' => '[a-z0-9_-]+']]);

        // Valuations
        $this->addRoute('GET', '/api/v2/valuations', ['module' => 'apiv2', 'action' => 'valuationsBrowse']);
        $this->addRoute('POST', '/api/v2/valuations', ['module' => 'apiv2', 'action' => 'valuationsCreate']);
        $this->addRoute('GET', '/api/v2/assets/:id/valuations', ['module' => 'apiv2', 'action' => 'assetValuations', 'params' => ['id' => '\d+']]);

        // Privacy/Compliance (International)
        $this->addRoute('GET', '/api/v2/privacy/dsars', ['module' => 'apiv2', 'action' => 'dsarsBrowse']);
        $this->addRoute('POST', '/api/v2/privacy/dsars', ['module' => 'apiv2', 'action' => 'dsarsCreate']);
        $this->addRoute('GET', '/api/v2/privacy/dsars/:id', ['module' => 'apiv2', 'action' => 'dsarsRead', 'params' => ['id' => '\d+']]);
        $this->addRoute('PUT,PATCH', '/api/v2/privacy/dsars/:id', ['module' => 'apiv2', 'action' => 'dsarsUpdate', 'params' => ['id' => '\d+']]);
        $this->addRoute('GET', '/api/v2/privacy/breaches', ['module' => 'apiv2', 'action' => 'breachesBrowse']);
        $this->addRoute('POST', '/api/v2/privacy/breaches', ['module' => 'apiv2', 'action' => 'breachesCreate']);

        // File Upload (Generic - for mobile)
        $this->addRoute('POST', '/api/v2/upload', ['module' => 'apiv2', 'action' => 'fileUpload']);
        $this->addRoute('POST', '/api/v2/descriptions/:slug/upload', ['module' => 'apiv2', 'action' => 'descriptionUpload', 'params' => ['slug' => '[a-z0-9_-]+']]);

        // Mobile Sync
        $this->addRoute('GET', '/api/v2/sync/changes', ['module' => 'apiv2', 'action' => 'syncChanges']);
        $this->addRoute('POST', '/api/v2/sync/batch', ['module' => 'apiv2', 'action' => 'syncBatch']);

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
