<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for([$object, 'module' => 'informationobject']) ?>"><?php echo htmlspecialchars($object->title ?? 'Record') ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_object?slug=' . $object->slug) ?>">ICIP</a></li>
            <li class="breadcrumb-item active">TK Labels</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="bi bi-tag me-2"></i>
        Manage TK Labels
    </h1>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Applied Labels -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Applied Labels</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($labels)): ?>
                        <p class="text-muted">No TK labels applied to this record.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($labels as $label): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body d-flex justify-content-between align-items-start">
                                            <div class="d-flex">
                                                <span class="badge <?php echo $label->category === 'TK' ? 'icip-tk-label' : 'icip-bc-label' ?> me-2 fs-6">
                                                    <?php echo strtoupper($label->label_code) ?>
                                                </span>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($label->label_name) ?></strong>
                                                    <br>
                                                    <small class="text-muted">Applied by: <?php echo ucfirst($label->applied_by) ?></small>
                                                    <?php if ($label->community_name): ?>
                                                        <br><small class="text-muted">Community: <?php echo htmlspecialchars($label->community_name) ?></small>
                                                    <?php endif ?>
                                                </div>
                                            </div>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Remove this label?');">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="label_id" value="<?php echo $label->id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>

            <!-- Add Label -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add TK Label</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label class="form-label">Label Type <span class="text-danger">*</span></label>
                            <select name="label_type_id" class="form-select" required>
                                <option value="">Select label</option>
                                <optgroup label="Traditional Knowledge (TK) Labels">
                                    <?php foreach ($labelTypes as $type): ?>
                                        <?php if ($type->category === 'TK'): ?>
                                            <option value="<?php echo $type->id ?>">
                                                <?php echo strtoupper($type->code) ?> - <?php echo htmlspecialchars($type->name) ?>
                                            </option>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </optgroup>
                                <optgroup label="Biocultural (BC) Labels">
                                    <?php foreach ($labelTypes as $type): ?>
                                        <?php if ($type->category === 'BC'): ?>
                                            <option value="<?php echo $type->id ?>">
                                                <?php echo strtoupper($type->code) ?> - <?php echo htmlspecialchars($type->name) ?>
                                            </option>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </optgroup>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Community</label>
                                <select name="community_id" class="form-select">
                                    <option value="">Not specified</option>
                                    <?php foreach ($communities as $community): ?>
                                        <option value="<?php echo $community->id ?>"><?php echo htmlspecialchars($community->name) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Applied By</label>
                                <select name="applied_by" class="form-select">
                                    <option value="institution">Institution</option>
                                    <option value="community">Community</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Local Contexts Project ID</label>
                            <input type="text" name="local_contexts_project_id" class="form-control" placeholder="Optional - link to Local Contexts Hub project">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Label
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">About TK Labels</h5>
                </div>
                <div class="card-body small">
                    <p>TK Labels are developed by <strong>Local Contexts</strong> to help Indigenous communities manage their cultural heritage.</p>
                    <p><strong>TK Labels</strong> (brown) relate to Traditional Knowledge.</p>
                    <p><strong>BC Labels</strong> (green) relate to Biocultural heritage.</p>
                    <p class="mb-0">
                        <a href="https://localcontexts.org/labels/traditional-knowledge-labels/" target="_blank">
                            Learn more at Local Contexts <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Applied By</h5>
                </div>
                <div class="card-body small">
                    <p><strong>Community:</strong> Labels applied directly by or at the request of the community.</p>
                    <p class="mb-0"><strong>Institution:</strong> Labels applied by the institution to acknowledge Indigenous origin or protocols.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icip-tk-label { background-color: #8B4513; color: white; }
.icip-bc-label { background-color: #228B22; color: white; }
</style>
