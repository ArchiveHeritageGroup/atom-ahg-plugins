<?php

namespace AhgDisplay\Services;

use AhgCore\Core\AhgConfig;
use AhgCore\Core\AhgDb;
use AhgCore\Contracts\DisplayActionProviderInterface;

/**
 * DisplayRegistry - Extension-based Action/Panel/Badge Registration
 *
 * Discovers and loads display providers from other plugins via their
 * extension.json files. Provides a centralized way to collect and
 * render display extensions.
 *
 * Usage:
 *   use AhgDisplay\Services\DisplayRegistry;
 *
 *   $registry = DisplayRegistry::getInstance();
 *   $actions = $registry->getActions($entity, ['view' => 'index']);
 *   $panels = $registry->getPanels($entity);
 *   $badges = $registry->getBadges($entity);
 */
class DisplayRegistry
{
    private static ?self $instance = null;
    private array $providers = [];
    private bool $discovered = false;
    private array $cache = [];

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a display provider
     */
    public function registerProvider(DisplayActionProviderInterface $provider): void
    {
        $id = $provider->getProviderId();
        $this->providers[$id] = $provider;
    }

    /**
     * Get all registered providers
     */
    public function getProviders(): array
    {
        $this->discover();
        return $this->providers;
    }

    /**
     * Get a specific provider
     */
    public function getProvider(string $id): ?DisplayActionProviderInterface
    {
        $this->discover();
        return $this->providers[$id] ?? null;
    }

    /**
     * Get all actions for an entity from all providers
     */
    public function getActions(object $entity, array $context = []): array
    {
        $this->discover();

        $entityType = get_class($entity);
        $actions = [];

        foreach ($this->providers as $provider) {
            if (!$provider->supportsEntity($entityType)) {
                continue;
            }

            try {
                $providerActions = $provider->getActions($entity, $context);
                foreach ($providerActions as $action) {
                    $action['provider'] = $provider->getProviderId();
                    $actions[] = $action;
                }
            } catch (\Exception $e) {
                error_log("DisplayRegistry: Error getting actions from {$provider->getProviderId()}: {$e->getMessage()}");
            }
        }

        // Sort by order
        usort($actions, fn($a, $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));

        return $actions;
    }

    /**
     * Get all panels for an entity from all providers
     */
    public function getPanels(object $entity, array $context = []): array
    {
        $this->discover();

        $entityType = get_class($entity);
        $panels = [];

        foreach ($this->providers as $provider) {
            if (!$provider->supportsEntity($entityType)) {
                continue;
            }

            try {
                $providerPanels = $provider->getPanels($entity, $context);
                foreach ($providerPanels as $panel) {
                    $panel['provider'] = $provider->getProviderId();
                    $panels[] = $panel;
                }
            } catch (\Exception $e) {
                error_log("DisplayRegistry: Error getting panels from {$provider->getProviderId()}: {$e->getMessage()}");
            }
        }

        // Sort by order
        usort($panels, fn($a, $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));

        return $panels;
    }

    /**
     * Get panels by position
     */
    public function getPanelsByPosition(object $entity, string $position, array $context = []): array
    {
        $allPanels = $this->getPanels($entity, $context);
        return array_filter($allPanels, fn($p) => ($p['position'] ?? 'sidebar') === $position);
    }

    /**
     * Get all badges for an entity from all providers
     */
    public function getBadges(object $entity, array $context = []): array
    {
        $this->discover();

        $entityType = get_class($entity);
        $badges = [];

        foreach ($this->providers as $provider) {
            if (!$provider->supportsEntity($entityType)) {
                continue;
            }

            try {
                $providerBadges = $provider->getBadges($entity, $context);
                foreach ($providerBadges as $badge) {
                    $badge['provider'] = $provider->getProviderId();
                    $badges[] = $badge;
                }
            } catch (\Exception $e) {
                error_log("DisplayRegistry: Error getting badges from {$provider->getProviderId()}: {$e->getMessage()}");
            }
        }

        // Sort by order
        usort($badges, fn($a, $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));

        return $badges;
    }

    /**
     * Discover providers from plugin extension.json files
     */
    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        // Get all enabled plugins
        try {
            $plugins = AhgDb::table('atom_plugin')
                ->where('is_enabled', 1)
                ->pluck('name')
                ->toArray();
        } catch (\Exception $e) {
            $plugins = [];
        }

        // Add AHG plugins directory
        $ahgPluginsPath = AhgConfig::getAhgPluginsPath();
        $pluginsPath = AhgConfig::getPluginsPath();

        foreach ($plugins as $pluginName) {
            // Check AHG plugins first, then regular plugins
            $extensionPaths = [
                $ahgPluginsPath . '/' . $pluginName . '/extension.json',
                $pluginsPath . '/' . $pluginName . '/extension.json',
            ];

            foreach ($extensionPaths as $extensionPath) {
                if (!file_exists($extensionPath)) {
                    continue;
                }

                $this->loadProviderFromExtension($extensionPath, $pluginName);
                break; // Only load from first found
            }
        }

        // Also scan AHG plugins directory directly for any not in database
        if (is_dir($ahgPluginsPath)) {
            foreach (scandir($ahgPluginsPath) as $dir) {
                if ($dir === '.' || $dir === '..' || !is_dir($ahgPluginsPath . '/' . $dir)) {
                    continue;
                }

                if (isset($this->providers[$dir])) {
                    continue;
                }

                $extensionPath = $ahgPluginsPath . '/' . $dir . '/extension.json';
                if (file_exists($extensionPath)) {
                    $this->loadProviderFromExtension($extensionPath, $dir);
                }
            }
        }
    }

    /**
     * Load provider from extension.json
     */
    protected function loadProviderFromExtension(string $extensionPath, string $pluginName): void
    {
        try {
            $json = file_get_contents($extensionPath);
            $extension = json_decode($json, true);

            if (!$extension) {
                return;
            }

            // Check for display_provider configuration
            $displayProvider = $extension['display_provider'] ?? null;
            if (!$displayProvider) {
                return;
            }

            $className = $displayProvider['class'] ?? null;
            if (!$className) {
                return;
            }

            // Try to load the class
            if (!class_exists($className)) {
                // Try to autoload from plugin directory
                $pluginPath = dirname($extensionPath);
                $classPath = str_replace('\\', '/', $className);
                $possiblePaths = [
                    $pluginPath . '/lib/' . $classPath . '.php',
                    $pluginPath . '/lib/' . basename($classPath) . '.php',
                ];

                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        require_once $path;
                        break;
                    }
                }
            }

            if (!class_exists($className)) {
                return;
            }

            $provider = new $className();
            if ($provider instanceof DisplayActionProviderInterface) {
                $this->providers[$provider->getProviderId()] = $provider;
            }
        } catch (\Exception $e) {
            error_log("DisplayRegistry: Error loading provider from {$extensionPath}: {$e->getMessage()}");
        }
    }

    /**
     * Render actions as HTML
     */
    public function renderActions(object $entity, array $context = [], string $template = 'buttons'): string
    {
        $actions = $this->getActions($entity, $context);

        if (empty($actions)) {
            return '';
        }

        $html = '<div class="display-actions">';
        foreach ($actions as $action) {
            $class = $action['class'] ?? 'btn btn-secondary btn-sm';
            $icon = isset($action['icon']) ? '<i class="' . htmlspecialchars($action['icon']) . '"></i> ' : '';
            $label = htmlspecialchars($action['label'] ?? '');
            $url = htmlspecialchars($action['url'] ?? '#');

            $html .= sprintf(
                '<a href="%s" class="%s" title="%s">%s%s</a> ',
                $url, $class, $label, $icon, $label
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Render badges as HTML
     */
    public function renderBadges(object $entity, array $context = []): string
    {
        $badges = $this->getBadges($entity, $context);

        if (empty($badges)) {
            return '';
        }

        $html = '<span class="display-badges">';
        foreach ($badges as $badge) {
            $class = $badge['class'] ?? 'badge bg-secondary';
            $icon = isset($badge['icon']) ? '<i class="' . htmlspecialchars($badge['icon']) . '"></i> ' : '';
            $label = htmlspecialchars($badge['label'] ?? '');
            $tooltip = isset($badge['tooltip']) ? ' title="' . htmlspecialchars($badge['tooltip']) . '"' : '';

            $html .= sprintf(
                '<span class="%s"%s>%s%s</span> ',
                $class, $tooltip, $icon, $label
            );
        }
        $html .= '</span>';

        return $html;
    }

    /**
     * Clear discovery cache
     */
    public function reset(): void
    {
        $this->providers = [];
        $this->discovered = false;
        $this->cache = [];
    }

    /**
     * Get discovered provider info (for debugging)
     */
    public function getProviderInfo(): array
    {
        $this->discover();

        $info = [];
        foreach ($this->providers as $id => $provider) {
            $info[$id] = [
                'class' => get_class($provider),
                'config' => $provider->getConfig(),
            ];
        }

        return $info;
    }
}
