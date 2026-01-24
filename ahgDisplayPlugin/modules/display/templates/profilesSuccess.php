<?php use_helper('Display'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'display', 'action' => 'index']); ?>">Display</a></li>
        <li class="breadcrumb-item active">Profiles</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-palette text-primary me-2"></i>Display Profiles</h1>

<div class="row">
    <?php foreach ($profiles as $p): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas <?php echo get_layout_icon($p->layout_mode); ?> me-2"></i>
                    <?php echo $p->name; ?>
                </span>
                <span class="badge bg-<?php echo get_type_color($p->domain ?? 'universal'); ?>">
                    <?php echo ucfirst($p->domain ?? 'universal'); ?>
                </span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted" width="120">Code:</th>
                        <td><code><?php echo $p->code; ?></code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Layout:</th>
                        <td><?php echo ucfirst($p->layout_mode); ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Thumbnail:</th>
                        <td><?php echo ucfirst($p->thumbnail_size); ?> (<?php echo $p->thumbnail_position; ?>)</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Default:</th>
                        <td><?php echo $p->is_default ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                    </tr>
                </table>
                
                <?php if ($p->description): ?>
                <hr>
                <small class="text-muted"><?php echo $p->description; ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
