<?php
/**
 * Inline "Versions" panel rendered on IO and actor view pages by ahgDisplayPlugin.
 *
 * Available variables:
 *   $resource - the entity object (QubitInformationObject or QubitActor)
 *   $context  - 'informationobject' | 'actor'
 *
 * Shows the latest 5 versions inline plus a link to the full history page.
 */

if (!isset($resource) || !isset($resource->id)) {
    return;
}
$objectId = (int) $resource->id;
if ($objectId <= 0) {
    return;
}

$entityType = ($context ?? '') === 'actor' ? 'actor' : 'information_object';
$versionTable = $entityType === 'actor' ? 'actor_version' : 'information_object_version';
$fk = $entityType === 'actor' ? 'actor_id' : 'information_object_id';

try {
    $totalCount = (int) \Illuminate\Database\Capsule\Manager::table($versionTable)
        ->where($fk, $objectId)->count();

    $versions = \Illuminate\Database\Capsule\Manager::table($versionTable)
        ->leftJoin('user', 'user.id', '=', $versionTable . '.created_by')
        ->where($fk, $objectId)
        ->orderBy('version_number', 'desc')
        ->limit(5)
        ->select(
            $versionTable . '.version_number',
            $versionTable . '.change_summary',
            $versionTable . '.changed_fields',
            $versionTable . '.created_at',
            $versionTable . '.is_restore',
            'user.username AS created_by_username',
        )
        ->get();
} catch (\Throwable $e) {
    // Plugin tables may not be installed in some contexts (e.g. install task running)
    return;
}

if ($totalCount === 0) {
    echo '<p class="text-muted">' . __('No versions captured yet.') . '</p>';
    return;
}

$fullHistoryUrl = url_for([
    'module' => 'versionControl',
    'action' => 'list',
    'entity' => $entityType,
    'id'     => $objectId,
]);
?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  .vc-panel .vc-row { padding: .4rem .5rem; border-bottom: 1px solid #f0f0f0; font-size: .9rem; }
  .vc-panel .vc-row:last-child { border-bottom: none; }
  .vc-panel .vc-row .vc-num { font-weight: 600; }
  .vc-panel .vc-row .vc-meta { color: #6c757d; font-size: .8rem; }
  .vc-panel .badge-restore { background:#fff3cd; color:#856404; border:1px solid #ffeeba; }
</style>

<div class="vc-panel">
    <p class="text-muted mb-2">
        <?php echo sprintf(__('%d version(s) on record'), $totalCount) ?> ·
        <a href="<?php echo $fullHistoryUrl ?>"><?php echo __('Full history') ?> →</a>
    </p>
    <?php foreach ($versions as $v): ?>
        <?php
            $detailUrl = url_for([
                'module' => 'versionControl',
                'action' => 'show',
                'entity' => $entityType,
                'id'     => $objectId,
                'number' => (int) $v->version_number,
            ]);
            $changed = is_string($v->changed_fields) ? (json_decode($v->changed_fields, true) ?? []) : [];
            $changedCount = is_array($changed) ? count($changed) : 0;
        ?>
        <div class="vc-row">
            <a href="<?php echo $detailUrl ?>"><span class="vc-num">v<?php echo (int) $v->version_number ?></span></a>
            <?php if ((int) $v->is_restore === 1): ?>
                <span class="badge badge-restore"><?php echo __('restore') ?></span>
            <?php endif ?>
            <span class="vc-meta">
                <?php echo esc_entities($v->created_at) ?>
                <?php if ($v->created_by_username): ?> · <?php echo esc_entities($v->created_by_username) ?><?php endif ?>
                <?php if ($changedCount > 0): ?>
                    · <?php echo sprintf(__('%d field(s) changed'), $changedCount) ?>
                <?php elseif ($v->changed_fields !== null): ?>
                    · <?php echo __('no archival metadata changes') ?>
                <?php endif ?>
            </span>
            <?php if (!empty($v->change_summary)): ?>
                <div class="text-muted small"><?php echo esc_entities($v->change_summary) ?></div>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>
