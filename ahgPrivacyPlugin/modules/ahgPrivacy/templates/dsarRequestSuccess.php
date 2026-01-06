<?php use_helper('Text'); ?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'index']); ?>"><?php echo __('Privacy'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('Submit Request'); ?></li>
        </ol>
    </nav>

    <h1 class="h2 mb-4"><i class="fas fa-file-alt me-2"></i><?php echo __('Data Subject Access Request'); ?></h1>

    <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'dsarRequest']); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Request Type'); ?> <span class="text-danger">*</span></label>
                        <select name="request_type" class="form-select" required>
                            <option value=""><?php echo __('Select...'); ?></option>
                            <?php foreach ($requestTypes as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo __($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h5 class="mt-4 mb-3"><?php echo __('Your Details'); ?></h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Full Name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="requestor_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Email Address'); ?> <span class="text-danger">*</span></label>
                        <input type="email" name="requestor_email" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Phone Number'); ?></label>
                        <input type="tel" name="requestor_phone" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('ID Type'); ?></label>
                        <select name="requestor_id_type" class="form-select">
                            <option value=""><?php echo __('Select...'); ?></option>
                            <option value="sa_id"><?php echo __('South African ID'); ?></option>
                            <option value="passport"><?php echo __('Passport'); ?></option>
                            <option value="other"><?php echo __('Other'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('ID Number'); ?></label>
                        <input type="text" name="requestor_id_number" class="form-control">
                        <small class="text-muted"><?php echo __('Required for identity verification'); ?></small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php echo __('Description of Request'); ?></label>
                    <textarea name="description" class="form-control" rows="4" placeholder="<?php echo __('Please describe the information you are requesting...'); ?>"></textarea>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo __('We will respond to your request within 30 days as required by POPIA.'); ?>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'index']); ?>" class="btn btn-secondary">
                        <?php echo __('Cancel'); ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i><?php echo __('Submit Request'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
