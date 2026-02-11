<?php

class ahgInformationObjectManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'ISAD(G) information object CRUD management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ioManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgInformationObjectManage\\') === 0) {
                $relativePath = str_replace('AhgInformationObjectManage\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }

            return false;
        });
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('ioManage');

        // Catch-all slug routes (checked last after prepending)
        $router->any('io_delete_override', '/informationobject/:slug/delete', 'delete');
        $router->any('io_edit_override', '/informationobject/:slug/edit', 'edit');

        // Digital object routes (checked after treeview, before slug catch-alls)
        $router->any('io_do_upload', '/digitalobject/upload', 'doUpload');
        $router->any('io_do_edit', '/digitalobject/:id/edit', 'doEdit', ['id' => '\d+']);
        $router->any('io_do_delete', '/digitalobject/:id/delete', 'doDelete', ['id' => '\d+']);

        // Treeview API routes
        $router->any('io_treeview', '/informationobject/treeview', 'treeview');
        $router->any('io_treeview_full', '/informationobject/treeviewFull', 'treeviewFull');
        $router->any('io_treeview_sort', '/informationobject/treeviewSort', 'treeviewSort');

        // Specific routes (checked first after prepending)
        $router->any('io_actor_autocomplete', '/informationobject/actorAutocomplete', 'actorAutocomplete');
        $router->any('io_repository_autocomplete', '/informationobject/repositoryAutocomplete', 'repositoryAutocomplete');
        $router->any('io_term_autocomplete', '/informationobject/termAutocomplete', 'termAutocomplete');
        $router->any('io_generate_identifier', '/informationobject/generateIdentifierJson', 'generateIdentifier');
        $router->any('io_add_override', '/informationobject/add', 'edit');

        $router->register($event->getSubject());
    }
}
