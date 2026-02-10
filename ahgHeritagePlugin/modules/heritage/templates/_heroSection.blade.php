{{--
  Hero Section partial.

  Full viewport hero with rotating images and search.
--}}

@php
$hasImages = !empty($heroImages);
$firstImage = $hasImages ? $heroImages[0] : null;

// Get overlay settings from first image or use defaults
$overlayType = $firstImage['overlay_type'] ?? 'gradient';
$overlayColor = $firstImage['overlay_color'] ?? '#000000';
$overlayOpacity = $firstImage['overlay_opacity'] ?? 0.5;
@endphp

<section class="heritage-hero position-relative vh-100 d-flex align-items-center justify-content-center overflow-hidden" id="heritage-hero">

    <!-- Background Images -->
    <div class="heritage-hero-backgrounds position-absolute top-0 start-0 w-100 h-100">
        @if ($hasImages)
            @foreach ($heroImages as $index => $image)
            @php
                $effectClass = ($image['ken_burns'] ?? 1) ? 'kenburns' : 'fade';
                $slideDuration = $image['display_duration'] ?? 8;
            @endphp
            <div class="heritage-hero-bg {{ $index === 0 ? 'active' : '' }} {{ $effectClass }}"
                 data-index="{{ $index }}"
                 data-duration="{{ $slideDuration }}"
                 style="background-image: url('{{ $image['image_path'] }}');">
            </div>
            @endforeach
        @else
            <div class="heritage-hero-bg active" style="background: linear-gradient(135deg, var(--heritage-primary) 0%, #1a1a2e 100%);"></div>
        @endif
    </div>

    <!-- Gradient Overlay -->
    @if ($overlayType === 'gradient')
    <div class="heritage-hero-overlay position-absolute top-0 start-0 w-100 h-100"
         style="background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.6) 100%);"></div>
    @elseif ($overlayType === 'solid')
    <div class="heritage-hero-overlay position-absolute top-0 start-0 w-100 h-100"
         style="background-color: {{ $overlayColor }}; opacity: {{ $overlayOpacity }};"></div>
    @endif

    <!-- Content -->
    <div class="heritage-hero-content position-relative text-center text-white px-4" style="z-index: 10;">

        <!-- Tagline -->
        <h1 class="heritage-hero-tagline display-3 fw-bold mb-4">
            {{ $tagline }}
        </h1>

        <!-- Subtext -->
        @if ($subtext)
        <p class="heritage-hero-subtext lead mb-5 mx-auto" style="max-width: 600px;">
            {{ $subtext }}
        </p>
        @endif

        <!-- Search Box -->
        <form action="{{ url_for(['module' => 'heritage', 'action' => 'search']) }}" method="get" class="heritage-search-form mx-auto mb-4" style="max-width: 600px;">
            <div class="input-group input-group-lg shadow-lg">
                <input type="text"
                       name="q"
                       class="form-control border-0 py-3"
                       placeholder="{{ $searchPlaceholder }}"
                       aria-label="Search"
                       autocomplete="off"
                       id="heritage-search-input">
                <button class="btn btn-primary px-4" type="submit" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <!-- Autocomplete dropdown -->
            <div class="heritage-autocomplete position-absolute w-100 bg-white rounded-bottom shadow-lg d-none" id="heritage-autocomplete">
            </div>
        </form>

        <!-- Suggested Searches -->
        @if (!empty($suggestedSearches))
        <div class="heritage-suggested-searches">
            <span class="text-white-50 me-2">Try:</span>
            @foreach ($suggestedSearches as $search)
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'search', 'q' => $search]) }}"
               class="btn btn-outline-light btn-sm rounded-pill me-2 mb-2">
                {{ $search }}
            </a>
            @endforeach
        </div>
        @endif

    </div>

    <!-- Image Caption (bottom) -->
    @if ($firstImage && ($firstImage['source_collection'] || $firstImage['photographer_credit']))
    <div class="heritage-hero-caption position-absolute bottom-0 start-0 w-100 p-4 text-white" style="z-index: 10;" id="heritage-hero-caption">
        <div class="container-xxl">
            @if ($firstImage['source_collection'])
            <small class="d-block opacity-75" id="caption-collection">
                {{ $firstImage['source_collection'] }}
            </small>
            @endif
            @if ($firstImage['photographer_credit'])
            <span id="caption-text">{{ $firstImage['photographer_credit'] }}</span>
            @endif
        </div>
    </div>
    @endif

    <!-- Scroll Indicator -->
    <div class="heritage-scroll-indicator position-absolute bottom-0 start-50 translate-middle-x pb-5" style="z-index: 10;">
        <a href="#heritage-explore" class="text-white text-decoration-none">
            <div class="d-flex flex-column align-items-center">
                <span class="small mb-2">Explore</span>
                <i class="fas fa-chevron-down animate-bounce"></i>
            </div>
        </a>
    </div>

</section>
