<?php

/**
 * authResPromoteSampleTask — Symfony 1.4 task for AtoM Heratio
 *
 * Demonstrate authority-resolution mention extraction with neighbourhood
 * context against a sample information object. Used for the Task 2
 * pause-gate demo on the AtoM side. Idempotent.
 *
 * Usage:
 *   php symfony auth-res:promote-sample <object_id> [--show] [--limit=N]
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later.
 */

// Explicit requires: SF1.4 has no PSR-4 autoloader for our namespaced plugin classes.
require_once __DIR__ . '/../Services/ContextDerivationService.php';
require_once __DIR__ . '/../Services/PromoteToMentionService.php';

use AtomFramework\Services\AuthorityResolution\ContextDerivationService;
use AtomFramework\Services\AuthorityResolution\PromoteToMentionService;
use Illuminate\Database\Capsule\Manager as DB;

class authResPromoteSampleTask extends arBaseTask
{
    protected function configure()
    {
        $this->addArguments(array(
            new sfCommandArgument('object_id', sfCommandArgument::REQUIRED, 'Information object ID to promote mentions for'),
        ));

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('show', null, sfCommandOption::PARAMETER_NONE, 'Print full context packets for the promoted mentions'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Max promoted mentions to display when --show is set', 5),
        ));

        $this->namespace = 'auth-res';
        $this->name = 'promote-sample';
        $this->briefDescription = 'Promote PERSON/ORG/GPE entities for an information object into the authority-resolution mention workflow.';
        $this->detailedDescription = <<<EOF
Demonstrates the authority-resolution context derivation against an existing
information object's NER entities. Reads ahg_ner_entity rows for the given
object_id, fetches source text from information_object_i18n (best-effort),
derives neighbourhood context, and writes ahg_mention + ahg_mention_context
in a single transaction per mention. Idempotent.

  php symfony auth-res:promote-sample 901990
  php symfony auth-res:promote-sample 901990 --show --limit=3
EOF;
    }

    public function execute($arguments = array(), $options = array())
    {
        parent::execute($arguments, $options);

        $objectId = (int) $arguments['object_id'];

        $entityCount = DB::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->whereIn('entity_type', ['PERSON', 'ORG', 'GPE', 'PLACE', 'LOC'])
            ->count();

        if ($entityCount === 0) {
            $this->logSection('auth-res', sprintf('No PERSON/ORG/GPE entities found for object_id=%d in ahg_ner_entity.', $objectId), null, 'ERROR');
            return 1;
        }

        $this->log(sprintf('Object %d: %d resolvable NER entities found. Promoting...', $objectId, $entityCount));

        $promoter = new PromoteToMentionService(new ContextDerivationService());
        $newCount = $promoter->promoteAllForObject($objectId);

        $this->log(sprintf('Promoted: %d new mentions. (Idempotent: existing mentions skipped.)', $newCount));

        $totalMentions = DB::table('ahg_mention')->where('object_id', $objectId)->count();
        $this->log(sprintf('Total ahg_mention rows for object %d: %d', $objectId, $totalMentions));

        if ($options['show']) {
            $this->printMentionDetail($objectId, (int) $options['limit']);
        }

        return 0;
    }

    private function printMentionDetail(int $objectId, int $limit): void
    {
        $rows = DB::table('ahg_mention as m')
            ->leftJoin('ahg_mention_context as c', 'c.mention_id', '=', 'm.id')
            ->leftJoin('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->where('m.object_id', $objectId)
            ->orderBy('m.id', 'desc')
            ->limit($limit)
            ->get([
                'm.id as mention_id',
                'n.entity_value',
                'm.entity_type',
                'm.state',
                'c.character_offset_start',
                'c.character_offset_end',
                'c.surrounding_text_before',
                'c.surrounding_text_after',
                'c.co_occurring_entities',
                'c.nearby_dates',
                'c.nearby_places',
                'c.role_language_tokens',
            ]);

        foreach ($rows as $row) {
            $this->log('');
            $this->log(sprintf('--- mention #%d ---', $row->mention_id));
            $this->log(sprintf('  entity        : [%s] %s', $row->entity_type, $row->entity_value));
            $this->log(sprintf('  state         : %s', $row->state));
            $offRange = ($row->character_offset_start !== null)
                ? sprintf('%d-%d', $row->character_offset_start, $row->character_offset_end)
                : '-';
            $this->log(sprintf('  offsets       : %s', $offRange));
            $this->log(sprintf('  before (~150) : %s', $this->truncate(trim((string) $row->surrounding_text_before), 120)));
            $this->log(sprintf('  after  (~150) : %s', $this->truncate(trim((string) $row->surrounding_text_after), 120)));
            $this->printJsonList('co_occurring ', $row->co_occurring_entities);
            $this->printJsonList('nearby_dates ', $row->nearby_dates);
            $this->printJsonList('nearby_places', $row->nearby_places);
            $this->printJsonList('role_language', $row->role_language_tokens);
        }
    }

    private function printJsonList(string $label, ?string $json): void
    {
        $items = json_decode((string) $json, true);
        if (!is_array($items) || empty($items)) {
            $this->log(sprintf('  %s : (empty)', $label));
            return;
        }
        $count = count($items);
        $preview = array_slice($items, 0, 5);
        $this->log(sprintf('  %s : %d items', $label, $count));
        foreach ($preview as $item) {
            if (is_array($item)) {
                if (isset($item['token'])) {
                    $this->log(sprintf("                  - [%s] '%s'", $item['kind'] ?? '?', $item['token']));
                } else {
                    $this->log(sprintf(
                        "                  - [%s] '%s' (d=%s)",
                        $item['type'] ?? '?',
                        $item['value'] ?? '',
                        $item['distance_chars'] ?? '?'
                    ));
                }
            }
        }
        if ($count > 5) {
            $this->log(sprintf('                  ... +%d more', $count - 5));
        }
    }

    private function truncate(string $s, int $max): string
    {
        $s = str_replace(["\n", "\r"], ' ', $s);
        if (strlen($s) <= $max) {
            return $s;
        }
        return substr($s, 0, $max - 3) . '...';
    }
}
