<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to list CDPA data subject requests.
 */
class cdpaRequestsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('pending', null, sfCommandOption::PARAMETER_NONE, 'Show only pending requests'),
            new sfCommandOption('overdue', null, sfCommandOption::PARAMETER_NONE, 'Show only overdue requests'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by request type (access, rectification, erasure, object)'),
        ]);

        $this->namespace = 'cdpa';
        $this->name = 'requests';
        $this->briefDescription = 'List data subject requests';
        $this->detailedDescription = <<<EOF
List data subject requests under CDPA.

Examples:
  php symfony cdpa:requests                    # List all requests
  php symfony cdpa:requests --pending          # List pending requests
  php symfony cdpa:requests --overdue          # List overdue requests
  php symfony cdpa:requests --type=access      # List access requests
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $query = DB::table('cdpa_data_subject_request')
            ->orderBy('due_date', 'asc');

        if ($options['pending']) {
            $query->where('status', 'pending');
        }

        if ($options['overdue']) {
            $query->where('status', 'pending')
                ->where('due_date', '<', date('Y-m-d'));
        }

        if ($options['type']) {
            $query->where('request_type', $options['type']);
        }

        $requests = $query->limit(100)->get();

        if ($requests->isEmpty()) {
            $this->logSection('cdpa', 'No requests found matching criteria');

            return 0;
        }

        $this->logSection('cdpa', sprintf('Found %d request(s)', $requests->count()));
        $this->log('');

        $this->log(str_pad('Ref', 16) . str_pad('Type', 15) . str_pad('Subject', 25) . str_pad('Due', 12) . 'Status');
        $this->log(str_repeat('-', 80));

        foreach ($requests as $req) {
            $isOverdue = 'pending' === $req->status && strtotime($req->due_date) < time();
            $statusDisplay = $isOverdue ? 'OVERDUE' : $req->status;

            $this->log(sprintf(
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
