<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Promote pre-existing library_item_creator rows into Authority Records by
 * upserting a QubitActor per creator name and populating
 * library_item_creator.actor_id.
 *
 *   php symfony library:backfill-authors
 *   php symfony library:backfill-authors --dry-run
 *   php symfony library:backfill-authors --culture=af
 *
 * Idempotent: rows whose actor_id is already set are skipped, so re-running
 * after subsequent imports is safe.
 */
class libraryBackfillAuthorsTask extends sfBaseTask
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
        $this->name = 'backfill-authors';
        $this->briefDescription = 'Upsert an Authority Record (actor) for every library_item_creator row whose actor_id is NULL and link them.';
        $this->detailedDescription = <<<EOF
Looks up an actor by authorized_form_of_name (case-insensitive, trimmed) for
each library_item_creator row whose actor_id is NULL. If no actor exists, a
new QubitActor object + actor + actor_i18n row is created. The creator row
is then updated with the resolved actor_id.

  php symfony library:backfill-authors
  php symfony library:backfill-authors --dry-run
  php symfony library:backfill-authors --culture=af
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        QubitSearch::disable();

        $dryRun = !empty($options['dry-run']);
        $culture = $options['culture'] ?: (sfContext::getInstance()->getUser()->getCulture() ?: 'en');
        $fallback = 'en';

        $service = LibraryService::getInstance($culture);

        $rows = DB::table('library_item_creator')
            ->whereNull('actor_id')
            ->orderBy('id')
            ->get(['id', 'name']);

        $count = is_object($rows) && method_exists($rows, 'count') ? $rows->count() : count($rows);
        if ($count === 0) {
            $this->logSection('library', 'Nothing to do: every library_item_creator row already has actor_id set.');

            return 0;
        }

        $this->logSection('library', sprintf(
            'Found %d unlinked creator rows (culture=%s%s)',
            $count,
            $culture,
            $dryRun ? ', DRY RUN' : ''
        ));

        $cache = [];
        $created = 0;
        $reused = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name = trim((string) $row->name);
            if ($name === '') {
                $skipped++;

                continue;
            }

            if (!array_key_exists($name, $cache)) {
                $existing = DB::table('actor_i18n')
                    ->whereIn('culture', array_unique([$culture, $fallback]))
                    ->whereRaw('LOWER(TRIM(authorized_form_of_name)) = ?', [mb_strtolower($name)])
                    ->orderByRaw('FIELD(culture, ?, ?)', [$culture, $fallback])
                    ->value('id');

                if ($dryRun) {
                    $cache[$name] = $existing ?: 0;
                } else {
                    $cache[$name] = $service->resolveOrCreateActor($name, $culture, $fallback);
                }

                if ($existing) {
                    $reused++;
                } else {
                    $created++;
                }
            }

            if (!$dryRun && !empty($cache[$name])) {
                DB::table('library_item_creator')
                    ->where('id', $row->id)
                    ->update(['actor_id' => $cache[$name]]);
            }
        }

        $this->logSection('library', sprintf(
            '%s%d rows processed: %d reused existing actors, %d new actors %s, %d skipped (empty names).',
            $dryRun ? '[DRY RUN] ' : '',
            $count,
            $reused,
            $created,
            $dryRun ? 'would be created' : 'created',
            $skipped
        ));

        if (!$dryRun) {
            $this->logSection('library', 'Reindex Elasticsearch (php symfony search:populate) so the new authority records resolve in search.');
        }

        return 0;
    }
}
