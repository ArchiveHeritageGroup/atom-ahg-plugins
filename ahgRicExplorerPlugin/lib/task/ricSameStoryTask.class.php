<?php

/**
 * Same-story links and duplicate-leaf QA for a collection (#196).
 *
 * Usage:
 *   php symfony ric:same-story --ancestor=<id|slug>
 *   php symfony ric:same-story --ancestor=<id|slug> --report=/path/leaves.csv
 *
 * Reads each published record's text (digital object OCR transcript, else
 * scope and content) and stores the links in ric_same_story_link, replacing
 * earlier links within the collection. --report writes the page pairs that
 * share 3 or more items (a leaf photographed twice) as CSV.
 *
 * @package    ahgRicExplorerPlugin
 */
class ricSameStoryTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('ancestor', null, sfCommandOption::PARAMETER_REQUIRED, 'Collection or fonds (id or slug)'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Most records to compare', 20000),
            new sfCommandOption('report', null, sfCommandOption::PARAMETER_REQUIRED, 'Write the duplicate-leaf report (CSV) here'),
        ]);

        $this->namespace = 'ric';
        $this->name = 'same-story';
        $this->briefDescription = 'Find records telling the same story (text overlap) and leaves photographed twice';
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once dirname(__DIR__).'/Services/SameStoryService.php';
        require_once dirname(__DIR__).'/Services/ExplorerDataService.php';
        $data = '\\AhgRicExplorer\\Services\\ExplorerDataService';

        $root = $options['ancestor'] ? $data::resolve((string) $options['ancestor']) : null;
        if (!$root) {
            $this->logSection('ric', 'Give --ancestor=<id|slug> of an existing collection.', null, 'ERROR');

            return 1;
        }
        $ids = $data::scope($root, null, (int) $options['limit']);
        $this->logSection('ric', count($ids).' published records in scope.');

        $result = \AhgRicExplorer\Services\SameStoryService::run($ids);
        $this->logSection('ric', sprintf('%d with enough text, %d links, %d page pairs photographed twice.', $result['texts'], $result['links'], count($result['leaves'])));

        if ($options['report']) {
            $f = fopen($options['report'], 'w');
            fputcsv($f, ['page_a_id', 'page_a_slug', 'page_b_id', 'page_b_slug', 'shared_items']);
            foreach ($result['leaves'] as $l) {
                $slug = fn ($id) => \Illuminate\Database\Capsule\Manager::table('slug')->where('object_id', $id)->value('slug');
                fputcsv($f, [$l['a'], $slug($l['a']), $l['b'], $slug($l['b']), $l['items']]);
            }
            fclose($f);
            $this->logSection('ric', 'Duplicate-leaf report: '.$options['report']);
        }

        return 0;
    }
}
