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

    public static function normalize(?string $code): ?string
    {
        if ($code === null || trim((string) $code) === '') {
            return null;
        }
        $code = trim((string) $code);
        return isset(self::PROCEDURES[$code]) ? $code : null;
    }
}
