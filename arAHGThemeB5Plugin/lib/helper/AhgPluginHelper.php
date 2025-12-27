<?php
/**
 * Check if a plugin is available (exists in filesystem)
 */
function ahg_plugin_exists($pluginName)
{
    return is_dir(sfConfig::get('sf_plugins_dir') . '/' . $pluginName);
}

/**
 * Check if a plugin is enabled in atom_plugin table
 */
function ahg_plugin_enabled($pluginName)
{
    static $cache = [];
    if (isset($cache[$pluginName])) {
        return $cache[$pluginName];
    }
    
    try {
        $configPath = sfConfig::get('sf_root_dir') . '/config/config.php';
        if (!file_exists($configPath)) {
            return $cache[$pluginName] = false;
        }
        
        $config = require $configPath;
        $params = $config['all']['propel']['param'] ?? [];
        $dsn = $params['dsn'] ?? '';
        
        preg_match('/dbname=([^;]+)/', $dsn, $m);
        $dbname = $m[1] ?? 'atom';
        preg_match('/host=([^;]+)/', $dsn, $m);
        $host = $m[1] ?? 'localhost';
        
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $params['username'] ?? 'root',
            $params['password'] ?? ''
        );
        
        $stmt = $pdo->prepare('SELECT is_enabled FROM atom_plugin WHERE name = ? LIMIT 1');
        $stmt->execute([$pluginName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $cache[$pluginName] = ($result && $result['is_enabled'] == 1);
    } catch (Exception $e) {
        return $cache[$pluginName] = false;
    }
}
