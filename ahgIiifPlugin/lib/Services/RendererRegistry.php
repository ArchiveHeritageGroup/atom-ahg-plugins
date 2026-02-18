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
    }
}
