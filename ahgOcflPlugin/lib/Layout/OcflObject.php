<?php

/**
 * OcflObject - one OCFL archival object (one logical archival item).
 *
 * Carries the OCFL object id (matches inventory.id), the current Inventory,
 * and a content tree (logical path -> local file path) for the in-flight
 * version being staged. New versions are staged via stageContent() and
 * persisted by StorageRoot.
 *
 * Ported from the Heratio ahg-ocfl package.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */

namespace AtomExtensions\Ocfl\Layout;

final class OcflObject
{
    public string $id;
    public Inventory $inventory;
    /** @var array<string, string> logical path => local file path */
    public array $stagedContent;

    public function __construct(string $id, Inventory $inventory, array $stagedContent = [])
    {
        $this->id = $id;
        $this->inventory = $inventory;
        $this->stagedContent = $stagedContent;
    }

    public static function fresh(string $id, string $digestAlgorithm = 'sha512'): self
    {
        // Empty placeholder inventory - completed at commit time when
        // StorageRoot calls Inventory::initial(...) with the real v1.
        return new self(
            $id,
            new Inventory(
                $id,
                'v1',
                ['__placeholder__' => []],
                ['v1' => Version::now([], 'placeholder')],
                $digestAlgorithm
            ),
            []
        );
    }

    public function stageContent(string $logicalPath, string $localFilePath): void
    {
        $clean = ltrim(str_replace(['../', '..\\'], '', $logicalPath), '/');
        $this->stagedContent[$clean] = $localFilePath;
    }
}
