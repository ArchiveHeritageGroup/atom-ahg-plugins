<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CCO Taxonomy Service.
 *
 * Loads CCO/CDWA controlled vocabularies from AtoM taxonomy tables.
 * Replaces static arrays with database-driven terms.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class CcoTaxonomyService
{
    // Taxonomy name constants
    public const CREATOR_ROLE = 'Creator Role (CCO)';
    public const ATTRIBUTION_QUALIFIER = 'Attribution Qualifier (CCO)';
    public const DATE_QUALIFIER = 'Date Qualifier (CCO)';
    public const CONDITION_TERM = 'Condition Term (CCO)';
    public const SUBJECT_TYPE = 'Subject Type (CCO)';
    public const INSCRIPTION_TYPE = 'Inscription Type (CCO)';
    public const RELATED_WORK_TYPE = 'Related Work Type (CCO)';
    public const RIGHTS_TYPE = 'Rights Type (CCO)';
    public const WORK_TYPE = 'Work Type (CCO)';
    public const MATERIAL = 'Material (CCO)';
    public const TECHNIQUE = 'Technique (CCO)';
    protected string $culture;

    protected static array $taxonomyIdCache = [];
    protected static array $termsCache = [];

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    /**
     * Get taxonomy ID by name.
     */
    public function getTaxonomyId(string $taxonomyName): ?int
    {
        $cacheKey = $taxonomyName.'_'.$this->culture;

        if (isset(self::$taxonomyIdCache[$cacheKey])) {
            return self::$taxonomyIdCache[$cacheKey];
        }

        $id = DB::table('taxonomy')
            ->join('taxonomy_i18n', 'taxonomy.id', '=', 'taxonomy_i18n.id')
            ->where('taxonomy_i18n.name', $taxonomyName)
            ->where('taxonomy_i18n.culture', $this->culture)
            ->value('taxonomy.id');

        if (!$id) {
            // Try without culture filter
            $id = DB::table('taxonomy')
                ->join('taxonomy_i18n', 'taxonomy.id', '=', 'taxonomy_i18n.id')
                ->where('taxonomy_i18n.name', $taxonomyName)
                ->value('taxonomy.id');
        }

        self::$taxonomyIdCache[$cacheKey] = $id;

        return $id;
    }

    /**
     * Get terms for a taxonomy as choices array for forms.
     * Excludes root terms (terms with parent_id = NULL).
     */
    public function getTermsAsChoices(string $taxonomyName, bool $includeEmpty = true): array
    {
        $cacheKey = $taxonomyName.'_choices_'.$this->culture.'_'.($includeEmpty ? '1' : '0');

        if (isset(self::$termsCache[$cacheKey])) {
            return self::$termsCache[$cacheKey];
        }

        $taxonomyId = $this->getTaxonomyId($taxonomyName);

        if (!$taxonomyId) {
            return $includeEmpty ? ['' => ''] : [];
        }

        $terms = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->whereNotNull('term.parent_id')  // Exclude root terms
            ->where('term_i18n.culture', $this->culture)
            ->orderBy('term_i18n.name')
            ->select('term.code', 'term_i18n.name')
            ->get();

        $choices = $includeEmpty ? ['' => ''] : [];

        foreach ($terms as $term) {
            $choices[$term->code] = $term->name;
        }

        self::$termsCache[$cacheKey] = $choices;

        return $choices;
    }

    /**
     * Get term name by code.
     */
    public function getTermName(string $taxonomyName, string $code): ?string
    {
        if (empty($code)) {
            return null;
        }

        $taxonomyId = $this->getTaxonomyId($taxonomyName);

        if (!$taxonomyId) {
            return null;
        }

        return DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term.code', $code)
            ->where('term_i18n.culture', $this->culture)
            ->value('term_i18n.name');
    }

    /**
     * Get term ID by code.
     */
    public function getTermId(string $taxonomyName, string $code): ?int
    {
        if (empty($code)) {
            return null;
        }

        $taxonomyId = $this->getTaxonomyId($taxonomyName);

        if (!$taxonomyId) {
            return null;
        }

        return DB::table('term')
            ->where('taxonomy_id', $taxonomyId)
            ->where('code', $code)
            ->value('id');
    }

    // ========================================================================
    // CONVENIENCE METHODS
    // ========================================================================

    public function getCreatorRoles(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::CREATOR_ROLE, $includeEmpty);
    }

    public function getAttributionQualifiers(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::ATTRIBUTION_QUALIFIER, $includeEmpty);
    }

    public function getDateQualifiers(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::DATE_QUALIFIER, $includeEmpty);
    }

    public function getConditionTerms(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::CONDITION_TERM, $includeEmpty);
    }

    public function getSubjectTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::SUBJECT_TYPE, $includeEmpty);
    }

    public function getInscriptionTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::INSCRIPTION_TYPE, $includeEmpty);
    }

    public function getRelatedWorkTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::RELATED_WORK_TYPE, $includeEmpty);
    }

    public function getRightsTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::RIGHTS_TYPE, $includeEmpty);
    }

    public function getWorkTypes(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::WORK_TYPE, $includeEmpty);
    }

    public function getMaterials(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::MATERIAL, $includeEmpty);
    }

    public function getTechniques(bool $includeEmpty = true): array
    {
        return $this->getTermsAsChoices(self::TECHNIQUE, $includeEmpty);
    }

    // ========================================================================
    // LABEL GETTERS
    // ========================================================================

    public function getCreatorRoleLabel(string $code): ?string
    {
        return $this->getTermName(self::CREATOR_ROLE, $code);
    }

    public function getAttributionQualifierLabel(string $code): ?string
    {
        return $this->getTermName(self::ATTRIBUTION_QUALIFIER, $code);
    }

    public function getDateQualifierLabel(string $code): ?string
    {
        return $this->getTermName(self::DATE_QUALIFIER, $code);
    }

    public function getConditionTermLabel(string $code): ?string
    {
        return $this->getTermName(self::CONDITION_TERM, $code);
    }

    public function getSubjectTypeLabel(string $code): ?string
    {
        return $this->getTermName(self::SUBJECT_TYPE, $code);
    }

    public function getInscriptionTypeLabel(string $code): ?string
    {
        return $this->getTermName(self::INSCRIPTION_TYPE, $code);
    }

    public function getRelatedWorkTypeLabel(string $code): ?string
    {
        return $this->getTermName(self::RELATED_WORK_TYPE, $code);
    }

    public function getRightsTypeLabel(string $code): ?string
    {
        return $this->getTermName(self::RIGHTS_TYPE, $code);
    }

    public function getWorkTypeLabel(string $code): ?string
    {
        return $this->getTermName(self::WORK_TYPE, $code);
    }

    public function getMaterialLabel(string $code): ?string
    {
        return $this->getTermName(self::MATERIAL, $code);
    }

    public function getTechniqueLabel(string $code): ?string
    {
        return $this->getTermName(self::TECHNIQUE, $code);
    }

    /**
     * Clear caches (useful after adding new terms).
     */
    public static function clearCache(): void
    {
        self::$taxonomyIdCache = [];
        self::$termsCache = [];
    }
}
