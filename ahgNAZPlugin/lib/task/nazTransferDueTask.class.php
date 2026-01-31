<?php

/**
 * NAZ Transfer Due Task
 *
 * Manages records transfers to National Archives:
 * - Lists pending transfers
 * - Identifies overdue transfers
 * - Reports transfer statistics
 *
 * Usage: php symfony naz:transfer-due [--status=pending] [--format=text]
 */
class nazTransferDueTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_REQUIRED, 'Filter by status', ''),
            new sfCommandOption('agency', null, sfCommandOption::PARAMETER_REQUIRED, 'Filter by agency name', ''),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Output format (text|csv|json)', 'text'),
        ]);

        $this->namespace = 'naz';
        $this->name = 'transfer-due';
        $this->briefDescription = 'List pending and overdue records transfers';
        $this->detailedDescription = <<<'EOF'
The [naz:transfer-due|INFO] task manages records transfers to NAZ:
  - Lists all pending transfers
  - Identifies overdue transfers (past proposed date)
  - Reports transfer statistics

Examples:
  [php symfony naz:transfer-due|INFO]                         List all pending transfers
  [php symfony naz:transfer-due --status=in_transit|INFO]     List transfers in transit
  [php symfony naz:transfer-due --agency="Ministry"|INFO]     Filter by agency
  [php symfony naz:transfer-due --format=csv|INFO]            Export as CSV
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $format = $options['format'];

        $this->logSection('naz', 'National Archives of Zimbabwe - Records Transfer Status');
        $this->log('');

        // Build query
        $query = \Illuminate\Database\Capsule\Manager::table('naz_transfer as t')
            ->leftJoin('naz_records_schedule as s', 't.schedule_id', '=', 's.id')
            ->select([
                't.*',
                's.schedule_number',
                's.record_series',
            ]);

        if (!empty($options['status'])) {
            $query->where('t.status', $options['status']);
        } else {
            $query->whereIn('t.status', ['proposed', 'scheduled', 'in_transit']);
        }

        if (!empty($options['agency'])) {
            $query->where('t.transferring_agency', 'like', '%'.$options['agency'].'%');
        }

        $transfers = $query->orderBy('t.proposed_date')->get();

        // Get overdue transfers
        $overdue = $transfers->filter(function ($t) {
            return $t->proposed_date && strtotime($t->proposed_date) < strtotime('today');
        });

        if ('json' === $format) {
            echo json_encode([
                'transfers' => $transfers->toArray(),
                'overdue_count' => $overdue->count(),
                'statistics' => $this->getStats(),
            ], JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "Transfer Number,Agency,Type,Proposed Date,Status,Quantity (m),Boxes,Items,Overdue\n";
            foreach ($transfers as $t) {
                $isOverdue = $t->proposed_date && strtotime($t->proposed_date) < strtotime('today');
                echo sprintf("%s,\"%s\",%s,%s,%s,%.2f,%d,%d,%s\n",
                    $t->transfer_number,
                    str_replace('"', '""', $t->transferring_agency),
                    $t->transfer_type,
                    $t->proposed_date ?? '',
                    $t->status,
                    $t->quantity_linear_metres ?? 0,
                    $t->quantity_boxes ?? 0,
                    $t->quantity_items ?? 0,
                    $isOverdue ? 'YES' : 'NO'
                );
            }

            return 0;
        }

        // Text output
        if ($overdue->count() > 0) {
            $this->logSection('overdue', sprintf('OVERDUE Transfers: %d', $overdue->count()), null, 'ERROR');

            foreach ($overdue as $t) {
                $daysOverdue = floor((strtotime('today') - strtotime($t->proposed_date)) / 86400);
                $this->log(sprintf('  [%s] %s', $t->transfer_number, $t->transferring_agency));
                $this->log(sprintf('       Proposed: %s (%d days overdue)', $t->proposed_date, $daysOverdue));
                $this->log(sprintf('       Status: %s | Type: %s', ucfirst($t->status), $t->transfer_type));
                if ($t->quantity_linear_metres || $t->quantity_boxes) {
                    $this->log(sprintf('       Quantity: %.2fm / %d boxes / %d items',
                        $t->quantity_linear_metres ?? 0,
                        $t->quantity_boxes ?? 0,
                        $t->quantity_items ?? 0
                    ));
                }
                $this->log('');
            }
        }

        // Upcoming transfers
        $upcoming = $transfers->filter(function ($t) {
            return !$t->proposed_date || strtotime($t->proposed_date) >= strtotime('today');
        });

        $this->logSection('upcoming', sprintf('Upcoming/Pending Transfers: %d', $upcoming->count()));

        foreach ($upcoming as $t) {
            $this->log(sprintf('  [%s] %s', $t->transfer_number, $t->transferring_agency));
            $this->log(sprintf('       Proposed: %s | Status: %s',
                $t->proposed_date ?? 'Not scheduled',
                ucfirst($t->status)
            ));
            if ($t->record_series) {
                $this->log(sprintf('       Series: %s', $t->record_series));
            }
            $this->log('');
        }

        // Statistics
        $this->log('');
        $this->logSection('stats', 'Transfer Statistics');

        $stats = $this->getStats();
        foreach ($stats['by_status'] as $status => $count) {
            $this->log(sprintf('  %s: %d', ucfirst($status), $count));
        }

        $this->log('');
        $this->log(sprintf('  Total Volume Accessioned (This Year): %.2f linear metres',
            $stats['volume_this_year']
        ));
        $this->log(sprintf('  Total Items Accessioned (This Year): %d',
            $stats['items_this_year']
        ));

        return 0;
    }

    protected function getStats(): array
    {
        $byStatus = \Illuminate\Database\Capsule\Manager::table('naz_transfer')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $volumeThisYear = \Illuminate\Database\Capsule\Manager::table('naz_transfer')
            ->where('status', 'accessioned')
            ->whereYear('actual_date', date('Y'))
            ->sum('quantity_linear_metres');

        $itemsThisYear = \Illuminate\Database\Capsule\Manager::table('naz_transfer')
            ->where('status', 'accessioned')
            ->whereYear('actual_date', date('Y'))
            ->sum('quantity_items');

        return [
            'by_status' => $byStatus,
            'volume_this_year' => $volumeThisYear ?? 0,
            'items_this_year' => $itemsThisYear ?? 0,
        ];
    }
}
