<?php
namespace AhgMigration\Sectors;

class SectorFactory
{
    protected static array $sectors = [];
    
    /**
     * Get sector definition by ID
     */
    public static function get(string $sectorId): SectorInterface
    {
        if (!isset(self::$sectors[$sectorId])) {
            self::$sectors[$sectorId] = match($sectorId) {
                'archives' => new ArchivesSector(),
                'museum' => new MuseumSector(),
                'library' => new LibrarySector(),
                'gallery' => new GallerySector(),
                'dam' => new DamSector(),
                default => throw new \InvalidArgumentException("Unknown sector: $sectorId")
            };
        }
        
        return self::$sectors[$sectorId];
    }
    
    /**
     * Get all available sectors
     */
    public static function getAll(): array
    {
        $sectorIds = ['archives', 'museum', 'library', 'gallery', 'dam'];
        $sectors = [];
        
        foreach ($sectorIds as $id) {
            $sector = self::get($id);
            $sectors[$id] = [
                'id' => $sector->getId(),
                'name' => $sector->getName(),
                'description' => $sector->getDescription(),
                'standard' => $sector->getStandard(),
                'plugin' => $sector->getPlugin()
            ];
        }
        
        return $sectors;
    }
    
    /**
     * Get sectors available based on installed plugins
     */
    public static function getAvailable(): array
    {
        $all = self::getAll();
        $available = [];
        
        foreach ($all as $id => $info) {
            // Archives is always available (core AtoM)
            if ($info['plugin'] === null) {
                $available[$id] = $info;
                continue;
            }
            
            // Check if required plugin is enabled
            if (self::isPluginEnabled($info['plugin'])) {
                $available[$id] = $info;
            }
        }
        
        return $available;
    }
    
    /**
     * Check if a plugin is enabled
     */
    protected static function isPluginEnabled(string $pluginName): bool
    {
        // Check atom_plugin table
        try {
            $result = \Illuminate\Database\Capsule\Manager::table('atom_plugin')
                ->where('name', $pluginName)
                ->where('is_enabled', 1)
                ->exists();
            return $result;
        } catch (\Exception $e) {
            // Fallback: check if plugin directory exists
            $pluginPath = sfConfig::get('sf_plugins_dir') . '/' . $pluginName;
            return is_dir($pluginPath);
        }
    }
    
    /**
     * Get field comparison between two sectors
     */
    public static function compareFields(string $fromSector, string $toSector): array
    {
        $from = self::get($fromSector)->getFields();
        $to = self::get($toSector)->getFields();
        
        $common = array_intersect_key($from, $to);
        $onlyFrom = array_diff_key($from, $to);
        $onlyTo = array_diff_key($to, $from);
        
        return [
            'common' => array_keys($common),
            'source_only' => array_keys($onlyFrom),
            'target_only' => array_keys($onlyTo)
        ];
    }
}
