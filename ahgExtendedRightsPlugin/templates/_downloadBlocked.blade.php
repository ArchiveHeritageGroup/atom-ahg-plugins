@php
// Load filter if not already loaded
if (!class_exists('DigitalObjectEmbargoFilter')) {
    require_once sfConfig::get('sf_root_dir') . '/plugins/ahgExtendedRightsPlugin/lib/DigitalObjectEmbargoFilter.php';
}

// Get result if not provided
if (!isset($result) && isset($objectId)) {
    $result = DigitalObjectEmbargoFilter::canDownloadByObjectId($objectId);
}

// If download is allowed or no result, don't show anything
if (!isset($result) || $result['allowed']) {
    return;
}

$embargoInfo = $result['embargo_info'] ?? [];
$canRequestAccess = $result['can_request_access'] ?? false;
$showRequestLink = $showRequestLink ?? true;

// Icon based on embargo type
$iconClass = 'fa-lock';
$alertClass = 'alert-warning';

if (isset($embargoInfo['type'])) {
    switch ($embargoInfo['type']) {
        case 'full':
            $iconClass = 'fa-ban';
            $alertClass = 'alert-danger';
            break;
        case 'digital_only':
            $iconClass = 'fa-download';
            $alertClass = 'alert-warning';
            break;
        case 'metadata_only':
            $iconClass = 'fa-eye-slash';
            $alertClass = 'alert-warning';
            break;
        case 'partial':
            $iconClass = 'fa-exclamation-triangle';
            $alertClass = 'alert-info';
            break;
    }
}
@endphp

<div class="embargo-download-blocked alert {{ $alertClass }}" role="alert">
    <div class="d-flex align-items-start">
        <div class="embargo-icon me-3">
            <i class="fas {{ $iconClass }} fa-2x"></i>
        </div>
        <div class="embargo-content flex-grow-1">
            <h5 class="alert-heading mb-2">
                <i class="fas fa-shield-alt me-1"></i>
                Download Restricted
            </h5>

            <p class="mb-2">
                {{ $result['reason'] ?? 'This material is currently under embargo' }}
            </p>

            @if(!empty($embargoInfo))
                <div class="embargo-details small">
                    <ul class="list-unstyled mb-2">
                        @if(!empty($embargoInfo['type_label']))
                            <li>
                                <strong>Restriction Type:</strong>
                                {{ $embargoInfo['type_label'] }}
                            </li>
                        @endif

                        @if(!empty($embargoInfo['end_date']) && !$embargoInfo['is_perpetual'])
                            <li>
                                <strong>Available From:</strong>
                                {{ date('j F Y', strtotime($embargoInfo['end_date'])) }}
                            </li>
                        @elseif($embargoInfo['is_perpetual'])
                            <li>
                                <strong>Duration:</strong>
                                <span class="text-muted">Permanent restriction</span>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif

            @if($showRequestLink && $canRequestAccess)
                <div class="embargo-actions mt-3">
                    <a href="{{ url_for(['module' => 'accessRequest', 'action' => 'add']) }}?object_id={{ $objectId }}"
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-envelope me-1"></i>
                        Request Access
                    </a>
                    <span class="text-muted small ms-2">
                        You may request special access to this material
                    </span>
                </div>
            @elseif($showRequestLink)
                <div class="embargo-contact mt-3">
                    <p class="text-muted small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        For access enquiries, please contact the repository.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<style {!! $csp_nonce !!}>
.embargo-download-blocked {
    border-left: 4px solid;
}
.embargo-download-blocked.alert-danger {
    border-left-color: #dc3545;
}
.embargo-download-blocked.alert-warning {
    border-left-color: #ffc107;
}
.embargo-download-blocked.alert-info {
    border-left-color: #0dcaf0;
}
.embargo-download-blocked .embargo-icon {
    opacity: 0.8;
}
.embargo-download-blocked .embargo-details {
    background: rgba(0,0,0,0.05);
    padding: 0.5rem 0.75rem;
    border-radius: 0.25rem;
}
</style>
