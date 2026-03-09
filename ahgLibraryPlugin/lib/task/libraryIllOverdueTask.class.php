<?php

class libraryIllOverdueTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Minimum days overdue', 1),
        ]);

        $this->namespace = 'library';
        $this->name = 'ill-overdue';
        $this->briefDescription = 'Check for overdue interlibrary loan items';
        $this->detailedDescription = <<<EOF
Reports ILL borrow requests where items are past their due date
and lend requests where partner libraries haven't returned items.

  php symfony library:ill-overdue
  php symfony library:ill-overdue --days=7
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $minDays = max(1, (int) ($options['days'] ?? 1));
        $cutoff = date('Y-m-d', strtotime("-{$minDays} days"));

        // Borrowed items overdue (we borrowed, need to return)
        $borrowed = $db->table('library_ill_request')
            ->where('direction', 'borrow')
            ->whereIn('status', ['received', 'in_use'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', $cutoff)
            ->select([
                'id', 'request_number', 'title', 'author',
                'partner_library', 'due_date',
                $db->raw('DATEDIFF(CURDATE(), due_date) as days_overdue'),
            ])
            ->orderBy('days_overdue', 'desc')
            ->get();

        // Lent items overdue (we lent, they need to return)
        $lent = $db->table('library_ill_request')
            ->where('direction', 'lend')
            ->where('status', 'shipped')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $cutoff)
            ->select([
                'id', 'request_number', 'title', 'author',
                'partner_library', 'due_date',
                $db->raw('DATEDIFF(CURDATE(), due_date) as days_overdue'),
            ])
            ->orderBy('days_overdue', 'desc')
            ->get();

        if ($borrowed->isEmpty() && $lent->isEmpty()) {
            $this->logSection('ill', 'No overdue ILL items found.');
            return;
        }

        if ($borrowed->isNotEmpty()) {
            $this->logSection('ill', sprintf('=== BORROWED — %d overdue (we need to return) ===', $borrowed->count()));
            foreach ($borrowed as $row) {
                $this->logSection('overdue', sprintf(
                    '[%s] %s — from %s — %d days overdue (due %s)',
                    $row->request_number, $row->title, $row->partner_library,
                    $row->days_overdue, $row->due_date
                ));
            }
        }

        if ($lent->isNotEmpty()) {
            $this->logSection('', '');
            $this->logSection('ill', sprintf('=== LENT — %d overdue (they need to return) ===', $lent->count()));
            foreach ($lent as $row) {
                $this->logSection('overdue', sprintf(
                    '[%s] %s — to %s — %d days overdue (due %s)',
                    $row->request_number, $row->title, $row->partner_library,
                    $row->days_overdue, $row->due_date
                ));
            }
        }

        $this->logSection('', '');
        $this->logSection('ill', sprintf(
            'Total: %d borrowed overdue, %d lent overdue',
            $borrowed->count(), $lent->count()
        ));
    }
}
