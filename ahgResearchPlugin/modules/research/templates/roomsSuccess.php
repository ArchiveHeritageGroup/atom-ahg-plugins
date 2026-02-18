<?php use_helper('Date') ?>

<?php echo get_partial('layout_start', ['title' => 'Research Rooms']) ?>

<div class="container-fluid py-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/research">Research</a></li>
            <?php if (!empty($project)): ?>
                <li class="breadcrumb-item"><a href="/research/project/<?php echo (int) $project->id; ?>"><?php echo htmlspecialchars($project->name ?? 'Project'); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active">Rooms</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-users me-2"></i>Research Rooms</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoomModal">
            <i class="fas fa-plus me-1"></i>Create Room
        </button>
    </div>

    <?php if (empty($rooms)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-1"></i>
            No research rooms yet. Create one to start collaborating on IIIF resources.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($rooms as $room): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title"><?php echo htmlspecialchars($room->name); ?></h5>
                                <?php
                                    $statusClass = match($room->status) {
                                        'active' => 'success',
                                        'archived' => 'secondary',
                                        default => 'warning',
                                    };
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($room->status); ?></span>
                            </div>
                            <?php if (!empty($room->description)): ?>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars(mb_substr($room->description, 0, 120)); ?></p>
                            <?php endif; ?>
                            <div class="d-flex gap-3 text-muted small mt-2">
                                <span><i class="fas fa-users me-1"></i><?php echo (int) $room->participant_count; ?> participants</span>
                                <span><i class="fas fa-image me-1"></i><?php echo (int) $room->manifest_count; ?> items</span>
                            </div>
                            <?php if (!empty($room->creator_name)): ?>
                                <div class="text-muted small mt-1">
                                    Created by <?php echo htmlspecialchars($room->creator_name); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="/research/room/<?php echo (int) $room->id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Open Room
                            </a>
                            <a href="/research/room/<?php echo (int) $room->id; ?>/manifest.json" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="fas fa-file-code me-1"></i>Manifest
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Room Modal -->
<div class="modal fade" id="createRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/research/room/create/<?php echo (int) $projectId; ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Research Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="room-name" class="form-label">Room Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="room-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="room-desc" class="form-label">Description</label>
                        <textarea class="form-control" id="room-desc" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="room-max" class="form-label">Max Participants</label>
                        <input type="number" class="form-control" id="room-max" name="max_participants" value="10" min="2" max="50">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Create</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php echo get_partial('layout_end') ?>
