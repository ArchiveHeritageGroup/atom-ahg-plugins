<?php

/**
 * SAHRA Permit Expiry Task.
 *
 * Lists permits expiring soon and (with --process) auto-expires permits past
 * their end date, plus reports overdue SAHRA reporting obligations.
 *
 * Usage: php symfony sahra:permit-expiry [--days=30] [--process]
 */
class sahraPermitExpiryTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_REQUIRED, 'Days until expiry to warn about', 30),
            new sfCommandOption('process', null, sfCommandOption::PARAMETER_NONE, 'Auto-expire past permits'),
        ]);

        $this->namespace = 'sahra';
        $this->name = 'permit-expiry';
        $this->briefDescription = 'Check SAHRA heritage permits for expiry';
        $this->detailedDescription = <<<'EOF'
The [sahra:permit-expiry|INFO] task manages SAHRA/NHRA permit expirations:
  - Lists permits expiring within the given number of days
  - With --process, auto-expires permits past their end date
  - Reports overdue permit reporting obligations

Examples:
  [php symfony sahra:permit-expiry|INFO]                Check (warn only)
  [php symfony sahra:permit-expiry --days=14|INFO]      Warn about 14-day expirations
  [php symfony sahra:permit-expiry --process|INFO]      Auto-expire past permits
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        require_once dirname(__DIR__) . '/Services/SahraPermitService.php';
        $service = new \AhgSAHRA\Services\SahraPermitService();

        $days = (int) $options['days'];

        $this->logSection('sahra', 'SAHRA / NHRA - Heritage Permit Expiry Check');

        $expiring = $service->getExpiring($days);
        $this->log(sprintf('  %d permit(s) expiring within %d days:', count($expiring), $days));
        foreach ($expiring as $p) {
            $this->log(sprintf('    - %s  %s  (expires %s)', $p->application_ref, $p->project_title, $p->end_date));
        }

        if (!empty($options['process'])) {
            $n = $service->expireOverdue(0);
            $this->logSection('sahra', sprintf('Auto-expired %d permit(s) past their end date.', $n));
        }

        $overdue = $service->getOverdueReports();
        if (!empty($overdue)) {
            $this->log(sprintf('  %d overdue reporting obligation(s):', count($overdue)));
            foreach ($overdue as $r) {
                $this->log(sprintf('    - %s  %s report  (due %s)', $r->application_ref, $r->report_type, $r->due_date));
            }
        }

        $this->logSection('sahra', 'Done.');
    }
}
