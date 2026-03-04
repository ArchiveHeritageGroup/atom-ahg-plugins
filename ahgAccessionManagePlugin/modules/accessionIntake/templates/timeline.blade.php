@php decorate_with('layout_1col') @endphp

@php
  $accId = $accession->id ?? $accession->accession_id ?? 0;
  $identifier = $accession->identifier ?? '--';

  $eventIcons = [
      'created'        => ['icon' => 'fas fa-plus-circle',     'color' => 'success',   'bg' => 'success'],
      'submitted'      => ['icon' => 'fas fa-paper-plane',     'color' => 'primary',   'bg' => 'primary'],
      'under_review'   => ['icon' => 'fas fa-search',          'color' => 'info',      'bg' => 'info'],
      'accepted'       => ['icon' => 'fas fa-check-circle',    'color' => 'success',   'bg' => 'success'],
      'rejected'       => ['icon' => 'fas fa-times-circle',    'color' => 'danger',    'bg' => 'danger'],
      'returned'       => ['icon' => 'fas fa-undo',            'color' => 'warning',   'bg' => 'warning'],
      'assigned'       => ['icon' => 'fas fa-user-plus',       'color' => 'info',      'bg' => 'info'],
      'commented'      => ['icon' => 'fas fa-comment',         'color' => 'secondary', 'bg' => 'secondary'],
      'checklist'      => ['icon' => 'fas fa-check-square',    'color' => 'success',   'bg' => 'success'],
      'attachment'     => ['icon' => 'fas fa-paperclip',       'color' => 'primary',   'bg' => 'primary'],
      'updated'        => ['icon' => 'fas fa-edit',            'color' => 'secondary', 'bg' => 'secondary'],
      'priority_change'=> ['icon' => 'fas fa-exclamation',     'color' => 'warning',   'bg' => 'warning'],
      'status_change'  => ['icon' => 'fas fa-exchange-alt',    'color' => 'info',      'bg' => 'info'],
  ];
@endphp

@slot('title')
  <h1>
    <i class="fas fa-history me-2"></i><?php echo __('Timeline'); ?>
    <small class="text-muted fs-5">{{ e($identifier) }}</small>
  </h1>
@endslot

@slot('before-content')
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_queue'); ?>"><?php echo __('Intake queue'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_detail?id=' . $accId); ?>">{{ e($identifier) }}</a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Timeline'); ?></li>
    </ol>
  </nav>
@endslot

@slot('content')
  @if (count($timeline ?? []) > 0)
    <style @cspNonce>
      .ahg-timeline {
        position: relative;
        padding-left: 40px;
      }
      .ahg-timeline::before {
        content: '';
        position: absolute;
        left: 19px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
      }
      .ahg-timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
      }
      .ahg-timeline-icon {
        position: absolute;
        left: -40px;
        top: 0;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        z-index: 1;
      }
    </style>

    <div class="ahg-timeline">
      @foreach ($timeline as $event)
        @php
          $evType = $event->event_type ?? 'created';
          $evStyle = $eventIcons[$evType] ?? ['icon' => 'fas fa-circle', 'color' => 'secondary', 'bg' => 'secondary'];
        @endphp
        <div class="ahg-timeline-item">
          <div class="ahg-timeline-icon bg-{{ $evStyle['bg'] }}">
            <i class="{{ $evStyle['icon'] }}"></i>
          </div>
          <div class="card">
            <div class="card-body py-2 px-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <span class="badge bg-{{ $evStyle['bg'] }} me-1">
                    {{ ucfirst(str_replace('_', ' ', $evType)) }}
                  </span>
                  @if (!empty($event->actor_name))
                    <strong>{{ e($event->actor_name) }}</strong>
                  @endif
                </div>
                <small class="text-muted text-nowrap ms-2">
                  @if (!empty($event->created_at))
                    {{ date('d M Y H:i', strtotime($event->created_at)) }}
                  @endif
                </small>
              </div>
              @if (!empty($event->description))
                <p class="mb-0 mt-1">{{ e($event->description) }}</p>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="text-center py-5 text-muted">
      <i class="fas fa-history fa-3x mb-3"></i>
      <p class="mb-0"><?php echo __('No timeline events recorded for this accession.'); ?></p>
    </div>
  @endif
@endslot

@slot('after-content')
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_intake_detail?id=' . $accId); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to intake detail'); ?>
    </a>
  </section>
@endslot
