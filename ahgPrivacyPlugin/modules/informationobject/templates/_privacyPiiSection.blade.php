{{--
  Privacy & PII Section partial.

  Shows privacy/PII status for authenticated users when ahgPrivacyPlugin is enabled.

  @var QubitInformationObject $resource
  @var sfUser $sf_user
--}}

@php
// Only show for authenticated users
if (!$sf_user->isAuthenticated()) {
    return;
}

// Check if Privacy plugin is enabled
if (!in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins())) {
    return;
}

// Load PII helper
require_once sfConfig::get('sf_plugins_dir').'/ahgPrivacyPlugin/lib/helper/PiiHelper.php';

$resourceId = $resource->id ?? null;
if (!$resourceId) {
    return;
}

// Check if record has PII flags
$hasPii = function_exists('pii_has_detected') ? pii_has_detected($resourceId) : false;
$hasRedacted = function_exists('pii_has_redacted') ? pii_has_redacted($resourceId) : false;

if (!$hasPii && !$hasRedacted) {
    return;
}
@endphp
<section class="card mb-4">
    <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>{{ __('Privacy & PII') }}</h5>
    </div>
    <div class="card-body">
        @if($hasRedacted)
            <div class="alert alert-info mb-2 py-2">
                <i class="fas fa-eye-slash me-2"></i>
                {{ __('This record contains redacted personally identifiable information (PII).') }}
            </div>
        @endif
        @if($hasPii && !$hasRedacted)
            <div class="alert alert-warning mb-2 py-2">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ __('PII detected in this record - review recommended.') }}
            </div>
        @endif
        <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'piiReview', 'slug' => $resource->slug]) }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-search me-1"></i>{{ __('Review PII') }}
        </a>
    </div>
</section>
