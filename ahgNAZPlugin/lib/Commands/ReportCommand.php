<?php

namespace AtomFramework\Console\Commands\Naz;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Generate NAZ compliance reports.
 */
class ReportCommand extends BaseCommand
{
    protected string $name = 'naz:report';
    protected string $description = 'Generate NAZ compliance reports';
    protected string $detailedDescription = <<<'EOF'
    Generates NAZ compliance reports:
      - summary: Overall compliance dashboard
      - closures: Closure period report
      - permits: Research permit statistics
      - transfers: Records transfer summary
      - annual: Annual compliance report for POTRAZ

    Examples:
      php bin/atom naz:report                            Summary report
      php bin/atom naz:report --type=annual --year=2025  Annual report
      php bin/atom naz:report --type=permits --format=csv
    EOF;

    protected function configure(): void
    {
        $this->addOption('type', 't', 'Report type (summary|closures|permits|transfers|annual)', 'summary');
        $this->addOption('year', 'y', 'Year for annual reports', date('Y'));
        $this->addOption('format', null, 'Output format (text|csv|json)', 'text');
    }

    protected function handle(): int
    {
        $type = $this->option('type', 'summary');
        $year = (int) $this->option('year', date('Y'));
        $format = $this->option('format', 'text');

        switch ($type) {
            case 'summary':
                return $this->reportSummary($format);

            case 'closures':
                return $this->reportClosures($format, $year);

            case 'permits':
                return $this->reportPermits($format, $year);

            case 'transfers':
                return $this->reportTransfers($format, $year);

            case 'annual':
                return $this->reportAnnual($format, $year);

            default:
                $this->error("Unknown report type: {$type}");

                return 1;
        }
    }

    private function reportSummary(string $format): int
    {
        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'closures' => [
                'active' => DB::table('naz_closure_period')
                    ->where('status', 'active')->count(),
                'standard' => DB::table('naz_closure_period')
                    ->where('status', 'active')
                    ->where('closure_type', 'standard')->count(),
                'extended' => DB::table('naz_closure_period')
                    ->where('status', 'active')
                    ->where('closure_type', 'extended')->count(),
                'indefinite' => DB::table('naz_closure_period')
                    ->where('status', 'active')
                    ->where('closure_type', 'indefinite')->count(),
                'ministerial' => DB::table('naz_closure_period')
                    ->where('status', 'active')
                    ->where('closure_type', 'ministerial')->count(),
            ],
            'researchers' => [
                'total' => DB::table('naz_researcher')->count(),
                'local' => DB::table('naz_researcher')
                    ->where('researcher_type', 'local')
                    ->where('status', 'active')->count(),
                'foreign' => DB::table('naz_researcher')
                    ->where('researcher_type', 'foreign')
                    ->where('status', 'active')->count(),
                'institutional' => DB::table('naz_researcher')
                    ->where('researcher_type', 'institutional')
                    ->where('status', 'active')->count(),
            ],
            'permits' => [
                'active' => DB::table('naz_research_permit')
                    ->where('status', 'active')->count(),
                'pending' => DB::table('naz_research_permit')
                    ->where('status', 'pending')->count(),
                'issued_this_year' => DB::table('naz_research_permit')
                    ->whereYear('approved_date', date('Y'))
                    ->where('status', '!=', 'rejected')->count(),
            ],
            'transfers' => [
                'pending' => DB::table('naz_transfer')
                    ->whereIn('status', ['proposed', 'scheduled'])->count(),
                'accessioned_this_year' => DB::table('naz_transfer')
                    ->where('status', 'accessioned')
                    ->whereYear('actual_date', date('Y'))->count(),
            ],
            'protected_records' => DB::table('naz_protected_record')
                ->where('status', 'active')->count(),
            'schedules' => DB::table('naz_records_schedule')
                ->where('status', 'approved')->count(),
        ];

        if ('json' === $format) {
            echo json_encode($data, JSON_PRETTY_PRINT);

            return 0;
        }

        $this->bold('  National Archives of Zimbabwe - Compliance Summary');
        $this->line('  Generated: ' . $data['generated_at']);
        $this->newline();

        $this->info('  Closure Periods (Section 10)');
        $this->line(sprintf('    Active Closures: %d', $data['closures']['active']));
        $this->line(sprintf('      - Standard (25 years): %d', $data['closures']['standard']));
        $this->line(sprintf('      - Extended: %d', $data['closures']['extended']));
        $this->line(sprintf('      - Indefinite: %d', $data['closures']['indefinite']));
        $this->line(sprintf('      - Ministerial Order: %d', $data['closures']['ministerial']));
        $this->newline();

        $this->info('  Registered Researchers');
        $this->line(sprintf('    Total Registered: %d', $data['researchers']['total']));
        $this->line(sprintf('      - Local: %d', $data['researchers']['local']));
        $this->line(sprintf('      - Foreign: %d', $data['researchers']['foreign']));
        $this->line(sprintf('      - Institutional: %d', $data['researchers']['institutional']));
        $this->newline();

        $this->info('  Research Permits');
        $this->line(sprintf('    Active Permits: %d', $data['permits']['active']));
        $this->line(sprintf('    Pending Applications: %d', $data['permits']['pending']));
        $this->line(sprintf('    Issued This Year: %d', $data['permits']['issued_this_year']));
        $this->newline();

        $this->info('  Records Transfers');
        $this->line(sprintf('    Pending Transfers: %d', $data['transfers']['pending']));
        $this->line(sprintf('    Accessioned This Year: %d', $data['transfers']['accessioned_this_year']));
        $this->newline();

        $this->info('  Other');
        $this->line(sprintf('    Protected Records (Section 12): %d', $data['protected_records']));
        $this->line(sprintf('    Approved Records Schedules: %d', $data['schedules']));

        return 0;
    }

    private function reportClosures(string $format, int $year): int
    {
        $closures = DB::table('naz_closure_period as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select(['c.*', 'ioi.title as record_title'])
            ->orderBy('c.end_date')
            ->get();

        if ('json' === $format) {
            echo json_encode($closures->toArray(), JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "ID,Record Title,Type,Start Date,End Date,Years,Status,Review Date\n";
            foreach ($closures as $c) {
                echo sprintf(
                    "%d,\"%s\",%s,%s,%s,%d,%s,%s\n",
                    $c->id,
                    str_replace('"', '""', $c->record_title ?? 'N/A'),
                    $c->closure_type,
                    $c->start_date,
                    $c->end_date ?? 'Indefinite',
                    $c->years ?? 0,
                    $c->status,
                    $c->review_date ?? ''
                );
            }

            return 0;
        }

        $this->bold('  Closure Period Report');
        $this->line('  Total: ' . $closures->count());
        $this->newline();

        foreach ($closures as $c) {
            $this->line(sprintf('  [%d] %s', $c->id, $c->record_title ?? 'Record #' . $c->information_object_id));
            $this->line(sprintf('       Type: %s | Status: %s', ucfirst($c->closure_type), ucfirst($c->status)));
            $this->line(sprintf(
                '       Period: %s to %s (%d years)',
                $c->start_date,
                $c->end_date ?? 'Indefinite',
                $c->years ?? 0
            ));
            if ($c->review_date) {
                $this->line(sprintf('       Review: %s', $c->review_date));
            }
            $this->newline();
        }

        return 0;
    }

    private function reportPermits(string $format, int $year): int
    {
        $permits = DB::table('naz_research_permit as p')
            ->join('naz_researcher as r', 'p.researcher_id', '=', 'r.id')
            ->whereYear('p.created_at', $year)
            ->select([
                'p.*',
                'r.first_name',
                'r.last_name',
                'r.researcher_type',
                'r.nationality',
                'r.institution',
            ])
            ->orderBy('p.created_at', 'desc')
            ->get();

        if ('json' === $format) {
            echo json_encode($permits->toArray(), JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "Permit Number,Researcher,Type,Nationality,Institution,Topic,Start,End,Fee,Status\n";
            foreach ($permits as $p) {
                echo sprintf(
                    "%s,\"%s %s\",%s,%s,\"%s\",\"%s\",%s,%s,%.2f,%s\n",
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $p->researcher_type,
                    $p->nationality ?? '',
                    str_replace('"', '""', $p->institution ?? ''),
                    str_replace('"', '""', $p->research_topic),
                    $p->start_date,
                    $p->end_date,
                    $p->fee_amount ?? 0,
                    $p->status
                );
            }

            return 0;
        }

        $this->bold(sprintf('  Research Permit Report - %d', $year));
        $this->line(sprintf('  Total: %d permits', $permits->count()));
        $this->newline();

        $revenue = $permits->where('fee_paid', 1)->sum('fee_amount');
        $this->line(sprintf('  Total Revenue: US$%.2f', $revenue));
        $this->newline();

        foreach ($permits as $p) {
            $this->line(sprintf(
                '  [%s] %s %s (%s)',
                $p->permit_number,
                $p->first_name,
                $p->last_name,
                ucfirst($p->researcher_type)
            ));
            $this->line(sprintf('       Topic: %s', substr($p->research_topic, 0, 60)));
            $this->line(sprintf(
                '       Period: %s to %s | Status: %s',
                $p->start_date,
                $p->end_date,
                ucfirst($p->status)
            ));
            $this->newline();
        }

        return 0;
    }

    private function reportTransfers(string $format, int $year): int
    {
        $transfers = DB::table('naz_transfer')
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->get();

        if ('json' === $format) {
            echo json_encode($transfers->toArray(), JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "Transfer Number,Agency,Type,Proposed Date,Actual Date,Status,Linear Metres,Boxes,Items\n";
            foreach ($transfers as $t) {
                echo sprintf(
                    "%s,\"%s\",%s,%s,%s,%s,%.2f,%d,%d\n",
                    $t->transfer_number,
                    str_replace('"', '""', $t->transferring_agency),
                    $t->transfer_type,
                    $t->proposed_date ?? '',
                    $t->actual_date ?? '',
                    $t->status,
                    $t->quantity_linear_metres ?? 0,
                    $t->quantity_boxes ?? 0,
                    $t->quantity_items ?? 0
                );
            }

            return 0;
        }

        $this->bold(sprintf('  Records Transfer Report - %d', $year));
        $this->newline();

        $totalVolume = $transfers->sum('quantity_linear_metres');
        $totalItems = $transfers->sum('quantity_items');
        $accessioned = $transfers->where('status', 'accessioned')->count();

        $this->line(sprintf('  Total Transfers: %d', $transfers->count()));
        $this->line(sprintf('  Accessioned: %d', $accessioned));
        $this->line(sprintf('  Total Volume: %.2f linear metres', $totalVolume));
        $this->line(sprintf('  Total Items: %d', $totalItems));
        $this->newline();

        foreach ($transfers as $t) {
            $this->line(sprintf('  [%s] %s', $t->transfer_number, $t->transferring_agency));
            $this->line(sprintf('       Type: %s | Status: %s', ucfirst($t->transfer_type), ucfirst($t->status)));
            if ($t->quantity_linear_metres || $t->quantity_boxes) {
                $this->line(sprintf(
                    '       Quantity: %.2fm / %d boxes / %d items',
                    $t->quantity_linear_metres ?? 0,
                    $t->quantity_boxes ?? 0,
                    $t->quantity_items ?? 0
                ));
            }
            $this->newline();
        }

        return 0;
    }

    private function reportAnnual(string $format, int $year): int
    {
        $data = [
            'report_year' => $year,
            'generated_at' => date('Y-m-d H:i:s'),
            'closures' => [
                'opened' => DB::table('naz_closure_period')
                    ->whereYear('created_at', $year)->count(),
                'released' => DB::table('naz_closure_period')
                    ->where('status', 'released')
                    ->whereYear('released_at', $year)->count(),
                'active_at_year_end' => DB::table('naz_closure_period')
                    ->where('status', 'active')->count(),
            ],
            'researchers' => [
                'new_registrations' => DB::table('naz_researcher')
                    ->whereYear('registration_date', $year)->count(),
                'local' => DB::table('naz_researcher')
                    ->where('researcher_type', 'local')
                    ->whereYear('registration_date', $year)->count(),
                'foreign' => DB::table('naz_researcher')
                    ->where('researcher_type', 'foreign')
                    ->whereYear('registration_date', $year)->count(),
            ],
            'permits' => [
                'applications_received' => DB::table('naz_research_permit')
                    ->whereYear('created_at', $year)->count(),
                'approved' => DB::table('naz_research_permit')
                    ->whereYear('approved_date', $year)
                    ->whereIn('status', ['approved', 'active', 'expired'])->count(),
                'rejected' => DB::table('naz_research_permit')
                    ->whereYear('created_at', $year)
                    ->where('status', 'rejected')->count(),
                'revenue_usd' => DB::table('naz_research_permit')
                    ->where('fee_paid', 1)
                    ->whereYear('payment_date', $year)
                    ->sum('fee_amount'),
            ],
            'transfers' => [
                'proposed' => DB::table('naz_transfer')
                    ->whereYear('created_at', $year)->count(),
                'accessioned' => DB::table('naz_transfer')
                    ->where('status', 'accessioned')
                    ->whereYear('actual_date', $year)->count(),
                'volume_linear_metres' => DB::table('naz_transfer')
                    ->where('status', 'accessioned')
                    ->whereYear('actual_date', $year)
                    ->sum('quantity_linear_metres'),
                'items_accessioned' => DB::table('naz_transfer')
                    ->where('status', 'accessioned')
                    ->whereYear('actual_date', $year)
                    ->sum('quantity_items'),
            ],
            'visits' => DB::table('naz_research_visit')
                ->whereYear('visit_date', $year)->count(),
        ];

        if ('json' === $format) {
            echo json_encode($data, JSON_PRETTY_PRINT);

            return 0;
        }

        $this->line('');
        $this->bold('  ===============================================================');
        $this->bold('         NATIONAL ARCHIVES OF ZIMBABWE');
        $this->bold(sprintf('         Annual Compliance Report - %d', $year));
        $this->bold('  ===============================================================');
        $this->newline();
        $this->line('  Generated: ' . $data['generated_at']);
        $this->newline();

        $this->info('  1. CLOSURE PERIODS (Section 10 - NAZ Act)');
        $this->line(sprintf('     New Closures Applied: %d', $data['closures']['opened']));
        $this->line(sprintf('     Closures Released: %d', $data['closures']['released']));
        $this->line(sprintf('     Active at Year End: %d', $data['closures']['active_at_year_end']));
        $this->newline();

        $this->info('  2. RESEARCHER REGISTRATIONS');
        $this->line(sprintf('     New Registrations: %d', $data['researchers']['new_registrations']));
        $this->line(sprintf('       - Local Researchers: %d', $data['researchers']['local']));
        $this->line(sprintf('       - Foreign Researchers: %d', $data['researchers']['foreign']));
        $this->newline();

        $this->info('  3. RESEARCH PERMITS');
        $this->line(sprintf('     Applications Received: %d', $data['permits']['applications_received']));
        $this->line(sprintf('     Permits Approved: %d', $data['permits']['approved']));
        $this->line(sprintf('     Applications Rejected: %d', $data['permits']['rejected']));
        $this->line(sprintf('     Revenue Collected: US$%.2f', $data['permits']['revenue_usd']));
        $this->newline();

        $this->info('  4. RECORDS TRANSFERS');
        $this->line(sprintf('     Transfers Proposed: %d', $data['transfers']['proposed']));
        $this->line(sprintf('     Transfers Accessioned: %d', $data['transfers']['accessioned']));
        $this->line(sprintf('     Volume Accessioned: %.2f linear metres', $data['transfers']['volume_linear_metres']));
        $this->line(sprintf('     Items Accessioned: %d', $data['transfers']['items_accessioned']));
        $this->newline();

        $this->info('  5. READING ROOM VISITS');
        $this->line(sprintf('     Total Visits: %d', $data['visits']));
        $this->newline();

        $this->bold('  ===============================================================');

        return 0;
    }
}
