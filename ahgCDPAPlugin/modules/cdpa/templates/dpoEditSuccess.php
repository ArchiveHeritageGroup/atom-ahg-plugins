<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpo']); ?>">DPO</a></li>
                    <li class="breadcrumb-item active"><?php echo $dpo ? 'Edit' : 'Appoint'; ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-user-shield me-2"></i><?php echo $dpo ? 'Edit' : 'Appoint'; ?> Data Protection Officer</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">DPO Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="<?php echo htmlspecialchars($dpo->name ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($dpo->email ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($dpo->phone ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">HIT Certificate Number</label>
                            <input type="text" name="hit_cert_number" class="form-control"
                                   value="<?php echo htmlspecialchars($dpo->hit_cert_number ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Qualifications</label>
                            <textarea name="qualifications" class="form-control" rows="3"><?php echo htmlspecialchars($dpo->qualifications ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                            <input type="date" name="appointment_date" class="form-control"
                                   value="<?php echo $dpo->appointment_date ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Term End Date</label>
                            <input type="date" name="term_end_date" class="form-control"
                                   value="<?php echo $dpo->term_end_date ?? ''; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Form DP2 (POTRAZ Notification)</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="form_dp2_submitted" id="form_dp2_submitted"
                                       <?php echo ($dpo->form_dp2_submitted ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="form_dp2_submitted">
                                    Form DP2 has been submitted to POTRAZ
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Submission Date</label>
                            <input type="date" name="form_dp2_date" class="form-control"
                                   value="<?php echo $dpo->form_dp2_date ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">POTRAZ Reference</label>
                            <input type="text" name="form_dp2_ref" class="form-control"
                                   value="<?php echo htmlspecialchars($dpo->form_dp2_ref ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save DPO
                    </button>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpo']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
