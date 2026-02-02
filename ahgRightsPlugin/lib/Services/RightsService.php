<?php

declare(strict_types=1);

namespace Plugins\ahgRightsPlugin\Services;

use ahgCorePlugin\Services\AhgTaxonomyService;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * RightsService
 *
 * Comprehensive rights management service handling:
 * - Rights Statements (rightsstatements.org)
 * - Creative Commons licenses
 * - Traditional Knowledge Labels
 * - Orphan works due diligence
 * - Embargo management
 * - Territory restrictions
 * - PREMIS rights grants
 *
 * @package ahgRightsPlugin
 */
class RightsService
{
    protected static ?RightsService $instance = null;
    protected string $culture;
    protected array $cache = [];
    protected AhgTaxonomyService $taxonomyService;

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? $this->getDefaultCulture();
        $this->taxonomyService = new AhgTaxonomyService();
    }

    public static function getInstance(?string $culture = null): self
    {
        if (null === self::$instance) {
            self::$instance = new self($culture);
        }

        return self::$instance;
    }

    protected function getDefaultCulture(): string
    {
        if (class_exists('sfContext') && \sfContext::hasInstance()) {
            return \sfContext::getInstance()->getUser()->getCulture() ?? 'en';
        }

        return 'en';
    }

    // =========================================================================
    // RIGHTS RECORDS
    // =========================================================================

    /**
     * Get all rights records for an object
     */
    public function getRightsForObject(int $objectId): Collection
    {
        return DB::table('rights_record as r')
            ->leftJoin('rights_record_i18n as ri', function ($join) {
                $join->on('r.id', '=', 'ri.id')
                    ->where('ri.culture', '=', $this->culture);
            })
            ->leftJoin('rights_statement as rs', 'r.rights_statement_id', '=', 'rs.id')
            ->leftJoin('rights_statement_i18n as rsi', function ($join) {
                $join->on('rs.id', '=', 'rsi.rights_statement_id')
                    ->where('rsi.culture', '=', $this->culture);
            })
            ->leftJoin('rights_cc_license as cc', 'r.cc_license_id', '=', 'cc.id')
            ->leftJoin('rights_cc_license_i18n as cci', function ($join) {
                $join->on('cc.id', '=', 'cci.id')
                    ->where('cci.culture', '=', $this->culture);
            })
            ->where('r.object_id', $objectId)
            ->select([
                'r.*',
                'ri.rights_note',
                'ri.restriction_note',
                'rs.code as rights_statement_code',
                'rs.uri as rights_statement_uri',
                'rsi.name as rights_statement_name',
                'cc.code as cc_license_code',
                'cc.uri as cc_license_uri',
                'cc.badge_url as cc_badge_url',
                'cci.name as cc_license_name',
            ])
            ->orderBy('r.created_at', 'desc')
            ->get();
    }

    /**
     * Get single rights record
     */
    public function getRightsRecord(int $id): ?object
    {
        $record = DB::table('rights_record as r')
            ->leftJoin('rights_record_i18n as ri', function ($join) {
                $join->on('r.id', '=', 'ri.id')
                    ->where('ri.culture', '=', $this->culture);
            })
            ->where('r.id', $id)
            ->select(['r.*', 'ri.rights_note', 'ri.restriction_note'])
            ->first();

        if ($record) {
            // Load grants
            $record->grants = $this->getGrantsForRecord($id);

            // Load territories
            $record->territories = $this->getTerritoriesForRecord($id);
        }

        return $record;
    }

    /**
     * Create rights record
     */
    public function createRightsRecord(array $data): int
    {
        $recordData = [
            'object_id' => $data['object_id'],
            'basis' => $data['basis'] ?? 'copyright',
            'rights_statement_id' => $data['rights_statement_id'] ?? null,
            'cc_license_id' => $data['cc_license_id'] ?? null,
            'copyright_status' => $data['copyright_status'] ?? 'unknown',
            'copyright_holder' => $data['copyright_holder'] ?? null,
            'copyright_holder_actor_id' => $data['copyright_holder_actor_id'] ?? null,
            'copyright_jurisdiction' => $data['copyright_jurisdiction'] ?? 'ZA',
            'copyright_determination_date' => $data['copyright_determination_date'] ?? null,
            'copyright_note' => $data['copyright_note'] ?? null,
            'license_identifier' => $data['license_identifier'] ?? null,
            'license_terms' => $data['license_terms'] ?? null,
            'license_note' => $data['license_note'] ?? null,
            'statute_citation' => $data['statute_citation'] ?? null,
            'statute_jurisdiction' => $data['statute_jurisdiction'] ?? null,
            'statute_determination_date' => $data['statute_determination_date'] ?? null,
            'statute_note' => $data['statute_note'] ?? null,
            'donor_name' => $data['donor_name'] ?? null,
            'donor_actor_id' => $data['donor_actor_id'] ?? null,
            'donor_agreement_date' => $data['donor_agreement_date'] ?? null,
            'donor_note' => $data['donor_note'] ?? null,
            'policy_identifier' => $data['policy_identifier'] ?? null,
            'policy_note' => $data['policy_note'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'documentation_identifier' => $data['documentation_identifier'] ?? null,
            'documentation_role' => $data['documentation_role'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $id = DB::table('rights_record')->insertGetId($recordData);

        // Insert i18n
        if (!empty($data['rights_note']) || !empty($data['restriction_note'])) {
            DB::table('rights_record_i18n')->insert([
                'id' => $id,
                'culture' => $this->culture,
                'rights_note' => $data['rights_note'] ?? null,
                'restriction_note' => $data['restriction_note'] ?? null,
            ]);
        }

        // Create grants if provided
        if (!empty($data['grants'])) {
            foreach ($data['grants'] as $grant) {
                $this->createGrant($id, $grant);
            }
        }

        return $id;
    }

    /**
     * Update rights record
     */
    public function updateRightsRecord(int $id, array $data): bool
    {
        $updateData = array_filter([
            'basis' => $data['basis'] ?? null,
            'rights_statement_id' => $data['rights_statement_id'] ?? null,
            'cc_license_id' => $data['cc_license_id'] ?? null,
            'copyright_status' => $data['copyright_status'] ?? null,
            'copyright_holder' => $data['copyright_holder'] ?? null,
            'copyright_jurisdiction' => $data['copyright_jurisdiction'] ?? null,
            'copyright_determination_date' => $data['copyright_determination_date'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], fn ($v) => null !== $v);

        DB::table('rights_record')->where('id', $id)->update($updateData);

        // Update i18n
        DB::table('rights_record_i18n')->updateOrInsert(
            ['id' => $id, 'culture' => $this->culture],
            [
                'rights_note' => $data['rights_note'] ?? null,
                'restriction_note' => $data['restriction_note'] ?? null,
            ]
        );

        return true;
    }

    /**
     * Delete rights record
     */
    public function deleteRightsRecord(int $id): bool
    {
        return DB::table('rights_record')->where('id', $id)->delete() > 0;
    }

    // =========================================================================
    // RIGHTS GRANTS (PREMIS Acts)
    // =========================================================================

    /**
     * Get grants for a rights record
     */
    public function getGrantsForRecord(int $recordId): Collection
    {
        return DB::table('rights_grant as g')
            ->leftJoin('rights_grant_i18n as gi', function ($join) {
                $join->on('g.id', '=', 'gi.id')
                    ->where('gi.culture', '=', $this->culture);
            })
            ->where('g.rights_record_id', $recordId)
            ->select(['g.*', 'gi.restriction_note'])
            ->get();
    }

    /**
     * Create a grant
     */
    public function createGrant(int $recordId, array $data): int
    {
        $id = DB::table('rights_grant')->insertGetId([
            'rights_record_id' => $recordId,
            'act' => $data['act'],
            'restriction' => $data['restriction'] ?? 'allow',
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'condition_type' => $data['condition_type'] ?? null,
            'condition_value' => $data['condition_value'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!empty($data['restriction_note'])) {
            DB::table('rights_grant_i18n')->insert([
                'id' => $id,
                'culture' => $this->culture,
                'restriction_note' => $data['restriction_note'],
            ]);
        }

        return $id;
    }

    // =========================================================================
    // EMBARGO MANAGEMENT
    // =========================================================================

    /**
     * Get embargo for an object
     */
    public function getEmbargo(int $objectId): ?object
    {
        return DB::table('rights_embargo as e')
            ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                $join->on('e.id', '=', 'ei.id')
                    ->where('ei.culture', '=', $this->culture);
            })
            ->where('e.object_id', $objectId)
            ->where('e.status', 'active')
            ->select(['e.*', 'ei.reason_note', 'ei.internal_note'])
            ->first();
    }

    /**
     * Get all active embargoes
     */
    public function getActiveEmbargoes(): Collection
    {
        return DB::table('rights_embargo as e')
            ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                $join->on('e.id', '=', 'ei.id')
                    ->where('ei.culture', '=', $this->culture);
            })
            ->leftJoin('information_object as io', 'e.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('e.status', 'active')
            ->select([
                'e.*',
                'ei.reason_note',
                'ioi.title as object_title',
                's.slug',
            ])
            ->orderBy('e.end_date')
            ->get();
    }

    /**
     * Get embargoes expiring soon
     */
    public function getExpiringEmbargoes(int $days = 30): Collection
    {
        return DB::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'e.object_id')
            ->where('e.status', 'active')
            ->whereNotNull('e.end_date')
            ->whereRaw('e.end_date <= DATE_ADD(NOW(), INTERVAL ? DAY)', [$days])
            ->where('e.notification_sent', 0)
            ->select(['e.*', 'ioi.title as object_title', 's.slug'])
            ->orderBy('e.end_date')
            ->get();
    }

    /**
     * Get embargoes due for review
     */
    public function getEmbargoesForReview(): Collection
    {
        return DB::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'e.object_id')
            ->where('e.status', 'active')
            ->whereNotNull('e.review_date')
            ->where('e.review_date', '<=', date('Y-m-d'))
            ->select(['e.*', 'ioi.title as object_title', 's.slug'])
            ->orderBy('e.review_date')
            ->get();
    }

    /**
     * Create embargo
     */
    public function createEmbargo(array $data): int
    {
        $id = DB::table('rights_embargo')->insertGetId([
            'object_id' => $data['object_id'],
            'embargo_type' => $data['embargo_type'] ?? 'full',
            'reason' => $data['reason'],
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'end_date' => $data['end_date'] ?? null,
            'auto_release' => $data['auto_release'] ?? 1,
            'review_date' => $data['review_date'] ?? null,
            'review_interval_months' => $data['review_interval_months'] ?? 12,
            'notify_before_days' => $data['notify_before_days'] ?? 30,
            'notify_emails' => json_encode($data['notify_emails'] ?? []),
            'status' => 'active',
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Insert i18n
        DB::table('rights_embargo_i18n')->insert([
            'id' => $id,
            'culture' => $this->culture,
            'reason_note' => $data['reason_note'] ?? null,
            'internal_note' => $data['internal_note'] ?? null,
        ]);

        // Log creation
        $this->logEmbargoAction($id, 'created', null, 'active');

        return $id;
    }

    /**
     * Lift embargo
     */
    public function liftEmbargo(int $id, ?string $reason = null, ?int $userId = null): bool
    {
        $embargo = DB::table('rights_embargo')->where('id', $id)->first();

        if (!$embargo) {
            return false;
        }

        DB::table('rights_embargo')->where('id', $id)->update([
            'status' => 'lifted',
            'lifted_at' => date('Y-m-d H:i:s'),
            'lifted_by' => $userId,
            'lift_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logEmbargoAction($id, 'lifted', $embargo->status, 'lifted');

        return true;
    }

    /**
     * Extend embargo
     */
    public function extendEmbargo(int $id, string $newEndDate, ?string $reason = null, ?int $userId = null): bool
    {
        $embargo = DB::table('rights_embargo')->where('id', $id)->first();

        if (!$embargo) {
            return false;
        }

        $oldEndDate = $embargo->end_date;

        DB::table('rights_embargo')->where('id', $id)->update([
            'end_date' => $newEndDate,
            'status' => 'extended',
            'notification_sent' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logEmbargoAction($id, 'extended', $embargo->status, 'extended', $oldEndDate, $newEndDate, $reason, $userId);

        return true;
    }

    /**
     * Process expired embargoes (auto-release)
     */
    public function processExpiredEmbargoes(): int
    {
        $expired = DB::table('rights_embargo')
            ->where('status', 'active')
            ->where('auto_release', 1)
            ->whereNotNull('end_date')
            ->where('end_date', '<', date('Y-m-d'))
            ->get();

        $count = 0;
        foreach ($expired as $embargo) {
            DB::table('rights_embargo')->where('id', $embargo->id)->update([
                'status' => 'expired',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logEmbargoAction($embargo->id, 'auto_released', 'active', 'expired');
            ++$count;
        }

        return $count;
    }

    /**
     * Log embargo action
     */
    protected function logEmbargoAction(
        int $embargoId,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $oldEndDate = null,
        ?string $newEndDate = null,
        ?string $notes = null,
        ?int $userId = null
    ): void {
        DB::table('rights_embargo_log')->insert([
            'embargo_id' => $embargoId,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_end_date' => $oldEndDate,
            'new_end_date' => $newEndDate,
            'notes' => $notes,
            'performed_by' => $userId,
            'performed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // ORPHAN WORKS
    // =========================================================================

    /**
     * Get orphan work record
     */
    public function getOrphanWork(int $objectId): ?object
    {
        return DB::table('rights_orphan_work as o')
            ->leftJoin('rights_orphan_work_i18n as oi', function ($join) {
                $join->on('o.id', '=', 'oi.id')
                    ->where('oi.culture', '=', $this->culture);
            })
            ->where('o.object_id', $objectId)
            ->select(['o.*', 'oi.notes', 'oi.search_summary'])
            ->first();
    }

    /**
     * Get orphan work search steps
     */
    public function getOrphanWorkSearchSteps(int $orphanWorkId): Collection
    {
        return DB::table('rights_orphan_search_step')
            ->where('orphan_work_id', $orphanWorkId)
            ->orderBy('search_date')
            ->get();
    }

    /**
     * Create orphan work record
     */
    public function createOrphanWork(array $data): int
    {
        $id = DB::table('rights_orphan_work')->insertGetId([
            'object_id' => $data['object_id'],
            'status' => 'in_progress',
            'work_type' => $data['work_type'],
            'search_started_date' => $data['search_started_date'] ?? date('Y-m-d'),
            'search_jurisdiction' => $data['search_jurisdiction'] ?? 'ZA',
            'intended_use' => $data['intended_use'] ?? null,
            'proposed_fee' => $data['proposed_fee'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('rights_orphan_work_i18n')->insert([
            'id' => $id,
            'culture' => $this->culture,
            'notes' => $data['notes'] ?? null,
        ]);

        return $id;
    }

    /**
     * Add search step to orphan work
     */
    public function addOrphanWorkSearchStep(int $orphanWorkId, array $data): int
    {
        return DB::table('rights_orphan_search_step')->insertGetId([
            'orphan_work_id' => $orphanWorkId,
            'source_type' => $data['source_type'],
            'source_name' => $data['source_name'],
            'source_url' => $data['source_url'] ?? null,
            'search_date' => $data['search_date'] ?? date('Y-m-d'),
            'search_terms' => $data['search_terms'] ?? null,
            'results_found' => $data['results_found'] ?? 0,
            'results_description' => $data['results_description'] ?? null,
            'evidence_file_path' => $data['evidence_file_path'] ?? null,
            'screenshot_path' => $data['screenshot_path'] ?? null,
            'performed_by' => $data['performed_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Complete orphan work search
     */
    public function completeOrphanWorkSearch(int $id, bool $rightsHolderFound = false): bool
    {
        $updateData = [
            'status' => $rightsHolderFound ? 'rights_holder_found' : 'completed',
            'search_completed_date' => date('Y-m-d'),
            'rights_holder_found' => $rightsHolderFound ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return DB::table('rights_orphan_work')->where('id', $id)->update($updateData) > 0;
    }

    // =========================================================================
    // TK LABELS
    // =========================================================================

    /**
     * Get TK labels for an object
     */
    public function getTkLabelsForObject(int $objectId): Collection
    {
        return DB::table('rights_object_tk_label as otl')
            ->join('rights_tk_label as tl', 'otl.tk_label_id', '=', 'tl.id')
            ->leftJoin('rights_tk_label_i18n as tli', function ($join) {
                $join->on('tl.id', '=', 'tli.id')
                    ->where('tli.culture', '=', $this->culture);
            })
            ->where('otl.object_id', $objectId)
            ->select([
                'otl.*',
                'tl.code',
                'tl.category',
                'tl.uri',
                'tl.color',
                'tli.name',
                'tli.description',
                'tli.usage_protocol',
            ])
            ->orderBy('tl.sort_order')
            ->get();
    }

    /**
     * Assign TK label to object
     */
    public function assignTkLabel(int $objectId, int $labelId, array $data = []): int
    {
        return DB::table('rights_object_tk_label')->insertGetId([
            'object_id' => $objectId,
            'tk_label_id' => $labelId,
            'community_name' => $data['community_name'] ?? null,
            'community_contact' => $data['community_contact'] ?? null,
            'custom_text' => $data['custom_text'] ?? null,
            'verified' => $data['verified'] ?? 0,
            'verified_by' => $data['verified_by'] ?? null,
            'verified_date' => $data['verified_date'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove TK label from object
     */
    public function removeTkLabel(int $objectId, int $labelId): bool
    {
        return DB::table('rights_object_tk_label')
            ->where('object_id', $objectId)
            ->where('tk_label_id', $labelId)
            ->delete() > 0;
    }

    // =========================================================================
    // TERRITORY RESTRICTIONS
    // =========================================================================

    /**
     * Get territories for a rights record
     */
    public function getTerritoriesForRecord(int $recordId): Collection
    {
        return DB::table('rights_territory')
            ->where('rights_record_id', $recordId)
            ->get();
    }

    /**
     * Add territory restriction
     */
    public function addTerritory(int $recordId, string $countryCode, string $type = 'include', bool $isGdpr = false): int
    {
        return DB::table('rights_territory')->insertGetId([
            'rights_record_id' => $recordId,
            'territory_type' => $type,
            'country_code' => strtoupper($countryCode),
            'is_gdpr_territory' => $isGdpr ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // ACCESS CHECKS
    // =========================================================================

    /**
     * Check if object is accessible
     */
    public function checkAccess(int $objectId, ?int $userId = null, ?string $purpose = null): array
    {
        $result = [
            'accessible' => true,
            'restrictions' => [],
            'embargo' => null,
            'rights_statement' => null,
            'cc_license' => null,
            'tk_labels' => [],
            'required_actions' => [],
        ];

        // Check embargo
        $embargo = $this->getEmbargo($objectId);
        if ($embargo) {
            $result['accessible'] = false;
            $result['embargo'] = $embargo;
            $result['restrictions'][] = [
                'type' => 'embargo',
                'reason' => $embargo->reason,
                'until' => $embargo->end_date,
            ];
        }

        // Check rights records
        $rights = $this->getRightsForObject($objectId);
        foreach ($rights as $right) {
            if ($right->rights_statement_code) {
                $result['rights_statement'] = [
                    'code' => $right->rights_statement_code,
                    'name' => $right->rights_statement_name,
                    'uri' => $right->rights_statement_uri,
                ];
            }

            if ($right->cc_license_code) {
                $result['cc_license'] = [
                    'code' => $right->cc_license_code,
                    'name' => $right->cc_license_name,
                    'uri' => $right->cc_license_uri,
                    'badge_url' => $right->cc_badge_url,
                ];
            }

            // Check grants
            $grants = $this->getGrantsForRecord($right->id);
            foreach ($grants as $grant) {
                if ('disallow' === $grant->restriction) {
                    $result['restrictions'][] = [
                        'type' => 'grant',
                        'act' => $grant->act,
                        'restriction' => $grant->restriction,
                    ];
                }
            }
        }

        // Check TK labels
        $tkLabels = $this->getTkLabelsForObject($objectId);
        if ($tkLabels->isNotEmpty()) {
            $result['tk_labels'] = $tkLabels->toArray();

            // Check for restricted labels
            $restrictedCodes = ['TK-SS', 'TK-MR', 'TK-WR', 'TK-C'];
            foreach ($tkLabels as $label) {
                if (in_array($label->code, $restrictedCodes, true)) {
                    $result['required_actions'][] = [
                        'type' => 'tk_consultation',
                        'label' => $label->code,
                        'community' => $label->community_name,
                    ];
                }
            }
        }

        return $result;
    }

    // =========================================================================
    // REFERENCE DATA
    // =========================================================================

    /**
     * Get all rights statements
     */
    public function getRightsStatements(): Collection
    {
        return DB::table('rights_statement as rs')
            ->leftJoin('rights_statement_i18n as rsi', function ($join) {
                $join->on('rs.id', '=', 'rsi.rights_statement_id')
                    ->where('rsi.culture', '=', $this->culture);
            })
            ->where('rs.is_active', 1)
            ->select(['rs.*', 'rsi.name', 'rsi.definition', 'rsi.scope_note'])
            ->orderBy('rs.sort_order')
            ->get();
    }

    /**
     * Get all CC licenses
     */
    public function getCcLicenses(): Collection
    {
        return DB::table('rights_cc_license as cc')
            ->leftJoin('rights_cc_license_i18n as cci', function ($join) {
                $join->on('cc.id', '=', 'cci.id')
                    ->where('cci.culture', '=', $this->culture);
            })
            ->where('cc.is_active', 1)
            ->select(['cc.*', 'cci.name', 'cci.description', 'cci.human_readable'])
            ->orderBy('cc.sort_order')
            ->get();
    }

    /**
     * Get all TK labels
     */
    public function getTkLabels(): Collection
    {
        return DB::table('rights_tk_label as tl')
            ->leftJoin('rights_tk_label_i18n as tli', function ($join) {
                $join->on('tl.id', '=', 'tli.id')
                    ->where('tli.culture', '=', $this->culture);
            })
            ->where('tl.is_active', 1)
            ->select(['tl.*', 'tli.name', 'tli.description', 'tli.usage_protocol'])
            ->orderBy('tl.category')
            ->orderBy('tl.sort_order')
            ->get();
    }

    /**
     * Get form options for dropdowns (loaded from database)
     */
    public function getFormOptions(): array
    {
        return [
            'basis_options' => $this->taxonomyService->getRightsBasis(false),
            'copyright_status_options' => $this->taxonomyService->getCopyrightStatuses(false),
            'act_options' => $this->taxonomyService->getActTypes(false),
            'restriction_options' => $this->taxonomyService->getRestrictionTypes(false),
            'embargo_type_options' => $this->taxonomyService->getEmbargoTypes(false),
            'embargo_reason_options' => $this->taxonomyService->getEmbargoReasons(false),
            'work_type_options' => $this->taxonomyService->getWorkTypes(false),
            'search_source_options' => $this->taxonomyService->getSourceTypes(false),
            'rights_statements' => $this->getRightsStatements(),
            'cc_licenses' => $this->getCcLicenses(),
            'tk_labels' => $this->getTkLabels(),
        ];
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /**
     * Get rights statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_rights_records' => DB::table('rights_record')->count(),
            'by_basis' => DB::table('rights_record')
                ->selectRaw('basis, COUNT(*) as count')
                ->groupBy('basis')
                ->pluck('count', 'basis')
                ->toArray(),
            'active_embargoes' => DB::table('rights_embargo')
                ->where('status', 'active')
                ->count(),
            'expiring_soon' => DB::table('rights_embargo')
                ->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereRaw('end_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)')
                ->count(),
            'orphan_works_in_progress' => DB::table('rights_orphan_work')
                ->where('status', 'in_progress')
                ->count(),
            'tk_label_assignments' => DB::table('rights_object_tk_label')->count(),
            'by_rights_statement' => DB::table('rights_record as r')
                ->join('rights_statement as rs', 'r.rights_statement_id', '=', 'rs.id')
                ->selectRaw('rs.code, COUNT(*) as count')
                ->groupBy('rs.code')
                ->pluck('count', 'code')
                ->toArray(),
            'by_cc_license' => DB::table('rights_record as r')
                ->join('rights_cc_license as cc', 'r.cc_license_id', '=', 'cc.id')
                ->selectRaw('cc.code, COUNT(*) as count')
                ->groupBy('cc.code')
                ->pluck('count', 'code')
                ->toArray(),
        ];
    }
}
