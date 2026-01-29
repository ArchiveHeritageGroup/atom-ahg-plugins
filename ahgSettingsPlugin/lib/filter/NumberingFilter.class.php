<?php

/**
 * Numbering Filter
 *
 * Hooks into information object save to auto-assign identifiers.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class NumberingFilter
{
    /**
     * Hook into QubitInformationObject pre-save.
     * Call this from the save action or via event dispatcher.
     */
    public static function assignIdentifier(QubitInformationObject $object): void
    {
        // Skip if identifier already set
        if (!empty($object->identifier)) {
            return;
        }

        // Skip if not a new record
        if ($object->id) {
            return;
        }

        // Determine sector from display standard
        $sector = self::getSectorFromObject($object);
        if (!$sector) {
            return;
        }

        // Build context
        $context = self::buildContext($object);

        try {
            $service = \AtomExtensions\Services\NumberingService::getInstance();

            // Check if auto-generation is enabled
            if (!$service->isAutoGenerateEnabled($sector, $object->repository_id)) {
                return;
            }

            // Generate identifier
            $identifier = $service->getNextReference($sector, $context, $object->repository_id);

            if ($identifier) {
                $object->identifier = $identifier;
            }
        } catch (\Exception $e) {
            // Log error but don't block save
            error_log('NumberingFilter error: ' . $e->getMessage());
        }
    }

    /**
     * Determine sector from information object.
     */
    private static function getSectorFromObject(QubitInformationObject $object): ?string
    {
        // Check display standard
        if ($object->displayStandardId) {
            $term = QubitTerm::getById($object->displayStandardId);
            if ($term) {
                $name = strtolower($term->getName(['sourceCulture' => true]));

                $mapping = [
                    'isad' => 'archive',
                    'isad(g)' => 'archive',
                    'rad' => 'archive',
                    'dacs' => 'archive',
                    'dc' => 'archive',
                    'mods' => 'archive',
                    'museum' => 'museum',
                    'cco' => 'museum',
                    'spectrum' => 'museum',
                    'library' => 'library',
                    'marc' => 'library',
                    'gallery' => 'gallery',
                    'dam' => 'dam',
                    'photo' => 'dam',
                ];

                if (isset($mapping[$name])) {
                    return $mapping[$name];
                }
            }
        }

        // Default to archive
        return 'archive';
    }

    /**
     * Build context for token replacement.
     */
    private static function buildContext(QubitInformationObject $object): array
    {
        $context = [];

        // Repository code
        if ($object->repository_id) {
            $repo = QubitRepository::getById($object->repository_id);
            if ($repo) {
                $context['repo'] = $repo->identifier ?? $repo->getAuthorizedFormOfName(['sourceCulture' => true]) ?? 'REPO';
            }
        }

        // Parent hierarchy for fonds/series
        if ($object->parentId && $object->parentId != QubitInformationObject::ROOT_ID) {
            $ancestors = [];
            $parent = QubitInformationObject::getById($object->parentId);

            while ($parent && $parent->id != QubitInformationObject::ROOT_ID) {
                $ancestors[] = $parent;
                $parent = $parent->parent;
            }

            // Reverse to get top-down order
            $ancestors = array_reverse($ancestors);

            // Fonds is typically first ancestor
            if (isset($ancestors[0])) {
                $context['fonds'] = $ancestors[0]->identifier ?? '';
            }

            // Series is typically second ancestor
            if (isset($ancestors[1])) {
                $context['series'] = $ancestors[1]->identifier ?? '';
            }

            // Collection
            if (isset($ancestors[0])) {
                $context['collection'] = $ancestors[0]->identifier ?? '';
            }
        }

        return $context;
    }

    /**
     * Validate identifier before save.
     * Returns array with 'valid' bool, 'errors' array, and 'warnings' array.
     *
     * @param string   $identifier The identifier to validate
     * @param string   $sector     Sector code
     * @param int|null $excludeId  Object ID to exclude (for edits)
     * @param int|null $repositoryId Repository ID for scheme lookup
     */
    public static function validateIdentifier(string $identifier, string $sector, ?int $excludeId = null, ?int $repositoryId = null): array
    {
        try {
            $service = \AtomExtensions\Services\NumberingService::getInstance();

            return $service->validateReference($identifier, $sector, $excludeId, $repositoryId);
        } catch (\Exception $e) {
            // Log but don't block
            error_log('NumberingFilter validation error: ' . $e->getMessage());

            return [
                'valid' => true,
                'errors' => [],
                'warnings' => [],
            ];
        }
    }

    /**
     * Validate and potentially block save if identifier is duplicate.
     * Call this before saving.
     *
     * @param QubitInformationObject $object The object being saved
     *
     * @return bool True if validation passed, false if should block save
     */
    public static function validateBeforeSave(QubitInformationObject $object): bool
    {
        if (empty($object->identifier)) {
            return true; // Empty is OK, will be auto-generated
        }

        $sector = self::getSectorFromObject($object);
        $excludeId = $object->id ?: null;

        $validation = self::validateIdentifier(
            $object->identifier,
            $sector,
            $excludeId,
            $object->repository_id
        );

        // Return false if there are errors (duplicates)
        return $validation['valid'];
    }
}
