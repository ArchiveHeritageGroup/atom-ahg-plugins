<?php
/**
 * PSIS / AtoM-AHG - ChainStore implementation backed by the ai_inference_log table.
 *
 * The on-disk schema is identical to Heratio's so receipts can be cross-
 * verified. This implementation uses the Illuminate Capsule DB layer that
 * atom-framework already exposes (see ahgAIPlugin/lib/Services for prior art);
 * no Propel ORM dependency for the new tables - they are accessed via the
 * query builder against raw column names.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

use AhgInferenceReceipts\Receipt;
use AhgInferenceReceipts\Storage\ChainStore;
use Illuminate\Database\Capsule\Manager as DB;

final class PropelChainStore implements ChainStore
{
    private string $table = 'ai_inference_log';

    public function append(Receipt $receipt): void
    {
        $payload = $receipt->payload;
        $table   = $this->table;

        DB::connection()->transaction(function () use ($receipt, $payload, $table): void {
            $existingHead = DB::table($table)
                ->orderByDesc('seq')
                ->lockForUpdate()
                ->first(['seq']);

            $expectedSeq = $existingHead === null ? 0 : ((int) $existingHead->seq) + 1;
            if ($receipt->seq !== $expectedSeq) {
                throw new RuntimeException(sprintf(
                    'PropelChainStore: expected seq %d, got %d',
                    $expectedSeq,
                    $receipt->seq
                ));
            }

            DB::table($table)->insert([
                'seq'                => $receipt->seq,
                'ts'                 => $receipt->ts,
                'prev_hash'          => $receipt->prevHash,
                'entry_hash'         => $receipt->entryHash,
                'signature'          => $receipt->signature,
                'kid'                => $receipt->kid,
                'v'                  => $receipt->version,
                'alg'                => $receipt->alg,
                'service'            => (string) ($payload['service'] ?? 'unknown'),
                'model_id'           => (string) ($payload['model_id'] ?? 'unknown'),
                'model_version'      => $payload['model_version'] ?? null,
                'input_fingerprint'  => $payload['input_fingerprint'] ?? null,
                'output_fingerprint' => $payload['output_fingerprint'] ?? null,
                'request_id'         => $payload['request_id'] ?? null,
                'user_id'            => $payload['user_id'] ?? null,
                'tenant_id'          => $payload['tenant_id'] ?? null,
                'latency_ms'         => $payload['latency_ms'] ?? null,
                'tokens_in'          => $payload['tokens_in'] ?? null,
                'tokens_out'         => $payload['tokens_out'] ?? null,
                'payload_json'       => json_encode(
                    $payload,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ),
                'created_at'         => (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                    ->format('Y-m-d H:i:s.v'),
            ]);
        });
    }

    public function head(): ?Receipt
    {
        $row = DB::table($this->table)
            ->orderByDesc('seq')
            ->first();
        return $row === null ? null : $this->rowToReceipt($row);
    }

    public function count(): int
    {
        return (int) DB::table($this->table)->count();
    }

    public function range(int $fromSeq = 0, ?int $toSeq = null): iterable
    {
        $query = DB::table($this->table)
            ->where('seq', '>=', $fromSeq)
            ->orderBy('seq');
        if ($toSeq !== null) {
            $query->where('seq', '<=', $toSeq);
        }

        foreach ($query->cursor() as $row) {
            yield $this->rowToReceipt($row);
        }
    }

    private function rowToReceipt(object $row): Receipt
    {
        $tsRaw = (string) $row->ts;
        $ts    = new DateTimeImmutable($tsRaw, new DateTimeZone('UTC'));

        $payload = [];
        if (!empty($row->payload_json)) {
            $decoded = json_decode((string) $row->payload_json, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return new Receipt(
            seq:       (int) $row->seq,
            ts:        $ts->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.v\Z'),
            prevHash:  (string) $row->prev_hash,
            payload:   $payload,
            kid:       (string) $row->kid,
            entryHash: (string) $row->entry_hash,
            signature: (string) $row->signature,
            version:   (int) ($row->v ?? 1),
            alg:       (string) ($row->alg ?? 'ed25519'),
        );
    }
}
