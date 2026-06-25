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
