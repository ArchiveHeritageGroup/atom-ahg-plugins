<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item active">National Monuments</li>
                </ol>
            </nav>
            <h1><i class="fas fa-monument me-2"></i>National Monuments</h1>
            <p class="text-muted">Protected heritage sites under NMMZ Act [Chapter 25:11]</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monumentCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Register Monument
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>" <?php echo $currentCategory == $cat->id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo 'active' === $currentStatus ? 'selected' : ''; ?>>Active</option>
                        <option value="at_risk" <?php echo 'at_risk' === $currentStatus ? 'selected' : ''; ?>>At Risk</option>
                        <option value="destroyed" <?php echo 'destroyed' === $currentStatus ? 'selected' : ''; ?>>Destroyed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" placeholder="Search..." value="">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Monuments Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($monuments->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-monument fa-3x mb-3"></i>
                    <p>No monuments found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Monument #</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Province</th>
                            <th>Legal Status</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monuments as $m): ?>
                            <?php
                            $legalColors = ['gazetted' => 'success', 'provisional' => 'info', 'proposed' => 'warning', 'delisted' => 'danger'];
                            $conditionColors = ['excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'danger', 'critical' => 'dark'];
                            $statusColors = ['active' => 'success', 'at_risk' => 'warning', 'destroyed' => 'danger'];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monumentView', 'id' => $m->id]); ?>">
                                        <?php echo htmlspecialchars($m->monument_number); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars(substr($m->name, 0, 40)); ?><?php echo strlen($m->name) > 40 ? '...' : ''; ?></td>
                                <td><?php echo htmlspecialchars($m->category_name ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($m->province ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $legalColors[$m->legal_status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($m->legal_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $conditionColors[$m->condition_rating] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($m->condition_rating); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$m->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $m->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monumentView', 'id' => $m->id]); ?>" class="btn btn-sm btn-outline-primary">
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
