<?php

/**
 * OCFL configuration defaults (documentation reference).
 *
 * Live values are resolved at runtime by OcflService from the ahg_settings
 * table (group "ocfl") with the defaults below. This file documents the
 * available keys; it is NOT loaded automatically (AtoM has no Laravel
 * config() resolver). To override, set the matching ahg_settings keys via
 * Admin > AHG Settings or AhgSettingsService::set().
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */

return [
    // Absolute path to the OCFL storage root. Defaults to <sf_root_dir>/ocfl.
    // Point at NAS-backed storage in production, e.g. /mnt/nas/heratio/ocfl.
    'ocfl_storage_root' => null,

    // Object-root layout: 'flat-id' (default, < ~10k objects), 'pairtree'
    // (two-char pairs), or 'hashed-n-tuple' (sha256-based 3x3, millions).
    'ocfl_storage_layout' => 'flat-id',

    // Default digest algorithm for new objects. OCFL v1.1 §6.1 allows
    // 'sha512' (recommended) or 'sha256'.
    'ocfl_digest_algorithm' => 'sha512',

    // Where ocfl:export writes tarballs. Defaults to <sf_root_dir>/cache/ocfl-exports.
    'ocfl_export_path' => null,
];
