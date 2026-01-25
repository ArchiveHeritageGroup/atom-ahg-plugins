<?php
/**
 * Contributor Registration.
 */

decorate_with('layout_1col');
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-user-plus me-2"></i>Create Contributor Account
</h1>
<?php end_slot(); ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if ($success): ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle display-1 text-success"></i>
                    <h2 class="h4 mt-3">Registration Successful!</h2>
                    <p class="text-muted mb-4">
                        We've sent a verification email to your address.
                        Please check your inbox and click the verification link to activate your account.
                    </p>
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorLogin']); ?>" class="btn btn-primary">
                        <i class="fas fa-box-arrow-in-right me-2"></i>Go to Login
                    </a>
                </div>
                <?php else: ?>
                <div class="text-center mb-4">
                    <i class="fas fa-user-plus-fill display-4 text-primary"></i>
                    <h2 class="h4 mt-3">Join Our Community</h2>
                    <p class="text-muted">Help preserve and share our heritage</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo esc_specialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="post" action="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorRegister']); ?>">
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="display_name" name="display_name"
                                   required minlength="2" maxlength="100"
                                   placeholder="How you want to be known"
                                   value="<?php echo esc_specialchars($_POST['display_name'] ?? ''); ?>">
                        </div>
                        <div class="form-text">This will be shown alongside your contributions</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   required
                                   placeholder="your@email.com"
                                   value="<?php echo esc_specialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="form-text">We'll send a verification email to this address</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   required minlength="8"
                                   placeholder="Minimum 8 characters">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock-fill"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   required minlength="8"
                                   placeholder="Re-enter your password">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                and understand that my contributions will be reviewed before publication
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-2">Already have an account?</p>
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorLogin']); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-box-arrow-in-right me-2"></i>Sign In
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']); ?>" class="text-muted text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i>Back to Heritage Portal
            </a>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Contributor Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Contribution Guidelines</h6>
                <p>By contributing to this heritage collection, you agree to:</p>
                <ul>
                    <li>Provide accurate and truthful information to the best of your knowledge</li>
                    <li>Not submit copyrighted material without permission</li>
                    <li>Respect the privacy of individuals mentioned in records</li>
                    <li>Not submit offensive, defamatory, or inappropriate content</li>
                </ul>

                <h6>2. Review Process</h6>
                <p>All contributions are reviewed by our team before publication. We reserve the right to:</p>
                <ul>
                    <li>Edit contributions for clarity and accuracy</li>
                    <li>Reject contributions that don't meet our guidelines</li>
                    <li>Request additional information or verification</li>
                </ul>

                <h6>3. Intellectual Property</h6>
                <p>By submitting a contribution, you grant us a non-exclusive license to use, display, and distribute your contribution as part of our heritage collection.</p>

                <h6>4. Privacy</h6>
                <p>Your personal information will be used in accordance with our privacy policy. Your display name will be shown alongside your contributions.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
