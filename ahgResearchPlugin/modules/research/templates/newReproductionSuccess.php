<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'reproductions']); ?>">Reproductions</a></li>
        <li class="breadcrumb-item active">New Request</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-copy text-primary me-2"></i>New Reproduction Request</h1>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Purpose of Reproduction *</label>
                        <select name="purpose" class="form-select" required>
                            <option value="">Select purpose...</option>
                            <option value="research">Academic Research</option>
                            <option value="publication">Publication</option>
                            <option value="exhibition">Exhibition</option>
                            <option value="documentary">Documentary/Film</option>
                            <option value="personal">Personal Use</option>
                            <option value="commercial">Commercial Use</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Intended Use</label>
                        <textarea name="intended_use" class="form-control" rows="3" placeholder="Describe how you plan to use the reproductions..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Publication Details</label>
                        <textarea name="publication_details" class="form-control" rows="2" placeholder="If for publication, provide title, publisher, expected date..."></textarea>
                        <small class="text-muted">Required for publication or commercial use</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Delivery Method</label>
                            <select name="delivery_method" class="form-select">
                                <option value="digital">Digital Download</option>
                                <option value="email">Email</option>
                                <option value="physical">Physical Copy (Post)</option>
                                <option value="collect">Collect in Person</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Urgency</label>
                            <select name="urgency" class="form-select">
                                <option value="normal">Normal (10-15 working days)</option>
                                <option value="high">High Priority (5-7 working days)</option>
                                <option value="rush">Rush (2-3 working days) - additional fee</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Special Instructions</label>
                        <textarea name="special_instructions" class="form-control" rows="2" placeholder="Any special requirements or notes..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        After creating the request, you can add individual items from your collections or by searching the archive.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Create Request</button>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'reproductions']); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Pricing Information</h6></div>
            <div class="card-body">
                <p class="small text-muted">Reproduction fees vary based on:</p>
                <ul class="small">
                    <li>Type of reproduction (scan, photograph, photocopy)</li>
                    <li>Size and resolution</li>
                    <li>Color or black & white</li>
                    <li>Quantity</li>
                    <li>Urgency</li>
                    <li>Intended use (commercial vs non-commercial)</li>
                </ul>
                <p class="small text-muted">A quote will be provided before processing.</p>
            </div>
        </div>
    </div>
</div>
