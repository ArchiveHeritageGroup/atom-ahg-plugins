<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Valuation approved -> propose a revaluation of the heritage asset.
 *
 * The first handler, and the worked example for the rest. A curator approving a
 * valuation is not the same act as an accountant recognising it, so this writes
 * nothing to the accounts. It states what it would write, and the GRAP screen is
 * where someone accountable for the figure accepts it.
 *
 * WHERE THIS WRITES, AND WHY NOT ELSEWHERE
 *
 * On acceptance it calls HeritageAssetService::addValuation(), which is the only
 * working writer in the accounting plugin. The Spectrum-side GRAP tables look
 * like the right home and are not: spectrum_grap_revaluation_history,
 * spectrum_grap_journal and spectrum_grap_depreciation_schedule have no code
 * reading or writing them at all, and grap_heritage_asset is a placeholder view
 * that returns literal 1s.
 *
 * KNOWN LIMIT, INHERITED
 *
 * addValuation() is a flat overwrite: it sets current_carrying_amount to the new
 * figure and writes heritage_valuation_history. It does NOT write the revaluation
 * surplus split, a journal entry, or a movement-register row - all three of which
 * GRAP 103.74(e) and IPSAS 45.88(e) require for the movement schedule. Accepting
 * a proposal therefore produces exactly the record the manual form produces, no
 * better. That is a pre-existing gap in the accounting plugin, and it is why the
 * result note below says "carrying amount updated" rather than "posted".
 */
class HeritageRevaluationOutcome implements SpectrumOutcomeHandler
{
    public function label(): string
    {
        return 'Revaluation of heritage asset';
    }

    public function propose(string $procedureType, int $recordId): ?array
    {
        $valuation = self::currentValuation($recordId);

        if (!$valuation) {
            // Nothing recorded. Say nothing rather than invent a figure.
            return null;
        }

        $asset = self::assetFor($recordId);

        if (!$asset) {
            return null;
        }

        $amount = (float) $valuation->valuation_amount;
        $previous = (float) ($asset->current_carrying_amount ?? 0);

        return [
            'summary' => sprintf(
                '%s %s (was %s) - %s',
                $valuation->valuation_currency ?: $valuation->currency ?: '',
                number_format($amount, 2),
                number_format($previous, 2),
                $valuation->valuer_name ?: ($valuation->valuer ?: 'valuer not recorded')
            ),
            'payload' => [
                'heritage_asset_id' => (int) $asset->id,
                'valuation_id' => (int) $valuation->id,
                'previous_carrying_amount' => $previous,
                'new_value' => $amount,
                'currency' => $valuation->valuation_currency ?: $valuation->currency,
                'valuation_date' => $valuation->valuation_date,
                'valuation_method' => $valuation->valuation_type,
                'valuer_name' => $valuation->valuer_name ?: $valuation->valuer,
                'valuer_organization' => $valuation->valuer_organization,
                'valuation_report_reference' => $valuation->valuation_reference,
                'notes' => $valuation->valuation_note,
            ],
        ];
    }

    public function apply(array $payload, int $recordId, ?int $userId): string
    {
        if (!class_exists('HeritageAssetService')) {
            throw new RuntimeException('ahgHeritageAccountingPlugin is not enabled, so there is nowhere to record this.');
        }

        $assetId = (int) ($payload['heritage_asset_id'] ?? 0);

        if ($assetId <= 0) {
            throw new RuntimeException('The proposal does not name a heritage asset.');
        }

        $service = new HeritageAssetService();

        $service->addValuation($assetId, [
            'valuation_date' => $payload['valuation_date'] ?? date('Y-m-d'),
            'new_value' => $payload['new_value'],
            'valuation_method' => $payload['valuation_method'] ?? null,
            'valuer_name' => $payload['valuer_name'] ?? null,
            'valuer_organization' => $payload['valuer_organization'] ?? null,
            'valuation_report_reference' => $payload['valuation_report_reference'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'created_by' => $userId,
        ]);

        return sprintf(
            'Carrying amount updated to %s and a valuation history row written. '
            .'Revaluation surplus and journal entries are not produced by the accounting plugin.',
            number_format((float) $payload['new_value'], 2)
        );
    }

    /**
     * The valuation this record is currently working from.
     *
     * Prefers the row flagged current; falls back to the most recent, because
     * is_current has no writer yet and would otherwise make this always null.
     */
    private static function currentValuation(int $recordId): ?object
    {
        $base = DB::table('spectrum_valuation')->where('object_id', $recordId);

        $current = (clone $base)->where('is_current', 1)->orderByDesc('valuation_date')->first();

        return $current ?: $base->orderByDesc('valuation_date')->orderByDesc('id')->first();
    }

    /**
     * The heritage asset for an information object.
     *
     * Matched on information_object_id. HeritageAssetService::getAssetByObjectId()
     * matches on ha.object_id only, and that column is null throughout this
     * install, so it would find nothing.
     */
    private static function assetFor(int $recordId): ?object
    {
        return DB::table('heritage_asset')
            ->where('information_object_id', $recordId)
            ->orderByDesc('id')
            ->first();
    }
}
