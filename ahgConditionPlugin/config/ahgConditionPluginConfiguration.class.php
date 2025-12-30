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
        $css = '<link rel="stylesheet" href="/plugins/ahgConditionPlugin/css/condition-annotator.css">';
        $content = str_replace('</head>', $css . "\n</head>", $content);
        return $content;
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Object autocomplete for IIIF collections
        $routing->prependRoute('object_autocomplete', new sfRoute(
            '/object/autocomplete',
            ['module' => 'ahgCondition', 'action' => 'objectAutocomplete']
        ));

        // Slug-based condition check route - exclude reserved words
        $routing->prependRoute('condition_check_by_slug', new sfRoute(
            '/:slug/condition',
            ['module' => 'ahgCondition', 'action' => 'conditionCheck'],
            ['slug' => '(?!reports|admin|spectrum|user|search|browse|clipboard|settings|import|export|object|actor|repository|term|taxonomy|digitalobject|informationobject|jobs|uploads|images|css|js|plugins|vendor|api|ahgMuseumPlugin).+']
        ));

        // Condition photo routes - require numeric ID
        $routing->prependRoute('condition_check_view', new sfRoute(
            '/condition/check/:id/view',
            ['module' => 'ahgCondition', 'action' => 'view'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_photos', new sfRoute(
            '/condition/check/:id/photos',
            ['module' => 'ahgCondition', 'action' => 'photos'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_annotate', new sfRoute(
            '/condition/photo/:id/annotate',
            ['module' => 'ahgCondition', 'action' => 'annotate'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_annotation_get', new sfRoute(
            '/condition/annotation/get',
            ['module' => 'ahgCondition', 'action' => 'getAnnotation']
        ));

        $routing->prependRoute('condition_annotation_save', new sfRoute(
            '/condition/annotation/save',
            ['module' => 'ahgCondition', 'action' => 'saveAnnotation']
        ));

        $routing->prependRoute('condition_photo_upload', new sfRoute(
            '/condition/check/:id/upload',
            ['module' => 'ahgCondition', 'action' => 'upload'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_photo_delete', new sfRoute(
            '/condition/photo/:id/delete',
            ['module' => 'ahgCondition', 'action' => 'deletePhoto'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_photo_view', new sfRoute(
            '/condition/photo/:id/view',
            ['module' => 'ahgCondition', 'action' => 'viewPhoto'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_photo_update_meta', new sfRoute(
            '/condition/photo/:id/update-meta',
            ['module' => 'ahgCondition', 'action' => 'updatePhotoMeta'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_report_export', new sfRoute(
            '/condition/check/:id/export',
            ['module' => 'ahgCondition', 'action' => 'exportReport'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_list_photos', new sfRoute(
            '/condition/check/:id/list',
            ['module' => 'ahgCondition', 'action' => 'listPhotos'],
            ['id' => '\d+']
        ));

        // Template routes
        $routing->prependRoute('condition_template_list', new sfRoute(
            '/condition/templates',
            ['module' => 'ahgCondition', 'action' => 'template', 'template_action' => 'list']
        ));

        $routing->prependRoute('condition_template_view', new sfRoute(
            '/condition/template/:id/view',
            ['module' => 'ahgCondition', 'action' => 'template', 'template_action' => 'view'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_template_form', new sfRoute(
            '/condition/template/:id/form',
            ['module' => 'ahgCondition', 'action' => 'template', 'template_action' => 'form'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('condition_template_export', new sfRoute(
            '/condition/template/:id/export',
            ['module' => 'ahgCondition', 'action' => 'template', 'template_action' => 'export'],
            ['id' => '\d+']
        ));
    }
}
