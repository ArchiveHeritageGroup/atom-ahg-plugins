<?php

namespace AtomFramework\Console\Commands\Research;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Backfill a researcher's journal/logbook (research_journal_entry) from their
 * real research footprint: collections, honoured reading-room bookings and
 * bibliographies, plus an optional "background & objectives" entry.
 *
 * Idempotent: every generated entry is linked to its source row via
 * (related_entity_type, related_entity_id) and tagged entry_type='backfill',
 * so re-running skips anything already backfilled. Safe to re-run.
 *
 * Usage:
 *   php bin/atom research:backfill-journal 25          # by researcher id
 *   php bin/atom research:backfill-journal --email=johan@theahg.co.za
 *   php bin/atom research:backfill-journal --all
 *   php bin/atom research:backfill-journal 25 --dry-run
 *   php bin/atom research:backfill-journal 25 --skip-background
 *
 * @package ahgResearchPlugin
 */
class BackfillJournalCommand extends BaseCommand
{
    protected string $name = 'research:backfill-journal';

    protected string $description = 'Backfill a researcher journal from their collections, bookings and bibliographies.';

    protected string $detailedDescription = <<<'EOF'
    Generate journal/logbook entries (research_journal_entry) for a researcher,
    derived from their real research footprint:

      - research_collection      -> "Collection survey: <name>"  (with item count)
      - research_booking          -> "Reading-room visit - <date>" (confirmed visits)
      - research_bibliography     -> "Bibliography started: <name>"
      - one optional "Research background & objectives" overview entry

    Idempotency: each entry stores its source linkage in
    (related_entity_type, related_entity_id) with entry_type='backfill'. A source
    that already has a backfill entry is skipped, so the command is safe to re-run.

    Existing auto_* / manual entries are never touched or removed.

    Target selection (one of):
      <researcher>     positional research_researcher.id
      --email=<email>  resolve the researcher by email
      --all            every researcher that has at least one source row

    Examples:
      php bin/atom research:backfill-journal 25
      php bin/atom research:backfill-journal --email=johan@theahg.co.za --dry-run
      php bin/atom research:backfill-journal --all --skip-background
    EOF;

    protected function configure(): void
    {
        $this->addArgument('researcher', 'research_researcher.id to backfill', false);
        $this->addOption('email', 'e', 'Resolve the researcher by email instead of id');
        $this->addOption('all', 'a', 'Backfill every researcher that has source rows');
        $this->addOption('dry-run', 'd', 'Preview entries without writing anything');
        $this->addOption('skip-background', null, 'Do not create the background/objectives entry');
        $this->addOption('public', null, 'Mark generated entries public (default: private)');
    }

    protected function handle(): int
    {
        if (!DB::schema()->hasTable('research_journal_entry')) {
            $this->error('research_journal_entry table is missing; run the package install SQL first.');

            return 1;
        }

        $dryRun = (bool) $this->option('dry-run');
        $skipBackground = (bool) $this->option('skip-background');
        $isPrivate = $this->option('public') ? 0 : 1;

        $researchers = $this->resolveResearchers();
        if (empty($researchers)) {
            $this->error('No matching researcher. Pass a researcher id, --email=<email>, or --all.');

            return 1;
        }

        if ($dryRun) {
            $this->warning('DRY RUN — no rows will be written.');
        }

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($researchers as $researcher) {
            $label = trim(($researcher->first_name ?? '') . ' ' . ($researcher->last_name ?? ''));
            $label = $label !== '' ? $label : ('researcher #' . $researcher->id);
            $this->newline();
            $this->bold("  {$label} (researcher #{$researcher->id})");

            $candidates = $this->buildCandidates((int) $researcher->id, $skipBackground);
            if (empty($candidates)) {
                $this->comment('    no source activity to backfill');
                continue;
            }

            $rows = [];
            foreach ($candidates as $c) {
                $exists = DB::table('research_journal_entry')
                    ->where('researcher_id', $researcher->id)
                    ->where('related_entity_type', $c['related_entity_type'])
                    ->where('related_entity_id', $c['related_entity_id'])
                    ->exists();

                if ($exists) {
                    ++$totalSkipped;
                    $rows[] = ['skip', $c['entry_date'], $c['title']];
                    continue;
                }

                if (!$dryRun) {
                    DB::table('research_journal_entry')->insert([
                        'researcher_id' => $researcher->id,
                        'project_id' => null,
                        'entry_date' => $c['entry_date'],
                        'title' => $c['title'],
                        'content' => $c['content'],
                        'content_format' => 'html',
                        'entry_type' => 'backfill',
                        'tags' => $c['tags'],
                        'is_private' => $isPrivate,
                        'related_entity_type' => $c['related_entity_type'],
                        'related_entity_id' => $c['related_entity_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                ++$totalCreated;
                $rows[] = [$dryRun ? 'would' : 'create', $c['entry_date'], $c['title']];
            }

            $this->table(['action', 'date', 'title'], $rows);
        }

        $this->newline();
        $verb = $dryRun ? 'Would create' : 'Created';
        $this->success("{$verb} {$totalCreated} entr" . ($totalCreated === 1 ? 'y' : 'ies') . ", skipped {$totalSkipped} already-backfilled.");

        return 0;
    }

    /**
     * Resolve the target researcher(s) from the argument / options.
     *
     * @return array<int, object>
     */
    private function resolveResearchers(): array
    {
        if ($this->option('all')) {
            // Researchers owning at least one collection, booking or bibliography.
            $ids = [];
            foreach (['research_collection', 'research_booking', 'research_bibliography'] as $table) {
                if (DB::schema()->hasTable($table)) {
                    $ids = array_merge($ids, DB::table($table)->distinct()->pluck('researcher_id')->all());
                }
            }
            $ids = array_values(array_unique(array_filter($ids)));
            if (empty($ids)) {
                return [];
            }

            return DB::table('research_researcher')->whereIn('id', $ids)
                ->orderBy('id')->get()->all();
        }

        if ($email = $this->option('email')) {
            $r = DB::table('research_researcher')->where('email', $email)->first();

            return $r ? [$r] : [];
        }

        if ($id = $this->argument('researcher')) {
            $r = DB::table('research_researcher')->where('id', (int) $id)->first();

            return $r ? [$r] : [];
        }

        return [];
    }

    /**
     * Build candidate journal entries for one researcher.
     *
     * @return array<int, array{entry_date:string,title:string,content:string,tags:string,related_entity_type:string,related_entity_id:int}>
     */
    private function buildCandidates(int $researcherId, bool $skipBackground): array
    {
        $candidates = [];

        // Collections (with item counts).
        $collections = DB::table('research_collection')
            ->where('researcher_id', $researcherId)
            ->orderBy('created_at')
            ->get();

        foreach ($collections as $col) {
            $itemCount = DB::table('research_collection_item')
                ->where('collection_id', $col->id)->count();
            $name = $this->clean($col->name);
            $desc = $this->clean($col->description ?? '');
            $descPart = $desc !== '' ? ' — ' . $desc : '';
            $candidates[] = [
                'entry_date' => $this->toDate($col->created_at),
                'title' => "Collection survey: {$name}",
                'content' => "<p>Working collection <strong>{$name}</strong> — {$itemCount} item(s) gathered{$descPart}. Initial survey of scope and arrangement.</p>",
                'tags' => $this->slugTags($name, 'collection'),
                'related_entity_type' => 'collection',
                'related_entity_id' => (int) $col->id,
            ];
        }

        // Honoured reading-room bookings (confirmed / checked-in / completed).
        $bookings = DB::table('research_booking')
            ->where('researcher_id', $researcherId)
            ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
            ->orderBy('booking_date')
            ->get();

        foreach ($bookings as $bk) {
            $date = $this->toDate($bk->booking_date);
            $start = substr((string) ($bk->start_time ?? ''), 0, 5);
            $end = substr((string) ($bk->end_time ?? ''), 0, 5);
            $when = ($start && $end) ? " ({$start}–{$end})" : '';
            $purpose = $this->clean($bk->purpose ?? '');
            $purposePart = ($purpose !== '' && stripos($purpose, 'purpose of visit') === false)
                ? ' — ' . $purpose : '';
            $candidates[] = [
                'entry_date' => $date,
                'title' => "Reading-room visit — {$date}",
                'content' => "<p>Reading-room visit{$when}{$purposePart}. Onsite consultation of originals; observations to be folded back into the working collections.</p>",
                'tags' => 'reading-room,visit',
                'related_entity_type' => 'booking',
                'related_entity_id' => (int) $bk->id,
            ];
        }

        // Bibliographies.
        if (DB::schema()->hasTable('research_bibliography')) {
            $bibs = DB::table('research_bibliography')
                ->where('researcher_id', $researcherId)
                ->orderBy('created_at')
                ->get();

            foreach ($bibs as $bib) {
                $name = $this->clean($bib->name);
                $style = $this->clean($bib->citation_style ?? 'Harvard');
                $candidates[] = [
                    'entry_date' => $this->toDate($bib->created_at),
                    'title' => "Bibliography started: {$name}",
                    'content' => "<p>Started a {$style}-style bibliography <strong>{$name}</strong> to track sources and citations.</p>",
                    'tags' => $this->slugTags($name, 'bibliography'),
                    'related_entity_type' => 'bibliography',
                    'related_entity_id' => (int) $bib->id,
                ];
            }
        }

        // Background / objectives overview (one per researcher, earliest date).
        if (!$skipBackground && !empty($collections)) {
            $names = array_map(fn ($c) => $this->clean($c->name), $collections->all());
            $list = '<strong>' . implode('</strong>, <strong>', $names) . '</strong>';
            $earliest = $this->toDate($collections->first()->created_at);
            $candidates[] = [
                'entry_date' => $earliest,
                'title' => 'Research background & objectives',
                'content' => "<p>Opening the research log. Strands in progress: {$list}. This entry records the background and objectives for each line of enquiry.</p>",
                'tags' => 'background,objectives',
                'related_entity_type' => 'background',
                'related_entity_id' => 0,
            ];
        }

        return $candidates;
    }

    private function toDate($value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return date('Y-m-d');
        }

        return substr($value, 0, 10);
    }

    private function clean($value): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));
    }

    private function slugTags(string $name, string $kind): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 40);

        return $slug !== '' ? "{$slug},{$kind}" : $kind;
    }
}
