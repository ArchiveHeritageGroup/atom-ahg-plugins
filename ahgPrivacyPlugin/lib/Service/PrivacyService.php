<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

class PrivacyService
{
    // =====================
    // Jurisdiction Definitions
    // =====================

    /**
     * Get all supported jurisdictions with their configurations
     */
    public static function getJurisdictions(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        
        $rows = DB::table('privacy_jurisdiction')
            ->orderBy('sort_order')
            ->get();
        
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row->code] = [
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
        return $cache;
    }

    /**
     * OLD HARDCODED - kept for reference, now using database
     */
    private static function getJurisdictionsLegacy(): array
    {
        return [
            // African Jurisdictions
            'popia' => [
                'name' => 'POPIA',
                'full_name' => 'Protection of Personal Information Act',
                'country' => 'South Africa',
                'region' => 'Africa',
                'regulator' => 'Information Regulator',
                'regulator_url' => 'https://www.justice.gov.za/inforeg/',
                'dsar_days' => 30,
                'breach_hours' => 72, // "as soon as reasonably possible"
                'effective_date' => '2021-07-01',
                'related_laws' => ['PAIA', 'ECTA', 'RICA'],
                'icon' => 'za'
            ],
            'ndpa' => [
                'name' => 'NDPA',
                'full_name' => 'Nigeria Data Protection Act',
                'country' => 'Nigeria',
                'region' => 'Africa',
                'regulator' => 'Nigeria Data Protection Commission (NDPC)',
                'regulator_url' => 'https://ndpc.gov.ng/',
                'dsar_days' => 30,
                'breach_hours' => 72,
                'effective_date' => '2023-06-14',
                'related_laws' => ['NITDA Act', 'Cybercrimes Act'],
                'icon' => 'ng'
            ],
            'kenya_dpa' => [
                'name' => 'Kenya DPA',
                'full_name' => 'Data Protection Act',
                'country' => 'Kenya',
                'region' => 'Africa',
                'regulator' => 'Office of the Data Protection Commissioner (ODPC)',
                'regulator_url' => 'https://www.odpc.go.ke/',
                'dsar_days' => 30,
                'breach_hours' => 72,
                'effective_date' => '2019-11-25',
                'related_laws' => ['Computer Misuse and Cybercrimes Act'],
                'icon' => 'ke'
            ],
            // International Jurisdictions
            'gdpr' => [
                'name' => 'GDPR',
                'full_name' => 'General Data Protection Regulation',
                'country' => 'European Union',
                'region' => 'Europe',
                'regulator' => 'Supervisory Authority (per member state)',
                'regulator_url' => 'https://edpb.europa.eu/',
                'dsar_days' => 30, // extendable by 60 days
                'breach_hours' => 72,
                'effective_date' => '2018-05-25',
                'related_laws' => ['ePrivacy Directive'],
                'icon' => 'eu'
            ],
            'pipeda' => [
                'name' => 'PIPEDA',
                'full_name' => 'Personal Information Protection and Electronic Documents Act',
                'country' => 'Canada',
                'region' => 'North America',
                'regulator' => 'Office of the Privacy Commissioner of Canada (OPC)',
                'regulator_url' => 'https://www.priv.gc.ca/',
                'dsar_days' => 30,
                'breach_hours' => 0, // "as soon as feasible"
                'effective_date' => '2000-01-01',
                'related_laws' => ['CASL', 'Provincial privacy laws'],
                'icon' => 'ca'
            ],
            'ccpa' => [
                'name' => 'CCPA/CPRA',
                'full_name' => 'California Consumer Privacy Act / California Privacy Rights Act',
                'country' => 'USA (California)',
                'region' => 'North America',
                'regulator' => 'California Privacy Protection Agency (CPPA)',
                'regulator_url' => 'https://cppa.ca.gov/',
                'dsar_days' => 45, // extendable by 45 days
                'breach_hours' => 0, // "expedient time"
                'effective_date' => '2020-01-01',
                'related_laws' => ['CPRA amendments'],
                'icon' => 'us'
            ]
        ];
    }

    /**
     * Get African jurisdictions only
     */
    /**
     * Get only enabled jurisdictions (for forms and public pages)
     */
    public function getEnabledJurisdictions(): array
    {
        $enabled = DB::table('privacy_config')
            ->where('is_active', 1)
            ->pluck('jurisdiction')
            ->toArray();
        
        // If none configured, default to popia
        if (empty($enabled)) {
            return ['popia' => self::getJurisdictions()['popia']];
        }
        
        return array_filter(
            self::getJurisdictions(),
            fn($code) => in_array($code, $enabled),
            ARRAY_FILTER_USE_KEY
        );
    }


    public static function getAfricanJurisdictions(): array
    {
        return array_filter(self::getJurisdictions(), fn($j) => $j['region'] === 'Africa');
    }

    /**
     * Get jurisdiction config
     */
    public static function getJurisdictionConfig(string $code): ?array
    {
        return self::getJurisdictions()[$code] ?? null;
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

    // =====================
    // DSAR Request Types (All Jurisdictions)
    // =====================

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

    // =====================
    // Configuration
    // =====================

    public function getConfig(string $jurisdiction = null, bool $includeInactive = false): ?object
    {
        $query = DB::table('privacy_config');
        if (!$includeInactive) {
            $query->where('is_active', 1);
        }
        if ($jurisdiction) {
            $query->where('jurisdiction', $jurisdiction);
        }
        return $query->first();
    }

    public function saveConfig(array $data): bool
    {
        $existing = DB::table('privacy_config')
            ->where('jurisdiction', $data['jurisdiction'] ?? 'popia')
            ->first();

        $record = [
            'jurisdiction' => $data['jurisdiction'] ?? 'popia',
            'organization_name' => $data['organization_name'] ?? null,
            'registration_number' => (!empty($data['registration_number'])) ? $data['registration_number'] : null,
            'data_protection_email' => $data['data_protection_email'] ?? null,
            'dsar_response_days' => $data['dsar_response_days'] ?? 30,
            'breach_notification_hours' => $data['breach_notification_hours'] ?? 72,
            'retention_default_years' => $data['retention_default_years'] ?? 5,
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'settings' => isset($data['settings']) ? json_encode($data['settings']) : null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            return DB::table('privacy_config')
                ->where('id', $existing->id)
                ->update($record) >= 0;
        }

        $record['created_at'] = date('Y-m-d H:i:s');
        return DB::table('privacy_config')->insert($record);
    }

    // =====================
    // Reference Generation
    // =====================

    public function generateReference(string $prefix = 'DSAR', string $jurisdiction = 'popia'): string
    {
        $year = date('Y');
        $month = date('m');
        $jurisdictionCode = strtoupper(substr($jurisdiction, 0, 2));
        $table = $prefix === 'DSAR' ? 'privacy_dsar' : 'privacy_breach';
        $count = DB::table($table)
            ->whereYear('created_at', $year)
            ->where('jurisdiction', $jurisdiction)
            ->count() + 1;
        return sprintf('%s-%s-%s%s-%04d', $prefix, $jurisdictionCode, $year, $month, $count);
    }

    // =====================
    // Privacy Officers / Information Officers
    // =====================

    public function getOfficers(string $jurisdiction = null): Collection
    {
        $query = DB::table('privacy_officer')->where('is_active', 1);
        if ($jurisdiction && $jurisdiction !== 'all') {
            $query->where(function($q) use ($jurisdiction) {
                $q->where('jurisdiction', $jurisdiction)
                  ->orWhere('jurisdiction', 'all');
            });
        }
        return $query->orderBy('name')->get();
    }

    public function getOfficer(int $id): ?object
    {
        return DB::table('privacy_officer')->where('id', $id)->first();
    }

    public function saveOfficer(array $data, ?int $id = null): int
    {
        $record = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => (!empty($data['phone'])) ? $data['phone'] : null,
            'title' => (!empty($data['title'])) ? $data['title'] : null,
            'jurisdiction' => $data['jurisdiction'] ?? 'all',
            'registration_number' => (!empty($data['registration_number'])) ? $data['registration_number'] : null, // For POPIA IO registration
            'appointed_date' => (!empty($data['appointed_date'])) ? $data['appointed_date'] : null,
            'user_id' => (!empty($data['user_id'])) ? (int)$data['user_id'] : null,
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            DB::table('privacy_officer')->where('id', $id)->update($record);
            return $id;
        }

        $record['created_at'] = date('Y-m-d H:i:s');
        return DB::table('privacy_officer')->insertGetId($record);
    }

    // =====================
    // DSAR Management
    // =====================

    public function getDsarList(array $filters = []): Collection
    {
        $query = DB::table('privacy_dsar as d')
            ->leftJoin('privacy_dsar_i18n as di', function ($j) {
                $j->on('di.id', '=', 'd.id')->where('di.culture', '=', 'en');
            })
            ->leftJoin('user as u', 'u.id', '=', 'd.assigned_to')
            ->select([
                'd.*', 
                'di.description', 
                'di.notes', 
                'di.response_summary',
                'u.username as assigned_username'
            ]);

        if (!empty($filters['status'])) {
            $query->where('d.status', $filters['status']);
        }
        if (!empty($filters['jurisdiction'])) {
            $query->where('d.jurisdiction', $filters['jurisdiction']);
        }
        if (!empty($filters['request_type'])) {
            $query->where('d.request_type', $filters['request_type']);
        }
        if (!empty($filters['overdue'])) {
            $query->where('d.due_date', '<', date('Y-m-d'))
                  ->whereNotIn('d.status', ['completed', 'rejected', 'withdrawn']);
        }
        if (!empty($filters['assigned_to'])) {
            $query->where('d.assigned_to', $filters['assigned_to']);
        }
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->orderByDesc('d.created_at')->get();
    }

    public function getDsar(int $id): ?object
    {
        return DB::table('privacy_dsar as d')
            ->leftJoin('privacy_dsar_i18n as di', function ($j) {
                $j->on('di.id', '=', 'd.id')->where('di.culture', '=', 'en');
            })
            ->leftJoin('user as u', 'u.id', '=', 'd.assigned_to')
            ->where('d.id', $id)
            ->select([
                'd.*', 
                'di.description', 
                'di.notes', 
                'di.response_summary',
                'u.username as assigned_username'
            ])
            ->first();
    }

    public function createDsar(array $data, ?int $userId = null): int
    {
        $jurisdiction = $data['jurisdiction'] ?? 'popia';
        $config = $this->getConfig($jurisdiction);
        $jurisdictionInfo = self::getJurisdictionConfig($jurisdiction);
        $responseDays = $config->dsar_response_days ?? $jurisdictionInfo['dsar_days'] ?? 30;
        $receivedDate = $data['received_date'] ?? date('Y-m-d');

        $dsarData = [
            'reference_number' => $this->generateReference('DSAR', $jurisdiction),
            'jurisdiction' => $jurisdiction,
            'request_type' => $data['request_type'],
            'requestor_name' => $data['requestor_name'],
            'requestor_email' => $data['requestor_email'] ?? null,
            'requestor_phone' => $data['requestor_phone'] ?? null,
            'requestor_id_type' => $data['requestor_id_type'] ?? null,
            'requestor_id_number' => $data['requestor_id_number'] ?? null,
            'requestor_address' => $data['requestor_address'] ?? null,
            'status' => 'received',
            'priority' => $data['priority'] ?? 'normal',
            'received_date' => $receivedDate,
            'due_date' => date('Y-m-d', strtotime($receivedDate . " + {$responseDays} days")),
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $id = DB::table('privacy_dsar')->insertGetId($dsarData);

        // Insert i18n
        if (!empty($data['description']) || !empty($data['notes'])) {
            DB::table('privacy_dsar_i18n')->insert([
                'id' => $id,
                'culture' => 'en',
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);
        }

        // Log activity
        $this->logDsarActivity($id, 'created', 'DSAR request created', $userId);

        $this->logAudit('create', 'PrivacyDsar', $id, [], $data, $data['reference'] ?? null);
        return $id;
    }

    public function updateDsar(int $id, array $data, ?int $userId = null): bool
    {
        $oldValues = (array)(DB::table('privacy_dsar')->where('id', $id)->first() ?? []);
        $updates = array_filter([
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'outcome' => $data['outcome'] ?? null,
            'refusal_reason' => $data['refusal_reason'] ?? null,
            'is_verified' => $data['is_verified'] ?? null,
            'fee_required' => $data['fee_required'] ?? null,
            'fee_paid' => $data['fee_paid'] ?? null,
        ], fn($v) => $v !== null);

        $updates['updated_at'] = date('Y-m-d H:i:s');

        // Handle completion
        if (isset($data['status']) && $data['status'] === 'completed') {
            $updates['completed_date'] = date('Y-m-d');
        }

        // Handle verification
        if (isset($data['is_verified']) && $data['is_verified']) {
            $updates['verified_at'] = date('Y-m-d H:i:s');
            $updates['verified_by'] = $userId;
        }

        $result = DB::table('privacy_dsar')->where('id', $id)->update($updates);

        // Update i18n
        if (!empty($data['notes']) || !empty($data['response_summary'])) {
            DB::table('privacy_dsar_i18n')->updateOrInsert(
                ['id' => $id, 'culture' => 'en'],
                array_filter([
                    'notes' => $data['notes'] ?? null,
                    'response_summary' => $data['response_summary'] ?? null
                ], fn($v) => $v !== null)
            );
        }

        // Log activity
        if (isset($data['status'])) {
            $this->logDsarActivity($id, 'status_changed', "Status changed to {$data['status']}", $userId);
        }

        return $result >= 0;
    }

    public function logDsarActivity(int $dsarId, string $action, string $details, ?int $userId = null): void
    {
        DB::table('privacy_dsar_log')->insert([
            'dsar_id' => $dsarId,
            'action' => $action,
            'details' => $details,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getDsarLogs(int $dsarId): Collection
    {
        return DB::table('privacy_dsar_log as l')
            ->leftJoin('user as u', 'u.id', '=', 'l.user_id')
            ->where('l.dsar_id', $dsarId)
            ->select(['l.*', 'u.username'])
            ->orderByDesc('l.created_at')
            ->get();
    }

    // =====================
    // Breach Management
    // =====================

    public function getBreachList(array $filters = []): Collection
    {
        $query = DB::table('privacy_breach as b')
            ->leftJoin('privacy_breach_i18n as bi', function ($j) {
                $j->on('bi.id', '=', 'b.id')->where('bi.culture', '=', 'en');
            })
            ->select(['b.*', 'bi.description', 'bi.impact_assessment', 'bi.remedial_actions']);

        if (!empty($filters['status'])) {
            $query->where('b.status', $filters['status']);
        }
        if (!empty($filters['severity'])) {
            $query->where('b.severity', $filters['severity']);
        }
        if (!empty($filters['jurisdiction'])) {
            $query->where('b.jurisdiction', $filters['jurisdiction']);
        }

        return $query->orderByDesc('b.detected_date')->get();
    }

    public function getBreach(int $id): ?object
    {
        return DB::table('privacy_breach as b')
            ->leftJoin('privacy_breach_i18n as bi', function ($j) {
                $j->on('bi.id', '=', 'b.id')->where('bi.culture', '=', 'en');
            })
            ->where('b.id', $id)
            ->select(['b.*', 'bi.description', 'bi.impact_assessment', 'bi.remedial_actions', 'bi.lessons_learned'])
            ->first();
    }

    public function createBreach(array $data, ?int $userId = null): int
    {
        $jurisdiction = $data['jurisdiction'] ?? 'popia';
        
        $breachData = [
            'reference_number' => $this->generateReference('BRE', $jurisdiction),
            'jurisdiction' => $jurisdiction,
            'breach_type' => $data['breach_type'],
            'severity' => $data['severity'] ?? 'medium',
            'status' => 'detected',
            'detected_date' => $data['detected_date'] ?? date('Y-m-d H:i:s'),
            'occurred_date' => (isset($data['occurred_date']) && $data['occurred_date'] !== '') ? $data['occurred_date'] : null,
            'data_categories_affected' => (isset($data['data_categories']) && $data['data_categories'] !== '') ? $data['data_categories'] : null,
            'data_subjects_affected' => (isset($data['records_affected']) && $data['records_affected'] !== '') ? (int)$data['records_affected'] : null,
            
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $id = DB::table('privacy_breach')->insertGetId($breachData);

        // Insert i18n
        if (!empty($data['description'])) {
            DB::table('privacy_breach_i18n')->insert([
                'id' => $id,
                'culture' => 'en',
                'description' => $data['description']
            ]);
        }

        $this->logAudit('create', 'PrivacyBreach', $id, [], $data, $data['reference'] ?? null);
        return $id;
    }

    public function updateBreach(int $id, array $data, ?int $userId = null): bool
    {
        $updates = array_filter([
            'status' => $data['status'] ?? null,
            'severity' => $data['severity'] ?? null,
            'regulator_notified' => $data['regulator_notified'] ?? null,
            'regulator_notified_date' => $data['regulator_notified_date'] ?? null,
            'subjects_notified' => $data['subjects_notified'] ?? null,
            'subjects_notified_date' => $data['subjects_notified_date'] ?? null,
            'contained_date' => $data['contained_date'] ?? null,
            'resolved_date' => $data['resolved_date'] ?? null,
        ], fn($v) => $v !== null);

        $updates['updated_at'] = date('Y-m-d H:i:s');

        $result = DB::table('privacy_breach')->where('id', $id)->update($updates);

        // Update i18n
        $i18nUpdates = array_filter([
            'impact_assessment' => $data['impact_assessment'] ?? null,
            'remedial_actions' => $data['remedial_actions'] ?? null,
            'lessons_learned' => $data['lessons_learned'] ?? null
        ], fn($v) => $v !== null);

        if (!empty($i18nUpdates)) {
            DB::table('privacy_breach_i18n')->updateOrInsert(
                ['id' => $id, 'culture' => 'en'],
                $i18nUpdates
            );
        }

        return $result >= 0;
    }

    // =====================
    // ROPA (Processing Activities)
    // =====================

    public function getRopaList(array $filters = []): Collection
    {
        $query = DB::table('privacy_processing_activity');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['lawful_basis'])) {
            $query->where('lawful_basis', $filters['lawful_basis']);
        }
        if (!empty($filters['jurisdiction'])) {
            $query->where('jurisdiction', $filters['jurisdiction']);
        }

        return $query->orderBy('name')->get();
    }

    public function getRopa(int $id): ?object
    {
        return DB::table('privacy_processing_activity')->where('id', $id)->first();
    }

    public function saveRopa(array $data, ?int $id = null, ?int $userId = null): int
    {
        $record = [
            'name' => $data['name'],
            'purpose' => $data['purpose'] ?? null,
            'jurisdiction' => $data['jurisdiction'] ?? 'popia',
            'lawful_basis' => $data['lawful_basis'] ?? null,
            'lawful_basis_code' => $data['lawful_basis_code'] ?? null,
            'data_categories' => is_array($data['data_categories'] ?? null) 
                ? json_encode($data['data_categories']) 
                : ($data['data_categories'] ?? null),
            'data_subjects' => $data['data_subjects'] ?? null,
            'recipients' => $data['recipients'] ?? null,
            'third_countries' => (!empty($data['third_countries'])) ? (is_array($data['third_countries']) ? json_encode($data['third_countries']) : $data['third_countries']) : null,
            'transfers' => $data['cross_border_safeguards'] ?? null,
            'retention_period' => $data['retention_period'] ?? null,
            'security_measures' => $data['security_measures'] ?? null,
            'dpia_required' => $data['dpia_required'] ?? 0,
            'dpia_completed' => $data['dpia_completed'] ?? 0,
            'dpia_date' => (!empty($data['dpia_date'])) ? $data['dpia_date'] : null,
            'owner' => $data['responsible_person'] ?? null,
            'department' => $data['department'] ?? null,
            'assigned_officer_id' => !empty($data['assigned_officer_id']) ? (int)$data['assigned_officer_id'] : null,
            
            'status' => $data['status'] ?? 'draft',
            'next_review_date' => (!empty($data['next_review_date'])) ? $data['next_review_date'] : null,
            
            
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            DB::table('privacy_processing_activity')->where('id', $id)->update($record);
            return $id;
        }

        $record['created_by'] = $userId;
        $record['created_at'] = date('Y-m-d H:i:s');
        return DB::table('privacy_processing_activity')->insertGetId($record);
    }

    // =====================
    // Consent Records
    // =====================

    public function getConsentRecords(array $filters = []): Collection
    {
        $query = DB::table('privacy_consent_record');

        if (!empty($filters['purpose'])) {
            $query->where('purpose', $filters['purpose']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('consent_date')->get();
    }

    public function recordConsent(array $data, ?int $userId = null): int
    {
        return DB::table('privacy_consent_record')->insertGetId([
            'subject_name' => $data['subject_name'],
            'subject_email' => $data['subject_email'] ?? null,
            'subject_identifier' => $data['subject_identifier'] ?? null,
            'purpose' => $data['purpose'],
            'processing_activity_id' => $data['processing_activity_id'] ?? null,
            'consent_date' => $data['consent_date'] ?? date('Y-m-d'),
            'consent_method' => $data['consent_method'] ?? 'form',
            'consent_text' => $data['consent_text'] ?? null,
            'jurisdiction' => $data['jurisdiction'] ?? 'popia',
            'status' => 'active',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function withdrawConsent(int $id, ?string $reason = null, ?int $userId = null): bool
    {
        return DB::table('privacy_consent_record')
            ->where('id', $id)
            ->update([
                'status' => 'withdrawn',
                'withdrawn_date' => date('Y-m-d'),
                'withdrawal_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ]) > 0;
    }

    // =====================
    // PAIA Requests (South Africa specific)
    // =====================

    public function getPaiaRequests(array $filters = []): Collection
    {
        $query = DB::table('privacy_paia_request');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['section'])) {
            $query->where('paia_section', $filters['section']);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function createPaiaRequest(array $data, ?int $userId = null): int
    {
        return DB::table('privacy_paia_request')->insertGetId([
            'reference_number' => $this->generateReference('PAIA', 'popia'),
            'paia_section' => $data['paia_section'],
            'requestor_name' => $data['requestor_name'],
            'requestor_email' => $data['requestor_email'] ?? null,
            'requestor_phone' => $data['requestor_phone'] ?? null,
            'requestor_id_number' => $data['requestor_id_number'] ?? null,
            'requestor_address' => $data['requestor_address'] ?? null,
            'record_description' => $data['record_description'] ?? null,
            'access_form' => $data['access_form'] ?? 'copy',
            'status' => 'received',
            'received_date' => $data['received_date'] ?? date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    // =====================
    // Dashboard & Statistics
    // =====================

    public function getDashboardStats(string $jurisdiction = null): array
    {
        $now = date('Y-m-d');

        $dsarQuery = DB::table('privacy_dsar');
        $breachQuery = DB::table('privacy_breach');
        $ropaQuery = DB::table('privacy_processing_activity');
        $consentQuery = DB::table('privacy_consent_record');
        $complaintQuery = DB::table('privacy_complaint');

        if ($jurisdiction) {
            $dsarQuery->where('jurisdiction', $jurisdiction);
            $breachQuery->where('jurisdiction', $jurisdiction);
            $ropaQuery->where('jurisdiction', $jurisdiction);
            $consentQuery->where('jurisdiction', $jurisdiction);
            $complaintQuery->where('jurisdiction', $jurisdiction);
        }

        return [
            'dsar' => [
                'total' => (clone $dsarQuery)->count(),
                'pending' => (clone $dsarQuery)
                    ->whereNotIn('status', ['completed', 'rejected', 'withdrawn'])
                    ->count(),
                'overdue' => (clone $dsarQuery)
                    ->where('due_date', '<', $now)
                    ->whereNotIn('status', ['completed', 'rejected', 'withdrawn'])
                    ->count(),
                'completed_this_month' => (clone $dsarQuery)
                    ->where('status', 'completed')
                    ->whereMonth('completed_date', date('m'))
                    ->whereYear('completed_date', date('Y'))
                    ->count()
            ],
            'breach' => [
                'total' => (clone $breachQuery)->count(),
                'open' => (clone $breachQuery)
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count(),
                'critical' => (clone $breachQuery)
                    ->where('severity', 'critical')
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count(),
                'this_year' => (clone $breachQuery)
                    ->whereYear('detected_date', date('Y'))
                    ->count()
            ],
            'ropa' => [
                'total' => (clone $ropaQuery)->count(),
                'approved' => (clone $ropaQuery)
                    ->where('status', 'approved')
                    ->count(),
                'requiring_dpia' => (clone $ropaQuery)
                    ->where('dpia_required', 1)
                    ->where('dpia_completed', 0)
                    ->count(),
                'review_due' => (clone $ropaQuery)
                    ->where('next_review_date', '<=', date('Y-m-d', strtotime('+30 days')))
                    ->count()
            ],
            'consent' => [
                'active' => (clone $consentQuery)
                    ->where('status', 'active')
                    ->count(),
                'withdrawn_this_month' => (clone $consentQuery)
                    ->where('status', 'withdrawn')
                    ->whereMonth('withdrawn_date', date('m'))
                    ->whereYear('withdrawn_date', date('Y'))
                    ->count()
            ],
            'complaint' => [
                'total' => (clone $complaintQuery)->count(),
                'open' => (clone $complaintQuery)
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count(),
                'received_this_month' => (clone $complaintQuery)
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->count()
            ],
            'compliance_score' => $this->calculateComplianceScore($jurisdiction)
        ];
    }

    public function calculateComplianceScore(string $jurisdiction = null): int
    {
        $score = 0;

        $ropaQuery = DB::table('privacy_processing_activity');
        $dsarQuery = DB::table('privacy_dsar');
        $breachQuery = DB::table('privacy_breach');

        if ($jurisdiction) {
            $ropaQuery->where('jurisdiction', $jurisdiction);
            $dsarQuery->where('jurisdiction', $jurisdiction);
            $breachQuery->where('jurisdiction', $jurisdiction);
        }

        // ROPA completeness (30 points)
        $ropaTotal = (clone $ropaQuery)->count();
        $ropaApproved = (clone $ropaQuery)->where('status', 'approved')->count();
        $score += $ropaTotal > 0 ? round(($ropaApproved / $ropaTotal) * 30) : 0;

        // DSAR response rate (30 points)
        $dsarTotal = (clone $dsarQuery)->where('status', 'completed')->count();
        $dsarOnTime = (clone $dsarQuery)
            ->where('status', 'completed')
            ->whereColumn('completed_date', '<=', 'due_date')
            ->count();
        $score += $dsarTotal > 0 ? round(($dsarOnTime / $dsarTotal) * 30) : 30;

        // Breach handling (20 points)
        $breachOpen = (clone $breachQuery)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();
        $score += $breachOpen === 0 ? 20 : max(0, 20 - ($breachOpen * 5));

        // DPIA completion (20 points)
        $dpiaRequired = (clone $ropaQuery)->where('dpia_required', 1)->count();
        $dpiaCompleted = DB::table('privacy_processing_activity')
            ->where('dpia_required', 1)
            ->where('dpia_completed', 1);
        if ($jurisdiction) {
            $dpiaCompleted->where('jurisdiction', $jurisdiction);
        }
        $dpiaCompletedCount = $dpiaCompleted->count();
        $score += $dpiaRequired > 0 ? round(($dpiaCompletedCount / $dpiaRequired) * 20) : 20;

        return min($score, 100);
    }

    // =====================
    // Static Helpers
    // =====================

    public static function getBreachTypes(): array
    {
        return [
            'confidentiality' => 'Confidentiality Breach (unauthorized disclosure)',
            'integrity' => 'Integrity Breach (unauthorized alteration)',
            'availability' => 'Availability Breach (loss or destruction)'
        ];
    }

    public static function getSeverityLevels(): array
    {
        return [
            'low' => 'Low - Minor impact, easily contained',
            'medium' => 'Medium - Moderate impact, limited exposure',
            'high' => 'High - Significant risk, many affected',
            'critical' => 'Critical - Severe harm likely'
        ];
    }

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

    public static function getDsarStatuses(): array
    {
        return [
            'received' => 'Received',
            'verified' => 'Verified',
            'in_progress' => 'In Progress',
            'pending_info' => 'Pending Information',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn'
        ];
    }

    public static function getDsarOutcomes(): array
    {
        return [
            '' => '-- Select Outcome --',
            'granted' => 'Granted',
            'partially_granted' => 'Partially Granted',
            'refused' => 'Refused',
            'not_applicable' => 'Not Applicable'
        ];
    }

    protected function logAudit(string $action, string $entityType, int $entityId, array $oldValues, array $newValues, ?string $title = null): void
    {
        try {
            $userId = null;
            $username = null;
            if (class_exists('sfContext') && \sfContext::hasInstance()) {
                $user = \sfContext::getInstance()->getUser();
                if ($user && $user->isAuthenticated()) {
                    $userId = $user->getAttribute('user_id');
                    if ($userId) {
                        $userRecord = \Illuminate\Database\Capsule\Manager::table('user')->where('id', $userId)->first();
                        $username = $userRecord->username ?? null;
                    }
                }
            }
            $changedFields = [];
            foreach ($newValues as $key => $val) {
                if (($oldValues[$key] ?? null) !== $val) $changedFields[] = $key;
            }
            if ($action === 'delete') $changedFields = array_keys($oldValues);
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
            \Illuminate\Database\Capsule\Manager::table('ahg_audit_log')->insert([
                'uuid' => $uuid, 'user_id' => $userId, 'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'session_id' => session_id() ?: null, 'action' => $action,
                'entity_type' => $entityType, 'entity_id' => $entityId, 'entity_title' => $title,
                'module' => $this->auditModule ?? 'ahgPrivacyPlugin', 'action_name' => $action,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'changed_fields' => !empty($changedFields) ? json_encode($changedFields) : null,
                'status' => 'success', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("AUDIT ERROR: " . $e->getMessage());
        }
    }
    protected string $auditModule = 'ahgPrivacyPlugin';

    // =====================
    // Notifications
    // =====================
    public function createNotification(int $userId, string $entityType, int $entityId, string $type, string $subject, ?string $message = null, ?string $link = null, ?int $createdBy = null): int
    {
        return DB::table('privacy_notification')->insertGetId([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'notification_type' => $type,
            'subject' => $subject,
            'message' => $message,
            'link' => $link,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getUnreadNotifications(int $userId, int $limit = 10): Collection
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getNotificationCount(int $userId): int
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    public function markNotificationRead(int $id, int $userId): bool
    {
        return DB::table('privacy_notification')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function markAllNotificationsRead(int $userId): int
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }

    // =====================
    // ROPA Approval Workflow
    // =====================
    public function submitRopaForApproval(int $id, int $userId, ?int $assignedOfficerId = null): bool
    {
        $activity = $this->getRopa($id);
        if (!$activity || $activity->status !== 'draft') {
            return false;
        }

        if (!$assignedOfficerId) {
            $officer = DB::table('privacy_officer')
                ->where('is_active', 1)
                ->where('is_primary', 1)
                ->first();
            $assignedOfficerId = $officer->user_id ?? null;
        }

        DB::table('privacy_processing_activity')
            ->where('id', $id)
            ->update([
                'status' => 'pending_review',
                'submitted_at' => date('Y-m-d H:i:s'),
                'submitted_by' => $userId,
                'assigned_officer_id' => $assignedOfficerId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $this->logApprovalAction($id, 'ropa', 'submitted', 'draft', 'pending_review', null, $userId);

        if ($assignedOfficerId) {
            $this->createNotification(
                $assignedOfficerId,
                'ropa',
                $id,
                'submitted',
                'ROPA Submitted for Review: ' . $activity->name,
                'A processing activity has been submitted for your review.',
                '/privacyAdmin/ropaView/id/' . $id,
                $userId
            );
            $this->sendApprovalEmail($assignedOfficerId, 'submitted', $activity);
        }

        return true;
    }

    public function approveRopa(int $id, int $userId, ?string $comment = null): bool
    {
        $activity = $this->getRopa($id);
        if (!$activity || $activity->status !== 'pending_review') {
            return false;
        }

        DB::table('privacy_processing_activity')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $this->logApprovalAction($id, 'ropa', 'approved', 'pending_review', 'approved', $comment, $userId);

        if ($activity->created_by) {
            $this->createNotification(
                $activity->created_by,
                'ropa',
                $id,
                'approved',
                'ROPA Approved: ' . $activity->name,
                $comment ?: 'Your processing activity has been approved.',
                '/privacyAdmin/ropaView/id/' . $id,
                $userId
            );
            $this->sendApprovalEmail($activity->created_by, 'approved', $activity, $comment);
        }

        return true;
    }

    public function rejectRopa(int $id, int $userId, string $reason): bool
    {
        $activity = $this->getRopa($id);
        if (!$activity || $activity->status !== 'pending_review') {
            return false;
        }

        DB::table('privacy_processing_activity')
            ->where('id', $id)
            ->update([
                'status' => 'draft',
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejected_by' => $userId,
                'rejection_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $this->logApprovalAction($id, 'ropa', 'rejected', 'pending_review', 'draft', $reason, $userId);

        if ($activity->created_by) {
            $this->createNotification(
                $activity->created_by,
                'ropa',
                $id,
                'rejected',
                'ROPA Requires Changes: ' . $activity->name,
                'Reason: ' . $reason,
                '/privacyAdmin/ropaEdit/id/' . $id,
                $userId
            );
            $this->sendApprovalEmail($activity->created_by, 'rejected', $activity, $reason);
        }

        return true;
    }

    protected function logApprovalAction(int $entityId, string $entityType, string $action, ?string $oldStatus, ?string $newStatus, ?string $comment, int $userId): int
    {
        return DB::table('privacy_approval_log')->insertGetId([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getApprovalHistory(int $entityId, string $entityType = 'ropa'): Collection
    {
        return DB::table('privacy_approval_log as l')
            ->leftJoin('user as u', 'u.id', '=', 'l.user_id')
            ->where('l.entity_type', $entityType)
            ->where('l.entity_id', $entityId)
            ->select(['l.*', 'u.username', 'u.email'])
            ->orderByDesc('l.created_at')
            ->get();
    }

    public function getPrivacyOfficers(): Collection
    {
        return DB::table('privacy_officer')
            ->where('is_active', 1)
            ->whereNotNull('user_id')
            ->get();
    }

    public function isPrivacyOfficer(int $userId): bool
    {
        return DB::table('privacy_officer')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->exists();
    }

    protected function sendApprovalEmail(int $userId, string $action, $activity, ?string $comment = null): void
    {
        $user = DB::table('user')->find($userId);
        if (!$user || empty($user->email)) {
            return;
        }

        $subjects = [
            'submitted' => 'ROPA Submitted for Review: ' . $activity->name,
            'approved' => 'ROPA Approved: ' . $activity->name,
            'rejected' => 'ROPA Requires Changes: ' . $activity->name
        ];

        $subject = $subjects[$action] ?? 'ROPA Update: ' . $activity->name;

        try {
            $baseUrl = sfConfig::get('app_siteBaseUrl', '');
            $link = $baseUrl . '/privacyAdmin/ropaView/id/' . $activity->id;
            
            $body = $this->buildApprovalEmailBody($action, $activity, $comment, $user, $link);
            
            $mailer = sfContext::getInstance()->getMailer();
            $message = $mailer->compose(
                sfConfig::get('app_mail_from', 'noreply@example.com'),
                $user->email,
                $subject,
                $body
            );
            $message->setContentType('text/html');
            $mailer->send($message);

            DB::table('privacy_notification')
                ->where('user_id', $userId)
                ->where('entity_type', 'ropa')
                ->where('entity_id', $activity->id)
                ->where('notification_type', $action)
                ->orderByDesc('created_at')
                ->limit(1)
                ->update(['email_sent' => 1, 'email_sent_at' => date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {
            error_log('Privacy email failed: ' . $e->getMessage());
        }
    }

    protected function buildApprovalEmailBody(string $action, $activity, ?string $comment, $user, string $link): string
    {
        $html = '<html><body style="font-family: Arial, sans-serif;">';
        $html .= '<h2>Processing Activity Update</h2>';
        $html .= '<p>Dear ' . htmlspecialchars($user->username ?? 'User') . ',</p>';

        switch ($action) {
            case 'submitted':
                $html .= '<p>A processing activity has been submitted for your review:</p>';
                break;
            case 'approved':
                $html .= '<p>Your processing activity has been <strong style="color: green;">approved</strong>:</p>';
                break;
            case 'rejected':
                $html .= '<p>Your processing activity requires <strong style="color: red;">changes</strong>:</p>';
                break;
        }

        $html .= '<div style="background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px;">';
        $html .= '<strong>' . htmlspecialchars($activity->name) . '</strong><br>';
        $html .= '<small>Purpose: ' . htmlspecialchars(substr($activity->purpose ?? '', 0, 100)) . '...</small>';
        $html .= '</div>';

        if ($comment) {
            $html .= '<p><strong>Comment:</strong><br>' . nl2br(htmlspecialchars($comment)) . '</p>';
        }

        $html .= '<p><a href="' . $link . '" style="display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px;">View Details</a></p>';
        $html .= '<p style="color: #666; font-size: 12px;">This is an automated message from the Privacy Management System.</p>';
        $html .= '</body></html>';

        return $html;
    }
}
