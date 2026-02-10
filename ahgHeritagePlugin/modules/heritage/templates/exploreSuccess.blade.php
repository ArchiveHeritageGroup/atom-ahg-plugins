@section('title')
  {{ isset($currentCategory) ? $currentCategory['name'] . ' - Explore' : 'Explore Our Collections' }}
@endsection

<div class="heritage-explore py-4">
  <div class="container">

    @if(!isset($currentCategory))
      <!-- Explore Categories Grid -->
      <div class="row mb-4">
        <div class="col-12">
          <h1 class="display-5 fw-bold mb-3">Explore Our Collections</h1>
          <p class="lead text-muted">Discover archives through different perspectives</p>
        </div>
      </div>

      <div class="row g-4">
        @foreach($categories as $cat)
          <div class="col-md-6 col-lg-4">
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'explore', 'category' => $cat['code']]) }}"
               class="card h-100 text-decoration-none explore-card"
               style="background-color: {{ $cat['background_color'] }}; color: {{ $cat['text_color'] }};">
              @if($cat['cover_image'])
                <div class="card-img-top" style="height: 150px; background: url('{{ $cat['cover_image'] }}') center/cover;"></div>
              @endif
              <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                  <i class="{{ $cat['icon'] }} fs-3 me-2"></i>
                  <h3 class="card-title h4 mb-0">{{ $cat['name'] }}</h3>
                </div>
                @if($cat['tagline'])
                  <p class="card-text opacity-75">{{ $cat['tagline'] }}</p>
                @endif
              </div>
            </a>
          </div>
        @endforeach
      </div>

    @else
      <!-- Category Items View -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'landing']) }}">Heritage</a>
          </li>
          <li class="breadcrumb-item">
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'explore']) }}">Explore</a>
          </li>
          <li class="breadcrumb-item active">{{ $currentCategory['name'] }}</li>
        </ol>
      </nav>

      <div class="row mb-4">
        <div class="col-12">
          <h1 class="display-5 fw-bold mb-2">
            <i class="{{ $currentCategory['icon'] }} me-2"></i>
            {{ $currentCategory['name'] }}
          </h1>
          @if($currentCategory['description'])
            <p class="lead text-muted">{{ $currentCategory['description'] }}</p>
          @endif
          @if(isset($totalItems))
            <p class="text-muted">{{ number_format($totalItems) }} items found</p>
          @endif
        </div>
      </div>

      @if(!empty($items))
        @if($currentCategory['display_style'] === 'grid' || $currentCategory['display_style'] === 'map')
          <!-- Grid display -->
          <div class="row g-3">
            @foreach($items as $item)
              <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ url_for(['module' => 'heritage', 'action' => 'search', 'q' => '', $currentCategory['source_reference'] => [$item['name']]]) }}"
                   class="card h-100 text-decoration-none explore-item-card">
                  <div class="card-body text-center">
                    <h5 class="card-title">{{ $item['name'] }}</h5>
                    @if(isset($item['count']))
                      <span class="badge bg-secondary">{{ number_format($item['count']) }} items</span>
                    @endif
                  </div>
                </a>
              </div>
            @endforeach
          </div>

        @elseif($currentCategory['display_style'] === 'list')
          <!-- List display -->
          <div class="list-group">
            @foreach($items as $item)
              <a href="{{ url_for(['module' => 'heritage', 'action' => 'search', 'q' => '', $currentCategory['source_reference'] => [$item['name']]]) }}"
                 class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                {{ $item['name'] }}
                @if(isset($item['count']))
                  <span class="badge bg-primary rounded-pill">{{ number_format($item['count']) }}</span>
                @endif
              </a>
            @endforeach
          </div>
        @endif

        <!-- Pagination -->
        @if(isset($totalPages) && $totalPages > 1)
          <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
              @if($page > 1)
                <li class="page-item">
                  <a class="page-link" href="{{ url_for(['module' => 'heritage', 'action' => 'explore', 'category' => $currentCategory['code'], 'page' => $page - 1]) }}">Previous</a>
                </li>
              @endif

              @for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++)
                <li class="page-item {{ $i === $page ? 'active' : '' }}">
                  <a class="page-link" href="{{ url_for(['module' => 'heritage', 'action' => 'explore', 'category' => $currentCategory['code'], 'page' => $i]) }}">{{ $i }}</a>
                </li>
              @endfor

              @if($page < $totalPages)
                <li class="page-item">
                  <a class="page-link" href="{{ url_for(['module' => 'heritage', 'action' => 'explore', 'category' => $currentCategory['code'], 'page' => $page + 1]) }}">Next</a>
                </li>
              @endif
            </ul>
          </nav>
        @endif

      @else
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          No items found in this category.
        </div>
      @endif

    @endif

  </div>
</div>

<style {!! $csp_nonce !!}>
.explore-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.explore-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
.explore-item-card {
  transition: transform 0.2s ease;
}
.explore-item-card:hover {
  transform: translateY(-3px);
}
</style>
