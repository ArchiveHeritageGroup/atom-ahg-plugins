<?php use_helper('Date'); ?>
<?php $rawClasses = $sf_data->getRaw('classes'); $rawStandards = $sf_data->getRaw('standards'); ?>
<?php slot('title') ?><?php echo __('Heritage Assets') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">
                <i class="fas fa-landmark me-2"></i><?php echo __('Heritage Assets') ?>
            </h1>
            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'add']) ?>" class="btn btn-success">
                <i class="fas fa-plus me-2"></i><?php echo __('Add Asset') ?>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('Search') ?></label>
                    <div class="position-relative"><input type="text" name="sq" id="heritageSearch" class="form-control" value="<?php echo esc_entities($filters['search'] ?? '') ?>" placeholder="<?php echo __('Identifier, title, donor...') ?>" autocomplete="off"><div id="heritageResults" class="autocomplete-dropdown"></div></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo __('Standard') ?></label>
                    <select name="standard_id" class="form-select">
                        <option value=""><?php echo __('All Standards') ?></option>
                        <?php foreach ($rawStandards as $s): ?>
                            <option value="<?php echo $s->id ?>" <?php echo ($filters['standard_id'] ?? '') == $s->id ? 'selected' : '' ?>><?php echo esc_entities($s->code) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo __('Class') ?></label>
                    <select name="class_id" class="form-select">
                        <option value=""><?php echo __('All Classes') ?></option>
                        <?php foreach ($rawClasses as $c): ?>
                            <option value="<?php echo $c->id ?>" <?php echo ($filters['class_id'] ?? '') == $c->id ? 'selected' : '' ?>><?php echo htmlspecialchars_decode($c->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo __('Status') ?></label>
                    <select name="status" class="form-select">
                        <option value=""><?php echo __('All Statuses') ?></option>
                        <option value="recognised" <?php echo ($filters['recognition_status'] ?? '') == 'recognised' ? 'selected' : '' ?>><?php echo __('Recognised') ?></option>
                        <option value="not_recognised" <?php echo ($filters['recognition_status'] ?? '') == 'not_recognised' ? 'selected' : '' ?>><?php echo __('Not Recognised') ?></option>
                        <option value="pending" <?php echo ($filters['recognition_status'] ?? '') == 'pending' ? 'selected' : '' ?>><?php echo __('Pending') ?></option>
                        <option value="derecognised" <?php echo ($filters['recognition_status'] ?? '') == 'derecognised' ? 'selected' : '' ?>><?php echo __('Derecognised') ?></option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search me-1"></i><?php echo __('Filter') ?></button>
                    <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'browse']) ?>" class="btn btn-outline-secondary"><?php echo __('Clear') ?></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span><?php echo __('Showing %1% of %2% assets', ['%1%' => count($assets), '%2%' => $total]) ?></span>
            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']) ?>" class="btn btn-sm btn-light">
                <i class="fas fa-chart-pie me-1"></i><?php echo __('Dashboard') ?>
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($assets)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo __('Identifier') ?></th>
                                <th><?php echo __('Title') ?></th>
                                <th><?php echo __('Class') ?></th>
                                <th><?php echo __('Standard') ?></th>
                                <th><?php echo __('Status') ?></th>
                                <th><?php echo __('Measurement') ?></th>
                                <th class="text-end"><?php echo __('Carrying Amount') ?></th>
                                <th><?php echo __('Last Valuation') ?></th>
                                <th class="text-center"><?php echo __('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $asset): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) ?>">
                                            <?php echo esc_entities($asset->object_identifier ?: 'N/A') ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_entities($asset->object_title ?: '-') ?></td>
                                    <td><span class="badge bg-secondary"><?php echo esc_entities($asset->class_name ?: '-') ?></span></td>
                                    <td><?php echo esc_entities($asset->standard_code ?: '-') ?></td>
                                    <td>
                                        <?php
                                        $statusColors = ['recognised' => 'success', 'not_recognised' => 'secondary', 'pending' => 'warning', 'derecognised' => 'danger'];
                                        $color = $statusColors[$asset->recognition_status] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color ?>"><?php echo ucfirst(str_replace('_', ' ', $asset->recognition_status)) ?></span>
                                    </td>
                                    <td><?php echo ucfirst($asset->measurement_basis ?: '-') ?></td>
                                    <td class="text-end fw-bold"><?php echo number_format($asset->current_carrying_amount, 2) ?></td>
                                    <td><?php echo $asset->last_valuation_date ? format_date($asset->last_valuation_date, 'D') : '-' ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) ?>" class="btn btn-outline-primary" title="<?php echo __('View') ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'edit', 'id' => $asset->id]) ?>" class="btn btn-outline-warning" title="<?php echo __('Edit') ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total > $limit): ?>
                    <nav class="p-3">
                        <ul class="pagination justify-content-center mb-0">
                            <?php $totalPages = ceil($total / $limit); ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?php echo $i ?>&sq=<?php echo urlencode($filters['search'] ?? '') ?>&standard_id=<?php echo $filters['standard_id'] ?? '' ?>&class_id=<?php echo $filters['class_id'] ?? '' ?>&status=<?php echo $filters['recognition_status'] ?? '' ?>"><?php echo $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <p class="text-muted"><?php echo __('No heritage assets found matching your criteria.') ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<style>
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 250px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.autocomplete-dropdown .ac-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}
.autocomplete-dropdown .ac-item:hover {
    background-color: #f5f5f5;
}
.autocomplete-dropdown .ac-item:last-child {
    border-bottom: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('heritageSearch');
    var resultsDiv = document.getElementById('heritageResults');
    var debounceTimer;
    
    if (!searchInput || !resultsDiv) return;
    
    searchInput.addEventListener('input', function() {
        var query = this.value.trim();
        clearTimeout(debounceTimer);
        
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            resultsDiv.innerHTML = '';
            return;
        }
        
        debounceTimer = setTimeout(function() {
            fetch('<?php echo url_for(["module" => "heritageApi", "action" => "autocomplete"]) ?>?term=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.length === 0) {
                        resultsDiv.style.display = 'none';
                        return;
                    }
                    resultsDiv.innerHTML = data.map(function(item) {
                        return '<div class="ac-item" data-label="' + (item.title || item.label).replace(/"/g, '&quot;') + '">' + item.label + '</div>';
                    }).join('');
                    resultsDiv.style.display = 'block';
                })
                .catch(function() { resultsDiv.style.display = 'none'; });
        }, 300);
    });
    
    resultsDiv.addEventListener('click', function(e) {
        if (e.target.classList.contains('ac-item')) {
            searchInput.value = e.target.dataset.label;
            resultsDiv.style.display = 'none';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#heritageSearch') && !e.target.closest('#heritageResults')) {
            resultsDiv.style.display = 'none';
        }
    });
});
</script>
