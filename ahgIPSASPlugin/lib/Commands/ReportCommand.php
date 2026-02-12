<?php

namespace AtomFramework\Console\Commands\Ipsas;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Generate IPSAS-compliant heritage asset reports.
 */
class ReportCommand extends BaseCommand
{
    protected string $name = 'ipsas:report';
    protected string $description = 'Generate IPSAS heritage asset reports';
    protected string $detailedDescription = <<<'EOF'
    Generates IPSAS-compliant heritage asset reports:
      - summary: Overall statistics and compliance status
      - assets: Full asset register
      - valuations: Valuation history
      - financial: Financial year movement summary
      - insurance: Insurance coverage report

    Examples:
      php bin/atom ipsas:report                                Summary report
      php bin/atom ipsas:report --type=financial --year=2025
      php bin/atom ipsas:report --type=assets --format=csv
      php bin/atom ipsas:report --type=insurance --format=json
    EOF;

    protected function configure(): void
    {
        $this->addOption('type', null, 'Report type (summary|assets|valuations|financial|insurance)', 'summary');
        $this->addOption('year', null, 'Financial year', date('Y'));
        $this->addOption('format', null, 'Output format (text|csv|json)', 'text');
    }

    protected function handle(): int
    {
        $type = $this->option('type');
        $year = $this->option('year');
        $format = $this->option('format');

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
                $this->error('Unknown report type: ' . $type);

                return 1;
        }
    }

    private function reportSummary(string $format): int
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

        $this->bold('IPSAS Heritage Asset Management - Summary Report');
        $this->line('Generated: ' . date('Y-m-d H:i:s'));
        $this->newline();

        $this->info('Asset Statistics');
        $this->line(sprintf('  Total Assets: %d', $stats['assets']['total']));
        $this->line(sprintf('    - Active: %d', $stats['assets']['active']));
        $this->line(sprintf('    - On Loan: %d', $stats['assets']['on_loan']));
        $this->line(sprintf('    - Disposed: %d', $stats['assets']['disposed']));
        $this->newline();

        $this->info('Values');
        $this->line(sprintf('  Total Asset Value: $%s', number_format($stats['values']['total'], 2)));
        $this->line(sprintf('  Total Insured Value: $%s', number_format($stats['values']['insured'], 2)));
        $this->newline();

        $this->info('Valuation Basis');
        foreach ($stats['valuation_basis'] as $basis => $count) {
            $this->line(sprintf('  %s: %d assets', ucfirst($basis), $count));
        }
        $this->newline();

        $this->info('Compliance Status: ' . strtoupper($compliance['status']));
        if (!empty($compliance['issues'])) {
            foreach ($compliance['issues'] as $issue) {
                $this->error('  [ISSUE] ' . $issue);
            }
        }
        if (!empty($compliance['warnings'])) {
            foreach ($compliance['warnings'] as $warning) {
                $this->warning('  [WARNING] ' . $warning);
            }
        }

        return 0;
    }

    private function reportAssets(string $format): int
    {
        $assets = DB::table('ipsas_heritage_asset as a')
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

        $this->bold('Heritage Asset Register');
        $this->line(sprintf('Total: %d assets', $assets->count()));
        $this->newline();

        $totalValue = 0;
        foreach ($assets as $a) {
            $this->line(sprintf('[%s] %s', $a->asset_number, $a->title));
            $this->line(sprintf('     Category: %s | Status: %s | Condition: %s',
                $a->category ?? 'N/A',
                ucfirst($a->status),
                ucfirst($a->condition_rating)
            ));
            $this->line(sprintf('     Value: %s %.2f (%s)',
                $a->currency,
                $a->current_value ?? 0,
                ucfirst(str_replace('_', ' ', $a->valuation_basis))
            ));
            $this->newline();
            $totalValue += $a->current_value ?? 0;
        }

        $this->bold(sprintf('Total Value: $%s', number_format($totalValue, 2)));

        return 0;
    }

    private function reportValuations(string $format, string $year): int
    {
        $valuations = DB::table('ipsas_valuation as v')
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

        $this->bold(sprintf('Valuation Report - %s', $year));
        $this->line(sprintf('Total valuations: %d', $valuations->count()));
        $this->newline();

        foreach ($valuations as $v) {
            $this->line(sprintf('[%s] %s - %s', $v->valuation_date, $v->asset_number, $v->asset_title));
            $this->line(sprintf('     Type: %s | Basis: %s',
                ucfirst($v->valuation_type),
                ucfirst(str_replace('_', ' ', $v->valuation_basis))
            ));
            $this->line(sprintf('     Previous: $%.2f -> New: $%.2f (Change: $%.2f / %.2f%%)',
                $v->previous_value ?? 0,
                $v->new_value ?? 0,
                $v->change_amount ?? 0,
                $v->change_percent ?? 0
            ));
            $this->newline();
        }

        return 0;
    }

    private function reportFinancial(string $format, string $year): int
    {
        $service = new \AhgIPSAS\Services\IPSASService();
        $summary = $service->calculateFinancialYearSummary($year);

        if ('json' === $format) {
            echo json_encode($summary, JSON_PRETTY_PRINT);

            return 0;
        }

        $this->line('================================================================');
        $this->bold('       HERITAGE ASSET FINANCIAL SUMMARY');
        $this->line(sprintf('       Financial Year: %s', $year));
        $this->line(sprintf('       Period: %s to %s', $summary['year_start'], $summary['year_end']));
        $this->line('================================================================');
        $this->newline();

        $this->info('Opening Balance');
        $this->line(sprintf('  Total Assets: %d', $summary['opening_total_assets']));
        $this->line(sprintf('  Total Value: $%s', number_format($summary['opening_total_value'], 2)));
        $this->newline();

        $this->info('Movements During Year');
        $this->line(sprintf('  Additions: %d assets ($%s)',
            $summary['additions_count'],
            number_format($summary['additions_value'], 2)
        ));
        $this->line(sprintf('  Disposals: %d assets ($%s)',
            $summary['disposals_count'],
            number_format($summary['disposals_value'], 2)
        ));
        $this->line(sprintf('  Revaluations (Increase): $%s', number_format($summary['revaluations_increase'], 2)));
        $this->line(sprintf('  Revaluations (Decrease): $%s', number_format($summary['revaluations_decrease'], 2)));
        $this->line(sprintf('  Impairments: $%s', number_format($summary['impairments'], 2)));
        $this->newline();

        $this->info('Closing Balance');
        $this->line(sprintf('  Total Assets: %d', $summary['closing_total_assets']));
        $this->line(sprintf('  Total Value: $%s', number_format($summary['closing_total_value'], 2)));
        $this->newline();

        $netMovement = $summary['closing_total_value'] - $summary['opening_total_value'];
        $this->bold(sprintf('Net Movement: $%s', number_format($netMovement, 2)));

        return 0;
    }

    private function reportInsurance(string $format): int
    {
        $policies = DB::table('ipsas_insurance as i')
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

        $this->bold('Insurance Coverage Report');
        $this->line(sprintf('Total Policies: %d', $policies->count()));
        $this->newline();

        $totalInsured = 0;
        $expiringSoon = 0;

        foreach ($policies as $p) {
            $daysToExpiry = floor((strtotime($p->coverage_end) - time()) / 86400);
            $expiryWarning = '';

            if ('active' === $p->status && $daysToExpiry <= 30) {
                $expiryWarning = ' [EXPIRING SOON]';
                ++$expiringSoon;
            }

            $this->line(sprintf('[%s] %s - %s%s',
                $p->policy_number,
                ucfirst($p->policy_type),
                $p->insurer,
                $expiryWarning
            ));
            $this->line(sprintf('     Asset: %s',
                $p->asset_number ? $p->asset_number . ' - ' . $p->asset_title : 'Blanket Coverage'
            ));
            $this->line(sprintf('     Sum Insured: %s %s | Period: %s to %s',
                $p->currency,
                number_format($p->sum_insured, 2),
                $p->coverage_start,
                $p->coverage_end
            ));
            $this->line(sprintf('     Status: %s', ucfirst($p->status)));
            $this->newline();

            if ('active' === $p->status) {
                $totalInsured += $p->sum_insured;
            }
        }

        $this->bold('Summary');
        $this->line(sprintf('  Total Insured Value: $%s', number_format($totalInsured, 2)));
        $this->line(sprintf('  Policies Expiring Soon: %d', $expiringSoon));

        return 0;
    }
}
