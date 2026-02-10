@php
$photoCount = 0;
$primaryPhoto = null;

// Get photo count and primary photo
try {
    $photos = SpectrumConditionPhoto::getByConditionCheck($conditionCheck['id']);
    $photoCount = count($photos);
    $primaryPhoto = SpectrumConditionPhoto::getPrimaryPhoto($conditionCheck['id']);
} catch (Exception $e) {
    // Table might not exist yet
}
@endphp

<div class="condition-photos-section">
    @if($primaryPhoto)
        <!-- Primary Photo Thumbnail -->
        <div class="primary-photo-preview mb-2">
            <a href="{{ url_for(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $resource->slug, 'condition_id' => $conditionCheck['id']]) }}">
                <img src="/uploads/{{ $primaryPhoto['file_path'] }}"
                     alt="{{ $primaryPhoto['caption'] ?? 'Primary photo' }}"
                     class="img-thumbnail"
                     style="max-height: 150px; max-width: 200px;">
            </a>
        </div>
    @endif

    <!-- Manage Photos Button -->
    <a href="{{ url_for(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $resource->slug, 'condition_id' => $conditionCheck['id']]) }}"
       class="btn btn-info btn-sm">
        <i class="fas fa-camera"></i>
        {{ __('Manage Photos') }}
        @if($photoCount > 0)
            <span class="badge badge-light ml-1">{{ $photoCount }}</span>
        @endif
    </a>

    @if($photoCount == 0)
        <small class="text-muted d-block mt-1">
            <i class="fas fa-info-circle"></i> {{ __('No photos uploaded yet') }}
        </small>
    @endif
</div>

<style {!! $csp_nonce !!}>
.condition-photos-section {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.primary-photo-preview img {
    border-radius: 4px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.primary-photo-preview img:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
