<?php

/**
 * NAZ Report Task
 *
 * Generates various NAZ compliance reports:
 * - Closure report
 * - Researcher statistics
 * - Transfer summary
 * - Annual compliance report
 *
 * Usage: php symfony naz:report [--type=summary] [--year=2025] [--format=text]
 */
class nazReportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_REQUIRED, 'Report type (summary|closures|permits|transfers|annual)', 'summary'),
            new sfCommandOption('year', null, sfCommandOption::PARAMETER_REQUIRED, 'Year for annual reports', date('Y')),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Output format (text|csv|json)', 'text'),
        ]);

        $this->namespace = 'naz';
        $this->name = 'report';
        $this->briefDescription = 'Generate NAZ compliance reports';
        $this->detailedDescription = <<<'EOF'
The [naz:report|INFO] task generates NAZ compliance reports:
  - summary: Overall compliance dashboard
  - closures: Closure period report
  - permits: Research permit statistics
  - transfers: Records transfer summary
  - annual: Annual compliance report for POTRAZ

Examples:
  [php symfony naz:report|INFO]                          Summary report
  [php symfony naz:report --type=annual --year=2025|INFO] Annual report
  [php symfony naz:report --type=permits --format=csv|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $type = $options['type'];
        $year = (int) $options['year'];
        $format = $options['format'];

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
                $this->logSection('error', 'Unknown report type: '.$type, null, 'ERROR');

                return 1;
        }
    }

    protected function reportSummary(string $format): int
    {
        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'closures' => [
                'active' => \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->where('status', 'active')->count(),
                'standard' => \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->where('status', 'active')
                    ->where('closure_type', 'standard')->count(),
                'extended' => \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->where('status', 'active')
                    ->where('closure_type', 'extended')->count(),
                'indefinite' => \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->where('status', 'active')
                    ->where('closure_type', 'indefinite')->count(),
                'ministerial' => \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->where('status', 'active')
                    ->where('closure_type', 'ministerial')->count(),
            ],
            'researchers' => [
                'total' => \Illuminate\Database\Capsule\Manager::table('naz_researcher')->count(),
                'local' => \Illuminate\Database\Capsule\Manager::table('naz_researcher')
                    ->where('researcher_type', 'local')
                    ->where('status', 'active')->count(),
                'foreign' => \Illuminate\Database\Capsule\Manager::table('naz_researcher')
                    ->where('researcher_type', 'foreign')
                    ->where('status', 'active')->count(),
                'institutional' => \Illuminate\Database\Capsule\Manager::table('naz_researcher')
                    ->where('researcher_type', 'institutional')
                    ->where('status', 'active')->count(),
            ],
            'permits' => [
                'active' => \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                    ->where('status', 'active')->count(),
                'pending' => \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                    ->where('status', 'pending')->count(),
                'issued_this_year' => \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                    ->whereYear('approved_date', date('Y'))
                    ->where('status', '!=', 'rejected')->count(),
            ],
            'transfers' => [
                'pending' => \Illuminate\Database\Capsule\Manager::table('naz_transfer')
                    ->whereIn('status', ['proposed', 'scheduled'])->count(),
                'accessioned_this_year' => \Illuminate\Database\Capsule\Manager::table('naz_transfer')
                    ->where('status', 'accessioned')
                    ->whereYear('actual_date', date('Y'))->count(),
            ],
            'protected_records' => \Illuminate\Database\Capsule\Manager::table('naz_protected_record')
                ->where('status', 'active')->count(),
            'schedules' => \Illuminate\Database\Capsule\Manager::table('naz_records_schedule')
                ->where('status', 'approved')->count(),
        ];

        if ('json' === $format) {
            echo json_encode($data, JSON_PRETTY_PRINT);

            return 0;
        }

        $this->logSection('naz', 'National Archives of Zimbabwe - Compliance Summary');
        $this->logSection('naz', 'Generated: '.$data['generated_at']);
        $this->log('');

        $this->logSection('closures', 'Closure Periods (Section 10)');
        $this->log(sprintf('  Active Closures: %d', $data['closures']['active']));
        $this->log(sprintf('    - Standard (25 years): %d', $data['closures']['standard']));
        $this->log(sprintf('    - Extended: %d', $data['closures']['extended']));
        $this->log(sprintf('    - Indefinite: %d', $data['closures']['indefinite']));
        $this->log(sprintf('    - Ministerial Order: %d', $data['closures']['ministerial']));
        $this->log('');

        $this->logSection('researchers', 'Registered Researchers');
        $this->log(sprintf('  Total Registered: %d', $data['researchers']['total']));
        $this->log(sprintf('    - Local: %d', $data['researchers']['local']));
        $this->log(sprintf('    - Foreign: %d', $data['researchers']['foreign']));
        $this->log(sprintf('    - Institutional: %d', $data['researchers']['institutional']));
        $this->log('');

        $this->logSection('permits', 'Research Permits');
        $this->log(sprintf('  Active Permits: %d', $data['permits']['active']));
        $this->log(sprintf('  Pending Applications: %d', $data['permits']['pending']));
        $this->log(sprintf('  Issued This Year: %d', $data['permits']['issued_this_year']));
        $this->log('');

        $this->logSection('transfers', 'Records Transfers');
        $this->log(sprintf('  Pending Transfers: %d', $data['transfers']['pending']));
        $this->log(sprintf('  Accessioned This Year: %d', $data['transfers']['accessioned_this_year']));
        $this->log('');

        $this->logSection('other', 'Other');
        $this->log(sprintf('  Protected Records (Section 12): %d', $data['protected_records']));
        $this->log(sprintf('  Approved Records Schedules: %d', $data['schedules']));

        return 0;
    }

    protected function reportClosures(string $format, int $year): int
    {
        $closures = \Illuminate\Database\Capsule\Manager::table('naz_closure_period as c')
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
                echo sprintf("%d,\"%s\",%s,%s,%s,%d,%s,%s\n",
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

        $this->logSection('naz', 'Closure Period Report');
        $this->logSection('naz', 'Total: '.$closures->count());
        $this->log('');

        foreach ($closures as $c) {
            $this->log(sprintf('[%d] %s', $c->id, $c->record_title ?? 'Record #'.$c->information_object_id));
            $this->log(sprintf('     Type: %s | Status: %s', ucfirst($c->closure_type), ucfirst($c->status)));
            $this->log(sprintf('     Period: %s to %s (%d years)',
                $c->start_date,
                $c->end_date ?? 'Indefinite',
                $c->years ?? 0
            ));
            if ($c->review_date) {
                $this->log(sprintf('     Review: %s', $c->review_date));
            }
            $this->log('');
        }

        return 0;
    }

    protected function reportPermits(string $format, int $year): int
    {
        $permits = \Illuminate\Database\Capsule\Manager::table('naz_research_permit as p')
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
                echo sprintf("%s,\"%s %s\",%s,%s,\"%s\",\"%s\",%s,%s,%.2f,%s\n",
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

        $this->logSection('naz', sprintf('Research Permit Report - %d', $year));
        $this->logSection('naz', sprintf('Total: %d permits', $permits->count()));
        $this->log('');

        $revenue = $permits->where('fee_paid', 1)->sum('fee_amount');
        $this->log(sprintf('Total Revenue: US$%.2f', $revenue));
        $this->log('');

        foreach ($permits as $p) {
            $this->log(sprintf('[%s] %s %s (%s)',
                $p->permit_number,
                $p->first_name,
                $p->last_name,
                ucfirst($p->researcher_type)
            ));
            $this->log(sprintf('     Topic: %s', substr($p->research_topic, 0, 60)));
            $this->log(sprintf('     Period: %s to %s | Status: %s',
                $p->start_date,
                $p->end_date,
                ucfirst($p->status)
            ));
            $this->log('');
        }

        return 0;
    }

    protected function reportTransfers(string $format, int $year): int
    {
        $transfers = \Illuminate\Database\Capsule\Manager::table('naz_transfer')
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
                echo sprintf("%s,\"%s\",%s,%s,%s,%s,%.2f,%d,%d\n",
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

        $this->logSection('naz', sprintf('Records Transfer Report - %d', $year));
        $this->log('');

        $totalVolume = $transfers->sum('quantity_linear_metres');
        $totalItems = $transfers->sum('quantity_items');
        $accessioned = $transfers->where('status', 'accessioned')->count();

        $this->log(sprintf('Total Transfers: %d', $transfers->count()));
        $this->log(sprintf('Accessioned: %d', $accessioned));
        $this->log(sprintf('Total Volume: %.2f linear metres', $totalVolume));
        $this->log(sprintf('Total Items: %d', $totalItems));
        $this->log('');

        foreach ($transfers as $t) {
            $this->log(sprintf('[%s] %s', $t->transfer_number, $t->transferring_agency));
            $this->log(sprintf('     Type: %s | Status: %s', ucfirst($t->transfer_type), ucfirst($t->status)));
            if ($t->quantity_linear_metres || $t->quantity_boxes) {
                $this->log(sprintf('     Quantity: %.2fm / %d boxes / %d items',
                    $t->quantity_linear_metres ?? 0,
                    $t->quantity_boxes ?? 0,
                    $t->quantity_items ?? 0
                ));
            }
            $this->log('');
        }

        return 0;
    }

    protected function reportAnnual(string $format, int $year): int
    {
        $data = [
            'report_year' => $year,
            'generated_at' => date('Y-m-d H:i:s'),
            'closures' => [
                'opened' => \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->whereYear('created_at', $year)->count(),
                'released' => \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->where('status', 'released')
                    ->whereYear('released_at', $year)->count(),
                'active_at_year_end' => \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->where('status', 'active')->count(),
            ],
            'researchers' => [
                'new_registrations' => \Illuminate\Database\Capsule\Manager::table('naz_researcher')
                    ->whereYear('registration_date', $year)->count(),
                'local' => \Illuminate\Database\Capsule\Manager::table('naz_researcher')
                    ->where('researcher_type', 'local')
                    ->whereYear('registration_date', $year)->count(),
                'foreign' => \Illuminate\Database\Capsule\Manager::table('naz_researcher')
                    ->where('researcher_type', 'foreign')
                    ->whereYear('registration_date', $year)->count(),
            ],
            'permits' => [
                'applications_received' => \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                    ->whereYear('created_at', $year)->count(),
                'approved' => \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                    ->whereYear('approved_date', $year)
                    ->whereIn('status', ['approved', 'active', 'expired'])->count(),
                'rejected' => \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                    ->whereYear('created_at', $year)
                    ->where('status', 'rejected')->count(),
                'revenue_usd' => \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                    ->where('fee_paid', 1)
                    ->whereYear('payment_date', $year)
                    ->sum('fee_amount'),
            ],
            'transfers' => [
                'proposed' => \Illuminate\Database\Capsule\Manager::table('naz_transfer')
                    ->whereYear('created_at', $year)->count(),
                'accessioned' => \Illuminate\Database\Capsule\Manager::table('naz_transfer')
                    ->where('status', 'accessioned')
                    ->whereYear('actual_date', $year)->count(),
                'volume_linear_metres' => \Illuminate\Database\Capsule\Manager::table('naz_transfer')
                    ->where('status', 'accessioned')
                    ->whereYear('actual_date', $year)
                    ->sum('quantity_linear_metres'),
                'items_accessioned' => \Illuminate\Database\Capsule\Manager::table('naz_transfer')
                    ->where('status', 'accessioned')
                    ->whereYear('actual_date', $year)
                    ->sum('quantity_items'),
            ],
            'visits' => \Illuminate\Database\Capsule\Manager::table('naz_research_visit')
                ->whereYear('visit_date', $year)->count(),
        ];

        if ('json' === $format) {
            echo json_encode($data, JSON_PRETTY_PRINT);

            return 0;
        }

        $this->logSection('naz', '═══════════════════════════════════════════════════════════════');
        $this->logSection('naz', '       NATIONAL ARCHIVES OF ZIMBABWE');
        $this->logSection('naz', sprintf('       Annual Compliance Report - %d', $year));
        $this->logSection('naz', '═══════════════════════════════════════════════════════════════');
        $this->log('');
        $this->log('Generated: '.$data['generated_at']);
        $this->log('');

        $this->logSection('section', '1. CLOSURE PERIODS (Section 10 - NAZ Act)');
        $this->log(sprintf('   New Closures Applied: %d', $data['closures']['opened']));
        $this->log(sprintf('   Closures Released: %d', $data['closures']['released']));
        $this->log(sprintf('   Active at Year End: %d', $data['closures']['active_at_year_end']));
        $this->log('');

        $this->logSection('section', '2. RESEARCHER REGISTRATIONS');
        $this->log(sprintf('   New Registrations: %d', $data['researchers']['new_registrations']));
        $this->log(sprintf('     - Local Researchers: %d', $data['researchers']['local']));
        $this->log(sprintf('     - Foreign Researchers: %d', $data['researchers']['foreign']));
        $this->log('');

        $this->logSection('section', '3. RESEARCH PERMITS');
        $this->log(sprintf('   Applications Received: %d', $data['permits']['applications_received']));
        $this->log(sprintf('   Permits Approved: %d', $data['permits']['approved']));
        $this->log(sprintf('   Applications Rejected: %d', $data['permits']['rejected']));
        $this->log(sprintf('   Revenue Collected: US$%.2f', $data['permits']['revenue_usd']));
        $this->log('');

        $this->logSection('section', '4. RECORDS TRANSFERS');
        $this->log(sprintf('   Transfers Proposed: %d', $data['transfers']['proposed']));
        $this->log(sprintf('   Transfers Accessioned: %d', $data['transfers']['accessioned']));
        $this->log(sprintf('   Volume Accessioned: %.2f linear metres', $data['transfers']['volume_linear_metres']));
        $this->log(sprintf('   Items Accessioned: %d', $data['transfers']['items_accessioned']));
        $this->log('');

        $this->logSection('section', '5. READING ROOM VISITS');
        $this->log(sprintf('   Total Visits: %d', $data['visits']));
        $this->log('');

        $this->logSection('naz', '═══════════════════════════════════════════════════════════════');

        return 0;
    }
}
