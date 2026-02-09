@extends('layouts.page')

@section('content')
<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
          <i class="fas fa-layer-group me-2"></i>
          {{ __('Levels of Description') }}
        </h1>
        <a href="{{ url_for(['module' => 'term', 'action' => 'add', 'taxonomy' => url_for(['module' => 'taxonomy', 'slug' => 'levels-of-description'])]) }}"
           class="btn btn-success" target="_blank">
          <i class="fas fa-plus me-1"></i>{{ __('Add new term in Taxonomy') }}
        </a>
      </div>

      <!-- Info Box -->
      <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>{{ __('How it works:') }}</strong>
        {{ __('Select which levels appear in each sector. Only sectors with enabled plugins are shown. Archive levels are always available.') }}
      </div>

      <div class="row">
        <!-- Sector Tabs - Only show available sectors -->
        <div class="col-12 mb-4">
          <ul class="nav nav-tabs">
            @foreach ($availableSectors as $sector)
              @php $count = $sectorCounts[$sector] ?? 0; @endphp
              <li class="nav-item">
                <a class="nav-link {{ $currentSector === $sector ? 'active' : '' }}"
                   href="{{ url_for(['module' => 'ahgSettings', 'action' => 'levels', 'sector' => $sector]) }}">
                  <i class="fas fa-{{ getSectorIcon($sector) }} me-1"></i>
                  {{ ucfirst($sector) }}
                  <span class="badge bg-secondary ms-1">{{ $count }}</span>
                </a>
              </li>
            @endforeach
          </ul>
        </div>

        <!-- Sector Levels Management -->
        <div class="col-lg-8">
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">
                <i class="fas fa-{{ getSectorIcon($currentSector) }} me-2"></i>
                {{ ucfirst($currentSector) }} {{ __('Levels') }}
              </h5>
            </div>
            <div class="card-body">
              <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'levels', 'sector' => $currentSector]) }}">
                <input type="hidden" name="action_type" value="update_sector">
                <input type="hidden" name="sector" value="{{ $currentSector }}">

                <p class="text-muted mb-3">{!! __('Select which levels appear in the %1% sector:', ['%1%' => '<strong>' . ucfirst($currentSector) . '</strong>']) !!}</p>

                @if ($sectorAvailableLevels === null || $sectorAvailableLevels->isEmpty())
                  <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ __('No levels available for this sector. The required terms may not exist in the database. Please add them via the Taxonomy.') }}
                  </div>
                @else
                  <div class="row">
                    @foreach ($sectorAvailableLevels as $level)
                      <div class="col-md-6 col-lg-4 mb-2">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox"
                                 name="levels[]" value="{{ $level->id }}"
                                 id="level_{{ $level->id }}"
                                 {{ in_array($level->id, $sectorLevelIds) ? 'checked' : '' }}>
                          <label class="form-check-label" for="level_{{ $level->id }}">
                            {{ $level->name }}
                            <a href="{{ url_for(['module' => 'term', 'slug' => $level->slug]) }}"
                               class="text-muted ms-1" title="{{ __('Edit in Taxonomy') }}" target="_blank">
                              <i class="fas fa-external-link-alt fa-xs"></i>
                            </a>
                          </label>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @endif

                <hr>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i> {{ __('Save Changes') }}
                </button>
              </form>
            </div>
          </div>

          <!-- Display Order -->
          @if (count($sectorLevels) > 0)
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-sort me-2"></i>{{ __('Display Order') }}</h5>
            </div>
            <div class="card-body">
              <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'levels', 'sector' => $currentSector]) }}">
                <input type="hidden" name="action_type" value="update_order">
                <input type="hidden" name="sector" value="{{ $currentSector }}">

                <table class="table table-sm table-hover">
                  <thead>
                    <tr>
                      <th>{{ __('Level') }}</th>
                      <th style="width: 100px;">{{ __('Order') }}</th>
                      <th style="width: 80px;">{{ __('Actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($sectorLevels as $level)
                      <tr>
                        <td>
                          {{ $level->name }}
                        </td>
                        <td>
                          <input type="number" class="form-control form-control-sm"
                                 name="order[{{ $level->id }}]"
                                 value="{{ $level->display_order }}"
                                 min="0" step="10">
                        </td>
                        <td>
                          <a href="{{ url_for(['module' => 'term', 'slug' => $level->slug]) }}"
                             class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}" target="_blank">
                            <i class="fas fa-edit"></i>
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>

                <button type="submit" class="btn btn-secondary btn-sm">
                  <i class="fas fa-sort me-1"></i> {{ __('Update Order') }}
                </button>
              </form>
            </div>
          </div>
          @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
          <!-- Sector Info -->
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('About Sectors') }}</h5>
            </div>
            <div class="card-body small">
              <dl class="mb-0">
                <dt><i class="fas fa-archive me-1"></i> {{ __('Archive') }}</dt>
                <dd class="text-muted">{{ __('Traditional archival levels (ISAD(G), RAD, DACS)') }}</dd>

                @if (in_array('museum', $availableSectors))
                <dt><i class="fas fa-landmark me-1"></i> {{ __('Museum') }}</dt>
                <dd class="text-muted">{{ __('Object-based descriptions (CCO/CDWA, Spectrum)') }}</dd>
                @endif

                @if (in_array('library', $availableSectors))
                <dt><i class="fas fa-book me-1"></i> {{ __('Library') }}</dt>
                <dd class="text-muted">{{ __('Bibliographic materials (books, journals, articles)') }}</dd>
                @endif

                @if (in_array('gallery', $availableSectors))
                <dt><i class="fas fa-image me-1"></i> {{ __('Gallery') }}</dt>
                <dd class="text-muted">{{ __('Artwork and visual materials') }}</dd>
                @endif

                @if (in_array('dam', $availableSectors))
                <dt><i class="fas fa-photo-video me-1"></i> {{ __('DAM') }}</dt>
                <dd class="text-muted mb-0">{{ __('Digital Asset Management (media files)') }}</dd>
                @endif
              </dl>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-link me-2"></i>{{ __('Quick Links') }}</h5>
            </div>
            <div class="list-group list-group-flush">
              <a href="{{ url_for(['module' => 'taxonomy', 'slug' => 'levels-of-description']) }}"
                 class="list-group-item list-group-item-action" target="_blank">
                <i class="fas fa-list me-2"></i>{{ __('Browse all levels in Taxonomy') }}
                <i class="fas fa-external-link-alt fa-xs float-end mt-1"></i>
              </a>
              <a href="{{ url_for(['module' => 'term', 'action' => 'add', 'taxonomy' => url_for(['module' => 'taxonomy', 'slug' => 'levels-of-description'])]) }}"
                 class="list-group-item list-group-item-action" target="_blank">
                <i class="fas fa-plus me-2"></i>{{ __('Create new level term') }}
                <i class="fas fa-external-link-alt fa-xs float-end mt-1"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<hr>
<div class="d-flex justify-content-start">
    <a href="{{ url_for('admin/ahg-settings') }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Settings') }}
    </a>
</div>

@php
function getSectorIcon($sector) {
    return match($sector) {
        'archive' => 'archive',
        'museum' => 'landmark',
        'library' => 'book',
        'gallery' => 'image',
        'dam' => 'photo-video',
        default => 'folder',
    };
}
@endphp
@endsection
