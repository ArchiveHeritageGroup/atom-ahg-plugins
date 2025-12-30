<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item active">Artists</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-user-circle text-primary me-2"></i>Artists</h1>
    <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'createArtist']); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Artist</a>
</div>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Search name..." value="<?php echo $sf_request->getParameter('q'); ?>"></div>
            <div class="col-md-3">
                <select name="represented" class="form-select">
                    <option value="">All Artists</option>
                    <option value="1" <?php echo $sf_request->getParameter('represented') === '1' ? 'selected' : ''; ?>>Represented Only</option>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Name</th><th>Nationality</th><th>Medium</th><th>Represented</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($artists)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No artists found</td></tr>
                <?php else: ?>
                    <?php foreach ($artists as $a): ?>
                        <tr>
                            <td><strong><?php echo $a->display_name; ?></strong><?php if ($a->birth_date || $a->death_date): ?><br><small class="text-muted"><?php echo ($a->birth_date ? date('Y', strtotime($a->birth_date)) : '?') . ' - ' . ($a->death_date ? date('Y', strtotime($a->death_date)) : ''); ?></small><?php endif; ?></td>
                            <td><?php echo $a->nationality ?: '-'; ?></td>
                            <td><?php echo $a->medium_specialty ? substr($a->medium_specialty, 0, 50) : '-'; ?></td>
                            <td><?php echo $a->represented ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                            <td><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewArtist', 'id' => $a->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
