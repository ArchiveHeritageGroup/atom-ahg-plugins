<?php
namespace ahgDataMigrationPlugin\Sectors;

require_once __DIR__ . '/SectorInterface.php';
require_once __DIR__ . '/BaseSector.php';
require_once __DIR__ . '/ArchivesSector.php';
require_once __DIR__ . '/MuseumSector.php';
require_once __DIR__ . '/LibrarySector.php';
require_once __DIR__ . '/GallerySector.php';
require_once __DIR__ . '/DamSector.php';

class SectorFactory
{
    private static array $sectors = [];

    public static function get(string $code): SectorInterface
    {
        if (!isset(self::$sectors[$code])) {
            self::$sectors[$code] = self::create($code);
        }
        return self::$sectors[$code];
    }

    private static function create(string $code): SectorInterface
    {
        return match($code) {
            'archive' => new ArchivesSector(),
            'museum' => new MuseumSector(),
            'library' => new LibrarySector(),
            'gallery' => new GallerySector(),
            'dam' => new DamSector(),
            default => throw new \InvalidArgumentException("Unknown sector: $code"),
        };
    }

    public static function getAvailable(): array
    {
        $available = [];
        $codes = ['archive', 'museum', 'library', 'gallery', 'dam'];
        
        foreach ($codes as $code) {
            $available[$code] = self::get($code);
        }
        
        return $available;
    }

    public static function compareFields(string $fromSector, string $toSector): array
    {
        $from = self::get($fromSector)->getFields();
        $to = self::get($toSector)->getFields();
        
        $fromKeys = array_keys($from);
        $toKeys = array_keys($to);
        
        return [
            'common' => array_intersect($fromKeys, $toKeys),
            'source_only' => array_diff($fromKeys, $toKeys),
            'target_only' => array_diff($toKeys, $fromKeys),
        ];
    }
}
