<?php

namespace ahgDataMigrationPlugin\Exporters;

/**
 * Factory for creating sector-specific exporters.
 */
class ExporterFactory
{
    private static array $exporters = [
        'archive' => ArchivesExporter::class,
        'archives' => ArchivesExporter::class,
        'isad' => ArchivesExporter::class,
        'museum' => MuseumExporter::class,
        'spectrum' => MuseumExporter::class,
        'library' => LibraryExporter::class,
        'marc' => LibraryExporter::class,
        'gallery' => GalleryExporter::class,
        'cco' => GalleryExporter::class,
        'dam' => DamExporter::class,
        'dc' => DamExporter::class,
        'dublin_core' => DamExporter::class,
    ];

    /**
     * Create an exporter for the given sector.
     */
    public static function create(string $sector): BaseExporter
    {
        $sector = strtolower(trim($sector));

        if (!isset(self::$exporters[$sector])) {
            throw new \InvalidArgumentException("Unknown sector: {$sector}. Available: " . implode(', ', array_keys(self::$exporters)));
        }

        $class = self::$exporters[$sector];
        return new $class();
    }

    /**
     * Get available sector codes.
     */
    public static function getAvailableSectors(): array
    {
        return ['archives', 'museum', 'library', 'gallery', 'dam'];
    }

    /**
     * Check if a sector is supported.
     */
    public static function isSupported(string $sector): bool
    {
        return isset(self::$exporters[strtolower(trim($sector))]);
    }
}
