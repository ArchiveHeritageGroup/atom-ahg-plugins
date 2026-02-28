<?php

class accessionIntakeTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('queue', null, sfCommandOption::PARAMETER_NONE, 'List intake queue'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show queue statistics'),
            new sfCommandOption('assign', null, sfCommandOption::PARAMETER_REQUIRED, 'Assign accession to user (accession ID)'),
            new sfCommandOption('user', null, sfCommandOption::PARAMETER_REQUIRED, 'Target user ID (for --assign)'),
            new sfCommandOption('accept', null, sfCommandOption::PARAMETER_REQUIRED, 'Accept accession (accession ID)'),
            new sfCommandOption('reject', null, sfCommandOption::PARAMETER_REQUIRED, 'Reject accession (accession ID)'),
            new sfCommandOption('reason', null, sfCommandOption::PARAMETER_REQUIRED, 'Rejection reason (for --reject)'),
            new sfCommandOption('checklist', null, sfCommandOption::PARAMETER_REQUIRED, 'Show checklist (accession ID)'),
            new sfCommandOption('timeline', null, sfCommandOption::PARAMETER_REQUIRED, 'Show timeline (accession ID)'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_REQUIRED, 'Filter by status'),
            new sfCommandOption('priority', null, sfCommandOption::PARAMETER_REQUIRED, 'Filter by priority'),
        ]);

        $this->namespace = 'accession';
        $this->name = 'intake';
        $this->briefDescription = 'Manage accession intake queue';
        $this->detailedDescription = <<<'EOF'
The [accession:intake|INFO] task manages the accession intake queue.

  [php symfony accession:intake --queue|INFO]                          List intake queue
  [php symfony accession:intake --stats|INFO]                          Show queue statistics
  [php symfony accession:intake --assign=123 --user=5|INFO]            Assign accession 123 to user 5
  [php symfony accession:intake --accept=123|INFO]                     Accept accession 123
  [php symfony accession:intake --reject=123 --reason="Incomplete"|INFO] Reject accession 123
  [php symfony accession:intake --checklist=123|INFO]                  Show checklist for accession 123
  [php symfony accession:intake --timeline=123|INFO]                   Show timeline for accession 123
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        // Bootstrap Laravel Query Builder
        $frameworkBootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkBootstrap)) {
            require_once $frameworkBootstrap;
        }

        // Load service
        $pluginDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAccessionManagePlugin';
        require_once $pluginDir . '/lib/Services/AccessionIntakeService.php';
        require_once $pluginDir . '/lib/Services/AccessionCrudService.php';

        $service = new \AhgAccessionManage\Services\AccessionIntakeService();

        // --stats
        if ($options['stats']) {
            $this->showStats($service);

            return;
        }

        // --queue
        if ($options['queue']) {
            $filters = [];
            if (!empty($options['status'])) {
                $filters['status'] = $options['status'];
            }
            if (!empty($options['priority'])) {
                $filters['priority'] = $options['priority'];
            }
            $filters['limit'] = 50;
            $this->showQueue($service, $filters);

            return;
        }

        // --assign
        if (!empty($options['assign'])) {
            if (empty($options['user'])) {
                $this->logSection('accession', 'ERROR: --user is required with --assign', null, 'ERROR');

                return;
            }
            $accessionId = (int) $options['assign'];
            $userId = (int) $options['user'];
            if ($service->assign($accessionId, $userId, $userId)) {
                $this->logSection('accession', sprintf('Accession %d assigned to user %d', $accessionId, $userId));
            } else {
                $this->logSection('accession', 'Failed to assign accession', null, 'ERROR');
            }

            return;
        }

        // --accept
        if (!empty($options['accept'])) {
            $accessionId = (int) $options['accept'];
            // Use admin user (id=1) as actor for CLI operations
            if ($service->accept($accessionId, 1)) {
                $this->logSection('accession', sprintf('Accession %d accepted', $accessionId));
            } else {
                $this->logSection('accession', 'Failed to accept. Check status.', null, 'ERROR');
            }

            return;
        }

        // --reject
        if (!empty($options['reject'])) {
            $accessionId = (int) $options['reject'];
            $reason = $options['reason'] ?? 'Rejected via CLI';
            if ($service->reject($accessionId, $reason, 1)) {
                $this->logSection('accession', sprintf('Accession %d rejected', $accessionId));
            } else {
                $this->logSection('accession', 'Failed to reject. Check status.', null, 'ERROR');
            }

            return;
        }

        // --checklist
        if (!empty($options['checklist'])) {
            $accessionId = (int) $options['checklist'];
            $this->showChecklist($service, $accessionId);

            return;
        }

        // --timeline
        if (!empty($options['timeline'])) {
            $accessionId = (int) $options['timeline'];
            $this->showTimeline($service, $accessionId);

            return;
        }

        // Default: show help
        $this->logSection('accession', 'Use --queue, --stats, --assign, --accept, --reject, --checklist, or --timeline');
    }

    protected function showStats($service)
    {
        $stats = $service->getQueueStats();

        $this->logSection('accession', 'Intake Queue Statistics');
        $this->logSection('accession', str_repeat('-', 40));
        $this->logSection('accession', sprintf('Total: %d', $stats['total']));

        if (!empty($stats['byStatus'])) {
            $this->logSection('accession', 'By Status:');
            foreach ($stats['byStatus'] as $status => $count) {
                $this->logSection('accession', sprintf('  %-15s %d', $status, $count));
            }
        }

        if (!empty($stats['byPriority'])) {
            $this->logSection('accession', 'By Priority:');
            foreach ($stats['byPriority'] as $priority => $count) {
                $this->logSection('accession', sprintf('  %-15s %d', $priority, $count));
            }
        }

        if ($stats['avgTimeToAcceptHours'] !== null) {
            $this->logSection('accession', sprintf('Avg time to accept: %.1f hours', $stats['avgTimeToAcceptHours']));
        }

        $this->logSection('accession', sprintf('Overdue (>7 days): %d', $stats['overdue']));
    }

    protected function showQueue($service, array $filters)
    {
        $data = $service->getQueue($filters);

        $this->logSection('accession', sprintf('Intake Queue (%d results)', $data['total']));
        $this->logSection('accession', str_repeat('-', 80));
        $this->logSection('accession', sprintf(
            '%-12s %-30s %-15s %-10s %-20s',
            'Identifier', 'Title', 'Status', 'Priority', 'Assigned To'
        ));
        $this->logSection('accession', str_repeat('-', 80));

        foreach ($data['rows'] as $row) {
            $title = mb_substr($row->title ?? '', 0, 28);
            $this->logSection('accession', sprintf(
                '%-12s %-30s %-15s %-10s %-20s',
                $row->identifier ?? '',
                $title,
                $row->status,
                $row->priority,
                $row->assignee_name ?? ''
            ));
        }
    }

    protected function showChecklist($service, int $accessionId)
    {
        $checklist = $service->getChecklist($accessionId);
        $progress = $service->getChecklistProgress($accessionId);

        $this->logSection('accession', sprintf('Checklist for Accession #%d (%d%% complete)', $accessionId, $progress['percent']));
        $this->logSection('accession', str_repeat('-', 50));

        foreach ($checklist as $item) {
            $check = $item->is_completed ? '[X]' : '[ ]';
            $this->logSection('accession', sprintf('  %s %s', $check, $item->label));
        }
    }

    protected function showTimeline($service, int $accessionId)
    {
        $timeline = $service->getTimeline($accessionId);

        $this->logSection('accession', sprintf('Timeline for Accession #%d', $accessionId));
        $this->logSection('accession', str_repeat('-', 70));

        foreach ($timeline as $event) {
            $this->logSection('accession', sprintf(
                '  [%s] %-15s %s%s',
                $event->created_at,
                $event->event_type,
                $event->description ?? '',
                $event->actor_name ? ' (by ' . $event->actor_name . ')' : ''
            ));
        }
    }
}
