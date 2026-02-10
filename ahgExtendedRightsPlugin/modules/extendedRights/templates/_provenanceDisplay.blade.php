@php if ((!isset($provenance) || !is_array($provenance)) && (!isset($agreements) || !is_array($agreements))) return; $provenance = $provenance ?? []; $agreements = $agreements ?? []; @endphp
@if (count($provenance) > 0 || count($agreements) > 0)
<section id="provenance-area" class="card mb-3">
  <div class="card-header">
    <h4 class="mb-0"><i class="fas fa-history me-2"></i>{{ __('Provenance') }}</h4>
  </div>
  <div class="card-body">

    @if (count($agreements) > 0)
    <h6 class="text-muted">{{ __('Donor Agreements') }}</h6>
    <ul class="list-unstyled mb-3">
      @foreach ($agreements as $agreement)
        <li class="mb-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <a href="{{ url_for(['module' => 'donorAgreement', 'action' => 'view', 'slug' => $agreement->agreement_slug]) }}">
                {{ $agreement->agreement_title ?? $agreement->agreement_number }}
              </a>
              <span class="badge bg-secondary ms-1">{{ ucfirst(str_replace('_', ' ', $agreement->relationship_type)) }}</span>
              <br>
              <small class="text-muted">
                {{ __('Donor') }}:
                <a href="{{ url_for(['module' => 'donor', 'action' => 'browse', 'slug' => $agreement->donor_slug]) }}">
                  {{ $agreement->donor_name }}
                </a>
              </small>
            </div>
            <small class="text-muted">{{ $agreement->agreement_date }}</small>
          </div>
        </li>
      @endforeach
    </ul>
    @endif

    @if (count($provenance) > 0)
    <h6 class="text-muted">{{ __('Custody History') }}</h6>
    <div class="provenance-timeline">
      @foreach ($provenance as $i => $record)
        <div class="provenance-item d-flex mb-2">
          <div class="provenance-marker me-3">
            <span class="badge rounded-pill bg-{{ $i === 0 ? 'primary' : 'secondary' }}">{{ count($provenance) - $i }}</span>
          </div>
          <div class="provenance-content flex-grow-1">
            <div class="d-flex justify-content-between">
              <div>
                <a href="{{ url_for(['module' => 'donor', 'action' => 'browse', 'slug' => $record->donor_slug]) }}">
                  <strong>{{ $record->donor_name }}</strong>
                </a>
                <span class="badge bg-info ms-1">{{ ucfirst($record->relationship_type) }}</span>
              </div>
              <small class="text-muted">{{ $record->provenance_date }}</small>
            </div>
            @if ($record->notes)
              <p class="small text-muted mb-0 mt-1">{{ $record->notes }}</p>
            @endif
          </div>
        </div>
      @endforeach
    </div>
    @endif

  </div>
</section>
@endif
