<?php

/**
 * Inventory - OCFL v1.1 inventory.json representation.
 *
 * Per OCFL v1.1 §3.5, inventory.json carries:
 *   - id                 (string, unique within storage root)
 *   - type               URI of the inventory schema
 *   - digestAlgorithm    "sha512" (default) or "sha256"
 *   - head               highest version id, e.g. "v3"
 *   - manifest           digest -> [content path(s)]
 *   - versions           "vN" => Version-shaped object
 *
 * Two implementations producing the same logical state MUST produce the
 * same inventory.json bytes; this class enforces that by sorting manifest
 * digest keys, sorting state digest keys per version, and serialising with
 * JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE.
 *
 * Ported from the Heratio ahg-ocfl package.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */

namespace AtomExtensions\Ocfl\Layout;

use InvalidArgumentException;

final class Inventory
{
    public const TYPE_URI = 'https://ocfl.io/1.1/spec/#inventory';

    public string $id;
    public string $head;
    /** @var array<string, array<int, string>> digest => [content paths] */
    public array $manifest;
    /** @var array<string, Version> "vN" => Version */
    public array $versions;
    public string $digestAlgorithm;
    public string $type;
    /** @var array<string, array<string, mixed>> extension name => block payload */
    public array $extensions;

    public function __construct(
        string $id,
        string $head,
        array $manifest,
        array $versions,
        string $digestAlgorithm = 'sha512',
        string $type = self::TYPE_URI,
        array $extensions = []
    ) {
        if ('' === $id) {
            throw new InvalidArgumentException('Inventory: id cannot be empty');
        }
        if (!preg_match('/^v\d+$/', $head)) {
            throw new InvalidArgumentException("Inventory: head '{$head}' is not vN");
        }
        if (!isset($versions[$head])) {
            throw new InvalidArgumentException("Inventory: head '{$head}' missing from versions");
        }
        $this->id = $id;
        $this->head = $head;
        $this->manifest = $manifest;
        $this->versions = $versions;
        $this->digestAlgorithm = $digestAlgorithm;
        $this->type = $type;
        $this->extensions = $extensions;
    }

    /** Build a Version-1 inventory from scratch. */
    public static function initial(
        string $id,
        Version $v1,
        array $manifest,
        string $digestAlgorithm = 'sha512'
    ): self {
        return new self($id, 'v1', $manifest, ['v1' => $v1], $digestAlgorithm);
    }

    /** Add a new version, returning a fresh inventory (immutability). */
    public function withNewVersion(Version $version, array $manifest): self
    {
        $next = $this->nextVersionId();
        $vs = $this->versions;
        $vs[$next] = $version;

        // Merge manifests deterministically - existing entries win for
        // identical digests (the spec requires content reuse across
        // versions, so the original path is preserved).
        $merged = $this->manifest;
        foreach ($manifest as $digest => $paths) {
            if (!isset($merged[$digest])) {
                $merged[$digest] = $paths;
            }
        }

        return new self(
            $this->id,
            $next,
            $merged,
            $vs,
            $this->digestAlgorithm,
            $this->type,
            $this->extensions
        );
    }

    /**
     * Return a fresh inventory with an OCFL extension block registered /
     * replaced. Passing null/empty clears the named extension.
     */
    public function withExtension(string $name, ?array $payload): self
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Inventory::withExtension: name cannot be empty');
        }
        $ext = $this->extensions;
        if (null === $payload || [] === $payload) {
            unset($ext[$name]);
        } else {
            $ext[$name] = $payload;
        }

        return new self(
            $this->id,
            $this->head,
            $this->manifest,
            $this->versions,
            $this->digestAlgorithm,
            $this->type,
            $ext
        );
    }

    public function nextVersionId(): string
    {
        $max = 0;
        foreach (array_keys($this->versions) as $k) {
            $n = (int) substr((string) $k, 1);
            if ($n > $max) {
                $max = $n;
            }
        }

        return 'v' . ($max + 1);
    }

    /**
     * Deterministic JSON encoding. Keys sorted at every layer; matches the
     * inventory.json examples in the OCFL spec for byte-for-byte stability.
     */
    public function toJson(): string
    {
        $sortedManifest = $this->manifest;
        ksort($sortedManifest, SORT_STRING);

        $versionsOut = [];
        foreach ($this->sortedVersionKeys() as $k) {
            $versionsOut[$k] = $this->versions[$k]->toInventoryArray();
        }

        $payload = [
            'digestAlgorithm' => $this->digestAlgorithm,
            'head' => $this->head,
            'id' => $this->id,
            'manifest' => $sortedManifest,
            'type' => $this->type,
            'versions' => $versionsOut,
        ];

        // OCFL v1.1 §3.7: extensions is an optional top-level object. Only
        // emit the key when at least one extension is registered, so the
        // determinism guarantee against the spec examples holds when no
        // vendor block is present.
        if ([] !== $this->extensions) {
            $sortedExtensions = $this->extensions;
            ksort($sortedExtensions, SORT_STRING);
            $payload['extensions'] = $sortedExtensions;
            ksort($payload, SORT_STRING);
        }

        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        return $json . "\n";
    }

    /** Parse inventory.json bytes back into an Inventory. */
    public static function fromJson(string $bytes): self
    {
        $data = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Inventory: JSON did not decode to an object');
        }

        $versions = [];
        foreach ((array) ($data['versions'] ?? []) as $k => $v) {
            $versions[(string) $k] = Version::fromInventoryArray((array) $v);
        }

        $extensions = [];
        if (isset($data['extensions']) && is_array($data['extensions'])) {
            foreach ($data['extensions'] as $name => $payload) {
                if (is_string($name) && '' !== $name && is_array($payload)) {
                    $extensions[$name] = $payload;
                }
            }
        }

        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['head'] ?? ''),
            (array) ($data['manifest'] ?? []),
            $versions,
            (string) ($data['digestAlgorithm'] ?? 'sha512'),
            (string) ($data['type'] ?? self::TYPE_URI),
            $extensions
        );
    }

    /** "v1", "v2", "v10" -> sorted numerically not lexically. */
    public function sortedVersionKeys(): array
    {
        $keys = array_keys($this->versions);
        usort($keys, function ($a, $b) {
            return ((int) substr((string) $a, 1)) <=> ((int) substr((string) $b, 1));
        });

        return $keys;
    }
}
