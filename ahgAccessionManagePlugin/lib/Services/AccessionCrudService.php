<?php

namespace AhgAccessionManage\Services;

use AhgCore\Services\I18nService;
use AhgCore\Services\ObjectService;
use AhgCore\Services\RelationService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Accession CRUD Service
 *
 * Pure Laravel Query Builder implementation for Accession entity operations.
 * Accessions follow: object -> accession (NOT actor-based).
 * Sub-tables: accession_event, deaccession, donor relations.
 */
class AccessionCrudService
{
    /**
     * Get an accession by ID with all related data.
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        $row = DB::table('accession')
            ->join('object', 'accession.id', '=', 'object.id')
            ->where('accession.id', $id)
            ->where('object.class_name', 'QubitAccession')
            ->select('accession.*', 'object.serial_number')
            ->first();

        if (!$row) {
            return null;
        }

        $i18n = I18nService::getWithFallback('accession_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);

        $result = [
            'id' => $id,
            'slug' => $slug,
            'identifier' => $row->identifier,
            'date' => $row->date,
            'acquisitionTypeId' => $row->acquisition_type_id,
            'processingPriorityId' => $row->processing_priority_id,
            'processingStatusId' => $row->processing_status_id,
            'resourceTypeId' => $row->resource_type_id,
            'sourceCulture' => $row->source_culture,
            'createdAt' => $row->created_at,
            'updatedAt' => $row->updated_at,
            'serialNumber' => $row->serial_number,
            'title' => $i18n->title ?? '',
            'appraisal' => $i18n->appraisal ?? '',
            'archivalHistory' => $i18n->archival_history ?? '',
            'locationInformation' => $i18n->location_information ?? '',
            'physicalCharacteristics' => $i18n->physical_characteristics ?? '',
            'processingNotes' => $i18n->processing_notes ?? '',
            'receivedExtentUnits' => $i18n->received_extent_units ?? '',
            'scopeAndContent' => $i18n->scope_and_content ?? '',
            'sourceOfAcquisition' => $i18n->source_of_acquisition ?? '',
        ];

        // Join V2 extension data if available
        try {
            $v2 = DB::table('accession_v2')->where('accession_id', $id)->first();
            if ($v2) {
                $result['v2'] = [
                    'status' => $v2->status,
                    'priority' => $v2->priority,
                    'assignedTo' => $v2->assigned_to,
                    'submittedAt' => $v2->submitted_at,
                    'acceptedAt' => $v2->accepted_at,
                    'rejectedAt' => $v2->rejected_at,
                    'rejectionReason' => $v2->rejection_reason,
                    'intakeNotes' => $v2->intake_notes,
                    'donorAgreementId' => $v2->donor_agreement_id,
                ];
            }
        } catch (\Exception $e) {
            // accession_v2 table may not exist yet
        }

        return $result;
    }

    /**
     * Get an accession by slug.
     */
    public static function getBySlug(string $slug, string $culture = 'en'): ?array
    {
        $objectId = ObjectService::resolveSlug($slug);
        if (!$objectId) {
            return null;
        }

        return self::getById($objectId, $culture);
    }

    /**
     * Create a new accession.
     *
     * @return int The new accession ID
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            $now = date('Y-m-d H:i:s');

            // 1. Create object record
            $id = ObjectService::create('QubitAccession');

            // 2. Generate slug
            ObjectService::generateSlug($id, $data['identifier'] ?? $data['title'] ?? null);

            // 3. Create accession record
            DB::table('accession')->insert([
                'id' => $id,
                'identifier' => $data['identifier'] ?? null,
                'date' => $data['date'] ?? null,
                'acquisition_type_id' => $data['acquisitionTypeId'] ?? null,
                'processing_priority_id' => $data['processingPriorityId'] ?? null,
                'processing_status_id' => $data['processingStatusId'] ?? null,
                'resource_type_id' => $data['resourceTypeId'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
                'source_culture' => $culture,
            ]);

            // 4. Create accession_i18n record
            $i18nData = self::extractI18nData($data);
            if (!empty($i18nData)) {
                I18nService::save('accession_i18n', $id, $culture, $i18nData);
            }

            // 5. Create accession_v2 record
            try {
                $intakeService = new AccessionIntakeService($data['tenantId'] ?? null);
                $intakeService->ensureV2Record($id);

                // Apply default checklist template
                $containerService = new AccessionContainerService($data['tenantId'] ?? null);
                $templateId = $containerService->getConfig('intake_checklist_template_id', '1');
                if ($templateId) {
                    $intakeService->applyChecklistTemplate($id, (int) $templateId);
                }

                // Add timeline event
                $userId = $data['userId'] ?? null;
                $intakeService->addTimelineEvent($id, AccessionIntakeService::EVENT_CREATED, $userId, 'Accession created');

                // Auto-generate accession number if enabled
                $autoNumber = $containerService->getConfig('auto_assign_enabled', '0');
                if ($autoNumber === '1' && empty($data['identifier'])) {
                    $repositoryId = $data['repositoryId'] ?? null;
                    $number = $containerService->generateNextNumber($repositoryId);
                    DB::table('accession')->where('id', $id)->update(['identifier' => $number]);
                    ObjectService::generateSlug($id, $number);
                }
            } catch (\Exception $e) {
                // V2 tables may not be installed yet — graceful degradation
            }

            return $id;
        });
    }

    /**
     * Update an existing accession.
     */
    public static function update(int $id, array $data, string $culture = 'en'): void
    {
        DB::transaction(function () use ($id, $data, $culture) {
            $now = date('Y-m-d H:i:s');

            // 1. Update accession record if needed
            $accUpdate = [];
            if (array_key_exists('identifier', $data)) {
                $accUpdate['identifier'] = $data['identifier'];
            }
            if (array_key_exists('date', $data)) {
                $accUpdate['date'] = $data['date'];
            }
            if (array_key_exists('acquisitionTypeId', $data)) {
                $accUpdate['acquisition_type_id'] = $data['acquisitionTypeId'];
            }
            if (array_key_exists('processingPriorityId', $data)) {
                $accUpdate['processing_priority_id'] = $data['processingPriorityId'];
            }
            if (array_key_exists('processingStatusId', $data)) {
                $accUpdate['processing_status_id'] = $data['processingStatusId'];
            }
            if (array_key_exists('resourceTypeId', $data)) {
                $accUpdate['resource_type_id'] = $data['resourceTypeId'];
            }
            if (!empty($accUpdate)) {
                $accUpdate['updated_at'] = $now;
                DB::table('accession')->where('id', $id)->update($accUpdate);
            }

            // 2. Update accession_i18n
            $i18nData = self::extractI18nData($data);
            if (!empty($i18nData)) {
                I18nService::save('accession_i18n', $id, $culture, $i18nData);
            }

            // 3. Touch the object record
            ObjectService::touch($id);
            ObjectService::incrementSerialNumber($id);

            // 4. Log timeline event for V2
            try {
                $intakeService = new AccessionIntakeService($data['tenantId'] ?? null);
                $userId = $data['userId'] ?? null;
                $intakeService->addTimelineEvent($id, AccessionIntakeService::EVENT_NOTE, $userId, 'Accession updated');
            } catch (\Exception $e) {
                // V2 tables may not be installed yet
            }
        });
    }

    /**
     * Delete an accession and all related data.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // 0. Delete V2 data (intake, appraisal, containers, rights)
            try {
                $intakeService = new AccessionIntakeService();
                $intakeService->deleteAllForAccession($id);

                $appraisalService = new AccessionAppraisalService();
                $appraisalService->deleteAllForAccession($id);

                $containerService = new AccessionContainerService();
                $containerService->deleteAllForAccession($id);
            } catch (\Exception $e) {
                // V2 tables may not be installed yet
            }

            // 1. Delete accession events
            $eventIds = DB::table('accession_event')
                ->where('accession_id', $id)
                ->pluck('id')
                ->all();
            foreach ($eventIds as $eventId) {
                I18nService::delete('accession_event_i18n', $eventId);
            }
            DB::table('accession_event')->where('accession_id', $id)->delete();

            // 2. Delete deaccessions
            $deaccIds = DB::table('deaccession')
                ->where('accession_id', $id)
                ->pluck('id')
                ->all();
            foreach ($deaccIds as $deaccId) {
                I18nService::delete('deaccession_i18n', $deaccId);
            }
            DB::table('deaccession')->where('accession_id', $id)->delete();

            // 3. Delete relations (donor links etc.)
            RelationService::deleteBySubjectOrObject($id);

            // 4. Delete accession_i18n
            I18nService::delete('accession_i18n', $id);

            // 5. Delete accession record
            DB::table('accession')->where('id', $id)->delete();

            // 6. Delete slug + object
            ObjectService::deleteObject($id);
        });
    }

    /**
     * Get donors linked to an accession (via relation table, type = DONOR_ID).
     */
    public static function getDonors(int $id, string $culture = 'en'): array
    {
        return DB::table('relation')
            ->join('actor', 'relation.object_id', '=', 'actor.id')
            ->leftJoin('actor_i18n', function ($j) use ($culture) {
                $j->on('actor.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'actor.id', '=', 'slug.object_id')
            ->where('relation.subject_id', $id)
            ->where('relation.type_id', \QubitTerm::DONOR_ID)
            ->select(
                'actor.id',
                'actor_i18n.authorized_form_of_name as name',
                'slug.slug'
            )
            ->get()
            ->all();
    }

    /**
     * Get accession events for an accession.
     */
    public static function getAccessionEvents(int $id, string $culture = 'en'): array
    {
        return DB::table('accession_event')
            ->leftJoin('accession_event_i18n', function ($j) use ($culture) {
                $j->on('accession_event.id', '=', 'accession_event_i18n.id')
                    ->where('accession_event_i18n.culture', '=', $culture);
            })
            ->where('accession_event.accession_id', $id)
            ->select(
                'accession_event.id',
                'accession_event.type_id',
                'accession_event.date',
                'accession_event_i18n.agent'
            )
            ->get()
            ->all();
    }

    /**
     * Save (create or update) an accession event.
     *
     * @return int The accession event ID
     */
    public static function saveAccessionEvent(int $accessionId, array $data, string $culture, ?int $eventId = null): int
    {
        return DB::transaction(function () use ($accessionId, $data, $culture, $eventId) {
            if ($eventId) {
                // Update existing
                $update = [];
                if (array_key_exists('typeId', $data)) {
                    $update['type_id'] = $data['typeId'];
                }
                if (array_key_exists('date', $data)) {
                    $update['date'] = $data['date'];
                }
                if (!empty($update)) {
                    DB::table('accession_event')->where('id', $eventId)->update($update);
                }
            } else {
                // Create new
                $eventId = DB::table('accession_event')->insertGetId([
                    'accession_id' => $accessionId,
                    'type_id' => $data['typeId'] ?? null,
                    'date' => $data['date'] ?? null,
                    'source_culture' => $culture,
                ]);
            }

            // Save i18n (agent field)
            $i18nData = [];
            if (isset($data['agent'])) {
                $i18nData['agent'] = $data['agent'];
            }
            if (!empty($i18nData)) {
                I18nService::save('accession_event_i18n', $eventId, $culture, $i18nData);
            }

            return $eventId;
        });
    }

    /**
     * Delete an accession event.
     */
    public static function deleteAccessionEvent(int $eventId): void
    {
        I18nService::delete('accession_event_i18n', $eventId);
        DB::table('accession_event')->where('id', $eventId)->delete();
    }

    /**
     * Get deaccessions for an accession.
     */
    public static function getDeaccessions(int $id, string $culture = 'en'): array
    {
        return DB::table('deaccession')
            ->leftJoin('deaccession_i18n', function ($j) use ($culture) {
                $j->on('deaccession.id', '=', 'deaccession_i18n.id')
                    ->where('deaccession_i18n.culture', '=', $culture);
            })
            ->where('deaccession.accession_id', $id)
            ->select(
                'deaccession.id',
                'deaccession.date',
                'deaccession.identifier',
                'deaccession.scope_id',
                'deaccession.created_at',
                'deaccession.updated_at',
                'deaccession_i18n.description',
                'deaccession_i18n.extent',
                'deaccession_i18n.reason'
            )
            ->get()
            ->all();
    }

    /**
     * Save (create or update) a deaccession.
     *
     * @return int The deaccession ID
     */
    public static function saveDeaccession(int $accessionId, array $data, string $culture, ?int $id = null): int
    {
        return DB::transaction(function () use ($accessionId, $data, $culture, $id) {
            $now = date('Y-m-d H:i:s');

            if ($id) {
                // Update existing
                $update = [];
                if (array_key_exists('date', $data)) {
                    $update['date'] = $data['date'];
                }
                if (array_key_exists('identifier', $data)) {
                    $update['identifier'] = $data['identifier'];
                }
                if (array_key_exists('scopeId', $data)) {
                    $update['scope_id'] = $data['scopeId'];
                }
                if (!empty($update)) {
                    $update['updated_at'] = $now;
                    DB::table('deaccession')->where('id', $id)->update($update);
                }
            } else {
                // Create new
                $id = DB::table('deaccession')->insertGetId([
                    'accession_id' => $accessionId,
                    'date' => $data['date'] ?? null,
                    'identifier' => $data['identifier'] ?? null,
                    'scope_id' => $data['scopeId'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'source_culture' => $culture,
                ]);
            }

            // Save i18n data
            $i18nData = [];
            if (isset($data['description'])) {
                $i18nData['description'] = $data['description'];
            }
            if (isset($data['extent'])) {
                $i18nData['extent'] = $data['extent'];
            }
            if (isset($data['reason'])) {
                $i18nData['reason'] = $data['reason'];
            }
            if (!empty($i18nData)) {
                I18nService::save('deaccession_i18n', $id, $culture, $i18nData);
            }

            return $id;
        });
    }

    /**
     * Delete a deaccession.
     */
    public static function deleteDeaccession(int $id): void
    {
        I18nService::delete('deaccession_i18n', $id);
        DB::table('deaccession')->where('id', $id)->delete();
    }

    /**
     * Get form dropdown choices for accession forms.
     */
    public static function getFormChoices(string $culture = 'en'): array
    {
        $termLookup = function (int $taxonomyId) use ($culture) {
            return DB::table('term')
                ->leftJoin('term_i18n', function ($j) use ($culture) {
                    $j->on('term.id', '=', 'term_i18n.id')
                        ->where('term_i18n.culture', '=', $culture);
                })
                ->where('term.taxonomy_id', $taxonomyId)
                ->select('term.id', 'term_i18n.name')
                ->orderBy('term_i18n.name')
                ->get()
                ->all();
        };

        return [
            'acquisitionTypes' => $termLookup(\QubitTaxonomy::ACCESSION_ACQUISITION_TYPE_ID),
            'processingPriorities' => $termLookup(\QubitTaxonomy::ACCESSION_PROCESSING_PRIORITY_ID),
            'processingStatuses' => $termLookup(\QubitTaxonomy::ACCESSION_PROCESSING_STATUS_ID),
            'resourceTypes' => $termLookup(\QubitTaxonomy::ACCESSION_RESOURCE_TYPE_ID),
            'deaccessionScopes' => $termLookup(\QubitTaxonomy::DEACCESSION_SCOPE_ID),
        ];
    }

    /**
     * Get full accession with V2 data, timeline, checklist, containers, appraisals, rights, attachments.
     */
    public static function getExtended(int $accessionId, string $culture = 'en'): ?array
    {
        $base = self::getById($accessionId, $culture);
        if (!$base) {
            return null;
        }

        try {
            $tenantId = null;
            $v2 = DB::table('accession_v2')->where('accession_id', $accessionId)->first();
            if ($v2) {
                $tenantId = $v2->tenant_id;
            }

            $intakeService = new AccessionIntakeService($tenantId);
            $appraisalService = new AccessionAppraisalService($tenantId);
            $containerService = new AccessionContainerService($tenantId);

            $base['timeline'] = $intakeService->getTimeline($accessionId);
            $base['checklist'] = $intakeService->getChecklist($accessionId);
            $base['checklistProgress'] = $intakeService->getChecklistProgress($accessionId);
            $base['attachments'] = $intakeService->getAttachments($accessionId);
            $base['appraisals'] = $appraisalService->getAppraisalsForAccession($accessionId);
            $base['valuations'] = $appraisalService->getValuationHistory($accessionId);
            $base['currentValuation'] = $appraisalService->getCurrentValuation($accessionId);
            $base['containers'] = $containerService->getContainers($accessionId);
            $base['rights'] = $containerService->getRights($accessionId);
        } catch (\Exception $e) {
            // V2 tables may not be installed yet
        }

        return $base;
    }

    /**
     * Get dashboard statistics.
     */
    public static function getDashboardStats(array $filters = []): array
    {
        $stats = [
            'total' => 0,
            'byStatus' => [],
            'recentActivity' => [],
            'topAssignees' => [],
        ];

        try {
            $stats['total'] = DB::table('accession')->count();

            $stats['byStatus'] = DB::table('accession_v2')
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->all();

            $stats['recentActivity'] = DB::table('accession_timeline as t')
                ->leftJoin('actor_i18n as ai', function ($j) {
                    $j->on('t.actor_id', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->leftJoin('accession as a', 't.accession_id', '=', 'a.id')
                ->select('t.event_type', 't.description', 't.created_at', 'ai.authorized_form_of_name as actor_name', 'a.identifier')
                ->orderBy('t.created_at', 'desc')
                ->limit(20)
                ->get()
                ->all();

            $stats['topAssignees'] = DB::table('accession_v2 as v2')
                ->join('actor_i18n as ai', function ($j) {
                    $j->on('v2.assigned_to', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->whereNotNull('v2.assigned_to')
                ->selectRaw('ai.authorized_form_of_name as name, COUNT(*) as cnt')
                ->groupBy('v2.assigned_to', 'ai.authorized_form_of_name')
                ->orderByDesc('cnt')
                ->limit(10)
                ->get()
                ->all();
        } catch (\Exception $e) {
            // V2 tables may not be installed yet
        }

        return $stats;
    }

    /**
     * Extract i18n fields from input data.
     */
    protected static function extractI18nData(array $data): array
    {
        $i18nData = [];
        $mapping = [
            'title' => 'title',
            'appraisal' => 'appraisal',
            'archivalHistory' => 'archival_history',
            'locationInformation' => 'location_information',
            'physicalCharacteristics' => 'physical_characteristics',
            'processingNotes' => 'processing_notes',
            'receivedExtentUnits' => 'received_extent_units',
            'scopeAndContent' => 'scope_and_content',
            'sourceOfAcquisition' => 'source_of_acquisition',
        ];

        foreach ($mapping as $camelKey => $snakeKey) {
            if (array_key_exists($camelKey, $data)) {
                $i18nData[$snakeKey] = $data[$camelKey];
            }
        }

        return $i18nData;
    }
}
