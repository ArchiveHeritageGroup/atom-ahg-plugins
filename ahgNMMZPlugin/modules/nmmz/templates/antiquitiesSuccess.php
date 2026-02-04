<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item active">Antiquities</li>
                </ol>
            </nav>
            <h1><i class="fas fa-vase me-2"></i>Antiquities Register</h1>
            <p class="text-muted">Objects over 100 years old protected under NMMZ Act [Chapter 25:11]</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquityCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Register Antiquity
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="ceramic">Ceramic</option>
                        <option value="stone">Stone</option>
                        <option value="metal">Metal</option>
                        <option value="bone">Bone/Ivory</option>
                        <option value="textile">Textile</option>
                        <option value="wooden">Wooden</option>
                        <option value="document">Document</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="in_collection" <?php echo 'in_collection' === $currentStatus ? 'selected' : ''; ?>>In Collection</option>
                        <option value="on_loan" <?php echo 'on_loan' === $currentStatus ? 'selected' : ''; ?>>On Loan</option>
                        <option value="missing" <?php echo 'missing' === $currentStatus ? 'selected' : ''; ?>>Missing</option>
                        <option value="exported" <?php echo 'exported' === $currentStatus ? 'selected' : ''; ?>>Exported</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" placeholder="Search...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Antiquities Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($antiquities->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-vase fa-3x mb-3"></i>
                    <p>No antiquities found.</p>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquityCreate']); ?>" class="btn btn-primary">Register First Antiquity</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Accession #</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Material</th>
                            <th>Estimated Age</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($antiquities as $a): ?>
                            <?php
                            $conditionColors = ['excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'danger', 'fragmentary' => 'dark'];
                            $statusColors = ['in_collection' => 'success', 'on_loan' => 'info', 'missing' => 'danger', 'exported' => 'warning'];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquityView', 'id' => $a->id]); ?>">
                                        <?php echo htmlspecialchars($a->accession_number ?? 'ANT-' . $a->id); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars(substr($a->name, 0, 35)); ?><?php echo strlen($a->name) > 35 ? '...' : ''; ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($a->object_type ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars($a->material ?? '-'); ?></td>
                                <td><?php echo $a->estimated_age_years ? $a->estimated_age_years . ' years' : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $conditionColors[$a->condition_rating] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($a->condition_rating ?? 'unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$a->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $a->status ?? 'unknown')); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquityView', 'id' => $a->id]); ?>" class="btn btn-sm btn-outline-primary">
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
