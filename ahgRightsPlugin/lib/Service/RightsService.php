<?php
declare(strict_types=1);

require_once dirname(__FILE__)."/../RightsConstants.php";

/**
 * RightsService - Business logic for rights management
 *
 * @package    ahgRightsPlugin
 * @subpackage Service
 */

use ahgCorePlugin\Services\AhgTaxonomyService;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class RightsService
{
    protected static ?RightsService $instance = null;
    protected Logger $logger;
    protected string $culture;
    protected AhgTaxonomyService $taxonomyService;

    // PREMIS acts
    public const ACT_RENDER = 'render';
    public const ACT_DISSEMINATE = 'disseminate';
    public const ACT_REPLICATE = 'replicate';
    public const ACT_MIGRATE = 'migrate';
    public const ACT_MODIFY = 'modify';
    public const ACT_DELETE = 'delete';
    public const ACT_PRINT = 'print';
    public const ACT_USE = 'use';

    // Embargo types
    public const EMBARGO_FULL = 'full';
    public const EMBARGO_METADATA = 'metadata_only';
    public const EMBARGO_DIGITAL = 'digital_only';
    public const EMBARGO_PARTIAL = 'partial';

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? sfContext::getInstance()->getUser()->getCulture() ?? 'en';
        $this->taxonomyService = new AhgTaxonomyService();
        $this->initLogger();
    }

    public static function getInstance(?string $culture = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($culture);
        }
        return self::$instance;
    }

    protected function initLogger(): void
    {
        $this->logger = new Logger('rights');
        $logPath = sfConfig::get('sf_log_dir', '/tmp') . '/rights.log';
        $this->logger->pushHandler(new RotatingFileHandler($logPath, 30, Logger::DEBUG));
    }

    // =========================================================================
    // RIGHTS RECORDS
    // =========================================================================

    public function getRightsForObject(int $objectId, string $objectType = 'information_object'): array
    {
        $rows = DB::table('rights_record as r')
            ->leftJoin('rights_statement as rs', 'r.rights_statement_id', '=', 'rs.id')
            ->leftJoin('rights_statement_i18n as rsi', function ($join) {
                $join->on('rs.id', '=', 'rsi.id')
                    ->where('rsi.culture', '=', $this->culture);
            })
            ->leftJoin('rights_cc_license as cc', 'r.cc_license_id', '=', 'cc.id')
            ->leftJoin('rights_cc_license_i18n as cci', function ($join) {
                $join->on('cc.id', '=', 'cci.id')
                    ->where('cci.culture', '=', $this->culture);
            })
            ->where('r.object_id', $objectId)
            ->where('r.object_type', $objectType)
            ->select([
                'r.*',
                'rs.code as statement_code',
                'rs.uri as statement_uri',
                'rsi.name as statement_name',
                'cc.code as cc_code',
                'cc.uri as cc_uri',
                'cci.name as cc_name',
            ])
            ->orderBy('r.created_at', 'desc')
            ->get();

        $results = [];
        foreach ($rows as $row) {
            $record = (array) $row;
            $record['granted_rights'] = $this->getGrantedRights($row->id);
            $results[] = $record;
        }

        return $results;
    }

    public function getRightsRecord(int $id): ?array
    {
        $row = DB::table('rights_record as r')
            ->leftJoin('rights_statement as rs', 'r.rights_statement_id', '=', 'rs.id')
            ->leftJoin('rights_statement_i18n as rsi', function ($join) {
                $join->on('rs.id', '=', 'rsi.id')
                    ->where('rsi.culture', '=', $this->culture);
            })
            ->leftJoin('rights_cc_license as cc', 'r.cc_license_id', '=', 'cc.id')
            ->where('r.id', $id)
            ->select(['r.*', 'rsi.name as statement_name', 'cc.code as cc_code'])
            ->first();

        if (!$row) {
            return null;
        }

        $record = (array) $row;
        $record['granted_rights'] = $this->getGrantedRights($id);
        $record['tk_labels'] = $this->getTkLabelsForObject($row->object_id, $row->object_type);

        return $record;
    }

    public function saveRightsRecord(array $data): int
    {
        $isNew = empty($data['id']);
        $oldValues = [];
        if (!$isNew && !empty($data['id'])) {
            $oldValues = $this->getRightsRecord((int)$data['id']) ?? [];
        }

        $now = date('Y-m-d H:i:s');
        $userId = sfContext::getInstance()->getUser()->getAttribute('user_id');

        $recordData = [
            'object_id' => $data['object_id'],
            'object_type' => $data['object_type'] ?? 'information_object',
            'basis' => $data['basis'],
            'basis_note' => $data['basis_note'] ?? null,
            'rights_statement_id' => $data['rights_statement_id'] ?? null,
            'copyright_status' => $data['copyright_status'] ?? null,
            'copyright_jurisdiction' => $data['copyright_jurisdiction'] ?? null,
            'copyright_status_date' => $data['copyright_status_date'] ?? null,
            'copyright_holder' => $data['copyright_holder'] ?? null,
            'copyright_expiry_date' => $data['copyright_expiry_date'] ?? null,
            'copyright_note' => $data['copyright_note'] ?? null,
            'license_type' => $data['license_type'] ?? null,
            'cc_license_id' => $data['cc_license_id'] ?? null,
            'license_identifier' => $data['license_identifier'] ?? null,
            'license_terms' => $data['license_terms'] ?? null,
            'license_url' => $data['license_url'] ?? null,
            'license_note' => $data['license_note'] ?? null,
            'statute_jurisdiction' => $data['statute_jurisdiction'] ?? null,
            'statute_citation' => $data['statute_citation'] ?? null,
            'statute_determination_date' => $data['statute_determination_date'] ?? null,
            'statute_note' => $data['statute_note'] ?? null,
            'donor_name' => $data['donor_name'] ?? null,
            'policy_identifier' => $data['policy_identifier'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'end_date_open' => $data['end_date_open'] ?? 0,
            'rights_holder_name' => $data['rights_holder_name'] ?? null,
            'rights_note' => $data['rights_note'] ?? null,
            'updated_at' => $now,
            'updated_by' => $userId,
        ];

        if (!empty($data['id'])) {
            DB::table('rights_record')->where('id', $data['id'])->update($recordData);
            $id = $data['id'];
            $this->logAudit($data['object_id'], $data['object_type'] ?? 'information_object', 'update', 'rights_record', $id);
        } else {
            $recordData['created_at'] = $now;
            $recordData['created_by'] = $userId;
            $id = DB::table('rights_record')->insertGetId($recordData);
            $this->logAudit($data['object_id'], $data['object_type'] ?? 'information_object', 'create', 'rights_record', $id);
        }

        // Save granted rights
        if (!empty($data['granted_rights'])) {
            $this->saveGrantedRights($id, $data['granted_rights']);
        }

        $this->logger->info('Rights record saved', ['id' => $id, 'object_id' => $data['object_id']]);

        
        $newValues = $this->getRightsRecord($id) ?? [];
        $this->logAudit($isNew ? 'create' : 'update', 'RightsRecord', $id, $oldValues, $newValues, null);
        return $id;
    }

    public function deleteRightsRecord(int $id): bool
    {
        $oldValues = $this->getRightsRecord($id) ?? [];
        $record = DB::table('rights_record')->where('id', $id)->first();
        if (!$record) {
            return false;
        }

        DB::table('rights_record')->where('id', $id)->delete();
        $this->logAudit($record->object_id, $record->object_type, 'delete', 'rights_record', $id);

        return true;
    }

    // =========================================================================
    // GRANTED RIGHTS
    // =========================================================================

    public function getGrantedRights(int $rightsRecordId): array
    {
        return DB::table('rights_granted')
            ->where('rights_record_id', $rightsRecordId)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function saveGrantedRights(int $rightsRecordId, array $grants): void
    {
        DB::table('rights_granted')->where('rights_record_id', $rightsRecordId)->delete();

        $now = date('Y-m-d H:i:s');
        foreach ($grants as $grant) {
            if (empty($grant['act'])) {
                continue;
            }

            DB::table('rights_granted')->insert([
                'rights_record_id' => $rightsRecordId,
                'act' => $grant['act'],
                'restriction' => $grant['restriction'] ?? 'allow',
                'restriction_reason' => $grant['restriction_reason'] ?? null,
                'start_date' => $grant['start_date'] ?? null,
                'end_date' => $grant['end_date'] ?? null,
                'grant_note' => $grant['grant_note'] ?? null,
                'created_at' => $now,
            ]);
        }
    }

    // =========================================================================
    // EMBARGOES
    // =========================================================================

    public function getEmbargo(int $objectId, string $objectType = 'information_object'): ?array
    {
        $row = DB::table('rights_embargo')
            ->where('object_id', $objectId)
            ->where('object_type', $objectType)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', date('Y-m-d'));
            })
            ->first();

        return $row ? (array) $row : null;
    }

    public function isEmbargoed(int $objectId, string $objectType = 'information_object'): bool
    {
        return $this->getEmbargo($objectId, $objectType) !== null;
    }

    public function setEmbargo(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $userId = sfContext::getInstance()->getUser()->getAttribute('user_id');

        $embargoData = [
            'object_id' => $data['object_id'],
            'object_type' => $data['object_type'] ?? 'information_object',
            'embargo_type' => $data['embargo_type'] ?? self::EMBARGO_FULL,
            'reason' => $data['reason'],
            'reason_detail' => $data['reason_detail'] ?? null,
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'end_date' => $data['end_date'] ?? null,
            'auto_release' => $data['auto_release'] ?? 1,
            'release_notification_days' => $data['release_notification_days'] ?? 30,
            'allow_staff' => $data['allow_staff'] ?? 1,
            'allow_researchers' => $data['allow_researchers'] ?? 0,
            'access_note' => $data['access_note'] ?? null,
            'updated_at' => $now,
        ];

        if (!empty($data['id'])) {
            DB::table('rights_embargo')->where('id', $data['id'])->update($embargoData);
            $id = $data['id'];
        } else {
            $embargoData['created_by'] = $userId;
            $embargoData['created_at'] = $now;
            $id = DB::table('rights_embargo')->insertGetId($embargoData);
        }

        $this->logAudit($data['object_id'], $data['object_type'] ?? 'information_object', 'embargo_set', 'embargo', $id);

        
        $this->logAudit('create', 'Embargo', $id, [], $data, null);
        return $id;
    }

    public function releaseEmbargo(int $embargoId): bool
    {
        $embargo = DB::table('rights_embargo')->where('id', $embargoId)->first();
        if (!$embargo) {
            return false;
        }

        $userId = sfContext::getInstance()->getUser()->getAttribute('user_id');

        DB::table('rights_embargo')->where('id', $embargoId)->update([
            'released_at' => date('Y-m-d H:i:s'),
            'released_by' => $userId,
        ]);

        $this->logAudit($embargo->object_id, $embargo->object_type, 'embargo_release', 'embargo', $embargoId);

        return true;
    }

    public function getExpiringEmbargoes(int $days = 30): array
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));

        return DB::table('rights_embargo as e')
            ->join('information_object as io', 'e.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->whereNotNull('e.end_date')
            ->where('e.end_date', '<=', $futureDate)
            ->where('e.end_date', '>=', date('Y-m-d'))
            ->whereNull('e.released_at')
            ->select(['e.*', 'io.slug', 'ioi.title'])
            ->orderBy('e.end_date')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    // =========================================================================
    // TK LABELS
    // =========================================================================

    public function getTkLabels(): array
    {
        return DB::table('rights_tk_label as l')
            ->leftJoin('rights_tk_label_i18n as li', function ($join) {
                $join->on('l.id', '=', 'li.id')
                    ->where('li.culture', '=', $this->culture);
            })
            ->where('l.is_active', 1)
            ->orderBy('l.sort_order')
            ->select(['l.*', 'li.name', 'li.description'])
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function getTkLabelsForObject(int $objectId, string $objectType = 'information_object'): array
    {
        return DB::table('rights_object_tk_label as ol')
            ->join('rights_tk_label as l', 'ol.tk_label_id', '=', 'l.id')
            ->leftJoin('rights_tk_label_i18n as li', function ($join) {
                $join->on('l.id', '=', 'li.id')
                    ->where('li.culture', '=', $this->culture);
            })
            ->where('ol.object_id', $objectId)
            ->where('ol.object_type', $objectType)
            ->select(['ol.*', 'l.code', 'l.color', 'l.icon_url', 'li.name', 'li.description'])
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function assignTkLabel(int $objectId, int $labelId, array $data = []): int
    {
        $userId = sfContext::getInstance()->getUser()->getAttribute('user_id');

        return DB::table('rights_object_tk_label')->insertGetId([
            'object_id' => $objectId,
            'object_type' => $data['object_type'] ?? 'information_object',
            'tk_label_id' => $labelId,
            'community_name' => $data['community_name'] ?? null,
            'community_contact' => $data['community_contact'] ?? null,
            'provenance_statement' => $data['provenance_statement'] ?? null,
            'cultural_note' => $data['cultural_note'] ?? null,
            'assigned_by' => $userId,
            'assigned_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // RIGHTS STATEMENTS
    // =========================================================================

    public function getRightsStatements(): array
    {
        return DB::table('rights_statement as s')
            ->leftJoin('rights_statement_i18n as si', function ($join) {
                $join->on('s.id', '=', 'si.id')
                    ->where('si.culture', '=', $this->culture);
            })
            ->where('s.is_active', 1)
            ->orderBy('s.sort_order')
            ->select(['s.*', 'si.name', 'si.description'])
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    // =========================================================================
    // CC LICENSES
    // =========================================================================

    public function getCcLicenses(): array
    {
        return DB::table('rights_cc_license as l')
            ->leftJoin('rights_cc_license_i18n as li', function ($join) {
                $join->on('l.id', '=', 'li.id')
                    ->where('li.culture', '=', $this->culture);
            })
            ->where('l.is_active', 1)
            ->orderBy('l.sort_order')
            ->select(['l.*', 'li.name', 'li.description'])
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    // =========================================================================
    // ACCESS CHECKS
    // =========================================================================

    public function checkAccess(int $objectId, string $objectType = 'information_object', ?int $userId = null): array
    {
        $result = [
            'allowed' => true,
            'restrictions' => [],
            'rights' => [],
            'embargo' => null,
            'tk_labels' => [],
        ];

        // Check embargo
        $embargo = $this->getEmbargo($objectId, $objectType);
        if ($embargo) {
            $result['embargo'] = $embargo;

            $isStaff = $this->isStaffUser($userId);
            $isResearcher = $this->isResearcherUser($userId);

            if ($embargo['embargo_type'] === self::EMBARGO_FULL) {
                if (!($isStaff && $embargo['allow_staff']) && !($isResearcher && $embargo['allow_researchers'])) {
                    $result['allowed'] = false;
                    $result['restrictions'][] = 'This item is under embargo until ' . ($embargo['end_date'] ?? 'further notice');
                }
            }
        }

        // Check rights records
        $rights = $this->getRightsForObject($objectId, $objectType);
        $result['rights'] = $rights;

        foreach ($rights as $right) {
            foreach ($right['granted_rights'] ?? [] as $grant) {
                if ($grant['restriction'] === 'disallow' && $grant['act'] === 'disseminate') {
                    $result['allowed'] = false;
                    $result['restrictions'][] = $grant['restriction_reason'] ?? 'Access restricted';
                }
            }
        }

        // Get TK labels
        $result['tk_labels'] = $this->getTkLabelsForObject($objectId, $objectType);

        return $result;
    }

    protected function isStaffUser(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return DB::table('user')
            ->join('aclUserGroup', 'user.id', '=', 'aclUserGroup.user_id')
            ->where('user.id', $userId)
            ->whereIn('aclUserGroup.group_id', [RightsConstants::ADMINISTRATOR_GROUP_ID, RightsConstants::EDITOR_GROUP_ID])
            ->exists();
    }

    protected function isResearcherUser(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return DB::table('research_researcher')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->exists();
    }

    // =========================================================================
    // ORPHAN WORKS
    // =========================================================================

    public function getOrphanWork(int $objectId): ?array
    {
        $row = DB::table('rights_orphan_work')
            ->where('object_id', $objectId)
            ->first();

        if (!$row) {
            return null;
        }

        $record = (array) $row;
        $record['search_sources'] = DB::table('rights_orphan_search_source')
            ->where('orphan_work_id', $row->id)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        return $record;
    }

    public function saveOrphanWork(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $userId = sfContext::getInstance()->getUser()->getAttribute('user_id');

        $workData = [
            'object_id' => $data['object_id'],
            'status' => $data['status'] ?? 'suspected',
            'work_type' => $data['work_type'] ?? null,
            'work_title' => $data['work_title'] ?? null,
            'creator_name' => $data['creator_name'] ?? null,
            'search_started_date' => $data['search_started_date'] ?? null,
            'search_completed_date' => $data['search_completed_date'] ?? null,
            'search_conducted_by' => $data['search_conducted_by'] ?? null,
            'search_methodology' => $data['search_methodology'] ?? null,
            'sources_searched' => $data['sources_searched'] ?? null,
            'evidence_summary' => $data['evidence_summary'] ?? null,
            'jurisdiction' => $data['jurisdiction'] ?? null,
            'updated_by' => $userId,
            'updated_at' => $now,
        ];

        if (!empty($data['id'])) {
            DB::table('rights_orphan_work')->where('id', $data['id'])->update($workData);
            return $data['id'];
        }

        $workData['created_by'] = $userId;
        $workData['created_at'] = $now;
        return DB::table('rights_orphan_work')->insertGetId($workData);
    }

    // =========================================================================
    // AUDIT LOG
    // =========================================================================

    protected function logAudit(int $objectId, string $objectType, string $action, string $entityType, int $entityId): void
    {
        $user = sfContext::getInstance()->getUser();
        $request = sfContext::getInstance()->getRequest();

        DB::table('rights_audit_log')->insert([
            'object_id' => $objectId,
            'object_type' => $objectType,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $user->getAttribute('user_id'),
            'user_name' => $user->getAttribute('username'),
            'ip_address' => $request->getRemoteAddress(),
            'user_agent' => $request->getHttpHeader('User-Agent'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    public function getStatistics(): array
    {
        return [
            'total_rights_records' => DB::table('rights_record')->count(),
            'by_basis' => DB::table('rights_record')
                ->select('basis', DB::raw('COUNT(*) as count'))
                ->groupBy('basis')
                ->pluck('count', 'basis')
                ->toArray(),
            'active_embargoes' => DB::table('rights_embargo')
                ->whereNull('released_at')
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', date('Y-m-d'));
                })
                ->count(),
            'orphan_works' => DB::table('rights_orphan_work')
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'tk_assignments' => DB::table('rights_object_tk_label')->count(),
        ];
    }

    // =========================================================================
    // FORM OPTIONS
    // =========================================================================

    public function getFormOptions(): array
    {
        return [
            'basis_options' => $this->taxonomyService->getRightsBasis(false),
            'copyright_status_options' => $this->taxonomyService->getCopyrightStatuses(false),
            'act_options' => $this->taxonomyService->getActTypes(false),
            'restriction_options' => $this->taxonomyService->getRestrictionTypes(false),
            'embargo_type_options' => $this->taxonomyService->getEmbargoTypes(false),
            'embargo_reason_options' => $this->taxonomyService->getEmbargoReasons(false),
            'rights_statements' => $this->getRightsStatements(),
            'cc_licenses' => $this->getCcLicenses(),
            'tk_labels' => $this->getTkLabels(),
        ];
    }
}
