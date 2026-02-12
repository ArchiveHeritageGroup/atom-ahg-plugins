<?php

namespace AtomFramework\Console\Commands\Cdpa;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * List data subject requests under CDPA.
 */
class RequestsCommand extends BaseCommand
{
    protected string $name = 'cdpa:requests';
    protected string $description = 'Manage CDPA requests';
    protected string $detailedDescription = <<<'EOF'
    List data subject requests under CDPA.

    Examples:
      php bin/atom cdpa:requests                    List all requests
      php bin/atom cdpa:requests --pending          List pending requests
      php bin/atom cdpa:requests --overdue          List overdue requests
      php bin/atom cdpa:requests --type=access      List access requests
    EOF;

    protected function configure(): void
    {
        $this->addOption('pending', null, 'Show only pending requests');
        $this->addOption('overdue', null, 'Show only overdue requests');
        $this->addOption('type', 't', 'Filter by request type (access, rectification, erasure, object)');
    }

    protected function handle(): int
    {
        $query = DB::table('cdpa_data_subject_request')
            ->orderBy('due_date', 'asc');

        if ($this->hasOption('pending')) {
            $query->where('status', 'pending');
        }

        if ($this->hasOption('overdue')) {
            $query->where('status', 'pending')
                ->where('due_date', '<', date('Y-m-d'));
        }

        $typeFilter = $this->option('type');
        if ($typeFilter) {
            $query->where('request_type', $typeFilter);
        }

        $requests = $query->limit(100)->get();

        if ($requests->isEmpty()) {
            $this->info('No requests found matching criteria');

            return 0;
        }

        $this->info(sprintf('Found %d request(s)', $requests->count()));
        $this->newline();

        $this->line(
            str_pad('Ref', 16)
            . str_pad('Type', 15)
            . str_pad('Subject', 25)
            . str_pad('Due', 12)
            . 'Status'
        );
        $this->line(str_repeat('-', 80));

        foreach ($requests as $req) {
            $isOverdue = 'pending' === $req->status && strtotime($req->due_date) < time();
            $statusDisplay = $isOverdue ? 'OVERDUE' : $req->status;

            $this->line(sprintf(
                '%s%s%s%s%s',
                str_pad($req->reference_number, 16),
                str_pad($req->request_type, 15),
                str_pad(mb_substr($req->data_subject_name, 0, 23), 25),
                str_pad($req->due_date, 12),
                $statusDisplay
            ));
        }

        return 0;
    }
}
