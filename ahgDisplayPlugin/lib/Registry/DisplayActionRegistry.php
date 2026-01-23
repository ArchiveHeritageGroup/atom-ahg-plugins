<?php

declare(strict_types=1);

namespace AhgDisplay\Registry;

/**
 * Display Action Registry
 *
 * Central registry for display actions, panels, and badges that plugins can register
 * via their extension.json files. This enables plugins to extend the display views
 * without modifying ahgDisplayPlugin directly.
 *
 * @package ahgDisplayPlugin
 */
class DisplayActionRegistry
{
    private static array $actions = [];
    private static array $panels = [];
    private static array $badges = [];
    private static bool $initialized = false;

    /**
     * Initialize the registry by scanning enabled plugins for extension.json
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;
        self::loadFromPlugins();
    }

    /**
     * Load display configurations from all enabled plugins' extension.json files
     */
    private static function loadFromPlugins(): void
    {
        $pluginsDir = \sfConfig::get('sf_plugins_dir');
        if (!$pluginsDir || !is_dir($pluginsDir)) {
            return;
        }

        $plugins = scandir($pluginsDir);
        foreach ($plugins as $plugin) {
            if ($plugin === '.' || $plugin === '..') {
                continue;
            }

            $extensionFile = $pluginsDir . '/' . $plugin . '/extension.json';
            if (!file_exists($extensionFile)) {
                continue;
            }

            $config = json_decode(file_get_contents($extensionFile), true);
            if (!is_array($config)) {
                continue;
            }

            // Load display_actions
            if (!empty($config['display_actions']) && is_array($config['display_actions'])) {
                foreach ($config['display_actions'] as $action) {
                    self::registerAction($plugin, $action);
                }
            }

            // Load display_panels
            if (!empty($config['display_panels']) && is_array($config['display_panels'])) {
                foreach ($config['display_panels'] as $panel) {
                    self::registerPanel($plugin, $panel);
                }
            }

            // Load display_badges
            if (!empty($config['display_badges']) && is_array($config['display_badges'])) {
                foreach ($config['display_badges'] as $badge) {
                    self::registerBadge($plugin, $badge);
                }
            }
        }
    }

    /**
     * Register a display action
     *
     * @param string $plugin Plugin name
     * @param array $config Action configuration
     */
    public static function registerAction(string $plugin, array $config): void
    {
        $id = $config['id'] ?? uniqid('action_');
        self::$actions[$id] = array_merge($config, [
            'plugin' => $plugin,
            'id' => $id,
        ]);
    }

    /**
     * Register a display panel
     *
     * @param string $plugin Plugin name
     * @param array $config Panel configuration
     */
    public static function registerPanel(string $plugin, array $config): void
    {
        $id = $config['id'] ?? uniqid('panel_');
        self::$panels[$id] = array_merge($config, [
            'plugin' => $plugin,
            'id' => $id,
        ]);
    }

    /**
     * Register a display badge
     *
     * @param string $plugin Plugin name
     * @param array $config Badge configuration
     */
    public static function registerBadge(string $plugin, array $config): void
    {
        $id = $config['id'] ?? uniqid('badge_');
        self::$badges[$id] = array_merge($config, [
            'plugin' => $plugin,
            'id' => $id,
        ]);
    }

    /**
     * Get actions for a specific context
     *
     * @param string $context Context name (e.g., 'informationobject', 'actor')
     * @param object|null $resource Optional resource to check conditions against
     * @return array Filtered actions
     */
    public static function getActionsForContext(string $context, $resource = null): array
    {
        self::init();

        $filtered = [];
        foreach (self::$actions as $id => $action) {
            // Check context match
            $contexts = $action['contexts'] ?? [];
            if (!empty($contexts) && !in_array($context, $contexts)) {
                continue;
            }

            // Check condition if provided
            if (!self::checkCondition($action, $resource)) {
                continue;
            }

            // Check permission if provided
            if (!self::checkPermission($action, $resource)) {
                continue;
            }

            $filtered[$id] = $action;
        }

        // Sort by weight
        uasort($filtered, function ($a, $b) {
            return ($a['weight'] ?? 50) <=> ($b['weight'] ?? 50);
        });

        return $filtered;
    }

    /**
     * Get panels for a specific context and position
     *
     * @param string $context Context name
     * @param string|null $position Optional position filter (e.g., 'sidebar', 'main')
     * @param object|null $resource Optional resource to check conditions against
     * @return array Filtered panels
     */
    public static function getPanelsForContext(string $context, ?string $position = null, $resource = null): array
    {
        self::init();

        $filtered = [];
        foreach (self::$panels as $id => $panel) {
            // Check context match
            $contexts = $panel['contexts'] ?? [];
            if (!empty($contexts) && !in_array($context, $contexts)) {
                continue;
            }

            // Check position match
            if ($position !== null && ($panel['position'] ?? 'main') !== $position) {
                continue;
            }

            // Check condition if provided
            if (!self::checkCondition($panel, $resource)) {
                continue;
            }

            $filtered[$id] = $panel;
        }

        // Sort by weight
        uasort($filtered, function ($a, $b) {
            return ($a['weight'] ?? 50) <=> ($b['weight'] ?? 50);
        });

        return $filtered;
    }

    /**
     * Get badges for a specific context
     *
     * @param string $context Context name
     * @param object|null $resource Optional resource to check conditions against
     * @return array Filtered badges
     */
    public static function getBadgesForContext(string $context, $resource = null): array
    {
        self::init();

        $filtered = [];
        foreach (self::$badges as $id => $badge) {
            // Check context match
            $contexts = $badge['contexts'] ?? [];
            if (!empty($contexts) && !in_array($context, $contexts)) {
                continue;
            }

            // Check badge-specific check_method
            if (!empty($badge['check_method']) && $resource !== null) {
                if (!self::callCheckMethod($badge['check_method'], $resource)) {
                    continue;
                }
            }

            $filtered[$id] = $badge;
        }

        // Sort by weight
        uasort($filtered, function ($a, $b) {
            return ($a['weight'] ?? 50) <=> ($b['weight'] ?? 50);
        });

        return $filtered;
    }

    /**
     * Get all registered actions
     */
    public static function getAllActions(): array
    {
        self::init();
        return self::$actions;
    }

    /**
     * Get all registered panels
     */
    public static function getAllPanels(): array
    {
        self::init();
        return self::$panels;
    }

    /**
     * Get all registered badges
     */
    public static function getAllBadges(): array
    {
        self::init();
        return self::$badges;
    }

    /**
     * Check a condition callback
     */
    private static function checkCondition(array $config, $resource): bool
    {
        if (empty($config['condition'])) {
            return true;
        }

        return self::callCheckMethod($config['condition'], $resource);
    }

    /**
     * Check permission for a resource
     */
    private static function checkPermission(array $config, $resource): bool
    {
        if (empty($config['permission'])) {
            return true;
        }

        $permission = $config['permission'];

        // Use QubitAcl if available
        if ($resource !== null && class_exists('QubitAcl')) {
            try {
                return \QubitAcl::check($resource, $permission);
            } catch (\Exception $e) {
                return true; // Default to allowing if ACL check fails
            }
        }

        return true;
    }

    /**
     * Call a static check method (e.g., "ClassName::methodName")
     */
    private static function callCheckMethod(string $method, $resource): bool
    {
        if (strpos($method, '::') === false) {
            return true;
        }

        [$class, $methodName] = explode('::', $method, 2);

        if (!class_exists($class) || !method_exists($class, $methodName)) {
            return true;
        }

        try {
            $resourceId = is_object($resource) && isset($resource->id) ? $resource->id : null;
            return (bool) call_user_func([$class, $methodName], $resourceId);
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Render an action template
     *
     * @param array $action Action configuration
     * @param object|null $resource Resource to pass to the template
     * @return string Rendered HTML
     */
    public static function renderAction(array $action, $resource = null): string
    {
        if (empty($action['template'])) {
            return '';
        }

        $templatePath = self::resolveTemplatePath($action['template']);
        if (!$templatePath || !file_exists($templatePath)) {
            return '';
        }

        ob_start();
        try {
            include $templatePath;
        } catch (\Exception $e) {
            error_log("DisplayActionRegistry: Error rendering action template: " . $e->getMessage());
        }
        return ob_get_clean();
    }

    /**
     * Render a panel template
     *
     * @param array $panel Panel configuration
     * @param object|null $resource Resource to pass to the template
     * @return string Rendered HTML
     */
    public static function renderPanel(array $panel, $resource = null): string
    {
        if (empty($panel['template'])) {
            return '';
        }

        $templatePath = self::resolveTemplatePath($panel['template']);
        if (!$templatePath || !file_exists($templatePath)) {
            return '';
        }

        ob_start();
        try {
            include $templatePath;
        } catch (\Exception $e) {
            error_log("DisplayActionRegistry: Error rendering panel template: " . $e->getMessage());
        }
        return ob_get_clean();
    }

    /**
     * Render a badge template
     *
     * @param array $badge Badge configuration
     * @param object|null $resource Resource to pass to the template
     * @return string Rendered HTML
     */
    public static function renderBadge(array $badge, $resource = null): string
    {
        if (empty($badge['template'])) {
            return '';
        }

        $templatePath = self::resolveTemplatePath($badge['template']);
        if (!$templatePath || !file_exists($templatePath)) {
            return '';
        }

        ob_start();
        try {
            include $templatePath;
        } catch (\Exception $e) {
            error_log("DisplayActionRegistry: Error rendering badge template: " . $e->getMessage());
        }
        return ob_get_clean();
    }

    /**
     * Resolve a template path like "pluginName/templates/path.php" to full path
     */
    private static function resolveTemplatePath(string $template): ?string
    {
        $pluginsDir = \sfConfig::get('sf_plugins_dir');

        // If template starts with plugin name
        if (strpos($template, '/') !== false) {
            return $pluginsDir . '/' . $template;
        }

        return null;
    }

    /**
     * Clear all registered items (useful for testing)
     */
    public static function clear(): void
    {
        self::$actions = [];
        self::$panels = [];
        self::$badges = [];
        self::$initialized = false;
    }
}
