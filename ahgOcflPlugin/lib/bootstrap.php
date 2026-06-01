<?php

/**
 * ahgOcflPlugin bootstrap - require_once the namespaced OCFL classes.
 *
 * Symfony 1.x does not autoload namespaced plugin classes, so every entry
 * point (CLI command, controller action) includes this file before using
 * the OCFL layer. Idempotent thanks to require_once.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */

$ocflLibDir = __DIR__;

require_once $ocflLibDir . '/Storage/OcflStorageAdapter.php';
require_once $ocflLibDir . '/Layout/ContentAddressing.php';
require_once $ocflLibDir . '/Layout/StorageLayout.php';
require_once $ocflLibDir . '/Layout/Version.php';
require_once $ocflLibDir . '/Layout/Inventory.php';
require_once $ocflLibDir . '/Layout/OcflObject.php';
require_once $ocflLibDir . '/Layout/StorageRoot.php';
require_once $ocflLibDir . '/Services/OcflService.php';
