<?php
/**
 * Rights section for detail layouts
 * 
 * Include in _detail.php after access section:
 * <?php include __DIR__ . '/_rights_section.php'; ?>
 * 
 * Expects $data from DisplayService::prepareForDisplay() with 'rights' key
 */

// Check if rights data exists
if (empty($data['rights'])) return;

$rights = $data['rights'];
$embargo = $rights['embargo'] ?? null;
$records = $rights['records'] ?? [];
$tkLabels = $rights['tk_labels'] ?? [];
$accessCheck = $rights['access_check'] ?? null;
$canEdit = $rights['can_edit'] ?? false;
$slug = $object->slug ?? '';
?>

<section class="field-section rights-section mb-4">
    <h5 class="section-title border-bottom pb-2 mb-3 d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-balance-scale text-muted me-2"></i><?php echo __('Rights'); ?>
        </span>
        <?php if ($canEdit): ?>
            <a href="<?php echo url_for(['module' => 'rights', 'action' => 'add', 'slug' => $slug]); ?>" 
               class="btn btn-sm btn-outline-primary d-print-none">
                <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
            </a>
        <?php endif; ?>
    </h5>

    <?php // Access Restrictions Alert ?>
    <?php if ($accessCheck && !$accessCheck['allowed']): ?>
        <div class="alert alert-warning py-2">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong><?php echo __('Access Restricted'); ?></strong>
            <?php if (!empty($accessCheck['restrictions'])): ?>
                <ul class="mb-0 mt-1 small">
                    <?php foreach ($accessCheck['restrictions'] as $r): ?>
                        <li><?php echo esc_entities($r); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php // Embargo Notice ?>
    <?php if ($embargo): ?>
        <div class="alert alert-danger py-2 mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-lock me-2"></i>
                <div>
                    <strong><?php echo __('Under Embargo'); ?></strong>
                    <span class="ms-2 badge bg-<?php echo $embargo['embargo_type'] === 'full' ? 'danger' : 'warning'; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $embargo['embargo_type'])); ?>
                    </span>
                    <?php if ($embargo['end_date']): ?>
                        <span class="ms-2 small">
                            <?php echo __('Until'); ?>: <?php echo date('j M Y', strtotime($embargo['end_date'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php // TK Labels ?>
    <?php if (!empty($tkLabels)): ?>
        <div class="mb-3">
            <small class="text-muted d-block mb-1"><?php echo __('Traditional Knowledge'); ?></small>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($tkLabels as $label): ?>
                    <span class="badge" 
                          style="background-color: <?php echo $label['color'] ?? '#666'; ?>;"
                          title="<?php echo esc_entities($label['description'] ?? ''); ?>">
                        <?php echo esc_entities($label['name']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php // Rights Records ?>
    <?php if (empty($records)): ?>
        <p class="text-muted small mb-0">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('No rights records have been added.'); ?>
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Basis'); ?></th>
                        <th><?php echo __('Statement/License'); ?></th>
                        <th><?php echo __('Acts'); ?></th>
                        <th><?php echo __('Period'); ?></th>
                        <?php if ($canEdit): ?>
                            <th class="d-print-none" style="width: 80px;"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td>
                                <span class="badge bg-<?php echo match($r['basis']) {
                                    'copyright' => 'primary',
                                    'license' => 'success',
                                    'statute' => 'info',
                                    'donor' => 'secondary',
                                    'policy' => 'warning',
                                    default => 'dark'
                                }; ?>">
                                    <?php echo ucfirst($r['basis']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($r['statement_name'])): ?>
                                    <a href="<?php echo $r['statement_uri']; ?>" target="_blank" class="text-decoration-none">
                                        <?php echo esc_entities($r['statement_name']); ?>
                                        <i class="fas fa-external-link-alt fa-xs"></i>
                                    </a>
                                <?php elseif (!empty($r['cc_name'])): ?>
                                    <a href="<?php echo $r['cc_uri']; ?>" target="_blank" class="text-decoration-none">
                                        <i class="fab fa-creative-commons me-1"></i>
                                        <?php echo esc_entities($r['cc_code']); ?>
                                    </a>
                                <?php elseif (!empty($r['rights_holder_name'])): ?>
                                    <?php echo esc_entities($r['rights_holder_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['granted_rights'])): ?>
                                    <?php foreach ($r['granted_rights'] as $g): ?>
                                        <span class="badge bg-<?php echo $g['restriction'] === 'allow' ? 'success' : ($g['restriction'] === 'disallow' ? 'danger' : 'warning'); ?> me-1" title="<?php echo esc_entities($g['restriction_reason'] ?? ''); ?>">
                                            <?php echo ucfirst($g['act']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?php if ($r['start_date'] || $r['end_date']): ?>
                                    <?php echo $r['start_date'] ? date('Y', strtotime($r['start_date'])) : '...'; ?>
                                    –
                                    <?php echo $r['end_date'] ? date('Y', strtotime($r['end_date'])) : ($r['end_date_open'] ? __('Open') : '...'); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <?php if ($canEdit): ?>
                                <td class="d-print-none">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url_for(['module' => 'rights', 'action' => 'edit', 'slug' => $slug, 'id' => $r['id']]); ?>" 
                                           class="btn btn-outline-secondary btn-sm" title="<?php echo __('Edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
