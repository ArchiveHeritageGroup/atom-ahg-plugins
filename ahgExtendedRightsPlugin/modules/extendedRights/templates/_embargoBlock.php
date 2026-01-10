<?php
/**
 * Embargo Block Partial
 * Shows embargo message when content is restricted
 * 
 * Usage: <?php include_partial('extendedRights/embargoBlock', ['objectId' => $resource->id, 'type' => 'digital_object']); ?>
 * Types: record, metadata, thumbnail, digital_object, download
 */

$objectId = $objectId ?? null;
$type = $type ?? 'record';

if (!$objectId) {
    return;
}

$embargoInfo = EmbargoHelper::getDisplayInfo($objectId);
if (!$embargoInfo) {
    return;
}

$typeMessages = [
    'record' => __('This record is currently under embargo and not available for public viewing.'),
    'metadata' => __('The metadata for this record is restricted.'),
    'thumbnail' => __('Preview images are not available for this record.'),
    'digital_object' => __('Digital content for this record is restricted.'),
    'download' => __('Downloads are not available for this record.'),
];

$message = $embargoInfo['public_message'] ?? $typeMessages[$type] ?? $typeMessages['record'];
?>
<div class="alert alert-warning border-warning mb-3 embargo-notice">
    <div class="d-flex align-items-start">
        <i class="fas fa-lock fa-2x me-3 text-warning"></i>
        <div>
            <h5 class="alert-heading mb-1"><?php echo esc_entities($embargoInfo['type_label']); ?></h5>
            <p class="mb-1"><?php echo esc_entities($message); ?></p>
            <?php if (!$embargoInfo['is_perpetual'] && $embargoInfo['end_date']): ?>
                <small class="text-muted">
                    <i class="fas fa-calendar-alt me-1"></i>
                    <?php echo __('Available from: %1%', ['%1%' => date('j F Y', strtotime($embargoInfo['end_date']))]); ?>
                </small>
            <?php elseif ($embargoInfo['is_perpetual']): ?>
                <small class="text-muted">
                    <i class="fas fa-ban me-1"></i>
                    <?php echo __('Indefinite restriction'); ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>
