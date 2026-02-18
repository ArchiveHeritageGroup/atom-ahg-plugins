<?php
declare(strict_types=1);

namespace AhgIiif\Services\Renderers;

/**
 * Interface for IIIF viewer renderers.
 *
 * Each renderer handles a specific content type (image, PDF, audio/video, 3D, etc.)
 * and produces the HTML needed to display that content.
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
interface RendererInterface
{
    /**
     * Check if this renderer supports the given MIME type.
     *
     * @param string $mimeType The MIME type to check
     * @param array $context Additional context (e.g. ['has3D' => true])
     * @return bool
     */
    public function supports(string $mimeType, array $context = []): bool;

    /**
     * Render the viewer HTML.
     *
     * @param array $config Viewer configuration including:
     *   - viewerId: unique viewer ID
     *   - mimeType: content MIME type
     *   - manifestUrl: IIIF manifest URL
     *   - digitalObject: the digital object data
     *   - pluginPath: plugin web path
     *   - baseUrl: site base URL
     *   - options: additional viewer options
     * @return string HTML output
     */
    public function render(array $config): string;

    /**
     * Get the renderer name.
     */
    public function getName(): string;

    /**
     * Get the renderer priority (higher = checked first).
     */
    public function getPriority(): int;
}
