<?php

/**
 * authResWriteProvenanceTask — Symfony 1.4 task for AtoM Heratio
 *
 * Demo task for Task 8 (Decision provenance to Fuseki). Writes RDF-Star
 * provenance for an existing ahg_mention_decision, or creates a simulated
 * "link" decision against a mention then writes its provenance.
 *
 * Usage:
 *   php symfony auth-res:write-provenance <decision_id> [--show]
 *   php symfony auth-res:write-provenance --simulate-link=<mention_id> [--actor-id=N] [--show]
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

// Explicit requires: Symfony 1.4 has no PSR-4 autoloader for our namespaced
// plugin classes. atom-framework boots Capsule but not our lib/Services/ tree.
require_once __DIR__ . '/../Services/FusekiUpdateService.php';
require_once __DIR__ . '/../Services/DecisionProvenanceWriter.php';

use AtomFramework\Services\AuthorityResolution\DecisionProvenanceWriter;
use AtomFramework\Services\AuthorityResolution\FusekiUpdateService;
use Illuminate\Database\Capsule\Manager as DB;

class authResWriteProvenanceTask extends arBaseTask
{
    protected function configure()
    {
        $this->addArguments(array(
            new sfCommandArgument('decision_id', sfCommandArgument::OPTIONAL, 'Existing ahg_mention_decision.id'),
        ));
        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('simulate-link', null, sfCommandOption::PARAMETER_REQUIRED, 'Mock-link this mention_id to a similar-named actor, then write provenance'),
            new sfCommandOption('actor-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Override the actor chosen by --simulate-link'),
            new sfCommandOption('show', null, sfCommandOption::PARAMETER_NONE, 'Print the SPARQL UPDATE body and status'),
        ));

        $this->namespace = 'auth-res';
        $this->name = 'write-provenance';
        $this->briefDescription = 'Write RDF-Star provenance for an authority-resolution decision to Fuseki.';
        $this->detailedDescription = <<<EOF
Demonstrates the Task 8 RDF-Star provenance write path. Either operates on
an existing ahg_mention_decision row (pass its id), or creates a simulated
"link" decision against a mention (--simulate-link=mention_id) and writes
that — used before Task 5 (review UI) ships to produce real decisions.

  php symfony auth-res:write-provenance 12
  php symfony auth-res:write-provenance --simulate-link=998 --show
  php symfony auth-res:write-provenance --simulate-link=998 --actor-id=42
EOF;
    }

    public function execute($arguments = array(), $options = array())
    {
        parent::execute($arguments, $options);

        $simulateMentionId = $options['simulate-link'] ?? null;

        if ($simulateMentionId) {
            $decisionId = $this->createSimulatedLinkDecision(
                (int) $simulateMentionId,
                isset($options['actor-id']) && $options['actor-id'] !== null ? (int) $options['actor-id'] : null
            );
            if ($decisionId === null) {
                return 1;
            }
            $this->log(sprintf('Simulated link decision #%d inserted into ahg_mention_decision.', $decisionId));
        } else {
            $decisionId = isset($arguments['decision_id']) ? (int) $arguments['decision_id'] : 0;
            if ($decisionId <= 0) {
                $this->logSection('auth-res', 'Provide a decision_id argument or --simulate-link=mention_id.', null, 'ERROR');
                return 1;
            }
        }

        $writer = new DecisionProvenanceWriter(new FusekiUpdateService());
        $result = $writer->write($decisionId);

        if (!empty($options['show'])) {
            $this->log('');
            $this->log('--- turtle body (inside INSERT DATA / GRAPH wrapper) ---');
            $this->log($result['turtle'] ?? '(no turtle returned)');
            $this->log('--- end turtle ---');
            $this->log('');
            $this->log('  graph_uri  : ' . ($result['graph'] ?? '(default)'));
            $this->log('  http_status: ' . ($result['status'] ?? '?'));
        }

        if (empty($result['ok'])) {
            $this->logSection('auth-res', 'Fuseki write FAILED: ' . ($result['error'] ?? 'unknown'), null, 'ERROR');
            return 1;
        }

        $this->log(sprintf('Provenance written for decision #%d. Graph: %s', $decisionId, $result['graph'] ?? '?'));
        return 0;
    }

    private function createSimulatedLinkDecision(int $mentionId, ?int $forceActorId): ?int
    {
        $mention = DB::table('ahg_mention as m')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->where('m.id', $mentionId)
            ->first(['m.id', 'm.entity_type', 'n.entity_value']);
        if (!$mention) {
            $this->logSection('auth-res', sprintf('Mention #%d not found.', $mentionId), null, 'ERROR');
            return null;
        }
        $actorId = $forceActorId ?? $this->pickClosestActor((string) $mention->entity_value, (string) $mention->entity_type);
        if ($actorId === null) {
            $this->logSection('auth-res', sprintf(
                "Couldn't find a candidate actor for entity_value='%s', entity_type='%s'. Use --actor-id=N.",
                $mention->entity_value, $mention->entity_type
            ), null, 'ERROR');
            return null;
        }
        $userId = (int) DB::table('user')->orderBy('id')->value('id') ?? 1;

        return DB::table('ahg_mention_decision')->insertGetId([
            'mention_id' => $mention->id,
            'decision_type' => 'link',
            'chosen_candidate_id' => null,
            'chosen_authority_id' => $actorId,
            'original_system_top_score' => 0.95,
            'archivist_user_id' => $userId,
            'decided_at' => date('Y-m-d H:i:s'),
            'candidates_visible_snapshot' => null,
            'evidence_snapshot' => null,
            'fuseki_graph_uri' => null,
        ]);
    }

    private function pickClosestActor(string $value, string $entityType): ?int
    {
        $entityTypeId = $entityType === 'PERSON' ? 132 : ($entityType === 'ORG' ? 131 : null);
        if ($entityTypeId === null) {
            return null;
        }
        $row = DB::table('actor as a')
            ->join('actor_i18n as ai', 'ai.id', '=', 'a.id')
            ->where('a.entity_type_id', $entityTypeId)
            ->where('ai.authorized_form_of_name', 'like', '%' . $value . '%')
            ->orderByRaw('LENGTH(ai.authorized_form_of_name)')
            ->limit(1)
            ->first(['a.id']);
        if ($row) {
            return (int) $row->id;
        }
        $row = DB::table('actor')->where('entity_type_id', $entityTypeId)->orderBy('id')->limit(1)->first(['id']);
        return $row ? (int) $row->id : null;
    }
}
