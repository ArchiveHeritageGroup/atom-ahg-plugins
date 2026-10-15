<?php
/**
 * indexSuccess: admin list of IIIF Content State shared views (executeIndex).
 * The action existed without a template, so the page answered with a 500.
 */
$savedViews = $sf_data->getRaw('savedViews') ?: [];
$stats = $sf_data->getRaw('stats') ?: [];
?>
<?php slot('title') ?><?php echo __('IIIF Shared Views') ?><?php end_slot() ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'admin', 'action' => 'index']) ?>"><?php echo __('Admin') ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('IIIF Shared Views') ?></li>
        </ol>
    </nav>

    <h1 class="h3 mb-1"><i class="fas fa-share-alt me-2"></i><?php echo __('IIIF Shared Views') ?></h1>
    <p class="text-muted"><?php echo __('Links to a saved viewer state (IIIF Content State): the image, the zoom and the region someone chose to share.') ?></p>

    <div class="row mb-4">
        <?php foreach ([
            ['total_views', __('Shared views'), 'primary'],
            ['active_views', __('Still valid'), 'success'],
            ['total_clicks', __('Times opened'), 'info'],
        ] as [$key, $label, $colour]): ?>
        <div class="col-md-4 mb-3">
            <div class="card border-<?php echo $colour ?> h-100">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo number_format((int) ($stats[$key] ?? 0)) ?></h3>
                    <small class="text-muted"><?php echo $label ?></small>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Recent shared views') ?></h5></div>
        <div class="card-body p-0">
            <?php if (empty($savedViews)): ?>
                <p class="text-muted text-center py-4 mb-0"><?php echo __('No views have been shared yet.') ?></p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Record') ?></th>
                            <th><?php echo __('Link') ?></th>
                            <th class="text-end"><?php echo __('Opened') ?></th>
                            <th><?php echo __('Created') ?></th>
                            <th><?php echo __('Expires') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($savedViews as $v): ?>
                        <tr>
                            <td>
                                <?php if (!empty($v['slug'])): ?>
                                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $v['slug']]) ?>"><?php echo esc_entities($v['title'] ?: $v['slug']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted"><?php echo __('(no record)') ?></span>
                                <?php endif ?>
                            </td>
                            <td><a href="<?php echo url_for('@iiif_cs_state').'?cs='.rawurlencode((string) $v['token']) ?>"><code><?php echo esc_entities($v['token']) ?></code></a></td>
                            <td class="text-end"><?php echo (int) $v['click_count'] ?></td>
                            <td><small><?php echo esc_entities((string) $v['created_at']) ?></small></td>
                            <td>
                                <?php if (empty($v['expires_at'])): ?>
                                    <small class="text-muted"><?php echo __('Never') ?></small>
                                <?php elseif (strtotime($v['expires_at']) < time()): ?>
                                    <small class="text-danger"><?php echo esc_entities($v['expires_at']) ?></small>
                                <?php else: ?>
                                    <small><?php echo esc_entities($v['expires_at']) ?></small>
                                <?php endif ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

