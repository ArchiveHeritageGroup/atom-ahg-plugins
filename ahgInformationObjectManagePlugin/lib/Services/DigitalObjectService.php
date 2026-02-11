<?php

namespace AhgInformationObjectManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Read-only digital object service using Laravel Query Builder.
 *
 * Write operations (upload, delete) delegate to QubitDigitalObject (Propel)
 * because the Propel model handles file system operations, derivative
 * generation, checksum computation, and search index updates.
 *
 * Usage IDs: 140=Master, 141=Reference, 142=Thumbnail
 */
class DigitalObjectService
{
    // Usage term IDs (taxonomy 47)
    const USAGE_MASTER = 140;
    const USAGE_REFERENCE = 141;
    const USAGE_THUMBNAIL = 142;

    // Media type term IDs (taxonomy 46)
    const MEDIA_AUDIO = 135;
    const MEDIA_IMAGE = 136;
    const MEDIA_TEXT = 137;
    const MEDIA_VIDEO = 138;
    const MEDIA_OTHER = 139;

    /**
     * Get the master digital object for an information object.
     *
     * @param int $ioId Information object ID
     *
     * @return array|null Structured digital object data or null
     */
    public static function getByInformationObjectId(int $ioId): ?array
    {
        $master = DB::table('digital_object')
            ->where('object_id', $ioId)
            ->whereNull('parent_id')
            ->first();

        if (!$master) {
            return null;
        }

        $result = self::formatRow($master);
        $result['derivatives'] = self::getDerivatives($master->id);

        return $result;
    }

    /**
     * Get a digital object by its ID with full metadata.
     *
     * @param int $doId Digital object ID
     *
     * @return array|null Structured data or null
     */
    public static function getById(int $doId): ?array
    {
        $row = DB::table('digital_object')
            ->where('id', $doId)
            ->first();

        if (!$row) {
            return null;
        }

        $result = self::formatRow($row);

        // Only load derivatives for master objects
        if (null === $row->parent_id) {
            $result['derivatives'] = self::getDerivatives($doId);
        }

        return $result;
    }

    /**
     * Get all derivatives for a master digital object.
     *
     * @param int $masterId Master digital object ID
     *
     * @return array Keyed by usage type: ['master' => ..., 'reference' => ..., 'thumbnail' => ...]
     */
    public static function getDerivatives(int $masterId): array
    {
        $derivatives = DB::table('digital_object')
            ->where('parent_id', $masterId)
            ->get()
            ->all();

        $result = [
            'reference' => null,
            'thumbnail' => null,
        ];

        foreach ($derivatives as $d) {
            switch ((int) $d->usage_id) {
                case self::USAGE_REFERENCE:
                    $result['reference'] = self::formatRow($d);
                    break;
                case self::USAGE_THUMBNAIL:
                    $result['thumbnail'] = self::formatRow($d);
                    break;
            }
        }

        return $result;
    }

    /**
     * Get extended metadata from digital_object_metadata table.
     *
     * @param int $doId Digital object ID
     *
     * @return array Metadata key-value pairs (empty if none)
     */
    public static function getMetadata(int $doId): array
    {
        try {
            $meta = DB::table('digital_object_metadata')
                ->where('digital_object_id', $doId)
                ->first();

            if (!$meta) {
                return [];
            }

            return (array) $meta;
        } catch (\Exception $e) {
            // Table may not exist in some installations
            return [];
        }
    }

    /**
     * Get the absolute file path for a digital object.
     *
     * @param int $doId Digital object ID
     *
     * @return string|null Full absolute file path or null
     */
    public static function getFilePath(int $doId): ?string
    {
        $row = DB::table('digital_object')
            ->where('id', $doId)
            ->select('path', 'name')
            ->first();

        if (!$row || empty($row->name)) {
            return null;
        }

        $webDir = \sfConfig::get('sf_web_dir', '');

        return rtrim($webDir, '/') . '/' . ltrim($row->path, '/') . $row->name;
    }

    /**
     * Get the web-accessible URL path for a digital object.
     *
     * @param int $doId Digital object ID
     *
     * @return string|null URL path (relative to web root) or null
     */
    public static function getWebPath(int $doId): ?string
    {
        $row = DB::table('digital_object')
            ->where('id', $doId)
            ->select('path', 'name')
            ->first();

        if (!$row || empty($row->name)) {
            return null;
        }

        return '/' . ltrim($row->path, '/') . $row->name;
    }

    /**
     * Get the information object ID that owns a digital object.
     *
     * For derivatives, walks up to the master's object_id.
     *
     * @param int $doId Digital object ID
     *
     * @return int|null Information object ID or null
     */
    public static function getInformationObjectId(int $doId): ?int
    {
        $row = DB::table('digital_object')
            ->where('id', $doId)
            ->select('object_id', 'parent_id')
            ->first();

        if (!$row) {
            return null;
        }

        // If this is a derivative, get the master's object_id
        if ($row->parent_id) {
            $master = DB::table('digital_object')
                ->where('id', $row->parent_id)
                ->value('object_id');

            return $master ? (int) $master : null;
        }

        return (int) $row->object_id;
    }

    /**
     * Get the slug for an information object by its ID.
     *
     * @param int $ioId Information object ID
     *
     * @return string|null Slug or null
     */
    public static function getIoSlug(int $ioId): ?string
    {
        return DB::table('slug')
            ->where('object_id', $ioId)
            ->value('slug');
    }

    /**
     * Get property values for a digital object.
     *
     * Reads displayAsCompound and digitalObjectAltText from the property table.
     *
     * @param int    $doId    Digital object ID
     * @param string $culture Culture code
     *
     * @return array ['displayAsCompound' => bool, 'altText' => string]
     */
    public static function getProperties(int $doId, string $culture = 'en'): array
    {
        $result = [
            'displayAsCompound' => false,
            'altText' => '',
        ];

        $props = DB::table('property')
            ->where('object_id', $doId)
            ->whereIn('name', ['displayAsCompound', 'digitalObjectAltText'])
            ->get();

        foreach ($props as $prop) {
            $value = DB::table('property_i18n')
                ->where('id', $prop->id)
                ->where('culture', $culture)
                ->value('value');

            if (null === $value) {
                // Fall back to source culture
                $value = DB::table('property_i18n')
                    ->where('id', $prop->id)
                    ->orderBy('culture')
                    ->value('value');
            }

            if ('displayAsCompound' === $prop->name) {
                $result['displayAsCompound'] = (bool) $value;
            } elseif ('digitalObjectAltText' === $prop->name) {
                $result['altText'] = $value ?? '';
            }
        }

        return $result;
    }

    /**
     * Update editable metadata properties for a digital object.
     *
     * Uses Laravel QB to update property table values.
     *
     * @param int    $doId    Digital object ID
     * @param array  $data    ['altText' => string, 'displayAsCompound' => bool]
     * @param string $culture Culture code
     */
    public static function updateProperties(int $doId, array $data, string $culture = 'en'): void
    {
        $propertyMap = [
            'altText' => 'digitalObjectAltText',
            'displayAsCompound' => 'displayAsCompound',
        ];

        foreach ($propertyMap as $dataKey => $propName) {
            if (!array_key_exists($dataKey, $data)) {
                continue;
            }

            $value = $data[$dataKey];
            if ('displayAsCompound' === $propName) {
                $value = $value ? '1' : '0';
            }

            // Find existing property
            $existing = DB::table('property')
                ->where('object_id', $doId)
                ->where('name', $propName)
                ->first();

            if ($existing) {
                // Update or insert i18n row
                $existsI18n = DB::table('property_i18n')
                    ->where('id', $existing->id)
                    ->where('culture', $culture)
                    ->exists();

                if ($existsI18n) {
                    DB::table('property_i18n')
                        ->where('id', $existing->id)
                        ->where('culture', $culture)
                        ->update(['value' => $value]);
                } else {
                    DB::table('property_i18n')->insert([
                        'id' => $existing->id,
                        'culture' => $culture,
                        'value' => $value,
                    ]);
                }
            } else {
                // Create new property + i18n
                $propId = DB::table('property')->insertGetId([
                    'object_id' => $doId,
                    'name' => $propName,
                    'source_culture' => $culture,
                    'serial_number' => 0,
                ]);

                DB::table('property_i18n')->insert([
                    'id' => $propId,
                    'culture' => $culture,
                    'value' => $value,
                ]);
            }
        }
    }

    /**
     * Get media type choices (taxonomy 46).
     *
     * @param string $culture Culture code
     *
     * @return array of objects with id, name
     */
    public static function getMediaTypes(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', 46)
            ->select('term.id', 'term_i18n.name')
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get a human-readable media type name.
     *
     * @param int|null $mediaTypeId Media type term ID
     * @param string   $culture     Culture code
     *
     * @return string Media type name or empty string
     */
    public static function getMediaTypeName(?int $mediaTypeId, string $culture = 'en'): string
    {
        if (!$mediaTypeId) {
            return '';
        }

        return DB::table('term_i18n')
            ->where('id', $mediaTypeId)
            ->where('culture', $culture)
            ->value('name') ?? '';
    }

    /**
     * Get a human-readable usage type name.
     *
     * @param int|null $usageId Usage term ID
     * @param string   $culture Culture code
     *
     * @return string Usage name or empty string
     */
    public static function getUsageName(?int $usageId, string $culture = 'en'): string
    {
        if (!$usageId) {
            return '';
        }

        return DB::table('term_i18n')
            ->where('id', $usageId)
            ->where('culture', $culture)
            ->value('name') ?? '';
    }

    /**
     * Delete a digital object and all its derivatives.
     *
     * Delegates to QubitDigitalObject::delete() which handles:
     * - File system cleanup (master + derivatives)
     * - Database record removal
     * - Search index update
     *
     * @param int $doId Digital object ID
     *
     * @return bool True on success
     */
    public static function delete(int $doId): bool
    {
        try {
            $do = \QubitDigitalObject::getById($doId);
            if (!$do) {
                return false;
            }

            // Get parent object for search index update
            $object = $do->object;

            $do->delete();

            // Update search index
            if ($object) {
                \QubitSearch::getInstance()->update($object);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format a database row into a structured array.
     *
     * @param object $row Database row object
     *
     * @return array Formatted digital object data
     */
    protected static function formatRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'objectId' => $row->object_id ? (int) $row->object_id : null,
            'usageId' => $row->usage_id ? (int) $row->usage_id : null,
            'mimeType' => $row->mime_type ?? '',
            'mediaTypeId' => $row->media_type_id ? (int) $row->media_type_id : null,
            'name' => $row->name ?? '',
            'path' => $row->path ?? '',
            'sequence' => $row->sequence ? (int) $row->sequence : null,
            'byteSize' => $row->byte_size ? (int) $row->byte_size : null,
            'checksum' => $row->checksum ?? '',
            'checksumType' => $row->checksum_type ?? '',
            'parentId' => $row->parent_id ? (int) $row->parent_id : null,
            'language' => $row->language ?? '',
        ];
    }

    /**
     * Format byte size for human-readable display.
     *
     * @param int|null $bytes Byte count
     *
     * @return string Formatted size (e.g. "1.5 MB")
     */
    public static function formatFileSize(?int $bytes): string
    {
        if (null === $bytes || $bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            ++$i;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Get maximum upload file size from PHP configuration.
     *
     * @return int Maximum upload size in bytes
     */
    public static function getMaxUploadSize(): int
    {
        $uploadMax = self::parseIniSize(ini_get('upload_max_filesize'));
        $postMax = self::parseIniSize(ini_get('post_max_size'));

        return min($uploadMax, $postMax);
    }

    /**
     * Parse PHP ini size value (e.g. "8M") to bytes.
     *
     * @param string $value INI size string
     *
     * @return int Size in bytes
     */
    protected static function parseIniSize(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $num = (int) $value;

        switch ($last) {
            case 'g':
                $num *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $num *= 1024 * 1024;
                break;
            case 'k':
                $num *= 1024;
                break;
        }

        return $num;
    }
}
