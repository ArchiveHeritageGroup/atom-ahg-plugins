<?php

/**
 * library:serial-renewal-reminders (#105) — email staff a digest of
 * subscriptions due for renewal within a window. Reuses
 * SerialService::getDueForRenewal(). Recipients come from
 * ahg_settings.serial_renewal_recipients (comma-separated). Intended for cron.
 *
 * Usage:
 *   php symfony library:serial-renewal-reminders
 *   php symfony library:serial-renewal-reminders --days=60 --dry-run
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class librarySerialRenewalReminderTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Days-ahead window', 30),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Build but do not send'),
        ]);

        $this->namespace = 'library';
        $this->name = 'serial-renewal-reminders';
        $this->briefDescription = 'Email staff the subscriptions due for renewal';
        $this->detailedDescription = <<<EOF
Emails the subscriptions due for renewal within --days to the recipients in
ahg_settings.serial_renewal_recipients.

  php symfony library:serial-renewal-reminders --days=30
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgLibraryPlugin/lib/Service/SerialService.php';
        $db = \Illuminate\Database\Capsule\Manager::class;

        $days = max(1, (int) ($options['days'] ?? 30));
        $due = (new \SerialService())->getDueForRenewal($days);

        if (empty($due)) {
            $this->logSection('serials', "No subscriptions due for renewal within {$days} days.");

            return 0;
        }

        $recipients = array_values(array_filter(array_map('trim', explode(',', (string) $db::table('ahg_settings')->where('setting_key', 'serial_renewal_recipients')->value('setting_value'))), fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)));

        $lines = ["Subscriptions due for renewal within {$days} days:", ''];
        foreach ($due as $s) {
            $s = (array) $s;
            $lines[] = sprintf('- %s (sub# %s) — renewal due %s', $s['title'] ?? $s['subscription_number'] ?? ('#' . ($s['id'] ?? '?')), $s['subscription_number'] ?? '', $s['renewal_date'] ?? '?');
        }
        $body = implode("\n", $lines) . "\n";

        if (empty($recipients)) {
            $this->logSection('serials', count($due) . ' subscription(s) due — no recipients configured (ahg_settings.serial_renewal_recipients).', null, 'ERROR');
            $this->log($body);

            return 0;
        }

        if (!empty($options['dry-run'])) {
            $this->logSection('serials', '--dry-run: would email ' . count($due) . ' due subscription(s) to ' . implode(', ', $recipients));
            $this->log($body);

            return 0;
        }

        try {
            $from = (string) \sfConfig::get('app_contact_email', 'noreply@psis.theahg.co.za');
            $message = \Swift_Message::newInstance()
                ->setFrom([$from => 'AHG Library'])
                ->setTo($recipients)
                ->setSubject('[Library] ' . count($due) . ' serial subscription(s) due for renewal')
                ->setBody($body, 'text/plain');
            \sfContext::getInstance()->getMailer()->send($message);
            $this->logSection('serials', 'Sent renewal reminder to ' . implode(', ', $recipients));
        } catch (\Throwable $e) {
            $this->logSection('serials', 'Send failed: ' . $e->getMessage(), null, 'ERROR');

            return 1;
        }

        return 0;
    }
}
