<?php
/**
 * GRAP Statistics API Action
 * 
 * RESTful API for GRAP statistics and summaries.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 */

class apiStatisticsApiAction extends sfAction
{
    public function execute($request)
    {
        $this->response->setContentType('application/json');

        $type = $request->getParameter('type', 'overview');
        $repositoryId = $request->getParameter('repository_id');
        $financialYear = $request->getParameter('financial_year');

        try {
            switch ($type) {
                case 'overview':
                    return $this->getOverview($repositoryId);

                case 'by_class':
                    return $this->getByClass($repositoryId);

                case 'by_status':
                    return $this->getByStatus($repositoryId);

                case 'compliance':
                    return $this->getComplianceStats($repositoryId);

                case 'trend':
                    $years = (int)$request->getParameter('years', 5);
                    return $this->getTrend($repositoryId, $years);

                case 'financial_summary':
                    return $this->getFinancialSummary($repositoryId, $financialYear);

                default:
                    return AhgCentralHelpers::apiJsonError($this, 'Unknown statistics type', 400);
            }
        } catch (Exception $e) {
            return AhgCentralHelpers::apiJsonError($this, $e->getMessage(), 500);
        }
    }

    protected function getOverview($repositoryId)
    {
        $assetService = new arGrapHeritageAssetService();
        $complianceService = new arGrapComplianceService();

        $summary = $assetService->getAssetSummary($repositoryId);
        $compliance = $complianceService->getRepositoryComplianceSummary($repositoryId);

        // Calculate totals
        $totals = [
            'total_assets' => 0,
            'total_carrying_amount' => 0,
            'total_impairment' => 0,
            'total_revaluation_surplus' => 0,
            'recognised' => 0,
            'unrecognised' => 0
        ];

        foreach ($summary as $row) {
            $totals['total_assets'] += $row['count'];
            $totals['total_carrying_amount'] += $row['total_carrying_amount'];
            $totals['total_impairment'] += $row['total_impairment'];
            $totals['total_revaluation_surplus'] += $row['total_revaluation_surplus'];

            if (in_array($row['recognition_status'], ['recognised', 'impaired'])) {
                $totals['recognised'] += $row['count'];
            } else {
                $totals['unrecognised'] += $row['count'];
            }
        }

        $totals['recognition_rate'] = $totals['total_assets'] > 0 
            ? round(($totals['recognised'] / $totals['total_assets']) * 100, 1) 
            : 0;

        return AhgCentralHelpers::apiJsonResponse($this, [
            'totals' => $totals,
            'compliance' => $compliance,
            'generated_at' => date('c')
        ]);
    }

    protected function getByClass($repositoryId)
    {
        $assetService = new arGrapHeritageAssetService();
        $summary = $assetService->getAssetSummary($repositoryId);

        $byClass = [];
        foreach ($summary as $row) {
            $class = $row['asset_class'] ?: 'unclassified';
            if (!isset($byClass[$class])) {
                $byClass[$class] = [
                    'class' => $class,
                    'label' => arGrapHeritageAssetService::$assetClassLabels[$class] ?? ucfirst(str_replace('_', ' ', $class)),
                    'count' => 0,
                    'carrying_amount' => 0,
                    'impairment' => 0,
                    'revaluation_surplus' => 0
                ];
            }
            $byClass[$class]['count'] += $row['count'];
            $byClass[$class]['carrying_amount'] += $row['total_carrying_amount'];
            $byClass[$class]['impairment'] += $row['total_impairment'];
            $byClass[$class]['revaluation_surplus'] += $row['total_revaluation_surplus'];
        }

        return AhgCentralHelpers::apiJsonResponse($this, [
            'by_class' => array_values($byClass),
            'generated_at' => date('c')
        ]);
    }

    protected function getByStatus($repositoryId)
    {
        $assetService = new arGrapHeritageAssetService();
        $summary = $assetService->getAssetSummary($repositoryId);

        $byStatus = [];
        foreach ($summary as $row) {
            $status = $row['recognition_status'];
            if (!isset($byStatus[$status])) {
                $statusInfo = arGrapHeritageAssetService::$statusLabels[$status] ?? ['label' => $status, 'color' => '#95a5a6'];
                $byStatus[$status] = [
                    'status' => $status,
                    'label' => $statusInfo['label'],
                    'color' => $statusInfo['color'],
                    'count' => 0,
                    'carrying_amount' => 0
                ];
            }
            $byStatus[$status]['count'] += $row['count'];
            $byStatus[$status]['carrying_amount'] += $row['total_carrying_amount'];
        }

        return AhgCentralHelpers::apiJsonResponse($this, [
            'by_status' => array_values($byStatus),
            'generated_at' => date('c')
        ]);
    }

    protected function getComplianceStats($repositoryId)
    {
        $complianceService = new arGrapComplianceService();
        $summary = $complianceService->getRepositoryComplianceSummary($repositoryId);

        $rates = [];
        if (($summary['total_assets'] ?? 0) > 0) {
            $total = $summary['total_assets'];
            $rates = [
                'recognised_rate' => round(($summary['recognised'] ?? 0) / $total * 100, 1),
                'class_assigned_rate' => round(($summary['has_class'] ?? 0) / $total * 100, 1),
                'measurement_set_rate' => round(($summary['has_measurement'] ?? 0) / $total * 100, 1),
                'carrying_recorded_rate' => round(($summary['has_carrying'] ?? 0) / $total * 100, 1)
            ];
        }

        return AhgCentralHelpers::apiJsonResponse($this, [
            'compliance' => $summary,
            'rates' => $rates,
            'generated_at' => date('c')
        ]);
    }

    protected function getTrend($repositoryId, $years)
    {
        $conn = Propel::getConnection();

        $sql = "SELECT 
                    financial_year_end,
                    SUM(total_assets) as total_assets,
                    SUM(total_carrying_amount) as total_carrying_amount,
                    SUM(total_impairment) as total_impairment,
                    SUM(total_revaluation_surplus) as total_revaluation_surplus
                FROM grap_financial_year_snapshot
                WHERE financial_year_end >= DATE_SUB(CURDATE(), INTERVAL :years YEAR)";

        $params = [':years' => $years];

        if ($repositoryId) {
            $sql .= " AND repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $sql .= " GROUP BY financial_year_end ORDER BY financial_year_end ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return AhgCentralHelpers::apiJsonResponse($this, [
            'trend' => $data,
            'years' => $years,
            'generated_at' => date('c')
        ]);
    }

    protected function getFinancialSummary($repositoryId, $financialYear)
    {
        $fyEnd = $financialYear ? $financialYear . '-03-31' : date('Y') . '-03-31';
        $fyStart = date('Y-m-d', strtotime($fyEnd . ' -1 year +1 day'));

        $conn = Propel::getConnection();

        // Get current balances
        $sql = "SELECT 
                    SUM(COALESCE(g.initial_cost, 0)) as total_cost,
                    SUM(COALESCE(g.current_carrying_amount, 0)) as total_carrying,
                    SUM(COALESCE(g.impairment_loss, 0)) as total_impairment,
                    SUM(COALESCE(g.revaluation_surplus, 0)) as total_surplus,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN g.recognition_status IN ('recognised', 'impaired') THEN 1 ELSE 0 END) as recognised_count
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                WHERE g.recognition_status != 'derecognised'";

        $params = [];
        if ($repositoryId) {
            $sql .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $balances = $stmt->fetch(PDO::FETCH_ASSOC);

        return AhgCentralHelpers::apiJsonResponse($this, [
            'financial_year' => [
                'start' => $fyStart,
                'end' => $fyEnd
            ],
            'balances' => $balances,
            'generated_at' => date('c')
        ]);
    }
}
