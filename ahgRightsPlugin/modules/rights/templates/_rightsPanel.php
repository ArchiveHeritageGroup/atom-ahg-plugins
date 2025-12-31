<?php
/**
 * Unified Rights Panel Component
 * 
 * Include in ISAD and Museum detail pages:
 * <?php include_partial('rights/rightsPanel', ['resource' => $resource]); ?>
 * 
 * @var QubitInformationObject $resource
 */

use Illuminate\Database\Capsule\Manager as DB;

// Initialize service
if (!class_exists('RightsService')) {
    require_once sfConfig::get('sf_plugins_dir') . '/ahgRightsPlugin/lib/Service/RightsService.php';
}

$rightsService = RightsService::getInstance();
$sf_user = sfContext::getInstance()->getUser();
$canEdit = $sf_user->isAdministrator() || $sf_user->hasCredential('editor');

// Get all rights data
$objectId = $resource->id;
$rights = $rightsService->getRightsForObject($objectId);
$embargo = $rightsService->getEmbargo($objectId);
$tkLabels = $rightsService->getTkLabelsForObject($objectId);
$orphanWork = $rightsService->getOrphanWork($objectId);

// Check if user has access
$accessCheck = $rightsService->checkAccess($objectId, 'information_object', $sf_user->getAttribute('user_id'));
?>

<section id="rightsArea" class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-balance-scale me-2"></i><?php echo __('Rights'); ?>
        </h5>
        <?php if ($canEdit): ?>
            <a href="<?php echo url_for(['module' => 'rights', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i><?php echo __('Add rights'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <?php if (!$accessCheck['allowed']): ?>
            <!-- Access Restrictions Alert -->
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong><?php echo __('Access Restricted'); ?></strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($accessCheck['restrictions'] as $restriction): ?>
                        <li><?php echo esc_entities($restriction); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($embargo): ?>
            <!-- Embargo Notice -->
            <div class="alert alert-danger mb-3">
                <div class="d-flex align-items-start">
                    <i class="fas fa-lock fa-2x me-3 mt-1"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1"><?php echo __('Under Embargo'); ?></h6>
                        <p class="mb-1">
                            <strong><?php echo __('Type'); ?>:</strong> 
                            <?php echo esc_entities(ucwords(str_replace('_', ' ', $embargo['embargo_type']))); ?>
                        </p>
                        <p class="mb-1">
                            <strong><?php echo __('Reason'); ?>:</strong> 
                            <?php echo esc_entities(ucwords(str_replace('_', ' ', $embargo['reason']))); ?>
                        </p>
                        <?php if ($embargo['end_date']): ?>
                            <p class="mb-0">
                                <strong><?php echo __('Until'); ?>:</strong> 
                                <?php echo date('j F Y', strtotime($embargo['end_date'])); ?>
                                <?php if ($embargo['auto_release']): ?>
                                    <span class="badge bg-info ms-2"><?php echo __('Auto-release'); ?></span>
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <p class="mb-0"><em><?php echo __('No end date specified'); ?></em></p>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                            <div class="mt-2">
                                <a href="<?php echo url_for(['module' => 'rights', 'action' => 'editEmbargo', 'slug' => $resource->slug]); ?>" class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-light ms-1" data-bs-toggle="modal" data-bs-target="#releaseEmbargoModal">
                                    <i class="fas fa-unlock me-1"></i><?php echo __('Release'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($tkLabels)): ?>
            <!-- TK Labels -->
            <div class="mb-3">
                <h6 class="text-muted mb-2"><?php echo __('Traditional Knowledge Labels'); ?></h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($tkLabels as $label): ?>
                        <span class="badge" style="background-color: <?php echo esc_entities($label['color'] ?? '#666'); ?>; font-size: 0.9em;" 
                              data-bs-toggle="tooltip" title="<?php echo esc_entities($label['description'] ?? ''); ?>">
                            <?php if ($label['icon_url']): ?>
                                <img src="<?php echo esc_entities($label['icon_url']); ?>" alt="" style="height: 16px; margin-right: 4px;">
                            <?php endif; ?>
                            <?php echo esc_entities($label['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php if ($canEdit): ?>
                    <a href="<?php echo url_for(['module' => 'rights', 'action' => 'tkLabels', 'slug' => $resource->slug]); ?>" class="btn btn-sm btn-link p-0 mt-1">
                        <i class="fas fa-edit me-1"></i><?php echo __('Manage TK Labels'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($orphanWork): ?>
            <!-- Orphan Work Status -->
            <div class="alert alert-info mb-3">
                <h6 class="alert-heading">
                    <i class="fas fa-search me-2"></i><?php echo __('Orphan Work'); ?>
                </h6>
                <p class="mb-1">
                    <strong><?php echo __('Status'); ?>:</strong>
                    <span class="badge bg-<?php echo $orphanWork['status'] === 'confirmed_orphan' ? 'warning' : ($orphanWork['status'] === 'rights_holder_found' ? 'success' : 'secondary'); ?>">
                        <?php echo esc_entities(ucwords(str_replace('_', ' ', $orphanWork['status']))); ?>
                    </span>
                </p>
                <?php if ($orphanWork['search_completed_date']): ?>
                    <p class="mb-0 small">
                        <?php echo __('Diligent search completed'); ?>: <?php echo date('j F Y', strtotime($orphanWork['search_completed_date'])); ?>
                    </p>
                <?php endif; ?>
                <?php if ($canEdit): ?>
                    <a href="<?php echo url_for(['module' => 'rights', 'action' => 'orphanWork', 'slug' => $resource->slug]); ?>" class="btn btn-sm btn-link p-0 mt-1">
                        <?php echo __('View/Edit Details'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($rights)): ?>
            <p class="text-muted mb-0">
                <i class="fas fa-info-circle me-1"></i>
                <?php echo __('No rights records have been added.'); ?>
            </p>
        <?php else: ?>
            <!-- Rights Records -->
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Basis'); ?></th>
                            <th><?php echo __('Rights Statement'); ?></th>
                            <th><?php echo __('Acts'); ?></th>
                            <th><?php echo __('Dates'); ?></th>
                            <?php if ($canEdit): ?>
                                <th style="width: 100px;"><?php echo __('Actions'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rights as $right): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?php echo $right['basis'] === 'copyright' ? 'primary' : ($right['basis'] === 'license' ? 'success' : 'secondary'); ?>">
                                        <?php echo esc_entities(ucfirst($right['basis'])); ?>
                                    </span>
                                    <?php if ($right['basis'] === 'copyright' && $right['copyright_status']): ?>
                                        <br><small class="text-muted"><?php echo esc_entities(ucwords(str_replace('_', ' ', $right['copyright_status']))); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($right['statement_name']): ?>
                                        <a href="<?php echo esc_entities($right['statement_uri']); ?>" target="_blank" class="text-decoration-none">
                                            <?php echo esc_entities($right['statement_name']); ?>
                                            <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                        </a>
                                    <?php elseif ($right['cc_name']): ?>
                                        <a href="<?php echo esc_entities($right['cc_uri']); ?>" target="_blank" class="text-decoration-none">
                                            <i class="fab fa-creative-commons me-1"></i>
                                            <?php echo esc_entities($right['cc_name']); ?>
                                            <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                        </a>
                                    <?php elseif ($right['rights_holder_name']): ?>
                                        <?php echo esc_entities($right['rights_holder_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($right['granted_rights'])): ?>
                                        <?php foreach ($right['granted_rights'] as $grant): ?>
                                            <span class="badge bg-<?php echo $grant['restriction'] === 'allow' ? 'success' : ($grant['restriction'] === 'disallow' ? 'danger' : 'warning'); ?> me-1">
                                                <?php echo esc_entities(ucfirst($grant['act'])); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($right['start_date'] || $right['end_date']): ?>
                                        <?php echo $right['start_date'] ? date('Y-m-d', strtotime($right['start_date'])) : '...'; ?>
                                        —
                                        <?php echo $right['end_date'] ? date('Y-m-d', strtotime($right['end_date'])) : ($right['end_date_open'] ? __('Open') : '...'); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canEdit): ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo url_for(['module' => 'rights', 'action' => 'edit', 'slug' => $resource->slug, 'id' => $right['id']]); ?>" 
                                               class="btn btn-outline-primary" title="<?php echo __('Edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteRightModal" 
                                                    data-right-id="<?php echo $right['id']; ?>"
                                                    title="<?php echo __('Delete'); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php if ($right['rights_note']): ?>
                                <tr class="table-light">
                                    <td colspan="<?php echo $canEdit ? 5 : 4; ?>" class="small text-muted py-1 ps-4">
                                        <i class="fas fa-comment me-1"></i><?php echo esc_entities($right['rights_note']); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($canEdit): ?>
<!-- Delete Right Modal -->
<div class="modal fade" id="deleteRightModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('Delete Rights Record'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?php echo __('Are you sure you want to delete this rights record? This action cannot be undone.'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                <form id="deleteRightForm" method="post" action="" style="display: inline;">
                    <input type="hidden" name="_csrf_token" value="<?php echo $sf_user->getAttribute('_csrf_token'); ?>">
                    <button type="submit" class="btn btn-danger"><?php echo __('Delete'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($embargo): ?>
<!-- Release Embargo Modal -->
<div class="modal fade" id="releaseEmbargoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('Release Embargo'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?php echo __('Are you sure you want to release this embargo immediately?'); ?></p>
                <p class="text-muted small"><?php echo __('The item will become accessible according to its other rights settings.'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                <form method="post" action="<?php echo url_for(['module' => 'rights', 'action' => 'releaseEmbargo', 'slug' => $resource->slug, 'id' => $embargo['id']]); ?>" style="display: inline;">
                    <input type="hidden" name="_csrf_token" value="<?php echo $sf_user->getAttribute('_csrf_token'); ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-unlock me-1"></i><?php echo __('Release Now'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete modal
    var deleteModal = document.getElementById('deleteRightModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var rightId = button.getAttribute('data-right-id');
            var form = document.getElementById('deleteRightForm');
            form.action = '<?php echo url_for(['module' => 'rights', 'action' => 'delete', 'slug' => $resource->slug]); ?>/' + rightId;
        });
    }
    
    // Initialize tooltips
    var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
});
</script>
<?php endif; ?>
