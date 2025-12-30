<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services;

/**
 * Measurement Service.
 *
 * Handles museum object measurements with automatic unit conversion,
 * display formatting, and CCO/CDWA compliance.
 *
 * Supports: length, weight, volume, area, and angle measurements.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class MeasurementService
{
    /** Length units with conversion factors to meters */
    public const LENGTH_UNITS = [
        'mm' => ['factor' => 0.001, 'name' => 'millimeters', 'symbol' => 'mm'],
        'cm' => ['factor' => 0.01, 'name' => 'centimeters', 'symbol' => 'cm'],
        'm' => ['factor' => 1.0, 'name' => 'meters', 'symbol' => 'm'],
        'km' => ['factor' => 1000.0, 'name' => 'kilometers', 'symbol' => 'km'],
        'in' => ['factor' => 0.0254, 'name' => 'inches', 'symbol' => 'in'],
        'ft' => ['factor' => 0.3048, 'name' => 'feet', 'symbol' => 'ft'],
        'yd' => ['factor' => 0.9144, 'name' => 'yards', 'symbol' => 'yd'],
        'mi' => ['factor' => 1609.344, 'name' => 'miles', 'symbol' => 'mi'],
    ];

    /** Weight units with conversion factors to kilograms */
    public const WEIGHT_UNITS = [
        'mg' => ['factor' => 0.000001, 'name' => 'milligrams', 'symbol' => 'mg'],
        'g' => ['factor' => 0.001, 'name' => 'grams', 'symbol' => 'g'],
        'kg' => ['factor' => 1.0, 'name' => 'kilograms', 'symbol' => 'kg'],
        't' => ['factor' => 1000.0, 'name' => 'metric tons', 'symbol' => 't'],
        'oz' => ['factor' => 0.0283495, 'name' => 'ounces', 'symbol' => 'oz'],
        'lb' => ['factor' => 0.453592, 'name' => 'pounds', 'symbol' => 'lb'],
        'st' => ['factor' => 6.35029, 'name' => 'stones', 'symbol' => 'st'],
    ];

    /** Volume units with conversion factors to liters */
    public const VOLUME_UNITS = [
        'ml' => ['factor' => 0.001, 'name' => 'milliliters', 'symbol' => 'ml'],
        'cl' => ['factor' => 0.01, 'name' => 'centiliters', 'symbol' => 'cl'],
        'l' => ['factor' => 1.0, 'name' => 'liters', 'symbol' => 'L'],
        'm3' => ['factor' => 1000.0, 'name' => 'cubic meters', 'symbol' => 'm³'],
        'cm3' => ['factor' => 0.001, 'name' => 'cubic centimeters', 'symbol' => 'cm³'],
        'fl_oz' => ['factor' => 0.0295735, 'name' => 'fluid ounces', 'symbol' => 'fl oz'],
        'pt' => ['factor' => 0.473176, 'name' => 'pints', 'symbol' => 'pt'],
        'qt' => ['factor' => 0.946353, 'name' => 'quarts', 'symbol' => 'qt'],
        'gal' => ['factor' => 3.78541, 'name' => 'gallons', 'symbol' => 'gal'],
    ];

    /** Area units with conversion factors to square meters */
    public const AREA_UNITS = [
        'mm2' => ['factor' => 0.000001, 'name' => 'square millimeters', 'symbol' => 'mm²'],
        'cm2' => ['factor' => 0.0001, 'name' => 'square centimeters', 'symbol' => 'cm²'],
        'm2' => ['factor' => 1.0, 'name' => 'square meters', 'symbol' => 'm²'],
        'km2' => ['factor' => 1000000.0, 'name' => 'square kilometers', 'symbol' => 'km²'],
        'in2' => ['factor' => 0.00064516, 'name' => 'square inches', 'symbol' => 'in²'],
        'ft2' => ['factor' => 0.092903, 'name' => 'square feet', 'symbol' => 'ft²'],
        'yd2' => ['factor' => 0.836127, 'name' => 'square yards', 'symbol' => 'yd²'],
        'ac' => ['factor' => 4046.86, 'name' => 'acres', 'symbol' => 'ac'],
        'ha' => ['factor' => 10000.0, 'name' => 'hectares', 'symbol' => 'ha'],
    ];

    /** Measurement types for museum objects */
    public const MEASUREMENT_TYPES = [
        'height' => ['category' => 'length', 'label' => 'Height'],
        'width' => ['category' => 'length', 'label' => 'Width'],
        'depth' => ['category' => 'length', 'label' => 'Depth'],
        'length' => ['category' => 'length', 'label' => 'Length'],
        'diameter' => ['category' => 'length', 'label' => 'Diameter'],
        'circumference' => ['category' => 'length', 'label' => 'Circumference'],
        'thickness' => ['category' => 'length', 'label' => 'Thickness'],
        'weight' => ['category' => 'weight', 'label' => 'Weight'],
        'volume' => ['category' => 'volume', 'label' => 'Volume'],
        'area' => ['category' => 'area', 'label' => 'Area'],
        'base_height' => ['category' => 'length', 'label' => 'Base Height'],
        'base_width' => ['category' => 'length', 'label' => 'Base Width'],
        'base_depth' => ['category' => 'length', 'label' => 'Base Depth'],
        'frame_height' => ['category' => 'length', 'label' => 'Frame Height'],
        'frame_width' => ['category' => 'length', 'label' => 'Frame Width'],
        'frame_depth' => ['category' => 'length', 'label' => 'Frame Depth'],
        'image_height' => ['category' => 'length', 'label' => 'Image Height'],
        'image_width' => ['category' => 'length', 'label' => 'Image Width'],
        'sheet_height' => ['category' => 'length', 'label' => 'Sheet Height'],
        'sheet_width' => ['category' => 'length', 'label' => 'Sheet Width'],
        'plate_height' => ['category' => 'length', 'label' => 'Plate Height'],
        'plate_width' => ['category' => 'length', 'label' => 'Plate Width'],
    ];

    /** Default display units by category */
    private array $defaultUnits = [
        'length' => 'cm',
        'weight' => 'kg',
        'volume' => 'l',
        'area' => 'cm2',
    ];

    /** Precision for display (decimal places) */
    private int $precision = 2;

    /**
     * Convert a measurement from one unit to another.
     *
     * @param float  $value    The value to convert
     * @param string $fromUnit Source unit
     * @param string $toUnit   Target unit
     *
     * @return float Converted value
     *
     * @throws \InvalidArgumentException If units are incompatible
     */
    public function convert(float $value, string $fromUnit, string $toUnit): float
    {
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);

        if ($fromUnit === $toUnit) {
            return $value;
        }

        // Find the category for these units
        $category = $this->findUnitCategory($fromUnit);
        $toCategory = $this->findUnitCategory($toUnit);

        if (!$category || !$toCategory) {
            throw new \InvalidArgumentException("Unknown unit: {$fromUnit} or {$toUnit}");
        }

        if ($category !== $toCategory) {
            throw new \InvalidArgumentException(
                "Cannot convert between {$category} ({$fromUnit}) and {$toCategory} ({$toUnit})"
            );
        }

        $units = $this->getUnitsForCategory($category);

        // Convert to base unit, then to target
        $baseValue = $value * $units[$fromUnit]['factor'];
        $result = $baseValue / $units[$toUnit]['factor'];

        return $result;
    }

    /**
     * Convert measurement to metric units.
     *
     * @param float  $value Value to convert
     * @param string $unit  Current unit
     *
     * @return array ['value' => float, 'unit' => string]
     */
    public function toMetric(float $value, string $unit): array
    {
        $category = $this->findUnitCategory($unit);

        if (!$category) {
            return ['value' => $value, 'unit' => $unit];
        }

        $metricUnits = [
            'length' => 'cm',
            'weight' => 'kg',
            'volume' => 'l',
            'area' => 'cm2',
        ];

        $targetUnit = $metricUnits[$category] ?? $unit;
        $converted = $this->convert($value, $unit, $targetUnit);

        return ['value' => $converted, 'unit' => $targetUnit];
    }

    /**
     * Convert measurement to imperial units.
     *
     * @param float  $value Value to convert
     * @param string $unit  Current unit
     *
     * @return array ['value' => float, 'unit' => string]
     */
    public function toImperial(float $value, string $unit): array
    {
        $category = $this->findUnitCategory($unit);

        if (!$category) {
            return ['value' => $value, 'unit' => $unit];
        }

        $imperialUnits = [
            'length' => 'in',
            'weight' => 'lb',
            'volume' => 'gal',
            'area' => 'in2',
        ];

        $targetUnit = $imperialUnits[$category] ?? $unit;
        $converted = $this->convert($value, $unit, $targetUnit);

        return ['value' => $converted, 'unit' => $targetUnit];
    }

    /**
     * Format a measurement for display.
     *
     * @param float       $value     The value
     * @param string      $unit      The unit
     * @param int|null    $precision Decimal places (null uses default)
     * @param string|null $locale    Locale for number formatting
     *
     * @return string Formatted measurement
     */
    public function format(
        float $value,
        string $unit,
        ?int $precision = null,
        ?string $locale = null
    ): string {
        $precision = $precision ?? $this->precision;
        $unit = strtolower($unit);

        // Get symbol
        $symbol = $this->getUnitSymbol($unit);

        // Format number
        if ($locale && class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);
            $formatted = $formatter->format($value);
        } else {
            $formatted = number_format($value, $precision, '.', ',');
        }

        return "{$formatted} {$symbol}";
    }

    /**
     * Format dimensions (H x W x D).
     *
     * @param array       $dimensions ['height' => float, 'width' => float, 'depth' => float]
     * @param string      $unit       Unit for all dimensions
     * @param int|null    $precision  Decimal places
     *
     * @return string Formatted dimensions
     */
    public function formatDimensions(
        array $dimensions,
        string $unit = 'cm',
        ?int $precision = null
    ): string {
        $precision = $precision ?? $this->precision;
        $symbol = $this->getUnitSymbol($unit);

        $parts = [];

        if (isset($dimensions['height'])) {
            $parts[] = number_format($dimensions['height'], $precision);
        }
        if (isset($dimensions['width'])) {
            $parts[] = number_format($dimensions['width'], $precision);
        }
        if (isset($dimensions['depth'])) {
            $parts[] = number_format($dimensions['depth'], $precision);
        }

        if (empty($parts)) {
            return '';
        }

        return implode(' × ', $parts).' '.$symbol;
    }

    /**
     * Parse a measurement string.
     *
     * @param string $input Input like "15.5 cm" or "6 inches"
     *
     * @return array|null ['value' => float, 'unit' => string] or null if invalid
     */
    public function parse(string $input): ?array
    {
        $input = trim($input);

        // Try pattern: number + unit
        if (preg_match('/^([\d.,]+)\s*([a-zA-Z²³]+)$/u', $input, $matches)) {
            $value = (float) str_replace(',', '.', $matches[1]);
            $unit = $this->normalizeUnit($matches[2]);

            if ($unit && $this->findUnitCategory($unit)) {
                return ['value' => $value, 'unit' => $unit];
            }
        }

        return null;
    }

    /**
     * Parse dimensions string.
     *
     * @param string $input Input like "10 x 20 x 5 cm" or "10cm x 20cm x 5cm"
     *
     * @return array|null Parsed dimensions or null
     */
    public function parseDimensions(string $input): ?array
    {
        $input = trim($input);

        // Pattern: H x W x D unit or HxWxD unit
        $pattern = '/^([\d.,]+)\s*[x×]\s*([\d.,]+)(?:\s*[x×]\s*([\d.,]+))?\s*([a-zA-Z²³]+)?$/iu';

        if (preg_match($pattern, $input, $matches)) {
            $result = [
                'height' => (float) str_replace(',', '.', $matches[1]),
                'width' => (float) str_replace(',', '.', $matches[2]),
            ];

            if (!empty($matches[3])) {
                $result['depth'] = (float) str_replace(',', '.', $matches[3]);
            }

            if (!empty($matches[4])) {
                $result['unit'] = $this->normalizeUnit($matches[4]) ?? 'cm';
            } else {
                $result['unit'] = 'cm'; // Default
            }

            return $result;
        }

        return null;
    }

    /**
     * Get all measurements converted to a common unit.
     *
     * @param array  $measurements Array of ['value' => float, 'unit' => string, 'type' => string]
     * @param string $targetUnit   Target unit for conversion
     *
     * @return array Converted measurements
     */
    public function normalizeAll(array $measurements, string $targetUnit): array
    {
        $result = [];

        foreach ($measurements as $key => $measurement) {
            if (isset($measurement['value'], $measurement['unit'])) {
                try {
                    $converted = $this->convert(
                        $measurement['value'],
                        $measurement['unit'],
                        $targetUnit
                    );
                    $result[$key] = [
                        'value' => $converted,
                        'unit' => $targetUnit,
                        'type' => $measurement['type'] ?? $key,
                        'original_value' => $measurement['value'],
                        'original_unit' => $measurement['unit'],
                    ];
                } catch (\InvalidArgumentException $e) {
                    // Keep original if conversion fails
                    $result[$key] = $measurement;
                }
            } else {
                $result[$key] = $measurement;
            }
        }

        return $result;
    }

    /**
     * Calculate area from dimensions.
     *
     * @param float  $width  Width value
     * @param float  $height Height value
     * @param string $unit   Unit of dimensions
     *
     * @return array ['value' => float, 'unit' => string]
     */
    public function calculateArea(float $width, float $height, string $unit): array
    {
        $area = $width * $height;

        // Convert to square unit
        $areaUnit = match ($unit) {
            'mm' => 'mm2',
            'cm' => 'cm2',
            'm' => 'm2',
            'in' => 'in2',
            'ft' => 'ft2',
            default => 'cm2',
        };

        return ['value' => $area, 'unit' => $areaUnit];
    }

    /**
     * Calculate volume from dimensions.
     *
     * @param float  $width  Width
     * @param float  $height Height
     * @param float  $depth  Depth
     * @param string $unit   Unit of dimensions
     *
     * @return array ['value' => float, 'unit' => string]
     */
    public function calculateVolume(float $width, float $height, float $depth, string $unit): array
    {
        $volume = $width * $height * $depth;

        // Convert to cubic unit (expressed in ml/liters for practicality)
        $volumeUnit = match ($unit) {
            'mm' => 'ml',  // mm³ = 0.001 ml
            'cm' => 'cm3',
            'm' => 'm3',
            default => 'cm3',
        };

        // Adjust for mm (1 mm³ = 0.000001 ml)
        if ('mm' === $unit) {
            $volume *= 0.001;
        }

        return ['value' => $volume, 'unit' => $volumeUnit];
    }

    /**
     * Get available units for a category.
     *
     * @param string $category length, weight, volume, or area
     *
     * @return array Unit definitions
     */
    public function getUnitsForCategory(string $category): array
    {
        return match ($category) {
            'length' => self::LENGTH_UNITS,
            'weight' => self::WEIGHT_UNITS,
            'volume' => self::VOLUME_UNITS,
            'area' => self::AREA_UNITS,
            default => [],
        };
    }

    /**
     * Get unit category for a unit.
     */
    public function findUnitCategory(string $unit): ?string
    {
        $unit = strtolower($unit);

        if (isset(self::LENGTH_UNITS[$unit])) {
            return 'length';
        }
        if (isset(self::WEIGHT_UNITS[$unit])) {
            return 'weight';
        }
        if (isset(self::VOLUME_UNITS[$unit])) {
            return 'volume';
        }
        if (isset(self::AREA_UNITS[$unit])) {
            return 'area';
        }

        return null;
    }

    /**
     * Get unit symbol for display.
     */
    public function getUnitSymbol(string $unit): string
    {
        $unit = strtolower($unit);
        $allUnits = array_merge(
            self::LENGTH_UNITS,
            self::WEIGHT_UNITS,
            self::VOLUME_UNITS,
            self::AREA_UNITS
        );

        return $allUnits[$unit]['symbol'] ?? $unit;
    }

    /**
     * Get unit full name.
     */
    public function getUnitName(string $unit): string
    {
        $unit = strtolower($unit);
        $allUnits = array_merge(
            self::LENGTH_UNITS,
            self::WEIGHT_UNITS,
            self::VOLUME_UNITS,
            self::AREA_UNITS
        );

        return $allUnits[$unit]['name'] ?? $unit;
    }

    /**
     * Normalize unit string to standard form.
     */
    private function normalizeUnit(string $unit): ?string
    {
        $unit = strtolower(trim($unit));

        // Common aliases
        $aliases = [
            'centimeter' => 'cm',
            'centimeters' => 'cm',
            'centimetre' => 'cm',
            'centimetres' => 'cm',
            'millimeter' => 'mm',
            'millimeters' => 'mm',
            'millimetre' => 'mm',
            'millimetres' => 'mm',
            'meter' => 'm',
            'meters' => 'm',
            'metre' => 'm',
            'metres' => 'm',
            'inch' => 'in',
            'inches' => 'in',
            '"' => 'in',
            'foot' => 'ft',
            'feet' => 'ft',
            "'" => 'ft',
            'kilogram' => 'kg',
            'kilograms' => 'kg',
            'gram' => 'g',
            'grams' => 'g',
            'pound' => 'lb',
            'pounds' => 'lb',
            'lbs' => 'lb',
            'ounce' => 'oz',
            'ounces' => 'oz',
            'liter' => 'l',
            'liters' => 'l',
            'litre' => 'l',
            'litres' => 'l',
            'sq cm' => 'cm2',
            'sq m' => 'm2',
            'sq in' => 'in2',
            'sq ft' => 'ft2',
        ];

        return $aliases[$unit] ?? ($this->findUnitCategory($unit) ? $unit : null);
    }

    /**
     * Set default display precision.
     */
    public function setPrecision(int $precision): self
    {
        $this->precision = max(0, min(10, $precision));

        return $this;
    }

    /**
     * Set default units for categories.
     */
    public function setDefaultUnits(array $defaults): self
    {
        foreach ($defaults as $category => $unit) {
            if (isset($this->defaultUnits[$category]) && $this->findUnitCategory($unit) === $category) {
                $this->defaultUnits[$category] = $unit;
            }
        }

        return $this;
    }

    /**
     * Get measurement types for dropdowns.
     */
    public function getMeasurementTypes(): array
    {
        return self::MEASUREMENT_TYPES;
    }

    /**
     * Get units for dropdown by category.
     */
    public function getUnitsForDropdown(string $category): array
    {
        $units = $this->getUnitsForCategory($category);
        $options = [];

        foreach ($units as $key => $unit) {
            $options[$key] = "{$unit['name']} ({$unit['symbol']})";
        }

        return $options;
    }
}
