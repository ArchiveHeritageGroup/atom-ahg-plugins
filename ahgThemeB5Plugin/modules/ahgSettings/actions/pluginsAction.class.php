<?php
use AtomExtensions\Services\AclService;

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
    }

    protected function loadPlugins()
    {
        $plugins = [];
        try {
            $conn = Propel::getConnection();
            $sql = "SELECT * FROM atom_plugin ORDER BY category, name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $plugins[] = $row;
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
            $conn = Propel::getConnection();
            $userId = $this->context->user->getAttribute('user_id');

            if ($action === 'enable') {
                $sql = "UPDATE atom_plugin SET is_enabled = 1, updated_at = NOW() WHERE name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$pluginName]);

                // Sync to setting_i18n (legacy support)
                $this->syncToSettingI18n($conn, $pluginName, true);

                // Audit log
                $this->logAudit($conn, $pluginName, 'enabled', $userId);
                $this->getUser()->setFlash('notice', "Plugin '$pluginName' enabled successfully.");

            } elseif ($action === 'disable') {
                // Check dependencies first
                $deps = $this->checkDependencies($conn, $pluginName);
                if (!empty($deps)) {
                    $this->getUser()->setFlash('error', "Cannot disable '$pluginName'. Required by: " . implode(', ', $deps));
                    return;
                }

                $sql = "UPDATE atom_plugin SET is_enabled = 0, updated_at = NOW() WHERE name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$pluginName]);

                // Sync to setting_i18n (legacy support)
                $this->syncToSettingI18n($conn, $pluginName, false);

                // Audit log
                $this->logAudit($conn, $pluginName, 'disabled', $userId);
                $this->getUser()->setFlash('notice', "Plugin '$pluginName' disabled successfully.");
            }

            // Clear Symfony cache to apply changes
            $this->clearCache();

            // Redirect to refresh
            $this->redirect(['module' => 'ahgSettings', 'action' => 'plugins']);

        } catch (Exception $e) {
            error_log("Plugin Manager: Error: " . $e->getMessage());
            $this->getUser()->setFlash('error', "Error: " . $e->getMessage());
        }
    }

    /**
     * Sync plugin enable/disable to setting_i18n (legacy Symfony plugin loading)
     */
    protected function syncToSettingI18n($conn, $pluginName, $enable)
    {
        try {
            $sql = "SELECT value FROM setting_i18n WHERE id = 1 AND culture = 'en'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['value'])) {
                return;
            }

            $plugins = @unserialize($row['value']);
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

            $sql = "UPDATE setting_i18n SET value = ? WHERE id = 1 AND culture = 'en'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([serialize($plugins)]);

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

    protected function checkDependencies($conn, $pluginName)
    {
        $dependents = [];
        try {
            $sql = "SELECT name FROM atom_plugin WHERE is_enabled = 1 AND dependencies LIKE ? AND name != ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['%' . $pluginName . '%', $pluginName]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dependents[] = $row['name'];
            }
        } catch (Exception $e) {
            // Ignore if dependencies column doesn't exist
        }
        return $dependents;
    }

    protected function logAudit($conn, $pluginName, $action, $userId)
    {
        try {
            $sql = "INSERT INTO atom_plugin_audit (plugin_name, action, user_id, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$pluginName, $action, $userId]);
        } catch (Exception $e) {
            error_log("Plugin Audit: " . $e->getMessage());
        }
    }
}
