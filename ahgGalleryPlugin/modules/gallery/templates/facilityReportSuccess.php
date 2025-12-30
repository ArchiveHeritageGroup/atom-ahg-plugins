<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewLoan', 'id' => $loan->id]); ?>"><?php echo $loan->loan_number; ?></a></li>
        <li class="breadcrumb-item active">Facility Report</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-clipboard-list text-primary me-2"></i>Facility Report</h1>
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-building me-2"></i>Building</h5>
                    <div class="mb-3"><label class="form-label">Institution Name</label><input type="text" name="institution_name" class="form-control" value="<?php echo $loan->institution_name; ?>"></div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Building Age (years)</label><input type="number" name="building_age" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Construction Type</label><input type="text" name="construction_type" class="form-control"></div>
                    </div>
                    <h6 class="mt-4">Security</h6>
                    <div class="row">
                        <div class="col-6"><div class="form-check mb-2"><input type="checkbox" name="security_24hr" class="form-check-input" id="sec24"><label class="form-check-label" for="sec24">24-Hour Security</label></div></div>
                        <div class="col-6"><div class="form-check mb-2"><input type="checkbox" name="security_guards" class="form-check-input" id="secguard"><label class="form-check-label" for="secguard">Security Guards</label></div></div>
                        <div class="col-6"><div class="form-check mb-2"><input type="checkbox" name="cctv" class="form-check-input" id="cctv"><label class="form-check-label" for="cctv">CCTV</label></div></div>
                        <div class="col-6"><div class="form-check mb-2"><input type="checkbox" name="intrusion_detection" class="form-check-input" id="intrusion"><label class="form-check-label" for="intrusion">Intrusion Detection</label></div></div>
                    </div>
                    <h6 class="mt-4">Fire Protection</h6>
                    <div class="row">
                        <div class="col-6"><div class="form-check mb-2"><input type="checkbox" name="fire_detection" class="form-check-input" id="firedet"><label class="form-check-label" for="firedet">Fire Detection</label></div></div>
                        <div class="col-6"><div class="form-check mb-2"><input type="checkbox" name="fire_suppression" class="form-check-input" id="firesup"><label class="form-check-label" for="firesup">Fire Suppression</label></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-thermometer-half me-2"></i>Environment</h5>
                    <div class="form-check mb-3"><input type="checkbox" name="climate_controlled" class="form-check-input" id="climate"><label class="form-check-label" for="climate">Climate Controlled</label></div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Temperature Range</label><input type="text" name="temperature_range" class="form-control" placeholder="e.g., 18-22°C"></div>
                        <div class="col-6"><label class="form-label">Humidity Range</label><input type="text" name="humidity_range" class="form-control" placeholder="e.g., 45-55%"></div>
                    </div>
                    <div class="form-check mb-3"><input type="checkbox" name="uv_filtering" class="form-check-input" id="uv"><label class="form-check-label" for="uv">UV Filtering</label></div>
                    <h5 class="mb-3 mt-4"><i class="fas fa-truck me-2"></i>Handling</h5>
                    <div class="row">
                        <div class="col-6"><div class="form-check mb-2"><input type="checkbox" name="trained_handlers" class="form-check-input" id="handlers"><label class="form-check-label" for="handlers">Trained Handlers</label></div></div>
                        <div class="col-6"><div class="form-check mb-2"><input type="checkbox" name="loading_dock" class="form-check-input" id="dock"><label class="form-check-label" for="dock">Loading Dock</label></div></div>
                    </div>
                    <h5 class="mb-3 mt-4"><i class="fas fa-user me-2"></i>Completed By</h5>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Name</label><input type="text" name="completed_by" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Date</label><input type="date" name="completed_date" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewLoan', 'id' => $loan->id]); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Report</button>
            </div>
        </form>
    </div>
</div>
