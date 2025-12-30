<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewExhibition', 'id' => $exhibition->id]); ?>"><?php echo $exhibition->title; ?></a></li>
        <li class="breadcrumb-item active">Add Object</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-plus text-primary me-2"></i>Add Object to Exhibition</h1>
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="mb-3"><label class="form-label">Object ID *</label><input type="number" name="object_id" class="form-control" required><small class="text-muted">Enter the information object ID from the catalog</small></div>
            <div class="mb-3"><label class="form-label">Space</label>
                <select name="space_id" class="form-select">
                    <option value="">Select space...</option>
                    <?php foreach ($spaces as $s): ?><option value="<?php echo $s->id; ?>"><?php echo $s->name; ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Section</label><input type="text" name="section" class="form-control" placeholder="e.g., Introduction, Main Gallery, etc."></div>
            <div class="mb-3"><label class="form-label">Label Text</label><textarea name="label_text" class="form-control" rows="3"></textarea></div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewExhibition', 'id' => $exhibition->id]); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Object</button>
            </div>
        </form>
    </div>
</div>
