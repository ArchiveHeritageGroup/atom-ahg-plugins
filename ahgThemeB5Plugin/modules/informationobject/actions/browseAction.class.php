<?php
/**
 * Browse Action - Conditionally redirect to GLAM Browse
 *
 * If ahgDisplayPlugin is enabled, redirects to GLAM browse.
 * Otherwise, uses standard AtoM browse.
 */
class InformationObjectBrowseAction extends sfAction
{
    public function execute($request)
    {
        // Check if ahgDisplayPlugin is enabled
        $plugins = sfConfig::get('app_plugins', []);
        
        // Ensure plugins is an array (may be serialized string)
        if (is_string($plugins)) {
            $plugins = @unserialize($plugins);
            if (!is_array($plugins)) {
                $plugins = [];
            }
        }
        
        if (!is_array($plugins) || !in_array('ahgDisplayPlugin', $plugins)) {
            // Use standard AtoM browse - forward to parent action
            $this->forward('informationobject', 'list');
            return sfView::NONE;
        }

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
        if (!empty($params['sq'])) {
            $glamParams['sq'] = $params['sq'];
        }
        if (!empty($params['sort'])) {
            $glamParams['sort'] = $params['sort'];
        }

        // Build redirect URL
        $url = 'glam/browse';
        if (!empty($glamParams)) {
            $url .= '?' . http_build_query($glamParams);
        }

        $this->redirect($url);
        return sfView::NONE;
    }
}
