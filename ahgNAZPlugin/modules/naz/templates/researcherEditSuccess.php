<?php use_helper('Date'); ?>

<?php
  $r = $researcher;
  $sel = function ($a, $b) { return (string) $a === (string) $b ? 'selected' : ''; };
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'researchers']); ?>">Researchers</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'researcherView', 'id' => $r->id]); ?>"><?php echo htmlspecialchars($r->first_name . ' ' . $r->last_name); ?></a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
            <h1><i class="fas fa-user-edit me-2"></i>Edit Researcher</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Personal Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Title</label>
                            <select name="title" class="form-select">
                                <option value="">-</option>
                                <?php foreach (['Mr', 'Mrs', 'Ms', 'Dr', 'Prof'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $sel($r->title, $t); ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars((string) $r->first_name); ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars((string) $r->last_name); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars((string) $r->email); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars((string) ($r->phone ?? '')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Researcher Type <span class="text-danger">*</span></label>
                            <select name="researcher_type" class="form-select" required>
                                <?php foreach (['local' => 'Local', 'foreign' => 'Foreign', 'institutional' => 'Institutional'] as $val => $lbl): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $sel($r->researcher_type, $val); ?>><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality" class="form-control" value="<?php echo htmlspecialchars((string) ($r->nationality ?? '')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">National ID / Passport</label>
                            <input type="text" name="national_id" class="form-control" value="<?php echo htmlspecialchars((string) ($r->national_id ?? '')); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Affiliation</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Institution</label>
                            <input type="text" name="institution" class="form-control" value="<?php echo htmlspecialchars((string) ($r->institution ?? '')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars((string) ($r->position ?? '')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Research Interests</label>
                            <textarea name="research_interests" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($r->research_interests ?? '')); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Save changes</button>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researcherView', 'id' => $r->id]); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
