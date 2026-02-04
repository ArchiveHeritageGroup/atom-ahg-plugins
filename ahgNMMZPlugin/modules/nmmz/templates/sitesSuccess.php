<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item active">Archaeological Sites</li>
                </ol>
            </nav>
            <h1><i class="fas fa-map-marker-alt me-2"></i>Archaeological Sites</h1>
            <p class="text-muted">Protected archaeological sites under NMMZ Act [Chapter 25:11]</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'siteCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Register Site
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="province" class="form-select">
                        <option value="">All Provinces</option>
                        <option value="Bulawayo">Bulawayo</option>
                        <option value="Harare">Harare</option>
                        <option value="Manicaland">Manicaland</option>
                        <option value="Mashonaland Central">Mashonaland Central</option>
                        <option value="Mashonaland East">Mashonaland East</option>
                        <option value="Mashonaland West">Mashonaland West</option>
                        <option value="Masvingo">Masvingo</option>
                        <option value="Matabeleland North">Matabeleland North</option>
                        <option value="Matabeleland South">Matabeleland South</option>
                        <option value="Midlands">Midlands</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="protected" <?php echo 'protected' === $currentStatus ? 'selected' : ''; ?>>Protected</option>
                        <option value="proposed" <?php echo 'proposed' === $currentStatus ? 'selected' : ''; ?>>Proposed</option>
                        <option value="at_risk" <?php echo 'at_risk' === $currentStatus ? 'selected' : ''; ?>>At Risk</option>
                        <option value="destroyed" <?php echo 'destroyed' === $currentStatus ? 'selected' : ''; ?>>Destroyed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sites Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($sites->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                    <p>No archaeological sites found.</p>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'siteCreate']); ?>" class="btn btn-primary">Register First Site</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Site ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Province</th>
                            <th>Period</th>
                            <th>Protection Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites as $site): ?>
                            <?php
                            $statusColors = ['protected' => 'success', 'proposed' => 'info', 'at_risk' => 'warning', 'destroyed' => 'danger'];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'siteView', 'id' => $site->id]); ?>">
                                        <?php echo htmlspecialchars($site->site_number ?? 'SITE-' . $site->id); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars(substr($site->name, 0, 40)); ?><?php echo strlen($site->name) > 40 ? '...' : ''; ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($site->site_type ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars($site->province ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($site->period ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$site->protection_status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $site->protection_status ?? 'unknown')); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'siteView', 'id' => $site->id]); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
