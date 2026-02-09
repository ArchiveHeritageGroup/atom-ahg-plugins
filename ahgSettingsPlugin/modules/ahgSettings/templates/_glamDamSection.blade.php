<div class="ahg-settings-section mb-5" id="glam-dam">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>
            <i class="fas fa-photo-video text-info me-2"></i>
            Digital Asset Management
        </h3>
    </div>

    <!-- TIFF to PDF Merge Component -->
    @include('_glamDamTiffPdfMerge')

    <!-- Additional DAM Tools -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-images fa-3x text-success mb-3"></i>
                    <h6>Digital Objects</h6>
                    <p class="small text-muted">Browse and manage all digital objects in the system.</p>
                    <a href="{{ url_for(['module' => 'digitalobject', 'action' => 'browse']) }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-search me-1"></i>Browse
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-tasks fa-3x text-info mb-3"></i>
                    <h6>Background Jobs</h6>
                    <p class="small text-muted">View status of all processing jobs including PDF merges.</p>
                    <a href="{{ url_for(['module' => 'jobs', 'action' => 'browse']) }}" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-list me-1"></i>View Jobs
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-cube fa-3x text-warning mb-3"></i>
                    <h6>3D Objects</h6>
                    <p class="small text-muted">Manage 3D models, thumbnails, and viewer settings.</p>
                    <a href="{{ url_for(['module' => 'threeDReports', 'action' => 'index']) }}" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-cog me-1"></i>Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
