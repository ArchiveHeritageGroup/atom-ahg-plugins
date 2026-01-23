<?php

namespace AhgCore\Contracts;

/**
 * PluginServiceInterface
 *
 * Base interface for plugin services. Provides a common contract
 * for plugin initialization and lifecycle management.
 */
interface PluginServiceInterface
{
    /**
     * Get the plugin name
     *
     * @return string Plugin identifier (e.g., 'ahgRightsPlugin')
     */
    public function getPluginName(): string;

    /**
     * Check if the plugin/service is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Initialize the service
     *
     * Called when the service is first instantiated.
     * Use for setting up dependencies, connections, etc.
     *
     * @return void
     */
    public function initialize(): void;

    /**
     * Get service configuration
     *
     * @return array Configuration array
     */
    public function getConfig(): array;
}
