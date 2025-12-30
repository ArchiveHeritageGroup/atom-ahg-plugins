<?php

/**
 * Museum Metadata helper functions.
 *
 * These functions are available in templates when the museum metadata plugin is enabled.
 */

/**
 * Get museum metadata adapter instance.
 *
 * @return \ahgMuseumPlugin\Adapters\LaravelMuseumAdapter|null
 */
function get_museum_adapter()
{
    static $adapter = null;
    static $attempted = false;

    if ($attempted) {
        return $adapter;
    }

    $attempted = true;

    try {
        require_once sfConfig::get('sf_plugins_dir').'/ahgMuseumPlugin/lib/Adapters/LaravelMuseumAdapter.php';
        $adapter = new \ahgMuseumPlugin\Adapters\LaravelMuseumAdapter();
    } catch (Exception $e) {
        error_log('MuseumMetadataHelper: Failed to initialize adapter - ' . $e->getMessage());
        $adapter = null;
    } catch (TypeError $e) {
        error_log('MuseumMetadataHelper: Failed to initialize adapter - ' . $e->getMessage());
        $adapter = null;
    }

    return $adapter;
}

/**
 * Check if information object has museum metadata.
 *
 * @param  $object
 *
 * @return bool
 */
function has_museum_metadata($object)
{
    if (!$object || !isset($object->id) || !$object->id) {
        return false;
    }

    $adapter = get_museum_adapter();
    if (!$adapter) {
        return false;
    }

    try {
        return $adapter->hasMuseumMetadata($object->id);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get museum metadata for information object.
 *
 * @param  $object
 *
 * @return null|array
 */
function get_museum_metadata($object)
{
    if (!$object || !isset($object->id) || !$object->id) {
        return null;
    }

    $adapter = get_museum_adapter();
    if (!$adapter) {
        return null;
    }

    try {
        $museumObject = $adapter->getMuseumMetadata($object->id);
        return $museumObject ? $museumObject->toArray() : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Format museum measurements for display.
 *
 * @param array $measurements
 *
 * @return string
 */
function format_museum_measurements($measurements)
{
    if (empty($measurements)) {
        return '';
    }

    $adapter = get_museum_adapter();
    if (!$adapter) {
        return '';
    }

    try {
        return $adapter->formatMeasurements($measurements);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Check if museum metadata is enabled.
 *
 * @return bool
 */
function is_museum_metadata_enabled()
{
    return sfConfig::get('app_museum_metadata_enabled', false);
}

/**
 * Get work types for select dropdown.
 *
 * @return array
 */
function get_work_types()
{
    $adapter = get_museum_adapter();
    if (!$adapter) {
        return [];
    }

    try {
        $workTypes = $adapter->getWorkTypes();
        $options = [];
        foreach ($workTypes as $type) {
            $config = $adapter->getWorkTypeConfig($type);
            $options[$type] = $config['label'] ?? ucwords(str_replace('_', ' ', $type));
        }
        return $options;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get materials for autocomplete.
 *
 * @return array
 */
function get_materials()
{
    $adapter = get_museum_adapter();
    if (!$adapter) {
        return [];
    }

    try {
        return $adapter->getMaterials();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get techniques for autocomplete.
 *
 * @return array
 */
function get_techniques()
{
    $adapter = get_museum_adapter();
    if (!$adapter) {
        return [];
    }

    try {
        return $adapter->getTechniques();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Format material list for display.
 *
 * @param array $materials
 *
 * @return string
 */
function format_materials($materials)
{
    if (empty($materials)) {
        return '';
    }
    return implode(', ', $materials);
}

/**
 * Format technique list for display.
 *
 * @param array $techniques
 *
 * @return string
 */
function format_techniques($techniques)
{
    if (empty($techniques)) {
        return '';
    }
    return implode(', ', $techniques);
}

/**
 * Get work type label.
 *
 * @param string $workType
 *
 * @return string
 */
function get_work_type_label($workType)
{
    $adapter = get_museum_adapter();
    if (!$adapter) {
        return ucwords(str_replace('_', ' ', $workType));
    }

    try {
        $config = $adapter->getWorkTypeConfig($workType);
        return $config['label'] ?? ucwords(str_replace('_', ' ', $workType));
    } catch (Exception $e) {
        return ucwords(str_replace('_', ' ', $workType));
    }
}

/**
 * Format date range for display.
 *
 * @param null|string $earliest
 * @param null|string $latest
 *
 * @return string
 */
function format_date_range($earliest, $latest)
{
    if (empty($earliest) && empty($latest)) {
        return '';
    }

    if ($earliest && $latest && $earliest === $latest) {
        return $earliest;
    }

    if ($earliest && $latest) {
        return "{$earliest} - {$latest}";
    }

    if ($earliest) {
        return "From {$earliest}";
    }

    return "Until {$latest}";
}

/**
 * Get extent statement from measurements.
 *
 * @param array $measurements
 *
 * @return string
 */
function get_extent_statement($measurements)
{
    if (empty($measurements)) {
        return '';
    }

    $adapter = get_museum_adapter();
    if (!$adapter) {
        return '';
    }

    try {
        return $adapter->getExtentStatement($measurements);
    } catch (Exception $e) {
        return '';
    }
}
