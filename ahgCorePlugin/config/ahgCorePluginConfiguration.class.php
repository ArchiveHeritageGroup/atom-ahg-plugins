<?php

/**
 * ahgCorePlugin Configuration
 *
 * Core utilities plugin for AHG extensions.
 * Provides shared services and contracts for all AHG plugins.
 */
class ahgCorePluginConfiguration extends sfPluginConfiguration
{
    /**
     * Shown by AtoM's stock plugin admin (sfPluginAdminPlugin). A plugin without
     * $summary is silently omitted from that list - see
     * plugins/sfPluginAdminPlugin/modules/sfPluginAdminPlugin/actions/pluginsAction.class.php:59.
     * Must not contain the word "theme", which the same line uses to filter themes out.
     */
    public static $summary = 'Core utilities and shared services for AHG plugins';

    public static $version = '1.0.0';

    /**
     * Plugin initialization
     */
    public function initialize()
    {
        // Register autoloader for AhgCore namespace
        $this->registerAutoloader();

        // Register global error notification handler
        \AhgCore\Services\ErrorNotificationService::register();

        // Register queue handler for async error alert emails
        if (class_exists('AtomFramework\Services\QueueJobRegistry', false)) {
            \AtomFramework\Services\QueueJobRegistry::register(
                'error:send-alert',
                'AhgCore\Services\ErrorAlertQueueHandler'
            );
        }

        // Culture guard (HTTP 500 fix): reset any request culture that is not an
        // enabled UI language (app_i18n_languages) back to the default culture.
        // Without this, term/actor/IO browse sorts on i18n.<culture>.title.alphasort,
        // which has no mapping in OpenSearch for non-enabled cultures present only in
        // (test/legacy) i18n data — crawlers follow the translation-link switcher to
        // those cultures and every page 500s. Falling back to the default renders the
        // page normally instead of erroring.
        $this->dispatcher->connect('controller.change_action', ['ahgCorePluginConfiguration', 'enforceEnabledCulture']);

        // Missing-representation guard. Connected after enforceEnabledCulture so
        // that guard's HTML fallback for authority modules is applied first.
        $this->dispatcher->connect('controller.change_action', ['ahgCorePluginConfiguration', 'refuseUnavailableFormat']);
    }

    /**
     * Return 404 when the requested format has no template in the target module.
     *
     * Symfony resolves the template path to an empty string when a module ships
     * no template for the requested format, and sfPHPView::renderFile() then runs
     * require('') and fatals. Because there is an ob_start() immediately above
     * that require, the buffer is discarded and the caller gets either a blank
     * HTTP 200 or a bare 500 rather than an error page - and the log fills with
     * "Failed opening required '/'".
     *
     * Reached by malformed export URLs only, never by the UI. The supported
     * syntax is the semicolon form (/<slug>;ead?sf_format=xml), which routes to
     * sfEadPlugin and works correctly; the query-string form (?template=ead)
     * never populates the route's template parameter, so the module stays on the
     * record's display standard, which has no XML template. A 404 is the honest
     * answer: that representation does not exist.
     *
     * Fails open - if the template directories cannot be resolved, the request
     * proceeds untouched.
     *
     * @param sfEvent $event
     */
    public static function refuseUnavailableFormat($event)
    {
        $module = $event['module'] ?? null;
        if (empty($module)) {
            return;
        }

        try {
            $context = sfContext::getInstance();
            $request = $context ? $context->getRequest() : null;
            if (!$request) {
                return;
            }

            $format = $request->getParameter('sf_format');
            if (empty($format) || 'html' === $format) {
                return;
            }

            if (self::moduleHasFormatTemplate($module, $format)) {
                return;
            }

            // Reset to HTML before raising: the 404 page is itself rendered
            // through the view layer, so leaving the format as (say) xml makes
            // the error template lookup fail exactly the same way and the caller
            // gets a blank 200 instead of a 404.
            $request->setRequestFormat('html');
            $request->setParameter('sf_format', 'html');
        } catch (\Throwable $e) {
            return; // never let the guard break the request
        }

        // Deliberately outside the try: this must propagate, not be swallowed.
        throw new sfError404Exception(sprintf(
            'No "%s" representation exists for module "%s".',
            $format,
            $module
        ));
    }

    /**
     * Whether a module ships at least one template for the given format.
     *
     * Returns true when it cannot be determined, so an unexpected condition
     * lets the request through rather than 404ing it.
     */
    private static function moduleHasFormatTemplate($module, $format)
    {
        // Formats are used to build a glob; keep them to a safe character set.
        if (!preg_match('/^[a-z0-9]{1,16}$/i', (string) $format)) {
            return true;
        }

        try {
            $dirs = sfContext::getInstance()->getConfiguration()->getTemplateDirs($module);
        } catch (\Throwable $e) {
            return true;
        }

        foreach ((array) $dirs as $dir) {
            if (is_dir($dir) && glob(rtrim($dir, '/').'/*.'.$format.'.php')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runs before every action (controller.change_action). Two HTTP-500 guards,
     * both fail open (any error here must never break the request):
     *   1. Reset a non-enabled request culture to the default (else term/actor/IO
     *      browse sorts on an unmapped OpenSearch culture field and 500s).
     *   2. Force HTML for the actor show action when an unsupported format is
     *      requested (the base actor module has no non-HTML template, so
     *      ?sf_format=xml from crawlers throws sfRenderException -> 500).
     */
    public static function enforceEnabledCulture($event)
    {
        try {
            $context = sfContext::getInstance();
            if (!$context || !$context->getUser()) {
                return;
            }
            $request = $context->getRequest();

            // --- Guard 2: authority-record show with an unsupported (non-HTML) format ---
            // Actor/authority pages render via the ISAAR/ISDIAH/ISDF plugin modules,
            // which have no non-HTML template — so ?sf_format=xml (appended by crawlers)
            // throws sfRenderException -> HTTP 500. Force HTML for them. IO/EAD xml
            // export (informationobject module) is deliberately not in this list.
            $authorityModules = ['sfIsaarPlugin', 'sfIsdiahPlugin', 'sfIsdfPlugin'];
            if ($request && in_array($event['module'] ?? null, $authorityModules, true) && 'index' === ($event['action'] ?? null)) {
                $fmt = $request->getParameter('sf_format');
                if (!empty($fmt) && 'html' !== $fmt) {
                    $request->setRequestFormat('html');
                    $request->setParameter('sf_format', 'html');
                }
            }

            // --- Guard 1: non-enabled request culture ---
            $enabled = self::enabledCultures();
            if (empty($enabled)) {
                return; // no allow-list resolvable -> leave culture untouched
            }

            $user = $context->getUser();

            // Prefer an explicit ?sf_culture (may not be applied to the user yet),
            // otherwise the user's current culture.
            $requested = $request ? $request->getParameter('sf_culture') : null;
            $culture = $requested ?: $user->getCulture();

            if (!$culture || in_array($culture, $enabled, true)) {
                return; // enabled (or unset) -> nothing to do
            }

            $default = sfConfig::get('default_culture');
            if (!$default || !in_array($default, $enabled, true)) {
                $default = in_array('en', $enabled, true) ? 'en' : reset($enabled);
            }

            $user->setCulture($default);
            if ($request) {
                $request->setParameter('sf_culture', $default);
            }
        } catch (\Throwable $e) {
            // never let the guard break the request
        }
    }

    /**
     * Enabled UI languages (the allow-list), read from the same source AtoM uses
     * to build app_i18n_languages: setting rows with scope 'i18n_languages'.
     * sfConfig('app_i18n_languages') is not reliably populated at
     * controller.change_action time, so query directly. Cached per request.
     */
    private static function enabledCultures(): array
    {
        static $cultures = null;
        if (null !== $cultures) {
            return $cultures;
        }
        $cultures = [];
        try {
            // Prefer the runtime config when available...
            $cfg = sfConfig::get('app_i18n_languages');
            if (is_array($cfg) && !empty($cfg)) {
                $cultures = array_values($cfg);
                return $cultures;
            }
            // ...otherwise read the enabled-languages settings directly.
            $rows = QubitPdo::fetchAll("SELECT name FROM setting WHERE scope = 'i18n_languages'");
            foreach ($rows as $row) {
                if (!empty($row->name)) {
                    $cultures[] = $row->name;
                }
            }
        } catch (\Throwable $e) {
            $cultures = [];
        }
        return $cultures;
    }

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            // Handle AhgCore namespace
            if (strpos($class, 'AhgCore\\') === 0) {
                $relativePath = str_replace('AhgCore\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }

            // Handle ahgCorePlugin namespace (used by dependent plugins)
            if (strpos($class, 'ahgCorePlugin\\') === 0) {
                $relativePath = str_replace('ahgCorePlugin\\', '', $class);
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

    /**
     * Get plugin root path
     */
    public static function getPluginPath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Get lib path
     */
    public static function getLibPath(): string
    {
        return dirname(__DIR__) . '/lib';
    }

    /**
     * Get web assets path
     */
    public static function getWebPath(): string
    {
        return dirname(__DIR__) . '/web';
    }
}
