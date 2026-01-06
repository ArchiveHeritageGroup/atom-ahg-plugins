<?php use_helper('Text'); ?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'index']); ?>"><?php echo __('Privacy'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('Lodge Complaint'); ?></li>
        </ol>
    </nav>

    <h1 class="h2 mb-4"><i class="fas fa-exclamation-circle me-2 text-warning"></i><?php echo __('Lodge a Privacy Complaint'); ?></h1>

    <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><?php echo __('Complaint Details'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'complaint']); ?>">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Type of Complaint'); ?> <span class="text-danger">*</span></label>
                            <select name="complaint_type" class="form-select" required>
                                <option value=""><?php echo __('Select...'); ?></option>
                                <?php foreach ($complaintTypes as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo __($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Date of Incident'); ?></label>
                            <input type="date" name="date_of_incident" class="form-control" max="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?> <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="5" required 
                                placeholder="<?php echo __('Please provide details of your complaint, including what happened, when it happened, and how it affected you...'); ?>"></textarea>
                        </div>

                        <hr class="my-4">
                        <h5><?php echo __('Your Contact Information'); ?></h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Full Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="complainant_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Email Address'); ?> <span class="text-danger">*</span></label>
                                <input type="email" name="complainant_email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Phone Number'); ?></label>
                                <input type="tel" name="complainant_phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <select name="jurisdiction" class="form-select">
                                    <option value="popia">POPIA (South Africa)</option>
                                    <option value="ndpa">NDPA (Nigeria)</option>
                                    <option value="kenya_dpa">Kenya DPA</option>
                                    <option value="gdpr">GDPR (EU)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="confirm" required>
                            <label class="form-check-label" for="confirm">
                                <?php echo __('I confirm that the information provided is accurate to the best of my knowledge'); ?>
                            </label>
                        </div>

                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-paper-plane me-2"></i><?php echo __('Submit Complaint'); ?>
                            </button>
                            <a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-lg">
                                <?php echo __('Cancel'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Before You Submit'); ?></h5>
                </div>
                <div class="card-body">
                    <p class="small"><?php echo __('A privacy complaint is appropriate when you believe your personal information has been mishandled or your privacy rights have been violated.'); ?></p>
                    <p class="small mb-0"><strong><?php echo __('Note:'); ?></strong> <?php echo __('If you simply want to access, correct, or delete your personal information, please use our'); ?> 
                        <a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'dsarRequest']); ?>"><?php echo __('Data Subject Request'); ?></a> <?php echo __('form instead.'); ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('What Happens Next'); ?></h5>
                </div>
                <div class="card-body">
                    <ol class="small mb-0">
                        <li class="mb-2"><?php echo __('You will receive a reference number'); ?></li>
                        <li class="mb-2"><?php echo __('Our privacy team will review your complaint'); ?></li>
                        <li class="mb-2"><?php echo __('We will contact you within 14 days'); ?></li>
                        <li><?php echo __('Investigation and resolution'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
