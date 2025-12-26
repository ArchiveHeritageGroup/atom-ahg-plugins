<?php

class pluginAdminActions extends sfActions
{
    public function preExecute()
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    public function executeIndex(sfWebRequest $request)
    {
        $conn = Propel::getConnection();
        
        // Check if table exists
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM atom_plugin");
            $this->databaseAvailable = true;
        } catch (Exception $e) {
            $this->databaseAvailable = false;
            $this->plugins = [];
            $this->categories = [];
            return;
        }
        
        // Get all plugins
        $stmt = $conn->query("SELECT * FROM atom_plugin ORDER BY category, name");
        $plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->plugins = $plugins;
        $this->categories = $this->groupByCategory($plugins);
    }

    public function executeToggle(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $name = $request->getParameter('name');
        $enable = $request->getParameter('enable') === 'true';
        $reason = $request->getParameter('reason', '');
        
        $conn = Propel::getConnection();
        
        try {
            if ($enable) {
                $stmt = $conn->prepare("UPDATE atom_plugin SET is_enabled = 1, enabled_at = NOW(), disabled_at = NULL, updated_at = NOW() WHERE name = ?");
                $stmt->execute([$name]);
                $action = 'enable';
                $prevState = 'disabled';
                $newState = 'enabled';
            } else {
                // Check if core
                $stmt = $conn->prepare("SELECT is_core FROM atom_plugin WHERE name = ?");
                $stmt->execute([$name]);
                $plugin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($plugin && $plugin['is_core']) {
                    return $this->renderText(json_encode(['success' => false, 'error' => 'Cannot disable core plugin']));
                }
                
                $stmt = $conn->prepare("UPDATE atom_plugin SET is_enabled = 0, disabled_at = NOW(), updated_at = NOW() WHERE name = ?");
                $stmt->execute([$name]);
                $action = 'disable';
                $prevState = 'enabled';
                $newState = 'disabled';
            }
            
            // Log audit
            $userId = $this->context->user->getUserId();
            $stmt = $conn->prepare("INSERT INTO atom_plugin_audit (plugin_name, action, previous_state, new_state, user_id, reason, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $action, $prevState, $newState, $userId, $reason, $_SERVER['REMOTE_ADDR'] ?? null]);
            
            return $this->renderText(json_encode(['success' => true]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    public function executeAuditLog(sfWebRequest $request)
    {
        $conn = Propel::getConnection();
        $pluginName = $request->getParameter('plugin');
        
        $sql = "SELECT * FROM atom_plugin_audit ORDER BY created_at DESC LIMIT 100";
        if ($pluginName) {
            $sql = "SELECT * FROM atom_plugin_audit WHERE plugin_name = ? ORDER BY created_at DESC LIMIT 100";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$pluginName]);
        } else {
            $stmt = $conn->query($sql);
        }
        
        $this->logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function executeSync(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $conn = Propel::getConnection();
        $pluginsDir = sfConfig::get('sf_plugins_dir');
        $added = 0;
        $updated = 0;

        foreach (glob($pluginsDir.'/*Plugin', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            $category = $this->detectCategory($name);
            $isCore = in_array($name, ['sfPropelPlugin', 'arElasticSearchPlugin', 'qbAclPlugin']);
            
            $stmt = $conn->prepare("SELECT id FROM atom_plugin WHERE name = ?");
            $stmt->execute([$name]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $stmt = $conn->prepare("UPDATE atom_plugin SET plugin_path = ?, category = ?, updated_at = NOW() WHERE name = ?");
                $stmt->execute([$dir, $category, $name]);
                $updated++;
            } else {
                $stmt = $conn->prepare("INSERT INTO atom_plugin (name, plugin_path, category, is_core, is_enabled, load_order, created_at, updated_at) VALUES (?, ?, ?, ?, 0, 100, NOW(), NOW())");
                $stmt->execute([$name, $dir, $category, $isCore ? 1 : 0]);
                $added++;
            }
        }
        
        return $this->renderText(json_encode(['success' => true, 'added' => $added, 'updated' => $updated]));
    }

    protected function groupByCategory(array $plugins): array
    {
        $groups = [];
        foreach ($plugins as $plugin) {
            $cat = $plugin['category'] ?? 'general';
            $groups[$cat][] = $plugin;
        }
        ksort($groups);
        return $groups;
    }

    protected function detectCategory(string $name): string
    {
        if (preg_match('/Theme/i', $name)) return 'theme';
        if (preg_match('/(Dc|Ead|Isad|Isdf|Isaar|Rad|Dacs|Mods)/i', $name)) return 'metadata';
        if (preg_match('/(Oidc|Ldap|Cas)/i', $name)) return 'integration';
        if (preg_match('/(Security|Acl|Clearance)/i', $name)) return 'security';
        if (preg_match('/(sf|Propel|Pager|WebBrowser)/i', $name)) return 'core';
        if (preg_match('/(Spectrum|Museum|Grap|Condition|3D|Iiif|Access|Research|Library|Gallery|DAM|Display)/i', $name)) return 'ahg';
        return 'general';
    }
}
