<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item active">Consultations</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-chat-dots me-2"></i>
            Consultation Log
        </h1>
        <a href="<?php echo url_for('@icip_consultation_add') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Log Consultation
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="initial_contact" <?php echo ($filters['type'] ?? '') === 'initial_contact' ? 'selected' : '' ?>>Initial Contact</option>
                        <option value="consent_request" <?php echo ($filters['type'] ?? '') === 'consent_request' ? 'selected' : '' ?>>Consent Request</option>
                        <option value="access_request" <?php echo ($filters['type'] ?? '') === 'access_request' ? 'selected' : '' ?>>Access Request</option>
                        <option value="repatriation" <?php echo ($filters['type'] ?? '') === 'repatriation' ? 'selected' : '' ?>>Repatriation</option>
                        <option value="digitisation" <?php echo ($filters['type'] ?? '') === 'digitisation' ? 'selected' : '' ?>>Digitisation</option>
                        <option value="exhibition" <?php echo ($filters['type'] ?? '') === 'exhibition' ? 'selected' : '' ?>>Exhibition</option>
                        <option value="publication" <?php echo ($filters['type'] ?? '') === 'publication' ? 'selected' : '' ?>>Publication</option>
                        <option value="research" <?php echo ($filters['type'] ?? '') === 'research' ? 'selected' : '' ?>>Research</option>
                        <option value="general" <?php echo ($filters['type'] ?? '') === 'general' ? 'selected' : '' ?>>General</option>
                        <option value="follow_up" <?php echo ($filters['type'] ?? '') === 'follow_up' ? 'selected' : '' ?>>Follow Up</option>
                    </select>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="scheduled" <?php echo ($filters['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="completed" <?php echo ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="follow_up_required" <?php echo ($filters['status'] ?? '') === 'follow_up_required' ? 'selected' : '' ?>>Follow Up Required</option>
                        <option value="cancelled" <?php echo ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="<?php echo url_for('@icip_consultations') ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header">
            <strong><?php echo count($consultations) ?></strong> consultations found
        </div>
        <div class="card-body p-0">
            <?php if (empty($consultations)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-chat-dots fs-1"></i>
                    <p class="mb-0 mt-2">No consultations found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Community</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Summary</th>
                                <th>Status</th>
                                <th>Follow-up</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultations as $consultation): ?>
                                <tr>
                                    <td><?php echo date('j M Y', strtotime($consultation->consultation_date)) ?></td>
                                    <td><?php echo htmlspecialchars($consultation->community_name) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo ucwords(str_replace('_', ' ', $consultation->consultation_type)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $methodIcon = match ($consultation->consultation_method) {
                                            'in_person' => 'bi-person',
                                            'phone' => 'bi-telephone',
                                            'video' => 'bi-camera-video',
                                            'email' => 'bi-envelope',
                                            'letter' => 'bi-envelope-paper',
                                            default => 'bi-chat'
                                        };
                                        ?>
                                        <i class="bi <?php echo $methodIcon ?>" title="<?php echo ucwords(str_replace('_', ' ', $consultation->consultation_method)) ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $consultation->consultation_method)) ?>
                                    </td>
                                    <td>
                                        <?php echo mb_substr($consultation->summary, 0, 60) . (strlen($consultation->summary) > 60 ? '...' : '') ?>
                                        <?php if ($consultation->object_title): ?>
                                            <br><small class="text-muted">Re: <?php echo htmlspecialchars($consultation->object_title) ?></small>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match ($consultation->status) {
                                            'completed' => 'bg-success',
                                            'scheduled' => 'bg-info',
                                            'cancelled' => 'bg-secondary',
                                            'follow_up_required' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $consultation->status)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($consultation->follow_up_date): ?>
                                            <?php
                                            $followUpDate = new DateTime($consultation->follow_up_date);
                                            $today = new DateTime();
                                            $isOverdue = $followUpDate < $today && $consultation->status === 'follow_up_required';
                                            ?>
                                            <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                                <?php echo date('j M Y', strtotime($consultation->follow_up_date)) ?>
                                                <?php if ($isOverdue): ?>
                                                    <i class="bi bi-exclamation-circle"></i>
                                                <?php endif ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo url_for('@icip_consultation_view?id=' . $consultation->id) ?>" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?php echo url_for('@icip_consultation_edit?id=' . $consultation->id) ?>" class="btn btn-outline-secondary" title="Edit">
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
