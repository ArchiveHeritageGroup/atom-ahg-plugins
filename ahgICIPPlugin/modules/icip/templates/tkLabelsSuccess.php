<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item active">TK Labels</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-tag me-2"></i>
            Traditional Knowledge Labels
        </h1>
        <a href="https://localcontexts.org/labels/traditional-knowledge-labels/" target="_blank" class="btn btn-outline-info">
            <i class="bi bi-box-arrow-up-right me-1"></i>
            Local Contexts
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Label Types -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Available Label Types</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-primary mb-3">Traditional Knowledge (TK) Labels</h6>
                    <div class="row">
                        <?php foreach ($labelTypes as $type): ?>
                            <?php if ($type->category === 'TK'): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="icip-tk-label-icon me-2" style="background-color: #8B4513; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">
                                            <?php echo strtoupper($type->code) ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($type->name) ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($type->description) ?></small>
                                            <?php if ($type->local_contexts_url): ?>
                                                <br><a href="<?php echo htmlspecialchars($type->local_contexts_url) ?>" target="_blank" class="small">Learn more</a>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>

                    <hr>

                    <h6 class="text-success mb-3">Biocultural (BC) Labels</h6>
                    <div class="row">
                        <?php foreach ($labelTypes as $type): ?>
                            <?php if ($type->category === 'BC'): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="icip-tk-label-icon me-2" style="background-color: #228B22; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">
                                            <?php echo strtoupper($type->code) ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($type->name) ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($type->description) ?></small>
                                            <?php if ($type->local_contexts_url): ?>
                                                <br><a href="<?php echo htmlspecialchars($type->local_contexts_url) ?>" target="_blank" class="small">Learn more</a>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Label Applications</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentLabels)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-tag fs-1"></i>
                            <p class="mb-0 mt-2">No labels applied yet</p>
                            <p class="small">Labels can be applied from individual record ICIP pages</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Label</th>
                                        <th>Record</th>
                                        <th>Community</th>
                                        <th>Applied By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLabels as $label): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?php echo $label->category === 'TK' ? 'icip-tk-label' : 'icip-bc-label' ?>">
                                                    <?php echo strtoupper($label->label_code) ?>
                                                </span>
                                                <?php echo htmlspecialchars($label->label_name) ?>
                                            </td>
                                            <td>
                                                <?php if ($label->slug): ?>
                                                    <a href="<?php echo url_for('@icip_object?slug=' . $label->slug) ?>">
                                                        <?php echo htmlspecialchars($label->object_title ?? 'Untitled') ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($label->object_title ?? 'Untitled') ?>
                                                <?php endif ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($label->community_name ?? '-') ?></td>
                                            <td>
                                                <span class="badge <?php echo $label->applied_by === 'community' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?php echo ucfirst($label->applied_by) ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('j M Y', strtotime($label->created_at)) ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Usage Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Label Usage</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($appliedLabels)): ?>
                        <p class="text-muted mb-0">No labels have been applied yet</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($appliedLabels as $stat): ?>
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <span>
                                        <span class="badge <?php echo $stat->category === 'TK' ? 'icip-tk-label' : 'icip-bc-label' ?> me-1">
                                            <?php echo strtoupper($stat->code) ?>
                                        </span>
                                        <?php echo htmlspecialchars($stat->name) ?>
                                    </span>
                                    <span class="badge bg-primary"><?php echo $stat->usage_count ?></span>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>

            <!-- About TK Labels -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">About TK Labels</h5>
                </div>
                <div class="card-body small">
                    <p>Traditional Knowledge (TK) Labels are a digital tool developed by <strong>Local Contexts</strong> to help Indigenous communities manage their cultural heritage in digital environments.</p>

                    <p>Labels can be:</p>
                    <ul>
                        <li><strong>Applied by communities</strong> to express cultural protocols</li>
                        <li><strong>Applied by institutions</strong> to acknowledge Indigenous origin</li>
                    </ul>

                    <p class="mb-0">
                        <a href="https://localcontexts.org/" target="_blank">
                            Visit Local Contexts <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icip-tk-label {
    background-color: #8B4513;
    color: white;
}
.icip-bc-label {
    background-color: #228B22;
    color: white;
}
</style>
