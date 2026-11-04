<?php

/**
 * Write a stand-alone relationship explorer for a slice of the catalogue
 * (#196, parity with Heratio's ahg:explorer-export, heratio#1524): an offline
 * folder (index.html, data.js, cytoscape.min.js) that opens from a USB drive
 * or file share with no server.
 *
 * Usage:
 *   php symfony ric:explorer-export --ancestor=<id|slug> [--year=YYYY] [--min=2]
 *       [--hide="World,African"] [--title="..."] [--base-url=https://...] [--out=/path]
 *
 * Built from published records' subjects, places, genres, name access points
 * and creators, plus the same-story links stored by ric:same-story.
 *
 * ponytail: no client branding yet (needs the client's site tokens), as in
 * Heratio; the page uses the AHG palette.
 *
 * @package    ahgRicExplorerPlugin
 */
class ricExplorerExportTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('ancestor', null, sfCommandOption::PARAMETER_REQUIRED, 'Collection or fonds (id or slug); the explorer covers it and everything under it'),
            new sfCommandOption('year', null, sfCommandOption::PARAMETER_REQUIRED, 'Only records dated in this year'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Most records to include', 8000),
            new sfCommandOption('min', null, sfCommandOption::PARAMETER_REQUIRED, 'Leave out names that occur on fewer records than this', 2),
            new sfCommandOption('base-url', null, sfCommandOption::PARAMETER_REQUIRED, 'Site address the record links point at (default siteBaseUrl)'),
            new sfCommandOption('hide', null, sfCommandOption::PARAMETER_REQUIRED, 'Comma-separated general words kept off the starting list and the map (still searchable)', ''),
            new sfCommandOption('title', null, sfCommandOption::PARAMETER_REQUIRED, 'Heading shown on the page', 'Relationship explorer'),
            new sfCommandOption('out', null, sfCommandOption::PARAMETER_REQUIRED, 'Folder to write (default <system temp>/ahg-explorer/<title>)'),
        ]);

        $this->namespace = 'ric';
        $this->name = 'explorer-export';
        $this->briefDescription = 'Export an offline relationship explorer (subjects, places, names, same-story links) for a collection or a year';
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once dirname(__DIR__).'/Services/SameStoryService.php';
        require_once dirname(__DIR__).'/Services/ExplorerDataService.php';
        $data = '\\AhgRicExplorer\\Services\\ExplorerDataService';

        $root = null;
        if ($options['ancestor'] && !($root = $data::resolve((string) $options['ancestor']))) {
            $this->logSection('ric', "No collection {$options['ancestor']}.", null, 'ERROR');

            return 1;
        }
        $year = (string) $options['year'];
        if ('' !== $year && !preg_match('/^\d{4}$/', $year)) {
            $this->logSection('ric', '--year takes four digits.', null, 'ERROR');

            return 1;
        }
        if (!$root && '' === $year) {
            $this->logSection('ric', 'Give --ancestor or --year (or both): the whole catalogue is too big to explore in a browser.', null, 'ERROR');

            return 1;
        }

        $ids = $data::scope($root, $year ?: null, (int) $options['limit']);
        if (!$ids) {
            $this->logSection('ric', 'No published records in that scope.', null, 'ERROR');

            return 1;
        }
        $this->logSection('ric', count($ids).' records in scope.');

        $base = $options['base-url'] ?: (string) \Illuminate\Database\Capsule\Manager::table('setting as s')
            ->join('setting_i18n as i', 'i.id', '=', 's.id')->where('s.name', 'siteBaseUrl')->value('i.value');
        $built = $data::build($ids, $base, (int) $options['min'], (string) $options['hide'], $year);

        $title = (string) $options['title'];
        // Not under the site: the checkout is the web root on some installs, so
        // a default inside it would publish the folder.
        $out = $options['out'] ?: sys_get_temp_dir().'/ahg-explorer/'.(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-') ?: 'explorer');
        if (!is_dir($out) && !mkdir($out, 0775, true)) {
            $this->logSection('ric', "Cannot create {$out}", null, 'ERROR');

            return 1;
        }
        $scope = trim(($options['ancestor'] ? 'collection '.$options['ancestor'] : '').' '.($year ? 'dated '.$year : ''));
        $built = ['site' => ['title' => $title, 'scope' => $scope, 'generated' => date('Y-m-d')]] + $built;
        file_put_contents("{$out}/data.js", 'window.DATA = '.json_encode($built, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).";\n");
        copy($data::template(), "{$out}/index.html");
        copy(dirname(__DIR__, 2).'/web/js/cytoscape.min.js', "{$out}/cytoscape.min.js");

        $this->logSection('ric', sprintf('%d records, %d names, %d same-story links. Written to %s - open index.html in a browser.',
            count($built['records']), count($built['entities']), count($built['links']), $out));

        return 0;
    }
}
