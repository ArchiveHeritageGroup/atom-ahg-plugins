<?php

/**
 * ai:classify-pages - per-page document type and privacy screening of records'
 * PDFs, rolled up to the record (catalogue#320, twin of heratio#1584).
 *
 * Run as the web user (www-data); the record's Pages screen starts it in the
 * background. Document types use the GPU through the AI gateway, so they are
 * left out between 18:00 and 06:00 SAST; the privacy screen always runs.
 */
class aiClassifyPagesTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('slug', null, sfCommandOption::PARAMETER_OPTIONAL, 'Record slugs, comma-separated'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Record ids, comma-separated'),
            new sfCommandOption('collection', null, sfCommandOption::PARAMETER_OPTIONAL, 'Every record with a PDF under this record id'),
            new sfCommandOption('privacy-only', null, sfCommandOption::PARAMETER_NONE, 'Skip the document type (no GPU)'),
            new sfCommandOption('user', null, sfCommandOption::PARAMETER_OPTIONAL, 'Triggering user id'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum records', 0),
        ]);
        $this->namespace = 'ai';
        $this->name = 'classify-pages';
        $this->briefDescription = 'Per-page document type and privacy screening, rolled up to the record';
        $this->detailedDescription = <<<'EOD'
Examples:
  [php symfony ai:classify-pages --slug=stores-file-2026|INFO]
  [php symfony ai:classify-pages --collection=913567 --privacy-only|INFO]
EOD;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();
        require_once dirname(__DIR__) . '/Services/PageClassifier.php';
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $ids = array_filter(array_map('intval', explode(',', (string) $options['id'])));
        if ($options['slug']) {
            $ids = array_merge($ids, $DB::table('slug')->whereIn('slug', array_map('trim', explode(',', $options['slug'])))->pluck('object_id')->map(fn ($i) => (int) $i)->all());
        }
        if ($root = (int) $options['collection']) {
            $n = $DB::table('information_object')->where('id', $root)->first(['lft', 'rgt']);
            if (!$n) {
                $this->logSection('pages', "No record {$root}", null, 'ERROR');

                return 1;
            }
            $q = $DB::table('digital_object as d')->join('information_object as io', 'io.id', '=', 'd.object_id')
                ->where('io.lft', '>=', $n->lft)->where('io.rgt', '<=', $n->rgt)
                ->whereNull('d.parent_id')->where('d.mime_type', 'application/pdf')->distinct()->orderBy('d.object_id');
            if ($limit = (int) $options['limit']) {
                $q->limit($limit);
            }
            $ids = array_merge($ids, $q->pluck('d.object_id')->map(fn ($i) => (int) $i)->all());
        }
        if (!$ids) {
            $this->logSection('pages', 'Name records with --slug, --id or --collection', null, 'ERROR');

            return 1;
        }

        $withDoctype = $options['privacy-only'] ? false : \AhgAIPlugin\Services\PageClassifier::gpuHours();
        if (!$withDoctype && !$options['privacy-only']) {
            $this->logSection('pages', 'Outside 06:00-18:00 SAST: privacy screen only, no document types (GPUs are off)');
        }
        $pc = new \AhgAIPlugin\Services\PageClassifier();
        $user = $options['user'] ? (int) $options['user'] : null;
        foreach (array_unique($ids) as $id) {
            try {
                $r = $pc->run($id, $user, $withDoctype);
                $this->logSection('pages', sprintf('%d: %s', $id, $r['error'] ?? sprintf('%d unit(s), flagged: %s', $r['units'], $r['flagged'] ? implode(', ', $r['flagged']) : 'none')));
            } catch (\Throwable $e) {
                $this->logSection('pages', "{$id}: " . $e->getMessage(), null, 'ERROR');
            }
        }

        return 0;
    }
}
