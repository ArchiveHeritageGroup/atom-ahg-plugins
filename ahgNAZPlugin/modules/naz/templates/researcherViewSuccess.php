<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'researchers']); ?>">Researchers</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($researcher->first_name . ' ' . $researcher->last_name); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($researcher->title . ' ' . $researcher->first_name . ' ' . $researcher->last_name); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permitCreate']); ?>?researcher_id=<?php echo $researcher->id; ?>" class="btn btn-primary">
                <i class="fas fa-id-card me-1"></i> Issue Permit
            </a>
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researchers']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Contact Information</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8"><a href="mailto:<?php echo htmlspecialchars($researcher->email); ?>"><?php echo htmlspecialchars($researcher->email); ?></a></dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($researcher->phone ?? '-'); ?></dd>

                        <dt class="col-sm-4">Nationality</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($researcher->nationality ?? '-'); ?></dd>

                        <dt class="col-sm-4">ID/Passport</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($researcher->national_id ?? $researcher->passport_number ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Affiliation</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Institution</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($researcher->institution ?? '-'); ?></dd>

                        <dt class="col-sm-4">Position</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($researcher->position ?? '-'); ?></dd>

                        <dt class="col-sm-4">Research Interests</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($researcher->research_interests ?? '-')); ?></dd>
                    </dl>
                </div>
            </div>

            <?php if (!$permits->isEmpty()): ?>
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Research Permits</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Permit #</th><th>Topic</th><th>Period</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($permits as $p): ?>
                            <tr>
                                <td><a href="<?php echo url_for(['module' => 'naz', 'action' => 'permitView', 'id' => $p->id]); ?>"><?php echo htmlspecialchars($p->permit_number); ?></a></td>
                                <td><?php echo htmlspecialchars(substr($p->research_topic, 0, 40)); ?></td>
                                <td><?php echo $p->start_date; ?> - <?php echo $p->end_date; ?></td>
                                <td><span class="badge bg-<?php echo $p->status === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($p->status); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php $typeColors = ['local' => 'success', 'foreign' => 'primary', 'institutional' => 'info']; ?>
                    <span class="badge bg-<?php echo $typeColors[$researcher->researcher_type] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst($researcher->researcher_type); ?>
                    </span>
                    <p class="mt-2 mb-0">
                        <span class="badge bg-<?php echo $researcher->status === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($researcher->status); ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Registration</h5></div>
                <div class="card-body">
                    <p class="mb-0"><strong>Registered:</strong> <?php echo date('j M Y', strtotime($researcher->registration_date)); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
