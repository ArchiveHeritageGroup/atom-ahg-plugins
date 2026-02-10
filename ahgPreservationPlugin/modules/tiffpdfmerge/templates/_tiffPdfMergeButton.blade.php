@php
$informationObjectId = $informationObjectId ?? null;
$buttonClass = $buttonClass ?? 'btn btn-outline-secondary btn-sm';
$buttonText = $buttonText ?? 'Merge Images to PDF';
@endphp

<button type="button"
        class="{{ $buttonClass }}"
        data-tpm-open
        data-information-object-id="{{ $informationObjectId }}"
        title="Upload multiple TIFF/image files and merge them into a single PDF/A document">
    <i class="fas fa-layer-group me-1"></i>
    {{ $buttonText }}
</button>
