<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-theater-masks"></i> <?php echo __('Style & Period Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-dark text-white"><?php echo __('By Style'); ?></div>
            <ul class="list-group list-group-flush">
                <?php if (empty($byStyle)): ?><li class="list-group-item text-muted"><?php echo __('No styles recorded'); ?></li><?php endif; ?>
                <?php foreach ($byStyle as $s): ?>
                <li class="list-group-item d-flex justify-content-between"><?php echo esc_specialchars($s->style); ?> <span class="badge bg-primary"><?php echo $s->count; ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-dark text-white"><?php echo __('By Period'); ?></div>
            <ul class="list-group list-group-flush">
                <?php if (empty($byPeriod)): ?><li class="list-group-item text-muted"><?php echo __('No periods recorded'); ?></li><?php endif; ?>
                <?php foreach ($byPeriod as $p): ?>
                <li class="list-group-item d-flex justify-content-between"><?php echo esc_specialchars($p->period); ?> <span class="badge bg-success"><?php echo $p->count; ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-dark text-white"><?php echo __('By Culture'); ?></div>
            <ul class="list-group list-group-flush">
                <?php if (empty($byCulture)): ?><li class="list-group-item text-muted"><?php echo __('No cultures recorded'); ?></li><?php endif; ?>
                <?php foreach ($byCulture as $c): ?>
                <li class="list-group-item d-flex justify-content-between"><?php echo esc_specialchars($c->cultural_context); ?> <span class="badge bg-info"><?php echo $c->count; ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-dark text-white"><?php echo __('By Movement'); ?></div>
            <ul class="list-group list-group-flush">
                <?php if (empty($byMovement)): ?><li class="list-group-item text-muted"><?php echo __('No movements recorded'); ?></li><?php endif; ?>
                <?php foreach ($byMovement as $m): ?>
                <li class="list-group-item d-flex justify-content-between"><?php echo esc_specialchars($m->movement); ?> <span class="badge bg-warning"><?php echo $m->count; ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php end_slot(); ?>
