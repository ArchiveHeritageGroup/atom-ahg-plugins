<?php

declare(strict_types=1);

namespace AhgLoan\Services\Loan;

use Illuminate\Database\ConnectionInterface;

/**
 * Loan Dashboard Service.
 *
 * Provides comprehensive dashboard data and reporting for loan management.
 * Includes statistics, trends, and exportable reports.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class LoanDashboardService
{
    private ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Get comprehensive dashboard data.
     *
     * @return array Dashboard data
     */
    public function getDashboardData(): array
    {
        return [
            'summary' => $this->getSummaryStatistics(),
            'by_status' => $this->getLoansByStatus(),
            'by_type' => $this->getLoansByType(),
            'by_purpose' => $this->getLoansByPurpose(),
            'overdue' => $this->getOverdueLoans(),
            'due_soon' => $this->getLoansDueSoon(30),
            'recent_activity' => $this->getRecentActivity(),
            'top_partners' => $this->getTopPartners(),
            'monthly_trend' => $this->getMonthlyTrend(),
            'insurance_exposure' => $this->getInsuranceExposure(),
        ];
    }

    /**
     * Get summary statistics.
     */
    public function getSummaryStatistics(): array
    {
        $today = date('Y-m-d');

        return [
            'total_loans' => $this->db->table('loan')->count(),
            'active_loans' => $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('wi.is_complete', false)
                ->count(),
            'loans_out' => $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('l.loan_type', 'out')
                ->where('wi.is_complete', false)
                ->count(),
            'loans_in' => $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('l.loan_type', 'in')
                ->where('wi.is_complete', false)
                ->count(),
            'overdue' => $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('l.end_date', '<', $today)
                ->whereNull('l.return_date')
                ->where('wi.is_complete', false)
                ->count(),
            'due_this_month' => $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->whereBetween('l.end_date', [date('Y-m-01'), date('Y-m-t')])
                ->whereNull('l.return_date')
                ->where('wi.is_complete', false)
                ->count(),
            'total_objects_on_loan' => $this->db->table('loan_object as lo')
                ->join('loan as l', 'l.id', '=', 'lo.loan_id')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('wi.is_complete', false)
                ->count(),
            'total_insurance_value' => $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('l.loan_type', 'out')
                ->where('wi.is_complete', false)
                ->sum('l.insurance_value') ?? 0,
            'pending_shipments' => $this->db->table('loan_shipment')
                ->whereIn('status', ['planned', 'picked_up', 'in_transit'])
                ->count(),
        ];
    }

    /**
     * Get loans grouped by status.
     */
    public function getLoansByStatus(): array
    {
        return $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->selectRaw('COALESCE(wi.current_state, "unknown") as status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->status => $r->count])
            ->all();
    }

    /**
     * Get loans grouped by type.
     */
    public function getLoansByType(): array
    {
        return $this->db->table('loan')
            ->selectRaw('loan_type, COUNT(*) as count')
            ->groupBy('loan_type')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->loan_type => $r->count])
            ->all();
    }

    /**
     * Get loans grouped by purpose.
     */
    public function getLoansByPurpose(): array
    {
        return $this->db->table('loan')
            ->selectRaw('purpose, COUNT(*) as count')
            ->groupBy('purpose')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->purpose => $r->count])
            ->all();
    }

    /**
     * Get overdue loans.
     */
    public function getOverdueLoans(): array
    {
        return $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('l.end_date', '<', date('Y-m-d'))
            ->whereNull('l.return_date')
            ->where('wi.is_complete', false)
            ->select(
                'l.id',
                'l.loan_number',
                'l.loan_type',
                'l.partner_institution',
                'l.end_date',
                'l.insurance_value',
                'wi.current_state'
            )
            ->orderBy('l.end_date')
            ->get()
            ->map(function ($r) {
                $data = (array) $r;
                $data['days_overdue'] = (int) ((time() - strtotime($r->end_date)) / 86400);

                return $data;
            })
            ->all();
    }

    /**
     * Get loans due soon.
     */
    public function getLoansDueSoon(int $days = 30): array
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));

        return $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('l.end_date', '>=', date('Y-m-d'))
            ->where('l.end_date', '<=', $futureDate)
            ->whereNull('l.return_date')
            ->where('wi.is_complete', false)
            ->select(
                'l.id',
                'l.loan_number',
                'l.loan_type',
                'l.partner_institution',
                'l.end_date',
                'wi.current_state'
            )
            ->orderBy('l.end_date')
            ->get()
            ->map(function ($r) {
                $data = (array) $r;
                $data['days_remaining'] = (int) ((strtotime($r->end_date) - time()) / 86400);

                return $data;
            })
            ->all();
    }

    /**
     * Get recent activity.
     */
    public function getRecentActivity(int $limit = 20): array
    {
        $activities = [];

        // Recent loans created
        $newLoans = $this->db->table('loan')
            ->where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        foreach ($newLoans as $loan) {
            $activities[] = [
                'date' => $loan->created_at,
                'type' => 'loan_created',
                'icon' => 'plus-circle',
                'color' => 'success',
                'title' => 'Loan Created',
                'description' => $loan->loan_number.' - '.$loan->partner_institution,
                'loan_id' => $loan->id,
            ];
        }

        // Recent workflow transitions
        $transitions = $this->db->table('workflow_history as wh')
            ->join('workflow_instance as wi', 'wi.id', '=', 'wh.instance_id')
            ->join('loan as l', function ($join) {
                $join->on('l.id', '=', 'wi.entity_id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('wh.created_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->select('wh.*', 'l.id as loan_id', 'l.loan_number', 'l.partner_institution')
            ->orderByDesc('wh.created_at')
            ->limit($limit)
            ->get();

        foreach ($transitions as $trans) {
            $activities[] = [
                'date' => $trans->created_at,
                'type' => 'status_change',
                'icon' => 'exchange-alt',
                'color' => 'info',
                'title' => 'Status Changed',
                'description' => $trans->loan_number.': '.($trans->from_state ?? 'Start').' → '.$trans->to_state,
                'loan_id' => $trans->loan_id,
            ];
        }

        // Recent returns
        $returns = $this->db->table('loan')
            ->whereNotNull('return_date')
            ->where('updated_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->orderByDesc('return_date')
            ->limit($limit)
            ->get();

        foreach ($returns as $loan) {
            $activities[] = [
                'date' => $loan->return_date,
                'type' => 'loan_returned',
                'icon' => 'check-circle',
                'color' => 'success',
                'title' => 'Loan Returned',
                'description' => $loan->loan_number.' - '.$loan->partner_institution,
                'loan_id' => $loan->id,
            ];
        }

        // Sort by date descending
        usort($activities, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get top partner institutions.
     */
    public function getTopPartners(int $limit = 10): array
    {
        return $this->db->table('loan')
            ->selectRaw('partner_institution, COUNT(*) as loan_count, SUM(insurance_value) as total_insurance')
            ->groupBy('partner_institution')
            ->orderByDesc('loan_count')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get monthly loan trend.
     */
    public function getMonthlyTrend(int $months = 12): array
    {
        $trend = [];
        $startDate = date('Y-m-01', strtotime("-{$months} months"));

        for ($i = 0; $i < $months; ++$i) {
            $monthStart = date('Y-m-01', strtotime("+{$i} months", strtotime($startDate)));
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $monthLabel = date('M Y', strtotime($monthStart));

            $trend[] = [
                'month' => $monthLabel,
                'created' => $this->db->table('loan')
                    ->whereBetween('created_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])
                    ->count(),
                'started' => $this->db->table('loan')
                    ->whereBetween('start_date', [$monthStart, $monthEnd])
                    ->count(),
                'ended' => $this->db->table('loan')
                    ->whereBetween('end_date', [$monthStart, $monthEnd])
                    ->count(),
                'returned' => $this->db->table('loan')
                    ->whereBetween('return_date', [$monthStart, $monthEnd])
                    ->count(),
            ];
        }

        return $trend;
    }

    /**
     * Get insurance exposure analysis.
     */
    public function getInsuranceExposure(): array
    {
        $activeLoansOut = $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('l.loan_type', 'out')
            ->where('wi.is_complete', false)
            ->select('l.*')
            ->get();

        $byInsuranceType = [];
        $totalValue = 0;

        foreach ($activeLoansOut as $loan) {
            $type = $loan->insurance_type ?? 'unknown';
            if (!isset($byInsuranceType[$type])) {
                $byInsuranceType[$type] = ['count' => 0, 'value' => 0];
            }
            ++$byInsuranceType[$type]['count'];
            $byInsuranceType[$type]['value'] += $loan->insurance_value ?? 0;
            $totalValue += $loan->insurance_value ?? 0;
        }

        return [
            'total_value' => $totalValue,
            'currency' => 'ZAR',
            'by_insurance_type' => $byInsuranceType,
            'active_loans_out_count' => count($activeLoansOut),
        ];
    }

    /**
     * Generate annual report data.
     */
    public function generateAnnualReport(int $year): array
    {
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        return [
            'year' => $year,
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => [
                'total_loans_created' => $this->db->table('loan')
                    ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                    ->count(),
                'total_loans_out' => $this->db->table('loan')
                    ->where('loan_type', 'out')
                    ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                    ->count(),
                'total_loans_in' => $this->db->table('loan')
                    ->where('loan_type', 'in')
                    ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                    ->count(),
                'total_objects_loaned' => $this->db->table('loan_object as lo')
                    ->join('loan as l', 'l.id', '=', 'lo.loan_id')
                    ->whereBetween('l.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                    ->count(),
                'total_insurance_value' => $this->db->table('loan')
                    ->where('loan_type', 'out')
                    ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                    ->sum('insurance_value') ?? 0,
            ],
            'by_month' => $this->getYearlyMonthlyBreakdown($year),
            'by_purpose' => $this->db->table('loan')
                ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->selectRaw('purpose, COUNT(*) as count')
                ->groupBy('purpose')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all(),
            'top_partners' => $this->db->table('loan')
                ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->selectRaw('partner_institution, COUNT(*) as loan_count')
                ->groupBy('partner_institution')
                ->orderByDesc('loan_count')
                ->limit(20)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all(),
        ];
    }

    /**
     * Get yearly monthly breakdown.
     */
    private function getYearlyMonthlyBreakdown(int $year): array
    {
        $months = [];

        for ($m = 1; $m <= 12; ++$m) {
            $monthStart = sprintf('%04d-%02d-01', $year, $m);
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            $months[] = [
                'month' => $m,
                'month_name' => date('F', strtotime($monthStart)),
                'loans_created' => $this->db->table('loan')
                    ->whereBetween('created_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])
                    ->count(),
                'loans_started' => $this->db->table('loan')
                    ->whereBetween('start_date', [$monthStart, $monthEnd])
                    ->count(),
                'loans_returned' => $this->db->table('loan')
                    ->whereBetween('return_date', [$monthStart, $monthEnd])
                    ->count(),
            ];
        }

        return $months;
    }

    /**
     * Export dashboard data to CSV.
     */
    public function exportToCsv(string $reportType = 'all_loans'): string
    {
        $data = [];
        $headers = [];

        switch ($reportType) {
            case 'all_loans':
                $headers = ['Loan #', 'Type', 'Partner', 'Purpose', 'Start Date', 'End Date', 'Return Date', 'Status', 'Insurance Value'];
                $loans = $this->db->table('loan as l')
                    ->leftJoin('workflow_instance as wi', function ($join) {
                        $join->on('wi.entity_id', '=', 'l.id')
                            ->where('wi.entity_type', '=', 'loan');
                    })
                    ->select('l.*', 'wi.current_state')
                    ->orderByDesc('l.created_at')
                    ->get();

                foreach ($loans as $loan) {
                    $data[] = [
                        $loan->loan_number,
                        $loan->loan_type,
                        $loan->partner_institution,
                        $loan->purpose,
                        $loan->start_date,
                        $loan->end_date,
                        $loan->return_date,
                        $loan->current_state,
                        $loan->insurance_value,
                    ];
                }
                break;

            case 'active_loans':
                $headers = ['Loan #', 'Type', 'Partner', 'Start Date', 'End Date', 'Days Remaining', 'Status', 'Insurance Value'];
                $loans = $this->db->table('loan as l')
                    ->leftJoin('workflow_instance as wi', function ($join) {
                        $join->on('wi.entity_id', '=', 'l.id')
                            ->where('wi.entity_type', '=', 'loan');
                    })
                    ->where('wi.is_complete', false)
                    ->select('l.*', 'wi.current_state')
                    ->orderBy('l.end_date')
                    ->get();

                foreach ($loans as $loan) {
                    $daysRemaining = $loan->end_date ? (int) ((strtotime($loan->end_date) - time()) / 86400) : null;
                    $data[] = [
                        $loan->loan_number,
                        $loan->loan_type,
                        $loan->partner_institution,
                        $loan->start_date,
                        $loan->end_date,
                        $daysRemaining,
                        $loan->current_state,
                        $loan->insurance_value,
                    ];
                }
                break;

            case 'overdue':
                $headers = ['Loan #', 'Type', 'Partner', 'End Date', 'Days Overdue', 'Status', 'Insurance Value'];
                $loans = $this->getOverdueLoans();

                foreach ($loans as $loan) {
                    $data[] = [
                        $loan['loan_number'],
                        $loan['loan_type'],
                        $loan['partner_institution'],
                        $loan['end_date'],
                        $loan['days_overdue'],
                        $loan['current_state'],
                        $loan['insurance_value'],
                    ];
                }
                break;
        }

        // Generate CSV
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
