<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for([$object, 'module' => 'informationobject']) ?>"><?php echo htmlspecialchars($object->title ?? 'Record') ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_object?slug=' . $object->slug) ?>">ICIP</a></li>
            <li class="breadcrumb-item active">Restrictions</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="bi bi-lock me-2"></i>
        Manage Access Restrictions
    </h1>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Active Restrictions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Active Restrictions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($restrictions)): ?>
                        <p class="text-muted">No access restrictions applied to this record.</p>
                    <?php else: ?>
                        <?php foreach ($restrictions as $restriction): ?>
                            <div class="alert alert-danger d-flex justify-content-between align-items-start">
                                <div>
                                    <i class="bi bi-lock-fill me-2"></i>
                                    <strong><?php echo $restrictionTypes[$restriction->restriction_type] ?? ucwords(str_replace('_', ' ', $restriction->restriction_type)) ?></strong>
                                    <?php if ($restriction->override_security_clearance): ?>
                                        <span class="badge bg-dark ms-2">Overrides Security Clearance</span>
                                    <?php endif ?>

                                    <?php if ($restriction->restriction_type === 'custom' && $restriction->custom_restriction_text): ?>
                                        <p class="mb-1 mt-1"><?php echo htmlspecialchars($restriction->custom_restriction_text) ?></p>
                                    <?php endif ?>

                                    <div class="mt-1">
                                        <?php if ($restriction->community_name): ?>
                                            <small class="text-muted">Community: <?php echo htmlspecialchars($restriction->community_name) ?></small>
                                        <?php endif ?>
                                        <?php if ($restriction->start_date || $restriction->end_date): ?>
                                            <br>
                                            <small class="text-muted">
                                                Period: <?php echo $restriction->start_date ? date('j M Y', strtotime($restriction->start_date)) : 'Start' ?>
                                                &ndash;
                                                <?php echo $restriction->end_date ? date('j M Y', strtotime($restriction->end_date)) : 'Indefinite' ?>
                                            </small>
                                        <?php endif ?>
                                    </div>
                                </div>
                                <form method="post" class="d-inline" onsubmit="return confirm('Remove this restriction?');">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="restriction_id" value="<?php echo $restriction->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>

            <!-- Add Restriction -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Restriction</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Restriction Type <span class="text-danger">*</span></label>
                                <select name="restriction_type" class="form-select" required id="restrictionType">
                                    <?php foreach ($restrictionTypes as $value => $label): ?>
                                        <option value="<?php echo $value ?>"><?php echo $label ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Community</label>
                                <select name="community_id" class="form-select">
                                    <option value="">Not specified</option>
                                    <?php foreach ($communities as $community): ?>
                                        <option value="<?php echo $community->id ?>"><?php echo htmlspecialchars($community->name) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3" id="customTextGroup" style="display: none;">
                            <label class="form-label">Custom Restriction Text</label>
                            <textarea name="custom_restriction_text" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control">
                                <div class="form-text">Leave blank for indefinite</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="applies_to_descendants" value="1" class="form-check-input" id="applyDescendants" checked>
                                    <label class="form-check-label" for="applyDescendants">Apply to child records</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="override_security_clearance" value="1" class="form-check-input" id="overrideSecurity" checked>
                                <label class="form-check-label" for="overrideSecurity">
                                    <strong>Override Security Clearance</strong>
                                    <br><small class="text-muted">ICIP restriction takes precedence over standard access controls</small>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-lock me-1"></i> Add Restriction
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Restriction Types</h5>
                </div>
                <div class="card-body small">
                    <dl class="mb-0">
                        <dt><i class="bi bi-people text-danger me-1"></i> Community Permission Required</dt>
                        <dd class="text-muted">Written permission from the community is required</dd>

                        <dt><i class="bi bi-gender-male text-danger me-1"></i> Men Only</dt>
                        <dd class="text-muted">Restricted to men with appropriate cultural standing</dd>

                        <dt><i class="bi bi-gender-female text-danger me-1"></i> Women Only</dt>
                        <dd class="text-muted">Restricted to women with appropriate cultural standing</dd>

                        <dt><i class="bi bi-shield-lock text-danger me-1"></i> Initiated Only</dt>
                        <dd class="text-muted">Restricted to initiated community members</dd>

                        <dt><i class="bi bi-calendar-event text-danger me-1"></i> Seasonal</dt>
                        <dd class="text-muted">Time-based restrictions</dd>

                        <dt><i class="bi bi-heart text-danger me-1"></i> Mourning Period</dt>
                        <dd class="text-muted">Temporary restriction during mourning</dd>

                        <dt><i class="bi bi-box-arrow-left text-danger me-1"></i> Repatriation Pending</dt>
                        <dd class="text-muted">Material awaiting return to community</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('restrictionType').addEventListener('change', function() {
    document.getElementById('customTextGroup').style.display = this.value === 'custom' ? 'block' : 'none';
});
</script>
