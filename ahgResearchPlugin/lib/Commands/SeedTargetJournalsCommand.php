<?php

namespace AtomFramework\Console\Commands\Research;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Seed the target-journal directory with the DHET-accredited starter set
 * (South-African accreditation module). Idempotent (upsert by title), so it is
 * safe to re-run. (#114 / Heratio #1107)
 *
 * AtoM port of Heratio AhgResearch\Commands\SeedTargetJournalsCommand.
 *
 * Usage:
 *   php bin/atom research:seed-target-journals
 *
 * @package ahgResearchPlugin
 */
class SeedTargetJournalsCommand extends BaseCommand
{
    protected string $name = 'research:seed-target-journals';

    protected string $description = 'Seed the target-journal directory with the DHET-accredited starter set (#114).';

    protected string $detailedDescription = <<<'EOF'
    Seed (idempotent upsert by title) the target-journal directory with a curated
    DHET-accredited starter set, tagged as the South-African accreditation module
    (accreditation_market = 'ZA'). Safe to re-run.

    The directory core is jurisdiction-neutral; other markets seed from
    DOAJ / Scopus / Web of Science / ERIH-PLUS via the admin UI.

    Examples:
      php bin/atom research:seed-target-journals
    EOF;

    protected function handle(): int
    {
        if (!DB::schema()->hasTable('research_target_journal')) {
            $this->error('research_target_journal table is missing; run the package install SQL first (database/target_journals.sql).');

            return 1;
        }

        require_once dirname(__DIR__) . '/Services/TargetJournalService.php';
        $service = new \TargetJournalService();

        $n = $service->seedDhetStarter();
        $this->info("Seeded/updated {$n} DHET-accredited journals (South-African accreditation module).");

        return 0;
    }
}
