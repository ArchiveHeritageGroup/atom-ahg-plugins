<?php

/**
 * EvidenceSignal - signal enum for AtoM Heratio
 *
 * Class constants representing the four possible per-dimension signal
 * outcomes for the AHG Authority Resolution Engine. Shared semantics
 * with the Laravel-side Heratio implementation so the composite-score
 * formula yields the same number on either platform.
 *
 *   MATCH    - evidence supports this candidate
 *   CONFLICT - evidence contradicts this candidate
 *   SILENT   - the dimension has data on the authority side, but no
 *              corresponding signal could be derived from the mention
 *              context (rare but distinct from "absent")
 *   ABSENT   - the dimension has no data to evaluate (no event dates
 *              on the actor, no nearby_places on the context, etc.)
 *
 * Composite-score deltas:
 *   match    +0.10
 *   conflict -0.30
 *   silent   +0.00
 *   absent   +0.00
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution\Evidence;

class EvidenceSignal
{
    const MATCH = 'match';
    const CONFLICT = 'conflict';
    const SILENT = 'silent';
    const ABSENT = 'absent';

    /**
     * Composite-score delta for a given signal. Shared with the
     * Heratio Laravel package.
     */
    public static function delta(string $signal): float
    {
        switch ($signal) {
            case self::MATCH:
                return 0.10;
            case self::CONFLICT:
                return -0.30;
            case self::SILENT:
            case self::ABSENT:
            default:
                return 0.0;
        }
    }
}
