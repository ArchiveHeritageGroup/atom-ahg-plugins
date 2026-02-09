<?php
use AtomExtensions\Services\AclService;
use Illuminate\Database\Capsule\Manager as DB;

class AhgSettingsPluginsAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        // Handle enable/disable actions
        if ($request->isMethod('post')) {
            $this->handlePluginAction($request);
        }

        // Load plugins from database
        $this->plugins = $this->loadPlugins();
        $this->categories = $this->getCategories();

        return [
            '_blade' => 'plugins',
            'plugins' => $this->plugins,
            'categories' => $this->categories,
        ];
    }

    protected function loadPlugins()
    {
        $plugins = [];
        try {
            $rows = DB::table('atom_plugin')
                ->orderBy('name')
                ->get();
            foreach ($rows as $row) {
                $plugin = (array) $row;
                // Normalize: all ahg* plugins are AHG Extensions
                if (strpos($plugin['name'], 'ahg') === 0 && !in_array($plugin['category'], ['core', 'theme'])) {
                    $plugin['category'] = 'ahg';
                }
                $plugins[] = $plugin;
            }
        } catch (Exception $e) {
            error_log("Plugin Manager: Error loading plugins: " . $e->getMessage());
        }
        return $plugins;
    }

    protected function getCategories()
    {
        return [
            'core' => ['label' => 'Core Plugins', 'icon' => 'fa-cube', 'class' => 'primary'],
            'admin' => ['label' => 'Admin & Settings', 'icon' => 'fa-cogs', 'class' => 'dark'],
            'theme' => ['label' => 'Themes', 'icon' => 'fa-palette', 'class' => 'info'],
            'ahg' => ['label' => 'AHG Extensions', 'icon' => 'fa-puzzle-piece', 'class' => 'success'],
            'integration' => ['label' => 'Integrations', 'icon' => 'fa-plug', 'class' => 'warning'],
            'other' => ['label' => 'Other', 'icon' => 'fa-ellipsis-h', 'class' => 'secondary'],
        ];
    }

    protected function handlePluginAction($request)
    {
        $action = $request->getParameter('plugin_action');
        $pluginName = $request->getParameter('plugin_name');

        if (!$action || !$pluginName) {
            return;
        }

        try {
            $userId = $this->context->user->getAttribute('user_id');

            if ($action === 'enable') {
                DB::table('atom_plugin')
                    ->where('name', $pluginName)
                    ->update(['is_enabled' => 1, 'updated_at' => DB::raw('NOW()')]);

                // Sync to setting_i18n (legacy support)
                $this->syncToSettingI18n($pluginName, true);

                // Audit log
                $this->logAudit($pluginName, 'enabled', $userId);
                $this->getUser()->setFlash('notice', "Plugin '$pluginName' enabled successfully.");

            } elseif ($action === 'disable') {
                // Check protection status (core, locked, linked records)
                $protection = $this->checkProtection($pluginName);
                if (!$protection['can_disable']) {
                    $this->getUser()->setFlash('error', "Cannot disable '$pluginName': " . $protection['reason']);
                    return;
                }

                // Check dependencies (other plugins that require this one)
                $deps = $this->checkDependencies($pluginName);
                if (!empty($deps)) {
                    $this->getUser()->setFlash('error', "Cannot disable '$pluginName'. Required by: " . implode(', ', $deps));
                    return;
                }

                DB::table('atom_plugin')
                    ->where('name', $pluginName)
                    ->update(['is_enabled' => 0, 'updated_at' => DB::raw('NOW()')]);

                // Sync to setting_i18n (legacy support)
                $this->syncToSettingI18n($pluginName, false);

                // Audit log
                $this->logAudit($pluginName, 'disabled', $userId);
                $this->getUser()->setFlash('notice', "Plugin '$pluginName' disabled successfully.");
            }

            // Clear Symfony cache to apply changes
            $this->clearCache();

            // Redirect to refresh
            $this->redirect(['module' => 'ahgSettings', 'action' => 'plugins']);

        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Plugin Manager: Error: " . $errorMsg);
            if (!empty($errorMsg)) {
                $this->getUser()->setFlash('error', "Error: " . $errorMsg);
            }
        }
    }

    /**
     * Sync plugin enable/disable to setting_i18n (legacy Symfony plugin loading)
     */
    protected function syncToSettingI18n($pluginName, $enable)
    {
        try {
            $row = DB::table('setting_i18n')
                ->where('id', 1)
                ->where('culture', 'en')
                ->first(['value']);

            if (!$row || empty($row->value)) {
                return;
            }

            $plugins = @unserialize($row->value);
            if (!is_array($plugins)) {
                $plugins = [];
            }

            $key = array_search($pluginName, $plugins);

            if ($enable && $key === false) {
                // Add plugin
                $plugins[] = $pluginName;
            } elseif (!$enable && $key !== false) {
                // Remove plugin
                unset($plugins[$key]);
                $plugins = array_values($plugins); // Re-index
            }

            DB::table('setting_i18n')
                ->where('id', 1)
                ->where('culture', 'en')
                ->update(['value' => serialize($plugins)]);

        } catch (Exception $e) {
            error_log("Plugin Manager: Error syncing to setting_i18n: " . $e->getMessage());
        }
    }

    /**
     * Clear Symfony cache after plugin changes
     */
    protected function clearCache()
    {
        try {
            $cacheDir = sfConfig::get('sf_cache_dir');
            if ($cacheDir && is_dir($cacheDir)) {
                // Clear template cache
                sfToolkit::clearDirectory($cacheDir);
            }
        } catch (Exception $e) {
            error_log("Plugin Manager: Cache clear failed: " . $e->getMessage());
        }
    }

    /**
     * Check if plugin can be disabled (core, locked, linked records).
     */
    protected function checkProtection(string $pluginName): array
    {
        try {
            $plugin = DB::table('atom_plugin')->where('name', $pluginName)->first();

            if (!$plugin) {
                return ['can_disable' => false, 'reason' => 'Plugin not found'];
            }

            if (!empty($plugin->is_core)) {
                return ['can_disable' => false, 'reason' => 'Core plugin cannot be disabled'];
            }

            if (!empty($plugin->is_locked)) {
                return ['can_disable' => false, 'reason' => 'Plugin is locked and cannot be disabled'];
            }

            // Check for linked records using record_check_query
            if (!empty($plugin->record_check_query)) {
                $query = trim($plugin->record_check_query);
                // Validate it's a safe SELECT COUNT query
                if (stripos($query, 'SELECT COUNT') === 0 && stripos($query, ';') === false) {
                    $result = DB::select($query);
                    $count = 0;
                    if (!empty($result)) {
                        $row = (array) $result[0];
                        $count = (int) reset($row);
                    }
                    if ($count > 0) {
                        return [
                            'can_disable' => false,
                            'reason' => sprintf(
                                'Plugin has %s linked record(s). Migrate or delete records first.',
                                number_format($count)
                            ),
                        ];
                    }
                }
            }

            return ['can_disable' => true, 'reason' => null];
        } catch (Exception $e) {
            error_log("Plugin Protection check error: " . $e->getMessage());
            return ['can_disable' => true, 'reason' => null];
        }
    }

    protected function checkDependencies($pluginName)
    {
        $dependents = [];
        try {
            $rows = DB::table('atom_plugin')
                ->where('is_enabled', 1)
                ->where('dependencies', 'like', '%' . $pluginName . '%')
                ->where('name', '!=', $pluginName)
                ->pluck('name')
                ->toArray();
            $dependents = $rows;
        } catch (Exception $e) {
            // Ignore if dependencies column doesn't exist
        }
        return $dependents;
    }

    protected function logAudit($pluginName, $action, $userId)
    {
        try {
            DB::table('atom_plugin_audit')->insert([
                'plugin_name' => $pluginName,
                'action' => $action,
                'user_id' => $userId,
                'created_at' => DB::raw('NOW()')
            ]);
        } catch (Exception $e) {
            error_log("Plugin Audit: " . $e->getMessage());
        }
    }
}
