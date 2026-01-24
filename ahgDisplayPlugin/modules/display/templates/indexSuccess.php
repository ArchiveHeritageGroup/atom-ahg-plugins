<?php use_helper('Display'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'admin', 'action' => 'index']); ?>">Admin</a></li>
        <li class="breadcrumb-item active">Display Configuration</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-desktop text-primary me-2"></i>Display Configuration</h1>

<?php if ($sf_user->hasFlash('success')): ?>
<div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <h3 class="mb-0"><?php echo number_format($stats['total_objects']); ?></h3>
                <small>Total Objects</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <h3 class="mb-0"><?php echo number_format($stats['configured_objects']); ?></h3>
                <small>Configured Objects</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <h3 class="mb-0"><?php echo count($profiles); ?></h3>
                <small>Display Profiles</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body text-center">
                <h3 class="mb-0"><?php echo count($levels); ?></h3>
                <small>Level Types</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Quick Links</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?php echo url_for(['module' => 'display', 'action' => 'profiles']); ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-palette me-2"></i>Display Profiles
                </a>
                <a href="<?php echo url_for(['module' => 'display', 'action' => 'levels']); ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-layer-group me-2"></i>Levels of Description
                </a>
                <a href="<?php echo url_for(['module' => 'display', 'action' => 'fields']); ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-list me-2"></i>Field Mappings
                </a>
                <a href="<?php echo url_for(['module' => 'display', 'action' => 'bulkSetType']); ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-tags me-2"></i>Bulk Set Object Types
                </a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>By Type</h5>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($stats['by_type'] as $t): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fas <?php echo get_type_icon($t->object_type); ?> me-2 text-<?php echo get_type_color($t->object_type); ?>"></i><?php echo ucfirst($t->object_type); ?></span>
                    <span class="badge bg-<?php echo get_type_color($t->object_type); ?>"><?php echo $t->count; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Display Profiles by Domain</h5>
            </div>
            <div class="card-body">
                <?php 
                $grouped = [];
                foreach ($profiles as $p) {
                    $grouped[$p->domain ?? 'universal'][] = $p;
                }
                ?>
                <div class="row">
                    <?php foreach ($grouped as $domain => $domainProfiles): ?>
                    <div class="col-md-4 mb-3">
                        <h6 class="text-<?php echo get_type_color($domain); ?>">
                            <i class="fas <?php echo get_type_icon($domain); ?> me-1"></i>
                            <?php echo ucfirst($domain); ?>
                        </h6>
                        <ul class="list-unstyled small">
                            <?php foreach ($domainProfiles as $p): ?>
                            <li>
                                <i class="fas <?php echo get_layout_icon($p->layout_mode); ?> text-muted me-1"></i>
                                <?php echo $p->name; ?>
                                <?php if ($p->is_default): ?><span class="badge bg-primary">Default</span><?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-th-list me-2"></i>Collection Types</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($collectionTypes as $ct): ?>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas <?php echo $ct->icon; ?> fa-2x text-muted me-3"></i>
                            <div>
                                <strong><?php echo $ct->name; ?></strong>
                                <?php if ($ct->description): ?>
                                <br><small class="text-muted"><?php echo $ct->description; ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
