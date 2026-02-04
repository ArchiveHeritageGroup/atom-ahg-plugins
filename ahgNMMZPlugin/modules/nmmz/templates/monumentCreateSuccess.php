<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monuments']); ?>">National Monuments</a></li>
                    <li class="breadcrumb-item active">Register Monument</li>
                </ol>
            </nav>
            <h1><i class="fas fa-monument me-2"></i>Register National Monument</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Monument Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Monument Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>"><?php echo htmlspecialchars($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Historical Significance</label>
                            <textarea name="historical_significance" class="form-control" rows="3" placeholder="Describe the historical and cultural significance"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Location</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Province</label>
                            <select name="province" class="form-select">
                                <option value="">Select...</option>
                                <option value="Bulawayo">Bulawayo</option>
                                <option value="Harare">Harare</option>
                                <option value="Manicaland">Manicaland</option>
                                <option value="Mashonaland Central">Mashonaland Central</option>
                                <option value="Mashonaland East">Mashonaland East</option>
                                <option value="Mashonaland West">Mashonaland West</option>
                                <option value="Masvingo">Masvingo</option>
                                <option value="Matabeleland North">Matabeleland North</option>
                                <option value="Matabeleland South">Matabeleland South</option>
                                <option value="Midlands">Midlands</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">District</label>
                            <input type="text" name="district" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location Description</label>
                            <textarea name="location_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GPS Latitude</label>
                            <input type="text" name="gps_latitude" class="form-control" placeholder="-17.8252">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GPS Longitude</label>
                            <input type="text" name="gps_longitude" class="form-control" placeholder="31.0335">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legal & Protection -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Legal & Protection Status</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Protection Level</label>
                            <select name="protection_level" class="form-select">
                                <option value="national">National</option>
                                <option value="provincial">Provincial</option>
                                <option value="local">Local</option>
                                <option value="world_heritage">World Heritage</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Legal Status</label>
                            <select name="legal_status" class="form-select">
                                <option value="proposed">Proposed</option>
                                <option value="provisional">Provisional</option>
                                <option value="gazetted">Gazetted</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ownership Type</label>
                            <select name="ownership_type" class="form-select">
                                <option value="">Select...</option>
                                <option value="state">State</option>
                                <option value="private">Private</option>
                                <option value="communal">Communal</option>
                                <option value="church">Church/Religious</option>
                                <option value="mixed">Mixed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Condition Rating</label>
                            <select name="condition_rating" class="form-select">
                                <option value="">Select...</option>
                                <option value="excellent">Excellent</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Register Monument
                    </button>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monuments']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
