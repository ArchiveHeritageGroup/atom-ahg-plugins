<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'venues']); ?>">Venues</a></li>
        <li class="breadcrumb-item active">New Venue</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-plus text-primary me-2"></i>New Venue</h1>
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Total Area (m²)</label><input type="number" name="total_area_sqm" class="form-control" step="0.01"></div>
                        <div class="col-6"><label class="form-label">Max Capacity</label><input type="number" name="max_capacity" class="form-control"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">Security Level</label>
                        <select name="security_level" class="form-select">
                            <option value="">Select...</option>
                            <option value="basic">Basic</option>
                            <option value="standard">Standard</option>
                            <option value="high">High</option>
                            <option value="maximum">Maximum</option>
                        </select>
                    </div>
                    <div class="form-check mb-3"><input type="checkbox" name="climate_controlled" class="form-check-input" id="climate"><label class="form-check-label" for="climate">Climate Controlled</label></div>
                    <h5 class="mt-4">Contact</h5>
                    <div class="mb-3"><label class="form-label">Contact Name</label><input type="text" name="contact_name" class="form-control"></div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Email</label><input type="email" name="contact_email" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Phone</label><input type="tel" name="contact_phone" class="form-control"></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'venues']); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Venue</button>
            </div>
        </form>
    </div>
</div>
