<?php

namespace AhgCore\Contracts;

/**
 * DisplayActionProviderInterface
 *
 * Interface for plugins that provide actions, panels, or badges to display views.
 * Used by ahgDisplayPlugin to collect extensions from other plugins.
 *
 * Plugins implement this interface and register via extension.json to provide:
 * - Action buttons (e.g., "Edit Rights", "View Audit Log")
 * - Information panels (e.g., rights summary, condition report)
 * - Badges/indicators (e.g., "Restricted", "Embargo Active")
 *
 * Usage in extension.json:
 *   {
 *     "display_provider": {
 *       "class": "MyPlugin\\DisplayProvider",
 *       "provides": ["actions", "panels", "badges"]
 *     }
 *   }
 */
interface DisplayActionProviderInterface
{
    /**
     * Get unique provider identifier
     *
     * @return string Provider ID (e.g., 'ahgRightsPlugin')
     */
    public function getProviderId(): string;

    /**
     * Get actions for an entity
     *
     * @param object $entity The entity being displayed
     * @param array $context Display context ('view' => 'index', 'user' => ..., etc.)
     * @return array Array of action definitions:
     *   [
     *     [
     *       'id' => 'edit_rights',
     *       'label' => 'Edit Rights',
     *       'url' => '/rights/edit/123',
     *       'icon' => 'fa-lock',
     *       'class' => 'btn btn-primary',
     *       'permission' => 'canEditRights',
     *       'order' => 100,
     *     ],
     *     ...
     *   ]
     */
    public function getActions(object $entity, array $context = []): array;

    /**
     * Get information panels for an entity
     *
     * @param object $entity The entity being displayed
     * @param array $context Display context
     * @return array Array of panel definitions:
     *   [
     *     [
     *       'id' => 'rights_panel',
     *       'title' => 'Rights Information',
     *       'content' => '<div>...</div>',
     *       'template' => 'ahgRightsPlugin/partials/rights_panel',
     *       'position' => 'sidebar', // 'main', 'sidebar', 'footer'
     *       'order' => 50,
     *       'collapsible' => true,
     *       'collapsed' => false,
     *     ],
     *     ...
     *   ]
     */
    public function getPanels(object $entity, array $context = []): array;

    /**
     * Get badges/indicators for an entity
     *
     * @param object $entity The entity being displayed
     * @param array $context Display context
     * @return array Array of badge definitions:
     *   [
     *     [
     *       'id' => 'restricted_badge',
     *       'label' => 'Restricted',
     *       'class' => 'badge bg-danger',
     *       'icon' => 'fa-lock',
     *       'tooltip' => 'This record has access restrictions',
     *       'order' => 10,
     *     ],
     *     ...
     *   ]
     */
    public function getBadges(object $entity, array $context = []): array;

    /**
     * Check if provider is enabled for an entity type
     *
     * @param string $entityType Entity class name
     * @return bool
     */
    public function supportsEntity(string $entityType): bool;

    /**
     * Get provider configuration
     *
     * @return array Configuration options
     */
    public function getConfig(): array;
}
