<h1>Embargo Management</h1>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'extendedRights', 'action' => 'index']) }}">Extended Rights</a></li>
    <li class="breadcrumb-item active">Embargoes</li>
  </ol>
</nav>

@if ($sf_user->hasFlash('notice'))
  <div class="alert alert-success">{!! $sf_user->getFlash('notice') !!}</div>
@endif

@if ($sf_user->hasFlash('error'))
  <div class="alert alert-danger">{!! $sf_user->getFlash('error') !!}</div>
@endif

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Active Embargoes</h5>
  </div>
  <div class="card-body">
    @if (!empty($embargoes) && count($embargoes) > 0)
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($embargoes as $embargo)
            <tr>
              <td>
                @if (!empty($embargo->slug))
                  <a href="{{ url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $embargo->slug]) }}">
                    {{ $embargo->title ?? 'Untitled' }}
                  </a>
                @else
                  {{ $embargo->title ?? 'Untitled' }}
                @endif
              </td>
              <td>
                <span class="badge bg-{{ $embargo->embargo_type === 'full' ? 'danger' : 'warning' }}">
                  {{ ucfirst($embargo->embargo_type ?? 'full') }}
                </span>
              </td>
              <td>{{ $embargo->start_date ?? '-' }}</td>
              <td>
                @if (!empty($embargo->end_date))
                  @php
                  $endDate = new DateTime($embargo->end_date);
                  $now = new DateTime();
                  $isExpiringSoon = $endDate <= $now->modify('+30 days');
                  @endphp
                  <span class="{{ $isExpiringSoon ? 'text-warning fw-bold' : '' }}">
                    {{ $embargo->end_date }}
                  </span>
                @else
                  <span class="text-muted">Indefinite</span>
                @endif
              </td>
              <td>
                <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'liftEmbargo', 'id' => $embargo->id]) }}"
                   class="btn btn-sm btn-success"
                   onclick="return confirm('Are you sure you want to lift this embargo?');">
                  <i class="fas fa-unlock"></i> Lift
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No active embargoes found.
      </div>
    @endif
  </div>
</div>

<div class="mt-3">
  <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'index']) }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back to Extended Rights
  </a>
</div>
