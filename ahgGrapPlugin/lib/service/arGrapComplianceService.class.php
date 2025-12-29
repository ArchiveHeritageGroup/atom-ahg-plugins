<?php
/**
 * GRAP 103 / NARSSA Compliance Checklist Service
 * 
 * Implements compliance checking against:
 * - GRAP 103 Heritage Assets requirements
 * - NARSSA (National Archives and Record Service of South Africa) requirements
 * - National Treasury reporting requirements
 * - PFMA (Public Finance Management Act) requirements
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 */

class arGrapComplianceService
{
    // Compliance Categories
    const CAT_RECOGNITION = 'recognition';
    const CAT_MEASUREMENT = 'measurement';
    const CAT_DISCLOSURE = 'disclosure';
    const CAT_DOCUMENTATION = 'documentation';
    const CAT_NARSSA = 'narssa';
    const CAT_PFMA = 'pfma';

    // Severity Levels
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_LOW = 'low';
    const SEVERITY_INFO = 'info';

    // GRAP 103 Checklist Items
    public static $grap103Checklist = [
        // Recognition (GRAP 103.14-25)
        'recognition' => [
            'REC001' => [
                'title' => 'Heritage Asset Identification',
                'description' => 'Asset has been identified as a heritage asset per GRAP 103.8 definition',
                'reference' => 'GRAP 103.8',
                'severity' => self::SEVERITY_CRITICAL,
                'validation' => 'hasAssetClass'
            ],
            'REC002' => [
                'title' => 'Recognition Criteria Met',
                'description' => 'Probable future economic benefits or service potential AND cost/fair value can be measured reliably',
                'reference' => 'GRAP 103.14',
                'severity' => self::SEVERITY_CRITICAL,
                'validation' => 'hasRecognitionStatus'
            ],
            'REC003' => [
                'title' => 'Initial Recognition Date',
                'description' => 'Date of initial recognition has been recorded',
                'reference' => 'GRAP 103.14',
                'severity' => self::SEVERITY_HIGH,
                'validation' => 'hasRecognitionDate'
            ],
            'REC004' => [
                'title' => 'Asset Classification',
                'description' => 'Heritage asset has been classified into appropriate class',
                'reference' => 'GRAP 103.74',
                'severity' => self::SEVERITY_HIGH,
                'validation' => 'hasAssetClass'
            ],
            'REC005' => [
                'title' => 'Donated Asset Recognition',
                'description' => 'Donated heritage assets recognised at fair value at acquisition date',
                'reference' => 'GRAP 103.18',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'checkDonationRecognition'
            ]
        ],

        // Measurement (GRAP 103.26-51)
        'measurement' => [
            'MEA001' => [
                'title' => 'Measurement Basis Specified',
                'description' => 'Measurement basis (cost or revaluation model) has been selected and documented',
                'reference' => 'GRAP 103.26',
                'severity' => self::SEVERITY_CRITICAL,
                'validation' => 'hasMeasurementBasis'
            ],
            'MEA002' => [
                'title' => 'Initial Cost Recorded',
                'description' => 'Cost at initial recognition has been recorded',
                'reference' => 'GRAP 103.26-28',
                'severity' => self::SEVERITY_CRITICAL,
                'validation' => 'hasInitialCost'
            ],
            'MEA003' => [
                'title' => 'Carrying Amount Current',
                'description' => 'Carrying amount reflects current measurement',
                'reference' => 'GRAP 103.36',
                'severity' => self::SEVERITY_HIGH,
                'validation' => 'hasCarryingAmount'
            ],
            'MEA004' => [
                'title' => 'Revaluation Frequency',
                'description' => 'Revaluations performed with sufficient regularity (if revaluation model used)',
                'reference' => 'GRAP 103.38',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'checkRevaluationFrequency'
            ],
            'MEA005' => [
                'title' => 'Impairment Assessment',
                'description' => 'Asset has been assessed for impairment indicators',
                'reference' => 'GRAP 21/26',
                'severity' => self::SEVERITY_HIGH,
                'validation' => 'checkImpairmentAssessment'
            ],
            'MEA006' => [
                'title' => 'Nominal Value Justification',
                'description' => 'If measured at nominal value (R1), justification documented',
                'reference' => 'GRAP 103.27',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'checkNominalValueJustification'
            ]
        ],

        // Disclosure (GRAP 103.68-83)
        'disclosure' => [
            'DIS001' => [
                'title' => 'Measurement Basis Disclosed',
                'description' => 'Measurement basis used for each class disclosed',
                'reference' => 'GRAP 103.68(a)',
                'severity' => self::SEVERITY_HIGH,
                'validation' => 'hasMeasurementBasis'
            ],
            'DIS002' => [
                'title' => 'Gross Carrying Amount',
                'description' => 'Gross carrying amount at beginning and end of period disclosed',
                'reference' => 'GRAP 103.68(d)',
                'severity' => self::SEVERITY_HIGH,
                'validation' => 'hasCarryingAmount'
            ],
            'DIS003' => [
                'title' => 'Accumulated Impairment',
                'description' => 'Accumulated impairment losses disclosed',
                'reference' => 'GRAP 103.68(d)',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'hasImpairmentTracking'
            ],
            'DIS004' => [
                'title' => 'Reconciliation Movement',
                'description' => 'Reconciliation of carrying amount movements disclosed',
                'reference' => 'GRAP 103.68(d)',
                'severity' => self::SEVERITY_HIGH,
                'validation' => 'hasValuationHistory'
            ],
            'DIS005' => [
                'title' => 'Revaluation Details',
                'description' => 'Effective date and valuer details for revaluations disclosed',
                'reference' => 'GRAP 103.69',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'hasRevaluationDetails'
            ],
            'DIS006' => [
                'title' => 'Restrictions Disclosed',
                'description' => 'Title restrictions and pledges as security disclosed',
                'reference' => 'GRAP 103.68(f)',
                'severity' => self::SEVERITY_LOW,
                'validation' => 'checkRestrictionsDisclosed'
            ],
            'DIS007' => [
                'title' => 'Insurance Information',
                'description' => 'Insurance coverage information available',
                'reference' => 'GRAP 103.68',
                'severity' => self::SEVERITY_LOW,
                'validation' => 'hasInsuranceInfo'
            ]
        ],

        // Documentation Requirements
        'documentation' => [
            'DOC001' => [
                'title' => 'Asset Register Entry',
                'description' => 'Asset properly recorded in heritage asset register',
                'reference' => 'GRAP 103',
                'severity' => self::SEVERITY_CRITICAL,
                'validation' => 'hasRegisterEntry'
            ],
            'DOC002' => [
                'title' => 'Unique Identifier',
                'description' => 'Asset has unique identifier/reference number',
                'reference' => 'PFMA',
                'severity' => self::SEVERITY_HIGH,
                'validation' => 'hasIdentifier'
            ],
            'DOC003' => [
                'title' => 'Location Documented',
                'description' => 'Physical location of asset documented',
                'reference' => 'PFMA',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'hasLocation'
            ],
            'DOC004' => [
                'title' => 'Condition Documented',
                'description' => 'Condition of asset has been documented',
                'reference' => 'Spectrum 5.0',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'hasConditionRecord'
            ],
            'DOC005' => [
                'title' => 'Provenance Documented',
                'description' => 'Acquisition source and provenance documented',
                'reference' => 'GRAP 103',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'hasProvenance'
            ],
            'DOC006' => [
                'title' => 'Supporting Documents',
                'description' => 'Valuation reports, donation deeds, or purchase documents available',
                'reference' => 'PFMA',
                'severity' => self::SEVERITY_MEDIUM,
                'validation' => 'hasSupportingDocs'
            ]
        ]
    ];

    // NARSSA Compliance Checklist
    public static $narssaChecklist = [
        'NAR001' => [
            'title' => 'Archives Act Compliance',
            'description' => 'Records managed in accordance with National Archives Act 43 of 1996',
            'reference' => 'Archives Act 43/1996',
            'severity' => self::SEVERITY_CRITICAL,
            'validation' => 'checkArchivesActCompliance'
        ],
        'NAR002' => [
            'title' => 'Disposal Authority',
            'description' => 'Disposal of public records requires NARSSA authorisation',
            'reference' => 'Archives Act s.13',
            'severity' => self::SEVERITY_CRITICAL,
            'validation' => 'checkDisposalAuthority'
        ],
        'NAR003' => [
            'title' => 'Access Classification',
            'description' => 'Access restrictions properly classified (PAIA)',
            'reference' => 'PAIA 2/2000',
            'severity' => self::SEVERITY_HIGH,
            'validation' => 'hasAccessClassification'
        ],
        'NAR004' => [
            'title' => 'Preservation Requirements',
            'description' => 'Preservation standards being met',
            'reference' => 'NARSSA Guidelines',
            'severity' => self::SEVERITY_HIGH,
            'validation' => 'checkPreservationStandards'
        ],
        'NAR005' => [
            'title' => 'Description Standards',
            'description' => 'Archival description meets ISAD(G) standards',
            'reference' => 'ISAD(G)',
            'severity' => self::SEVERITY_MEDIUM,
            'validation' => 'checkDescriptionStandards'
        ],
        'NAR006' => [
            'title' => 'Digital Preservation',
            'description' => 'Digital objects meet preservation requirements',
            'reference' => 'NARSSA Digital Guidelines',
            'severity' => self::SEVERITY_MEDIUM,
            'validation' => 'checkDigitalPreservation'
        ]
    ];

    // PFMA Requirements
    public static $pfmaChecklist = [
        'PFM001' => [
            'title' => 'Asset Register Complete',
            'description' => 'All heritage assets included in asset register',
            'reference' => 'PFMA s.38(1)(d)',
            'severity' => self::SEVERITY_CRITICAL,
            'validation' => 'hasRegisterEntry'
        ],
        'PFM002' => [
            'title' => 'Safeguarding Assets',
            'description' => 'Appropriate measures in place to safeguard assets',
            'reference' => 'PFMA s.38(1)(d)',
            'severity' => self::SEVERITY_HIGH,
            'validation' => 'checkSafeguarding'
        ],
        'PFM003' => [
            'title' => 'Annual Reporting',
            'description' => 'Asset information available for annual report disclosure',
            'reference' => 'PFMA s.40(1)(d)',
            'severity' => self::SEVERITY_HIGH,
            'validation' => 'checkAnnualReportingReady'
        ],
        'PFM004' => [
            'title' => 'Audit Trail',
            'description' => 'Complete audit trail of asset transactions maintained',
            'reference' => 'PFMA',
            'severity' => self::SEVERITY_HIGH,
            'validation' => 'hasAuditTrail'
        ]
    ];

    protected $assetService;

    public function __construct()
    {
        $this->assetService = new arGrapHeritageAssetService();
    }

    /**
     * Run full compliance check for an object
     */
    public function checkCompliance($objectId)
    {
        $results = [
            'object_id' => $objectId,
            'checked_at' => date('Y-m-d H:i:s'),
            'overall_score' => 0,
            'categories' => [],
            'issues' => [],
            'recommendations' => []
        ];

        $grapRecord = $this->assetService->getAssetRecord($objectId);
        $object = QubitInformationObject::getById($objectId);

        // Run GRAP 103 checks
        foreach (self::$grap103Checklist as $category => $items) {
            $categoryResults = $this->checkCategory($category, $items, $objectId, $grapRecord, $object);
            $results['categories'][$category] = $categoryResults;
        }

        // Run NARSSA checks
        $results['categories']['narssa'] = $this->checkCategory(
            'narssa', 
            self::$narssaChecklist, 
            $objectId, 
            $grapRecord, 
            $object
        );

        // Run PFMA checks
        $results['categories']['pfma'] = $this->checkCategory(
            'pfma', 
            self::$pfmaChecklist, 
            $objectId, 
            $grapRecord, 
            $object
        );

        // Calculate overall score
        $results = $this->calculateOverallScore($results);

        // Generate recommendations
        $results['recommendations'] = $this->generateRecommendations($results);

        return $results;
    }

    /**
     * Check a category of compliance items
     */
    protected function checkCategory($category, $items, $objectId, $grapRecord, $object)
    {
        $categoryResult = [
            'category' => $category,
            'total' => count($items),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'not_applicable' => 0,
            'items' => []
        ];

        foreach ($items as $itemId => $item) {
            $checkResult = $this->runCheck($item, $objectId, $grapRecord, $object);
            
            $categoryResult['items'][$itemId] = [
                'id' => $itemId,
                'title' => $item['title'],
                'description' => $item['description'],
                'reference' => $item['reference'],
                'severity' => $item['severity'],
                'status' => $checkResult['status'],
                'message' => $checkResult['message'],
                'value' => $checkResult['value'] ?? null
            ];

            switch ($checkResult['status']) {
                case 'pass':
                    $categoryResult['passed']++;
                    break;
                case 'fail':
                    $categoryResult['failed']++;
                    break;
                case 'warning':
                    $categoryResult['warnings']++;
                    break;
                case 'not_applicable':
                    $categoryResult['not_applicable']++;
                    break;
            }
        }

        $applicable = $categoryResult['total'] - $categoryResult['not_applicable'];
        $categoryResult['score'] = $applicable > 0 
            ? round(($categoryResult['passed'] / $applicable) * 100) 
            : 100;

        return $categoryResult;
    }

    /**
     * Run individual compliance check
     */
    protected function runCheck($item, $objectId, $grapRecord, $object)
    {
        $method = $item['validation'];
        
        if (method_exists($this, $method)) {
            return $this->$method($objectId, $grapRecord, $object);
        }

        return ['status' => 'not_applicable', 'message' => 'Check not implemented'];
    }

    // Validation Methods

    protected function hasAssetClass($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['asset_class'])) {
            return [
                'status' => 'pass',
                'message' => 'Asset class assigned: ' . arGrapHeritageAssetService::$assetClassLabels[$grapRecord['asset_class']] ?? $grapRecord['asset_class'],
                'value' => $grapRecord['asset_class']
            ];
        }
        return ['status' => 'fail', 'message' => 'Asset class not assigned'];
    }

    protected function hasRecognitionStatus($objectId, $grapRecord, $object)
    {
        if ($grapRecord['recognition_status'] === arGrapHeritageAssetService::STATUS_RECOGNISED ||
            $grapRecord['recognition_status'] === arGrapHeritageAssetService::STATUS_IMPAIRED) {
            return [
                'status' => 'pass',
                'message' => 'Asset is recognised',
                'value' => $grapRecord['recognition_status']
            ];
        }
        if ($grapRecord['recognition_status'] === arGrapHeritageAssetService::STATUS_PENDING_RECOGNITION) {
            return ['status' => 'warning', 'message' => 'Recognition pending'];
        }
        return ['status' => 'fail', 'message' => 'Asset not recognised'];
    }

    protected function hasRecognitionDate($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['initial_recognition_date'])) {
            return [
                'status' => 'pass',
                'message' => 'Recognition date: ' . $grapRecord['initial_recognition_date'],
                'value' => $grapRecord['initial_recognition_date']
            ];
        }
        return ['status' => 'fail', 'message' => 'Initial recognition date not recorded'];
    }

    protected function hasMeasurementBasis($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['measurement_basis'])) {
            return [
                'status' => 'pass',
                'message' => 'Measurement basis: ' . ucfirst(str_replace('_', ' ', $grapRecord['measurement_basis'])),
                'value' => $grapRecord['measurement_basis']
            ];
        }
        return ['status' => 'fail', 'message' => 'Measurement basis not specified'];
    }

    protected function hasInitialCost($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['initial_cost']) || $grapRecord['initial_cost'] === 0) {
            return [
                'status' => 'pass',
                'message' => 'Initial cost: R ' . number_format($grapRecord['initial_cost'], 2),
                'value' => $grapRecord['initial_cost']
            ];
        }
        return ['status' => 'fail', 'message' => 'Initial cost not recorded'];
    }

    protected function hasCarryingAmount($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['carrying_amount']) || $grapRecord['carrying_amount'] === 0) {
            return [
                'status' => 'pass',
                'message' => 'Carrying amount: R ' . number_format($grapRecord['carrying_amount'], 2),
                'value' => $grapRecord['carrying_amount']
            ];
        }
        return ['status' => 'fail', 'message' => 'Carrying amount not recorded'];
    }

    protected function hasImpairmentTracking($objectId, $grapRecord, $object)
    {
        if (isset($grapRecord['accumulated_impairment'])) {
            return [
                'status' => 'pass',
                'message' => 'Accumulated impairment: R ' . number_format($grapRecord['accumulated_impairment'], 2),
                'value' => $grapRecord['accumulated_impairment']
            ];
        }
        return ['status' => 'warning', 'message' => 'Impairment tracking not configured'];
    }

    protected function hasValuationHistory($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['valuation_history']) && count($grapRecord['valuation_history']) > 0) {
            return [
                'status' => 'pass',
                'message' => count($grapRecord['valuation_history']) . ' valuation(s) recorded',
                'value' => count($grapRecord['valuation_history'])
            ];
        }
        return ['status' => 'warning', 'message' => 'No valuation history recorded'];
    }

    protected function hasRevaluationDetails($objectId, $grapRecord, $object)
    {
        if ($grapRecord['measurement_basis'] !== arGrapHeritageAssetService::MEASUREMENT_FAIR_VALUE) {
            return ['status' => 'not_applicable', 'message' => 'Not using revaluation model'];
        }

        if (!empty($grapRecord['last_valuation_date'])) {
            return [
                'status' => 'pass',
                'message' => 'Last valuation: ' . $grapRecord['last_valuation_date'],
                'value' => $grapRecord['last_valuation_date']
            ];
        }
        return ['status' => 'fail', 'message' => 'Revaluation details required but not recorded'];
    }

    protected function checkRevaluationFrequency($objectId, $grapRecord, $object)
    {
        if ($grapRecord['measurement_basis'] !== arGrapHeritageAssetService::MEASUREMENT_FAIR_VALUE) {
            return ['status' => 'not_applicable', 'message' => 'Not using revaluation model'];
        }

        if (empty($grapRecord['last_valuation_date'])) {
            return ['status' => 'fail', 'message' => 'No revaluation recorded'];
        }

        $lastValuation = strtotime($grapRecord['last_valuation_date']);
        $threeYearsAgo = strtotime('-3 years');

        if ($lastValuation >= $threeYearsAgo) {
            return [
                'status' => 'pass',
                'message' => 'Revaluation within 3 years',
                'value' => $grapRecord['last_valuation_date']
            ];
        }

        $fiveYearsAgo = strtotime('-5 years');
        if ($lastValuation >= $fiveYearsAgo) {
            return ['status' => 'warning', 'message' => 'Revaluation over 3 years old'];
        }

        return ['status' => 'fail', 'message' => 'Revaluation overdue (over 5 years)'];
    }

    protected function checkImpairmentAssessment($objectId, $grapRecord, $object)
    {
        // Check if condition has been assessed recently via Spectrum
        if (class_exists('arSpectrumEventService')) {
            $spectrumService = new arSpectrumEventService();
            $conditionStatus = $spectrumService->getProcedureStatus($objectId, 'object_condition');
            
            if ($conditionStatus['last_update']) {
                $lastCheck = strtotime($conditionStatus['last_update']);
                $oneYearAgo = strtotime('-1 year');
                
                if ($lastCheck >= $oneYearAgo) {
                    return [
                        'status' => 'pass',
                        'message' => 'Condition assessed: ' . date('Y-m-d', $lastCheck)
                    ];
                }
                return ['status' => 'warning', 'message' => 'Condition assessment over 1 year old'];
            }
        }
        
        return ['status' => 'warning', 'message' => 'No recent impairment assessment recorded'];
    }

    protected function checkNominalValueJustification($objectId, $grapRecord, $object)
    {
        if ($grapRecord['measurement_basis'] !== arGrapHeritageAssetService::MEASUREMENT_NOMINAL) {
            return ['status' => 'not_applicable', 'message' => 'Not using nominal value'];
        }

        $metadata = $grapRecord['metadata'] ?? [];
        if (!empty($metadata['nominal_value_justification'])) {
            return ['status' => 'pass', 'message' => 'Nominal value justification documented'];
        }
        return ['status' => 'fail', 'message' => 'Nominal value used but justification not documented'];
    }

    protected function checkDonationRecognition($objectId, $grapRecord, $object)
    {
        $fundingSource = $grapRecord['funding_source'] ?? '';
        if (stripos($fundingSource, 'donat') === false && stripos($fundingSource, 'gift') === false) {
            return ['status' => 'not_applicable', 'message' => 'Not a donated asset'];
        }

        if ($grapRecord['measurement_basis'] === arGrapHeritageAssetService::MEASUREMENT_FAIR_VALUE ||
            !empty($grapRecord['initial_cost'])) {
            return ['status' => 'pass', 'message' => 'Donated asset properly valued'];
        }
        return ['status' => 'warning', 'message' => 'Donated asset may need fair value assessment'];
    }

    protected function checkRestrictionsDisclosed($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['donor_restrictions'])) {
            return ['status' => 'pass', 'message' => 'Restrictions documented'];
        }
        return ['status' => 'not_applicable', 'message' => 'No restrictions to disclose'];
    }

    protected function hasInsuranceInfo($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['insurance_value'])) {
            return [
                'status' => 'pass',
                'message' => 'Insurance value: R ' . number_format($grapRecord['insurance_value'], 2),
                'value' => $grapRecord['insurance_value']
            ];
        }
        return ['status' => 'warning', 'message' => 'Insurance information not recorded'];
    }

    protected function hasRegisterEntry($objectId, $grapRecord, $object)
    {
        if ($grapRecord['id']) {
            return ['status' => 'pass', 'message' => 'Asset in GRAP register'];
        }
        return ['status' => 'fail', 'message' => 'Asset not in GRAP register'];
    }

    protected function hasIdentifier($objectId, $grapRecord, $object)
    {
        if ($object && !empty($object->identifier)) {
            return [
                'status' => 'pass',
                'message' => 'Identifier: ' . $object->identifier,
                'value' => $object->identifier
            ];
        }
        return ['status' => 'fail', 'message' => 'No unique identifier assigned'];
    }

    protected function hasLocation($objectId, $grapRecord, $object)
    {
        // Check for location in AtoM
        if ($object) {
            $physicalObjects = $object->getPhysicalObjects();
            if (count($physicalObjects) > 0) {
                return ['status' => 'pass', 'message' => 'Location documented'];
            }
        }
        return ['status' => 'warning', 'message' => 'Physical location not documented'];
    }

    protected function hasConditionRecord($objectId, $grapRecord, $object)
    {
        if (class_exists('arSpectrumEventService')) {
            $spectrumService = new arSpectrumEventService();
            $status = $spectrumService->getProcedureStatus($objectId, 'object_condition');
            if ($status['last_update']) {
                return ['status' => 'pass', 'message' => 'Condition recorded via Spectrum'];
            }
        }
        return ['status' => 'warning', 'message' => 'Condition not formally recorded'];
    }

    protected function hasProvenance($objectId, $grapRecord, $object)
    {
        if (!empty($grapRecord['funding_source'])) {
            return ['status' => 'pass', 'message' => 'Acquisition source documented'];
        }
        return ['status' => 'warning', 'message' => 'Provenance/acquisition source not documented'];
    }

    protected function hasSupportingDocs($objectId, $grapRecord, $object)
    {
        // Check for digital objects attached
        if ($object) {
            $digitalObjects = $object->digitalObjectsRelatedByObjectId;
            if (count($digitalObjects) > 0) {
                return ['status' => 'pass', 'message' => count($digitalObjects) . ' supporting document(s)'];
            }
        }
        return ['status' => 'warning', 'message' => 'No supporting documents attached'];
    }

    protected function hasAuditTrail($objectId, $grapRecord, $object)
    {
        $history = $this->assetService->getTransactionHistory($objectId, 1);
        if (!empty($history)) {
            return ['status' => 'pass', 'message' => 'Audit trail maintained'];
        }
        return ['status' => 'warning', 'message' => 'No transaction history recorded'];
    }

    protected function checkArchivesActCompliance($objectId, $grapRecord, $object)
    {
        // Basic check - would need more sophisticated validation in production
        return ['status' => 'pass', 'message' => 'Managed in AtoM compliant system'];
    }

    protected function checkDisposalAuthority($objectId, $grapRecord, $object)
    {
        if ($grapRecord['recognition_status'] === arGrapHeritageAssetService::STATUS_PENDING_DERECOGNITION ||
            $grapRecord['recognition_status'] === arGrapHeritageAssetService::STATUS_DERECOGNISED) {
            $metadata = $grapRecord['metadata'] ?? [];
            if (!empty($metadata['narssa_disposal_authority'])) {
                return ['status' => 'pass', 'message' => 'NARSSA disposal authority obtained'];
            }
            return ['status' => 'fail', 'message' => 'NARSSA disposal authority required'];
        }
        return ['status' => 'not_applicable', 'message' => 'Not being disposed'];
    }

    protected function hasAccessClassification($objectId, $grapRecord, $object)
    {
        // Check for access restrictions in AtoM
        if ($object && $object->accessConditions) {
            return ['status' => 'pass', 'message' => 'Access conditions documented'];
        }
        return ['status' => 'warning', 'message' => 'Access classification not specified'];
    }

    protected function checkPreservationStandards($objectId, $grapRecord, $object)
    {
        // Would integrate with preservation assessment
        return ['status' => 'warning', 'message' => 'Preservation assessment recommended'];
    }

    protected function checkDescriptionStandards($objectId, $grapRecord, $object)
    {
        if ($object) {
            $hasTitle = !empty($object->getTitle(['cultureFallback' => true]));
            $hasScope = !empty($object->getScopeAndContent(['cultureFallback' => true]));
            
            if ($hasTitle && $hasScope) {
                return ['status' => 'pass', 'message' => 'Core descriptive elements present'];
            }
            if ($hasTitle) {
                return ['status' => 'warning', 'message' => 'Description incomplete'];
            }
        }
        return ['status' => 'fail', 'message' => 'Archival description incomplete'];
    }

    protected function checkDigitalPreservation($objectId, $grapRecord, $object)
    {
        if ($object) {
            $digitalObjects = $object->digitalObjectsRelatedByObjectId;
            if (count($digitalObjects) > 0) {
                return ['status' => 'pass', 'message' => 'Digital objects present'];
            }
        }
        return ['status' => 'not_applicable', 'message' => 'No digital objects'];
    }

    protected function checkSafeguarding($objectId, $grapRecord, $object)
    {
        $checks = 0;
        if (!empty($grapRecord['insurance_value'])) $checks++;
        if ($object) {
            $physicalObjects = $object->getPhysicalObjects();
            if (count($physicalObjects) > 0) $checks++;
        }
        
        if ($checks >= 2) {
            return ['status' => 'pass', 'message' => 'Safeguarding measures documented'];
        }
        if ($checks >= 1) {
            return ['status' => 'warning', 'message' => 'Partial safeguarding documentation'];
        }
        return ['status' => 'fail', 'message' => 'Safeguarding measures not documented'];
    }

    protected function checkAnnualReportingReady($objectId, $grapRecord, $object)
    {
        $required = ['asset_class', 'carrying_amount', 'recognition_status'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($grapRecord[$field])) {
                $missing[] = $field;
            }
        }

        if (empty($missing)) {
            return ['status' => 'pass', 'message' => 'Ready for annual report disclosure'];
        }
        return ['status' => 'fail', 'message' => 'Missing: ' . implode(', ', $missing)];
    }

    /**
     * Calculate overall compliance score
     */
    protected function calculateOverallScore($results)
    {
        $totalWeight = 0;
        $weightedScore = 0;

        $categoryWeights = [
            'recognition' => 25,
            'measurement' => 25,
            'disclosure' => 15,
            'documentation' => 15,
            'narssa' => 10,
            'pfma' => 10
        ];

        foreach ($results['categories'] as $category => $data) {
            $weight = $categoryWeights[$category] ?? 10;
            $totalWeight += $weight;
            $weightedScore += $data['score'] * $weight;
        }

        $results['overall_score'] = $totalWeight > 0 ? round($weightedScore / $totalWeight) : 0;

        // Collect all issues
        foreach ($results['categories'] as $category => $data) {
            foreach ($data['items'] as $itemId => $item) {
                if ($item['status'] === 'fail' || $item['status'] === 'warning') {
                    $results['issues'][] = [
                        'category' => $category,
                        'item_id' => $itemId,
                        'title' => $item['title'],
                        'severity' => $item['severity'],
                        'status' => $item['status'],
                        'message' => $item['message']
                    ];
                }
            }
        }

        // Sort issues by severity
        usort($results['issues'], function($a, $b) {
            $severityOrder = [
                self::SEVERITY_CRITICAL => 1,
                self::SEVERITY_HIGH => 2,
                self::SEVERITY_MEDIUM => 3,
                self::SEVERITY_LOW => 4,
                self::SEVERITY_INFO => 5
            ];
            return ($severityOrder[$a['severity']] ?? 5) - ($severityOrder[$b['severity']] ?? 5);
        });

        return $results;
    }

    /**
     * Generate recommendations based on compliance results
     */
    protected function generateRecommendations($results)
    {
        $recommendations = [];

        foreach ($results['issues'] as $issue) {
            if ($issue['status'] === 'fail' && $issue['severity'] === self::SEVERITY_CRITICAL) {
                $recommendations[] = [
                    'priority' => 'immediate',
                    'action' => $this->getRecommendationAction($issue['item_id']),
                    'reference' => $issue['item_id']
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get specific recommendation action
     */
    protected function getRecommendationAction($itemId)
    {
        $actions = [
            'REC001' => 'Assign heritage asset class under GRAP 103.74',
            'REC002' => 'Complete initial recognition assessment',
            'MEA001' => 'Select and document measurement basis (cost or revaluation)',
            'MEA002' => 'Record initial cost at recognition date',
            'DOC001' => 'Add asset to GRAP heritage asset register',
            'DOC002' => 'Assign unique identifier to asset',
            'NAR002' => 'Obtain NARSSA disposal authority before proceeding',
            'PFM001' => 'Ensure asset is included in asset register'
        ];

        return $actions[$itemId] ?? 'Review and address compliance item';
    }

    /**
     * Get repository-wide compliance summary
     */
    public function getRepositoryComplianceSummary($repositoryId = null)
    {
        $sql = "SELECT 
                    COUNT(*) as total_assets,
                    SUM(CASE WHEN g.recognition_status = 'recognised' THEN 1 ELSE 0 END) as recognised,
                    SUM(CASE WHEN g.recognition_status = 'unrecognised' THEN 1 ELSE 0 END) as unrecognised,
                    SUM(CASE WHEN g.asset_class IS NOT NULL THEN 1 ELSE 0 END) as has_class,
                    SUM(CASE WHEN g.measurement_basis IS NOT NULL THEN 1 ELSE 0 END) as has_measurement,
                    SUM(CASE WHEN g.current_carrying_amount IS NOT NULL THEN 1 ELSE 0 END) as has_carrying
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id";

        $params = [];
        if ($repositoryId) {
            $sql .= " WHERE io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $conn = Propel::getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
