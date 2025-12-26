<?php
/**
 * DisplayViewFilter - Hooks into information object views for auto-detection
 */

class DisplayViewFilter extends sfFilter
{
    public function execute($filterChain)
    {
        // Execute the rest of the filter chain first
        $filterChain->execute();

        // Only process on information object views
        $context = $this->getContext();
        $request = $context->getRequest();
        $response = $context->getResponse();
        
        $module = $context->getModuleName();
        $action = $context->getActionName();

        // Hook into information object index (view) action
        if (in_array($module, ['sfIsadPlugin', 'sfRadPlugin', 'sfDcPlugin', 'sfModsPlugin', 'informationobject'])) {
            if ($action === 'index' || $action === 'view') {
                $this->processInformationObject($context);
            }
        }
    }

    protected function processInformationObject($context)
    {
        $resource = $context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;
        
        if (!$resource || (!$resource || !isset($resource->id))) {
            return;
        }

        try {
            require_once sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/lib/Services/DisplayTypeDetector.php';
            
            $objectId = (int) $resource->id;
            
            // Auto-detect type (this also saves it)
            $type = DisplayTypeDetector::detect($objectId);
            $profile = DisplayTypeDetector::getProfile($objectId);
            
            // Store in request attributes for templates to use
            sfContext::getInstance()->getRequest()->setAttribute('display_type', $type);
            sfContext::getInstance()->getRequest()->setAttribute('display_profile', $profile);
            
        } catch (Exception $e) {
            // Log but don't break the page
            error_log('arDisplayPlugin: Auto-detection failed: ' . $e->getMessage());
        }
    }
}
