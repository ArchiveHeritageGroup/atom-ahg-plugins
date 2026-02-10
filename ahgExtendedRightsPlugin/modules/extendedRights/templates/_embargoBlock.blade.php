@php
/**
 * Embargo Block Partial
 * Shows embargo message when content is restricted
 *
 * Usage: include_partial('extendedRights/embargoBlock', ['objectId' => $resource->id, 'type' => 'digital_object'])
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
@endphp
<div class="alert alert-warning border-warning mb-3 embargo-notice">
    <div class="d-flex align-items-start">
        <i class="fas fa-lock fa-2x me-3 text-warning"></i>
        <div>
            <h5 class="alert-heading mb-1">{{ $embargoInfo['type_label'] }}</h5>
            <p class="mb-1">{{ $message }}</p>
            @if (!$embargoInfo['is_perpetual'] && $embargoInfo['end_date'])
                <small class="text-muted">
                    <i class="fas fa-calendar-alt me-1"></i>
                    {{ __('Available from: %1%', ['%1%' => date('j F Y', strtotime($embargoInfo['end_date']))]) }}
                </small>
            @elseif ($embargoInfo['is_perpetual'])
                <small class="text-muted">
                    <i class="fas fa-ban me-1"></i>
                    {{ __('Indefinite restriction') }}
                </small>
            @endif
        </div>
    </div>
</div>
