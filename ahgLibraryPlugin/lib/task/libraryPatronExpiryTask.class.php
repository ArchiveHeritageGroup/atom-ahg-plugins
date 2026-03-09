<?php

class libraryPatronExpiryTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without updating'),
            new sfCommandOption('grace-days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Grace period after expiry before flagging', 0),
        ]);

        $this->namespace = 'library';
        $this->name = 'patron-expiry';
        $this->briefDescription = 'Flag patrons with expired memberships';
        $this->detailedDescription = <<<EOF
Checks patron membership_expiry dates and sets borrowing_status
to 'expired' for patrons past their expiry date.

  php symfony library:patron-expiry
  php symfony library:patron-expiry --grace-days=7
  php symfony library:patron-expiry --dry-run
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $dryRun = !empty($options['dry-run']);
        $graceDays = max(0, (int) ($options['grace-days'] ?? 0));
        $cutoff = date('Y-m-d', strtotime("-{$graceDays} days"));
        $flagged = 0;

        $patrons = $db->table('library_patron')
            ->where('borrowing_status', 'active')
            ->whereNotNull('membership_expiry')
            ->where('membership_expiry', '<', $cutoff)
            ->select(['id', 'card_number', 'first_name', 'last_name', 'email', 'membership_expiry'])
            ->get();

        if ($patrons->isEmpty()) {
            $this->logSection('patron', 'No expired memberships found.');
            return;
        }

        $this->logSection('patron', sprintf('Found %d expired patron(s)', $patrons->count()));

        foreach ($patrons as $patron) {
            if ($dryRun) {
                $this->logSection('dry-run', sprintf(
                    'Would expire: %s %s [%s] — expired %s',
                    $patron->first_name, $patron->last_name, $patron->card_number, $patron->membership_expiry
                ));
                $flagged++;
                continue;
            }

            $db->table('library_patron')
                ->where('id', $patron->id)
                ->update([
                    'borrowing_status' => 'expired',
                    'updated_at' => now(),
                ]);

            $flagged++;

            $this->logSection('expired', sprintf(
                '%s %s [%s] — membership expired %s',
                $patron->first_name, $patron->last_name, $patron->card_number, $patron->membership_expiry
            ));
        }

        $this->logSection('', '');
        $this->logSection('patron', sprintf('Done: %d patron(s) %s', $flagged, $dryRun ? 'would be flagged' : 'flagged as expired'));
    }
}
