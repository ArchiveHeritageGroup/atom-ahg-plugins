<?php
$session = $sf_data->getRaw('session');
$mappings = $sf_data->getRaw('mappings') ?? [];
$targetFields = $sf_data->getRaw('targetFields') ?? [];
$savedProfiles = $sf_data->getRaw('savedProfiles') ?? [];
$sampleRows = $sf_data->getRaw('sampleRows') ?? [];
?>

<h1><?php echo __('Map & Enrich') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager'), 'url' => url_for(['module' => 'ingest', 'action' => 'index'])],
        ['title' => esc_entities($session->title ?: __('Session #' . $session->id))],
        ['title' => __('Map & Enrich')]
    ]
]) ?>

<!-- Wizard Progress -->
<div class="mb-4">
    <div class="d-flex justify-content-between text-center">
        <div class="flex-fill"><span class="badge bg-success rounded-pill">1</span><br><small class="text-muted"><?php echo __('Configure') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">2</span><br><small class="text-muted"><?php echo __('Upload') ?></small></div>
        <div class="flex-fill"><span class="badge bg-primary rounded-pill">3</span><br><small class="fw-bold"><?php echo __('Map') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">4</span><br><small class="text-muted"><?php echo __('Validate') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">5</span><br><small class="text-muted"><?php echo __('Preview') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">6</span><br><small class="text-muted"><?php echo __('Commit') ?></small></div>
    </div>
    <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar" style="width: 42%"></div>
    </div>
</div>

<?php if (!empty($savedProfiles)): ?>
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'map', 'id' => $session->id]) ?>" class="d-flex align-items-center">
            <input type="hidden" name="form_action" value="load_profile">
            <label class="form-label mb-0 me-2 text-nowrap"><?php echo __('Load saved profile:') ?></label>
            <select class="form-select form-select-sm me-2" name="mapping_profile_id" style="max-width: 300px;">
                <option value=""><?php echo __('— Select —') ?></option>
                <?php foreach ($savedProfiles as $p): ?>
                    <option value="<?php echo $p->id ?>"><?php echo esc_entities($p->name) ?> (<?php echo $p->target_type ?>)</option>
                <?php endforeach ?>
            </select>
            <button type="submit" class="btn btn-outline-secondary btn-sm"><?php echo __('Load') ?></button>
        </form>
    </div>
</div>
<?php endif ?>

<form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'map', 'id' => $session->id]) ?>">
    <input type="hidden" name="form_action" value="save">

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-columns me-2"></i><?php echo __('Column Mapping') ?></h5>
                    <span class="badge bg-info">
                        <?php echo count($mappings) ?> <?php echo __('columns') ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%"><?php echo __('Source Column') ?></th>
                                    <th style="width: 30%"><?php echo __('Target Field') ?></th>
                                    <th style="width: 15%"><?php echo __('Default') ?></th>
                                    <th style="width: 15%"><?php echo __('Transform') ?></th>
                                    <th style="width: 10%"><?php echo __('Ignore') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mappings as $map): ?>
                                    <?php
                                    // Determine confidence color
                                    $confClass = 'bg-danger bg-opacity-10';
                                    if ($map->target_field) {
                                        $confClass = 'bg-success bg-opacity-10';
                                    }
                                    ?>
                                    <tr class="<?php echo $confClass ?>">
                                        <td>
                                            <code><?php echo esc_entities($map->source_column) ?></code>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="target_field[<?php echo $map->id ?>]">
                                                <option value=""><?php echo __('— unmapped —') ?></option>
                                                <?php foreach ($targetFields as $tf): ?>
                                                    <option value="<?php echo $tf ?>"
                                                        <?php echo $map->target_field === $tf ? 'selected' : '' ?>>
                                                        <?php echo $tf ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                   name="default_value[<?php echo $map->id ?>]"
                                                   value="<?php echo esc_entities($map->default_value ?? '') ?>"
                                                   placeholder="<?php echo __('default') ?>">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="transform[<?php echo $map->id ?>]">
                                                <option value=""><?php echo __('None') ?></option>
                                                <?php foreach (['trim', 'uppercase', 'lowercase', 'titlecase', 'date_iso', 'strip_html'] as $t): ?>
                                                    <option value="<?php echo $t ?>"
                                                        <?php echo ($map->transform ?? '') === $t ? 'selected' : '' ?>>
                                                        <?php echo $t ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input"
                                                   name="is_ignored[<?php echo $map->id ?>]" value="1"
                                                   <?php echo $map->is_ignored ? 'checked' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i><?php echo __('Data Preview') ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($sampleRows)): ?>
                        <?php foreach ($sampleRows as $i => $sr): ?>
                            <?php $data = json_decode($sr->data, true) ?: []; ?>
                            <div class="mb-2 p-2 border rounded <?php echo $i === 0 ? 'border-primary' : '' ?>">
                                <small class="text-muted"><?php echo __('Row') ?> <?php echo $sr->row_number ?></small>
                                <div><strong><?php echo esc_entities($sr->title ?: '—') ?></strong></div>
                                <?php if ($sr->level_of_description): ?>
                                    <small class="badge bg-secondary"><?php echo $sr->level_of_description ?></small>
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?php echo __('No data rows found') ?></p>
                    <?php endif ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Digital Object Matching') ?></h5>
                </div>
                <div class="card-body">
                    <select class="form-select form-select-sm" name="do_match_strategy">
                        <option value="filename"><?php echo __('Match by filename') ?></option>
                        <option value="legacyId"><?php echo __('Match by legacyId') ?></option>
                        <option value="title"><?php echo __('Match by title') ?></option>
                    </select>
                    <small class="text-muted"><?php echo __('How to match digital objects from ZIP to CSV rows') ?></small>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6><?php echo __('Legend') ?></h6>
                    <div class="d-flex align-items-center mb-1">
                        <span class="d-inline-block me-2 rounded" style="width:16px;height:16px;background:rgba(25,135,84,0.1);border:1px solid rgba(25,135,84,0.3)"></span>
                        <small><?php echo __('Mapped (auto or manual)') ?></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="d-inline-block me-2 rounded" style="width:16px;height:16px;background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.3)"></span>
                        <small><?php echo __('Unmapped (needs attention)') ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'upload', 'id' => $session->id]) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <?php echo __('Save Mappings & Validate') ?> <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>
</form>
