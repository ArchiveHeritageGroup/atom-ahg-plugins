<?php

namespace ahgAiConditionPlugin\Helpers;

/**
 * View helpers for AI condition assessment display.
 */
class AiConditionHelper
{
    /**
     * Grade color mapping (Bootstrap classes).
     */
    private const GRADE_COLORS = [
        'excellent' => 'success',
        'good'      => 'info',
        'fair'      => 'warning',
        'poor'      => 'danger',
        'critical'  => 'dark',
    ];

    /**
     * Grade icons.
     */
    private const GRADE_ICONS = [
        'excellent' => 'fa-check-circle',
        'good'      => 'fa-thumbs-up',
        'fair'      => 'fa-exclamation-triangle',
        'poor'      => 'fa-times-circle',
        'critical'  => 'fa-skull-crossbones',
    ];

    /**
     * Damage type colors for overlay rendering.
     */
    private const DAMAGE_COLORS = [
        'tear'           => '#dc3545',
        'stain'          => '#fd7e14',
        'foxing'         => '#ffc107',
        'fading'         => '#6c757d',
        'water_damage'   => '#0dcaf0',
        'mold'           => '#198754',
        'pest_damage'    => '#6f42c1',
        'abrasion'       => '#adb5bd',
        'brittleness'    => '#495057',
        'loss'           => '#212529',
        'discoloration'  => '#e0a800',
        'warping'        => '#20c997',
        'cracking'       => '#d63384',
        'delamination'   => '#0d6efd',
        'corrosion'      => '#795548',
    ];

    /**
     * Render a condition grade badge.
     */
    public static function gradeBadge(?string $grade): string
    {
        if (!$grade) {
            return '<span class="badge bg-secondary">N/A</span>';
        }
        $color = self::GRADE_COLORS[$grade] ?? 'secondary';
        $icon = self::GRADE_ICONS[$grade] ?? 'fa-question-circle';
        $label = ucfirst($grade);

        return '<span class="badge bg-' . $color . '"><i class="fas ' . $icon . ' me-1"></i>' . $label . '</span>';
    }

    /**
     * Render a score gauge (colored number).
     */
    public static function scoreDisplay(?float $score): string
    {
        if ($score === null) {
            return '<span class="text-muted">--</span>';
        }

        $color = 'success';
        if ($score < 40) {
            $color = 'danger';
        } elseif ($score < 60) {
            $color = 'warning';
        } elseif ($score < 80) {
            $color = 'info';
        }

        return '<span class="fw-bold text-' . $color . '">' . number_format($score, 1) . '</span>';
    }

    /**
     * Get Bootstrap color class for a damage type.
     */
    public static function damageColor(string $type): string
    {
        return self::DAMAGE_COLORS[$type] ?? '#6c757d';
    }

    /**
     * Render a confidence bar.
     */
    public static function confidenceBar(float $confidence): string
    {
        $percent = round($confidence * 100);
        $color = 'success';
        if ($percent < 50) {
            $color = 'danger';
        } elseif ($percent < 75) {
            $color = 'warning';
        }

        return '<div class="progress" style="height:6px;width:60px;display:inline-block;vertical-align:middle">'
            . '<div class="progress-bar bg-' . $color . '" style="width:' . $percent . '%"></div>'
            . '</div> <small class="text-muted">' . $percent . '%</small>';
    }

    /**
     * Get severity label with color.
     */
    public static function severityBadge(?string $severity): string
    {
        $colors = [
            'minor'    => 'success',
            'moderate' => 'warning',
            'severe'   => 'danger',
            'critical' => 'dark',
        ];
        if (!$severity) {
            return '';
        }
        $color = $colors[$severity] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . ucfirst($severity) . '</span>';
    }
}
