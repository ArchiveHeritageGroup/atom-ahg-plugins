<?php

/**
 * research:orcid-sync — scheduled ORCID profile/works sync (#102).
 *
 * For every researcher linked to an ORCID iD (research_orcid_link), pulls the
 * public profile (tokenless) and, when an OAuth token is present, pulls Works.
 * Intended to run daily from cron.
 *
 * Usage:
 *   php symfony research:orcid-sync
 *   php symfony research:orcid-sync --id=42        # one researcher
 *   php symfony research:orcid-sync --works        # also pull Works (needs token)
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class researchOrcidSyncTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Sync a single researcher id'),
            new sfCommandOption('works', null, sfCommandOption::PARAMETER_NONE, 'Also pull Works (requires a stored OAuth token)'),
        ]);

        $this->namespace = 'research';
        $this->name = 'orcid-sync';
        $this->briefDescription = 'Sync linked researchers\' ORCID profiles (and optionally Works)';
        $this->detailedDescription = <<<EOF
Pulls the public ORCID profile for every linked researcher (tokenless) and,
with --works, pulls Works for those holding an OAuth token.

  php symfony research:orcid-sync
  php symfony research:orcid-sync --id=42 --works
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/OrcidService.php';
        $svc = new \OrcidService();
        $db = \Illuminate\Database\Capsule\Manager::class;

        $query = $db::table('research_orcid_link')->whereNotNull('orcid_id')->where('orcid_id', '!=', '');
        if (!empty($options['id'])) {
            $query->where('researcher_id', (int) $options['id']);
        }
        $links = $query->get();

        $this->logSection('orcid', sprintf('Syncing %d linked researcher(s)…', count($links)));
        $profiles = 0;
        $works = 0;
        $errors = 0;

        foreach ($links as $link) {
            $rid = (int) $link->researcher_id;
            try {
                if ($svc->pullProfile($rid)) {
                    $profiles++;
                }
                if (!empty($options['works']) && !empty($link->access_token_encrypted)) {
                    $svc->pullWorks($rid);
                    $works++;
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->logSection('orcid', sprintf('  researcher %d: %s', $rid, $e->getMessage()), null, 'ERROR');
            }
        }

        $this->logSection('orcid', sprintf('Done: %d profile(s) updated, %d works-sync(s), %d error(s).', $profiles, $works, $errors));

        return 0;
    }
}
