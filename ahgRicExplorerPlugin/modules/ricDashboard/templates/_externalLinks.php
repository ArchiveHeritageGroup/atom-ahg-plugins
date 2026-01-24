<!-- External RIC Tools Links -->
<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa fa-external-link-alt"></i> <?php echo __('RIC Tools'); ?></h5>
    </div>
    <div class="card-body">
        <div class="d-grid gap-2">
            <a href="<?php echo url_for(['module' => 'settings', 'action' => 'section', 'section' => 'fuseki']); ?>" class="btn btn-outline-primary">
                <i class="fa fa-cog"></i> <?php echo __('RIC/Fuseki Settings'); ?>
            </a>
            <a href="https://www.ica.org/standards/RiC/ontology" target="_blank" class="btn btn-outline-info">
                <i class="fa fa-book"></i> <?php echo __('RiC-O Ontology Reference'); ?>
            </a>
<?php
$config = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
    ->where('setting_group', 'fuseki')
    ->pluck('setting_value', 'setting_key')
    ->toArray();
$fusekiEndpoint = $config['fuseki_endpoint'] ?? sfConfig::get('app_ric_fuseki_endpoint', 'http://localhost:3030/ric');
$fusekiAdmin = preg_replace('#/[^/]+$#', '/', $fusekiEndpoint);
?>
            <a href="<?php echo esc_specialchars($fusekiAdmin); ?>" target="_blank" class="btn btn-outline-dark">
                <i class="fa fa-database"></i> <?php echo __('Fuseki Admin'); ?>
            </a>
        </div>
    </div>
</div>
