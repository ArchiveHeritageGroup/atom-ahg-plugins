<?php use_helper('Text'); ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i><?php echo __('Request Submitted Successfully'); ?></h5>
                </div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-envelope-open-text fa-4x text-success mb-4"></i>
                    
                    <h4><?php echo __('Thank you for your request'); ?></h4>
                    
                    <div class="bg-light p-3 rounded my-4">
                        <p class="mb-1"><?php echo __('Your Reference Number:'); ?></p>
                        <h3 class="text-primary mb-0"><?php echo esc_entities($dsar->reference_number); ?></h3>
                    </div>

                    <p class="mb-1"><strong><?php echo __('Due Date:'); ?></strong> <?php echo $dsar->due_date; ?></p>
                    <p class="text-muted"><?php echo __('We will respond within 30 days as required by POPIA.'); ?></p>

                    <hr class="my-4">

                    <p><?php echo __('A confirmation email has been sent to:'); ?></p>
                    <p><strong><?php echo esc_entities($dsar->requestor_email); ?></strong></p>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <a href="<?php echo url_for(['module' => 'privacy', 'action' => 'dsarStatus']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i><?php echo __('Check Status'); ?>
                        </a>
                        <a href="<?php echo url_for(['module' => 'privacy', 'action' => 'index']); ?>" class="btn btn-secondary">
                            <?php echo __('Back to Privacy'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
