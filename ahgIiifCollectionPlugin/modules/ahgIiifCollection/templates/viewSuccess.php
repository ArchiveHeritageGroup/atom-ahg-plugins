<?php decorate_with('layout_2col.php') ?>

<?php
// Initialize database for image checking
require_once sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';

if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
    \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
}

use Illuminate\Database\Capsule\Manager as DB;

// Usage IDs
$USAGE_REFERENCE = 141;
$USAGE_THUMBNAIL = 142;

// Media type IDs
$MEDIA_IMAGE = 136;
$MEDIA_AUDIO = 135;
$MEDIA_VIDEO = 138;
$MEDIA_TEXT = 137;

// Supported IIIF formats (images only)
$iiifSupportedFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/tiff', 'image/jp2'];

// Get all object IDs from collection items
$objectIds = [];
foreach ($collection->items as $item) {
    if ($item->object_id) {
        $objectIds[] = $item->object_id;
    }
}

// Get digital objects and their derivatives for all items
$imageStatus = [];
if (!empty($objectIds)) {
    // Get master digital objects
    $masters = DB::table('digital_object')
        ->whereIn('object_id', $objectIds)
        ->whereNull('parent_id')
        ->select('id', 'object_id', 'name', 'path', 'mime_type', 'media_type_id')
        ->get()
        ->keyBy('object_id');
    
    // Get derivatives
    $masterIds = $masters->pluck('id')->toArray();
    $derivatives = [];
    if (!empty($masterIds)) {
        $derivatives = DB::table('digital_object')
            ->whereIn('parent_id', $masterIds)
            ->whereIn('usage_id', [$USAGE_REFERENCE, $USAGE_THUMBNAIL])
            ->select('id', 'parent_id', 'name', 'path', 'usage_id')
            ->get()
            ->groupBy('parent_id');
    }
    
    // Build status for each object
    foreach ($objectIds as $objId) {
        $master = $masters->get($objId);
        $status = [
            'has_master' => false,
            'has_reference' => false,
            'has_thumbnail' => false,
            'iiif_compatible' => false,
            'displayable' => false,
            'media_type' => null,
            'is_image' => false,
            'is_audio' => false,
            'is_video' => false,
            'warning' => null,
        ];
        
        if ($master) {
            $status['has_master'] = true;
            $status['media_type'] = $master->media_type_id;
            $status['is_image'] = ($master->media_type_id == $MEDIA_IMAGE);
            $status['is_audio'] = ($master->media_type_id == $MEDIA_AUDIO);
            $status['is_video'] = ($master->media_type_id == $MEDIA_VIDEO);
            
            $masterDerivs = $derivatives[$master->id] ?? collect();
            $status['has_reference'] = $masterDerivs->contains('usage_id', $USAGE_REFERENCE);
            $status['has_thumbnail'] = $masterDerivs->contains('usage_id', $USAGE_THUMBNAIL);
            
            // For carousels/galleries, we need a visual representation
            // Images: can use IIIF or derivatives
            // Audio/Video: MUST have a thumbnail derivative to display
            // Text/Other: not displayable in visual carousels
            
            if ($status['is_image']) {
                $status['iiif_compatible'] = in_array($master->mime_type, $iiifSupportedFormats);
                $status['displayable'] = $status['has_reference'] || $status['has_thumbnail'] || $status['iiif_compatible'];
                
                if (!$status['displayable']) {
                    $status['warning'] = __('Unsupported image format: %1%', ['%1%' => $master->mime_type]);
                }
            } elseif ($status['is_video']) {
                // Videos need at least a thumbnail to show in carousel
                $status['displayable'] = $status['has_reference'] || $status['has_thumbnail'];
                
                if (!$status['displayable']) {
                    $status['warning'] = __('Video without preview image - generate thumbnail');
                }
            } elseif ($status['is_audio']) {
                // Audio files need a thumbnail/cover art to show in visual carousel
                $status['displayable'] = $status['has_reference'] || $status['has_thumbnail'];
                
                if (!$status['displayable']) {
                    $status['warning'] = __('Audio without cover art - not displayable in carousel');
                }
            } else {
                // Text, Other - not displayable in visual carousels
                $status['displayable'] = false;
                $status['warning'] = __('Non-visual media type - not displayable in carousel');
            }
        } else {
            $status['warning'] = __('No digital object attached');
        }
        
        $imageStatus[$objId] = $status;
    }
}

// Count displayable items
$displayableCount = 0;
$warningCount = 0;
foreach ($collection->items as $item) {
    if ($item->object_id && isset($imageStatus[$item->object_id])) {
        if ($imageStatus[$item->object_id]['displayable']) {
            $displayableCount++;
        } else {
            $warningCount++;
        }
    } elseif ($item->manifest_uri) {
        $displayableCount++; // External manifests assumed displayable
    } else {
        $warningCount++;
    }
}
?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Actions') ?></h5>
        </div>
        <div class="card-body">
            <a href="<?php echo '/index.php/ahgIiifCollection/manifest?slug=' . $collection->slug ?>" class="btn btn-info w-100 mb-2" target="_blank">
                <i class="fas fa-code me-2"></i><?php echo __('View IIIF JSON') ?>
            </a>
            <?php if ($sf_user->isAuthenticated()): ?>
            <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'addItems', 'id' => $collection->id]) ?>" class="btn btn-success w-100 mb-2">
                <i class="fas fa-plus me-2"></i><?php echo __('Add Items') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'edit', 'id' => $collection->id]) ?>" class="btn btn-warning w-100 mb-2">
                <i class="fas fa-edit me-2"></i><?php echo __('Edit Collection') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'new', 'parent_id' => $collection->id]) ?>" class="btn btn-outline-success w-100 mb-2">
                <i class="fas fa-folder-plus me-2"></i><?php echo __('Create Subcollection') ?>
            </a>
            <hr>
            <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'delete', 'id' => $collection->id]) ?>" class="btn btn-danger w-100" onclick="return confirm('<?php echo __('Are you sure you want to delete this collection?') ?>')">
                <i class="fas fa-trash me-2"></i><?php echo __('Delete Collection') ?>
            </a>
            <?php endif ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Details') ?></h5>
        </div>
        <div class="card-body">
            <dl class="mb-0">
                <dt><?php echo __('Items') ?></dt>
                <dd><?php echo count($collection->items) ?></dd>

                <dt><?php echo __('Displayable') ?></dt>
                <dd>
                    <span class="badge bg-success"><?php echo $displayableCount ?></span>
                    <?php if ($warningCount > 0): ?>
                    <span class="badge bg-warning text-dark"><?php echo $warningCount ?> <?php echo __('with issues') ?></span>
                    <?php endif ?>
                </dd>

                <dt><?php echo __('Subcollections') ?></dt>
                <dd><?php echo count($collection->subcollections) ?></dd>

                <dt><?php echo __('Visibility') ?></dt>
                <dd>
                    <?php if ($collection->is_public): ?>
                    <span class="badge bg-success"><?php echo __('Public') ?></span>
                    <?php else: ?>
                    <span class="badge bg-warning"><?php echo __('Private') ?></span>
                    <?php endif ?>
                </dd>

                <?php if ($collection->viewing_hint): ?>
                <dt><?php echo __('Viewing Hint') ?></dt>
                <dd><code><?php echo esc_entities($collection->viewing_hint) ?></code></dd>
                <?php endif ?>

                <dt><?php echo __('IIIF URI') ?></dt>
                <dd><small><code><?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'manifest', 'slug' => $collection->slug], true) ?></code></small></dd>
            </dl>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'index']) ?>"><?php echo __('Collections') ?></a></li>
        <?php foreach ($breadcrumbs as $bc): ?>
            <?php if ($bc->id === $collection->id): ?>
            <li class="breadcrumb-item active"><?php echo esc_entities($bc->display_name) ?></li>
            <?php else: ?>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'view', 'id' => $bc->id]) ?>"><?php echo esc_entities($bc->display_name) ?></a></li>
            <?php endif ?>
        <?php endforeach ?>
    </ol>
</nav>
<h1><i class="fas fa-layer-group me-2"></i><?php echo esc_entities($collection->display_name) ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="iiif-collection-view">
    <?php if ($collection->display_description): ?>
    <div class="lead mb-4"><?php echo esc_entities($collection->display_description) ?></div>
    <?php endif ?>

    <?php if ($collection->attribution): ?>
    <p class="text-muted"><strong><?php echo __('Attribution') ?>:</strong> <?php echo esc_entities($collection->attribution) ?></p>
    <?php endif ?>

    <?php if ($warningCount > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong><?php echo __('Display Warning:') ?></strong>
        <?php echo __('%1% item(s) will not display in carousels/galleries.', ['%1%' => $warningCount]) ?>
        <small class="d-block mt-1"><?php echo __('Audio/video files need thumbnails, and only image files can be displayed directly.') ?></small>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif ?>

    <?php if (!empty($collection->subcollections)): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-folder me-2"></i><?php echo __('Subcollections') ?></h5>
        </div>
        <div class="card-body">
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <?php foreach ($collection->subcollections as $sub): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">
                                <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'view', 'id' => $sub->id]) ?>">
                                    <i class="fas fa-folder me-1"></i><?php echo esc_entities($sub->display_name) ?>
                                </a>
                            </h6>
                            <span class="badge bg-secondary"><?php echo $sub->item_count ?> <?php echo __('items') ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
    <?php endif ?>

    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-images me-2"></i><?php echo __('Items') ?> (<?php echo count($collection->items) ?>)</h5>
            <?php if ($sf_user->isAuthenticated() && $warningCount > 0): ?>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="hideProblematic" onchange="toggleProblematicItems(this.checked)">
                <label class="form-check-label" for="hideProblematic"><?php echo __('Hide non-displayable') ?></label>
            </div>
            <?php endif ?>
        </div>
        <div class="card-body">
            <?php if (empty($collection->items)): ?>
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo __('No items in this collection yet.') ?>
                <?php if ($sf_user->isAuthenticated()): ?>
                <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'addItems', 'id' => $collection->id]) ?>"><?php echo __('Add items') ?></a>
                <?php endif ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;"></th>
                            <th><?php echo __('Title') ?></th>
                            <th><?php echo __('Identifier') ?></th>
                            <th><?php echo __('Type') ?></th>
                            <th style="width: 80px;"><?php echo __('Media') ?></th>
                            <th style="width: 120px;"><?php echo __('Status') ?></th>
                            <th style="width: 150px;"><?php echo __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody class="sortable-items">
                        <?php foreach ($collection->items as $item): ?>
                        <?php 
                            $status = $imageStatus[$item->object_id] ?? null;
                            $isDisplayable = $item->manifest_uri || ($status && $status['displayable']);
                            $rowClass = $isDisplayable ? '' : 'table-warning item-problematic';
                        ?>
                        <tr data-item-id="<?php echo $item->id ?>" class="<?php echo $rowClass ?>">
                            <td class="drag-handle text-center text-muted"><i class="fas fa-grip-vertical"></i></td>
                            <td>
                                <?php if ($item->slug): ?>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]) ?>">
                                    <?php echo esc_entities($item->label ?: $item->object_title ?: __('Untitled')) ?>
                                </a>
                                <?php elseif ($item->manifest_uri): ?>
                                <a href="<?php echo esc_entities($item->manifest_uri) ?>" target="_blank">
                                    <?php echo esc_entities($item->label ?: __('External Manifest')) ?>
                                    <i class="fas fa-external-link-alt ms-1 small"></i>
                                </a>
                                <?php else: ?>
                                <?php echo esc_entities($item->label ?: __('Untitled')) ?>
                                <?php endif ?>
                            </td>
                            <td><code><?php echo esc_entities($item->identifier ?: '-') ?></code></td>
                            <td>
                                <?php if ($item->item_type === 'collection'): ?>
                                <span class="badge bg-info"><?php echo __('Collection') ?></span>
                                <?php else: ?>
                                <span class="badge bg-primary"><?php echo __('Manifest') ?></span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if ($status): ?>
                                    <?php if ($status['is_image']): ?>
                                        <span class="badge bg-success"><i class="fas fa-image"></i></span>
                                    <?php elseif ($status['is_video']): ?>
                                        <span class="badge bg-info"><i class="fas fa-film"></i></span>
                                    <?php elseif ($status['is_audio']): ?>
                                        <span class="badge bg-secondary"><i class="fas fa-music"></i></span>
                                    <?php else: ?>
                                        <span class="badge bg-dark"><i class="fas fa-file"></i></span>
                                    <?php endif ?>
                                <?php elseif ($item->manifest_uri): ?>
                                    <span class="badge bg-info"><i class="fas fa-external-link-alt"></i></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">-</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if ($item->manifest_uri): ?>
                                    <span class="badge bg-info" title="<?php echo __('External manifest') ?>">
                                        <i class="fas fa-check me-1"></i><?php echo __('External') ?>
                                    </span>
                                <?php elseif ($status): ?>
                                    <?php if ($status['displayable']): ?>
                                        <span class="badge bg-success" title="<?php echo $status['has_reference'] ? __('Reference image') : ($status['has_thumbnail'] ? __('Thumbnail') : __('IIIF compatible')) ?>">
                                            <i class="fas fa-check me-1"></i><?php echo __('OK') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark" title="<?php echo esc_entities($status['warning']) ?>">
                                            <i class="fas fa-exclamation-triangle me-1"></i><?php echo __('No preview') ?>
                                        </span>
                                    <?php endif ?>
                                <?php else: ?>
                                    <span class="badge bg-danger" title="<?php echo __('No digital object') ?>">
                                        <i class="fas fa-times me-1"></i><?php echo __('Missing') ?>
                                    </span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if ($item->slug): ?>
                                <a href="/iiif-manifest.php?slug=<?php echo $item->slug ?>" class="btn btn-sm btn-outline-info" target="_blank" title="<?php echo __('View IIIF Manifest') ?>">
                                    <i class="fas fa-code"></i>
                                </a>
                                <?php endif ?>
                                <?php if ($sf_user->isAuthenticated()): ?>
                                <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'removeItem', 'item_id' => $item->id, 'collection_id' => $collection->id]) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('<?php echo __('Remove this item from the collection?') ?>')"
                                   title="<?php echo __('Remove') ?>">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif ?>
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

<?php if ($sf_user->isAuthenticated()): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.querySelector('.sortable-items');
    if (tbody) {
        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                var itemIds = Array.from(tbody.querySelectorAll('tr')).map(function(row) {
                    return row.dataset.itemId;
                });

                fetch('<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'reorder']) ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'collection_id=<?php echo $collection->id ?>&item_ids[]=' + itemIds.join('&item_ids[]=')
                });
            }
        });
    }
});

function toggleProblematicItems(hide) {
    document.querySelectorAll('.item-problematic').forEach(function(row) {
        row.style.display = hide ? 'none' : '';
    });
}
</script>
<?php endif ?>

<style>
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }
.sortable-ghost { opacity: 0.4; background: #f0f0f0; }
</style>
<?php end_slot() ?>
