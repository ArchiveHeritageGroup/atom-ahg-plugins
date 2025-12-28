<?php

/**
 * Security Clearance Scheduled Tasks.
 *
 * Processes:
 * - Automatic declassification
 * - Expired clearance notifications
 * - 2FA session cleanup
 * - Audit log retention
 *
 * Run via cron: php symfony security:process
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class securityProcessTask extends arBaseTask
{
    protected function configure(): void
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection name', 'propel'),
        ]);

        $this->namespace = 'security';
        $this->name = 'process';
        $this->briefDescription = 'Process security clearance scheduled tasks';
        $this->detailedDescription = <<<'EOF'
The [security:process|INFO] task processes scheduled security tasks:

  - Automatic declassification of objects
  - Clearance expiry notifications
  - 2FA session cleanup
  - Audit log retention enforcement

  [php symfony security:process|INFO]

Add to crontab for daily execution:
  0 1 * * * /usr/bin/php /var/www/atom/symfony security:process
EOF;
    }

    protected function execute($arguments = [], $options = []): int
    {
        parent::execute($arguments, $options);

        $this->logSection('security', 'Starting security scheduled tasks');

        // Load services
        require_once sfConfig::get('sf_plugins_dir').'/arSecurityClearancePlugin/lib/Services/SecurityClearanceService.php';

        // 1. Process declassifications
        $this->processDeclassifications();

        // 2. Process expired clearances
        $this->processExpiredClearances();

        // 3. Send expiry warnings
        $this->sendExpiryWarnings();

        // 4. Cleanup expired 2FA sessions
        $this->cleanup2FASessions();

        // 5. Enforce audit log retention
        $this->enforceAuditRetention();

        $this->logSection('security', 'Security tasks completed');

        return 0;
    }

    /**
     * Process due declassifications.
     */
    private function processDeclassifications(): void
    {
        $this->logSection('security', 'Processing declassifications...');

        // Get system user ID (admin)
        $systemUserId = $this->getSystemUserId();

        $processed = SecurityClearanceService::processDueDeclassifications($systemUserId);

        $this->logSection('security', sprintf('Processed %d declassifications', $processed));
    }

    /**
     * Mark expired clearances as inactive.
     */
    private function processExpiredClearances(): void
    {
        $this->logSection('security', 'Processing expired clearances...');

        $db = Illuminate\Database\Capsule\Manager::connection();

        $expired = $db->table('user_security_clearance')
            ->where('active', 1)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', date('Y-m-d'))
            ->get();

        $count = 0;
        foreach ($expired as $clearance) {
            $db->table('user_security_clearance')
                ->where('id', $clearance->id)
                ->update(['active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

            $db->table('security_clearance_history')->insert([
                'user_id' => $clearance->user_id,
                'previous_classification_id' => $clearance->classification_id,
                'new_classification_id' => null,
                'action' => 'expired',
                'changed_by' => $this->getSystemUserId(),
                'reason' => 'Automatic expiry',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            ++$count;
        }

        $this->logSection('security', sprintf('Expired %d clearances', $count));
    }

    /**
     * Send expiry warning emails.
     */
    private function sendExpiryWarnings(): void
    {
        $this->logSection('security', 'Sending expiry warnings...');

        $expiringClearances = SecurityClearanceService::getExpiringClearances(30);
        $warningsSent = 0;

        foreach ($expiringClearances as $clearance) {
            // Check if warning already sent recently
            $recentWarning = Illuminate\Database\Capsule\Manager::table('security_access_log')
                ->where('user_id', $clearance->user_id)
                ->where('action', 'expiry_warning')
                ->where('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                ->exists();

            if (!$recentWarning && $clearance->email) {
                // Send email (simplified - integrate with your email system)
                $this->sendExpiryWarningEmail($clearance);

                // Log the warning
                Illuminate\Database\Capsule\Manager::table('security_access_log')->insert([
                    'user_id' => $clearance->user_id,
                    'action' => 'expiry_warning',
                    'access_granted' => 1,
                    'justification' => sprintf('Clearance expires in %d days', $clearance->days_remaining),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                ++$warningsSent;
            }
        }

        $this->logSection('security', sprintf('Sent %d expiry warnings', $warningsSent));
    }

    /**
     * Send expiry warning email.
     */
    private function sendExpiryWarningEmail($clearance): void
    {
        $subject = 'Security Clearance Expiry Warning';
        $body = sprintf(
            "Dear %s,\n\n".
            "Your %s security clearance will expire on %s (%d days remaining).\n\n".
            "Please contact your security administrator to request a renewal.\n\n".
            "Regards,\nSecurity Administration",
            $clearance->username,
            $clearance->clearance_name,
            $clearance->expiry_date,
            $clearance->days_remaining
        );

        // Use AtoM's email system if available
        // QubitJob replaced with framework JobService - TODO: implement
            try {
                // $job = new QubitJob(); // Use JobService instead
                $job->setName('Email: Clearance Expiry Warning');
                $job->setDownloadPath(null);
                $job->setCompletedAt(null);
                // Queue email job...
            } catch (Exception $e) {
                $this->logSection('security', 'Email failed: '.$e->getMessage(), null, 'ERROR');
            }
        }
    }

    /**
     * Cleanup expired 2FA sessions.
     */
    private function cleanup2FASessions(): void
    {
        $this->logSection('security', 'Cleaning up 2FA sessions...');

        $deleted = SecurityClearanceService::cleanupExpired2FASessions();

        $this->logSection('security', sprintf('Removed %d expired 2FA sessions', $deleted));
    }

    /**
     * Enforce audit log retention policy.
     */
    private function enforceAuditRetention(): void
    {
        $this->logSection('security', 'Enforcing audit retention...');

        $retentionDays = sfConfig::get('app_security_audit_retention_days', 365);
        $cutoffDate = date('Y-m-d', strtotime("-$retentionDays days"));

        $deleted = Illuminate\Database\Capsule\Manager::table('security_access_log')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $this->logSection('security', sprintf('Removed %d old audit entries', $deleted));
    }

    /**
     * Get system user ID for automated actions.
     */
    private function getSystemUserId(): int
    {
        // Get first admin user
        $admin = Illuminate\Database\Capsule\Manager::table('user')
            ->join('aclUserGroup', 'user.id', '=', 'aclUserGroup.user_id')
            ->join('aclGroup', 'aclUserGroup.group_id', '=', 'aclGroup.id')
            ->where('aclGroup.name', 'administrator')
            ->select('user.id')
            ->first();

        return $admin ? $admin->id : 1;
    }
}
