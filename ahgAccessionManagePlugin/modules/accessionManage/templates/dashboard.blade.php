@php decorate_with('layout_1col') @endphp

@slot('title')
  <h1><?php echo __('Accession dashboard'); ?></h1>
@endslot

@slot('content')
@php
  $stats = isset($stats) ? $stats : ['total' => 0, 'byStatus' => [], 'recentActivity' => [], 'topAssignees' => []];
  $queueStats = isset($queueStats) ? $queueStats : [];
  $valuationReport = isset($valuationReport) ? $valuationReport : [];

  $totalAccessions = $stats['total'] ?? 0;
  $byStatus = $stats['byStatus'] ?? [];
  $recentActivity = $stats['recentActivity'] ?? [];
  $topAssignees = $stats['topAssignees'] ?? [];
  $totalValue = $valuationReport['total_value'] ?? 0;
  $byCurrency = $valuationReport['by_currency'] ?? [];
  $valuedCount = $valuationReport['accession_count'] ?? 0;

  $queueTotal = $queueStats['total'] ?? 0;
  $queueByStatus = $queueStats['byStatus'] ?? [];
  $queueByPriority = $queueStats['byPriority'] ?? [];
  $avgTime = $queueStats['avgTimeToAcceptHours'] ?? null;
  $overdue = $queueStats['overdue'] ?? 0;
@endphp

<div class="container-fluid px-0">

  {{-- KPI Cards Row --}}
  <div class="row g-3 mb-4">
    {{-- Total accessions --}}
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-primary border-4 h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small text-uppercase"><?php echo __('Total accessions'); ?></div>
              <div class="h2 mb-0">{{ number_format($totalAccessions) }}</div>
            </div>
            <div class="text-primary opacity-50">
              <i class="fas fa-archive fa-2x"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Queue depth --}}
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-warning border-4 h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small text-uppercase"><?php echo __('Queue depth'); ?></div>
              <div class="h2 mb-0">{{ number_format($queueTotal) }}</div>
              @if ($overdue > 0)
                <small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>{{ $overdue }} <?php echo __('overdue'); ?></small>
              @endif
            </div>
            <div class="text-warning opacity-50">
              <i class="fas fa-inbox fa-2x"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Avg processing time --}}
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-info border-4 h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small text-uppercase"><?php echo __('Avg processing time'); ?></div>
              <div class="h2 mb-0">
                @if ($avgTime !== null)
                  {{ number_format($avgTime, 1) }}h
                @else
                  &mdash;
                @endif
              </div>
            </div>
            <div class="text-info opacity-50">
              <i class="fas fa-clock fa-2x"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Portfolio value --}}
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-success border-4 h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small text-uppercase"><?php echo __('Portfolio value'); ?></div>
              <div class="h2 mb-0">
                @if ($totalValue > 0)
                  @php
                    $primaryCurrency = !empty($byCurrency) ? array_key_first($byCurrency) : 'ZAR';
                  @endphp
                  {{ $primaryCurrency }} {{ number_format($totalValue, 0) }}
                @else
                  &mdash;
                @endif
              </div>
              @if ($valuedCount > 0)
                <small class="text-muted">{{ $valuedCount }} <?php echo __('valued accessions'); ?></small>
              @endif
            </div>
            <div class="text-success opacity-50">
              <i class="fas fa-coins fa-2x"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    {{-- Left column --}}
    <div class="col-lg-8">

      {{-- Status breakdown --}}
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-chart-pie me-2"></i><?php echo __('Status breakdown'); ?>
        </div>
        <div class="card-body">
          @if (!empty($byStatus))
            <div class="row">
              <div class="col-md-6">
                <h6 class="text-muted mb-3"><?php echo __('Accession statuses'); ?></h6>
                @php
                  $statusColors = [
                    'draft' => 'secondary',
                    'submitted' => 'info',
                    'under_review' => 'warning',
                    'accepted' => 'success',
                    'rejected' => 'danger',
                    'returned' => 'dark',
                    'processing' => 'primary',
                    'completed' => 'success',
                  ];
                @endphp
                <div class="d-flex flex-wrap gap-2">
                  @foreach ($byStatus as $status => $count)
                    <span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }} fs-6 px-3 py-2">
                      {{ ucfirst(str_replace('_', ' ', $status)) }}: {{ number_format($count) }}
                    </span>
                  @endforeach
                </div>
              </div>
              <div class="col-md-6">
                @if (!empty($queueByStatus))
                  <h6 class="text-muted mb-3"><?php echo __('Queue statuses'); ?></h6>
                  <div class="d-flex flex-wrap gap-2">
                    @foreach ($queueByStatus as $qs => $qc)
                      <span class="badge bg-{{ $statusColors[$qs] ?? 'secondary' }} fs-6 px-3 py-2">
                        {{ ucfirst(str_replace('_', ' ', $qs)) }}: {{ number_format($qc) }}
                      </span>
                    @endforeach
                  </div>
                @endif

                @if (!empty($queueByPriority))
                  <h6 class="text-muted mb-3 mt-3"><?php echo __('Queue priorities'); ?></h6>
                  @php
                    $priorityColors = [
                      'low' => 'secondary',
                      'normal' => 'primary',
                      'high' => 'warning',
                      'urgent' => 'danger',
                    ];
                  @endphp
                  <div class="d-flex flex-wrap gap-2">
                    @foreach ($queueByPriority as $pri => $pc)
                      <span class="badge bg-{{ $priorityColors[$pri] ?? 'secondary' }} fs-6 px-3 py-2">
                        {{ ucfirst($pri) }}: {{ number_format($pc) }}
                      </span>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          @else
            <p class="text-muted mb-0"><?php echo __('No status data available.'); ?></p>
          @endif
        </div>
      </div>

      {{-- Valuation breakdown (by currency) --}}
      @if (!empty($byCurrency) && count($byCurrency) > 1)
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-money-bill-wave me-2"></i><?php echo __('Portfolio value by currency'); ?>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th><?php echo __('Currency'); ?></th>
                  <th class="text-end"><?php echo __('Total value'); ?></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($byCurrency as $cur => $val)
                <tr>
                  <td><strong>{{ $cur }}</strong></td>
                  <td class="text-end">{{ number_format($val, 2) }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      @endif

      {{-- Recent activity --}}
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-history me-2"></i><?php echo __('Recent activity'); ?>
        </div>
        <div class="card-body p-0">
          @if (!empty($recentActivity))
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?php echo __('Event'); ?></th>
                    <th><?php echo __('Accession'); ?></th>
                    <th><?php echo __('Description'); ?></th>
                    <th><?php echo __('User'); ?></th>
                    <th><?php echo __('Time'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($recentActivity as $activity)
                  @php
                    $eventColors = [
                      'created' => 'success',
                      'submitted' => 'info',
                      'accepted' => 'success',
                      'rejected' => 'danger',
                      'returned' => 'warning',
                      'note' => 'secondary',
                      'containerized' => 'primary',
                      'rights_assigned' => 'dark',
                      'appraised' => 'info',
                      'valued' => 'success',
                    ];
                    $evType = $activity->event_type ?? 'note';
                    $evBadge = $eventColors[$evType] ?? 'secondary';
                  @endphp
                  <tr>
                    <td>
                      <span class="badge bg-{{ $evBadge }}">{{ ucfirst(str_replace('_', ' ', $evType)) }}</span>
                    </td>
                    <td>
                      @if (!empty($activity->identifier))
                        {{ htmlspecialchars($activity->identifier) }}
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>{{ htmlspecialchars($activity->description ?? '') }}</td>
                    <td>{{ htmlspecialchars($activity->actor_name ?? '-') }}</td>
                    <td>
                      @if (!empty($activity->created_at))
                        <span title="{{ $activity->created_at }}">
                          {{ date('d M Y H:i', strtotime($activity->created_at)) }}
                        </span>
                      @else
                        -
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-4 text-muted">
              <i class="fas fa-history fa-2x mb-2"></i>
              <p class="mb-0"><?php echo __('No recent activity.'); ?></p>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-4">

      {{-- Top assignees --}}
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-user-tie me-2"></i><?php echo __('Top assignees'); ?>
        </div>
        <div class="card-body p-0">
          @if (!empty($topAssignees))
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?php echo __('Name'); ?></th>
                    <th class="text-end"><?php echo __('Assigned'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($topAssignees as $assignee)
                  <tr>
                    <td>{{ htmlspecialchars($assignee->name ?? '') }}</td>
                    <td class="text-end">
                      <span class="badge bg-primary rounded-pill">{{ $assignee->cnt ?? 0 }}</span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-4 text-muted">
              <i class="fas fa-user-tie fa-2x mb-2"></i>
              <p class="mb-0"><?php echo __('No assignments yet.'); ?></p>
            </div>
          @endif
        </div>
      </div>

      {{-- Quick links --}}
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-link me-2"></i><?php echo __('Quick links'); ?>
        </div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            <a href="<?php echo url_for('@accession_browse_override'); ?>" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="fas fa-list me-3 text-primary"></i>
              <div>
                <strong><?php echo __('Browse accessions'); ?></strong>
                <br><small class="text-muted"><?php echo __('View and search all accessions'); ?></small>
              </div>
            </a>
            <a href="<?php echo url_for('@accession_intake_queue'); ?>" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="fas fa-inbox me-3 text-warning"></i>
              <div>
                <strong><?php echo __('Intake queue'); ?></strong>
                <br><small class="text-muted"><?php echo __('Review and process submissions'); ?></small>
              </div>
            </a>
            <a href="<?php echo url_for('@accession_valuation_report'); ?>" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="fas fa-chart-line me-3 text-success"></i>
              <div>
                <strong><?php echo __('Valuation report'); ?></strong>
                <br><small class="text-muted"><?php echo __('Portfolio value and analysis'); ?></small>
              </div>
            </a>
            <a href="<?php echo url_for('@accession_intake_config'); ?>" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="fas fa-cog me-3 text-secondary"></i>
              <div>
                <strong><?php echo __('Configuration'); ?></strong>
                <br><small class="text-muted"><?php echo __('Intake and numbering settings'); ?></small>
              </div>
            </a>
            <a href="<?php echo url_for('@accession_add_override'); ?>" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="fas fa-plus me-3 text-info"></i>
              <div>
                <strong><?php echo __('New accession'); ?></strong>
                <br><small class="text-muted"><?php echo __('Create a new accession record'); ?></small>
              </div>
            </a>
          </div>
        </div>
      </div>

      {{-- Valuation types breakdown --}}
      @php $byType = $valuationReport['by_type'] ?? []; @endphp
      @if (!empty($byType))
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-tag me-2"></i><?php echo __('Valuations by type'); ?>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th><?php echo __('Type'); ?></th>
                  <th class="text-end"><?php echo __('Count'); ?></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($byType as $vtype => $vcount)
                <tr>
                  <td>{{ ucfirst(str_replace('_', ' ', $vtype)) }}</td>
                  <td class="text-end">{{ number_format($vcount) }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endslot

@slot('after-content')
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_browse_override'); ?>" class="btn atom-btn-outline-light">
      <?php echo __('Browse accessions'); ?>
    </a>
  </section>
@endslot
