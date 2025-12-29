<?php

/**
 * IIIF Viewer Settings Actions
 */
class arIiifViewerSettingsActions extends sfActions
{
    /**
     * Check admin access
     */
    public function preExecute()
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->redirect('@homepage');
        }
    }

    /**
     * Display and save settings
     */
    public function executeIndex(sfWebRequest $request)
    {
        // Initialize database
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';
        
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }
        
        $db = \Illuminate\Database\Capsule\Manager::class;
        
        if ($request->isMethod('post')) {
            $settings = [
                // Homepage settings
                'homepage_collection_enabled' => $request->getParameter('homepage_collection_enabled', '0'),
                'homepage_collection_id' => $request->getParameter('homepage_collection_id', ''),
                'homepage_carousel_height' => $request->getParameter('homepage_carousel_height', '450px'),
                'homepage_carousel_autoplay' => $request->getParameter('homepage_carousel_autoplay', '0'),
                'homepage_carousel_interval' => $request->getParameter('homepage_carousel_interval', '5000'),
                'homepage_show_captions' => $request->getParameter('homepage_show_captions', '0'),
                'homepage_max_items' => $request->getParameter('homepage_max_items', '12'),
                // Viewer settings
                'viewer_type' => $request->getParameter('viewer_type', 'carousel'),
                'carousel_autoplay' => $request->getParameter('carousel_autoplay', '0'),
                'carousel_interval' => $request->getParameter('carousel_interval', '5000'),
                'carousel_show_thumbnails' => $request->getParameter('carousel_show_thumbnails', '0'),
                'carousel_show_controls' => $request->getParameter('carousel_show_controls', '0'),
                'viewer_height' => $request->getParameter('viewer_height', '500px'),
                'show_zoom_controls' => $request->getParameter('show_zoom_controls', '0'),
                'enable_fullscreen' => $request->getParameter('enable_fullscreen', '0'),
                'default_zoom' => $request->getParameter('default_zoom', '1'),
                'background_color' => $request->getParameter('background_color', '#000000'),
                'show_on_browse' => $request->getParameter('show_on_browse', '0'),
                'show_on_view' => $request->getParameter('show_on_view', '0'),
            ];
            
            foreach ($settings as $key => $value) {
                $exists = $db::table('iiif_viewer_settings')->where('setting_key', $key)->exists();
                if ($exists) {
                    $db::table('iiif_viewer_settings')->where('setting_key', $key)->update(['setting_value' => $value]);
                } else {
                    $db::table('iiif_viewer_settings')->insert(['setting_key' => $key, 'setting_value' => $value]);
                }
            }
            
            $this->getUser()->setFlash('notice', 'Settings saved successfully.');
            $this->redirect(['module' => 'arIiifViewerSettings', 'action' => 'index']);
        }
        
        // Load current settings
        $this->settings = $db::table('iiif_viewer_settings')
            ->pluck('setting_value', 'setting_key')
            ->all();
        
        // Load collections for dropdown
        $this->collections = $db::table('iiif_collection as c')
            ->leftJoin($db::raw('(SELECT collection_id, COUNT(*) as cnt FROM iiif_collection_item GROUP BY collection_id) as items'), 'c.id', '=', 'items.collection_id')
            ->select('c.id', 'c.name', 'c.slug', 'c.is_public', $db::raw('COALESCE(items.cnt, 0) as item_count'))
            ->orderBy('c.name')
            ->get();
        
        $this->response->setTitle('IIIF Viewer Settings');
    }
}
