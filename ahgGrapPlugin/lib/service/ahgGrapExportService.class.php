<?php
/**
 * GRAP Export Service
 * 
 * Generates exports for National Treasury reporting, board packs,
 * and multi-year trend analysis.
 * 
 * Export formats match NT return templates and GRAP 103 disclosure requirements.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 */

class ahgGrapExportService
{
    protected $assetService;
    protected $complianceService;

    public function __construct()
    {
        $this->assetService = new ahgGrapHeritageAssetService();
        $this->complianceService = new ahgGrapComplianceService();
    }

    /**
     * Export Heritage Asset Register (NT Format)
     * Matches National Treasury asset register template
     */
    public function exportAssetRegisterNT($repositoryId = null, $financialYear = null)
    {
        $assets = $this->getAssetsForExport($repositoryId);

        $output = fopen('php://temp', 'r+');

        // NT Asset Register Headers
        fputcsv($output, [
            'Asset Number',
            'Description',
            'Asset Class',
            'Location',
            'Date Acquired',
            'Measurement Basis',
            'Cost / Initial Value (R)',
            'Accumulated Impairment (R)',
            'Revaluation Surplus (R)',
            'Carrying Amount (R)',
            'Last Valuation Date',
            'Funding Source',
            'Condition',
            'Insurance Value (R)',
            'Recognition Status',
            'Notes'
        ]);

        foreach ($assets as $asset) {
            fputcsv($output, [
                $asset['identifier'],
                $asset['title'],
                ahgGrapHeritageAssetService::$assetClassLabels[$asset['asset_class']] ?? $asset['asset_class'],
                $asset['location'] ?? '',
                $asset['initial_recognition_date'],
                ucfirst(str_replace('_', ' ', $asset['measurement_basis'] ?? '')),
                number_format($asset['initial_cost'] ?? 0, 2, '.', ''),
                number_format($asset['accumulated_impairment'] ?? 0, 2, '.', ''),
                number_format($asset['revaluation_surplus'] ?? 0, 2, '.', ''),
                number_format($asset['carrying_amount'] ?? 0, 2, '.', ''),
                $asset['last_valuation_date'] ?? '',
                $asset['funding_source'] ?? '',
                $asset['condition'] ?? '',
                number_format($asset['insurance_value'] ?? 0, 2, '.', ''),
                ahgGrapHeritageAssetService::$statusLabels[$asset['recognition_status']]['label'] ?? $asset['recognition_status'],
                ''
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'content' => $csv,
            'filename' => sprintf('NT_Heritage_Asset_Register_%s.csv', date('Y-m-d')),
            'mime_type' => 'text/csv'
        ];
    }

    /**
     * Export GRAP 103 Disclosure Note (AFS Format)
     * For Annual Financial Statements
     */
    public function exportDisclosureNote($repositoryId = null, $financialYearEnd = null)
    {
        $fyEnd = $financialYearEnd ?? date('Y') . '-03-31'; // SA Financial year end
        $fyStart = date('Y-m-d', strtotime($fyEnd . ' -1 year +1 day'));

        $summary = $this->getClassSummary($repositoryId);
        $movements = $this->getMovementReconciliation($repositoryId, $fyStart, $fyEnd);

        $output = fopen('php://temp', 'r+');

        // Header
        fputcsv($output, ['GRAP 103 - Heritage Assets Disclosure Note']);
        fputcsv($output, ['Financial Year Ended: ' . $fyEnd]);
        fputcsv($output, []);

        // Note disclosure per class
        fputcsv($output, ['1. Carrying Amount by Class']);
        fputcsv($output, [
            'Asset Class',
            'Cost Model (R)',
            'Revaluation Model (R)',
            'Nominal Value (R)',
            'Total Carrying Amount (R)',
            'Number of Items'
        ]);

        $totalCost = 0;
        $totalRevaluation = 0;
        $totalNominal = 0;
        $totalCarrying = 0;
        $totalItems = 0;

        foreach ($summary as $row) {
            $classLabel = ahgGrapHeritageAssetService::$assetClassLabels[$row['asset_class']] ?? $row['asset_class'];
            fputcsv($output, [
                $classLabel,
                number_format($row['cost_model'] ?? 0, 2, '.', ''),
                number_format($row['revaluation_model'] ?? 0, 2, '.', ''),
                number_format($row['nominal_value'] ?? 0, 2, '.', ''),
                number_format($row['total_carrying'] ?? 0, 2, '.', ''),
                $row['item_count']
            ]);

            $totalCost += $row['cost_model'] ?? 0;
            $totalRevaluation += $row['revaluation_model'] ?? 0;
            $totalNominal += $row['nominal_value'] ?? 0;
            $totalCarrying += $row['total_carrying'] ?? 0;
            $totalItems += $row['item_count'];
        }

        fputcsv($output, [
            'TOTAL',
            number_format($totalCost, 2, '.', ''),
            number_format($totalRevaluation, 2, '.', ''),
            number_format($totalNominal, 2, '.', ''),
            number_format($totalCarrying, 2, '.', ''),
            $totalItems
        ]);

        fputcsv($output, []);

        // Movement reconciliation
        fputcsv($output, ['2. Reconciliation of Carrying Amount']);
        fputcsv($output, [
            'Movement Type',
            'Art Collections (R)',
            'Museum Collections (R)',
            'Library Collections (R)',
            'Archival Collections (R)',
            'Other (R)',
            'Total (R)'
        ]);

        foreach ($movements as $movementType => $values) {
            $row = [$movementType];
            $rowTotal = 0;
            foreach (['art_collections', 'museum_collections', 'library_collections', 'archival_collections', 'other'] as $class) {
                $value = $values[$class] ?? 0;
                $row[] = number_format($value, 2, '.', '');
                $rowTotal += $value;
            }
            $row[] = number_format($rowTotal, 2, '.', '');
            fputcsv($output, $row);
        }

        fputcsv($output, []);

        // Impairment note
        fputcsv($output, ['3. Impairment Losses']);
        $impairmentData = $this->getImpairmentSummary($repositoryId, $fyStart, $fyEnd);
        fputcsv($output, [
            'Total impairment losses recognised in surplus/deficit: R ' . 
            number_format($impairmentData['total_impairment'] ?? 0, 2)
        ]);
        fputcsv($output, [
            'Total impairment reversals: R ' . 
            number_format($impairmentData['total_reversals'] ?? 0, 2)
        ]);

        fputcsv($output, []);

        // Revaluation surplus note
        fputcsv($output, ['4. Revaluation Surplus']);
        $revaluationData = $this->getRevaluationSurplusSummary($repositoryId);
        fputcsv($output, [
            'Total revaluation surplus: R ' . 
            number_format($revaluationData['total_surplus'] ?? 0, 2)
        ]);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'content' => $csv,
            'filename' => sprintf('GRAP103_Disclosure_Note_%s.csv', date('Y-m-d')),
            'mime_type' => 'text/csv'
        ];
    }

    /**
     * Export Multi-Year Trend Analysis
     */
    public function exportMultiYearTrend($repositoryId = null, $years = 5)
    {
        $trends = [];
        $currentYear = (int)date('Y');

        for ($i = 0; $i < $years; $i++) {
            $year = $currentYear - $i;
            $fyEnd = $year . '-03-31';
            $trends[$year] = $this->getYearEndSummary($repositoryId, $fyEnd);
        }

        $output = fopen('php://temp', 'r+');

        // Header
        fputcsv($output, ['Heritage Assets - Multi-Year Trend Analysis']);
        fputcsv($output, []);

        // Year headers
        $yearHeaders = ['Metric'];
        for ($i = $years - 1; $i >= 0; $i--) {
            $yearHeaders[] = ($currentYear - $i) . ' FY';
        }
        fputcsv($output, $yearHeaders);

        // Metrics
        $metrics = [
            'total_assets' => 'Total Heritage Assets',
            'total_carrying_amount' => 'Total Carrying Amount (R)',
            'recognised_assets' => 'Recognised Assets',
            'unrecognised_assets' => 'Unrecognised Assets',
            'total_impairment' => 'Accumulated Impairment (R)',
            'total_revaluation_surplus' => 'Revaluation Surplus (R)',
            'acquisitions' => 'New Acquisitions',
            'disposals' => 'Disposals/De-recognitions'
        ];

        foreach ($metrics as $key => $label) {
            $row = [$label];
            for ($i = $years - 1; $i >= 0; $i--) {
                $year = $currentYear - $i;
                $value = $trends[$year][$key] ?? 0;
                if (strpos($key, 'amount') !== false || strpos($key, 'impairment') !== false || strpos($key, 'surplus') !== false) {
                    $row[] = number_format($value, 2, '.', '');
                } else {
                    $row[] = $value;
                }
            }
            fputcsv($output, $row);
        }

        fputcsv($output, []);

        // Year-on-year changes
        fputcsv($output, ['Year-on-Year Changes']);
        
        $changeHeaders = ['Metric'];
        for ($i = $years - 2; $i >= 0; $i--) {
            $changeHeaders[] = ($currentYear - $i - 1) . ' to ' . ($currentYear - $i);
        }
        fputcsv($output, $changeHeaders);

        foreach (['total_carrying_amount' => 'Carrying Amount Change (R)', 'total_assets' => 'Asset Count Change'] as $key => $label) {
            $row = [$label];
            for ($i = $years - 2; $i >= 0; $i--) {
                $year = $currentYear - $i;
                $prevYear = $year - 1;
                $change = ($trends[$year][$key] ?? 0) - ($trends[$prevYear][$key] ?? 0);
                if (strpos($key, 'amount') !== false) {
                    $row[] = number_format($change, 2, '.', '');
                } else {
                    $row[] = $change;
                }
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'content' => $csv,
            'filename' => sprintf('Heritage_Assets_Trend_%d_Years_%s.csv', $years, date('Y-m-d')),
            'mime_type' => 'text/csv'
        ];
    }

    /**
     * Export Board Pack Summary
     */
    public function exportBoardPack($repositoryId = null, $reportDate = null)
    {
        $reportDate = $reportDate ?? date('Y-m-d');
        
        $summary = $this->assetService->getAssetSummary($repositoryId);
        $complianceSummary = $this->complianceService->getRepositoryComplianceSummary($repositoryId);
        $highValueAssets = $this->getHighValueAssets($repositoryId, 10);
        $recentActivity = $this->getRecentActivity($repositoryId, 30);

        // Generate HTML for PDF conversion
        $html = $this->renderBoardPackHTML([
            'report_date' => $reportDate,
            'summary' => $summary,
            'compliance' => $complianceSummary,
            'high_value' => $highValueAssets,
            'recent_activity' => $recentActivity
        ]);

        return $this->htmlToPDF($html, sprintf('Heritage_Assets_Board_Pack_%s.pdf', $reportDate));
    }

    /**
     * Export Impairment Schedule
     */
    public function exportImpairmentSchedule($repositoryId = null, $financialYear = null)
    {
        $fyEnd = $financialYear ?? date('Y') . '-03-31';
        $fyStart = date('Y-m-d', strtotime($fyEnd . ' -1 year +1 day'));

        $impairments = $this->getImpairmentDetails($repositoryId, $fyStart, $fyEnd);

        $output = fopen('php://temp', 'r+');

        fputcsv($output, ['Heritage Assets - Impairment Schedule']);
        fputcsv($output, ['Financial Year: ' . $fyStart . ' to ' . $fyEnd]);
        fputcsv($output, []);

        fputcsv($output, [
            'Asset Number',
            'Description',
            'Asset Class',
            'Impairment Date',
            'Impairment Indicator',
            'Carrying Amount Before (R)',
            'Impairment Loss (R)',
            'Carrying Amount After (R)',
            'Accumulated Impairment (R)'
        ]);

        $totalLoss = 0;
        foreach ($impairments as $imp) {
            fputcsv($output, [
                $imp['identifier'],
                $imp['title'],
                ahgGrapHeritageAssetService::$assetClassLabels[$imp['asset_class']] ?? $imp['asset_class'],
                $imp['date'],
                $imp['indicator'],
                number_format($imp['previous_carrying'], 2, '.', ''),
                number_format($imp['amount'], 2, '.', ''),
                number_format($imp['new_carrying'], 2, '.', ''),
                number_format($imp['accumulated'], 2, '.', '')
            ]);
            $totalLoss += $imp['amount'];
        }

        fputcsv($output, []);
        fputcsv($output, ['', '', '', '', 'TOTAL IMPAIRMENT LOSS:', '', number_format($totalLoss, 2, '.', ''), '', '']);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'content' => $csv,
            'filename' => sprintf('Impairment_Schedule_%s.csv', date('Y-m-d')),
            'mime_type' => 'text/csv'
        ];
    }

    /**
     * Export De-recognition Schedule
     */
    public function exportDerecognitionSchedule($repositoryId = null, $financialYear = null)
    {
        $fyEnd = $financialYear ?? date('Y') . '-03-31';
        $fyStart = date('Y-m-d', strtotime($fyEnd . ' -1 year +1 day'));

        $derecognitions = $this->getDerecognitionDetails($repositoryId, $fyStart, $fyEnd);

        $output = fopen('php://temp', 'r+');

        fputcsv($output, ['Heritage Assets - De-recognition Schedule']);
        fputcsv($output, ['Financial Year: ' . $fyStart . ' to ' . $fyEnd]);
        fputcsv($output, []);

        fputcsv($output, [
            'Asset Number',
            'Description',
            'Asset Class',
            'De-recognition Date',
            'Reason',
            'Carrying Amount (R)',
            'Disposal Proceeds (R)',
            'Gain/(Loss) on Disposal (R)',
            'NARSSA Authority Ref'
        ]);

        $totalCarrying = 0;
        $totalProceeds = 0;
        $totalGainLoss = 0;

        foreach ($derecognitions as $derec) {
            fputcsv($output, [
                $derec['identifier'],
                $derec['title'],
                ahgGrapHeritageAssetService::$assetClassLabels[$derec['asset_class']] ?? $derec['asset_class'],
                $derec['derecognition_date'],
                $derec['derecognition_reason'],
                number_format($derec['carrying_amount'] ?? 0, 2, '.', ''),
                number_format($derec['disposal_proceeds'] ?? 0, 2, '.', ''),
                number_format($derec['gain_loss_on_disposal'] ?? 0, 2, '.', ''),
                $derec['narssa_ref'] ?? ''
            ]);

            $totalCarrying += $derec['carrying_amount'] ?? 0;
            $totalProceeds += $derec['disposal_proceeds'] ?? 0;
            $totalGainLoss += $derec['gain_loss_on_disposal'] ?? 0;
        }

        fputcsv($output, []);
        fputcsv($output, [
            '', '', '', '', 'TOTALS:',
            number_format($totalCarrying, 2, '.', ''),
            number_format($totalProceeds, 2, '.', ''),
            number_format($totalGainLoss, 2, '.', ''),
            ''
        ]);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'content' => $csv,
            'filename' => sprintf('Derecognition_Schedule_%s.csv', date('Y-m-d')),
            'mime_type' => 'text/csv'
        ];
    }

    /**
     * Export Revaluation Schedule
     */
    public function exportRevaluationSchedule($repositoryId = null, $financialYear = null)
    {
        $fyEnd = $financialYear ?? date('Y') . '-03-31';
        $fyStart = date('Y-m-d', strtotime($fyEnd . ' -1 year +1 day'));

        $revaluations = $this->getRevaluationDetails($repositoryId, $fyStart, $fyEnd);

        $output = fopen('php://temp', 'r+');

        fputcsv($output, ['Heritage Assets - Revaluation Schedule']);
        fputcsv($output, ['Financial Year: ' . $fyStart . ' to ' . $fyEnd]);
        fputcsv($output, []);

        fputcsv($output, [
            'Asset Number',
            'Description',
            'Asset Class',
            'Valuation Date',
            'Valuer',
            'Previous Value (R)',
            'New Value (R)',
            'Increase/(Decrease) (R)',
            'Revaluation Surplus (R)'
        ]);

        $totalIncrease = 0;
        $totalDecrease = 0;

        foreach ($revaluations as $reval) {
            $difference = $reval['new_value'] - $reval['previous_value'];
            fputcsv($output, [
                $reval['identifier'],
                $reval['title'],
                ahgGrapHeritageAssetService::$assetClassLabels[$reval['asset_class']] ?? $reval['asset_class'],
                $reval['date'],
                $reval['valuer'],
                number_format($reval['previous_value'], 2, '.', ''),
                number_format($reval['new_value'], 2, '.', ''),
                number_format($difference, 2, '.', ''),
                number_format($reval['revaluation_surplus'] ?? 0, 2, '.', '')
            ]);

            if ($difference > 0) {
                $totalIncrease += $difference;
            } else {
                $totalDecrease += abs($difference);
            }
        }

        fputcsv($output, []);
        fputcsv($output, ['', '', '', '', '', 'Total Increase:', number_format($totalIncrease, 2, '.', ''), '', '']);
        fputcsv($output, ['', '', '', '', '', 'Total Decrease:', number_format($totalDecrease, 2, '.', ''), '', '']);
        fputcsv($output, ['', '', '', '', '', 'Net Movement:', number_format($totalIncrease - $totalDecrease, 2, '.', ''), '', '']);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'content' => $csv,
            'filename' => sprintf('Revaluation_Schedule_%s.csv', date('Y-m-d')),
            'mime_type' => 'text/csv'
        ];
    }

    // Helper methods

    protected function getAssetsForExport($repositoryId)
    {
        $conn = Propel::getConnection();

        $sql = "SELECT g.*, io.identifier, s.slug
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                LEFT JOIN slug s ON io.id = s.object_id";

        $params = [];
        if ($repositoryId) {
            $sql .= " WHERE io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $sql .= " ORDER BY g.asset_class, io.identifier";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add object details
        foreach ($results as &$row) {
            $object = QubitInformationObject::getById($row['object_id']);
            if ($object) {
                $row['title'] = $object->getTitle(['cultureFallback' => true]);
                $physicalObjects = $object->getPhysicalObjects();
                $row['location'] = count($physicalObjects) > 0 ? $physicalObjects[0]->getName(['cultureFallback' => true]) : '';
            }
        }

        return $results;
    }

    protected function getClassSummary($repositoryId)
    {
        $conn = Propel::getConnection();

        $sql = "SELECT 
                    g.asset_class,
                    SUM(CASE WHEN g.measurement_basis = 'cost' THEN g.current_carrying_amount ELSE 0 END) as cost_model,
                    SUM(CASE WHEN g.measurement_basis = 'fair_value' THEN g.current_carrying_amount ELSE 0 END) as revaluation_model,
                    SUM(CASE WHEN g.measurement_basis = 'nominal' THEN g.current_carrying_amount ELSE 0 END) as nominal_value,
                    SUM(COALESCE(g.current_carrying_amount, 0)) as total_carrying,
                    COUNT(*) as item_count
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                LEFT JOIN slug s ON io.id = s.object_id
                WHERE g.recognition_status IN ('recognised', 'impaired')";

        $params = [];
        if ($repositoryId) {
            $sql .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $sql .= " GROUP BY g.asset_class ORDER BY g.asset_class";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getMovementReconciliation($repositoryId, $fyStart, $fyEnd)
    {
        // This would query transaction logs for movements
        // Simplified version - would need actual transaction queries

        return [
            'Opening Balance' => [],
            'Additions - Acquisitions' => [],
            'Additions - Donations' => [],
            'Revaluation Increases' => [],
            'Revaluation Decreases' => [],
            'Impairment Losses' => [],
            'Impairment Reversals' => [],
            'De-recognitions' => [],
            'Closing Balance' => []
        ];
    }

    protected function getImpairmentSummary($repositoryId, $fyStart, $fyEnd)
    {
        $conn = Propel::getConnection();

        $sql = "SELECT 
                    SUM(CASE WHEN t.transaction_type = 'impairment' THEN 
                        JSON_EXTRACT(t.transaction_data, '$.impairment_amount') ELSE 0 END) as total_impairment,
                    SUM(CASE WHEN t.transaction_type = 'impairment_reversal' THEN 
                        JSON_EXTRACT(t.transaction_data, '$.reversal_amount') ELSE 0 END) as total_reversals
                FROM grap_transaction_log t
                WHERE t.created_at BETWEEN :fy_start AND :fy_end";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':fy_start' => $fyStart, ':fy_end' => $fyEnd]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_impairment' => 0, 'total_reversals' => 0];
    }

    protected function getRevaluationSurplusSummary($repositoryId)
    {
        $conn = Propel::getConnection();

        $sql = "SELECT SUM(COALESCE(g.revaluation_surplus, 0)) as total_surplus
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                LEFT JOIN slug s ON io.id = s.object_id
                WHERE g.recognition_status IN ('recognised', 'impaired')";

        $params = [];
        if ($repositoryId) {
            $sql .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_surplus' => 0];
    }

    protected function getYearEndSummary($repositoryId, $fyEnd)
    {
        // Would query historical snapshots
        // Simplified version
        $conn = Propel::getConnection();

        $sql = "SELECT 
                    COUNT(*) as total_assets,
                    SUM(COALESCE(g.current_carrying_amount, 0)) as total_carrying_amount,
                    SUM(CASE WHEN g.recognition_status = 'recognised' THEN 1 ELSE 0 END) as recognised_assets,
                    SUM(CASE WHEN g.recognition_status = 'unrecognised' THEN 1 ELSE 0 END) as unrecognised_assets,
                    SUM(COALESCE(g.impairment_loss, 0)) as total_impairment,
                    SUM(COALESCE(g.revaluation_surplus, 0)) as total_revaluation_surplus
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                LEFT JOIN slug s ON io.id = s.object_id";

        $params = [];
        if ($repositoryId) {
            $sql .= " WHERE io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['acquisitions'] = 0; // Would query acquisitions in period
        $result['disposals'] = 0; // Would query disposals in period

        return $result;
    }

    protected function getHighValueAssets($repositoryId, $limit)
    {
        $conn = Propel::getConnection();

        $sql = "SELECT g.*, io.identifier
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                LEFT JOIN slug s ON io.id = s.object_id
                WHERE g.recognition_status IN ('recognised', 'impaired')";

        $params = [];
        if ($repositoryId) {
            $sql .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $sql .= " ORDER BY g.current_carrying_amount DESC LIMIT :limit";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getRecentActivity($repositoryId, $days)
    {
        $conn = Propel::getConnection();

        $sql = "SELECT t.*, io.identifier
                FROM grap_transaction_log t
                JOIN information_object io ON t.object_id = io.id
                WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";

        $params = [':days' => $days];
        if ($repositoryId) {
            $sql .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT 20";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getImpairmentDetails($repositoryId, $fyStart, $fyEnd)
    {
        // Would query impairment history
        return [];
    }

    protected function getDerecognitionDetails($repositoryId, $fyStart, $fyEnd)
    {
        $conn = Propel::getConnection();

        $sql = "SELECT g.*, io.identifier
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                LEFT JOIN slug s ON io.id = s.object_id
                WHERE g.recognition_status = 'derecognised'
                  AND g.derecognition_date BETWEEN :fy_start AND :fy_end";

        $params = [':fy_start' => $fyStart, ':fy_end' => $fyEnd];
        if ($repositoryId) {
            $sql .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            $object = QubitInformationObject::getById($row['object_id']);
            $row['title'] = $object ? $object->getTitle(['cultureFallback' => true]) : '';
            $metadata = json_decode($row['metadata'] ?? '{}', true);
            $row['narssa_ref'] = $metadata['narssa_disposal_authority'] ?? '';
        }

        return $results;
    }

    protected function getRevaluationDetails($repositoryId, $fyStart, $fyEnd)
    {
        // Would query valuation history within date range
        return [];
    }

    protected function renderBoardPackHTML($data)
    {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Heritage Assets Board Pack</title>
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11pt; color: #333; }
        .header { background: #1a5276; color: #fff; padding: 30px; }
        .header h1 { font-size: 24pt; margin-bottom: 10px; }
        .header .date { font-size: 12pt; opacity: 0.8; }
        .section { padding: 25px; page-break-inside: avoid; }
        .section h2 { font-size: 16pt; color: #1a5276; border-bottom: 2px solid #3498db; padding-bottom: 8px; margin-bottom: 20px; }
        .summary-cards { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { flex: 1; background: #f8f9fa; border-radius: 8px; padding: 20px; text-align: center; }
        .card .value { font-size: 28pt; font-weight: bold; color: #1a5276; }
        .card .label { font-size: 10pt; color: #7f8c8d; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #1a5276; color: #fff; padding: 10px; text-align: left; font-size: 10pt; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .compliance-score { font-size: 48pt; font-weight: bold; }
        .score-good { color: #27ae60; }
        .score-warning { color: #f39c12; }
        .score-poor { color: #e74c3c; }
        .footer { padding: 20px; font-size: 9pt; color: #7f8c8d; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Heritage Assets - Board Pack</h1>
        <div class="date">Report Date: <?php echo $data['report_date']; ?></div>
    </div>

    <div class="section">
        <h2>Executive Summary</h2>
        <div class="summary-cards">
            <div class="card">
                <div class="value"><?php echo number_format($data['compliance']['total_assets'] ?? 0); ?></div>
                <div class="label">Total Assets</div>
            </div>
            <div class="card">
                <div class="value"><?php echo number_format($data['compliance']['recognised'] ?? 0); ?></div>
                <div class="label">Recognised</div>
            </div>
            <div class="card">
                <div class="value"><?php 
                    $complianceRate = ($data['compliance']['total_assets'] ?? 0) > 0 
                        ? round(($data['compliance']['recognised'] ?? 0) / $data['compliance']['total_assets'] * 100) 
                        : 0;
                    echo $complianceRate . '%';
                ?></div>
                <div class="label">Recognition Rate</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>High Value Assets</h2>
        <table>
            <tr>
                <th>Asset Number</th>
                <th>Class</th>
                <th>Carrying Amount</th>
                <th>Status</th>
            </tr>
            <?php foreach ($data['high_value'] as $asset): ?>
            <tr>
                <td><?php echo htmlspecialchars($asset['identifier']); ?></td>
                <td><?php echo ahgGrapHeritageAssetService::$assetClassLabels[$asset['asset_class']] ?? $asset['asset_class']; ?></td>
                <td>R <?php echo number_format($asset['carrying_amount'] ?? 0, 2); ?></td>
                <td><?php echo ahgGrapHeritageAssetService::$statusLabels[$asset['recognition_status']]['label'] ?? $asset['recognition_status']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="footer">
        Generated by ahgGrapPlugin | GRAP 103 Heritage Asset Accounting
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    protected function htmlToPDF($html, $filename)
    {
        // Try dompdf
        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return [
                'content' => $dompdf->output(),
                'filename' => $filename,
                'mime_type' => 'application/pdf'
            ];
        }

        // Fallback to HTML
        return [
            'content' => $html,
            'filename' => str_replace('.pdf', '.html', $filename),
            'mime_type' => 'text/html'
        ];
    }
}
