<?php

class ahgAPIPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Enhanced REST API v2 with webhooks';
    public static $version = '1.2.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'apiv2';
        $enabledModules[] = 'api';
        $enabledModules[] = 'identifierApi';
        sfConfig::set('sf_enabled_modules', $enabledModules);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();

        // ===================
        // API v2 ROUTES (apiv2 module)
        // ===================
        $apiv2 = new \AtomFramework\Routing\RouteLoader('apiv2');

        // Index
        $apiv2->get('apiv2_index', '/api/v2', 'index');

        // Descriptions
        $apiv2->get('apiv2_descriptionsBrowse', '/api/v2/descriptions', 'descriptionsBrowse');
        $apiv2->post('apiv2_descriptionsCreate', '/api/v2/descriptions', 'descriptionsCreate');
        $apiv2->get('apiv2_descriptionsRead', '/api/v2/descriptions/:slug', 'descriptionsRead', ['slug' => '[a-z0-9_-]+']);
        $apiv2->any('apiv2_descriptionsUpdate', '/api/v2/descriptions/:slug', 'descriptionsUpdate', ['slug' => '[a-z0-9_-]+']);
        $apiv2->any('apiv2_descriptionsDelete', '/api/v2/descriptions/:slug', 'descriptionsDelete', ['slug' => '[a-z0-9_-]+']);

        // Authorities
        $apiv2->get('apiv2_authoritiesBrowse', '/api/v2/authorities', 'authoritiesBrowse');
        $apiv2->get('apiv2_authoritiesRead', '/api/v2/authorities/:slug', 'authoritiesRead', ['slug' => '[a-z0-9_-]+']);

        // Repositories
        $apiv2->get('apiv2_repositoriesBrowse', '/api/v2/repositories', 'repositoriesBrowse');

        // Taxonomies
        $apiv2->get('apiv2_taxonomiesBrowse', '/api/v2/taxonomies', 'taxonomiesBrowse');
        $apiv2->get('apiv2_taxonomyTerms', '/api/v2/taxonomies/:id/terms', 'taxonomyTerms', ['id' => '\d+']);

        // Search
        $apiv2->any('apiv2_search', '/api/v2/search', 'search');

        // Batch
        $apiv2->post('apiv2_batch', '/api/v2/batch', 'batch');

        // Condition Assessment (Mobile)
        $apiv2->get('apiv2_conditionsBrowse', '/api/v2/conditions', 'conditionsBrowse');
        $apiv2->post('apiv2_conditionsCreate', '/api/v2/conditions', 'conditionsCreate');
        $apiv2->get('apiv2_conditionsRead', '/api/v2/conditions/:id', 'conditionsRead', ['id' => '\d+']);
        $apiv2->any('apiv2_conditionsUpdate', '/api/v2/conditions/:id', 'conditionsUpdate', ['id' => '\d+']);
        $apiv2->any('apiv2_conditionsDelete', '/api/v2/conditions/:id', 'conditionsDelete', ['id' => '\d+']);
        $apiv2->get('apiv2_descriptionConditions', '/api/v2/descriptions/:slug/conditions', 'descriptionConditions', ['slug' => '[a-z0-9_-]+']);

        // Condition Photos (Mobile Upload)
        $apiv2->get('apiv2_conditionPhotos', '/api/v2/conditions/:id/photos', 'conditionPhotos', ['id' => '\d+']);
        $apiv2->post('apiv2_conditionPhotoUpload', '/api/v2/conditions/:id/photos', 'conditionPhotoUpload', ['id' => '\d+']);
        $apiv2->any('apiv2_conditionPhotoDelete', '/api/v2/conditions/:id/photos/:photoId', 'conditionPhotoDelete', ['id' => '\d+', 'photoId' => '\d+']);

        // Heritage Assets (International Standards)
        $apiv2->get('apiv2_assetsBrowse', '/api/v2/assets', 'assetsBrowse');
        $apiv2->post('apiv2_assetsCreate', '/api/v2/assets', 'assetsCreate');
        $apiv2->get('apiv2_assetsRead', '/api/v2/assets/:id', 'assetsRead', ['id' => '\d+']);
        $apiv2->any('apiv2_assetsUpdate', '/api/v2/assets/:id', 'assetsUpdate', ['id' => '\d+']);
        $apiv2->get('apiv2_descriptionAsset', '/api/v2/descriptions/:slug/asset', 'descriptionAsset', ['slug' => '[a-z0-9_-]+']);

        // Valuations
        $apiv2->get('apiv2_valuationsBrowse', '/api/v2/valuations', 'valuationsBrowse');
        $apiv2->post('apiv2_valuationsCreate', '/api/v2/valuations', 'valuationsCreate');
        $apiv2->get('apiv2_assetValuations', '/api/v2/assets/:id/valuations', 'assetValuations', ['id' => '\d+']);

        // Privacy/Compliance (International)
        $apiv2->get('apiv2_dsarsBrowse', '/api/v2/privacy/dsars', 'dsarsBrowse');
        $apiv2->post('apiv2_dsarsCreate', '/api/v2/privacy/dsars', 'dsarsCreate');
        $apiv2->get('apiv2_dsarsRead', '/api/v2/privacy/dsars/:id', 'dsarsRead', ['id' => '\d+']);
        $apiv2->any('apiv2_dsarsUpdate', '/api/v2/privacy/dsars/:id', 'dsarsUpdate', ['id' => '\d+']);
        $apiv2->get('apiv2_breachesBrowse', '/api/v2/privacy/breaches', 'breachesBrowse');
        $apiv2->post('apiv2_breachesCreate', '/api/v2/privacy/breaches', 'breachesCreate');

        // File Upload (Generic - for mobile)
        $apiv2->post('apiv2_fileUpload', '/api/v2/upload', 'fileUpload');
        $apiv2->post('apiv2_descriptionUpload', '/api/v2/descriptions/:slug/upload', 'descriptionUpload', ['slug' => '[a-z0-9_-]+']);

        // Mobile Sync
        $apiv2->get('apiv2_syncChanges', '/api/v2/sync/changes', 'syncChanges');
        $apiv2->post('apiv2_syncBatch', '/api/v2/sync/batch', 'syncBatch');

        // API Keys management
        $apiv2->get('apiv2_keysBrowse', '/api/v2/keys', 'keysBrowse');
        $apiv2->post('apiv2_keysCreate', '/api/v2/keys', 'keysCreate');
        $apiv2->any('apiv2_keysDelete', '/api/v2/keys/:id', 'keysDelete', ['id' => '\d+']);

        // Webhooks
        $apiv2->get('apiv2_webhooksBrowse', '/api/v2/webhooks', 'webhooksBrowse');
        $apiv2->post('apiv2_webhooksCreate', '/api/v2/webhooks', 'webhooksCreate');
        $apiv2->get('apiv2_webhooksRead', '/api/v2/webhooks/:id', 'webhooksRead', ['id' => '\d+']);
        $apiv2->any('apiv2_webhooksUpdate', '/api/v2/webhooks/:id', 'webhooksUpdate', ['id' => '\d+']);
        $apiv2->any('apiv2_webhooksDelete', '/api/v2/webhooks/:id', 'webhooksDelete', ['id' => '\d+']);
        $apiv2->get('apiv2_webhookDeliveries', '/api/v2/webhooks/:id/deliveries', 'webhookDeliveries', ['id' => '\d+']);
        $apiv2->post('apiv2_webhookRegenerateSecret', '/api/v2/webhooks/:id/regenerate-secret', 'webhookRegenerateSecret', ['id' => '\d+']);

        $apiv2->register($routing);

        // ===================
        // LEGACY API ROUTES (api module)
        // ===================
        $api = new \AtomFramework\Routing\RouteLoader('api');
        $api->any('api_search_io', '/api/search/io', 'searchInformationObjects');
        $api->any('api_autocomplete_glam', '/api/autocomplete/glam', 'autocompleteGlam');
        $api->any('api_plugin_protection', '/api/plugin-protection', 'pluginProtection');
        $api->register($routing);
    }
}
