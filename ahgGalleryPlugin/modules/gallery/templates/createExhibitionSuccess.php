<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'exhibitions']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item active">New Exhibition</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-plus text-primary me-2"></i>New Exhibition</h1>
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Subtitle</label><input type="text" name="subtitle" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"></textarea></div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3"><label class="form-label">Type</label>
                        <select name="exhibition_type" class="form-select">
                            <option value="temporary">Temporary</option>
                            <option value="permanent">Permanent</option>
                            <option value="traveling">Traveling</option>
                            <option value="virtual">Virtual</option>
                            <option value="pop-up">Pop-up</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Curator</label><input type="text" name="curator" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Venue</label>
                        <select name="venue_id" class="form-select">
                            <option value="">Select venue...</option>
                            <?php foreach ($venues as $v): ?>
                                <option value="<?php echo $v->id; ?>"><?php echo $v->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div>
                        <div class="col-6"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Budget (ZAR)</label><input type="number" name="budget" class="form-control" step="0.01"></div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'exhibitions']); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Exhibition</button>
            </div>
        </form>
    </div>
</div>
