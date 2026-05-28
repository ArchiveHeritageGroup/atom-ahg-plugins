<?php
/**
 * PSIS / AtoM-AHG - resolves an Ed25519 kid to its public-key bytes via ai_inference_key.
 *
 * Symfony 1.4 / PHP 8.3 port of Heratio's AhgAiCompliance\Services\KeyResolver.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

final class KeyResolver
{
    private const TABLE = 'ai_inference_key';

    /** @var array<string,string> in-memory cache keyed by kid */
    private array $cache = [];

    /**
     * Return the raw 32-byte Ed25519 public key for a kid, or null if not registered.
     */
    public function publicKey(string $kid): ?string
    {
        if (array_key_exists($kid, $this->cache)) {
            return $this->cache[$kid];
        }

        $row = DB::table(self::TABLE)
            ->where('kid', $kid)
            ->first(['public_key']);
        if ($row === null) {
            return null;
        }

        $bytes = (string) $row->public_key;
        $this->cache[$kid] = $bytes;
        return $bytes;
    }

    /**
     * Currently-active kid, or null if no key is installed.
     */
    public function activeKid(): ?string
    {
        $row = DB::table(self::TABLE)
            ->where('active', 1)
            ->orderByDesc('id')
            ->first(['kid']);
        return $row === null ? null : (string) $row->kid;
    }

    /**
     * Register or update a key row. If $active=true, demote any other active row first
     * and stamp its rotated_at.
     */
    public function register(string $kid, string $publicKeyBytes, bool $active): void
    {
        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        if ($active) {
            DB::table(self::TABLE)
                ->where('active', 1)
                ->update([
                    'active'     => 0,
                    'rotated_at' => $now,
                ]);
        }

        $existing = DB::table(self::TABLE)->where('kid', $kid)->first(['id']);
        if ($existing !== null) {
            DB::table(self::TABLE)
                ->where('kid', $kid)
                ->update([
                    'public_key' => $publicKeyBytes,
                    'alg'        => 'ed25519',
                    'active'     => $active ? 1 : 0,
                ]);
        } else {
            DB::table(self::TABLE)->insert([
                'kid'        => $kid,
                'public_key' => $publicKeyBytes,
                'alg'        => 'ed25519',
                'active'     => $active ? 1 : 0,
                'created_at' => $now,
            ]);
        }

        $this->cache[$kid] = $publicKeyBytes;
    }

    /**
     * Closure compatible with the ReceiptChain publicKeyResolver contract.
     * Captures $this so verification can pull keys lazily.
     */
    public function asCallable(): callable
    {
        return function (string $kid): ?string {
            return $this->publicKey($kid);
        };
    }
}
