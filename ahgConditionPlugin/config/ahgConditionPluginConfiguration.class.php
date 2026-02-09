<?php

class ahgConditionPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Condition report photo annotation plugin for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->dispatcher->connect('response.filter_content', [$this, 'addAssets']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }

    public function addAssets(sfEvent $event, $content)
    {
        $response = $event->getSubject();
        $css = '<link rel="stylesheet" href="/plugins/ahgConditionPlugin/web/css/condition-annotator.css">';
        $content = str_replace('</head>', $css . "\n</head>", $content);
        return $content;
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('condition');

        // Object autocomplete for IIIF collections
        $router->any('object_autocomplete', '/object/autocomplete', 'objectAutocomplete');

        // Slug-based condition check route - exclude reserved words
        $router->any('condition_check_by_slug', '/:slug/condition', 'conditionCheck', ['slug' => '(?!reports|admin|spectrum|user|search|browse|clipboard|settings|import|export|object|actor|repository|term|taxonomy|digitalobject|informationobject|jobs|uploads|images|css|js|plugins|vendor|api|ahgMuseumPlugin).+']);

        // Condition photo routes - require numeric ID
        $router->any('condition_check_view', '/condition/check/:id/view', 'view', ['id' => '\d+']);
        $router->any('condition_photos', '/condition/check/:id/photos', 'photos', ['id' => '\d+|new']);
        $router->any('condition_annotate', '/condition/photo/:id/annotate', 'annotate', ['id' => '\d+']);
        $router->any('condition_annotation_get', '/condition/annotation/get', 'getAnnotation');
        $router->any('condition_annotation_save', '/condition/annotation/save', 'saveAnnotation');
        $router->any('condition_photo_upload', '/condition/check/:id/upload', 'upload', ['id' => '\d+|new']);
        $router->any('condition_photo_delete', '/condition/photo/:id/delete', 'deletePhoto', ['id' => '\d+']);
        $router->any('condition_photo_view', '/condition/photo/:id/view', 'viewPhoto', ['id' => '\d+']);
        $router->any('condition_photo_update_meta', '/condition/photo/:id/update-meta', 'updatePhotoMeta', ['id' => '\d+']);
        $router->any('condition_report_export', '/condition/check/:id/export', 'exportReport', ['id' => '\d+']);
        $router->any('condition_list_photos', '/condition/check/:id/list', 'listPhotos', ['id' => '\d+|new']);

        // Template routes
        $router->any('condition_template_list', '/condition/templates', 'template', [], ['template_action' => 'list']);
        $router->any('condition_template_view', '/condition/template/:id/view', 'template', ['id' => '\d+'], ['template_action' => 'view']);
        $router->any('condition_template_form', '/condition/template/:id/form', 'template', ['id' => '\d+'], ['template_action' => 'form']);
        $router->any('condition_template_export', '/condition/template/:id/export', 'template', ['id' => '\d+'], ['template_action' => 'export']);

        $router->register($event->getSubject());
    }
}
