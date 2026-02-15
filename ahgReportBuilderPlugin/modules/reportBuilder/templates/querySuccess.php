<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-database text-primary me-2"></i><?php echo __('Query Builder'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$rawReport = $sf_data->getRaw('report');
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'preview', 'id' => $rawReport->id]); ?>"><?php echo htmlspecialchars($rawReport->name); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Query Builder'); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?php echo htmlspecialchars($rawReport->name); ?></h4>
        <small class="text-muted"><?php echo __('Build and execute queries for this report'); ?></small>
    </div>
    <div>
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'edit', 'id' => $rawReport->id]); ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i><?php echo __('Back to Editor'); ?>
        </a>
    </div>
</div>

<!-- Query Builder Container -->
<div id="queryBuilderContainer"></div>

<!-- Load query builder JS -->
<script src="/plugins/ahgReportBuilderPlugin/web/js/query-builder.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.QueryBuilderUI !== 'undefined') {
        window.QueryBuilderUI.init('queryBuilderContainer', {
            tables: '<?php echo url_for(['module' => 'reportBuilder', 'action' => 'apiQueryTables']); ?>',
            columns: '/index.php/api/report-builder/query/columns',
            relationships: '/index.php/api/report-builder/query/relationships',
            execute: '<?php echo url_for(['module' => 'reportBuilder', 'action' => 'apiQueryExecute']); ?>',
            save: '<?php echo url_for(['module' => 'reportBuilder', 'action' => 'apiQuerySave']); ?>',
            load: '',
            delete: ''
        });
    } else {
        console.error('QueryBuilderUI not loaded. Ensure query-builder.js is included.');
        document.getElementById('queryBuilderContainer').innerHTML =
            '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>Query Builder failed to load. Please refresh the page.</div>';
    }
});
</script>
<?php end_slot() ?>
