<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'sites']); ?>">Archaeological Sites</a></li>
                    <li class="breadcrumb-item active">Register Site</li>
                </ol>
            </nav>
            <h1><i class="fas fa-map-marker-alt me-2"></i>Register Archaeological Site</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <!-- Basic Information -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Site Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Site Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Site Type</label>
                            <select name="site_type" class="form-select">
                                <option value="">Select...</option>
                                <option value="rock_art">Rock Art</option>
                                <option value="settlement">Settlement</option>
                                <option value="burial">Burial Site</option>
                                <option value="industrial">Industrial</option>
                                <option value="religious">Religious/Ceremonial</option>
                                <option value="cave">Cave/Shelter</option>
                                <option value="iron_age">Iron Age</option>
                                <option value="stone_age">Stone Age</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Period</label>
                            <input type="text" name="period" class="form-control" placeholder="e.g., Late Stone Age, Iron Age">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Research Potential</label>
                            <select name="research_potential" class="form-select">
                                <option value="">Select...</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                                <option value="unknown">Unknown</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

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

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Discovery Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Discovery Date</label>
                            <input type="date" name="discovery_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discovered By</label>
                            <input type="text" name="discovered_by" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Actions -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Protection Status</h5></div>
                <div class="card-body">
                    <select name="protection_status" class="form-select">
                        <option value="proposed">Proposed</option>
                        <option value="protected">Protected</option>
                        <option value="at_risk">At Risk</option>
                    </select>
                </div>
            </div>

            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Register Site
                    </button>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'sites']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
