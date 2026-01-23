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
            <div class="col">
                <strong><i class="fas fa-filter me-2"></i>Filter by Category</strong>
            </div>
            <div class="col-auto">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                    <?php foreach ($categories as $key => $cat): ?>
                    <button type="button" class="btn btn-outline-<?php echo $cat['class']; ?>" data-filter="<?php echo $key; ?>">
                        <i class="fas <?php echo $cat['icon']; ?> me-1"></i><?php echo $cat['label']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="plugins-grid">
    <?php if (empty($plugins)): ?>
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No plugins found in database. 
            Run <code>php symfony plugin:manage sync</code> to synchronize plugins.
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($plugins as $plugin): ?>
    <?php 
        $isEnabled = !empty($plugin['is_enabled']);
        $category = $plugin['category'] ?? 'other';
        $catInfo = $categories[$category] ?? $categories['other'];
    ?>
    <div class="col-lg-4 col-md-6 mb-4 plugin-card" data-category="<?php echo $category; ?>">
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
                <form method="post" class="d-inline">
                    <input type="hidden" name="plugin_name" value="<?php echo htmlspecialchars($plugin['name']); ?>">
                    <?php $isLocked = !empty($plugin['is_locked']); ?>
                    <?php if ($isLocked): ?>
                    <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Locked</span>
                    <?php elseif ($isEnabled): ?>
                    <button type="submit" name="plugin_action" value="disable"
                            class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Disable <?php echo htmlspecialchars($plugin['name']); ?>?');">
                        <i class="fas fa-power-off me-1"></i>Disable
                    </button>
                    <?php else: ?>
                    <button type="submit" name="plugin_action" value="enable"
                            class="btn btn-sm btn-success">
                        <i class="fas fa-check me-1"></i>Enable
                    </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="/plugins/ahgCorePlugin/js/vendor/plugin-filter.js"></script>
