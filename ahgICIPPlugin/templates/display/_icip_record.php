<?php
/**
 * ICIP display for an archival description.
 *
 * Reads the four record-linked ICIP tables and renders, for a given object:
 *   - a prominent cultural-notice / access-restriction BADGE (the warning a
 *     researcher must see before engaging with sensitive material), and
 *   - a PANEL listing traditional-knowledge labels and consent status.
 *
 * Loaded by the display panel system. Renders nothing when the record carries no
 * ICIP data, so it never adds an empty section.
 *
 * $resource - the entity object
 * $context  - display context (informationobject)
 * $mode     - 'badge' or 'panel' (set by the caller)
 */

$objectId = isset($resource->id) ? (int) $resource->id : 0;
if ($objectId <= 0) {
    return;
}

$mode = $mode ?? 'panel';

use Illuminate\Database\Capsule\Manager as DB;

// A record inherits ICIP controls set on any ancestor with
// applies_to_descendants, so nested finds are covered by a notice on the site.
$ancestorIds = [];
try {
    $node = DB::table('information_object')->where('id', $objectId)->select('lft', 'rgt')->first();
    if ($node) {
        $ancestorIds = DB::table('information_object')
            ->where('lft', '<', $node->lft)->where('rgt', '>', $node->rgt)
            ->pluck('id')->all();
    }
} catch (\Throwable $e) {
    // nested set unavailable - fall back to the record itself only
}
$selfAndAncestors = array_merge([$objectId], $ancestorIds);

// --- notices (own, plus inheritable ones on ancestors) ---
$notices = DB::table('icip_cultural_notice as n')
    ->leftJoin('icip_cultural_notice_type as t', 't.id', '=', 'n.notice_type_id')
    ->where(function ($q) use ($objectId, $ancestorIds) {
        $q->where('n.information_object_id', $objectId)
            ->orWhere(function ($w) use ($ancestorIds) {
                if ($ancestorIds) {
                    $w->whereIn('n.information_object_id', $ancestorIds)->where('n.applies_to_descendants', 1);
                }
            });
    })
    ->select('t.name', 't.severity', 't.icon', 't.blocks_access', 'n.custom_text', 'n.information_object_id')
    ->get();

$restrictions = DB::table('icip_access_restriction')
    ->where(function ($q) use ($objectId, $ancestorIds) {
        $q->where('information_object_id', $objectId)
            ->orWhere(function ($w) use ($ancestorIds) {
                if ($ancestorIds) {
                    $w->whereIn('information_object_id', $ancestorIds)->where('applies_to_descendants', 1);
                }
            });
    })
    ->select('restriction_type', 'custom_restriction_text')
    ->get();

$tkLabels = DB::table('icip_tk_label as l')
    ->leftJoin('icip_tk_label_type as t', 't.id', '=', 'l.label_type_id')
    ->where('l.information_object_id', $objectId)
    ->select('t.name', 't.category', 't.icon_path', 't.local_contexts_url', 'l.notes')
    ->get();

$consent = DB::table('icip_consent as c')
    ->leftJoin('icip_community as m', 'm.id', '=', 'c.community_id')
    ->where('c.information_object_id', $objectId)
    ->select('c.consent_status', 'c.consent_scope', 'm.name as community')
    ->get();

$hasAny = count($notices) || count($restrictions) || count($tkLabels) || count($consent);
if (!$hasAny) {
    return;
}

$esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Machine restriction_type values -> human-readable labels. Closure (not a named
// function) because this template is included twice per page (badge + panel).
// Unknown values fall back to Title Case of the raw value.
$restrictionLabel = static function ($type) {
    $map = [
        'community_permission_required' => 'Community permission required',
        'secret_sacred' => 'Secret / sacred',
        'sacred' => 'Sacred',
        'sacred_site' => 'Sacred site',
        'gender_restricted' => 'Gender restricted',
        'men_only' => 'Men only',
        'women_only' => 'Women only',
        'initiated_only' => 'Initiated persons only',
        'ceremonial_use_only' => 'Ceremonial use only',
        'seasonal_restriction' => 'Seasonal restriction',
        'seasonal' => 'Seasonal restriction',
        'no_public_access' => 'No public access',
        'restricted_access' => 'Restricted access',
        'attribution_required' => 'Attribution required',
        'traditional_knowledge' => 'Traditional knowledge',
    ];
    $key = (string) $type;

    return __($map[$key] ?? ucwords(str_replace('_', ' ', $key)));
};

// ---------------------------------------------------------------- BADGE mode
if ('badge' === $mode) {
    if (!count($notices) && !count($restrictions)) {
        return;   // badge is only for notices and restrictions
    }
    foreach ($notices as $n) {
        $sev = strtolower((string) $n->severity);
        $cls = 'blocks_access' === $sev || $n->blocks_access ? 'danger' : ('high' === $sev ? 'warning' : 'info');
        $inherited = ((int) $n->information_object_id !== $objectId) ? ' <span class="small">(' . __('inherited') . ')</span>' : '';
        echo '<div class="alert alert-' . $cls . ' d-flex align-items-start mb-2" role="alert">';
        echo '<i class="fas fa-hands ' . 'me-2 mt-1" aria-hidden="true"></i>';
        echo '<div><strong>' . $esc($n->name ?: __('Cultural notice')) . '</strong>' . $inherited;
        if ($n->custom_text) {
            echo '<div class="small">' . $esc($n->custom_text) . '</div>';
        }
        echo '</div></div>';
    }
    foreach ($restrictions as $r) {
        echo '<div class="alert alert-warning d-flex align-items-start mb-2" role="alert">';
        echo '<i class="fas fa-lock me-2 mt-1" aria-hidden="true"></i>';
        echo '<div><strong>' . $esc(__('Access restriction')) . ': ' . $esc($restrictionLabel($r->restriction_type)) . '</strong>';
        if ($r->custom_restriction_text) {
            echo '<div class="small">' . $esc($r->custom_restriction_text) . '</div>';
        }
        echo '</div></div>';
    }

    return;
}

// ---------------------------------------------------------------- PANEL mode
// Rendered to match the "Additional Fields" section: render_b5_section_heading
// (the same helper the standard ISAD areas use) with NO icon and no card, so the
// header is identical to every other section on the page. The Manage action moves
// to the foot of the body so the heading stays clean.
$heading = __('Indigenous cultural & IP');
$slug = $resource->slug ?? null;
$canManage = isset($sf_user) && $sf_user->isAuthenticated();
?>
<section id="icipArea" class="border-bottom">
    <?php if (function_exists('render_b5_section_heading')): ?>
        <?php echo render_b5_section_heading($heading); ?>
    <?php else: ?>
        <h2 class="h5 mb-0 atom-section-header"><div class="d-flex p-3 border-bottom text-primary"><?php echo $esc($heading); ?></div></h2>
    <?php endif; ?>

    <div>
        <?php if (count($tkLabels)): ?>
            <div class="mb-3">
                <div class="text-muted small text-uppercase mb-1"><?php echo __('Traditional knowledge labels'); ?></div>
                <?php foreach ($tkLabels as $l): ?>
                    <span class="badge bg-secondary me-1 mb-1">
                        <?php echo $esc($l->name ?: $l->category ?: __('TK label')); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($consent)): ?>
            <div class="mb-2">
                <div class="text-muted small text-uppercase mb-1"><?php echo __('Community consent'); ?></div>
                <?php foreach ($consent as $c): ?>
                    <div class="small">
                        <?php echo $esc($c->community ?: __('Community')); ?>:
                        <strong><?php echo $esc($c->consent_status ?: __('unknown')); ?></strong>
                        <?php if ($c->consent_scope): ?><span class="text-muted">(<?php echo $esc($c->consent_scope); ?>)</span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($restrictions)): ?>
            <div class="mb-0">
                <div class="text-muted small text-uppercase mb-1"><?php echo __('Access restrictions'); ?></div>
                <?php foreach ($restrictions as $r): ?>
                    <div class="small"><i class="fas fa-lock me-1"></i><?php echo $esc($restrictionLabel($r->restriction_type)); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
