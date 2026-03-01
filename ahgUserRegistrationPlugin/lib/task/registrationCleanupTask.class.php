<?php

/**
 * CLI command: php symfony registration:cleanup
 *
 * Marks unverified registration requests older than 48 hours as expired.
 * Recommended to run via cron daily.
 */
class registrationCleanupTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
        ]);

        $this->namespace = 'registration';
        $this->name = 'cleanup';
        $this->briefDescription = 'Clean up expired registration requests';
        $this->detailedDescription = <<<'EOF'
Marks unverified registration requests older than 48 hours as expired.

  php symfony registration:cleanup

Run this command daily via cron to keep the registration queue clean.
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        // Initialize Laravel Query Builder
        \AhgCore\Core\AhgDb::init();

        // Lazy-load the service
        require_once dirname(__FILE__) . '/../Services/RegistrationService.php';

        $service = new \AhgUserRegistration\Services\RegistrationService();
        $count = $service->cleanupExpired();

        if ($count > 0) {
            $this->logSection('registration', "Marked {$count} expired request(s)");
        } else {
            $this->logSection('registration', 'No expired requests found');
        }
    }
}
