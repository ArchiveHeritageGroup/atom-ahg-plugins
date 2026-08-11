<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Conservation completed -> propose updating the object's condition record.
 *
 * The second handler, and the one that shows the mechanism is not about
 * accounting. Nothing here posts a figure: it proposes the condition the object
 * was left in, the date it was assessed, and what future care it needs.
 *
 * WHY THIS IS WORTH HAVING
 *
 * `spectrum_condition_check` holds twenty rows on this install and
 * `heritage_asset.last_condition_assessment` has never been written by anything
 * - zero references in code. A conservator records a treatment in Spectrum and
 * the asset record still says the condition was never assessed. The two plugins
 * hold halves of the same fact.
 *
 * WHAT IT PROPOSES
 *
 * `condition_after` becomes the condition rating, `treatment_end_date` (or the
 * start, if the work is not finished) becomes the assessment date, and
 * `recommendations` becomes the conservation requirement. All three are already
 * captured by the conservation screen and none of them reach the asset today.
 */
class ConservationRecordOutcome implements SpectrumOutcomeHandler
{
    public function label(): string
    {
        return 'Condition and conservation record';
    }

    public function propose(string $procedureType, int $recordId): ?array
    {
        $treatment = DB::table('spectrum_conservation')
            ->where('object_id', $recordId)
            ->orderByDesc('treatment_date')
            ->orderByDesc('id')
            ->first();

        if (!$treatment) {
            // Flow completed with no treatment recorded. Nothing to say.
            return null;
        }

        $asset = DB::table('heritage_asset')
            ->where('information_object_id', $recordId)
            ->orderByDesc('id')
            ->first();

        if (!$asset) {
            return null;
        }

        // condition_rating is varchar(43), so a long free-text condition note
        // would be truncated by MySQL. Cut it here, deliberately and visibly,
        // rather than letting the column do it silently.
        $rating = trim((string) ($treatment->condition_after ?: ''));
        $rating = '' === $rating ? null : mb_substr($rating, 0, 43);

        $assessed = $treatment->treatment_end_date ?: $treatment->treatment_date;

        if (null === $rating && !$assessed) {
            return null;
        }

        return [
            'summary' => sprintf(
                '%s%s - %s',
                $rating ? $rating : 'condition not stated',
                $assessed ? ', assessed '.substr((string) $assessed, 0, 10) : '',
                $treatment->conservator_name ?: 'conservator not recorded'
            ),
            'payload' => [
                'heritage_asset_id' => (int) $asset->id,
                'conservation_id' => (int) $treatment->id,
                'condition_rating' => $rating,
                'last_condition_assessment' => $assessed ? substr((string) $assessed, 0, 10) : null,
                'conservation_requirements' => $treatment->recommendations ?: null,
                'previous_condition_rating' => $asset->condition_rating,
                'previous_assessment' => $asset->last_condition_assessment,
            ],
        ];
    }

    public function apply(array $payload, int $recordId, ?int $userId): string
    {
        $assetId = (int) ($payload['heritage_asset_id'] ?? 0);

        if ($assetId <= 0) {
            throw new RuntimeException('The proposal does not name a heritage asset.');
        }

        $update = ['updated_at' => date('Y-m-d H:i:s')];

        // Only write what the proposal actually carries. A treatment that did not
        // state a condition should not blank one already on the record.
        foreach (['condition_rating', 'last_condition_assessment', 'conservation_requirements'] as $field) {
            if (null !== ($payload[$field] ?? null) && '' !== $payload[$field]) {
                $update[$field] = $payload[$field];
            }
        }

        if (1 === count($update)) {
            throw new RuntimeException('The proposal carries nothing to write.');
        }

        if (null !== $userId) {
            $update['updated_by'] = $userId;
        }

        DB::table('heritage_asset')->where('id', $assetId)->update($update);

        return sprintf(
            'Condition recorded on the asset: %s%s.',
            $payload['condition_rating'] ?: 'no rating given',
            $payload['last_condition_assessment']
                ? ', assessed '.$payload['last_condition_assessment']
                : ''
        );
    }
}
