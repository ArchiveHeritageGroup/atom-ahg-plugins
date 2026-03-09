<?php

class libraryHoldExpiryTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without expiring'),
        ]);

        $this->namespace = 'library';
        $this->name = 'hold-expiry';
        $this->briefDescription = 'Expire unfulfilled holds past their expiry date';
        $this->detailedDescription = <<<EOF
Checks pending and ready holds with expiry dates in the past.
Marks them expired and promotes next patron in queue.

  php symfony library:hold-expiry
  php symfony library:hold-expiry --dry-run
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $dryRun = !empty($options['dry-run']);
        $today = date('Y-m-d');
        $expired = 0;

        $holds = $db->table('library_hold as h')
            ->join('library_patron as p', 'h.patron_id', '=', 'p.id')
            ->join('library_item as li', 'h.library_item_id', '=', 'li.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('li.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->whereIn('h.status', ['pending', 'ready'])
            ->whereNotNull('h.expiry_date')
            ->where('h.expiry_date', '<', $today)
            ->select([
                'h.id', 'h.library_item_id', 'h.queue_position',
                'p.first_name', 'p.last_name', 'p.card_number',
                'ioi.title',
            ])
            ->get();

        if ($holds->isEmpty()) {
            $this->logSection('holds', 'No expired holds found.');
            return;
        }

        $this->logSection('holds', sprintf('Found %d expired hold(s)', $holds->count()));

        foreach ($holds as $hold) {
            if ($dryRun) {
                $this->logSection('dry-run', sprintf(
                    'Would expire hold #%d — %s — %s %s [%s]',
                    $hold->id, $hold->title, $hold->first_name, $hold->last_name, $hold->card_number
                ));
                $expired++;
                continue;
            }

            $db->table('library_hold')
                ->where('id', $hold->id)
                ->update([
                    'status' => 'expired',
                    'cancelled_date' => now(),
                    'cancel_reason' => 'Expired — not collected by expiry date',
                ]);

            // Promote next in queue for this item
            $db->table('library_hold')
                ->where('library_item_id', $hold->library_item_id)
                ->where('status', 'pending')
                ->where('queue_position', '>', $hold->queue_position)
                ->decrement('queue_position');

            $expired++;

            $this->logSection('expired', sprintf(
                'Hold #%d — %s — %s %s',
                $hold->id, $hold->title, $hold->first_name, $hold->last_name
            ));
        }

        $this->logSection('', '');
        $this->logSection('holds', sprintf('Done: %d hold(s) %s', $expired, $dryRun ? 'would be expired' : 'expired'));
    }
}
