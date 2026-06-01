<?php

/**
 * Version - one logical OCFL version (v1, v2, ...).
 *
 * Per OCFL v1.1 §3.5 a version carries:
 *   - a state map (digest -> [logical paths])
 *   - the user who created it (name, optional address/URI)
 *   - a free-text message
 *   - an RFC 3339 created timestamp
 *
 * The OCFL inventory.json sorts state digests by key (deterministically),
 * and within each digest preserves the original order of logical paths.
 *
 * Ported from the Heratio ahg-ocfl package.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */

namespace AtomExtensions\Ocfl\Layout;

use DateTimeImmutable;
use DateTimeInterface;

final class Version
{
    public string $created;
    public array $state;
    public string $message;
    public ?string $userName;
    public ?string $userAddress;

    /**
     * @param array<string, array<int, string>> $state digest => logical paths
     */
    public function __construct(
        string $created,
        array $state,
        string $message = '',
        ?string $userName = null,
        ?string $userAddress = null
    ) {
        $this->created = $created;
        $this->state = $state;
        $this->message = $message;
        $this->userName = $userName;
        $this->userAddress = $userAddress;
    }

    public static function now(
        array $state,
        string $message = '',
        ?string $userName = null,
        ?string $userAddress = null
    ): self {
        return new self(
            (new DateTimeImmutable('now'))->format(DateTimeInterface::RFC3339),
            $state,
            $message,
            $userName,
            $userAddress
        );
    }

    /** Build the inventory representation of this version. */
    public function toInventoryArray(): array
    {
        $sortedState = $this->state;
        ksort($sortedState, SORT_STRING);

        $out = [
            'created' => $this->created,
            'message' => $this->message,
            'state' => $sortedState,
        ];

        $user = [];
        if (null !== $this->userName && '' !== $this->userName) {
            $user['name'] = $this->userName;
        }
        if (null !== $this->userAddress && '' !== $this->userAddress) {
            $user['address'] = $this->userAddress;
        }
        if ([] !== $user) {
            $out['user'] = $user;
        }

        return $out;
    }

    /** Reconstruct from an inventory.json fragment. */
    public static function fromInventoryArray(array $data): self
    {
        $state = $data['state'] ?? [];
        if (!is_array($state)) {
            $state = [];
        }

        return new self(
            (string) ($data['created'] ?? ''),
            $state,
            (string) ($data['message'] ?? ''),
            isset($data['user']['name']) ? (string) $data['user']['name'] : null,
            isset($data['user']['address']) ? (string) $data['user']['address'] : null
        );
    }
}
