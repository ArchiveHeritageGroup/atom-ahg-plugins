<?php

/**
 * spectrumOverdueTask - PSIS Symfony port of Heratio Spectrum Phase C4
 *   `php symfony spectrum:overdue --days=N --notify=user`
 *
 * Drops Workbench notifications grouped by procedure into the spool dir.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

class spectrumOverdueTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Overdue threshold in days', 14),
            new sfCommandOption('notify', null, sfCommandOption::PARAMETER_OPTIONAL, 'Workbench username to notify', ''),
            new sfCommandOption('inbox', null, sfCommandOption::PARAMETER_OPTIONAL, 'Workbench notification inbox path', '/var/spool/workbench/notifications'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Log overdue items but do not write notifications'),
        ]);

        $this->namespace = 'spectrum';
        $this->name = 'overdue';
        $this->briefDescription = 'Scan for overdue Spectrum tasks and drop Workbench notifications.';
        $this->detailedDescription = $this->briefDescription;
    }

    public function execute($arguments = [], $options = [])
    {
        $configuration = ProjectConfiguration::getApplicationConfiguration($options['application'], $options['env'], true);
        sfContext::createInstance($configuration);

        require_once dirname(__DIR__).'/Services/SpectrumComplianceService.php';
        require_once dirname(__DIR__).'/Services/SpectrumProcedureCatalog.php';

        $days = max(1, (int) $options['days']);
        $username = (string) $options['notify'];
        $inbox = (string) $options['inbox'];
        $dryRun = !empty($options['dry-run']);

        $svc = new SpectrumComplianceService();
        $overdue = $svc->findOverdue($days);

        $this->log(sprintf('Found %d overdue Spectrum task(s) past %d-day threshold.', count($overdue), $days));

        if ($dryRun) {
            foreach ($overdue as $row) {
                $this->log(sprintf('  task=%d object=%d procedure=%s created=%s', $row->task_id, $row->object_id, $row->spectrum_procedure, $row->created_at));
            }
            $this->log('DRY RUN — no notifications written.');
            return 0;
        }

        if (count($overdue) === 0) {
            return 0;
        }
        if ($username === '') {
            $this->log('No --notify=<username> set; skipping notification drop.');
            return 0;
        }
        if (!is_dir($inbox)) {
            $this->logSection('spectrum:overdue', "Workbench inbox directory does not exist: $inbox");
            return 1;
        }

        $byProcedure = [];
        foreach ($overdue as $row) {
            $byProcedure[$row->spectrum_procedure][] = $row;
        }

        $written = 0;
        foreach ($byProcedure as $procedure => $rows) {
            $label = SpectrumProcedureCatalog::label($procedure);
            $count = count($rows);
            $oldest = '';
            foreach ($rows as $r) {
                if ($oldest === '' || $r->created_at < $oldest) {
                    $oldest = $r->created_at;
                }
            }
            $payload = [
                'username'  => $username,
                'title'     => sprintf('Spectrum overdue: %s (%d task%s)', $label, $count, $count === 1 ? '' : 's'),
                'message'   => sprintf('There %s %d %s task%s past the %d-day overdue threshold. Oldest started %s.',
                    $count === 1 ? 'is' : 'are', $count, $label, $count === 1 ? '' : 's', $days, $oldest),
                'eventType' => 'spectrum_overdue',
                'webLink'   => sprintf('/index.php/workflow/spectrumDashboard?overdue_days=%d', $days),
            ];
            $tmp = $inbox.'/psis-spectrum-'.$procedure.'-'.date('YmdHis').'-'.bin2hex(random_bytes(4)).'.json';
            if (file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT)) !== false) {
                $written++;
                $this->log("  + $tmp ($count overdue $label)");
            } else {
                $this->logSection('spectrum:overdue', "  ! failed to write: $tmp");
            }
        }
        $this->log("Wrote $written notification(s) to $inbox");
        return 0;
    }
}
