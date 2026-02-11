@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    <div class="sidebar-content">
      <div class="card mb-3">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="fas fa-cog me-2"></i>{{ __('Actions') }}</h5>
        </div>
        <div class="card-body">
          <a href="{{ url_for(['module' => 'ahgDropdown', 'action' => 'index']) }}" class="btn btn-outline-secondary w-100 mb-2">
            <i class="fas fa-arrow-left me-2"></i>{{ __('Back to List') }}
          </a>
          <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addTermModal">
            <i class="fas fa-plus me-2"></i>{{ __('Add Term') }}
          </button>
        </div>
      </div>

      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Info') }}</h6>
        </div>
        <div class="card-body small">
          <dl class="mb-0">
            <dt>{{ __('Code') }}</dt>
            <dd><code>{{ e($taxonomy) }}</code></dd>
            <dt>{{ __('Terms') }}</dt>
            <dd>{{ count($terms) }}</dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-9">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'ahgDropdown', 'action' => 'index']) }}">{{ __('Dropdown Manager') }}</a></li>
        <li class="breadcrumb-item active">{{ e($taxonomyLabel) }}</li>
      </ol>
    </nav>
    <h1><i class="fas fa-list me-2"></i>{{ e($taxonomyLabel) }}</h1>

    <div class="taxonomy-editor">
      @if ($terms->isEmpty())
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        {{ __('No terms in this taxonomy.') }}
        <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#addTermModal">
          {{ __('Add your first term') }}
        </button>
      </div>
      @else
      <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <span><i class="fas fa-grip-lines me-2"></i>{{ __('Drag to reorder') }}</span>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="showInactive" onchange="toggleInactive()">
            <label class="form-check-label" for="showInactive">{{ __('Show inactive') }}</label>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th style="width: 40px;"></th>
                <th>{{ __('Label') }}</th>
                <th>{{ __('Code') }}</th>
                <th style="width: 80px;">{{ __('Color') }}</th>
                <th style="width: 80px;">{{ __('Default') }}</th>
                <th style="width: 80px;">{{ __('Active') }}</th>
                <th style="width: 120px;">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody id="termsTable">
              @foreach ($terms as $term)
              <tr data-id="{{ $term->id }}" class="term-row {{ !$term->is_active ? 'table-secondary inactive-row d-none' : '' }}">
                <td class="drag-handle text-center text-muted"><i class="fas fa-grip-vertical"></i></td>
                <td>
                  <input type="text" class="form-control form-control-sm border-0 bg-transparent" value="{{ e($term->label) }}" onchange="updateTerm({{ $term->id }}, 'label', this.value)">
                </td>
                <td><code class="small">{{ e($term->code) }}</code></td>
                <td>
                  <input type="color" class="form-control form-control-color form-control-sm" value="{{ $term->color ?: '#6c757d' }}" onchange="updateTerm({{ $term->id }}, 'color', this.value)" title="{{ __('Choose color') }}">
                </td>
                <td class="text-center">
                  <div class="form-check form-switch d-flex justify-content-center">
                    <input type="radio" name="default_term" class="form-check-input" {{ $term->is_default ? 'checked' : '' }} onchange="setDefault({{ $term->id }})">
                  </div>
                </td>
                <td class="text-center">
                  <div class="form-check form-switch d-flex justify-content-center">
                    <input type="checkbox" class="form-check-input" {{ $term->is_active ? 'checked' : '' }} onchange="updateTerm({{ $term->id }}, 'is_active', this.checked ? 1 : 0)">
                  </div>
                </td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTerm({{ $term->id }})" title="{{ __('Delete') }}">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      @endif
    </div>

    <!-- Add Term Modal -->
    <div class="modal fade" id="addTermModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-plus me-2"></i>{{ __('Add Term') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">{{ __('Label') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="newTermLabel" placeholder="e.g., Approved">
            </div>
            <div class="mb-3">
              <label class="form-label">{{ __('Code') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="newTermCode" placeholder="e.g., approved">
              <div class="form-text">{{ __('Lowercase letters, numbers, and underscores only') }}</div>
            </div>
            <div class="row">
              <div class="col-6">
                <label class="form-label">{{ __('Color') }}</label>
                <input type="color" class="form-control form-control-color w-100" id="newTermColor" value="#6c757d">
              </div>
              <div class="col-6">
                <label class="form-label">{{ __('Icon') }}</label>
                <input type="text" class="form-control" id="newTermIcon" placeholder="fa-check">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-success" onclick="addTerm()">{{ __('Add') }}</button>
          </div>
        </div>
      </div>
    </div>

    <script src="/plugins/ahgCorePlugin/web/js/vendor/sortable.min.js" {!! $csp_nonce !!}></script>
    <script {!! $csp_nonce !!}>
    var taxonomy = '{{ e($taxonomy) }}';
    var taxonomyLabel = '{{ e($taxonomyLabel) }}';

    document.addEventListener('DOMContentLoaded', function() {
        var tbody = document.getElementById('termsTable');
        if (tbody) {
            new Sortable(tbody, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function() {
                    var order = Array.from(tbody.querySelectorAll('tr')).map(r => r.dataset.id);
                    fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'reorder']) }}', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'order[]=' + order.join('&order[]=')
                    });
                }
            });
        }
    });

    function updateTerm(id, field, value) {
        fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'updateTerm']) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&' + field + '=' + encodeURIComponent(value)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert('{{ __('Error updating term') }}');
            }
        });
    }

    function setDefault(id) {
        fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'setDefault']) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&taxonomy=' + encodeURIComponent(taxonomy)
        });
    }

    function deleteTerm(id) {
        if (!confirm('{{ __('Delete this term?') }}')) return;

        fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'deleteTerm']) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&hard_delete=1'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.querySelector('tr[data-id="' + id + '"]').remove();
            } else {
                alert('{{ __('Error deleting term') }}');
            }
        });
    }

    function addTerm() {
        var label = document.getElementById('newTermLabel').value.trim();
        var code = document.getElementById('newTermCode').value.trim();
        var color = document.getElementById('newTermColor').value;
        var icon = document.getElementById('newTermIcon').value.trim();

        if (!label || !code) {
            alert('{{ __('Label and code are required') }}');
            return;
        }

        fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'addTerm']) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'taxonomy=' + encodeURIComponent(taxonomy) +
                  '&taxonomy_label=' + encodeURIComponent(taxonomyLabel) +
                  '&code=' + encodeURIComponent(code) +
                  '&label=' + encodeURIComponent(label) +
                  '&color=' + encodeURIComponent(color) +
                  '&icon=' + encodeURIComponent(icon)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || '{{ __('Error adding term') }}');
            }
        });
    }

    function toggleInactive() {
        var show = document.getElementById('showInactive').checked;
        document.querySelectorAll('.inactive-row').forEach(function(row) {
            row.classList.toggle('d-none', !show);
        });
    }

    // Auto-generate code from label
    document.getElementById('newTermLabel').addEventListener('input', function() {
        var code = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        document.getElementById('newTermCode').value = code;
    });
    </script>

    <style {!! $csp_nonce !!}>
    .drag-handle { cursor: grab; }
    .drag-handle:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; background: #f8f9fa; }
    .form-control-color { height: 31px; padding: 2px; }
    </style>
  </div>
</div>
@endsection
