<?php

class ahgActorManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance actor browse, autocomplete, and management';
    public static $version = '1.0.0';

    /** Guard so the visibility gate runs once per request (fires on forwards too). */
    protected $visibilityGateChecked = false;

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        // Authority-record visibility gate: 404 a draft/embargoed actor's detail
        // page (and its EAC-CPF export, which shares this module dispatch) for
        // anonymous users. Staff always see it.
        $this->dispatcher->connect('controller.change_action', [$this, 'enforceActorVisibility']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'actorManage';
        $enabledModules[] = 'sfIsaarPlugin';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    /**
     * Suppress the detail view of a hidden authority record for anonymous users.
     *
     * Covers the actor detail page (sfIsaarPlugin/actor index) and the EAC-CPF
     * export (sfEacPlugin index extends ActorIndexAction). Fail-open on any error
     * so a misconfiguration can never hide the whole catalogue.
     */
    public function enforceActorVisibility(sfEvent $event)
    {
        if ($this->visibilityGateChecked) {
            return; // controller.change_action also fires on internal forwards
        }
        $this->visibilityGateChecked = true;

        $params = $event->getParameters();
        $module = $params['module'] ?? '';
        $action = $params['action'] ?? '';

        // Only gate actor detail / EAC views.
        if (!in_array($module, ['actor', 'sfIsaarPlugin', 'sfEacPlugin'], true) || 'index' !== $action) {
            return;
        }

        try {
            $context = sfContext::getInstance();

            // Staff see everything; suppression is public-only.
            if ($context->getUser()->isAuthenticated()) {
                return;
            }

            $slug = $context->getRequest()->getParameter('slug');
            if (empty($slug)) {
                return;
            }

            $row = \Illuminate\Database\Capsule\Manager::table('slug')
                ->where('slug', $slug)
                ->first();
            $actorId = $row ? (int) $row->object_id : 0;
            if ($actorId <= 0) {
                return;
            }

            if (\AhgActorManage\Services\ActorVisibilityService::isHiddenFromPublic($actorId)) {
                throw new sfError404Exception();
            }
        } catch (sfError404Exception $e) {
            throw $e; // intended 404 control-flow
        } catch (\Throwable $e) {
            error_log('actor.visibility.gate.error: ' . $e->getMessage());
        }
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgActorManage\\') === 0) {
                $relativePath = str_replace('AhgActorManage\\', '', $class);
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
        $routing = $event->getSubject();

        // sfIsaarPlugin routes (catch-all slug routes registered first = checked last)
        $isaar = new \AtomFramework\Routing\RouteLoader('sfIsaarPlugin');
        $isaar->any('actor_view_override', '/actor/:slug', 'index', ['slug' => '[a-zA-Z0-9_.-]+']);
        $isaar->any('actor_delete_override', '/actor/:slug/delete', 'delete', ['slug' => '[a-zA-Z0-9_.-]+']);
        $isaar->any('actor_edit_override', '/actor/:slug/edit', 'edit', ['slug' => '[a-zA-Z0-9_.-]+']);
        $isaar->any('actor_add_override', '/actor/add', 'edit');
        $isaar->register($routing);

        // actorManage routes (specific routes registered last = checked first)
        $manage = new \AtomFramework\Routing\RouteLoader('actorManage');
        $manage->any('actor_browse_override', '/actor/browse', 'browse');
        $manage->any('actor_autocomplete_override', '/actor/autocomplete', 'autocomplete');
        $manage->register($routing);
    }
}
