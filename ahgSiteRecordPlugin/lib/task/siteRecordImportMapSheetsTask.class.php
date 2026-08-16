<?php

/**
 * Create site records from locality held as free text on authority records.
 *
 * RARI records a site's location as a 1:50,000 map sheet reference buried in the
 * ISAAR "Internal structures/genealogy" field, mixed with a classification code
 * and an internal index:
 *
 *     E?.8.2.G  pl location th i: x100  Map sheet: 3027AC
 *               Map sheet: 3027AC_1965_ED1_GEO  Map sheet: 3027AC_2009_ED3_GEO
 *
 * That field is currently hidden from everyone - the deployment comments the
 * render call out of the ISAAR templates - so staff who need the locality cannot
 * see it either. This task lifts it into ahg_site_record, where
 * LocalityVisibilityService shows it to cleared users and withholds it from the
 * public. Restrict rather than remove, which is what issue #299 asked for.
 *
 * WHAT IS AND IS NOT EXTRACTED
 *
 *   map_sheet          the plain sheet, e.g. 3027AC. The _1965_ED1_GEO forms are
 *                      scanned editions OF that sheet, not distinct localities,
 *                      so they are not treated as separate references.
 *   locality_original  the whole string, verbatim. Structuring must not destroy
 *                      the source, and the code and index are preserved here.
 *   locality_sensitive always 1 on create.
 *
 * Deliberately NOT mapped to site_number: neither the classification code nor
 * the internal index identifies a site. Measured on RARI's 7,585 records: 383
 * distinct codes, and 280 of 387 indexes are shared by more than one actor -
 * "Tin Tazarift XIII" and "Timenzouzin II" both carry x788. The site's identity
 * is the authority record's own name, which the plugin deliberately does not
 * duplicate.
 *
 * Dry run unless --apply is given.
 */
class siteRecordImportMapSheetsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_OPTIONAL, 'The environment', 'cli'),
            new sfCommandOption('apply', null, sfCommandOption::PARAMETER_NONE, 'Write the records. Without this the task only reports.'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process at most this many authority records', null),
            new sfCommandOption('update', null, sfCommandOption::PARAMETER_NONE, 'Also update authority records that already have a site record'),
            new sfCommandOption('show-unparsed', null, sfCommandOption::PARAMETER_NONE, 'List the records no locality could be read from'),
        ]);

        $this->namespace = 'siterecord';
        $this->name = 'import-mapsheets';
        $this->briefDescription = 'Create site records from map sheet references held in ISAAR internal structures';
        $this->detailedDescription = <<<'EOF'
Reads actor_i18n.internal_structures, extracts the 1:50,000 map sheet reference,
and creates one ahg_site_record per authority record with the locality marked
sensitive.

Reports without writing unless --apply is given:

  php symfony siterecord:import-mapsheets
  php symfony siterecord:import-mapsheets --apply
  php symfony siterecord:import-mapsheets --apply --update
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $apply = !empty($options['apply']);
        $update = !empty($options['update']);

        $query = $db->table('actor_i18n as ai')
            ->join('actor as a', 'a.id', '=', 'ai.id')
            ->whereNotNull('ai.internal_structures')
            ->where('ai.internal_structures', '<>', '')
            ->where('a.id', '>', 6)
            ->select(['ai.id', 'ai.internal_structures', 'ai.authorized_form_of_name as name'])
            ->orderBy('ai.id');

        if (!empty($options['limit'])) {
            $query->limit((int) $options['limit']);
        }

        $rows = $query->get();

        $existing = $db->table('ahg_site_record')->pluck('actor_id')->all();
        $existing = array_flip(array_map('intval', $existing));

        $created = $updated = $skipped = $noSheet = $unparsed = 0;
        $unparsedSamples = [];
        $sheets = [];

        foreach ($rows as $row) {
            $actorId = (int) $row->id;
            $text = $this->normalise((string) $row->internal_structures);

            if ('' === $text) {
                ++$unparsed;
                if (count($unparsedSamples) < 10) {
                    $unparsedSamples[] = $actorId.'  '.($row->name ?? '');
                }

                continue;
            }

            $sheet = $this->sheet($text);

            if (null === $sheet) {
                ++$noSheet;
            } else {
                $sheets[$sheet] = true;
            }

            $has = isset($existing[$actorId]);

            if ($has && !$update) {
                ++$skipped;

                continue;
            }

            if ($apply) {
                if ($has) {
                    // Never clear a value a person has since entered by hand.
                    $db->table('ahg_site_record')
                        ->where('actor_id', $actorId)
                        ->update(array_filter([
                            'map_sheet' => $sheet,
                            'locality_original' => $text,
                        ], static fn ($v) => null !== $v) + ['updated_at' => date('Y-m-d H:i:s')]);
                } else {
                    $db->table('ahg_site_record')->insert([
                        'actor_id' => $actorId,
                        'map_sheet' => $sheet,
                        'locality_original' => $text,
                        // Sensitive by default. This is the whole point of the
                        // exercise - the data becomes visible to staff, not to
                        // the public.
                        'locality_sensitive' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $has ? ++$updated : ++$created;
        }

        $this->logSection('siterecord', sprintf('%s %d authority records with locality text',
            $apply ? 'Processed' : 'Would process', count($rows)));
        $this->logSection('siterecord', sprintf('  %s        : %d', $apply ? 'created      ' : 'would create ', $created));
        $this->logSection('siterecord', sprintf('  %s        : %d', $apply ? 'updated      ' : 'would update ', $updated));
        $this->logSection('siterecord', sprintf('  skipped (already have one) : %d', $skipped));
        $this->logSection('siterecord', sprintf('  no map sheet found         : %d (locality text still kept)', $noSheet));
        $this->logSection('siterecord', sprintf('  nothing readable at all    : %d', $unparsed));
        $this->logSection('siterecord', sprintf('  distinct map sheets        : %d', count($sheets)));

        if ($unparsedSamples && !empty($options['show-unparsed'])) {
            $this->logSection('siterecord', 'unreadable records:');
            foreach ($unparsedSamples as $s) {
                $this->logSection('siterecord', '  '.$s);
            }
        }

        if (!$apply) {
            $this->logSection('siterecord', 'Dry run. Re-run with --apply to write.');
        }

        return 0;
    }

    /**
     * Parsing lives in LocalityTextParser so it can be unit tested without a
     * database, and so the standalone importer used on instances that do not
     * load AHG plugins in CLI runs the same code rather than a copy.
     */
    private function normalise(string $raw): string
    {
        require_once dirname(__FILE__, 2).'/Services/LocalityTextParser.php';

        return \AhgSiteRecordPlugin\Services\LocalityTextParser::normalise($raw);
    }

    private function sheet(string $text): ?string
    {
        require_once dirname(__FILE__, 2).'/Services/LocalityTextParser.php';

        return \AhgSiteRecordPlugin\Services\LocalityTextParser::mapSheet($text);
    }
}
