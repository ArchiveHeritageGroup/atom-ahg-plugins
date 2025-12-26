<?php use_helper('Display'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'display', 'action' => 'index']); ?>">Display</a></li>
        <li class="breadcrumb-item active">Levels of Description</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-layer-group text-primary me-2"></i>Levels of Description</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <select name="domain" class="form-select">
                    <option value="">All Domains</option>
                    <?php foreach ($domains as $d): ?>
                    <option value="<?php echo $d; ?>" <?php echo $currentDomain === $d ? 'selected' : ''; ?>>
                        <?php echo ucfirst($d); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="50"></th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Domain</th>
                    <th>Valid Parents</th>
                    <th>Valid Children</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($levels as $l): ?>
                <tr>
                    <td class="text-center">
                        <i class="fas <?php echo $l->icon ?: 'fa-file'; ?> text-muted"></i>
                    </td>
                    <td><strong><?php echo $l->name; ?></strong></td>
                    <td><code><?php echo $l->code; ?></code></td>
                    <td>
                        <span class="badge bg-<?php echo get_type_color($l->domain); ?>">
                            <?php echo ucfirst($l->domain); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $parents = json_decode($l->valid_parent_codes ?? '[]', true);
                        if (!empty($parents)): ?>
                            <small><?php echo implode(', ', $parents); ?></small>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $children = json_decode($l->valid_child_codes ?? '[]', true);
                        if (!empty($children)): ?>
                            <small><?php echo implode(', ', $children); ?></small>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
