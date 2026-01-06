<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarList']); ?>" class="btn btn-outline-secondary btn-sm me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h2 mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('New DSAR'); ?></h1>
    </div>

    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarAdd']); ?>">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Request Details'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?> <span class="text-danger">*</span></label>
                                <select name="jurisdiction" id="jurisdiction" class="form-select" required onchange="updateRequestTypes()">
                                    <?php foreach ($jurisdictions as $code => $info): ?>
                                    <option value="<?php echo $code; ?>" <?php echo $code === $defaultJurisdiction ? 'selected' : ''; ?>>
                                        <?php echo $info['name']; ?> (<?php echo $info['country']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Request Type'); ?> <span class="text-danger">*</span></label>
                                <select name="request_type" id="request_type" class="form-select" required>
                                    <?php foreach ($requestTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo __($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Priority'); ?></label>
                                <select name="priority" class="form-select">
                                    <option value="low"><?php echo __('Low'); ?></option>
                                    <option value="normal" selected><?php echo __('Normal'); ?></option>
                                    <option value="high"><?php echo __('High'); ?></option>
                                    <option value="urgent"><?php echo __('Urgent'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Received Date'); ?></label>
                                <input type="date" name="received_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Requestor Information'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Full Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="requestor_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Email Address'); ?></label>
                                <input type="email" name="requestor_email" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Phone Number'); ?></label>
                                <input type="tel" name="requestor_phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('ID Type'); ?></label>
                                <select name="requestor_id_type" class="form-select">
                                    <option value=""><?php echo __('Select...'); ?></option>
                                    <?php foreach ($idTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo __($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('ID Number'); ?></label>
                                <input type="text" name="requestor_id_number" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Address'); ?></label>
                            <textarea name="requestor_address" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Request Description'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?></label>
                            <textarea name="description" class="form-control" rows="4" placeholder="<?php echo __('Details of the information or action requested...'); ?>"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Internal Notes'); ?></label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="<?php echo __('Internal notes (not shared with requestor)...'); ?>"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4 bg-light">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Jurisdiction Info'); ?></h5>
                    </div>
                    <div class="card-body" id="jurisdiction-info">
                        <?php $info = $jurisdictions[$defaultJurisdiction]; ?>
                        <p><strong><?php echo $info['full_name']; ?></strong></p>
                        <ul class="list-unstyled mb-0">
                            <li><i class="fas fa-clock me-2 text-primary"></i><?php echo __('Response:'); ?> <?php echo $info['dsar_days']; ?> <?php echo __('days'); ?></li>
                            <li><i class="fas fa-university me-2 text-primary"></i><?php echo $info['regulator']; ?></li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i><?php echo __('Create DSAR'); ?>
                            </button>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarList']); ?>" class="btn btn-outline-secondary">
                                <?php echo __('Cancel'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
