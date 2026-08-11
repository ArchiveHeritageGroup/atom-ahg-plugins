<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Outcomes: what a procedure produces when it reaches a state.
 *
 * Declared per procedure in spectrum_workflow_config, so a flow states what it
 * produces rather than the code knowing about each flow:
 *
 *     "outcomes": [
 *       { "on_state": "approved", "handler": "heritage_revaluation" }
 *     ]
 *
 * A procedure with no outcomes behaves exactly as it did before.
 *
 * Nothing here writes a destination. Reaching the state records a PROPOSAL;
 * a human accepting it is what writes. That split exists because approving a
 * valuation and recognising it in the accounts are different acts by different
 * people, and the same is true of derecognising a disposal or setting an insured
 * value.
 */
class SpectrumOutcomeService
{
    /**
     * Handler key => class, and the plugin that owns its destination.
     *
     * A handler is registered only when the plugin it writes to is enabled.
     * That is the gate: on a Spectrum-only install `heritage_revaluation` does
     * not exist, so nothing tries to reach an accounting table that is not
     * there.
     *
     * Deliberately not "call it and catch the failure". Spectrum's index action
     * already wraps its GRAP read in `catch { // Table may not exist }`, and a
     * swallowed exception is indistinguishable from a feature that is switched
     * off - which is the shape of several bugs in this codebase. An absent
     * handler is reported as absent.
     */
    private const HANDLER_MAP = [
        'heritage_revaluation' => [
            'class' => 'HeritageRevaluationOutcome',
            'requires_plugin' => 'ahgHeritageAccountingPlugin',
        ],
    ];

    /**
     * Is the plugin that owns a handler's destination actually enabled?
     */
    public static function pluginEnabled(string $plugin): bool
    {
        try {
            $configuration = sfProjectConfiguration::getActive();

            return $configuration && in_array($plugin, $configuration->getPlugins(), true);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Handler keys available on this install.
     *
     * @return array<int, string>
     */
    public static function available(): array
    {
        $keys = [];

        foreach (self::HANDLER_MAP as $key => $spec) {
            if (self::pluginEnabled($spec['requires_plugin']) && class_exists($spec['class'])) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public static function handler(string $key): ?SpectrumOutcomeHandler
    {
        $spec = self::HANDLER_MAP[$key] ?? null;

        if (!$spec || !self::pluginEnabled($spec['requires_plugin']) || !class_exists($spec['class'])) {
            return null;
        }

        $instance = new $spec['class']();

        return $instance instanceof SpectrumOutcomeHandler ? $instance : null;
    }

    /**
     * Outcomes a procedure declares for a state.
     *
     * @return array<int, array>
     */
    public static function forState(string $procedureType, string $state): array
    {
        $row = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();

        if (!$row) {
            return [];
        }

        $config = json_decode((string) $row->config_json, true);

        if (!is_array($config) || empty($config['outcomes'])) {
            return [];
        }

        return array_values(array_filter(
            $config['outcomes'],
            static fn ($o) => is_array($o) && ($o['on_state'] ?? null) === $state
        ));
    }

    /**
     * Fire any outcomes for the state just entered.
     *
     * Called after the transition has committed, never inside it. A handler that
     * throws is recorded and swallowed: an outcome is a consequence of the
     * transition, and a broken consequence must not undo the fact that the
     * transition happened.
     *
     * @return int proposals raised
     */
    public static function onStateEntered(
        string $procedureType,
        int $recordId,
        string $state,
        ?int $userId = null
    ): int {
        $raised = 0;

        foreach (self::forState($procedureType, $state) as $outcome) {
            $key = (string) ($outcome['handler'] ?? '');
            $handler = self::handler($key);

            if (!$handler) {
                // Normal on an install without the destination plugin. Logged so
                // a genuine misconfiguration is still visible, and worded so the
                // two cases can be told apart.
                $known = array_key_exists($key, self::HANDLER_MAP);
                error_log(sprintf(
                    'SpectrumOutcome: handler "%s" for %s is %s',
                    $key,
                    $procedureType,
                    $known ? 'declared but its plugin is not enabled' : 'not a known handler'
                ));

                continue;
            }

            try {
                if (!empty($outcome['requires_evidence'])
                    && class_exists('SpectrumEvidenceService')
                    && 0 === SpectrumEvidenceService::countFor($procedureType, $recordId)) {
                    error_log(sprintf(
                        'SpectrumOutcome: %s/%s reached %s with no evidence attached; proposal not raised',
                        $procedureType, $recordId, $state
                    ));

                    continue;
                }

                $proposed = $handler->propose($procedureType, $recordId);

                if (null === $proposed) {
                    continue;
                }

                // One live proposal per handler per record. An earlier pending
                // one is superseded rather than left to be accepted twice.
                DB::table('spectrum_outcome_proposal')
                    ->where('procedure_type', $procedureType)
                    ->where('record_id', $recordId)
                    ->where('handler', $key)
                    ->where('status', 'pending')
                    ->update(['status' => 'superseded', 'decided_at' => date('Y-m-d H:i:s')]);

                DB::table('spectrum_outcome_proposal')->insert([
                    'procedure_type' => $procedureType,
                    'record_id' => $recordId,
                    'handler' => $key,
                    'payload' => json_encode($proposed['payload']),
                    'summary' => mb_substr((string) $proposed['summary'], 0, 255),
                    'status' => 'pending',
                    'proposed_by' => $userId,
                    'proposed_at' => date('Y-m-d H:i:s'),
                ]);

                ++$raised;
            } catch (Throwable $e) {
                error_log(sprintf('SpectrumOutcome %s failed for %s/%s: %s',
                    $key, $procedureType, $recordId, $e->getMessage()));
            }
        }

        return $raised;
    }

    /**
     * @return array<int, object>
     */
    public static function pending(?string $procedureType = null, ?int $recordId = null): array
    {
        $q = DB::table('spectrum_outcome_proposal')->where('status', 'pending');

        if (null !== $procedureType) {
            $q->where('procedure_type', $procedureType);
        }

        if (null !== $recordId) {
            $q->where('record_id', $recordId);
        }

        return $q->orderByDesc('proposed_at')->get()->all();
    }

    public static function get(int $id): ?object
    {
        return DB::table('spectrum_outcome_proposal')->where('id', $id)->first();
    }

    /**
     * Accept a proposal - this is the point at which anything is written.
     */
    public static function accept(int $id, ?int $userId, ?string $note = null): bool
    {
        $proposal = self::get($id);

        if (!$proposal || 'pending' !== $proposal->status) {
            return false;
        }

        $handler = self::handler((string) $proposal->handler);

        if (!$handler) {
            return false;
        }

        $payload = json_decode((string) $proposal->payload, true) ?: [];

        try {
            $result = $handler->apply($payload, (int) $proposal->record_id, $userId);
        } catch (Throwable $e) {
            DB::table('spectrum_outcome_proposal')->where('id', $id)->update([
                'status' => 'failed',
                'decided_by' => $userId,
                'decided_at' => date('Y-m-d H:i:s'),
                'decision_note' => $note,
                'result_note' => $e->getMessage(),
            ]);

            return false;
        }

        DB::table('spectrum_outcome_proposal')->where('id', $id)->update([
            'status' => 'accepted',
            'decided_by' => $userId,
            'decided_at' => date('Y-m-d H:i:s'),
            'decision_note' => $note,
            'result_note' => $result,
        ]);

        return true;
    }

    public static function reject(int $id, ?int $userId, ?string $note = null): bool
    {
        $proposal = self::get($id);

        if (!$proposal || 'pending' !== $proposal->status) {
            return false;
        }

        return DB::table('spectrum_outcome_proposal')->where('id', $id)->update([
            'status' => 'rejected',
            'decided_by' => $userId,
            'decided_at' => date('Y-m-d H:i:s'),
            'decision_note' => $note,
        ]) > 0;
    }
}
