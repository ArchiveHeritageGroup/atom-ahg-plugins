<!-- External RIC Tools Links -->
<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa fa-external-link-alt"></i> <?php echo __('RIC Tools'); ?></h5>
    </div>
    <div class="card-body">
        <div class="d-grid gap-2">
            <a href="/ric/" target="_blank" class="btn btn-outline-primary">
                <i class="fa fa-project-diagram"></i> <?php echo __('RIC Explorer Dashboard'); ?>
            </a>
            <a href="/ric/" target="_blank" class="btn btn-outline-info">
                <i class="fa fa-sitemap"></i> <?php echo __('Graph Visualization'); ?>
            </a>
            <a href="/ric-dashboard/categories.php" target="_blank" class="btn btn-outline-secondary">
                <i class="fa fa-tags"></i> <?php echo __('RIC Categories Reference'); ?>
            </a>
<?php $fusekiEndpoint = sfConfig::get('app_ric_fuseki_endpoint', 'http://localhost:3030/ric');
$fusekiAdmin = preg_replace('#/[^/]+$#', '/', $fusekiEndpoint); ?>
            <a href="<?php echo esc_specialchars($fusekiAdmin); ?>" target="_blank" class="btn btn-outline-dark">
                <i class="fa fa-database"></i> <?php echo __('Fuseki Admin'); ?>
            </a>
        </div>
    </div>
</div>
