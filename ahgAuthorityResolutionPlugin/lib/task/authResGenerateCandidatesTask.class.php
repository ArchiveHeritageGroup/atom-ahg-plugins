<?php

/**
 * authResGenerateCandidatesTask - Symfony 1.4 task for AtoM Heratio
 *
 * Demo task for Task 3 (Candidate generation). Given a mention_id (or
 * every mention on an information object via --object-id), runs the
 * adapter pipeline, persists the top-N candidates into
 * ahg_mention_candidate, and optionally prints the resulting ranks.
 *
 * Usage:
 *   php symfony auth-res:generate-candidates <mention_id> [--show] [--top=N]
 *   php symfony auth-res:generate-candidates --object-id=901990 [--show] [--top=N]
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later.
 */

// Explicit requires: Symfony 1.4 has no PSR-4 autoloader for our namespaced
// plugin classes. atom-framework boots Capsule but not our lib/Services/ tree.
require_once __DIR__ . '/../Services/Adapters/CandidateAdapterInterface.php';
require_once __DIR__ . '/../Services/Adapters/MysqlActorAdapter.php';
require_once __DIR__ . '/../Services/Adapters/MysqlTermAdapter.php';
require_once __DIR__ . '/../Services/Adapters/FusekiAgentAdapter.php';
require_once __DIR__ . '/../Services/Adapters/FusekiPlaceAdapter.php';
require_once __DIR__ . '/../Services/CandidateGeneratorService.php';

use AtomFramework\Services\AuthorityResolution\Adapters\FusekiAgentAdapter;
use AtomFramework\Services\AuthorityResolution\Adapters\FusekiPlaceAdapter;
use AtomFramework\Services\AuthorityResolution\Adapters\MysqlActorAdapter;
use AtomFramework\Services\AuthorityResolution\Adapters\MysqlTermAdapter;
use AtomFramework\Services\AuthorityResolution\CandidateGeneratorService;
use Illuminate\Database\Capsule\Manager as DB;

class authResGenerateCandidatesTask extends arBaseTask
{
    protected function configure()
    {
        $this->addArguments(array(
            new sfCommandArgument('mention_id', sfCommandArgument::OPTIONAL, 'ahg_mention.id to generate candidates for'),
        ));

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Generate candidates for every mention on this information_object.id'),
            new sfCommandOption('show', null, sfCommandOption::PARAMETER_NONE, 'Print ranked candidates per mention'),
            new sfCommandOption('top', null, sfCommandOption::PARAMETER_REQUIRED, 'Override top-N (otherwise reads ahg_settings.authority_resolution.candidate_top_n)'),
        ));

        $this->namespace = 'auth-res';
        $this->name = 'generate-candidates';
        $this->briefDescription = 'Generate ranked authority candidates for an ahg_mention.';
        $this->detailedDescription = <<<EOF
Runs the Task 3 candidate-generation pipeline for either a single mention
or every mention on a given information_object. Wipes and rewrites
ahg_mention_candidate rows for each mention (transactional). Scoring is
shared verbatim with the Laravel side so rankings converge.

  php symfony auth-res:generate-candidates 998
  php symfony auth-res:generate-candidates 998 --show --top=10
  php symfony auth-res:generate-candidates --object-id=901990 --show
EOF;
    }

    public function execute($arguments = array(), $options = array())
    {
        parent::execute($arguments, $options);

        $topOverride = (isset($options['top']) && $options['top'] !== null && $options['top'] !== '')
            ? (int) $options['top']
            : null;

        $generator = new CandidateGeneratorService([
            new MysqlActorAdapter(),
            new MysqlTermAdapter(),
            new FusekiAgentAdapter(),
            new FusekiPlaceAdapter(),
        ]);

        $mentionIds = $this->resolveMentionIds($arguments, $options);
        if (empty($mentionIds)) {
            $this->logSection('auth-res', 'No mentions resolved. Pass a mention_id argument or --object-id=N.', null, 'ERROR');
            return 1;
        }

        $totalCandidates = 0;
        foreach ($mentionIds as $mentionId) {
            $ids = $generator->generate($mentionId, $topOverride);
            $totalCandidates += count($ids);
            $this->log(sprintf(
                'Mention #%d: %d candidate(s) written.',
                $mentionId,
                count($ids)
            ));
            if (!empty($options['show'])) {
                $this->printRanked($mentionId);
            }
        }

        $this->log(sprintf(
            'Done. %d mention(s) processed, %d candidate row(s) written.',
            count($mentionIds),
            $totalCandidates
        ));
        return 0;
    }

    /**
     * @return int[]
     */
    private function resolveMentionIds(array $arguments, array $options): array
    {
        if (!empty($options['object-id'])) {
            $rows = DB::table('ahg_mention')
                ->where('object_id', (int) $options['object-id'])
                ->orderBy('id')
                ->pluck('id');
            $ids = [];
            foreach ($rows as $id) {
                $ids[] = (int) $id;
            }
            return $ids;
        }
        if (!empty($arguments['mention_id'])) {
            return [(int) $arguments['mention_id']];
        }
        return [];
    }

    private function printRanked(int $mentionId): void
    {
        $mention = DB::table('ahg_mention as m')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->where('m.id', $mentionId)
            ->first(['m.entity_type', 'n.entity_value']);

        $header = sprintf(
            '--- mention #%d  [%s]  "%s" ---',
            $mentionId,
            $mention ? $mention->entity_type : '?',
            $mention ? $mention->entity_value : '?'
        );
        $this->log('');
        $this->log($header);

        $rows = DB::table('ahg_mention_candidate')
            ->where('mention_id', $mentionId)
            ->orderBy('rank_position')
            ->get([
                'rank_position',
                'candidate_source',
                'candidate_authority_id',
                'candidate_fuseki_uri',
                'candidate_display_name',
                'name_similarity_score',
                'composite_score',
            ]);

        if (count($rows) === 0) {
            $this->log('  (no candidates)');
            return;
        }
        foreach ($rows as $r) {
            $ref = $r->candidate_authority_id !== null
                ? sprintf('id=%d', $r->candidate_authority_id)
                : sprintf('uri=%s', $r->candidate_fuseki_uri ?: '?');
            $this->log(sprintf(
                '  #%-2d [%s]  score=%s  %s  "%s"',
                $r->rank_position,
                str_pad((string) $r->candidate_source, 12),
                number_format((float) $r->name_similarity_score, 4),
                str_pad($ref, 26),
                $this->truncate((string) $r->candidate_display_name, 80)
            ));
        }
    }

    private function truncate(string $s, int $max): string
    {
        $s = str_replace(["\n", "\r"], ' ', $s);
        if (mb_strlen($s, 'UTF-8') <= $max) {
            return $s;
        }
        return mb_substr($s, 0, $max - 3, 'UTF-8') . '...';
    }
}
