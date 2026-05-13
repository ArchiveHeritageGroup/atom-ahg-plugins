<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Promote library_item_subject sidecar rows into the AtoM Subject taxonomy by
 * upserting a QubitTerm under taxonomy_id=35 for each heading and linking it
 * to the IO via object_term_relation. Without this, ISBN-captured subjects
 * never appear in the standard AtoM Subject facets.
 *
 *   php symfony library:backfill-subjects
 *   php symfony library:backfill-subjects --dry-run
 *
 * Idempotent: existing object_term_relation links are detected and skipped,
 * existing terms are reused.
 */
class libraryBackfillSubjectsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report what would change without writing'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Force a specific culture', null),
        ]);

        $this->namespace = 'library';
        $this->name = 'backfill-subjects';
        $this->briefDescription = 'Mirror library_item_subject rows into AtoM Subject taxonomy (term + object_term_relation).';
        $this->detailedDescription = <<<EOF
For every library_item_subject row, ensure a Subject term (taxonomy_id=35) exists
for the heading and that the heading's information_object is linked via
object_term_relation. Mirrors ISBN-capture logic for retroactive data.

  php symfony library:backfill-subjects
  php symfony library:backfill-subjects --dry-run
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        $dryRun = !empty($options['dry-run']);
        $culture = $options['culture'] ?: (sfContext::getInstance()->getUser()->getCulture() ?: 'en');

        $service = LibraryService::getInstance($culture);

        // Skip sidecar rows whose IO has been deleted (stale library_item).
        // The inner join on object filters those out before we try to write
        // an FK-constrained object_term_relation.id row pointing at a missing
        // information_object.
        $rows = DB::table('library_item_subject as lis')
            ->join('library_item as li', 'li.id', '=', 'lis.library_item_id')
            ->join('object as o', 'o.id', '=', 'li.information_object_id')
            ->select('lis.id', 'lis.heading', 'li.information_object_id')
            ->orderBy('lis.id')
            ->get();

        $count = is_object($rows) && method_exists($rows, 'count') ? $rows->count() : count($rows);
        if ($count === 0) {
            $this->logSection('library', 'Nothing to do: library_item_subject is empty.');

            return 0;
        }

        $this->logSection('library', sprintf(
            'Processing %d sidecar subject rows (culture=%s%s)',
            $count,
            $culture,
            $dryRun ? ', DRY RUN' : ''
        ));

        $termCache = [];
        $termsCreated = 0;
        $termsReused = 0;
        $linksCreated = 0;
        $linksExisted = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $heading = trim((string) $row->heading);
            $ioId = (int) $row->information_object_id;
            if ($heading === '' || $ioId === 0) {
                $skipped++;

                continue;
            }

            if (!array_key_exists($heading, $termCache)) {
                // Check whether term already exists so we can report
                // created-vs-reused without re-querying inside the service.
                $existing = DB::table('term_i18n')
                    ->join('term', 'term.id', '=', 'term_i18n.id')
                    ->where('term.taxonomy_id', 35)
                    ->where('term_i18n.culture', $culture)
                    ->whereRaw('LOWER(TRIM(term_i18n.name)) = ?', [mb_strtolower($heading)])
                    ->value('term.id');

                if ($dryRun) {
                    $termCache[$heading] = $existing ?: 0;
                } else {
                    $termCache[$heading] = $service->resolveOrCreateSubjectTerm($heading, $culture);
                }

                if ($existing) {
                    $termsReused++;
                } else {
                    $termsCreated++;
                }
            }

            $termId = $termCache[$heading];
            if (!$termId) {
                continue;
            }

            $alreadyLinked = DB::table('object_term_relation')
                ->where('object_id', $ioId)
                ->where('term_id', $termId)
                ->exists();

            if ($alreadyLinked) {
                $linksExisted++;
            } else {
                if (!$dryRun) {
                    $service->linkIoToTerm($ioId, (int) $termId);
                }
                $linksCreated++;
            }
        }

        $this->logSection('library', sprintf(
            '%sTerms: %d reused, %d %s. Links: %d %s, %d already in place. Skipped (empty): %d.',
            $dryRun ? '[DRY RUN] ' : '',
            $termsReused,
            $termsCreated,
            $dryRun ? 'would be created' : 'created',
            $linksCreated,
            $dryRun ? 'would be created' : 'created',
            $linksExisted,
            $skipped
        ));

        if (!$dryRun) {
            $this->logSection('library', 'Run `php symfony search:populate` so the new Subject links surface in browse facets.');
        }

        return 0;
    }
}
