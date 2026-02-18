<?php use_helper('Date') ?>
<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php echo get_partial('layout_start', ['title' => htmlspecialchars($room->name)]) ?>

<div class="container-fluid py-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/research">Research</a></li>
            <li class="breadcrumb-item"><a href="/research/project/<?php echo (int) $room->project_id; ?>/rooms">Rooms</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($room->name); ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2><?php echo htmlspecialchars($room->name); ?></h2>
            <?php if (!empty($room->description)): ?>
                <p class="text-muted"><?php echo htmlspecialchars($room->description); ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <?php
                $statusClass = match($room->status) {
                    'active' => 'success',
                    'archived' => 'secondary',
                    default => 'warning',
                };
            ?>
            <span class="badge bg-<?php echo $statusClass; ?> align-self-start fs-6"><?php echo ucfirst($room->status); ?></span>
            <?php if ($isOwner): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form method="post" action="/research/room/<?php echo (int) $room->id; ?>/update" class="px-3 py-1">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($room->name); ?>">
                                <input type="hidden" name="description" value="<?php echo htmlspecialchars($room->description ?? ''); ?>">
                                <input type="hidden" name="max_participants" value="<?php echo (int) $room->max_participants; ?>">
                                <?php if ($room->status === 'draft'): ?>
                                    <input type="hidden" name="status" value="active">
                                    <button type="submit" class="dropdown-item"><i class="fas fa-play me-1"></i>Activate</button>
                                <?php elseif ($room->status === 'active'): ?>
                                    <input type="hidden" name="status" value="archived">
                                    <button type="submit" class="dropdown-item"><i class="fas fa-archive me-1"></i>Archive</button>
                                <?php endif; ?>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Left: IIIF Items -->
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-image me-1"></i>Shared Items (<?php echo count($manifests); ?>)</h5>
                    <div class="btn-group btn-group-sm">
                        <a href="/research/room/<?php echo (int) $room->id; ?>/manifest.json" class="btn btn-outline-info" target="_blank" title="IIIF Collection Manifest">
                            <i class="fas fa-file-code me-1"></i>Manifest
                        </a>
                        <a href="/research/room/<?php echo (int) $room->id; ?>/annotations.json" class="btn btn-outline-info" target="_blank" title="W3C Annotation Export">
                            <i class="fas fa-sticky-note me-1"></i>Annotations
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($manifests)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            No items shared in this room yet. Add items from your project resources.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($manifests as $m): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($m->object_slug)): ?>
                                                    <a href="/<?php echo htmlspecialchars($m->object_slug); ?>"><?php echo htmlspecialchars($m->object_title ?? 'Untitled'); ?></a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($m->object_title ?? "Object #{$m->object_id}"); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($m->derivative_type); ?></span></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($m->created_at ?? ''); ?></td>
                                            <td>
                                                <?php if (!empty($m->object_slug)): ?>
                                                    <a href="/iiif/manifest/<?php echo htmlspecialchars($m->object_slug); ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="View IIIF Manifest">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Participants -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-1"></i>Participants (<?php echo count($participants); ?>/<?php echo (int) $room->max_participants; ?>)</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($participants as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($p->user_name ?? "User #{$p->user_id}"); ?>
                            </div>
                            <?php
                                $roleClass = match($p->role) {
                                    'owner' => 'primary',
                                    'editor' => 'success',
                                    default => 'secondary',
                                };
                            ?>
                            <span class="badge bg-<?php echo $roleClass; ?>"><?php echo ucfirst($p->role); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php echo get_partial('layout_end') ?>
