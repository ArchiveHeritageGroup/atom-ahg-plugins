<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item active">Communities</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-people me-2"></i>
            Community Registry
        </h1>
        <a href="<?php echo url_for('@icip_community_add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Add Community
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">State/Territory</label>
                    <select name="state" class="form-select">
                        <option value="">All States</option>
                        <?php foreach ($states as $code => $name): ?>
                            <option value="<?php echo $code ?>" <?php echo ($filters['state'] ?? '') === $code ? 'selected' : '' ?>>
                                <?php echo $name ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, language group, region..." value="<?php echo htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input type="checkbox" name="active_only" value="1" class="form-check-input" id="activeOnly" <?php echo ($filters['active_only'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activeOnly">Active only</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="<?php echo url_for('@icip_communities') ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header">
            <strong><?php echo count($communities) ?></strong> communities found
        </div>
        <div class="card-body p-0">
            <?php if (empty($communities)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-people fs-1"></i>
                    <p class="mb-0 mt-2">No communities found</p>
                    <a href="<?php echo url_for('@icip_community_add') ?>" class="btn btn-primary mt-3">Add First Community</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Community Name</th>
                                <th>Language Group</th>
                                <th>Region</th>
                                <th>State</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($communities as $community): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo url_for('@icip_community_view?id=' . $community->id) ?>">
                                            <strong><?php echo htmlspecialchars($community->name) ?></strong>
                                        </a>
                                        <?php if ($community->prescribed_body_corporate): ?>
                                            <br><small class="text-muted">PBC: <?php echo htmlspecialchars($community->prescribed_body_corporate) ?></small>
                                        <?php endif ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($community->language_group ?? '-') ?></td>
                                    <td><?php echo htmlspecialchars($community->region ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $community->state_territory ?></span>
                                    </td>
                                    <td>
                                        <?php if ($community->contact_name): ?>
                                            <?php echo htmlspecialchars($community->contact_name) ?>
                                            <?php if ($community->contact_email): ?>
                                                <br><small><a href="mailto:<?php echo htmlspecialchars($community->contact_email) ?>"><?php echo htmlspecialchars($community->contact_email) ?></a></small>
                                            <?php endif ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php if ($community->is_active): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo url_for('@icip_community_view?id=' . $community->id) ?>" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?php echo url_for('@icip_community_edit?id=' . $community->id) ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?php echo url_for('@icip_report_community?id=' . $community->id) ?>" class="btn btn-outline-info" title="Report">
                                                <i class="bi bi-graph-up"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
