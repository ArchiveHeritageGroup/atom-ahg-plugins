<?php

namespace AtomFramework\Console\Commands\Naz;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * List pending and overdue records transfers to National Archives.
 */
class TransferDueCommand extends BaseCommand
{
    protected string $name = 'naz:transfer-due';
    protected string $description = 'Check for records due for transfer';
    protected string $detailedDescription = <<<'EOF'
    Manages records transfers to NAZ:
      - Lists all pending transfers
      - Identifies overdue transfers (past proposed date)
      - Reports transfer statistics

    Examples:
      php bin/atom naz:transfer-due                         List all pending transfers
      php bin/atom naz:transfer-due --status=in_transit     List transfers in transit
      php bin/atom naz:transfer-due --agency="Ministry"     Filter by agency
      php bin/atom naz:transfer-due --format=csv            Export as CSV
    EOF;

    protected function configure(): void
    {
        $this->addOption('status', 's', 'Filter by status', '');
        $this->addOption('agency', 'a', 'Filter by agency name', '');
        $this->addOption('format', null, 'Output format (text|csv|json)', 'text');
    }

    protected function handle(): int
    {
        $format = $this->option('format', 'text');

        $this->bold('  National Archives of Zimbabwe - Records Transfer Status');
        $this->newline();

        // Build query
        $query = DB::table('naz_transfer as t')
            ->leftJoin('naz_records_schedule as s', 't.schedule_id', '=', 's.id')
            ->select([
                't.*',
                's.schedule_number',
                's.record_series',
            ]);

        $statusFilter = $this->option('status', '');
        $agencyFilter = $this->option('agency', '');

        if (!empty($statusFilter)) {
            $query->where('t.status', $statusFilter);
        } else {
            $query->whereIn('t.status', ['proposed', 'scheduled', 'in_transit']);
        }

        if (!empty($agencyFilter)) {
            $query->where('t.transferring_agency', 'like', '%' . $agencyFilter . '%');
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
                echo sprintf(
                    "%s,\"%s\",%s,%s,%s,%.2f,%d,%d,%s\n",
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
            $this->error(sprintf('  OVERDUE Transfers: %d', $overdue->count()));

            foreach ($overdue as $t) {
                $daysOverdue = floor((strtotime('today') - strtotime($t->proposed_date)) / 86400);
                $this->line(sprintf('    [%s] %s', $t->transfer_number, $t->transferring_agency));
                $this->line(sprintf('         Proposed: %s (%d days overdue)', $t->proposed_date, $daysOverdue));
                $this->line(sprintf('         Status: %s | Type: %s', ucfirst($t->status), $t->transfer_type));
                if ($t->quantity_linear_metres || $t->quantity_boxes) {
                    $this->line(sprintf(
                        '         Quantity: %.2fm / %d boxes / %d items',
                        $t->quantity_linear_metres ?? 0,
                        $t->quantity_boxes ?? 0,
                        $t->quantity_items ?? 0
                    ));
                }
                $this->newline();
            }
        }

        // Upcoming transfers
        $upcoming = $transfers->filter(function ($t) {
            return !$t->proposed_date || strtotime($t->proposed_date) >= strtotime('today');
        });

        $this->info(sprintf('  Upcoming/Pending Transfers: %d', $upcoming->count()));

        foreach ($upcoming as $t) {
            $this->line(sprintf('    [%s] %s', $t->transfer_number, $t->transferring_agency));
            $this->line(sprintf(
                '         Proposed: %s | Status: %s',
                $t->proposed_date ?? 'Not scheduled',
                ucfirst($t->status)
            ));
            if ($t->record_series) {
                $this->line(sprintf('         Series: %s', $t->record_series));
            }
            $this->newline();
        }

        // Statistics
        $this->newline();
        $this->info('  Transfer Statistics');

        $stats = $this->getStats();
        foreach ($stats['by_status'] as $status => $count) {
            $this->line(sprintf('    %s: %d', ucfirst($status), $count));
        }

        $this->newline();
        $this->line(sprintf(
            '    Total Volume Accessioned (This Year): %.2f linear metres',
            $stats['volume_this_year']
        ));
        $this->line(sprintf(
            '    Total Items Accessioned (This Year): %d',
            $stats['items_this_year']
        ));

        return 0;
    }

    private function getStats(): array
    {
        $byStatus = DB::table('naz_transfer')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $volumeThisYear = DB::table('naz_transfer')
            ->where('status', 'accessioned')
            ->whereYear('actual_date', date('Y'))
            ->sum('quantity_linear_metres');

        $itemsThisYear = DB::table('naz_transfer')
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
