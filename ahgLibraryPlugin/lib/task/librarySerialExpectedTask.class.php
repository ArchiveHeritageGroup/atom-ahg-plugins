<?php

class librarySerialExpectedTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('months', null, sfCommandOption::PARAMETER_OPTIONAL, 'Generate expected issues for N months ahead', 3),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without creating'),
        ]);

        $this->namespace = 'library';
        $this->name = 'serial-expected';
        $this->briefDescription = 'Generate expected serial issues based on subscription frequency';
        $this->detailedDescription = <<<EOF
Creates expected issue records for active subscriptions based on
their frequency and issues_per_year. Also reports missing/late issues.

  php symfony library:serial-expected
  php symfony library:serial-expected --months=6
  php symfony library:serial-expected --dry-run
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $dryRun = !empty($options['dry-run']);
        $months = max(1, (int) ($options['months'] ?? 3));
        $horizon = date('Y-m-d', strtotime("+{$months} months"));
        $created = 0;
        $late = 0;

        // Frequency to days mapping
        $freqDays = [
            'daily' => 1, 'weekly' => 7, 'biweekly' => 14,
            'monthly' => 30, 'bimonthly' => 61, 'quarterly' => 91,
            'triannual' => 122, 'semiannual' => 183, 'annual' => 365,
        ];

        $subs = $db->table('library_subscription as s')
            ->join('library_item as li', 's.library_item_id', '=', 'li.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('li.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('s.status', 'active')
            ->select(['s.*', 'ioi.title'])
            ->get();

        if ($subs->isEmpty()) {
            $this->logSection('serial', 'No active subscriptions found.');
            return;
        }

        $this->logSection('serial', sprintf('Processing %d active subscription(s)...', $subs->count()));

        foreach ($subs as $sub) {
            $freq = $sub->frequency ?? 'monthly';
            $intervalDays = $freqDays[$freq] ?? 30;

            // Get the last expected issue date
            $lastExpected = $db->table('library_serial_issue')
                ->where('subscription_id', $sub->id)
                ->orderBy('expected_date', 'desc')
                ->value('expected_date');

            $startDate = $lastExpected
                ? date('Y-m-d', strtotime($lastExpected . " +{$intervalDays} days"))
                : date('Y-m-d');

            // Generate future expected issues
            $nextDate = $startDate;
            while ($nextDate <= $horizon) {
                // Check if already exists
                $exists = $db->table('library_serial_issue')
                    ->where('subscription_id', $sub->id)
                    ->where('expected_date', $nextDate)
                    ->exists();

                if (!$exists) {
                    if ($dryRun) {
                        $this->logSection('dry-run', sprintf(
                            'Would create: %s — expected %s',
                            $sub->title, $nextDate
                        ));
                    } else {
                        $db->table('library_serial_issue')->insert([
                            'subscription_id' => $sub->id,
                            'library_item_id' => $sub->library_item_id,
                            'expected_date'   => $nextDate,
                            'status'          => 'expected',
                            'created_at'      => now(),
                        ]);
                    }
                    $created++;
                }

                $nextDate = date('Y-m-d', strtotime($nextDate . " +{$intervalDays} days"));
            }

            // Report late issues (expected but not received, past expected date)
            $lateIssues = $db->table('library_serial_issue')
                ->where('subscription_id', $sub->id)
                ->where('status', 'expected')
                ->where('expected_date', '<', date('Y-m-d'))
                ->count();

            if ($lateIssues > 0) {
                $this->logSection('late', sprintf('%s — %d issue(s) overdue', $sub->title, $lateIssues));
                $late += $lateIssues;
            }
        }

        $this->logSection('', '');
        $this->logSection('serial', sprintf(
            'Done: %d issue(s) %s, %d late issue(s) flagged',
            $created, $dryRun ? 'would be created' : 'created', $late
        ));
    }
}
