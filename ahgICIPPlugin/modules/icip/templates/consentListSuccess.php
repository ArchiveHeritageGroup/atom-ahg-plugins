<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item active">Consent Records</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-file-earmark-check me-2"></i>
            Consent Records
        </h1>
        <a href="<?php echo url_for('@icip_consent_add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Add Consent Record
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?php echo $value ?>" <?php echo ($filters['status'] ?? '') === $value ? 'selected' : '' ?>>
                                <?php echo $label ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Community</label>
                    <select name="community_id" class="form-select">
                        <option value="">All Communities</option>
                        <?php foreach ($communities as $community): ?>
                            <option value="<?php echo $community->id ?>" <?php echo ($filters['community_id'] ?? '') == $community->id ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($community->name) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="<?php echo url_for('@icip_consent_list') ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header">
            <strong><?php echo count($consents) ?></strong> consent records found
        </div>
        <div class="card-body p-0">
            <?php if (empty($consents)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-file-earmark-check fs-1"></i>
                    <p class="mb-0 mt-2">No consent records found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Record</th>
                                <th>Community</th>
                                <th>Status</th>
                                <th>Consent Date</th>
                                <th>Expiry</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consents as $consent): ?>
                                <tr>
                                    <td>
                                        <?php if ($consent->slug): ?>
                                            <a href="<?php echo url_for('@icip_object?slug=' . $consent->slug) ?>">
                                                <?php echo htmlspecialchars($consent->object_title ?? 'Untitled') ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($consent->object_title ?? 'Untitled') ?>
                                        <?php endif ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($consent->community_name ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match ($consent->consent_status) {
                                            'full_consent' => 'bg-success',
                                            'conditional_consent', 'restricted_consent' => 'bg-info',
                                            'pending_consultation', 'consultation_in_progress' => 'bg-warning text-dark',
                                            'denied' => 'bg-danger',
                                            'not_required' => 'bg-light text-dark',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass ?>">
                                            <?php echo $statusOptions[$consent->consent_status] ?? ucwords(str_replace('_', ' ', $consent->consent_status)) ?>
                                        </span>
                                    </td>
                                    <td><?php echo $consent->consent_date ? date('j M Y', strtotime($consent->consent_date)) : '-' ?></td>
                                    <td>
                                        <?php if ($consent->consent_expiry_date): ?>
                                            <?php
                                            $expiryDate = new DateTime($consent->consent_expiry_date);
                                            $today = new DateTime();
                                            $isExpired = $expiryDate < $today;
                                            $daysUntil = $today->diff($expiryDate)->days;
                                            $isExpiringSoon = !$isExpired && $daysUntil <= 90;
                                            ?>
                                            <span class="<?php echo $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : '') ?>">
                                                <?php echo date('j M Y', strtotime($consent->consent_expiry_date)) ?>
                                                <?php if ($isExpired): ?>
                                                    <i class="bi bi-exclamation-circle" title="Expired"></i>
                                                <?php elseif ($isExpiringSoon): ?>
                                                    <i class="bi bi-clock" title="Expiring soon"></i>
                                                <?php endif ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo url_for('@icip_consent_view?id=' . $consent->id) ?>" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?php echo url_for('@icip_consent_edit?id=' . $consent->id) ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
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
