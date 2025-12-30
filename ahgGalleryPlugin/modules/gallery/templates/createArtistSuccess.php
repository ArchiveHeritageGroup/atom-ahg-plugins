<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'artists']); ?>">Artists</a></li>
        <li class="breadcrumb-item active">New Artist</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-plus text-primary me-2"></i>New Artist</h1>
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-user me-2"></i>Basic Information</h5>
                    <div class="mb-3"><label class="form-label">Display Name *</label><input type="text" name="display_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Sort Name</label><input type="text" name="sort_name" class="form-control" placeholder="e.g., Lastname, Firstname"></div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Birth Date</label><input type="date" name="birth_date" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Birth Place</label><input type="text" name="birth_place" class="form-control"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Death Date</label><input type="date" name="death_date" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Nationality</label><input type="text" name="nationality" class="form-control"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Artist Type</label>
                        <select name="artist_type" class="form-select">
                            <option value="individual">Individual</option>
                            <option value="collective">Collective</option>
                            <option value="studio">Studio</option>
                            <option value="anonymous">Anonymous</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-palette me-2"></i>Professional</h5>
                    <div class="mb-3"><label class="form-label">Medium Specialty</label><textarea name="medium_specialty" class="form-control" rows="2" placeholder="e.g., Oil painting, Sculpture, Photography"></textarea></div>
                    <div class="mb-3"><label class="form-label">Biography</label><textarea name="biography" class="form-control" rows="3"></textarea></div>
                    <div class="form-check mb-3"><input type="checkbox" name="represented" class="form-check-input" id="rep"><label class="form-check-label" for="rep">Represented Artist</label></div>
                    <h5 class="mb-3"><i class="fas fa-address-card me-2"></i>Contact</h5>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Website</label><input type="url" name="website" class="form-control"></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'artists']); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Artist</button>
            </div>
        </form>
    </div>
</div>
