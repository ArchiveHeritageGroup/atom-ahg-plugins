<?php

/**
 * StorageRoot - an OCFL v1.1 storage root.
 *
 * Responsibilities:
 *   - initialise the root with `0=ocfl_1.1` namaste + `ocfl_layout.json`
 *   - resolve an object id to its on-disk path via StorageLayout
 *   - read / write inventory.json + content files for an object
 *   - verify fixity for one or all objects
 *
 * Operates over the OcflStorageAdapter so the OCFL layer stays decoupled
 * from the concrete filesystem.
 *
 * Ported from the Heratio ahg-ocfl package. The embedded-metadata extension
 * hook present in Heratio is omitted here (it belongs to a separate Heratio
 * subsystem); the generic Inventory::withExtension() capability is retained
 * for forward compatibility.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */

namespace AtomExtensions\Ocfl\Layout;

use AtomExtensions\Ocfl\Storage\OcflStorageAdapter;
use RuntimeException;

final class StorageRoot
{
    public const NAMASTE_FILE = '0=ocfl_1.1';
    public const NAMASTE_CONTENT = "ocfl_1.1\n";
    public const LAYOUT_FILE = 'ocfl_layout.json';

    public OcflStorageAdapter $adapter;
    public StorageLayout $layout;
    public ContentAddressing $digester;

    public function __construct(
        OcflStorageAdapter $adapter,
        string $layout = StorageLayout::FLAT_ID,
        string $digestAlgorithm = ContentAddressing::ALG_SHA512
    ) {
        $this->adapter = $adapter;
        $this->layout = new StorageLayout($layout);
        $this->digester = new ContentAddressing($digestAlgorithm);
    }

    public function isInitialized(): bool
    {
        return $this->adapter->exists(self::NAMASTE_FILE);
    }

    public function initialize(): void
    {
        $this->adapter->put(self::NAMASTE_FILE, self::NAMASTE_CONTENT);

        $layoutDescriptor = $this->layout->descriptor();
        $this->adapter->put(
            self::LAYOUT_FILE,
            json_encode(
                $layoutDescriptor,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ) . "\n"
        );
    }

    /** List object ids by walking the root for object-root inventory.json files. */
    public function list(): array
    {
        $ids = [];
        $seen = [];
        foreach ($this->adapter->files('') as $path) {
            if (!str_ends_with($path, '/inventory.json') && 'inventory.json' !== $path) {
                continue;
            }
            // Skip per-version snapshot inventories (vN/inventory.json).
            if (1 === preg_match('#/v\d+/inventory\.json$#', $path)) {
                continue;
            }
            try {
                $inv = Inventory::fromJson($this->adapter->get($path));
                if (!isset($seen[$inv->id])) {
                    $ids[] = $inv->id;
                    $seen[$inv->id] = true;
                }
            } catch (\Throwable $e) {
                // Skip malformed inventories - verify() flags them.
            }
        }
        sort($ids, SORT_STRING);

        return $ids;
    }

    public function exists(string $objectId): bool
    {
        return $this->adapter->exists($this->objectRoot($objectId) . '/inventory.json');
    }

    public function read(string $objectId): OcflObject
    {
        $invPath = $this->objectRoot($objectId) . '/inventory.json';
        if (!$this->adapter->exists($invPath)) {
            throw new RuntimeException("OCFL object '{$objectId}' not found at {$invPath}");
        }
        $inv = Inventory::fromJson($this->adapter->get($invPath));

        return new OcflObject($objectId, $inv);
    }

    /**
     * Write an OCFL object (new v1 or new vN) into the storage root.
     *
     * The caller supplies an OcflObject with the object id and stagedContent
     * (logical path -> local file path). Returns the freshly-written
     * inventory (head reflects the new version).
     */
    public function write(
        OcflObject $object,
        string $message,
        ?string $userName,
        ?string $userAddress
    ): Inventory {
        if (!$this->isInitialized()) {
            $this->initialize();
        }

        $root = $this->objectRoot($object->id);

        // Determine whether we're creating v1 or appending vN.
        $existing = $this->adapter->exists($root . '/inventory.json')
            ? Inventory::fromJson($this->adapter->get($root . '/inventory.json'))
            : null;

        $nextVersionId = null === $existing ? 'v1' : $existing->nextVersionId();
        $versionDir = $nextVersionId;

        // Hash each staged file, build state + manifest for THIS version.
        $newManifest = [];
        $state = [];
        foreach ($object->stagedContent as $logicalPath => $localFile) {
            $digest = $this->digester->digestFile($localFile);

            if (!isset($state[$digest])) {
                $state[$digest] = [];
            }
            $state[$digest][] = $logicalPath;

            // Content reuse per OCFL v1.1 §3.5.3.1: skip staging bytes whose
            // digest already exists in the prior inventory's manifest.
            $existingPaths = (null !== $existing && isset($existing->manifest[$digest]))
                ? $existing->manifest[$digest]
                : null;
            if (null === $existingPaths) {
                $contentPath = $this->digester->contentPath($versionDir, $logicalPath);
                $newManifest[$digest] = [$contentPath];
                $this->adapter->putFromFile($root . '/' . $contentPath, $localFile);
            }
        }

        $version = Version::now($state, $message, $userName, $userAddress);

        $inventory = null === $existing
            ? Inventory::initial($object->id, $version, $newManifest, $this->digester->algorithm)
            : $existing->withNewVersion($version, $newManifest);

        // Version directory's own snapshot inventory.json + sidecar (§3.5).
        $invBytes = $inventory->toJson();
        $this->adapter->put($root . '/' . $versionDir . '/inventory.json', $invBytes);
        $this->adapter->put(
            $root . '/' . $versionDir . '/inventory.json.' . $this->digester->algorithm,
            $this->sidecar($invBytes)
        );

        // Canonical (head) inventory + sidecar at the object root.
        $this->adapter->put($root . '/inventory.json', $invBytes);
        $this->adapter->put(
            $root . '/inventory.json.' . $this->digester->algorithm,
            $this->sidecar($invBytes)
        );

        // Object namaste declaration at the object root (idempotent).
        $this->adapter->put($root . '/0=ocfl_object_1.1', "ocfl_object_1.1\n");

        return $inventory;
    }

    /**
     * Verify fixity + basic structure for one object.
     *
     * Returns an array of error strings; empty array means the object is
     * spec-conformant for the checks we run.
     */
    public function verify(string $objectId): array
    {
        $errors = [];
        $root = $this->objectRoot($objectId);

        if (!$this->adapter->exists($root . '/0=ocfl_object_1.1')) {
            $errors[] = "{$objectId}: missing 0=ocfl_object_1.1 namaste";
        }

        $invPath = $root . '/inventory.json';
        if (!$this->adapter->exists($invPath)) {
            $errors[] = "{$objectId}: missing inventory.json";

            return $errors;
        }

        try {
            $bytes = $this->adapter->get($invPath);
            $inv = Inventory::fromJson($bytes);
        } catch (\Throwable $e) {
            return ["{$objectId}: inventory.json unreadable - " . $e->getMessage()];
        }

        // Sidecar fixity for the inventory itself.
        $sidecarPath = $invPath . '.' . $inv->digestAlgorithm;
        if ($this->adapter->exists($sidecarPath)) {
            $expected = trim(explode(' ', $this->adapter->get($sidecarPath))[0] ?? '');
            $actual = $this->digester->digestBytes($bytes);
            if ('' !== $expected && $expected !== $actual) {
                $errors[] = "{$objectId}: inventory.json sidecar mismatch (expected {$expected}, got {$actual})";
            }
        }

        // Per-file fixity from the manifest.
        foreach ($inv->manifest as $digest => $paths) {
            foreach ((array) $paths as $contentPath) {
                $full = $root . '/' . $contentPath;
                if (!$this->adapter->exists($full)) {
                    $errors[] = "{$objectId}: missing content file {$contentPath}";

                    continue;
                }
                $actual = hash($inv->digestAlgorithm, $this->adapter->get($full));
                if ($actual !== $digest) {
                    $errors[] = "{$objectId}: digest mismatch for {$contentPath} (expected {$digest}, got {$actual})";
                }
            }
        }

        return $errors;
    }

    public function objectRoot(string $objectId): string
    {
        return $this->layout->pathFor($objectId);
    }

    /** OCFL sidecar format: `<digest> inventory.json` followed by newline. */
    private function sidecar(string $bytes): string
    {
        return $this->digester->digestBytes($bytes) . ' inventory.json' . "\n";
    }
}
