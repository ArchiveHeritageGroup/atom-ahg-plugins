<section class="heritage-stories py-5 bg-light">
    <div class="container-xxl">

        <!-- Section Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Featured</h2>
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'search', 'featured' => 1]) }}" class="btn btn-link text-decoration-none">
                View all stories <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Stories Grid -->
        <div class="row g-4">
            @foreach($stories as $story)
            <div class="col-md-6 col-lg-4">
                <article class="card heritage-story-card h-100 border-0 shadow-sm overflow-hidden">
                    <!-- Cover Image -->
                    <div class="heritage-story-image position-relative" style="height: 200px;">
                        @if(!empty($story['cover_image']))
                        <img src="{{ $story['cover_image'] }}"
                             alt="{{ $story['title'] }}"
                             class="w-100 h-100 object-fit-cover">
                        @else
                        <?php
                        $colors = ['primary', 'success', 'info', 'warning', 'danger'];
                        $colorIndex = crc32($story['title'] ?? 'story') % count($colors);
                        $bgColor = $colors[$colorIndex];
                        $icons = ['bi-book', 'bi-collection', 'bi-archive', 'bi-folder2-open', 'bi-journals'];
                        $icon = $icons[$colorIndex];
                        ?>
                        <div class="w-100 h-100 bg-{{ $bgColor }} bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="fas {{ $icon }} display-4 text-{{ $bgColor }}"></i>
                        </div>
                        @endif
                        <!-- Story Type Badge -->
                        @if(!empty($story['story_type']))
                        <span class="badge bg-primary position-absolute top-0 end-0 m-3">
                            {{ ucfirst($story['story_type']) }}
                        </span>
                        @endif
                    </div>

                    <div class="card-body">
                        <!-- Title -->
                        <h3 class="h5 card-title">
                            <?php
                            $linkUrl = '#';
                            if ($story['link_type'] === 'search') {
                                $linkUrl = url_for(['module' => 'heritage', 'action' => 'search', 'q' => $story['link_reference']]);
                            } elseif ($story['link_type'] === 'collection') {
                                $linkUrl = '/' . $story['link_reference'];
                            } elseif ($story['link_type'] === 'external') {
                                $linkUrl = $story['link_reference'];
                            } elseif ($story['link_type'] === 'page') {
                                $linkUrl = '/' . $story['link_reference'];
                            }
                            ?>
                            <a href="{{ $linkUrl }}" class="text-decoration-none text-dark stretched-link">
                                {{ $story['title'] }}
                            </a>
                        </h3>

                        <!-- Subtitle -->
                        @if(!empty($story['subtitle']))
                        <p class="card-text text-muted">
                            {{ $story['subtitle'] }}
                        </p>
                        @endif
                    </div>

                    <!-- Footer with item count -->
                    @if(!empty($story['item_count']))
                    <div class="card-footer bg-transparent border-top-0 pt-0">
                        <small class="text-muted">
                            {{ number_format($story['item_count']) }} items
                            <i class="fas fa-arrow-right ms-1"></i>
                        </small>
                    </div>
                    @endif

                </article>
            </div>
            @endforeach
        </div>

    </div>
</section>
