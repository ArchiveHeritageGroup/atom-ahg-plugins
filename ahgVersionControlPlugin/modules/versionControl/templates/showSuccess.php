<?php
/** @var string $entityType */
/** @var int    $entityId */
/** @var int    $versionNumber */
/** @var ?string $entityTitle */
/** @var ?string $entitySlug */
/** @var stdClass $version */
/** @var array<string,mixed> $snapshot */
/** @var array<int,string>   $changedFields */

$base = is_array($snapshot['base'] ?? null) ? $snapshot['base'] : [];
$i18n = is_array($snapshot['i18n'] ?? null) ? $snapshot['i18n'] : [];
$ap = is_array($snapshot['access_points'] ?? null) ? $snapshot['access_points'] : [];
$ev = is_array($snapshot['events'] ?? null) ? $snapshot['events'] : [];
$rel = is_array($snapshot['relations'] ?? null) ? $snapshot['relations'] : [];
$po = is_array($snapshot['physical_objects'] ?? null) ? $snapshot['physical_objects'] : [];
$cf = is_array($snapshot['custom_fields'] ?? null) ? $snapshot['custom_fields'] : [];
?>
<h1>
    <?php echo sprintf(__('Version %d'), $versionNumber) ?>
    <small class="text-muted"><?php echo esc_entities($entityTitle ?? '') ?></small>
</h1>

<?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo esc_entities($sf_user->getFlash('notice')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo esc_entities($sf_user->getFlash('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif ?>

<p>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for(['module' => 'versionControl', 'action' => 'list', 'entity' => $entityType, 'id' => $entityId]) ?>">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('All versions') ?>
    </a>
    <?php if ($entitySlug): ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo '/' . esc_entities($entitySlug) ?>">
            <i class="fas fa-eye me-1"></i><?php echo __('View record') ?>
        </a>
    <?php endif ?>
    <?php if ($versionNumber > 1): ?>
        <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'versionControl', 'action' => 'diff', 'entity' => $entityType, 'id' => $entityId, 'v1' => $versionNumber - 1, 'v2' => $versionNumber]) ?>">
            <i class="fas fa-code-compare me-1"></i><?php echo sprintf(__('Diff v%d → v%d'), $versionNumber - 1, $versionNumber) ?>
        </a>
    <?php endif ?>
    <button type="button" class="btn btn-warning btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#vc-restore-modal">
        <i class="fas fa-undo me-1"></i><?php echo sprintf(__('Restore this version (v%d)'), $versionNumber) ?>
    </button>
</p>

<div class="modal fade" id="vc-restore-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for(['module' => 'versionControl', 'action' => 'restore', 'entity' => $entityType, 'id' => $entityId, 'number' => $versionNumber]) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo sprintf(__('Restore version %d?'), $versionNumber) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo sprintf(__('You are about to overwrite the current state of <strong>%s</strong> with the snapshot from v%d (captured %s).'),
                        esc_entities($entityTitle ?? ''),
                        $versionNumber,
                        esc_entities($version->created_at)) ?></p>
                    <p class="text-muted small mb-2">
                        <?php echo __('A new version will be created marking the restore (is_restore=1, restored_from_version=' . $versionNumber . ').') ?>
                    </p>
                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong><?php echo __('Scope of restore (v1):') ?></strong>
                        <?php echo __('Base record fields + descriptive metadata (titles, scope, notes, all cultures) + custom fields.') ?>
                        <?php echo __('Access points, events, relationships and physical-object links are NOT restored — they stay as they are currently. Full restore of these is a planned enhancement.') ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-undo me-1"></i><?php echo __('Confirm restore') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Version details') ?></h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3"><?php echo __('Version number') ?></dt>
            <dd class="col-sm-9">v<?php echo $versionNumber ?>
                <?php if ((int) $version->is_restore === 1): ?>
                    <span class="badge bg-warning text-dark"><?php echo __('restore') ?></span>
                    <?php if ($version->restored_from_version): ?>
                        <span class="text-muted">↩ <?php echo sprintf(__('from v%d'), (int) $version->restored_from_version) ?></span>
                    <?php endif ?>
                <?php endif ?>
            </dd>

            <dt class="col-sm-3"><?php echo __('Created at') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities($version->created_at) ?></dd>

            <dt class="col-sm-3"><?php echo __('Created by') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities($version->created_by_username ?? '—') ?></dd>

            <dt class="col-sm-3"><?php echo __('Summary') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities($version->change_summary ?: '—') ?></dd>

            <dt class="col-sm-3"><?php echo __('Changed fields') ?></dt>
            <dd class="col-sm-9">
                <?php if (!empty($changedFields)): ?>
                    <ul class="mb-0">
                        <?php foreach ($changedFields as $f): ?>
                            <li><code><?php echo esc_entities($f) ?></code></li>
                        <?php endforeach ?>
                    </ul>
                <?php else: ?>
                    <span class="text-muted"><?php echo __('No archival metadata changes (or first version)') ?></span>
                <?php endif ?>
            </dd>
        </dl>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0"><?php echo __('Snapshot') ?></h5>
        <small class="text-muted"><?php echo __('Schema version') ?> <?php echo (int) ($snapshot['schema_version'] ?? 0) ?> · <?php echo __('Captured at') ?> <?php echo esc_entities($snapshot['captured_at'] ?? '') ?></small>
    </div>
    <div class="card-body">

        <h6><?php echo __('Base') ?> <small class="text-muted">(<?php echo count($base) ?> <?php echo __('fields') ?>)</small></h6>
        <table class="table table-sm">
            <tbody>
            <?php foreach ($base as $k => $val): ?>
                <tr>
                    <th style="width:30%"><code><?php echo esc_entities((string) $k) ?></code></th>
                    <td><?php echo esc_entities(is_scalar($val) || $val === null ? (string) $val : json_encode($val)) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>

        <h6 class="mt-3"><?php echo __('Localized fields') ?> <small class="text-muted">(<?php echo count($i18n) ?> <?php echo __('cultures') ?>)</small></h6>
        <?php foreach ($i18n as $row): ?>
            <details class="mb-2">
                <summary><strong><?php echo esc_entities($row['culture'] ?? '?') ?></strong></summary>
                <table class="table table-sm mt-1">
                    <tbody>
                    <?php foreach ($row as $k => $val): ?>
                        <?php if ($k === 'culture' || $k === 'id' || $val === null || $val === '') continue; ?>
                        <tr>
                            <th style="width:30%"><code><?php echo esc_entities((string) $k) ?></code></th>
                            <td><?php echo nl2br(esc_entities(is_scalar($val) ? (string) $val : json_encode($val))) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </details>
        <?php endforeach ?>

        <h6 class="mt-3"><?php echo __('Access points, events, relations') ?></h6>
        <ul>
            <li><?php echo sprintf(__('Access points: %d'), count($ap)) ?></li>
            <li><?php echo sprintf(__('Events: %d'), count($ev)) ?></li>
            <li><?php echo sprintf(__('Relations: %d'), count($rel)) ?></li>
            <li><?php echo sprintf(__('Physical objects: %d'), count($po)) ?></li>
            <li><?php echo sprintf(__('Custom fields: %d'), count($cf)) ?></li>
        </ul>

    </div>
</div>
