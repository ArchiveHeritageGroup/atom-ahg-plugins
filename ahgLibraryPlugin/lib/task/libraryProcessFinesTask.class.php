<?php

class libraryProcessFinesTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview fines without creating them'),
        ]);

        $this->namespace = 'library';
        $this->name = 'process-fines';
        $this->briefDescription = 'Process daily overdue fines for active checkouts past due date';
        $this->detailedDescription = <<<EOF
Calculates and creates overdue fines based on loan rules.
Respects grace periods and fine caps. Run daily via cron.

  php symfony library:process-fines
  php symfony library:process-fines --dry-run
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $dryRun = !empty($options['dry-run']);
        $today = date('Y-m-d');
        $created = 0;
        $skipped = 0;

        // Get all active overdue checkouts
        $overdue = $db->table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->join('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('li.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('c.status', 'active')
            ->where('c.due_date', '<', $today)
            ->select([
                'c.id as checkout_id',
                'c.patron_id',
                'c.due_date',
                'li.material_type',
                'ioi.title',
                $db->raw('DATEDIFF(CURDATE(), c.due_date) as days_overdue'),
            ])
            ->get();

        if ($overdue->isEmpty()) {
            $this->logSection('fines', 'No overdue checkouts. Nothing to process.');
            return;
        }

        $this->logSection('fines', sprintf('Processing %d overdue checkout(s)...', $overdue->count()));

        // Cache loan rules
        $loanRules = $db->table('library_loan_rule')->get()->keyBy(function ($r) {
            return $r->material_type . '|' . $r->patron_type;
        });

        foreach ($overdue as $row) {
            // Find applicable loan rule (exact → material default → global)
            $patronType = $db->table('library_patron')->where('id', $row->patron_id)->value('patron_type') ?? 'public';
            $rule = $loanRules->get($row->material_type . '|' . $patronType)
                ?? $loanRules->get($row->material_type . '|*')
                ?? $loanRules->first();

            if (!$rule) {
                $skipped++;
                continue;
            }

            // Check grace period
            $effectiveOverdue = $row->days_overdue - $rule->grace_period_days;
            if ($effectiveOverdue <= 0) {
                $skipped++;
                continue;
            }

            // Check if fine already exists for today
            $existsToday = $db->table('library_fine')
                ->where('checkout_id', $row->checkout_id)
                ->where('fine_date', $today)
                ->exists();

            if ($existsToday) {
                $skipped++;
                continue;
            }

            // Calculate fine amount
            $fineAmount = (float) $rule->fine_per_day;

            // Check fine cap
            if ($rule->fine_cap > 0) {
                $totalExisting = (float) $db->table('library_fine')
                    ->where('checkout_id', $row->checkout_id)
                    ->where('fine_type', 'overdue')
                    ->sum('amount');

                if ($totalExisting >= $rule->fine_cap) {
                    $skipped++;
                    continue;
                }

                // Don't exceed cap
                if (($totalExisting + $fineAmount) > $rule->fine_cap) {
                    $fineAmount = $rule->fine_cap - $totalExisting;
                }
            }

            if ($fineAmount <= 0) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->logSection('dry-run', sprintf(
                    'Would fine R%.2f — %s — %d days overdue — Patron #%d',
                    $fineAmount, $row->title, $row->days_overdue, $row->patron_id
                ));
                $created++;
                continue;
            }

            // Create fine
            $db->table('library_fine')->insert([
                'patron_id'   => $row->patron_id,
                'checkout_id' => $row->checkout_id,
                'fine_type'   => 'overdue',
                'amount'      => $fineAmount,
                'paid_amount' => 0,
                'currency'    => 'ZAR',
                'status'      => 'outstanding',
                'description' => "Overdue: {$row->title} (day {$row->days_overdue})",
                'fine_date'   => $today,
                'created_at'  => now(),
            ]);

            // Update patron balance
            $db->table('library_patron')
                ->where('id', $row->patron_id)
                ->increment('total_fines_owed', $fineAmount);

            $created++;

            $this->logSection('fine', sprintf(
                'R%.2f — %s — %d days overdue — Patron #%d',
                $fineAmount, $row->title, $row->days_overdue, $row->patron_id
            ));
        }

        $this->logSection('', '');
        $this->logSection('fines', sprintf(
            'Done: %d fine(s) %s, %d skipped',
            $created, $dryRun ? 'would be created' : 'created', $skipped
        ));
    }
}
