@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    <div class="sidebar-content">
      <div class="card mb-3">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>{{ __('Dropdown Manager') }}</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small">{{ __('Manage controlled vocabularies (dropdowns) used throughout the system.') }}</p>
          <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#createTaxonomyModal">
            <i class="fas fa-plus me-2"></i>{{ __('Create Taxonomy') }}
          </button>
          <hr>
          <div class="mb-2">
            <input type="text" class="form-control form-control-sm" id="sectionSearch" placeholder="{{ __('Filter sections...') }}">
          </div>
          <div class="list-group list-group-flush" id="sectionNav">
            @foreach ($sectionMeta as $code => $meta)
            <a href="#section-{{ $code }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-2 section-nav-link" data-section="{{ $code }}">
              <span><i class="fas {{ $meta['icon'] }} fa-fw me-1"></i>{{ $meta['label'] }}</span>
              <span class="badge bg-secondary rounded-pill">{{ $meta['count'] }}</span>
            </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-9">
    <h1 class="h3 mb-0"><i class="fas fa-list-alt me-2"></i>{{ __('Dropdown Manager') }}</h1>
    <p class="text-muted small mb-3">{{ __('Manage controlled vocabularies for form dropdowns') }}</p>

    <div class="mb-3 d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" id="expandAll"><i class="fas fa-expand-arrows-alt me-1"></i>{{ __('Expand All') }}</button>
      <button class="btn btn-sm btn-outline-secondary" id="collapseAll"><i class="fas fa-compress-arrows-alt me-1"></i>{{ __('Collapse All') }}</button>
      <input type="text" class="form-control form-control-sm w-auto" id="taxonomySearch" placeholder="{{ __('Search taxonomies...') }}">
    </div>

    @if (empty($sections))
    <div class="alert alert-info">
      <i class="fas fa-info-circle me-2"></i>
      {{ __('No taxonomies found.') }}
      <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#createTaxonomyModal">
        {{ __('Create your first taxonomy') }}
      </button>
    </div>
    @else
    <div class="accordion" id="sectionAccordion">
      @foreach ($sections as $sectionCode => $sectionTaxonomies)
      @php $meta = $sectionMeta[$sectionCode]; @endphp
      <div class="accordion-item section-block" data-section="{{ $sectionCode }}" id="section-{{ $sectionCode }}">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $sectionCode }}" style="font-size:0.9rem">
            <i class="fas {{ $meta['icon'] }} fa-fw me-2 text-muted"></i>
            {{ $meta['label'] }}
            <span class="badge bg-secondary ms-2">{{ $meta['count'] }}</span>
          </button>
        </h2>
        <div id="collapse-{{ $sectionCode }}" class="accordion-collapse collapse" data-bs-parent="#sectionAccordion">
          <div class="accordion-body p-0">
            <table class="table table-hover table-sm align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Taxonomy') }}</th>
                  <th>{{ __('Code') }}</th>
                  <th class="text-center">{{ __('Terms') }}</th>
                  <th class="text-end">{{ __('Actions') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($sectionTaxonomies as $tax)
                <tr class="taxonomy-row" data-label="{{ strtolower($tax->taxonomy_label) }}" data-code="{{ $tax->taxonomy }}">
                  <td>
                    <a href="{{ url_for(['module' => 'ahgDropdown', 'action' => 'edit', 'taxonomy' => $tax->taxonomy]) }}" class="fw-bold text-decoration-none">
                      {{ e($tax->taxonomy_label) }}
                    </a>
                  </td>
                  <td><code>{{ e($tax->taxonomy) }}</code></td>
                  <td class="text-center">
                    <span class="badge bg-secondary">{{ $termCounts[$tax->taxonomy] ?? 0 }}</span>
                  </td>
                  <td class="text-end">
                    <a href="{{ url_for(['module' => 'ahgDropdown', 'action' => 'edit', 'taxonomy' => $tax->taxonomy]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit Terms') }}">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="renameTaxonomy('{{ e($tax->taxonomy) }}', '{{ e($tax->taxonomy_label) }}')" title="{{ __('Rename') }}">
                      <i class="fas fa-pen"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="moveTaxonomy('{{ e($tax->taxonomy) }}', '{{ $sectionCode }}')" title="{{ __('Move to Section') }}">
                      <i class="fas fa-arrows-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTaxonomy('{{ e($tax->taxonomy) }}')" title="{{ __('Delete') }}">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    @endif

    <!-- Create Taxonomy Modal -->
    <div class="modal fade" id="createTaxonomyModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-plus me-2"></i>{{ __('Create Taxonomy') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">{{ __('Section') }} <span class="text-danger">*</span></label>
              <select class="form-select" id="newTaxonomySection">
                @foreach ($availableSections as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ __('Display Name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="newTaxonomyLabel" placeholder="e.g., Condition Status">
            </div>
            <div class="mb-3">
              <label class="form-label">{{ __('Code') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="newTaxonomyCode" placeholder="e.g., condition_status">
              <div class="form-text">{{ __('Lowercase letters, numbers, and underscores only') }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-success" onclick="createTaxonomy()">{{ __('Create') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Rename Taxonomy Modal -->
    <div class="modal fade" id="renameTaxonomyModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-pen me-2"></i>{{ __('Rename Taxonomy') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="renameTaxonomyCode">
            <div class="mb-3">
              <label class="form-label">{{ __('New Display Name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="renameTaxonomyLabel">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-warning" onclick="doRenameTaxonomy()">{{ __('Rename') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Move Section Modal -->
    <div class="modal fade" id="moveSectionModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-arrows-alt me-2"></i>{{ __('Move to Section') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="moveTaxonomyCode">
            <div class="mb-3">
              <label class="form-label">{{ __('Target Section') }}</label>
              <select class="form-select" id="moveTargetSection">
                @foreach ($availableSections as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" onclick="doMoveSection()">{{ __('Move') }}</button>
          </div>
        </div>
      </div>
    </div>

    <script {!! $csp_nonce !!}>
    function createTaxonomy() {
        var label = document.getElementById('newTaxonomyLabel').value.trim();
        var code = document.getElementById('newTaxonomyCode').value.trim();
        var section = document.getElementById('newTaxonomySection').value;

        if (!label || !code) {
            alert('{{ __('Please fill in all required fields') }}');
            return;
        }

        fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'create']) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'code=' + encodeURIComponent(code) + '&label=' + encodeURIComponent(label) + '&section=' + encodeURIComponent(section)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || '{{ __('Error creating taxonomy') }}');
            }
        });
    }

    function renameTaxonomy(code, label) {
        document.getElementById('renameTaxonomyCode').value = code;
        document.getElementById('renameTaxonomyLabel').value = label;
        new bootstrap.Modal(document.getElementById('renameTaxonomyModal')).show();
    }

    function doRenameTaxonomy() {
        var code = document.getElementById('renameTaxonomyCode').value;
        var label = document.getElementById('renameTaxonomyLabel').value.trim();

        if (!label) {
            alert('{{ __('Please enter a name') }}');
            return;
        }

        fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'rename']) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'taxonomy=' + encodeURIComponent(code) + '&label=' + encodeURIComponent(label)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || '{{ __('Error renaming taxonomy') }}');
            }
        });
    }

    function moveTaxonomy(code, currentSection) {
        document.getElementById('moveTaxonomyCode').value = code;
        document.getElementById('moveTargetSection').value = currentSection;
        new bootstrap.Modal(document.getElementById('moveSectionModal')).show();
    }

    function doMoveSection() {
        var taxonomy = document.getElementById('moveTaxonomyCode').value;
        var section = document.getElementById('moveTargetSection').value;

        fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'moveSection']) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'taxonomy=' + encodeURIComponent(taxonomy) + '&section=' + encodeURIComponent(section)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || '{{ __('Error moving taxonomy') }}');
            }
        });
    }

    function deleteTaxonomy(code) {
        if (!confirm('{{ __('Are you sure you want to delete this taxonomy and all its terms?') }}')) {
            return;
        }

        fetch('{{ url_for(['module' => 'ahgDropdown', 'action' => 'deleteTaxonomy']) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'taxonomy=' + encodeURIComponent(code) + '&hard_delete=1'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || '{{ __('Error deleting taxonomy') }}');
            }
        });
    }

    // Auto-generate code from label
    document.getElementById('newTaxonomyLabel').addEventListener('input', function() {
        var code = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        document.getElementById('newTaxonomyCode').value = code;
    });

    // Expand/Collapse all
    document.getElementById('expandAll').addEventListener('click', function() {
        document.querySelectorAll('.accordion-collapse').forEach(function(el) {
            new bootstrap.Collapse(el, {toggle: false}).show();
        });
    });
    document.getElementById('collapseAll').addEventListener('click', function() {
        document.querySelectorAll('.accordion-collapse.show').forEach(function(el) {
            new bootstrap.Collapse(el, {toggle: false}).hide();
        });
    });

    // Search/filter taxonomies
    document.getElementById('taxonomySearch').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.taxonomy-row').forEach(function(row) {
            var match = row.dataset.label.indexOf(q) !== -1 || row.dataset.code.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
        });
        // Auto-expand sections with matches
        if (q.length > 1) {
            document.querySelectorAll('.section-block').forEach(function(block) {
                var visible = block.querySelectorAll('.taxonomy-row:not([style*="display: none"])').length;
                var collapse = block.querySelector('.accordion-collapse');
                if (visible > 0) {
                    new bootstrap.Collapse(collapse, {toggle: false}).show();
                }
            });
        }
    });

    // Sidebar section filter
    document.getElementById('sectionSearch').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.section-nav-link').forEach(function(link) {
            link.style.display = link.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
    });

    // Sidebar section click - scroll and expand
    document.querySelectorAll('.section-nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var section = this.dataset.section;
            var target = document.getElementById('section-' + section);
            var collapse = target.querySelector('.accordion-collapse');
            new bootstrap.Collapse(collapse, {toggle: false}).show();
            target.scrollIntoView({behavior: 'smooth', block: 'start'});
        });
    });
    </script>
  </div>
</div>
@endsection
