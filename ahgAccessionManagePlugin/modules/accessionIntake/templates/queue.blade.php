@php decorate_with('layout_1col') @endphp

@slot('title')
  <h1><?php echo __('Intake queue'); ?></h1>
@endslot

@slot('before-content')
  {{-- Filter Bar --}}
  <form method="get" action="<?php echo url_for('@accession_intake_queue'); ?>" class="mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label form-label-sm"><?php echo __('Status'); ?></label>
        <select name="status" class="form-select form-select-sm">
          <option value=""><?php echo __('All statuses'); ?></option>
          @foreach ($statuses as $s)
            <option value="{{ $s }}" @if (($filters['status'] ?? '') === $s) selected @endif>
              {{ ucfirst(str_replace('_', ' ', $s)) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label form-label-sm"><?php echo __('Priority'); ?></label>
        <select name="priority" class="form-select form-select-sm">
          <option value=""><?php echo __('All priorities'); ?></option>
          @foreach ($priorities as $p)
            <option value="{{ $p }}" @if (($filters['priority'] ?? '') === $p) selected @endif>
              {{ ucfirst($p) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label form-label-sm"><?php echo __('Assigned to'); ?></label>
        <select name="assigned_to" class="form-select form-select-sm">
          <option value=""><?php echo __('Anyone'); ?></option>
          @foreach ($users as $u)
            <option value="{{ $u->id }}" @if (($filters['assigned_to'] ?? '') == $u->id) selected @endif>
              {{ e($u->name) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label form-label-sm"><?php echo __('Search'); ?></label>
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="<?php echo __('Identifier, title...'); ?>"
               value="{{ e($filters['search'] ?? '') }}">
      </div>

      <div class="col-md-auto">
        <button type="submit" class="btn btn-sm btn-primary">
          <i class="fas fa-filter me-1"></i><?php echo __('Filter'); ?>
        </button>
        <a href="<?php echo url_for('@accession_intake_queue'); ?>" class="btn btn-sm btn-outline-secondary">
          <?php echo __('Reset'); ?>
        </a>
      </div>
    </div>
  </form>

  {{-- Stats Cards --}}
  @php
    $statsArr = is_array($stats) ? $stats : (array) $stats;
    $statusColors = [
        'draft'        => 'secondary',
        'submitted'    => 'primary',
        'under_review' => 'info',
        'accepted'     => 'success',
        'rejected'     => 'danger',
        'returned'     => 'warning',
    ];
  @endphp
  <div class="row g-2 mb-3">
    <div class="col">
      <div class="card bg-dark text-white h-100">
        <div class="card-body py-2 px-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-white-50"><?php echo __('Total'); ?></small>
              <h4 class="mb-0">{{ number_format($statsArr['total'] ?? 0) }}</h4>
            </div>
            <i class="fas fa-inbox fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    @foreach ($statusColors as $statusKey => $color)
      <div class="col">
        <div class="card bg-{{ $color }} text-white h-100">
          <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <small class="text-white-50">{{ ucfirst(str_replace('_', ' ', $statusKey)) }}</small>
                <h4 class="mb-0">{{ number_format($statsArr[$statusKey] ?? 0) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach

    <div class="col">
      <div class="card bg-danger text-white h-100 border-danger">
        <div class="card-body py-2 px-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-white-50"><?php echo __('Overdue'); ?></small>
              <h4 class="mb-0">{{ number_format($statsArr['overdue'] ?? 0) }}</h4>
            </div>
            <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
@endslot

@slot('content')
  @php
    $rows = $queueData->rows ?? [];
    $total = $queueData->total ?? 0;
    $page = (int) ($queueData->page ?? 1);
    $limit = (int) ($queueData->limit ?? 30);
    $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

    $statusBadges = [
        'draft'        => 'secondary',
        'submitted'    => 'primary',
        'under_review' => 'info',
        'accepted'     => 'success',
        'rejected'     => 'danger',
        'returned'     => 'warning',
    ];
    $priorityBadges = [
        'low'    => 'secondary',
        'normal' => 'info',
        'high'   => 'warning',
        'urgent' => 'danger',
    ];
  @endphp

  @if (count($rows) > 0)
    <div class="table-responsive mb-3">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Identifier'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Priority'); ?></th>
            <th><?php echo __('Assigned to'); ?></th>
            <th><?php echo __('Submitted'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rows as $row)
            <tr>
              <td>
                <a href="<?php echo url_for('@accession_intake_detail?id=' . ($row->accession_id ?? $row->id ?? '')); ?>">
                  {{ e($row->identifier ?? '--') }}
                </a>
              </td>
              <td>{{ e($row->title ?? '--') }}</td>
              <td>
                @php $st = $row->status ?? 'draft'; @endphp
                <span class="badge bg-{{ $statusBadges[$st] ?? 'secondary' }}">
                  {{ ucfirst(str_replace('_', ' ', $st)) }}
                </span>
              </td>
              <td>
                @php $pr = $row->priority ?? 'normal'; @endphp
                <span class="badge bg-{{ $priorityBadges[$pr] ?? 'info' }}">
                  {{ ucfirst($pr) }}
                </span>
              </td>
              <td>{{ e($row->assigned_to_name ?? '--') }}</td>
              <td>
                @if (!empty($row->submitted_at))
                  {{ date('d M Y H:i', strtotime($row->submitted_at)) }}
                @else
                  <span class="text-muted">--</span>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo url_for('@accession_intake_detail?id=' . ($row->accession_id ?? $row->id ?? '')); ?>"
                     class="btn btn-outline-primary" title="<?php echo __('View'); ?>">
                    <i class="fas fa-eye"></i>
                  </a>

                  {{-- Assign dropdown --}}
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            title="<?php echo __('Assign'); ?>">
                      <i class="fas fa-user-plus"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      @foreach ($users as $u)
                        <li>
                          <form method="post" action="<?php echo url_for('@accession_intake_assign'); ?>" class="d-inline">
                            <input type="hidden" name="accession_id" value="{{ $row->accession_id ?? $row->id ?? '' }}">
                            <input type="hidden" name="assignee_id" value="{{ $u->id }}">
                            <button type="submit" class="dropdown-item">
                              {{ e($u->name) }}
                            </button>
                          </form>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if ($totalPages > 1)
      <nav aria-label="<?php echo __('Queue pagination'); ?>">
        <ul class="pagination justify-content-center mb-0">
          <li class="page-item @if ($page <= 1) disabled @endif">
            <a class="page-link"
               href="<?php echo url_for('@accession_intake_queue'); ?>?page={{ $page - 1 }}&status={{ e($filters['status'] ?? '') }}&priority={{ e($filters['priority'] ?? '') }}&assigned_to={{ e($filters['assigned_to'] ?? '') }}&search={{ e($filters['search'] ?? '') }}">
              &laquo;
            </a>
          </li>
          @for ($i = 1; $i <= $totalPages; $i++)
            <li class="page-item @if ($i === $page) active @endif">
              <a class="page-link"
                 href="<?php echo url_for('@accession_intake_queue'); ?>?page={{ $i }}&status={{ e($filters['status'] ?? '') }}&priority={{ e($filters['priority'] ?? '') }}&assigned_to={{ e($filters['assigned_to'] ?? '') }}&search={{ e($filters['search'] ?? '') }}">
                {{ $i }}
              </a>
            </li>
          @endfor
          <li class="page-item @if ($page >= $totalPages) disabled @endif">
            <a class="page-link"
               href="<?php echo url_for('@accession_intake_queue'); ?>?page={{ $page + 1 }}&status={{ e($filters['status'] ?? '') }}&priority={{ e($filters['priority'] ?? '') }}&assigned_to={{ e($filters['assigned_to'] ?? '') }}&search={{ e($filters['search'] ?? '') }}">
              &raquo;
            </a>
          </li>
        </ul>
      </nav>
    @endif
  @else
    <div class="text-center py-5 text-muted">
      <i class="fas fa-inbox fa-3x mb-3"></i>
      <p class="mb-0"><?php echo __('No accessions found in the intake queue.'); ?></p>
    </div>
  @endif
@endslot

@slot('after-content')
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_intake_config'); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-cog me-1"></i><?php echo __('Configuration'); ?>
    </a>
    <a href="<?php echo url_for('@accession_intake_numbering'); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-hashtag me-1"></i><?php echo __('Numbering'); ?>
    </a>
  </section>
@endslot
