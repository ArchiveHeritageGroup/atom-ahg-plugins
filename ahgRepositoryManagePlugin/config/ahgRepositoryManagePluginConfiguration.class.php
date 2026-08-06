<?php

class ahgRepositoryManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance archival institution browse and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        // Stop unresolvable term values reaching base AtoM's repository form.
        // See dropUnresolvableTermValues() for why.
        $this->dispatcher->connect('request.filter_parameters', [$this, 'dropUnresolvableTermValues']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'repositoryManage';
        $enabledModules[] = 'sfIsdiahPlugin';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgRepositoryManage\\') === 0) {
                $relativePath = str_replace('AhgRepositoryManage\\', '', $class);
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

        // sfIsdiahPlugin module routes (QubitResourceRoute with requirements - registered directly)
        // Catch-all slug routes (checked last after prepending)
        $routing->prependRoute('repository_view_override', new \QubitResourceRoute(
            '/repository/:slug',
            ['module' => 'sfIsdiahPlugin', 'action' => 'index'],
            ['slug' => '[a-zA-Z0-9_.-]+']
        ));
        $routing->prependRoute('repository_delete_override', new \QubitResourceRoute(
            '/repository/:slug/delete',
            ['module' => 'sfIsdiahPlugin', 'action' => 'delete'],
            ['slug' => '[a-zA-Z0-9_.-]+']
        ));
        $routing->prependRoute('repository_edit_override', new \QubitResourceRoute(
            '/repository/:slug/edit',
            ['module' => 'sfIsdiahPlugin', 'action' => 'edit'],
            ['slug' => '[a-zA-Z0-9_.-]+']
        ));

        // Institution autocomplete.
        //
        // Prepended AFTER the catch-all above, which puts it AHEAD of it: the
        // '/repository/:slug' route matches "autocomplete" as a slug, finds no
        // repository by that name, and 404s rather than declining, so the
        // autocomplete action was unreachable even though it exists in both base
        // AtoM and ahgCorePlugin. Nothing in base routing.yml defines this route,
        // so there was nothing to fall through to.
        //
        // The visible symptom is the "Add institution" dialog on the group
        // permissions screen (aclGroup/editInformationObjectAcl) offering no
        // suggestions at all - its widget points at this URL. Broken on every
        // instance, PSIS included, not only here.
        $routing->prependRoute('repository_autocomplete_override', new \sfRoute(
            '/repository/autocomplete',
            ['module' => 'repository', 'action' => 'autocomplete']
        ));

        // sfIsdiahPlugin add route (no requirements, sfRoute is fine)
        $sfIsdiah = new \AtomFramework\Routing\RouteLoader('sfIsdiahPlugin');
        $sfIsdiah->any('repository_add_override', '/repository/add', 'edit');
        $sfIsdiah->register($routing);

        // repositoryManage module routes
        $repoManage = new \AtomFramework\Routing\RouteLoader('repositoryManage');
        $repoManage->any('repository_browse_override', '/repository/browse', 'browse');
        $repoManage->register($routing);
    }

    /**
     * Remove term values on the repository form that do not resolve to a record.
     *
     * apps/qubit/modules/repository/actions/editAction.class.php dereferences the
     * parsed route without checking it, in four places (lines 235, 260, 285, 313):
     *
     *   $params = $this->context->routing->parse(Qubit::pathInfo($item));
     *   $resource = $params['_sf_route']->resource;
     *   $value[$resource->id] = ...
     *
     * When a submitted value does not correspond to an existing term, resource is
     * null and saving an institution dies with HTTP 500, losing the whole form.
     * Reproduced on archaeology 2026-08-06 against /repository/add.
     *
     * That file is in apps/ and is base AtoM, which this project does not modify
     * under any circumstances. So the offending values are removed before base
     * sees them: multi-value fields keep their resolvable entries and drop the
     * rest, single-value fields are unset, and the institution saves with
     * everything else intact.
     *
     * This is the same defect as the accession donor field, guarded the same way
     * in ahgAccessionManagePlugin. It is a base AtoM bug reachable by any user,
     * not something specific to this deployment.
     */
    public function dropUnresolvableTermValues(sfEvent $event, $parameters)
    {
        if (!is_array($parameters) || 'repository' !== ($parameters['module'] ?? null)) {
            return $parameters;
        }

        $multi = ['type', 'geographicSubregion', 'thematicArea'];
        $single = ['descStatus', 'descDetail'];

        foreach ($multi as $field) {
            if (empty($parameters[$field]) || !is_array($parameters[$field])) {
                continue;
            }

            $kept = [];
            foreach ($parameters[$field] as $value) {
                if (is_string($value) && $this->slugExists($this->slugFromValue($value))) {
                    $kept[] = $value;
                } else {
                    $this->logDrop($field, $value);
                }
            }

            $parameters[$field] = $kept;
        }

        foreach ($single as $field) {
            if (empty($parameters[$field]) || !is_string($parameters[$field])) {
                continue;
            }

            if (!$this->slugExists($this->slugFromValue($parameters[$field]))) {
                $this->logDrop($field, $parameters[$field]);
                unset($parameters[$field]);
            }
        }

        return $parameters;
    }

    protected function logDrop(string $field, $value): void
    {
        try {
            $this->dispatcher->notify(new sfEvent(
                $this,
                'application.log',
                [sprintf('ahgRepositoryManage: dropped unresolvable "%s" value "%s" before base editAction could dereference it', $field, is_string($value) ? $value : gettype($value))]
            ));
        } catch (Exception $e) {
            // Logging must never be the thing that breaks the save.
        }
    }

    /**
     * The trailing path segment of a resource URL, which is the slug.
     */
    protected function slugFromValue(string $value): string
    {
        $path = parse_url(trim($value), PHP_URL_PATH);

        if (!is_string($path) || '' === $path) {
            $path = trim($value);
        }

        $segments = array_values(array_filter(explode('/', $path), static function ($s) {
            return '' !== $s && 'index.php' !== $s;
        }));

        return $segments === [] ? '' : (string) end($segments);
    }

    protected function slugExists(string $slug): bool
    {
        if ('' === $slug) {
            return false;
        }

        try {
            $connection = Propel::getConnection();
            $statement = $connection->prepare('SELECT 1 FROM slug WHERE slug = ? LIMIT 1');
            $statement->execute([$slug]);

            return false !== $statement->fetchColumn();
        } catch (Exception $e) {
            // If the check cannot run, leave the value alone rather than
            // discarding something that may well be valid.
            return true;
        }
    }

}
