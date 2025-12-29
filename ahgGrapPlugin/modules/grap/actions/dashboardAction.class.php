<?php
/**
 * GRAP Dashboard Action
 * 
 * Repository-wide heritage asset overview.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 */

class grapDashboardAction extends sfAction
{
    public function execute($request)
    {
        $this->assetService = new arGrapHeritageAssetService();
        $this->complianceService = new arGrapComplianceService();
        $this->exportService = new arGrapExportService();

        // Repository filter
        $this->repositoryId = $request->getParameter('repository_id');
        $this->repositories = $this->getRepositories();

        // Financial year
        $this->financialYear = $request->getParameter('financial_year') ?: date('Y');
        $this->fyEnd = $this->financialYear . '-03-31';

        // Get summary statistics
        $this->summary = $this->assetService->getAssetSummary($this->repositoryId);

        // Get compliance overview
        $this->complianceOverview = $this->complianceService->getRepositoryComplianceSummary($this->repositoryId);

        // Group summary by class
        $this->classSummary = $this->groupByClass($this->summary);

        // Group by status
        $this->statusSummary = $this->groupByStatus($this->summary);

        // Calculate totals
        $this->totals = $this->calculateTotals($this->summary);

        // Get pending actions
        $this->pendingRecognition = $this->getPendingRecognition($this->repositoryId);
        $this->pendingDerecognition = $this->getPendingDerecognition($this->repositoryId);

        // Asset classes and status labels for display
        $this->assetClasses = arGrapHeritageAssetService::$assetClassLabels;
        $this->statusLabels = arGrapHeritageAssetService::$statusLabels;
    }

    protected function getRepositories()
    {
        // Use Laravel Query Builder for PHP 8.3 compatibility
        // Repository extends Actor, so authorized_form_of_name is in actor_i18n
        $repos = [];
        
        $results = \Illuminate\Database\Capsule\Manager::table('repository')
            ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.culture', 'en')
            ->whereNotNull('actor_i18n.authorized_form_of_name')
            ->orderBy('actor_i18n.authorized_form_of_name', 'asc')
            ->select('repository.id', 'actor_i18n.authorized_form_of_name')
            ->get();
        
        foreach ($results as $row) {
            $repos[$row->id] = $row->authorized_form_of_name;
        }
        
        return $repos;
    }

    protected function groupByClass($summary)
    {
        $byClass = [];
        foreach ($summary as $row) {
            $class = $row['asset_class'] ?: 'unclassified';
            if (!isset($byClass[$class])) {
                $byClass[$class] = [
                    'count' => 0,
                    'carrying_amount' => 0,
                    'impairment' => 0,
                    'surplus' => 0
                ];
            }
            $byClass[$class]['count'] += $row['count'];
            $byClass[$class]['carrying_amount'] += $row['total_carrying_amount'];
            $byClass[$class]['impairment'] += $row['total_impairment'];
            $byClass[$class]['surplus'] += $row['total_revaluation_surplus'];
        }
        return $byClass;
    }

    protected function groupByStatus($summary)
    {
        $byStatus = [];
        foreach ($summary as $row) {
            $status = $row['recognition_status'];
            if (!isset($byStatus[$status])) {
                $byStatus[$status] = [
                    'count' => 0,
                    'carrying_amount' => 0
                ];
            }
            $byStatus[$status]['count'] += $row['count'];
            $byStatus[$status]['carrying_amount'] += $row['total_carrying_amount'];
        }
        return $byStatus;
    }

    protected function calculateTotals($summary)
    {
        $totals = [
            'total_assets' => 0,
            'total_carrying_amount' => 0,
            'total_impairment' => 0,
            'total_surplus' => 0,
            'recognised' => 0,
            'unrecognised' => 0
        ];

        foreach ($summary as $row) {
            $totals['total_assets'] += $row['count'];
            $totals['total_carrying_amount'] += $row['total_carrying_amount'];
            $totals['total_impairment'] += $row['total_impairment'];
            $totals['total_surplus'] += $row['total_revaluation_surplus'];

            if ($row['recognition_status'] === arGrapHeritageAssetService::STATUS_RECOGNISED ||
                $row['recognition_status'] === arGrapHeritageAssetService::STATUS_IMPAIRED) {
                $totals['recognised'] += $row['count'];
            } else {
                $totals['unrecognised'] += $row['count'];
            }
        }

        $totals['recognition_rate'] = $totals['total_assets'] > 0 
            ? round(($totals['recognised'] / $totals['total_assets']) * 100) 
            : 0;

        return $totals;
    }

    protected function getPendingRecognition($repositoryId)
    {
        $query = \Illuminate\Database\Capsule\Manager::table('grap_heritage_asset as g')
            ->join('information_object as io', 'g.object_id', '=', 'io.id')
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->whereIn('g.recognition_status', ['unrecognised', 'pending_recognition'])
            ->select('g.object_id', 'io.identifier', 's.slug', 'ioi.title')
            ->limit(20);

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }

        $results = $query->get()->map(function($row) {
            return [
                'object_id' => $row->object_id,
                'identifier' => $row->identifier,
                'slug' => $row->slug,
                'title' => $row->title ?? ''
            ];
        })->toArray();

        return $results;
    }

    protected function getPendingDerecognition($repositoryId)
    {
        $query = \Illuminate\Database\Capsule\Manager::table('grap_heritage_asset as g')
            ->join('information_object as io', 'g.object_id', '=', 'io.id')
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->where('g.recognition_status', 'pending_derecognition')
            ->select('g.object_id', 'g.derecognition_reason', 'io.identifier', 's.slug', 'ioi.title')
            ->limit(20);

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }

        $results = $query->get()->map(function($row) {
            return [
                'object_id' => $row->object_id,
                'derecognition_reason' => $row->derecognition_reason,
                'identifier' => $row->identifier,
                'slug' => $row->slug,
                'title' => $row->title ?? ''
            ];
        })->toArray();

        return $results;
    }
}
