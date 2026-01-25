<?php
/**
 * Email Verification Result.
 */

decorate_with('layout_1col');
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-envelope-check me-2"></i>Email Verification
</h1>
<?php end_slot(); ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <?php if ($success): ?>
                <i class="fas fa-check-circle display-1 text-success"></i>
                <h2 class="h4 mt-4">Email Verified!</h2>
                <p class="text-muted mb-4">
                    Your email address has been verified successfully.
                    You can now log in and start contributing to our heritage collection.
                </p>
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorLogin']); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-box-arrow-in-right me-2"></i>Sign In
                </a>
                <?php else: ?>
                <i class="fas fa-times-circle display-1 text-danger"></i>
                <h2 class="h4 mt-4">Verification Failed</h2>
                <p class="text-muted mb-4">
                    <?php echo esc_specialchars($error ?? 'The verification link is invalid or has expired.'); ?>
                </p>
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorRegister']); ?>" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Register Again
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
