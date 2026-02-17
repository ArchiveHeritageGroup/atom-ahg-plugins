<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Register</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-user-plus text-primary me-2"></i>Researcher Registration</h1>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-user me-2"></i>Personal Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Title</label>
                            <select name="title" class="form-select">
                                <option value="">--</option>
                                <option value="Mr">Mr</option>
                                <option value="Mrs">Mrs</option>
                                <option value="Ms">Ms</option>
                                <option value="Dr">Dr</option>
                                <option value="Prof">Prof</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $user->email ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    
                    <h5 class="mb-3 mt-4"><i class="fas fa-id-card me-2"></i>Identification</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">ID Type</label>
                            <select name="id_type" class="form-select">
                                <option value="">--</option>
                                <option value="passport">Passport</option>
                                <option value="national_id">National ID</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="student_card">Student Card</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">ID Number</label>
                            <input type="text" name="id_number" class="form-control">
                        </div>

                <!-- Student ID (conditional) -->
                <div class="col-md-6" id="student_id_wrapper" style="display:none;">
                    <label class="form-label"><?php echo __('Student ID'); ?></label>
                    <input type="text" name="student_id" class="form-control" placeholder="<?php echo __('University student number'); ?>">
                    <small class="text-muted"><?php echo __('Required for student researchers'); ?></small>
                </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-university me-2"></i>Affiliation</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Affiliation Type *</label>
                        <select name="affiliation_type" class="form-select" required>
                            <option value="independent">Independent Researcher</option>
                            <option value="academic">Academic Institution</option>
                            <option value="government">Government</option>
                            <option value="private">Private Organization</option>
                            <option value="student">Student</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Institution</label>
                        <input type="text" name="institution" class="form-control" placeholder="University, Organization, etc.">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ORCID ID</label>
                        <input type="text" name="orcid_id" class="form-control" placeholder="0000-0000-0000-0000">
                    </div>
                    
                    <h5 class="mb-3 mt-4"><i class="fas fa-flask me-2"></i>Research</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Research Interests</label>
                        <textarea name="research_interests" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Project</label>
                        <textarea name="current_project" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Submit Registration</button>
            </div>
        </form>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var affiliationType = document.querySelector('[name="affiliation_type"]');
    var studentWrapper = document.getElementById('student_id_wrapper');
    
    function toggleStudentId() {
        if (affiliationType && studentWrapper) {
            studentWrapper.style.display = affiliationType.value === 'student' ? 'block' : 'none';
        }
    }
    
    if (affiliationType) {
        affiliationType.addEventListener('change', toggleStudentId);
        toggleStudentId(); // Initial state
    }
});
</script>
