<?php

declare(strict_types=1);

namespace ahgMuseumPlugin\Adapters;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Museum Object Adapter
 *
 * This adapter provides compatibility by bridging information object
 * instances with the Laravel-based museum metadata services.
 *
 * Converted from Propel to pure Laravel Query Builder.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class PropelMuseumObjectAdapter
{
    // Term IDs
    const TERM_CREATION_ID = 111;

    private LaravelMuseumAdapter $adapter;

    public function __construct()
    {
        $this->adapter = new LaravelMuseumAdapter();
    }

    /**
     * Enrich an information object with museum metadata.
     *
     * @param int|object $object Object ID or object with id property
     * @param array $museumProperties
     */
    public function enrichInformationObject($object, array $museumProperties): void
    {
        $objectId = $this->resolveObjectId($object);

        if (!$objectId) {
            throw new \InvalidArgumentException('Information object must be saved before enriching with museum metadata');
        }

        $this->adapter->enrichWithCcoMetadata($objectId, $museumProperties);
    }

    /**
     * Get museum metadata for an information object.
     *
     * @param int|object $object Object ID or object with id property
     * @return array|null Museum metadata as array or null if none exists
     */
    public function getMuseumMetadata($object): ?array
    {
        $objectId = $this->resolveObjectId($object);

        if (!$objectId) {
            return null;
        }

        $museumObject = $this->adapter->getMuseumMetadata($objectId);

        if (!$museumObject) {
            return null;
        }

        return is_array($museumObject) ? $museumObject : (array) $museumObject;
    }

    /**
     * Check if information object has museum metadata.
     *
     * @param int|object $object Object ID or object with id property
     * @return bool
     */
    public function hasMuseumMetadata($object): bool
    {
        $objectId = $this->resolveObjectId($object);

        if (!$objectId) {
            return false;
        }

        return $this->adapter->hasMuseumMetadata($objectId);
    }

    /**
     * Delete museum metadata for an information object.
     *
     * @param int|object $object Object ID or object with id property
     * @return bool
     */
    public function deleteMuseumMetadata($object): bool
    {
        $objectId = $this->resolveObjectId($object);

        if (!$objectId) {
            return false;
        }

        return $this->adapter->deleteMuseumMetadata($objectId);
    }

    /**
     * Extract museum metadata from information object properties.
     *
     * This method extracts museum-relevant data from standard AtoM fields
     * to populate museum metadata fields.
     *
     * @param int|object $object Object ID or object with id property
     * @return array
     */
    public function extractFromInformationObject($object): array
    {
        $objectId = $this->resolveObjectId($object);

        if (!$objectId) {
            return [];
        }

        // Load information object data
        $ioData = $this->getInformationObjectData($objectId);

        if (!$ioData) {
            return [];
        }

        $properties = [];

        // Work type - try to infer from level of description or type
        if ($ioData->level_of_description_id) {
            $properties['work_type'] = $this->inferWorkType($ioData);
        }

        // Extract date information
        $dates = $this->getCreationDates($objectId);
        if (!empty($dates)) {
            if (!empty($dates['earliest'])) {
                $properties['creation_date_earliest'] = $dates['earliest'];
            }
            if (!empty($dates['latest'])) {
                $properties['creation_date_latest'] = $dates['latest'];
            }
        }

        // Extract physical characteristics
        if (!empty($ioData->extent_and_medium)) {
            $properties['measurements'] = $this->adapter->parseMeasurements($ioData->extent_and_medium);
        }

        // Extract scope and content as potential description
        if (!empty($ioData->scope_and_content)) {
            // Could be used for condition notes or inscription
            $properties['condition_notes'] = $ioData->scope_and_content;
        }

        return $properties;
    }

    /**
     * Apply museum metadata to information object fields.
     *
     * This method updates standard AtoM fields based on museum metadata.
     *
     * @param int|object $object Object ID or object with id property
     * @param array $museumMetadata
     * @return bool Success status
     */
    public function applyToInformationObject($object, array $museumMetadata): bool
    {
        $objectId = $this->resolveObjectId($object);

        if (!$objectId) {
            return false;
        }

        // Load current i18n data
        $currentI18n = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', 'en')
            ->first();

        $updates = [];

        // Update extent and medium with formatted measurements
        if (!empty($museumMetadata['measurements'])) {
            $extentStatement = $this->adapter->getExtentStatement($museumMetadata['measurements']);
            if ($extentStatement) {
                $updates['extent_and_medium'] = $extentStatement;
            }
        }

        // Build physical characteristics updates
        $physicalChars = $currentI18n->physical_characteristics ?? '';

        // Add materials to physical characteristics
        if (!empty($museumMetadata['materials'])) {
            $materialsText = 'Materials: ' . implode(', ', $museumMetadata['materials']);
            if ($physicalChars) {
                $physicalChars .= "\n\n" . $materialsText;
            } else {
                $physicalChars = $materialsText;
            }
        }

        // Add techniques to physical characteristics
        if (!empty($museumMetadata['techniques'])) {
            $techniquesText = 'Techniques: ' . implode(', ', $museumMetadata['techniques']);
            if ($physicalChars) {
                $physicalChars .= "\n" . $techniquesText;
            } else {
                $physicalChars = $techniquesText;
            }
        }

        if ($physicalChars !== ($currentI18n->physical_characteristics ?? '')) {
            $updates['physical_characteristics'] = $physicalChars;
        }

        // Apply updates if any
        if (!empty($updates)) {
            DB::table('information_object_i18n')
                ->updateOrInsert(
                    ['id' => $objectId, 'culture' => 'en'],
                    $updates
                );

            // Update object timestamp
            DB::table('object')
                ->where('id', $objectId)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);

            return true;
        }

        return false;
    }

    /**
     * Get the underlying Laravel adapter.
     *
     * @return LaravelMuseumAdapter
     */
    public function getAdapter(): LaravelMuseumAdapter
    {
        return $this->adapter;
    }

    /**
     * Resolve object ID from various input types.
     *
     * @param int|object $object Object ID or object with id property
     * @return int|null
     */
    protected function resolveObjectId($object): ?int
    {
        if (is_int($object)) {
            return $object;
        }

        if (is_object($object) && isset($object->id)) {
            return (int) $object->id;
        }

        if (is_array($object) && isset($object['id'])) {
            return (int) $object['id'];
        }

        return null;
    }

    /**
     * Get information object data with i18n.
     *
     * @param int $objectId
     * @return object|null
     */
    protected function getInformationObjectData(int $objectId): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->select(
                'io.*',
                'i18n.title',
                'i18n.scope_and_content',
                'i18n.extent_and_medium',
                'i18n.physical_characteristics'
            )
            ->first();
    }

    /**
     * Get creation dates for an information object.
     *
     * @param int $objectId
     * @return array ['earliest' => string|null, 'latest' => string|null]
     */
    protected function getCreationDates(int $objectId): array
    {
        $dates = DB::table('event')
            ->where('information_object_id', $objectId)
            ->where('type_id', self::TERM_CREATION_ID)
            ->select('start_date', 'end_date')
            ->get();

        $result = [
            'earliest' => null,
            'latest' => null,
        ];

        foreach ($dates as $date) {
            if ($date->start_date) {
                if (!$result['earliest'] || $date->start_date < $result['earliest']) {
                    $result['earliest'] = $date->start_date;
                }
            }
            if ($date->end_date) {
                if (!$result['latest'] || $date->end_date > $result['latest']) {
                    $result['latest'] = $date->end_date;
                }
            }
        }

        return $result;
    }

    /**
     * Infer work type from information object properties.
     *
     * @param object $ioData Information object data
     * @return string
     */
    protected function inferWorkType(object $ioData): string
    {
        // Try to infer from title and scope/content
        $text = strtolower(($ioData->title ?? '') . ' ' . ($ioData->scope_and_content ?? ''));

        $visualKeywords = ['painting', 'photograph', 'drawing', 'print', 'sculpture'];
        $builtKeywords = ['building', 'architecture', 'monument', 'structure'];
        $movableKeywords = ['object', 'artifact', 'tool', 'vessel', 'textile'];

        foreach ($visualKeywords as $keyword) {
            if (false !== strpos($text, $keyword)) {
                return 'visual_works';
            }
        }

        foreach ($builtKeywords as $keyword) {
            if (false !== strpos($text, $keyword)) {
                return 'built_works';
            }
        }

        foreach ($movableKeywords as $keyword) {
            if (false !== strpos($text, $keyword)) {
                return 'movable_works';
            }
        }

        // Default to visual works
        return 'visual_works';
    }

    /**
     * Sync information object with museum metadata.
     *
     * Bidirectional sync that extracts from IO fields and applies museum metadata.
     *
     * @param int $objectId
     * @param array $museumMetadata Optional museum metadata to apply
     * @return array Extracted and merged metadata
     */
    public function syncInformationObject(int $objectId, array $museumMetadata = []): array
    {
        // Extract current data from information object
        $extracted = $this->extractFromInformationObject($objectId);

        // Merge with provided museum metadata (provided takes precedence)
        $merged = array_merge($extracted, $museumMetadata);

        // Apply back to information object
        if (!empty($merged)) {
            $this->applyToInformationObject($objectId, $merged);
        }

        // Enrich with museum metadata
        if (!empty($merged)) {
            $this->enrichInformationObject($objectId, $merged);
        }

        return $merged;
    }

    /**
     * Get level of description name.
     *
     * @param int|null $termId
     * @return string|null
     */
    protected function getLevelOfDescriptionName(?int $termId): ?string
    {
        if (!$termId) {
            return null;
        }

        $term = DB::table('term_i18n')
            ->where('id', $termId)
            ->where('culture', 'en')
            ->first();

        return $term ? $term->name : null;
    }

    /**
     * Create or update event date.
     *
     * @param int $objectId Information object ID
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @param int $typeId Event type term ID
     * @return int Event ID
     */
    public function setEventDate(int $objectId, ?string $startDate, ?string $endDate, int $typeId = self::TERM_CREATION_ID): int
    {
        // Check for existing event
        $existing = DB::table('event')
            ->where('information_object_id', $objectId)
            ->where('type_id', $typeId)
            ->first();

        if ($existing) {
            // Update existing
            DB::table('event')
                ->where('id', $existing->id)
                ->update([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);

            return $existing->id;
        }

        // Create new event
        $eventId = DB::table('object')->insertGetId([
            'class_name' => 'QubitEvent',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('event')->insert([
            'id' => $eventId,
            'information_object_id' => $objectId,
            'type_id' => $typeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $eventId;
    }

    /**
     * Batch process multiple information objects.
     *
     * @param array $objectIds Array of information object IDs
     * @param callable|null $progressCallback Optional callback for progress updates
     * @return array Results summary
     */
    public function batchProcess(array $objectIds, ?callable $progressCallback = null): array
    {
        $results = [
            'total' => count($objectIds),
            'processed' => 0,
            'enriched' => 0,
            'errors' => 0,
        ];

        foreach ($objectIds as $index => $objectId) {
            try {
                $extracted = $this->extractFromInformationObject($objectId);

                if (!empty($extracted)) {
                    $this->enrichInformationObject($objectId, $extracted);
                    $results['enriched']++;
                }

                $results['processed']++;

                if ($progressCallback) {
                    $progressCallback($index + 1, $results['total'], $objectId);
                }
            } catch (\Exception $e) {
                $results['errors']++;
                $results['error_details'][$objectId] = $e->getMessage();
            }
        }

        return $results;
    }
}