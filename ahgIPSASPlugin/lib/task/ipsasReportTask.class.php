<?php

/**
 * IPSAS Report Task
 *
 * Generates IPSAS-compliant heritage asset reports:
 * - Asset register report
 * - Valuation summary
 * - Financial year summary
 * - Insurance coverage report
 *
 * Usage: php symfony ipsas:report [--type=summary] [--year=2025] [--format=text]
 */
class ipsasReportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_REQUIRED, 'Report type (summary|assets|valuations|financial|insurance)', 'summary'),
            new sfCommandOption('year', null, sfCommandOption::PARAMETER_REQUIRED, 'Financial year', date('Y')),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Output format (text|csv|json)', 'text'),
        ]);

        $this->namespace = 'ipsas';
        $this->name = 'report';
        $this->briefDescription = 'Generate IPSAS heritage asset reports';
        $this->detailedDescription = <<<'EOF'
The [ipsas:report|INFO] task generates heritage asset reports:
  - summary: Overall statistics and compliance status
  - assets: Full asset register
  - valuations: Valuation history
  - financial: Financial year movement summary
  - insurance: Insurance coverage report

Examples:
  [php symfony ipsas:report|INFO]                           Summary report
  [php symfony ipsas:report --type=financial --year=2025|INFO]
  [php symfony ipsas:report --type=assets --format=csv|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $type = $options['type'];
        $year = $options['year'];
        $format = $options['format'];

        switch ($type) {
            case 'summary':
                return $this->reportSummary($format);
            case 'assets':
                return $this->reportAssets($format);
            case 'valuations':
                return $this->reportValuations($format, $year);
            case 'financial':
                return $this->reportFinancial($format, $year);
            case 'insurance':
                return $this->reportInsurance($format);
            default:
                $this->logSection('error', 'Unknown report type: '.$type, null, 'ERROR');

                return 1;
        }
    }

    protected function reportSummary(string $format): int
    {
        $service = new \AhgIPSAS\Services\IPSASService();
        $stats = $service->getDashboardStats();
        $compliance = $service->getComplianceStatus();

        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'statistics' => $stats,
            'compliance' => $compliance,
        ];

        if ('json' === $format) {
            echo json_encode($data, JSON_PRETTY_PRINT);

            return 0;
        }

        $this->logSection('ipsas', 'IPSAS Heritage Asset Management - Summary Report');
        $this->logSection('ipsas', 'Generated: '.date('Y-m-d H:i:s'));
        $this->log('');

        $this->logSection('stats', 'Asset Statistics');
        $this->log(sprintf('  Total Assets: %d', $stats['assets']['total']));
        $this->log(sprintf('    - Active: %d', $stats['assets']['active']));
        $this->log(sprintf('    - On Loan: %d', $stats['assets']['on_loan']));
        $this->log(sprintf('    - Disposed: %d', $stats['assets']['disposed']));
        $this->log('');

        $this->logSection('values', 'Values');
        $this->log(sprintf('  Total Asset Value: $%s', number_format($stats['values']['total'], 2)));
        $this->log(sprintf('  Total Insured Value: $%s', number_format($stats['values']['insured'], 2)));
        $this->log('');

        $this->logSection('valuation', 'Valuation Basis');
        foreach ($stats['valuation_basis'] as $basis => $count) {
            $this->log(sprintf('  %s: %d assets', ucfirst($basis), $count));
        }
        $this->log('');

        $this->logSection('compliance', 'Compliance Status: '.strtoupper($compliance['status']));
        if (!empty($compliance['issues'])) {
            foreach ($compliance['issues'] as $issue) {
                $this->log('  [ISSUE] '.$issue);
            }
        }
        if (!empty($compliance['warnings'])) {
            foreach ($compliance['warnings'] as $warning) {
                $this->log('  [WARNING] '.$warning);
            }
        }

        return 0;
    }

    protected function reportAssets(string $format): int
    {
        $assets = \Illuminate\Database\Capsule\Manager::table('ipsas_heritage_asset as a')
            ->leftJoin('ipsas_asset_category as c', 'a.category_id', '=', 'c.id')
            ->select([
                'a.asset_number',
                'a.title',
                'c.name as category',
                'a.valuation_basis',
                'a.current_value',
                'a.current_value_currency as currency',
                'a.status',
                'a.condition_rating',
                'a.acquisition_date',
                'a.location',
            ])
            ->whereNotIn('a.status', ['disposed', 'lost', 'destroyed'])
            ->orderBy('a.asset_number')
            ->get();

        if ('json' === $format) {
            echo json_encode($assets->toArray(), JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "Asset Number,Title,Category,Valuation Basis,Value,Currency,Status,Condition,Acquisition Date,Location\n";
            foreach ($assets as $a) {
                echo sprintf("%s,\"%s\",%s,%s,%.2f,%s,%s,%s,%s,\"%s\"\n",
                    $a->asset_number,
                    str_replace('"', '""', $a->title),
                    $a->category ?? '',
                    $a->valuation_basis,
                    $a->current_value ?? 0,
                    $a->currency,
                    $a->status,
                    $a->condition_rating,
                    $a->acquisition_date ?? '',
                    str_replace('"', '""', $a->location ?? '')
                );
            }

            return 0;
        }

        $this->logSection('ipsas', 'Heritage Asset Register');
        $this->logSection('ipsas', sprintf('Total: %d assets', $assets->count()));
        $this->log('');

        $totalValue = 0;
        foreach ($assets as $a) {
            $this->log(sprintf('[%s] %s', $a->asset_number, $a->title));
            $this->log(sprintf('     Category: %s | Status: %s | Condition: %s',
                $a->category ?? 'N/A',
                ucfirst($a->status),
                ucfirst($a->condition_rating)
            ));
            $this->log(sprintf('     Value: %s %.2f (%s)',
                $a->currency,
                $a->current_value ?? 0,
                ucfirst(str_replace('_', ' ', $a->valuation_basis))
            ));
            $this->log('');
            $totalValue += $a->current_value ?? 0;
        }

        $this->logSection('total', sprintf('Total Value: $%s', number_format($totalValue, 2)));

        return 0;
    }

    protected function reportValuations(string $format, string $year): int
    {
        $valuations = \Illuminate\Database\Capsule\Manager::table('ipsas_valuation as v')
            ->join('ipsas_heritage_asset as a', 'v.asset_id', '=', 'a.id')
            ->whereYear('v.valuation_date', $year)
            ->select([
                'v.valuation_date',
                'a.asset_number',
                'a.title as asset_title',
                'v.valuation_type',
                'v.valuation_basis',
                'v.previous_value',
                'v.new_value',
                'v.change_amount',
                'v.change_percent',
                'v.valuer_type',
            ])
            ->orderBy('v.valuation_date', 'desc')
            ->get();

        if ('json' === $format) {
            echo json_encode($valuations->toArray(), JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "Date,Asset Number,Asset Title,Type,Basis,Previous Value,New Value,Change,Change %,Valuer Type\n";
            foreach ($valuations as $v) {
                echo sprintf("%s,%s,\"%s\",%s,%s,%.2f,%.2f,%.2f,%.2f,%s\n",
                    $v->valuation_date,
                    $v->asset_number,
                    str_replace('"', '""', $v->asset_title),
                    $v->valuation_type,
                    $v->valuation_basis,
                    $v->previous_value ?? 0,
                    $v->new_value ?? 0,
                    $v->change_amount ?? 0,
                    $v->change_percent ?? 0,
                    $v->valuer_type
                );
            }

            return 0;
        }

        $this->logSection('ipsas', sprintf('Valuation Report - %s', $year));
        $this->logSection('ipsas', sprintf('Total valuations: %d', $valuations->count()));
        $this->log('');

        foreach ($valuations as $v) {
            $this->log(sprintf('[%s] %s - %s', $v->valuation_date, $v->asset_number, $v->asset_title));
            $this->log(sprintf('     Type: %s | Basis: %s',
                ucfirst($v->valuation_type),
                ucfirst(str_replace('_', ' ', $v->valuation_basis))
            ));
            $this->log(sprintf('     Previous: $%.2f -> New: $%.2f (Change: $%.2f / %.2f%%)',
                $v->previous_value ?? 0,
                $v->new_value ?? 0,
                $v->change_amount ?? 0,
                $v->change_percent ?? 0
            ));
            $this->log('');
        }

        return 0;
    }

    protected function reportFinancial(string $format, string $year): int
    {
        $service = new \AhgIPSAS\Services\IPSASService();
        $summary = $service->calculateFinancialYearSummary($year);

        if ('json' === $format) {
            echo json_encode($summary, JSON_PRETTY_PRINT);

            return 0;
        }

        $this->logSection('ipsas', '═══════════════════════════════════════════════════════════════');
        $this->logSection('ipsas', '       HERITAGE ASSET FINANCIAL SUMMARY');
        $this->logSection('ipsas', sprintf('       Financial Year: %s', $year));
        $this->logSection('ipsas', sprintf('       Period: %s to %s', $summary['year_start'], $summary['year_end']));
        $this->logSection('ipsas', '═══════════════════════════════════════════════════════════════');
        $this->log('');

        $this->logSection('opening', 'Opening Balance');
        $this->log(sprintf('  Total Assets: %d', $summary['opening_total_assets']));
        $this->log(sprintf('  Total Value: $%s', number_format($summary['opening_total_value'], 2)));
        $this->log('');

        $this->logSection('movements', 'Movements During Year');
        $this->log(sprintf('  Additions: %d assets ($%s)',
            $summary['additions_count'],
            number_format($summary['additions_value'], 2)
        ));
        $this->log(sprintf('  Disposals: %d assets ($%s)',
            $summary['disposals_count'],
            number_format($summary['disposals_value'], 2)
        ));
        $this->log(sprintf('  Revaluations (Increase): $%s', number_format($summary['revaluations_increase'], 2)));
        $this->log(sprintf('  Revaluations (Decrease): $%s', number_format($summary['revaluations_decrease'], 2)));
        $this->log(sprintf('  Impairments: $%s', number_format($summary['impairments'], 2)));
        $this->log('');

        $this->logSection('closing', 'Closing Balance');
        $this->log(sprintf('  Total Assets: %d', $summary['closing_total_assets']));
        $this->log(sprintf('  Total Value: $%s', number_format($summary['closing_total_value'], 2)));
        $this->log('');

        $netMovement = $summary['closing_total_value'] - $summary['opening_total_value'];
        $this->logSection('net', sprintf('Net Movement: $%s', number_format($netMovement, 2)));

        return 0;
    }

    protected function reportInsurance(string $format): int
    {
        $policies = \Illuminate\Database\Capsule\Manager::table('ipsas_insurance as i')
            ->leftJoin('ipsas_heritage_asset as a', 'i.asset_id', '=', 'a.id')
            ->select([
                'i.policy_number',
                'i.policy_type',
                'i.insurer',
                'a.asset_number',
                'a.title as asset_title',
                'i.sum_insured',
                'i.currency',
                'i.coverage_start',
                'i.coverage_end',
                'i.status',
            ])
            ->orderBy('i.coverage_end')
            ->get();

        if ('json' === $format) {
            echo json_encode($policies->toArray(), JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "Policy Number,Type,Insurer,Asset,Sum Insured,Currency,Start,End,Status\n";
            foreach ($policies as $p) {
                echo sprintf("%s,%s,\"%s\",%s,%.2f,%s,%s,%s,%s\n",
                    $p->policy_number,
                    $p->policy_type,
                    str_replace('"', '""', $p->insurer),
                    $p->asset_number ?? 'Blanket',
                    $p->sum_insured,
                    $p->currency,
                    $p->coverage_start,
                    $p->coverage_end,
                    $p->status
                );
            }

            return 0;
        }

        $this->logSection('ipsas', 'Insurance Coverage Report');
        $this->logSection('ipsas', sprintf('Total Policies: %d', $policies->count()));
        $this->log('');

        $totalInsured = 0;
        $expiringSoon = 0;

        foreach ($policies as $p) {
            $daysToExpiry = floor((strtotime($p->coverage_end) - time()) / 86400);
            $expiryWarning = '';

            if ('active' === $p->status && $daysToExpiry <= 30) {
                $expiryWarning = ' [EXPIRING SOON]';
                ++$expiringSoon;
            }

            $this->log(sprintf('[%s] %s - %s%s',
                $p->policy_number,
                ucfirst($p->policy_type),
                $p->insurer,
                $expiryWarning
            ));
            $this->log(sprintf('     Asset: %s',
                $p->asset_number ? $p->asset_number.' - '.$p->asset_title : 'Blanket Coverage'
            ));
            $this->log(sprintf('     Sum Insured: %s %s | Period: %s to %s',
                $p->currency,
                number_format($p->sum_insured, 2),
                $p->coverage_start,
                $p->coverage_end
            ));
            $this->log(sprintf('     Status: %s', ucfirst($p->status)));
            $this->log('');

            if ('active' === $p->status) {
                $totalInsured += $p->sum_insured;
            }
        }

        $this->logSection('summary', 'Summary');
        $this->log(sprintf('  Total Insured Value: $%s', number_format($totalInsured, 2)));
        $this->log(sprintf('  Policies Expiring Soon: %d', $expiringSoon));

        return 0;
    }
}
