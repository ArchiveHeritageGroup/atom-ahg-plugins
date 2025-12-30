<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item active">Exhibitions</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-images text-primary me-2"></i>Exhibitions</h1>
    <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'createExhibition']); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Exhibition</a>
</div>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['planning', 'confirmed', 'installing', 'open', 'closing', 'closed', 'cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $currentStatus === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Title</th><th>Type</th><th>Venue</th><th>Dates</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($exhibitions)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No exhibitions found</td></tr>
                <?php else: ?>
                    <?php foreach ($exhibitions as $e): ?>
                        <tr>
                            <td><strong><?php echo $e->title; ?></strong><?php if ($e->subtitle): ?><br><small class="text-muted"><?php echo $e->subtitle; ?></small><?php endif; ?></td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($e->exhibition_type); ?></span></td>
                            <td><?php echo $e->venue_name ?: '-'; ?></td>
                            <td><?php echo $e->start_date ? $e->start_date . ' - ' . $e->end_date : '-'; ?></td>
                            <td><?php
                                $colors = ['planning' => 'info', 'confirmed' => 'primary', 'installing' => 'warning', 'open' => 'success', 'closing' => 'warning', 'closed' => 'secondary', 'cancelled' => 'danger'];
                                echo '<span class="badge bg-' . ($colors[$e->status] ?? 'secondary') . '">' . ucfirst($e->status) . '</span>';
                            ?></td>
                            <td><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewExhibition', 'id' => $e->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
