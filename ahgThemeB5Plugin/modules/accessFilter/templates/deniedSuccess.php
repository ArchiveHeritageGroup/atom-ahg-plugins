<?php use_helper('Text'); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Access Denied
                    </h4>
                </div>
                <div class="card-body">
                    <p class="lead">You do not have permission to access:</p>
                    <p class="h5 text-muted"><?php echo esc_entities($objectTitle); ?></p>
                    
                    <hr>

                    <?php if (in_array('classification', $access['reasons'] ?? [])): ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-shield-alt me-2"></i>Security Classification Required</h5>
                            <p class="mb-0">
                                This material is classified as 
                                <strong><?php echo esc_entities($access['classification']['name'] ?? 'Classified'); ?></strong>.
                                <?php if (!$sf_user->isAuthenticated()): ?>
                                    <br>Please <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>">log in</a> to verify your clearance level.
                                <?php else: ?>
                                    <br>Your current clearance level does not permit access.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array('donor_restriction', $access['reasons'] ?? [])): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-user-shield me-2"></i>Donor Restriction</h5>
                            <p class="mb-0">Access to this material is restricted by donor agreement.</p>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array('embargo', $access['reasons'] ?? [])): ?>
                        <div class="alert alert-secondary">
                            <h5><i class="fas fa-clock me-2"></i>Under Embargo</h5>
                            <p class="mb-0">
                                This material is embargoed until 
                                <strong><?php echo date('F j, Y', strtotime($access['embargo']['end_date'])); ?></strong>.
                            </p>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Go Back
                        </a>
                        
                        <?php if (!$sf_user->isAuthenticated()): ?>
                            <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-1"></i> Log In
                            </a>
                        <?php else: ?>
                            <a href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'create', 'object_id' => $objectId]); ?>" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Request Access
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
