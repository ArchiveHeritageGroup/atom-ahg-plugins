<?php

/**
 * Chase artworks that are past their return date.
 *
 * The register of what is out exists on screen. This is what makes it act: works
 * go out easily and come back when someone chases them, and nobody chases what
 * they are not reminded of. A placement programme without this becomes a record
 * of what left the building.
 *
 * Intended for cron, once a day:
 *
 *   0 8 * * * www-data cd /path/to/atom && \
 *     php plugins/ahgRuntimePlugin/bin/ahg artwork:remind-overdue
 *
 * Note the entry point: plugin tasks do not register through base AtoM's
 * `symfony` on a packaged install, which is why bin/ahg exists.
 */
class artworkRemindOverdueTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('every-days', null, sfCommandOption::PARAMETER_REQUIRED,
                'Do not chase the same request more often than this', 7),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE,
                'List what would be chased and send nothing'),
        ]);

        $this->namespace = 'artwork';
        $this->name = 'remind-overdue';
        $this->briefDescription = 'Email reminders for artworks past their return date';
        $this->detailedDescription = <<<'EOF'
The [artwork:remind-overdue|INFO] task emails the borrower and the gallery about
placements that are past their return date.

Each send is written to artwork_request_log as a `reminded` event, so the record
shows what was chased and when, and the same request is not chased again inside
--every-days.

  [php plugins/ahgRuntimePlugin/bin/ahg artwork:remind-overdue|INFO]
  [php plugins/ahgRuntimePlugin/bin/ahg artwork:remind-overdue --dry-run|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $configuration = ProjectConfiguration::getApplicationConfiguration(
            $options['application'], $options['env'], true
        );
        sfContext::createInstance($configuration);

        require_once __DIR__.'/../Service/ArtworkRequestService.php';

        $every = (int) ($options['every-days'] ?? 7);

        if (!empty($options['dry-run'])) {
            $due = \AhgArtworkRequest\Service\ArtworkRequestService::overdueNeedingReminder($every);

            foreach ($due as $r) {
                $days = (int) floor((time() - strtotime((string) $r->requested_to)) / 86400);
                $this->logSection('artwork', sprintf(
                    '%s - %s, due %s (%d day(s) ago), with %s',
                    $r->request_number,
                    $r->requester_name ?: 'unknown',
                    $r->requested_to,
                    $days,
                    $r->placement_occupant ?: $r->requester_name
                ));
            }

            $this->logSection('artwork', sprintf('%d overdue placement(s) would be chased.', count($due)));

            return 0;
        }

        $sent = \AhgArtworkRequest\Service\ArtworkRequestService::sendOverdueReminders($every);

        $this->logSection('artwork', sprintf('%d reminder(s) sent.', $sent));

        return 0;
    }
}
