<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Jurisdiction-Specific Privacy Rules Service
 * Contains lawful bases and special categories for each supported jurisdiction.
 * Extracted from PrivacyService for better separation of concerns.
 */
class PrivacyJurisdictionService
{
    private static ?array $jurisdictionCache = null;

    /**
     * Get all supported jurisdictions with their configurations
     */
    public static function getJurisdictions(): array
    {
        if (self::$jurisdictionCache !== null) {
            return self::$jurisdictionCache;
        }

        $rows = DB::table('privacy_jurisdiction')
            ->orderBy('sort_order')
            ->get();

        self::$jurisdictionCache = [];
        foreach ($rows as $row) {
            self::$jurisdictionCache[$row->code] = [
                'id' => $row->id,
                'name' => $row->name,
                'full_name' => $row->full_name,
                'country' => $row->country,
                'region' => $row->region,
                'regulator' => $row->regulator,
                'regulator_url' => $row->regulator_url,
                'dsar_days' => $row->dsar_days,
                'breach_hours' => $row->breach_hours,
                'effective_date' => $row->effective_date,
                'related_laws' => $row->related_laws ? json_decode($row->related_laws, true) : [],
                'icon' => $row->icon,
                'is_active' => (bool)$row->is_active
            ];
        }
        return self::$jurisdictionCache;
    }

    /**
     * Get jurisdiction config by code
     */
    public static function getJurisdictionConfig(string $code): ?array
    {
        return self::getJurisdictions()[$code] ?? null;
    }

    /**
     * Get African jurisdictions only
     */
    public static function getAfricanJurisdictions(): array
    {
        return array_filter(self::getJurisdictions(), fn($j) => $j['region'] === 'Africa');
    }

    /**
     * Get lawful bases for a jurisdiction
     */
    public static function getLawfulBases(string $jurisdiction = 'popia'): array
    {
        return match($jurisdiction) {
            'popia' => self::getPOPIALawfulBases(),
            'ndpa' => self::getNDPALawfulBases(),
            'kenya_dpa' => self::getKenyaLawfulBases(),
            'gdpr' => self::getGDPRLawfulBases(),
            'pipeda' => [
                'consent' => ['code' => 'PIPEDA 4.3', 'label' => 'Consent', 'description' => 'Knowledge and consent'],
                'without_consent' => ['code' => 'PIPEDA 7', 'label' => 'Without Consent', 'description' => 'Permitted collection without consent']
            ],
            'ccpa' => [
                'business_purpose' => ['code' => 'CCPA §1798.140(e)', 'label' => 'Business Purpose', 'description' => 'Disclosed business purpose'],
                'service_provider' => ['code' => 'CCPA §1798.140(ag)', 'label' => 'Service Provider', 'description' => 'Service provider processing']
            ],
            default => self::getPOPIALawfulBases()
        };
    }

    /**
     * Get request types for a specific jurisdiction
     */
    public static function getRequestTypes(string $jurisdiction = 'popia'): array
    {
        $common = [
            'access' => 'Right of Access',
            'rectification' => 'Right to Rectification',
            'erasure' => 'Right to Erasure/Deletion',
            'restriction' => 'Right to Restriction',
            'objection' => 'Right to Object',
            'withdraw_consent' => 'Withdraw Consent'
        ];

        $jurisdiction_specific = [
            'popia' => [
                'access' => 'Right of Access (POPIA S23 / PAIA S50)',
                'rectification' => 'Right to Rectification (POPIA S24)',
                'erasure' => 'Right to Erasure (POPIA S24)',
                'objection' => 'Right to Object (POPIA S11(3))',
                'paia_access' => 'PAIA Access Request (PAIA S50)'
            ],
            'ndpa' => [
                'access' => 'Right of Access (NDPA S34)',
                'rectification' => 'Right to Rectification (NDPA S35)',
                'erasure' => 'Right to Erasure (NDPA S36)',
                'restriction' => 'Right to Restriction (NDPA S37)',
                'portability' => 'Right to Data Portability (NDPA S38)',
                'objection' => 'Right to Object (NDPA S39)',
                'automated' => 'Automated Decision Rights (NDPA S40)'
            ],
            'kenya_dpa' => [
                'access' => 'Right of Access (Kenya DPA S26)',
                'rectification' => 'Right to Rectification (Kenya DPA S27)',
                'erasure' => 'Right to Erasure (Kenya DPA S28)',
                'portability' => 'Right to Data Portability (Kenya DPA S29)'
            ],
            'gdpr' => [
                'access' => 'Right of Access (GDPR Art.15)',
                'rectification' => 'Right to Rectification (GDPR Art.16)',
                'erasure' => 'Right to Erasure (GDPR Art.17)',
                'restriction' => 'Right to Restriction (GDPR Art.18)',
                'portability' => 'Right to Data Portability (GDPR Art.20)',
                'objection' => 'Right to Object (GDPR Art.21)',
                'automated' => 'Automated Decision Rights (GDPR Art.22)'
            ],
            'pipeda' => [
                'access' => 'Right of Access (PIPEDA Principle 4.9)',
                'rectification' => 'Right to Rectification (PIPEDA Principle 4.9.5)',
                'withdraw_consent' => 'Withdraw Consent (PIPEDA Principle 4.3.8)'
            ],
            'ccpa' => [
                'access' => 'Right to Know (CCPA §1798.100)',
                'erasure' => 'Right to Delete (CCPA §1798.105)',
                'opt_out' => 'Right to Opt-Out of Sale (CCPA §1798.120)',
                'non_discrimination' => 'Right to Non-Discrimination (CCPA §1798.125)',
                'correct' => 'Right to Correct (CPRA §1798.106)',
                'limit_use' => 'Right to Limit Use (CPRA §1798.121)'
            ]
        ];

        return array_merge($common, $jurisdiction_specific[$jurisdiction] ?? []);
    }

    // =====================
    // POPIA-Specific (South Africa)
    // =====================

    /**
     * POPIA Section 11 - Lawful Processing Conditions
     */
    public static function getPOPIALawfulBases(): array
    {
        return [
            'consent' => [
                'code' => 'POPIA S11(1)(a)',
                'label' => 'Consent',
                'description' => 'Data subject has consented to the processing'
            ],
            'contract' => [
                'code' => 'POPIA S11(1)(b)',
                'label' => 'Contractual Necessity',
                'description' => 'Processing necessary for contract performance'
            ],
            'legal_obligation' => [
                'code' => 'POPIA S11(1)(c)',
                'label' => 'Legal Obligation',
                'description' => 'Processing required to comply with law'
            ],
            'vital_interests' => [
                'code' => 'POPIA S11(1)(d)',
                'label' => 'Vital Interests',
                'description' => 'Protecting vital interests of data subject'
            ],
            'public_body' => [
                'code' => 'POPIA S11(1)(e)',
                'label' => 'Public Body Function',
                'description' => 'For proper performance of public law duty'
            ],
            'legitimate_interests' => [
                'code' => 'POPIA S11(1)(f)',
                'label' => 'Legitimate Interests',
                'description' => 'Necessary for pursuing legitimate interests'
            ]
        ];
    }

    /**
     * POPIA Special Personal Information (Section 26-33)
     */
    public static function getPOPIASpecialCategories(): array
    {
        return [
            'religious_beliefs' => 'Religious or philosophical beliefs',
            'race_ethnicity' => 'Race or ethnic origin',
            'trade_union' => 'Trade union membership',
            'political_opinions' => 'Political persuasion',
            'health' => 'Health or sex life',
            'biometric' => 'Biometric information',
            'criminal' => 'Criminal behaviour (alleged or convicted)'
        ];
    }

    /**
     * PAIA Request Types (Promotion of Access to Information Act)
     */
    public static function getPAIARequestTypes(): array
    {
        return [
            'section_18' => [
                'code' => 'PAIA S18',
                'label' => 'Request for access to record of public body',
                'days' => 30
            ],
            'section_22' => [
                'code' => 'PAIA S22',
                'label' => 'Fees payable',
                'days' => 30
            ],
            'section_23' => [
                'code' => 'PAIA S23',
                'label' => 'Access request to private body',
                'days' => 30
            ],
            'section_50' => [
                'code' => 'PAIA S50',
                'label' => 'Request for access to record of private body',
                'days' => 30
            ],
            'section_77' => [
                'code' => 'PAIA S77',
                'label' => 'Internal appeal',
                'days' => 30
            ]
        ];
    }

    /**
     * NARSSA compliance links (National Archives and Records Service)
     */
    public static function getNARSSARequirements(): array
    {
        return [
            'file_plan' => [
                'regulation' => 'NARS Regulation 4',
                'description' => 'Approved file plan required'
            ],
            'disposal_authority' => [
                'regulation' => 'NARS Regulation 5',
                'description' => 'Disposal authority from National Archivist'
            ],
            'transfer' => [
                'regulation' => 'NARS Regulation 6',
                'description' => 'Transfer of records to archives repository'
            ],
            'electronic_records' => [
                'regulation' => 'NARS Regulation 7',
                'description' => 'Management of electronic records'
            ]
        ];
    }

    // =====================
    // NDPA-Specific (Nigeria)
    // =====================

    /**
     * NDPA Lawful Bases (Nigeria)
     */
    public static function getNDPALawfulBases(): array
    {
        return [
            'consent' => [
                'code' => 'NDPA S25(1)(a)',
                'label' => 'Consent',
                'description' => 'Data subject has given consent'
            ],
            'contract' => [
                'code' => 'NDPA S25(1)(b)',
                'label' => 'Contract Performance',
                'description' => 'Necessary for contract with data subject'
            ],
            'legal_obligation' => [
                'code' => 'NDPA S25(1)(c)',
                'label' => 'Legal Obligation',
                'description' => 'Compliance with legal obligation'
            ],
            'vital_interests' => [
                'code' => 'NDPA S25(1)(d)',
                'label' => 'Vital Interests',
                'description' => 'Protect vital interests'
            ],
            'public_interest' => [
                'code' => 'NDPA S25(1)(e)',
                'label' => 'Public Interest',
                'description' => 'Performance of task in public interest'
            ],
            'legitimate_interests' => [
                'code' => 'NDPA S25(1)(f)',
                'label' => 'Legitimate Interests',
                'description' => 'Legitimate interests of controller'
            ]
        ];
    }

    /**
     * NDPA Data Subject Rights
     */
    public static function getNDPARights(): array
    {
        return [
            'access' => 'Right of access (S34)',
            'rectification' => 'Right to rectification (S35)',
            'erasure' => 'Right to erasure (S36)',
            'restriction' => 'Right to restriction (S37)',
            'portability' => 'Right to data portability (S38)',
            'objection' => 'Right to object (S39)',
            'automated' => 'Rights related to automated decision-making (S40)'
        ];
    }

    // =====================
    // Kenya DPA-Specific
    // =====================

    /**
     * Kenya DPA Lawful Bases
     */
    public static function getKenyaLawfulBases(): array
    {
        return [
            'consent' => [
                'code' => 'Kenya DPA S30(1)(a)',
                'label' => 'Consent',
                'description' => 'Data subject has consented'
            ],
            'contract' => [
                'code' => 'Kenya DPA S30(1)(b)',
                'label' => 'Contract',
                'description' => 'Necessary for contract performance'
            ],
            'legal_obligation' => [
                'code' => 'Kenya DPA S30(1)(c)',
                'label' => 'Legal Obligation',
                'description' => 'Compliance with legal obligation'
            ],
            'vital_interests' => [
                'code' => 'Kenya DPA S30(1)(d)',
                'label' => 'Vital Interests',
                'description' => 'Protect vital interests'
            ],
            'public_interest' => [
                'code' => 'Kenya DPA S30(1)(e)',
                'label' => 'Public Interest',
                'description' => 'Public interest or official authority'
            ],
            'legitimate_interests' => [
                'code' => 'Kenya DPA S30(1)(f)',
                'label' => 'Legitimate Interests',
                'description' => 'Legitimate interests pursued by controller'
            ]
        ];
    }

    // =====================
    // GDPR-Specific (EU)
    // =====================

    /**
     * GDPR Article 6 - Lawful Bases
     */
    public static function getGDPRLawfulBases(): array
    {
        return [
            'consent' => [
                'code' => 'GDPR Art.6(1)(a)',
                'label' => 'Consent',
                'description' => 'Data subject has given consent'
            ],
            'contract' => [
                'code' => 'GDPR Art.6(1)(b)',
                'label' => 'Contract',
                'description' => 'Necessary for contract performance'
            ],
            'legal_obligation' => [
                'code' => 'GDPR Art.6(1)(c)',
                'label' => 'Legal Obligation',
                'description' => 'Compliance with legal obligation'
            ],
            'vital_interests' => [
                'code' => 'GDPR Art.6(1)(d)',
                'label' => 'Vital Interests',
                'description' => 'Protect vital interests'
            ],
            'public_task' => [
                'code' => 'GDPR Art.6(1)(e)',
                'label' => 'Public Task',
                'description' => 'Task in public interest or official authority'
            ],
            'legitimate_interests' => [
                'code' => 'GDPR Art.6(1)(f)',
                'label' => 'Legitimate Interests',
                'description' => 'Legitimate interests of controller'
            ]
        ];
    }

    /**
     * GDPR Article 9 - Special Category Data
     */
    public static function getGDPRSpecialCategories(): array
    {
        return [
            'racial_ethnic' => 'Racial or ethnic origin',
            'political' => 'Political opinions',
            'religious' => 'Religious or philosophical beliefs',
            'trade_union' => 'Trade union membership',
            'genetic' => 'Genetic data',
            'biometric' => 'Biometric data for identification',
            'health' => 'Health data',
            'sex_life' => 'Sex life or sexual orientation'
        ];
    }

    /**
     * Get ID types
     */
    public static function getIdTypes(): array
    {
        return [
            'sa_id' => 'South African ID',
            'ng_nin' => 'Nigerian NIN',
            'ke_id' => 'Kenyan ID',
            'passport' => 'Passport',
            'drivers_license' => 'Driver\'s License',
            'other' => 'Other'
        ];
    }
}
