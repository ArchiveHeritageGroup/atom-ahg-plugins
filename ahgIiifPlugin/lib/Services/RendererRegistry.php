<?php
declare(strict_types=1);

namespace AhgIiif\Services;

use AhgIiif\Services\Renderers\RendererInterface;

/**
 * Registry for IIIF viewer renderers.
 *
 * Auto-discovers renderer classes from the Renderers/ directory
 * and selects the appropriate one based on MIME type and priority.
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class RendererRegistry
{
    /** @var RendererInterface[] */
    private array $renderers = [];

    private bool $discovered = false;

    /**
     * Register a renderer.
     */
    public function register(RendererInterface $renderer): void
    {
        $this->renderers[] = $renderer;
        // Re-sort by priority descending (highest checked first)
        usort($this->renderers, fn($a, $b) => $b->getPriority() - $a->getPriority());
    }

    /**
     * Get the best renderer for a MIME type.
     *
     * @param string $mimeType The content MIME type
     * @param array $context Additional context (e.g. ['has3D' => true])
     * @return RendererInterface|null
     */
    public function getRenderer(string $mimeType, array $context = []): ?RendererInterface
    {
        $this->autoDiscover();

        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($mimeType, $context)) {
                return $renderer;
            }
        }

        return null;
    }

    /**
     * Get a renderer by name.
     */
    public function getRendererByName(string $name): ?RendererInterface
    {
        $this->autoDiscover();

        foreach ($this->renderers as $renderer) {
            if ($renderer->getName() === $name) {
                return $renderer;
            }
        }

        return null;
    }

    /**
     * Get all registered renderers sorted by priority.
     *
     * @return RendererInterface[]
     */
    public function all(): array
    {
        $this->autoDiscover();

        return $this->renderers;
    }

    /**
     * Auto-discover renderer classes from the Renderers/ directory.
     */
    private function autoDiscover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        $dir = __DIR__ . '/Renderers';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.php') as $file) {
            $basename = basename($file, '.php');

            // Skip the interface itself
            if ($basename === 'RendererInterface') {
                continue;
            }

            require_once $file;

            $className = 'AhgIiif\\Services\\Renderers\\' . $basename;

            if (class_exists($className, false)) {
                $instance = new $className();
                if ($instance instanceof RendererInterface) {
                    // Avoid duplicate registration
                    $exists = false;
                    foreach ($this->renderers as $existing) {
                        if ($existing->getName() === $instance->getName()) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $this->register($instance);
                    }
                }
            }
        }

        $this->discoverFromPlugins();
    }

    /**
     * Discover renderers contributed by OTHER plugins.
     *
     * Convention: any enabled plugin may ship <plugin>/lib/Renderers/*.php. This is
     * what lets a viewer (OpenSeadragon, Mirador, ...) be an independent plugin
     * rather than a file inside this one - install it and it registers itself,
     * disable it and the registry falls through to the next by priority.
     *
     * Class names are detected by diffing get_declared_classes() around the
     * require, so a contributing plugin is free to use its own namespace and this
     * registry never has to guess it.
     */
    private function discoverFromPlugins(): void
    {
        if (!class_exists('\sfConfig', false)) {
            return;
        }

        $pluginsDir = \sfConfig::get('sf_plugins_dir');
        if (!$pluginsDir || !is_dir($pluginsDir)) {
            return;
        }

        foreach ((array) glob($pluginsDir . '/*/lib/Renderers/*.php') as $file) {
            $before = get_declared_classes();

            require_once $file;

            foreach (array_diff(get_declared_classes(), $before) as $className) {
                if (!in_array(RendererInterface::class, class_implements($className) ?: [], true)) {
                    continue;
                }

                $instance = new $className();

                foreach ($this->renderers as $existing) {
                    if ($existing->getName() === $instance->getName()) {
                        continue 2;
                    }
                }

                $this->register($instance);
            }
        }
    }
}
