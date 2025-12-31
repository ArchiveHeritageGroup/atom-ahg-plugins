<?php use_helper('Date') ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1><i class="fas fa-clipboard-list text-success me-2"></i><?php echo __('Condition Assessment Templates') ?></h1>
            <p class="text-muted"><?php echo __('Predefined assessment forms for different material types') ?></p>
        </div>
    </div>

    <!-- Material Type Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <span class="me-2 align-self-center"><strong><?php echo __('Filter by Material:') ?></strong></span>
                <a href="#" class="btn btn-sm btn-outline-secondary active material-filter" data-material="all">All</a>
                <?php foreach ($materialTypes as $type): ?>
                <a href="#" class="btn btn-sm btn-outline-secondary material-filter" data-material="<?php echo $type ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $type)) ?>
                </a>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="row" id="templatesGrid">
        <?php foreach ($templates as $template): ?>
        <div class="col-md-6 col-lg-4 mb-4 template-card" data-material="<?php echo $template->material_type ?>">
            <div class="card h-100 <?php echo !$template->is_active ? 'border-secondary opacity-50' : '' ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php if ($template->is_default): ?>
                        <i class="fas fa-star text-warning me-1" title="Default"></i>
                        <?php endif ?>
                        <?php echo esc_entities($template->name) ?>
                    </h5>
                    <span class="badge bg-<?php echo $template->is_active ? 'success' : 'secondary' ?>">
                        <?php echo $template->is_active ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted"><?php echo esc_entities($template->description ?: 'No description') ?></p>
                    <div class="mb-3">
                        <span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $template->material_type)) ?></span>
                        <span class="badge bg-light text-dark"><?php echo esc_entities($template->code) ?></span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="/condition/template/<?php echo $template->id ?>/view" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i> View
                    </a>
                    <a href="/condition/template/<?php echo $template->id ?>/export" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> Export
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <?php if (empty($templates)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No templates available. Run the SQL seed script to add predefined templates.
    </div>
    <?php endif ?>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.material-filter').forEach(function(filter) {
        filter.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.material-filter').forEach(f => f.classList.remove('active'));
            this.classList.add('active');
            var material = this.dataset.material;
            document.querySelectorAll('.template-card').forEach(function(card) {
                card.style.display = (material === 'all' || card.dataset.material === material) ? '' : 'none';
            });
        });
    });
});
</script>
