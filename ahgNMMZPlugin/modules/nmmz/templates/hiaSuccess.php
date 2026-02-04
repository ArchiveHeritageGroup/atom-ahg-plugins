<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item active">Heritage Impact Assessments</li>
                </ol>
            </nav>
            <h1><i class="fas fa-clipboard-check me-2"></i>Heritage Impact Assessments</h1>
            <p class="text-muted">HIA submissions under NMMZ Act [Chapter 25:11]</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'hiaCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Assessment
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo 'pending' === $currentStatus ? 'selected' : ''; ?>>Pending Review</option>
                        <option value="approved" <?php echo 'approved' === $currentStatus ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo 'rejected' === $currentStatus ? 'selected' : ''; ?>>Rejected</option>
                        <option value="conditions" <?php echo 'conditions' === $currentStatus ? 'selected' : ''; ?>>Approved with Conditions</option>
                    </select>
                </div>
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- HIA Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($hias->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                    <p>No heritage impact assessments found.</p>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'hiaCreate']); ?>" class="btn btn-primary">Submit Assessment</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Reference #</th>
                            <th>Project Name</th>
                            <th>Developer</th>
                            <th>Province</th>
                            <th>Impact Level</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hias as $hia): ?>
                            <?php
                            $statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'conditions' => 'info'];
                            $impactColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($hia->reference_number ?? 'HIA-' . $hia->id); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars(substr($hia->project_name, 0, 30)); ?><?php echo strlen($hia->project_name) > 30 ? '...' : ''; ?></td>
                                <td><?php echo htmlspecialchars(substr($hia->developer_name ?? '', 0, 25)); ?></td>
                                <td><?php echo htmlspecialchars($hia->province ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $impactColors[$hia->impact_level] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($hia->impact_level ?? 'unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$hia->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($hia->status ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td><?php echo $hia->created_at ? date('Y-m-d', strtotime($hia->created_at)) : '-'; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" disabled>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
