<?php use_helper('Date') ?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1><i class="fas fa-clipboard-list text-success me-2"></i><?php echo __('Condition Assessment Templates') ?></h1>
            <p class="text-muted"><?php echo __('Predefined assessment forms for different material types') ?></p>
        </div>
        <?php if ($canManage): ?>
        <div>
            <a href="<?php echo url_for(['module' => 'ahgCondition', 'action' => 'template', 'template_action' => 'manage']) ?>" class="btn btn-primary">
                <i class="fas fa-cog me-1"></i> <?php echo __('Manage Templates') ?>
            </a>
        </div>
        <?php endif ?>
    </div>

    <!-- Material Type Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <span class="me-2 align-self-center"><strong><?php echo __('Filter by Material:') ?></strong></span>
                <a href="#" class="btn btn-sm btn-outline-secondary active material-filter" data-material="all">
                    <?php echo __('All') ?>
                </a>
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
                        <i class="fas fa-star text-warning me-1" title="Default template"></i>
                        <?php endif ?>
                        <?php echo esc_entities($template->name) ?>
                    </h5>
                    <span class="badge bg-<?php echo $template->is_active ? 'success' : 'secondary' ?>">
                        <?php echo $template->is_active ? __('Active') : __('Inactive') ?>
                    </span>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        <?php echo esc_entities($template->description ?: 'No description') ?>
                    </p>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $template->material_type)) ?></span>
                        <span class="badge bg-light text-dark"><?php echo esc_entities($template->code) ?></span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for(['module' => 'ahgCondition', 'action' => 'template', 'template_action' => 'view', 'id' => $template->id]) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i> <?php echo __('View') ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'ahgCondition', 'action' => 'template', 'template_action' => 'export', 'id' => $template->id]) ?>" class="btn btn-sm btn-outline-secondary" download>
                        <i class="fas fa-download me-1"></i> <?php echo __('Export') ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <?php if (empty($templates)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('No templates available. Templates can be added by running the seed SQL script.') ?>
    </div>
    <?php endif ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filters = document.querySelectorAll('.material-filter');
    const cards = document.querySelectorAll('.template-card');
    
    filters.forEach(filter => {
        filter.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active state
            filters.forEach(f => f.classList.remove('active'));
            this.classList.add('active');
            
            const material = this.dataset.material;
            
            // Filter cards
            cards.forEach(card => {
                if (material === 'all' || card.dataset.material === material) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>
