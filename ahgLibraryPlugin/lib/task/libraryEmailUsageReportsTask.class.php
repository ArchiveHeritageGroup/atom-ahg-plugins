<?php

/**
 * library:email-usage-reports
 *
 * Scheduled COUNTER R5 report email delivery (Heratio #766 parity).
 *
 * Emails the prior period's COUNTER R5 reports to the configured recipients,
 * attaching each as JSON + TSV. Reuses the existing LibraryCounterService
 * (PR / TR_J1 / TR_J3 / DR / IR + toJson/toTsv) — no new tables or report
 * engine. Settings live in the ahg_settings table:
 *
 *   counter_email_enabled      bool    master kill-switch (default false)
 *   counter_email_recipients   csv     comma-separated recipient addresses
 *   counter_email_reports      csv     report IDs (PR,TR_J1,TR_J3,DR,IR); default TR_J1,DR,PR
 *   counter_email_period       string  monthly (default) | quarterly | yearly
 *   counter_email_from         string  RFC 5322 From address (falls back to app_contact_email)
 *   counter_email_from_name    string  From display name
 *   counter_institution_name   string  COUNTER institution name
 *
 * Schedule monthly, e.g. (1st of month 04:00 SAST):
 *   0 4 1 * * www-data cd /usr/share/nginx/archive && php symfony library:email-usage-reports
 */
class libraryEmailUsageReportsTask extends sfBaseTask
{
    private const VALID_REPORTS = ['PR', 'TR_J1', 'TR_J3', 'DR', 'IR'];

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Build the reports but do not send the email'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Bypass the counter_email_enabled kill-switch (recipients still required)'),
        ]);

        $this->namespace = 'library';
        $this->name = 'email-usage-reports';
        $this->briefDescription = 'Email the prior period\'s COUNTER R5 usage reports to configured recipients';
        $this->detailedDescription = <<<EOF
Builds the prior period's COUNTER R5 reports (via LibraryCounterService) and
emails them as JSON + TSV attachments to the recipients in ahg_settings.

  php symfony library:email-usage-reports
  php symfony library:email-usage-reports --dry-run
  php symfony library:email-usage-reports --force
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        require_once sfConfig::get('sf_plugins_dir') . '/ahgLibraryPlugin/lib/Service/LibraryCounterService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgSettingsPlugin/lib/Services/AhgSettingsService.php';

        $S = '\AhgSettings\Services\AhgSettingsService';

        $enabled    = $S::getBool('counter_email_enabled', false);
        $recipients = array_values(array_filter(array_map('trim', explode(',', (string) $S::get('counter_email_recipients', ''))), function ($e) {
            return $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL);
        }));

        if (!$enabled && empty($options['force'])) {
            $this->logSection('library', 'counter_email_enabled is off — skipping. Use --force for a one-off send.');

            return 0;
        }
        if (empty($recipients)) {
            $this->logSection('library', 'No valid recipients in ahg_settings.counter_email_recipients — nothing to send.', null, 'ERROR');

            return 0;
        }

        [$begin, $end, $label] = $this->resolvePeriod((string) $S::get('counter_email_period', 'monthly'));

        $reportIds = array_values(array_filter(
            array_map('trim', explode(',', strtoupper((string) $S::get('counter_email_reports', 'TR_J1,DR,PR')))),
            fn ($r) => in_array($r, self::VALID_REPORTS, true)
        ));
        if (empty($reportIds)) {
            $reportIds = ['TR_J1', 'DR', 'PR'];
        }

        $institution = (string) $S::get('counter_institution_name', \sfConfig::get('app_library_name', 'AHG Library'));
        $this->logSection('library', sprintf('Building %s reports for %s → %s: %s', $label, $begin, $end, implode(', ', $reportIds)));

        $svc = new \AtomExtensions\Services\LibraryCounterService($begin, $end, $institution);

        $attachments = [];
        foreach ($reportIds as $reportId) {
            try {
                $records = $this->buildReport($svc, $reportId);
                $attachments[] = [
                    'name' => sprintf('COUNTER_%s_%s_%s.json', $reportId, $begin, $end),
                    'mime' => 'application/json',
                    'data' => $svc->toJson($reportId, $records),
                ];
                $attachments[] = [
                    'name' => sprintf('COUNTER_%s_%s_%s.tsv', $reportId, $begin, $end),
                    'mime' => 'text/tab-separated-values',
                    'data' => $svc->toTsv($reportId, $records),
                ];
                $this->logSection('library', sprintf('  built %s (%d rows)', $reportId, is_countable($records) ? count($records) : 0));
            } catch (\Throwable $e) {
                $this->logSection('library', sprintf('  %s build failed: %s', $reportId, $e->getMessage()), null, 'ERROR');
            }
        }

        if (empty($attachments)) {
            $this->logSection('library', 'No reports built — nothing to send.', null, 'ERROR');

            return 1;
        }

        $subject = sprintf('[AHG Library COUNTER R5] %s usage reports (%s)', ucfirst($label), $begin);
        $body = sprintf(
            "Attached are the COUNTER R5 reports for %s to %s.\n\nReport IDs: %s\nGenerated by %s on %s.\n",
            $begin,
            $end,
            implode(', ', $reportIds),
            $institution,
            date('c')
        );

        if (!empty($options['dry-run'])) {
            $this->logSection('library', sprintf('--dry-run: %d attachment(s) would be sent to: %s', count($attachments), implode(', ', $recipients)));

            return 0;
        }

        try {
            $fromAddress = (string) ($S::get('counter_email_from', '') ?: \sfConfig::get('app_contact_email', 'noreply@psis.theahg.co.za'));
            $fromName    = (string) $S::get('counter_email_from_name', $institution);

            $message = \Swift_Message::newInstance()
                ->setFrom([$fromAddress => $fromName])
                ->setTo($recipients)
                ->setSubject($subject)
                ->setBody($body, 'text/plain');

            foreach ($attachments as $a) {
                $message->attach(\Swift_Attachment::newInstance($a['data'], $a['name'], $a['mime']));
            }

            \sfContext::getInstance()->getMailer()->send($message);
            $this->logSection('library', 'Sent COUNTER reports to: ' . implode(', ', $recipients));

            return 0;
        } catch (\Throwable $e) {
            $this->logSection('library', 'Send failed: ' . $e->getMessage(), null, 'ERROR');

            return 1;
        }
    }

    /**
     * Dispatch a report id to the matching LibraryCounterService method.
     */
    private function buildReport(\AtomExtensions\Services\LibraryCounterService $svc, string $reportId): array
    {
        switch ($reportId) {
            case 'TR_J1': return $svc->TR_J1();
            case 'TR_J3': return $svc->TR_J3();
            case 'DR':    return $svc->DR();
            case 'IR':    return $svc->IR();
            case 'PR':
            default:      return $svc->PR();
        }
    }

    /**
     * Resolve the prior reporting period as [beginDate, endDate, label].
     */
    private function resolvePeriod(string $period): array
    {
        switch ($period) {
            case 'yearly':
                $year  = (int) date('Y') - 1;
                return [sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year), 'yearly'];

            case 'quarterly':
                $q          = (int) ceil((int) date('n') / 3);   // current quarter 1-4
                $prevQ      = $q - 1;
                $year       = (int) date('Y');
                if ($prevQ < 1) {
                    $prevQ = 4;
                    $year--;
                }
                $startMonth = (($prevQ - 1) * 3) + 1;
                $begin      = sprintf('%04d-%02d-01', $year, $startMonth);
                $end        = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $startMonth + 2)));
                return [$begin, $end, 'quarterly'];

            case 'monthly':
            default:
                $begin = date('Y-m-01', strtotime('first day of last month'));
                $end   = date('Y-m-t', strtotime('last day of last month'));
                return [$begin, $end, 'monthly'];
        }
    }
}
