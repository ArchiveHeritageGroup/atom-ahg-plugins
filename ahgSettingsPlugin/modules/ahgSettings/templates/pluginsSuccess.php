<?php slot('title', __('Plugin Management')); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0"><i class="fas fa-puzzle-piece"></i> Plugin Management</h1>
    <a href="/index.php/admin/ahg-settings" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to AHG Settings'); ?>
    </a>
</div>

<!-- Flash messages handled by layout _alerts.php -->

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <div class="row align-items-center">
            <div class="col-auto">
                <strong><i class="fas fa-filter me-2"></i>Category</strong>
                <div class="btn-group btn-group-sm ms-2" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                    <?php foreach ($categories as $key => $cat): ?>
                    <button type="button" class="btn btn-outline-<?php echo $cat['class']; ?>" data-filter="<?php echo $key; ?>">
                        <i class="fas <?php echo $cat['icon']; ?> me-1"></i><?php echo $cat['label']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-auto">
                <strong><i class="fas fa-toggle-on me-2"></i>Status</strong>
                <div class="btn-group btn-group-sm ms-2" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-status="all">All</button>
                    <button type="button" class="btn btn-outline-success" data-status="enabled">
                        <i class="fas fa-check me-1"></i>Enabled
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-status="disabled">
                        <i class="fas fa-times me-1"></i>Disabled
                    </button>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <?php
                    $enabledCount = 0;
                    $disabledCount = 0;
                    foreach ($plugins as $p) {
                        if (!empty($p['is_enabled'])) { $enabledCount++; } else { $disabledCount++; }
                    }
                ?>
                <span class="badge bg-success"><?php echo $enabledCount; ?> Enabled</span>
                <span class="badge bg-secondary"><?php echo $disabledCount; ?> Disabled</span>
                <span class="badge bg-primary"><?php echo count($plugins); ?> Total</span>
            </div>
        </div>
    </div>
</div>

<div class="row" id="plugins-grid">
    <?php if (empty($plugins)): ?>
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No plugins found in database.
            Run <code>php bin/atom extension:discover</code> to see available plugins.
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($plugins as $plugin): ?>
    <?php
        $isEnabled = !empty($plugin['is_enabled']);
        $category = $plugin['category'] ?? 'other';
        $catInfo = $categories[$category] ?? $categories['other'];
    ?>
    <div class="col-lg-4 col-md-6 mb-4 plugin-card"
         data-category="<?php echo $category; ?>"
         data-status="<?php echo $isEnabled ? 'enabled' : 'disabled'; ?>">
        <div class="card h-100 <?php echo $isEnabled ? '' : 'border-secondary opacity-75'; ?>">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="badge bg-<?php echo $catInfo['class']; ?>">
                    <i class="fas <?php echo $catInfo['icon']; ?> me-1"></i><?php echo $catInfo['label']; ?>
                </span>
                <span class="badge <?php echo $isEnabled ? 'bg-success' : 'bg-secondary'; ?>">
                    <?php echo $isEnabled ? 'Enabled' : 'Disabled'; ?>
                </span>
            </div>
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-plug me-2 text-muted"></i>
                    <?php echo htmlspecialchars($plugin['name']); ?>
                </h5>
                <p class="card-text text-muted small">
                    <?php echo htmlspecialchars($plugin['description'] ?? 'No description available'); ?>
                </p>
                <?php if (!empty($plugin['version'])): ?>
                <small class="text-muted">
                    <i class="fas fa-code-branch me-1"></i>v<?php echo htmlspecialchars($plugin['version']); ?>
                </small>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <form method="post" action="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'plugins']); ?>" class="d-inline">
                    <input type="hidden" name="plugin_name" value="<?php echo htmlspecialchars($plugin['name']); ?>">
                    <?php $isLocked = !empty($plugin['is_locked']); ?>
                    <?php $isCore = !empty($plugin['is_core']); ?>
                    <?php if ($isCore): ?>
                    <span class="badge bg-primary"><i class="fas fa-shield-alt me-1"></i>Core</span>
                    <?php elseif ($isLocked): ?>
                    <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Locked</span>
                    <?php elseif ($isEnabled): ?>
                    <button type="submit" name="plugin_action" value="disable"
                            class="btn btn-sm btn-outline-danger btn-plugin-disable"
                            data-plugin-name="<?php echo htmlspecialchars($plugin['name']); ?>">
                        <i class="fas fa-power-off me-1"></i>Disable
                    </button>
                    <?php else: ?>
                    <button type="submit" name="plugin_action" value="enable"
                            class="btn btn-sm btn-success">
                        <i class="fas fa-check me-1"></i>Enable
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($plugin['record_check_query']) && $isEnabled): ?>
                    <span class="badge bg-info ms-1" title="This plugin has a record check query — cannot be disabled if records exist"><i class="fas fa-database me-1"></i>Record-linked</span>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var activeCategory = 'all';
    var activeStatus = 'all';
    var cards = document.querySelectorAll('.plugin-card');

    function filterCards() {
        cards.forEach(function(card) {
            var catMatch = (activeCategory === 'all' || card.dataset.category === activeCategory);
            var statusMatch = (activeStatus === 'all' || card.dataset.status === activeStatus);
            card.style.display = (catMatch && statusMatch) ? '' : 'none';
        });
    }

    // Category filter buttons
    document.querySelectorAll('.card-header [data-filter]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.card-header [data-filter]').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            activeCategory = btn.dataset.filter;
            filterCards();
        });
    });

    // Status filter buttons (only target filter bar buttons, not card data-status attributes)
    document.querySelectorAll('.card-header [data-status]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.card-header [data-status]').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            activeStatus = btn.dataset.status;
            filterCards();
        });
    });

    // Disable button confirmation (avoid inline onclick which CSP blocks)
    document.querySelectorAll('.btn-plugin-disable').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Disable ' + btn.dataset.pluginName + '?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
