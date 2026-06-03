<?php

declare(strict_types=1);

namespace AhgLoan\Adapters;

/**
 * Adapter Factory.
 *
 * Creates sector-specific adapters for loan management.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class AdapterFactory
{
    /** Adapter class map (mutable so register() can add sectors at runtime) */
    private static array $adapters = [
        'museum' => MuseumAdapter::class,
        'gallery' => GalleryAdapter::class,
        'archive' => ArchiveAdapter::class,
        'dam' => DAMAdapter::class,
    ];

    /** Singleton instances */
    private static array $instances = [];

    /**
     * Get adapter for a sector.
     *
     * @param string $sector Sector code
     *
     * @return SectorAdapterInterface
     *
     * @throws \InvalidArgumentException If sector not supported
     */
    public static function create(string $sector): SectorAdapterInterface
    {
        $sector = strtolower($sector);

        if (!isset(self::$adapters[$sector])) {
            throw new \InvalidArgumentException("Unsupported sector: {$sector}. Supported: ".implode(', ', array_keys(self::$adapters)));
        }

        // Return cached instance if available
        if (!isset(self::$instances[$sector])) {
            $className = self::$adapters[$sector];
            self::$instances[$sector] = new $className();
        }

        return self::$instances[$sector];
    }

    /**
     * Get all supported sectors.
     *
     * @return array<string, string> Sector code => name
     */
    public static function getSupportedSectors(): array
    {
        $sectors = [];

        foreach (array_keys(self::$adapters) as $code) {
            $adapter = self::create($code);
            $sectors[$code] = $adapter->getSectorName();
        }

        return $sectors;
    }

    /**
     * Check if a sector is supported.
     */
    public static function isSupported(string $sector): bool
    {
        return isset(self::$adapters[strtolower($sector)]);
    }

    /**
     * Register a custom adapter.
     *
     * @param string $sector   Sector code
     * @param string $class    Adapter class name
     */
    public static function register(string $sector, string $class): void
    {
        if (!is_subclass_of($class, SectorAdapterInterface::class)) {
            throw new \InvalidArgumentException("Adapter class must implement SectorAdapterInterface");
        }

        self::$adapters[strtolower($sector)] = $class;

        // Clear cached instance if exists
        unset(self::$instances[strtolower($sector)]);
    }
}
