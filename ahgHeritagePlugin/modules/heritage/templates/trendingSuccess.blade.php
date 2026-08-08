{{-- Rules moved out of style attributes: a CSP nonce covers <style>
     elements and never an attribute. --}}
<style @if(!empty($cspNonce)) nonce="{{ $cspNonce }}" @endif>
  .herita-font-size-3rem-5afc { font-size: 3rem; }
  .herita-height-180px-648e { height: 180px; }
  .herita-height-180px-display-none-3271 { height: 180px; display: none; }
  .herita-height-180px-object-fit-cove-b052 { height: 180px; object-fit: cover; }
</style>
@section('title')
  Trending Items
@endsection

<div class="heritage-trending py-4">
  <div class="container">
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-5 fw-bold mb-3">
          <i class="fas fa-chart-line-arrow me-2"></i>
          Trending Now
        </h1>
        <p class="lead text-muted">Popular items being viewed this week</p>
      </div>
    </div>

    @if(!empty($items))
      <div class="row g-3">
        @foreach($items as $index => $item)
          <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ url_for(['module' => 'informationobject', 'slug' => $item['slug']]) }}"
               class="card h-100 text-decoration-none trending-card">
              <span class="position-absolute top-0 start-0 m-2 badge {{ $index < 3 ? 'bg-warning text-dark' : 'bg-secondary' }}">
                #{{ $index + 1 }}
              </span>
              @if(!empty($item['thumbnail']))
                <img src="{{ $item['thumbnail'] }}" class="card-img-top" alt="{{ $item['title'] }}" class="herita-height-180px-object-fit-cove-b052" onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="card-img-top bg-light align-items-center justify-content-center herita-height-180px-display-none-3271" >
                  <i class="fas fa-file-alt text-muted herita-font-size-3rem-5afc" ></i>
                </div>
              @else
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center herita-height-180px-648e" >
                  <i class="fas fa-file-alt text-muted herita-font-size-3rem-5afc" ></i>
                </div>
              @endif
              <div class="card-body">
                <h5 class="card-title h6">{{ substr($item['title'], 0, 60) }}</h5>
                @if(isset($item['view_count']))
                  <small class="text-muted">
                    <i class="fas fa-eye me-1"></i>{{ number_format($item['view_count']) }} views
                  </small>
                @endif
              </div>
            </a>
          </div>
        @endforeach
      </div>

    @else
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No trending data available yet. Browse some items to get started!
      </div>
    @endif
  </div>
</div>

<style @cspNonce>
.trending-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.trending-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
</style>
