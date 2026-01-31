<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item active">Researcher Registry</li>
                </ol>
            </nav>
            <h1><i class="fas fa-users me-2"></i>Researcher Registry</h1>
            <p class="text-muted">Registered researchers and their permit history</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researcherCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Register Researcher
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="btn-group">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researchers']); ?>"
                           class="btn btn-<?php echo !$currentType ? 'primary' : 'outline-primary'; ?>">All</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researchers', 'type' => 'local']); ?>"
                           class="btn btn-<?php echo 'local' === $currentType ? 'success' : 'outline-success'; ?>">Local</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researchers', 'type' => 'foreign']); ?>"
                           class="btn btn-<?php echo 'foreign' === $currentType ? 'info' : 'outline-info'; ?>">Foreign</a>
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researchers', 'type' => 'institutional']); ?>"
                           class="btn btn-<?php echo 'institutional' === $currentType ? 'secondary' : 'outline-secondary'; ?>">Institutional</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <form method="get" class="d-flex">
                        <input type="text" name="q" class="form-control me-2" placeholder="Search researchers..."
                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Researchers Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($researchers->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <p>No researchers found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Institution</th>
                            <th>Nationality</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($researchers as $researcher): ?>
                            <?php
                            $typeColors = [
                                'local' => 'success',
                                'foreign' => 'info',
                                'institutional' => 'secondary',
                            ];
                            $statusColors = [
                                'active' => 'success',
                                'inactive' => 'secondary',
                                'suspended' => 'warning',
                                'blacklisted' => 'danger',
                            ];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researcherView', 'id' => $researcher->id]); ?>">
                                        <?php echo htmlspecialchars($researcher->title ? $researcher->title.' ' : ''); ?>
                                        <?php echo htmlspecialchars($researcher->first_name.' '.$researcher->last_name); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $typeColors[$researcher->researcher_type] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($researcher->researcher_type); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($researcher->email); ?></td>
                                <td><?php echo htmlspecialchars($researcher->institution ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($researcher->nationality ?? '-'); ?></td>
                                <td><?php echo $researcher->registration_date; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $statusColors[$researcher->status] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($researcher->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researcherView', 'id' => $researcher->id]); ?>"
                                       class="btn btn-sm btn-outline-primary">
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
