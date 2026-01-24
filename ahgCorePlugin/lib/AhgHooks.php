<?php

namespace AhgCore;

/**
 * AHG Hooks - Simple hook dispatcher for plugin extensibility.
 *
 * Allows plugins to register and trigger hooks without duplicating core modules.
 * Vendors can use this to extend functionality without action file conflicts.
 *
 * Usage:
 *   // Register a hook
 *   AhgHooks::register('record.view.panels', function($record) {
 *       return ['title' => 'My Panel', 'content' => '...'];
 *   });
 *
 *   // Trigger hooks
 *   $panels = AhgHooks::trigger('record.view.panels', $record);
 */
class AhgHooks
{
    private static array $hooks = [];
    private static array $priorities = [];

    /**
     * Register a hook callback.
     *
     * @param string   $hook     Hook name (e.g., 'record.view.panels')
     * @param callable $callback Callback function
     * @param int      $priority Priority (lower = earlier, default 10)
     */
    public static function register(string $hook, callable $callback, int $priority = 10): void
    {
        if (!isset(self::$hooks[$hook])) {
            self::$hooks[$hook] = [];
            self::$priorities[$hook] = [];
        }

        self::$hooks[$hook][] = $callback;
        self::$priorities[$hook][] = $priority;
    }

    /**
     * Trigger a hook and collect results.
     *
     * @param string $hook Hook name
     * @param mixed  ...$args Arguments to pass to callbacks
     * @return array Array of results from all callbacks
     */
    public static function trigger(string $hook, ...$args): array
    {
        if (!isset(self::$hooks[$hook])) {
            return [];
        }

        // Sort by priority
        $callbacks = self::$hooks[$hook];
        $priorities = self::$priorities[$hook];
        array_multisort($priorities, SORT_ASC, $callbacks);

        $results = [];
        foreach ($callbacks as $callback) {
            $result = $callback(...$args);
            if ($result !== null) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Trigger a filter hook (passes value through callbacks).
     *
     * @param string $hook  Hook name
     * @param mixed  $value Initial value to filter
     * @param mixed  ...$args Additional arguments
     * @return mixed Filtered value
     */
    public static function filter(string $hook, $value, ...$args)
    {
        if (!isset(self::$hooks[$hook])) {
            return $value;
        }

        $callbacks = self::$hooks[$hook];
        $priorities = self::$priorities[$hook];
        array_multisort($priorities, SORT_ASC, $callbacks);

        foreach ($callbacks as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    /**
     * Check if a hook has any registered callbacks.
     */
    public static function has(string $hook): bool
    {
        return isset(self::$hooks[$hook]) && count(self::$hooks[$hook]) > 0;
    }

    /**
     * Remove all callbacks for a hook.
     */
    public static function clear(string $hook): void
    {
        unset(self::$hooks[$hook], self::$priorities[$hook]);
    }

    /**
     * Get list of registered hook names.
     */
    public static function getRegisteredHooks(): array
    {
        return array_keys(self::$hooks);
    }
}
