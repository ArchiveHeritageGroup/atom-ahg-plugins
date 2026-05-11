<?php

namespace AhgShareLink\Services;

/**
 * AccessResult — the outcome of an access-token validation.
 *
 * On success: `allowed=true`, `tokenRow` and `action` populated, `httpStatus=200`.
 * On denial: `allowed=false`, `reason` and `httpStatus=410` populated.
 *
 * The middleware decides what to do with this; the controller renders it.
 *
 * @phase D
 */
final class AccessResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly ?object $tokenRow,
        public readonly string $action,        // view | denied_expired | denied_revoked | denied_quota | denied_unknown
        public readonly ?string $reason,
        public readonly int $httpStatus,
    ) {
    }

    public static function allow(object $tokenRow, string $action = 'view'): self
    {
        return new self(true, $tokenRow, $action, null, 200);
    }

    public static function deny(?object $tokenRow, string $action, string $reason): self
    {
        return new self(false, $tokenRow, $action, $reason, 410);
    }

    public static function notFound(): self
    {
        return new self(false, null, 'denied_unknown', 'Share link not found.', 410);
    }
}
