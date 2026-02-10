<section class="heritage-explore py-5" id="heritage-explore">
    <div class="container-xxl">

        <!-- Section Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Explore By</h2>
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'search']) }}" class="btn btn-link text-decoration-none">
                Browse all <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Filter Cards Grid -->
        <div class="row g-4">
            @foreach($filters as $filter)
            @if(!empty($filter['values']))
            <div class="col-md-6 col-lg-4">
                <div class="card heritage-filter-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <!-- Filter Header -->
                        <div class="d-flex align-items-center mb-3">
                            @if(!empty($filter['icon']))
                            <i class="{{ $filter['icon'] }} fs-4 text-primary me-2"></i>
                            @endif
                            <h3 class="h5 mb-0">{{ $filter['label'] }}</h3>
                        </div>

                        <!-- Filter Values -->
                        <ul class="list-unstyled mb-0">
                            @foreach($filter['values'] as $value)
                            <li class="mb-2">
                                <a href="{{ url_for(['module' => 'heritage', 'action' => 'search', $filter['code'] . '[]' => $value['value']]) }}"
                                   class="text-decoration-none d-flex justify-content-between align-items-center py-1 heritage-filter-link">
                                    <span class="text-truncate">{{ $value['label'] }}</span>
                                    <span class="badge bg-light text-muted ms-2">{{ number_format($value['count']) }}</span>
                                </a>
                            </li>
                            @endforeach
                        </ul>

                        <!-- More Link -->
                        <div class="mt-3 pt-2 border-top">
                            <a href="{{ url_for(['module' => 'heritage', 'action' => 'search', 'expand' => $filter['code']]) }}"
                               class="small text-muted text-decoration-none">
                                more <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>

    </div>
</section>
