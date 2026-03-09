<?php

class libraryOverdueCheckTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('notify', null, sfCommandOption::PARAMETER_NONE, 'Send overdue notifications to patrons'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Minimum days overdue to report', 1),
        ]);

        $this->namespace = 'library';
        $this->name = 'overdue-check';
        $this->briefDescription = 'Check for overdue checkouts and optionally notify patrons';
        $this->detailedDescription = <<<EOF
Scans active checkouts past their due date. Reports overdue items
and optionally flags them for notification.

  php symfony library:overdue-check
  php symfony library:overdue-check --days=7
  php symfony library:overdue-check --notify
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $minDays = max(1, (int) ($options['days'] ?? 1));
        $cutoff = date('Y-m-d', strtotime("-{$minDays} days"));

        $overdue = $db->table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->join('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('li.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->join('library_patron as p', 'c.patron_id', '=', 'p.id')
            ->where('c.status', 'active')
            ->where('c.due_date', '<', $cutoff)
            ->select([
                'c.id as checkout_id',
                'p.card_number',
                'p.first_name',
                'p.last_name',
                'p.email',
                'ioi.title',
                'cp.barcode',
                'c.due_date',
                $db->raw('DATEDIFF(CURDATE(), c.due_date) as days_overdue'),
            ])
            ->orderBy('days_overdue', 'desc')
            ->get();

        if ($overdue->isEmpty()) {
            $this->logSection('library', 'No overdue checkouts found.');
            return;
        }

        $this->logSection('library', sprintf('Found %d overdue checkout(s) (>= %d days)', $overdue->count(), $minDays));
        $this->logSection('', '');

        foreach ($overdue as $row) {
            $this->logSection('overdue', sprintf(
                '%s — %s (%s) — %d days overdue — Patron: %s %s [%s]',
                $row->barcode ?? 'N/A',
                $row->title,
                $row->due_date,
                $row->days_overdue,
                $row->first_name,
                $row->last_name,
                $row->card_number
            ));
        }

        if (!empty($options['notify'])) {
            $notified = 0;
            foreach ($overdue as $row) {
                if (!empty($row->email)) {
                    // Flag checkout for notification (actual email sending via separate system)
                    $db->table('library_checkout')
                        ->where('id', $row->checkout_id)
                        ->update(['checkout_notes' => $db->raw("CONCAT(COALESCE(checkout_notes, ''), '\n[Overdue notice sent " . date('Y-m-d') . "]')")]);
                    $notified++;
                }
            }
            $this->logSection('notify', sprintf('%d patron(s) flagged for notification', $notified));
        }

        $this->logSection('', '');
        $this->logSection('library', 'Done.');
    }
}
