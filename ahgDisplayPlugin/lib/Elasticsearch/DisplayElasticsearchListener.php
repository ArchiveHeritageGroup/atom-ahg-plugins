<?php
/**
 * Elasticsearch Indexing Listener for arDisplayPlugin
 * 
 * Hooks into AtoM's indexing events to add display-specific fields
 */

class DisplayElasticsearchListener
{
    protected static $service;
    
    /**
     * Get service instance
     */
    protected static function getService(): DisplayElasticsearchService
    {
        if (!self::$service) {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Elasticsearch/DisplayElasticsearchService.php';
            self::$service = new DisplayElasticsearchService();
        }
        return self::$service;
    }
    
    /**
     * Handle information object indexing
     * Connect to: arElasticSearchPlugin's indexing event
     */
    public static function onInformationObjectIndex(sfEvent $event)
    {
        $informationObject = $event->getSubject();
        $body = $event->getParameters();
        
        if (!$informationObject || !isset($informationObject->id)) {
            return;
        }
        
        try {
            // Add display data to the indexing body
            $displayData = self::getService()->getIndexData($informationObject->id);
            
            // Merge display data with existing body
            foreach ($displayData as $key => $value) {
                $body[$key] = $value;
            }
            
            // Return modified body
            $event->setReturnValue($body);
        } catch (\Exception $e) {
            error_log('DisplayES Listener: Failed to add display data for object ' . $informationObject->id . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Handle bulk indexing completion
     * Use this to ensure mapping is updated
     */
    public static function onBulkIndexComplete(sfEvent $event)
    {
        try {
            $service = self::getService();
            
            if (!$service->hasDisplayMapping()) {
                $service->updateMapping();
            }
        } catch (\Exception $e) {
            error_log('DisplayES Listener: Failed to check/update mapping: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle object type change
     * Re-index affected documents
     */
    public static function onObjectTypeChange(int $objectId, string $newType, bool $recursive = false)
    {
        $service = self::getService();
        
        // Get ES client
        $hosts = sfConfig::get('app_elasticsearch_hosts', ['127.0.0.1:9200']);
        $client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
        $index = sfConfig::get('app_elasticsearch_index', 'atom');
        
        // Update single document
        try {
            $client->update([
                'index' => $index,
                'id' => $objectId,
                'body' => [
                    'doc' => [
                        'display_object_type' => $newType,
                        'display_domain' => $newType,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            error_log('DisplayES: Failed to update object type in ES: ' . $e->getMessage());
        }
        
        // Handle recursive update
        if ($recursive) {
            $children = \Illuminate\Database\Capsule\Manager::table('information_object')
                ->where('parent_id', $objectId)
                ->pluck('id')
                ->toArray();
            
            foreach ($children as $childId) {
                self::onObjectTypeChange($childId, $newType, true);
            }
        }
    }
}
