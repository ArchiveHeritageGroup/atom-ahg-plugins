<?php

/**
 * Redirect to GLAM Browse (arDisplayPlugin)
 * 
 * This action redirects all informationobject/browse requests to the
 * GLAM browse interface provided by arDisplayPlugin.
 */
class InformationObjectBrowseAction extends sfAction
{
    public function execute($request)
    {
        // Build redirect URL with all query parameters
        $params = $request->getGetParameters();
        
        // Map common parameters
        $glamParams = [];
        
        // Pass through relevant filters
        if (!empty($params['repos'])) {
            $glamParams['repo'] = $params['repos'];
        }
        if (!empty($params['levels'])) {
            $glamParams['level'] = $params['levels'];
        }
        if (!empty($params['onlyMedia']) && $params['onlyMedia'] == '1') {
            $glamParams['hasDigital'] = '1';
        }
        if (!empty($params['view'])) {
            $glamParams['view'] = $params['view'];
        }
        if (!empty($params['sort'])) {
            $glamParams['sort'] = $params['sort'];
        }
        if (!empty($params['topLod']) && $params['topLod'] == '1') {
            $glamParams['topLevel'] = '1';
        }
        
        // Build URL
        $url = 'glam/browse';
        if (!empty($glamParams)) {
            $url .= '?' . http_build_query($glamParams);
        }
        
        $this->redirect($url);
    }
}
