<?php

/**
 * SpectrumProcedureCatalog - PSIS Symfony port of Heratio Spectrum#A.
 *
 * Canonical list of the 21 Spectrum 5.1 primary procedures (UK Collections Trust).
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

class SpectrumProcedureCatalog
{
    public const PROCEDURES = [
        'object_entry'        => 'Object entry',
        'acquisition'         => 'Acquisition and accessioning',
        'inventory'           => 'Inventory',
        'location_movement'   => 'Location and movement control',
        'cataloguing'         => 'Cataloguing',
        'object_exit'         => 'Object exit',
        'loans_in'            => 'Loans in (borrowing)',
        'loans_out'           => 'Loans out (lending)',
        'insurance'           => 'Insurance and indemnity',
        'damage_loss'         => 'Damage and loss',
        'conservation'        => 'Conservation and collections care',
        'audit'               => 'Audit',
        'condition_check'     => 'Object condition checking and technical assessment',
        'valuation'           => 'Object valuation',
        'risk_management'     => 'Risk management',
        'emergency_planning'  => 'Emergency planning for collections',
        'use_of_collections'  => 'Use of collections',
        'rights_management'   => 'Rights management',
        'reproduction'        => 'Reproduction',
        'deaccessioning'      => 'Deaccessioning and disposal',
        'retrospective_doc'   => 'Retrospective documentation',
    ];

    public static function all(): array
    {
        return self::PROCEDURES;
    }

    public static function codes(): array
    {
        return array_keys(self::PROCEDURES);
    }

    public static function label(?string $code): string
    {
        if ($code === null || $code === '') {
            return '';
        }
        return self::PROCEDURES[$code] ?? $code;
    }

    /**
     * Codes for the same procedure that differ between the two plugins.
     *
     * This catalogue and ahgSpectrumPlugin's spectrum_workflow_config grew
     * separately and name the same Spectrum procedures differently. Both have live
     * data - ahg_workflow holds 21 rows using the codes on the left,
     * spectrum_workflow_config holds 21 using the codes on the right - so
     * neither can simply be renamed without rewriting stored client data.
     *
     * The workflow configs, states, transitions and history all live on the
     * right-hand codes, so those are canonical. This maps the left onto them and
     * leaves both readable.
     *
     * `emergency_planning` and `use_of_collections` are Spectrum procedures with
     * no workflow config, so they have no counterpart and map to themselves.
     */
    public const ALIASES = [
        'condition_check' => 'condition_checking',
        'damage_loss' => 'loss_damage',
        'deaccessioning' => 'deaccession',
        'inventory' => 'inventory_control',
        'retrospective_doc' => 'retrospective_documentation',
    ];

    /**
     * The canonical code for a procedure, whichever vocabulary it arrived in.
     */
    public static function canonical(?string $code): ?string
    {
        if (null === $code || '' === trim((string) $code)) {
            return null;
        }

        $code = trim((string) $code);

        return self::ALIASES[$code] ?? $code;
    }

    /**
     * Every code that means this procedure, for querying across both stores.
     *
     * @return array<int, string>
     */
    public static function synonyms(?string $code): array
    {
        $canonical = self::canonical($code);

        if (null === $canonical) {
            return [];
        }

        $out = [$canonical];

        foreach (self::ALIASES as $alias => $target) {
            if ($target === $canonical && !in_array($alias, $out, true)) {
                $out[] = $alias;
            }
        }

        return $out;
    }

    public static function normalize(?string $code): ?string
    {
        if ($code === null || trim((string) $code) === '') {
            return null;
        }
        $code = trim((string) $code);

        // Accept either vocabulary. A code that only exists as an alias was
        // previously rejected outright, so a workflow filed against
        // `condition_checking` did not match this catalogue at all.
        if (isset(self::PROCEDURES[$code])) {
            return $code;
        }

        $flipped = array_search($code, self::ALIASES, true);

        return false !== $flipped ? $flipped : null;
    }
}
