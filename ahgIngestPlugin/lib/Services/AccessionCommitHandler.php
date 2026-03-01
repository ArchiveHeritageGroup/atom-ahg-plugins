<?php

namespace AhgIngestPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Creates accession records from ingest rows.
 *
 * Follows AtoM entity chain: object → accession → accession_i18n
 * Plus optional: donor (actor → donor → contact_information), accession events,
 * physical objects, and accession_v2 extended fields.
 */
class AccessionCommitHandler
{
    /**
     * Create an accession record from enriched ingest row data.
     *
     * @return array{accession_id: int, do_id: int|null}
     */
    public function createAccession(array $data, object $session): array
    {
        $culture = $data['culture'] ?? 'en';
        $now = date('Y-m-d H:i:s');

        // 1. Create base object row
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitAccession',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Resolve taxonomy term IDs
        $acquisitionTypeId = $this->resolveTermId($data['acquisitionType'] ?? null, 'Acquisition type');
        $resourceTypeId = $this->resolveTermId($data['resourceType'] ?? null, 'Resource type');
        $processingStatusId = $this->resolveTermId($data['processingStatus'] ?? null, 'Processing status');
        $processingPriorityId = $this->resolveTermId($data['processingPriority'] ?? null, 'Processing priority');

        // 3. Create accession row
        $acquisitionDate = null;
        if (!empty($data['acquisitionDate'])) {
            $ts = strtotime($data['acquisitionDate']);
            $acquisitionDate = $ts ? date('Y-m-d', $ts) : null;
        }

        DB::table('accession')->insert([
            'id' => $objectId,
            'identifier' => $data['accessionNumber'] ?? null,
            'date' => $acquisitionDate,
            'acquisition_type_id' => $acquisitionTypeId,
            'resource_type_id' => $resourceTypeId,
            'processing_status_id' => $processingStatusId,
            'processing_priority_id' => $processingPriorityId,
            'source_culture' => $culture,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 4. Create accession_i18n row
        DB::table('accession_i18n')->insert([
            'id' => $objectId,
            'culture' => $culture,
            'title' => $data['title'] ?? null,
            'scope_and_content' => $data['scopeAndContent'] ?? null,
            'appraisal' => $data['appraisal'] ?? null,
            'archival_history' => $data['archivalHistory'] ?? null,
            'location_information' => $data['locationInformation'] ?? null,
            'processing_notes' => $data['processingNotes'] ?? null,
            'received_extent_units' => $data['receivedExtentUnits'] ?? null,
            'source_of_acquisition' => $data['sourceOfAcquisition'] ?? null,
            'physical_characteristics' => $data['physicalCharacteristics'] ?? null,
        ]);

        // 5. Create slug
        $slug = $this->generateSlug($data['accessionNumber'] ?? $data['title'] ?? 'accession-' . $objectId);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
        ]);

        // 6. Create donor if donor fields present
        if (!empty($data['donorName'])) {
            $this->createDonorForAccession($objectId, $data, $culture);
        }

        // 7. Create accession events if event fields present
        if (!empty($data['accessionEventTypes'])) {
            $this->createAccessionEvents($objectId, $data, $culture);
        }

        // 8. Create physical objects if present
        if (!empty($data['physicalObjectName']) || !empty($data['physicalObjectLocation'])) {
            $this->createPhysicalObject($objectId, $data, $culture);
        }

        // 9. Populate accession_v2 extended fields if table exists
        $this->populateExtendedFields($objectId, $data);

        // 10. Alternative identifiers
        if (!empty($data['alternativeIdentifiers'])) {
            $this->createAlternativeIdentifiers($objectId, $data);
        }

        return ['accession_id' => $objectId, 'do_id' => null];
    }

    /**
     * Resolve a taxonomy term name to its ID. Creates the term if not found.
     */
    protected function resolveTermId(?string $termName, string $taxonomyName): ?int
    {
        if (empty($termName)) {
            return null;
        }

        $termName = trim($termName);

        // Find taxonomy
        $taxonomy = DB::table('taxonomy')
            ->join('taxonomy_i18n', 'taxonomy.id', '=', 'taxonomy_i18n.id')
            ->where('taxonomy_i18n.name', $taxonomyName)
            ->first();

        if (!$taxonomy) {
            return null;
        }

        // Find existing term
        $term = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', $taxonomy->id)
            ->where('term_i18n.name', $termName)
            ->first();

        if ($term) {
            return $term->id;
        }

        // Create new term via Propel for proper nested-set handling
        try {
            $newTerm = new \QubitTerm();
            $newTerm->taxonomyId = $taxonomy->id;
            $newTerm->parentId = \QubitTerm::ROOT_ID ?? 110;
            $newTerm->name = $termName;
            $newTerm->sourceCulture = 'en';
            $newTerm->save();

            return $newTerm->id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Create a donor (actor + donor + contact_information) linked to an accession.
     */
    protected function createDonorForAccession(int $accessionId, array $data, string $culture): void
    {
        $now = date('Y-m-d H:i:s');
        $donorName = trim($data['donorName']);

        // Check if donor already exists by name
        $existingActor = DB::table('actor')
            ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
            ->join('donor', 'donor.id', '=', 'actor.id')
            ->where('actor_i18n.authorized_form_of_name', $donorName)
            ->first();

        if ($existingActor) {
            $donorId = $existingActor->id;
        } else {
            // Create object for actor
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitDonor',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Create actor row
            DB::table('actor')->insert([
                'id' => $objectId,
                'entity_type_id' => null,
                'source_culture' => $culture,
            ]);

            // Create actor_i18n
            DB::table('actor_i18n')->insert([
                'id' => $objectId,
                'culture' => $culture,
                'authorized_form_of_name' => $donorName,
            ]);

            // Create donor row
            DB::table('donor')->insert([
                'id' => $objectId,
            ]);

            // Create slug for donor
            $slug = $this->generateSlug($donorName);
            DB::table('slug')->insert([
                'object_id' => $objectId,
                'slug' => $slug,
            ]);

            $donorId = $objectId;

            // Create contact information if any donor contact fields present
            $hasContact = !empty($data['donorStreetAddress']) || !empty($data['donorCity'])
                || !empty($data['donorEmail']) || !empty($data['donorTelephone'])
                || !empty($data['donorPostalCode']) || !empty($data['donorCountry']);

            if ($hasContact) {
                DB::table('contact_information')->insert([
                    'actor_id' => $donorId,
                    'contact_type' => 'primary',
                    'primary_contact' => 1,
                    'contact_person' => $data['donorContactPerson'] ?? null,
                    'street_address' => $data['donorStreetAddress'] ?? null,
                    'email' => $data['donorEmail'] ?? null,
                    'telephone' => $data['donorTelephone'] ?? null,
                    'fax' => $data['donorFax'] ?? null,
                    'postal_code' => $data['donorPostalCode'] ?? null,
                    'country_code' => $data['donorCountry'] ?? null,
                    'source_culture' => $culture,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // contact_information_i18n for city/region
                $contactId = DB::table('contact_information')
                    ->where('actor_id', $donorId)
                    ->orderByDesc('id')
                    ->value('id');

                if ($contactId) {
                    DB::table('contact_information_i18n')->insert([
                        'id' => $contactId,
                        'culture' => $culture,
                        'city' => $data['donorCity'] ?? null,
                        'region' => $data['donorRegion'] ?? null,
                        'note' => $data['donorNote'] ?? null,
                    ]);
                }
            }
        }

        // Link donor to accession via relation
        try {
            $relation = new \QubitRelation();
            $relation->subjectId = $donorId;
            $relation->objectId = $accessionId;
            $relation->typeId = \QubitTerm::DONOR_ID ?? 334;
            $relation->save();
        } catch (\Throwable $e) {
            // Non-fatal — donor still exists
        }
    }

    /**
     * Create accession events from pipe-delimited CSV fields.
     */
    protected function createAccessionEvents(int $accessionId, array $data, string $culture): void
    {
        $types = array_map('trim', explode('|', $data['accessionEventTypes'] ?? ''));
        $dates = !empty($data['accessionEventDates']) ? array_map('trim', explode('|', $data['accessionEventDates'])) : [];
        $agents = !empty($data['accessionEventAgents']) ? array_map('trim', explode('|', $data['accessionEventAgents'])) : [];
        $notes = !empty($data['accessionEventNotes']) ? array_map('trim', explode('|', $data['accessionEventNotes'])) : [];

        foreach ($types as $i => $typeName) {
            if (empty($typeName)) {
                continue;
            }

            $typeId = $this->resolveTermId($typeName, 'Accession event type');

            $eventDate = null;
            if (!empty($dates[$i])) {
                $ts = strtotime($dates[$i]);
                $eventDate = $ts ? date('Y-m-d', $ts) : null;
            }

            // Create base object
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitAccessionEvent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('accession_event')->insert([
                'id' => $objectId,
                'type_id' => $typeId,
                'accession_id' => $accessionId,
                'date' => $eventDate,
                'source_culture' => $culture,
            ]);

            // Agent info in i18n
            $agent = $agents[$i] ?? null;
            DB::table('accession_event_i18n')->insert([
                'id' => $objectId,
                'culture' => $culture,
                'agent' => $agent,
            ]);
        }
    }

    /**
     * Create a physical object linked to the accession.
     */
    protected function createPhysicalObject(int $accessionId, array $data, string $culture): void
    {
        $now = date('Y-m-d H:i:s');

        // Resolve physical object type
        $typeId = null;
        if (!empty($data['physicalObjectType'])) {
            $typeId = $this->resolveTermId($data['physicalObjectType'], 'Physical object type');
        }

        // Create object
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitPhysicalObject',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create physical_object
        DB::table('physical_object')->insert([
            'id' => $objectId,
            'type_id' => $typeId,
            'source_culture' => $culture,
        ]);

        // Create physical_object_i18n
        DB::table('physical_object_i18n')->insert([
            'id' => $objectId,
            'culture' => $culture,
            'name' => $data['physicalObjectName'] ?? null,
            'location' => $data['physicalObjectLocation'] ?? null,
        ]);

        // Create slug
        $slug = $this->generateSlug($data['physicalObjectName'] ?? 'physical-object-' . $objectId);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
        ]);

        // Link to accession via relation
        try {
            $relation = new \QubitRelation();
            $relation->subjectId = $objectId;
            $relation->objectId = $accessionId;
            $relation->typeId = \QubitTerm::HAS_PHYSICAL_OBJECT_ID ?? 335;
            $relation->save();
        } catch (\Throwable $e) {
            // Non-fatal
        }
    }

    /**
     * Populate accession_v2 extended fields (ahgAccessionManagePlugin).
     */
    protected function populateExtendedFields(int $accessionId, array $data): void
    {
        // Check if accession_v2 table exists
        try {
            $exists = DB::select("SHOW TABLES LIKE 'accession_v2'");
            if (empty($exists)) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $extendedData = [];

        if (!empty($data['intakeNotes'])) {
            $extendedData['intake_notes'] = $data['intakeNotes'];
        }
        if (!empty($data['intakePriority'])) {
            $extendedData['priority'] = strtolower($data['intakePriority']);
        }

        if (!empty($extendedData)) {
            // Check if row already exists
            $existing = DB::table('accession_v2')->where('accession_id', $accessionId)->exists();
            if ($existing) {
                DB::table('accession_v2')->where('accession_id', $accessionId)->update($extendedData);
            } else {
                $extendedData['accession_id'] = $accessionId;
                $extendedData['status'] = 'draft';
                $extendedData['created_at'] = date('Y-m-d H:i:s');
                $extendedData['updated_at'] = date('Y-m-d H:i:s');
                DB::table('accession_v2')->insert($extendedData);
            }
        }

        // Container info
        if (!empty($data['containerType']) || !empty($data['containerLabel']) || !empty($data['containerBarcode'])) {
            try {
                $exists = DB::select("SHOW TABLES LIKE 'accession_container'");
                if (!empty($exists)) {
                    DB::table('accession_container')->insert([
                        'accession_id' => $accessionId,
                        'container_type' => $data['containerType'] ?? null,
                        'label' => $data['containerLabel'] ?? null,
                        'barcode' => $data['containerBarcode'] ?? null,
                        'quantity' => !empty($data['containerQuantity']) ? (int) $data['containerQuantity'] : null,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Throwable $e) {
                // Table may not exist — non-fatal
            }
        }
    }

    /**
     * Create alternative identifiers for an accession.
     */
    protected function createAlternativeIdentifiers(int $accessionId, array $data): void
    {
        $identifiers = array_map('trim', explode('|', $data['alternativeIdentifiers']));
        $types = !empty($data['alternativeIdentifierTypes']) ? array_map('trim', explode('|', $data['alternativeIdentifierTypes'])) : [];
        $notes = !empty($data['alternativeIdentifierNotes']) ? array_map('trim', explode('|', $data['alternativeIdentifierNotes'])) : [];

        foreach ($identifiers as $i => $identifier) {
            if (empty($identifier)) {
                continue;
            }

            try {
                DB::table('other_name')->insert([
                    'object_id' => $accessionId,
                    'type_id' => \QubitTerm::ALTERNATIVE_IDENTIFIER_ID ?? 174,
                    'source_culture' => 'en',
                ]);

                $nameId = DB::table('other_name')
                    ->where('object_id', $accessionId)
                    ->orderByDesc('id')
                    ->value('id');

                if ($nameId) {
                    DB::table('other_name_i18n')->insert([
                        'id' => $nameId,
                        'culture' => 'en',
                        'name' => $identifier,
                        'note' => $notes[$i] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }
    }

    /**
     * Generate a unique slug from a string.
     */
    protected function generateSlug(string $text): string
    {
        // Transliterate and normalize
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'accession-' . time();
        }

        // Ensure uniqueness
        $baseSlug = substr($slug, 0, 200);
        $candidate = $baseSlug;
        $counter = 1;

        while (DB::table('slug')->where('slug', $candidate)->exists()) {
            $candidate = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
