@php
$io = $informationObject ?? null;
$slug = $io ? $io->slug : null;
$btnClass = $buttonClass ?? 'btn btn-outline-secondary';
$showText = $showText ?? true;
@endphp

@if($sf_user->hasCredential(['contributor', 'editor', 'administrator'], false))
<a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'index', 'informationObject' => $slug]) }}"
   class="{{ $btnClass }}"
   title="Upload multiple images and merge into a single PDF document">
    <i class="fas fa-layer-group{{ $showText ? ' me-1' : '' }}"></i>
    @if($showText)Merge to PDF@endif
</a>
@endif
