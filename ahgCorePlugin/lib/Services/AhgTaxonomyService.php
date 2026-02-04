<?php

namespace ahgCorePlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AHG Taxonomy Service
 *
 * Loads controlled vocabularies from the ahg_dropdown table.
 * Replaces hardcoded dropdown arrays with database-driven terms.
 * Supports extended attributes (color, icon, sort_order).
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class AhgTaxonomyService
{
    // ========================================================================
    // TAXONOMY CODE CONSTANTS
    // ========================================================================

    // Exhibition
    public const EXHIBITION_TYPE = 'exhibition_type';
    public const EXHIBITION_STATUS = 'exhibition_status';
    public const EXHIBITION_OBJECT_STATUS = 'exhibition_object_status';

    // Request to Publish
    public const RTP_STATUS = 'rtp_status';

    // Loans
    public const LOAN_STATUS = 'loan_status';
    public const LOAN_TYPE = 'loan_type';

    // Workflow
    public const WORKFLOW_STATUS = 'workflow_status';

    // Getty/Vocabulary Links
    public const LINK_STATUS = 'link_status';

    // Spectrum
    public const SPECTRUM_PROCEDURE_STATUS = 'spectrum_procedure_status';

    // Rights
    public const RIGHTS_BASIS = 'rights_basis';
    public const COPYRIGHT_STATUS = 'copyright_status';
    public const ACT_TYPE = 'act_type';
    public const RESTRICTION_TYPE = 'restriction_type';
    public const EMBARGO_TYPE = 'embargo_type';
    public const EMBARGO_REASON = 'embargo_reason';
    public const WORK_TYPE = 'work_type';
    public const SOURCE_TYPE = 'source_type';

    // Agreements
    public const AGREEMENT_STATUS = 'agreement_status';

    // Condition Reports
    public const CONDITION_GRADE = 'condition_grade';
    public const DAMAGE_TYPE = 'damage_type';
    public const REPORT_TYPE = 'report_type';
    public const IMAGE_TYPE = 'image_type';

    // Shipping/Courier
    public const SHIPMENT_TYPE = 'shipment_type';
    public const SHIPMENT_STATUS = 'shipment_status';
    public const COST_TYPE = 'cost_type';

    // Embargo
    public const EMBARGO_STATUS = 'embargo_status';

    // Research/Visitors
    public const ID_TYPE = 'id_type';
    public const ORGANIZATION_TYPE = 'organization_type';
    public const EQUIPMENT_TYPE = 'equipment_type';
    public const EQUIPMENT_CONDITION = 'equipment_condition';
    public const WORKSPACE_PRIVACY = 'workspace_privacy';

    // Library/Bibliographic
    public const CREATOR_ROLE = 'creator_role';

    // Documents/Agreements
    public const DOCUMENT_TYPE = 'document_type';
    public const REMINDER_TYPE = 'reminder_type';

    // Export Formats
    public const RDF_FORMAT = 'rdf_format';

    // Federation
    public const FEDERATION_SYNC_DIRECTION = 'federation_sync_direction';
    public const FEDERATION_CONFLICT_RESOLUTION = 'federation_conflict_resolution';
    public const FEDERATION_HARVEST_ACTION = 'federation_harvest_action';
    public const FEDERATION_SESSION_STATUS = 'federation_session_status';
    public const FEDERATION_MAPPING_STATUS = 'federation_mapping_status';
    public const FEDERATION_CHANGE_TYPE = 'federation_change_type';
    public const FEDERATION_SEARCH_STATUS = 'federation_search_status';

    // ========================================================================
    // PROPERTIES
    // ========================================================================

    protected static array $termsCache = [];

    // ========================================================================
    // CORE METHODS
    // ========================================================================

    /**
     * Get all terms for a taxonomy with full attributes.
     * Returns array keyed by term code.
     */
    public function getTermsWithAttributes(string $taxonomy): array
    {
        $cacheKey = $taxonomy . '_attrs';

        if (isset(self::$termsCache[$cacheKey])) {
            return self::$termsCache[$cacheKey];
        }

        $terms = DB::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->select([
                'id',
                'code',
                'label as name',
                'color',
                'icon',
                'sort_order',
                'is_default',
                'metadata'
            ])
            ->get();

        $result = [];
        foreach ($terms as $term) {
            $result[$term->code] = $term;
        }

        self::$termsCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Get terms as simple choices array for forms.
     * Returns [code => label, ...] format.
     */
    public function getTermsAsChoices(string $taxonomy, bool $includeEmpty = true): array
    {
        $cacheKey = $taxonomy . '_choices_' . ($includeEmpty ? '1' : '0');

        if (isset(self::$termsCache[$cacheKey])) {
            return self::$termsCache[$cacheKey];
        }

        $terms = $this->getTermsWithAttributes($taxonomy);

        $choices = $includeEmpty ? ['' => ''] : [];

        foreach ($terms as $code => $term) {
            $choices[$code] = $term->name;
        }

        self::$termsCache[$cacheKey] = $choices;

        return $choices;
    }

    /**
     * Get term by code.
     */
    public function getTermByCode(string $taxonomy, string $code): ?object
    {
        if (empty($code)) {
            return null;
        }

        $terms = $this->getTermsWithAttributes($taxonomy);

        return $terms[$code] ?? null;
    }

    /**
     * Get term label by code.
     */
    public function getTermName(string $taxonomy, string $code): ?string
    {
        $term = $this->getTermByCode($taxonomy, $code);

        return $term->name ?? null;
    }

    /**
     * Get term color by code.
     */
    public function getTermColor(string $taxonomy, string $code): ?string
    {
        $term = $this->getTermByCode($taxonomy, $code);

        return $term->color ?? null;
    }

    /**
     * Get term ID by code.
     */
    public function getTermId(string $taxonomy, string $code): ?int
    {
        $term = $this->getTermByCode($taxonomy, $code);

        return $term->id ?? null;
    }

    /**
     * Get default term for a taxonomy.
     */
    public function getDefaultTerm(string $taxonomy): ?object
    {
        $terms = $this->getTermsWithAttributes($taxonomy);

        foreach ($terms as $term) {
            if (!empty($term->is_default)) {
                return $term;
            }
        }

        // Return first term if no default set
        return reset($terms) ?: null;
    }

    /**
     * Get all taxonomies.
     */
    public function getAllTaxonomies(): array
    {
        return DB::table('ahg_dropdown')
            ->select('taxonomy', 'taxonomy_label')
            ->groupBy('taxonomy', 'taxonomy_label')
            ->orderBy('taxonomy_label')
            ->get()
            ->all();
    }

    /**
     * Get taxonomy label by code.
     */
    public function getTaxonomyLabel(string $taxonomy): ?string
    {
        return DB::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->value('taxonomy_label');
    }

    // ========================================================================
    // CONVENIENCE METHODS - EXHIBITION
    // ========================================================================

    public function getExhibitionTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::EXHIBITION_TYPE, $includeEmpty);
    }

    public function getExhibitionStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::EXHIBITION_STATUS, $includeEmpty);
    }

    public function getExhibitionStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::EXHIBITION_STATUS);
    }

    public function getExhibitionObjectStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::EXHIBITION_OBJECT_STATUS, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - REQUEST TO PUBLISH
    // ========================================================================

    public function getRtpStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::RTP_STATUS, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - LOANS
    // ========================================================================

    public function getLoanStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::LOAN_STATUS, $includeEmpty);
    }

    public function getLoanStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::LOAN_STATUS);
    }

    public function getLoanTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::LOAN_TYPE, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - WORKFLOW
    // ========================================================================

    public function getWorkflowStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::WORKFLOW_STATUS, $includeEmpty);
    }

    public function getWorkflowStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::WORKFLOW_STATUS);
    }

    // ========================================================================
    // CONVENIENCE METHODS - LINKS
    // ========================================================================

    public function getLinkStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::LINK_STATUS, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - SPECTRUM
    // ========================================================================

    public function getSpectrumProcedureStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::SPECTRUM_PROCEDURE_STATUS, $includeEmpty);
    }

    public function getSpectrumProcedureStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::SPECTRUM_PROCEDURE_STATUS);
    }

    // ========================================================================
    // CONVENIENCE METHODS - RIGHTS
    // ========================================================================

    public function getRightsBasis(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::RIGHTS_BASIS, $includeEmpty);
    }

    public function getCopyrightStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::COPYRIGHT_STATUS, $includeEmpty);
    }

    public function getActTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::ACT_TYPE, $includeEmpty);
    }

    public function getRestrictionTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::RESTRICTION_TYPE, $includeEmpty);
    }

    public function getEmbargoTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::EMBARGO_TYPE, $includeEmpty);
    }

    public function getEmbargoReasons(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::EMBARGO_REASON, $includeEmpty);
    }

    public function getWorkTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::WORK_TYPE, $includeEmpty);
    }

    public function getSourceTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::SOURCE_TYPE, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - AGREEMENTS
    // ========================================================================

    public function getAgreementStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::AGREEMENT_STATUS, $includeEmpty);
    }

    public function getAgreementStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::AGREEMENT_STATUS);
    }

    // ========================================================================
    // CONVENIENCE METHODS - CONDITION REPORTS
    // ========================================================================

    public function getConditionGrades(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::CONDITION_GRADE, $includeEmpty);
    }

    public function getConditionGradesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::CONDITION_GRADE);
    }

    public function getDamageTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::DAMAGE_TYPE, $includeEmpty);
    }

    public function getDamageTypesWithAttributes(): array
    {
        return $this->getTermsWithAttributes(self::DAMAGE_TYPE);
    }

    public function getReportTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::REPORT_TYPE, $includeEmpty);
    }

    public function getImageTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::IMAGE_TYPE, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - SHIPPING/COURIER
    // ========================================================================

    public function getShipmentTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::SHIPMENT_TYPE, $includeEmpty);
    }

    public function getShipmentStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::SHIPMENT_STATUS, $includeEmpty);
    }

    public function getShipmentStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::SHIPMENT_STATUS);
    }

    public function getCostTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::COST_TYPE, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - EMBARGO
    // ========================================================================

    public function getEmbargoStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::EMBARGO_STATUS, $includeEmpty);
    }

    public function getEmbargoStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::EMBARGO_STATUS);
    }

    // ========================================================================
    // CONVENIENCE METHODS - RESEARCH/VISITORS
    // ========================================================================

    public function getIdTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::ID_TYPE, $includeEmpty);
    }

    public function getOrganizationTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::ORGANIZATION_TYPE, $includeEmpty);
    }

    public function getEquipmentTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::EQUIPMENT_TYPE, $includeEmpty);
    }

    public function getEquipmentConditions(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::EQUIPMENT_CONDITION, $includeEmpty);
    }

    public function getEquipmentConditionsWithColors(): array
    {
        return $this->getTermsWithAttributes(self::EQUIPMENT_CONDITION);
    }

    public function getWorkspacePrivacyOptions(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::WORKSPACE_PRIVACY, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - LIBRARY/BIBLIOGRAPHIC
    // ========================================================================

    public function getCreatorRoles(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::CREATOR_ROLE, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - DOCUMENTS/AGREEMENTS
    // ========================================================================

    public function getDocumentTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::DOCUMENT_TYPE, $includeEmpty);
    }

    public function getReminderTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::REMINDER_TYPE, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - EXPORT FORMATS
    // ========================================================================

    public function getRdfFormats(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::RDF_FORMAT, $includeEmpty);
    }

    // ========================================================================
    // CONVENIENCE METHODS - FEDERATION
    // ========================================================================

    public function getFederationSyncDirections(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::FEDERATION_SYNC_DIRECTION, $includeEmpty);
    }

    public function getFederationConflictResolutions(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::FEDERATION_CONFLICT_RESOLUTION, $includeEmpty);
    }

    public function getFederationHarvestActions(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::FEDERATION_HARVEST_ACTION, $includeEmpty);
    }

    public function getFederationSessionStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::FEDERATION_SESSION_STATUS, $includeEmpty);
    }

    public function getFederationSessionStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::FEDERATION_SESSION_STATUS);
    }

    public function getFederationMappingStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::FEDERATION_MAPPING_STATUS, $includeEmpty);
    }

    public function getFederationMappingStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::FEDERATION_MAPPING_STATUS);
    }

    public function getFederationChangeTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::FEDERATION_CHANGE_TYPE, $includeEmpty);
    }

    public function getFederationSearchStatuses(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::FEDERATION_SEARCH_STATUS, $includeEmpty);
    }

    public function getFederationSearchStatusesWithColors(): array
    {
        return $this->getTermsWithAttributes(self::FEDERATION_SEARCH_STATUS);
    }

    // ========================================================================
    // CRUD METHODS FOR MANAGEMENT UI
    // ========================================================================

    /**
     * Add a new term to a taxonomy.
     */
    public function addTerm(string $taxonomy, string $taxonomyLabel, string $code, string $label, array $options = []): int
    {
        $maxSort = DB::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->max('sort_order') ?? 0;

        $id = DB::table('ahg_dropdown')->insertGetId([
            'taxonomy' => $taxonomy,
            'taxonomy_label' => $taxonomyLabel,
            'code' => $code,
            'label' => $label,
            'color' => $options['color'] ?? null,
            'icon' => $options['icon'] ?? null,
            'sort_order' => $options['sort_order'] ?? ($maxSort + 10),
            'is_default' => $options['is_default'] ?? 0,
            'is_active' => $options['is_active'] ?? 1,
            'metadata' => isset($options['metadata']) ? json_encode($options['metadata']) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        self::clearCache();

        return $id;
    }

    /**
     * Update a term.
     */
    public function updateTerm(int $id, array $data): bool
    {
        $allowed = ['code', 'label', 'color', 'icon', 'sort_order', 'is_default', 'is_active', 'metadata'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (isset($update['metadata']) && is_array($update['metadata'])) {
            $update['metadata'] = json_encode($update['metadata']);
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        $result = DB::table('ahg_dropdown')
            ->where('id', $id)
            ->update($update);

        self::clearCache();

        return $result > 0;
    }

    /**
     * Delete a term (soft delete by setting is_active = 0).
     */
    public function deleteTerm(int $id, bool $hardDelete = false): bool
    {
        self::clearCache();

        if ($hardDelete) {
            return DB::table('ahg_dropdown')->where('id', $id)->delete() > 0;
        }

        return DB::table('ahg_dropdown')
            ->where('id', $id)
            ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /**
     * Create a new taxonomy with initial terms.
     */
    public function createTaxonomy(string $code, string $label, array $terms = []): bool
    {
        foreach ($terms as $index => $term) {
            $this->addTerm($code, $label, $term['code'], $term['label'], [
                'color' => $term['color'] ?? null,
                'icon' => $term['icon'] ?? null,
                'sort_order' => ($index + 1) * 10,
                'is_default' => $term['is_default'] ?? 0,
            ]);
        }

        return true;
    }

    /**
     * Rename a taxonomy (updates taxonomy_label for all terms).
     */
    public function renameTaxonomy(string $taxonomy, string $newLabel): bool
    {
        self::clearCache();

        return DB::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->update(['taxonomy_label' => $newLabel, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /**
     * Delete an entire taxonomy.
     */
    public function deleteTaxonomy(string $taxonomy, bool $hardDelete = false): bool
    {
        self::clearCache();

        if ($hardDelete) {
            return DB::table('ahg_dropdown')->where('taxonomy', $taxonomy)->delete() > 0;
        }

        return DB::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Clear all caches (useful after adding/modifying terms).
     */
    public static function clearCache(): void
    {
        self::$termsCache = [];
    }

    /**
     * Check if taxonomy exists and has active terms.
     */
    public function taxonomyExists(string $taxonomy): bool
    {
        return DB::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Get term count for a taxonomy.
     */
    public function getTermCount(string $taxonomy): int
    {
        return DB::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->where('is_active', 1)
            ->count();
    }
}
