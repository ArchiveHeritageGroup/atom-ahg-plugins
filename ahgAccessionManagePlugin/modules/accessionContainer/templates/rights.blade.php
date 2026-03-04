@php decorate_with('layout_1col') @endphp

@slot('title')
  <h1><?php echo __('Rights'); ?> &mdash; <?php echo htmlspecialchars($accession['identifier'] ?? ''); ?></h1>
@endslot

@slot('content')
@php
  $accId = $accession['id'] ?? 0;
  $accIdentifier = htmlspecialchars($accession['identifier'] ?? '', ENT_QUOTES, 'UTF-8');
  $rights = isset($rights) ? $rights : [];
  $rightsBasis = isset($rightsBasis) ? $rightsBasis : [];
  $restrictionTypes = isset($restrictionTypes) ? $restrictionTypes : [];
  $grantActs = isset($grantActs) ? $grantActs : [];
  $grantRestrictions = isset($grantRestrictions) ? $grantRestrictions : [];
@endphp

<div class="container-fluid px-0">

  {{-- Navigation breadcrumb --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('@accession_browse_override'); ?>"><?php echo __('Accessions'); ?></a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for('@accession_view_override?slug=' . ($accession['slug'] ?? '')); ?>"><?php echo $accIdentifier; ?></a></li>
      <li class="breadcrumb-item active"><?php echo __('Rights'); ?></li>
    </ol>
  </nav>

  {{-- Tab navigation for M3 --}}
  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link" href="<?php echo url_for('@accession_containers_view?id=' . $accId); ?>"><?php echo __('Containers'); ?></a>
    </li>
    <li class="nav-item">
      <a class="nav-link active" href="<?php echo url_for('@accession_rights_view?id=' . $accId); ?>"><?php echo __('Rights'); ?></a>
    </li>
  </ul>

  {{-- Add Right form --}}
  <div class="card mb-4">
    <div class="card-header">
      <i class="fas fa-plus-circle me-2"></i><?php echo __('Add right'); ?>
    </div>
    <div class="card-body">
      <form id="rightsForm">
        <input type="hidden" name="accession_id" value="<?php echo $accId; ?>">
        <input type="hidden" name="right_id" value="0">

        <div class="row mb-3">
          <div class="col-md-4">
            <label for="rightsBasis" class="form-label"><?php echo __('Rights basis'); ?> <span class="text-danger">*</span></label>
            <select id="rightsBasis" name="rights_basis" class="form-select" required>
              @foreach ($rightsBasis as $basis)
              <option value="{{ $basis }}">{{ ucfirst($basis) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label for="rightsHolder" class="form-label"><?php echo __('Rights holder'); ?></label>
            <input type="text" id="rightsHolder" name="rights_holder" class="form-control">
          </div>
          <div class="col-md-4">
            <label for="restrictionType" class="form-label"><?php echo __('Restriction type'); ?></label>
            <select id="restrictionType" name="restriction_type" class="form-select">
              @foreach ($restrictionTypes as $rt)
              <option value="{{ $rt }}">{{ ucfirst($rt) }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-3">
            <label for="rightsStartDate" class="form-label"><?php echo __('Start date'); ?></label>
            <input type="date" id="rightsStartDate" name="start_date" class="form-control">
          </div>
          <div class="col-md-3">
            <label for="rightsEndDate" class="form-label"><?php echo __('End date'); ?></label>
            <input type="date" id="rightsEndDate" name="end_date" class="form-control">
          </div>
          <div class="col-md-3">
            <label for="grantAct" class="form-label"><?php echo __('Grant act'); ?></label>
            <select id="grantAct" name="grant_act" class="form-select">
              <option value=""><?php echo __('-- Select --'); ?></option>
              @foreach ($grantActs as $ga)
              <option value="{{ $ga }}">{{ ucfirst($ga) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label for="grantRestriction" class="form-label"><?php echo __('Grant restriction'); ?></label>
            <select id="grantRestriction" name="grant_restriction" class="form-select">
              <option value=""><?php echo __('-- Select --'); ?></option>
              @foreach ($grantRestrictions as $gr)
              <option value="{{ $gr }}">{{ ucfirst($gr) }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label for="rightsConditions" class="form-label"><?php echo __('Conditions'); ?></label>
            <textarea id="rightsConditions" name="conditions" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-md-6">
            <label for="rightsNotes" class="form-label"><?php echo __('Notes'); ?></label>
            <textarea id="rightsNotes" name="notes" class="form-control" rows="3"></textarea>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-12">
            <div class="form-check">
              <input type="checkbox" id="inheritToChildren" name="inherit_to_children" value="1" class="form-check-input" checked>
              <label for="inheritToChildren" class="form-check-label">
                <?php echo __('Inherit rights to linked information objects'); ?>
              </label>
              <div class="form-text"><?php echo __('When enabled, this right can be propagated to all information objects linked through accession containers.'); ?></div>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?php echo __('Save right'); ?>
          </button>
          <button type="reset" class="btn btn-secondary">
            <?php echo __('Clear'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Rights list --}}
  <h5 class="mb-3"><i class="fas fa-balance-scale me-2"></i><?php echo __('Assigned rights'); ?></h5>

  @if (count($rights) === 0)
    <div class="alert alert-info">
      <i class="fas fa-info-circle me-2"></i><?php echo __('No rights have been assigned to this accession yet.'); ?>
    </div>
  @else
    <div id="rightsList">
      @foreach ($rights as $right)
      @php
        $basisColors = [
          'copyright' => 'primary',
          'license' => 'success',
          'statute' => 'danger',
          'policy' => 'warning',
          'donor' => 'info',
          'other' => 'secondary',
        ];
        $basisBadge = $basisColors[$right->rights_basis ?? ''] ?? 'secondary';

        $restrictionColors = [
          'none' => 'success',
          'restricted' => 'danger',
          'conditional' => 'warning',
          'closed' => 'dark',
          'partial' => 'info',
        ];
        $restrictBadge = $restrictionColors[$right->restriction_type ?? ''] ?? 'secondary';
      @endphp

      <div class="card mb-3 right-card" data-right-id="{{ $right->id }}">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <span class="badge bg-{{ $basisBadge }} me-2">{{ ucfirst($right->rights_basis ?? '') }}</span>
            @if (!empty($right->rights_holder))
              <strong>{{ htmlspecialchars($right->rights_holder) }}</strong>
            @endif
            <span class="badge bg-{{ $restrictBadge }} ms-2">{{ ucfirst($right->restriction_type ?? 'none') }}</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            @if ($right->inherit_to_children)
              <button type="button" class="btn btn-sm btn-outline-success inherit-rights-btn" data-right="{{ $right->id }}" title="<?php echo __('Inherit to information objects'); ?>">
                <i class="fas fa-sitemap me-1"></i><?php echo __('Inherit to IOs'); ?>
              </button>
            @endif
            <button type="button" class="btn btn-sm btn-outline-danger delete-right-btn" data-right="{{ $right->id }}" title="<?php echo __('Delete right'); ?>">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <small class="text-muted"><?php echo __('Period'); ?>:</small><br>
              @if (!empty($right->start_date) || !empty($right->end_date))
                {{ $right->start_date ?? '...' }} &mdash; {{ $right->end_date ?? '...' }}
              @else
                <span class="text-muted"><?php echo __('No dates specified'); ?></span>
              @endif
            </div>
            <div class="col-md-3">
              <small class="text-muted"><?php echo __('Grant'); ?>:</small><br>
              @if (!empty($right->grant_act))
                {{ ucfirst($right->grant_act) }}
                @if (!empty($right->grant_restriction))
                  &mdash; {{ ucfirst($right->grant_restriction) }}
                @endif
              @else
                <span class="text-muted">-</span>
              @endif
            </div>
            <div class="col-md-3">
              <small class="text-muted"><?php echo __('Conditions'); ?>:</small><br>
              @if (!empty($right->conditions))
                {{ htmlspecialchars($right->conditions) }}
              @else
                <span class="text-muted">-</span>
              @endif
            </div>
            <div class="col-md-3">
              <small class="text-muted"><?php echo __('Notes'); ?>:</small><br>
              @if (!empty($right->notes))
                {{ htmlspecialchars($right->notes) }}
              @else
                <span class="text-muted">-</span>
              @endif
            </div>
          </div>

          @if ($right->inherit_to_children)
            <div class="mt-2">
              <span class="badge bg-outline-success border border-success text-success">
                <i class="fas fa-check me-1"></i><?php echo __('Inheritable'); ?>
              </span>
            </div>
          @endif
        </div>
        <div class="card-footer text-muted small">
          <?php echo __('Created'); ?>: {{ date('d M Y H:i', strtotime($right->created_at)) }}
          @if (!empty($right->updated_at) && $right->updated_at !== $right->created_at)
            &middot; <?php echo __('Updated'); ?>: {{ date('d M Y H:i', strtotime($right->updated_at)) }}
          @endif
        </div>
      </div>
      @endforeach
    </div>
  @endif
</div>
@endslot

@slot('after-content')
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_view_override?slug=' . ($accession['slug'] ?? '')); ?>" class="btn atom-btn-outline-light">
      <?php echo __('Back to accession'); ?>
    </a>
  </section>
@endslot

<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {

  // Save right (AJAX)
  var rightsForm = document.getElementById('rightsForm');
  if (rightsForm) {
    rightsForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(rightsForm);

      // Checkbox handling: if unchecked, FormData won't include it
      if (!formData.has('inherit_to_children')) {
        formData.append('inherit_to_children', '0');
      }

      fetch('<?php echo url_for("@accession_api_rights_save"); ?>', {
        method: 'POST',
        body: formData
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          window.location.reload();
        } else {
          alert(<?php echo json_encode(__('Error saving right.')); ?>);
        }
      })
      .catch(function() {
        alert(<?php echo json_encode(__('Error saving right.')); ?>);
      });
    });
  }

  // Delete right
  document.querySelectorAll('.delete-right-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm(<?php echo json_encode(__('Are you sure you want to delete this right? Inherited rights on information objects will also be removed.')); ?>)) return;
      var rightId = btn.getAttribute('data-right');
      fetch('/api/accession/rights/' + rightId + '/delete', {
        method: 'POST'
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          var card = btn.closest('.right-card');
          if (card) card.remove();
        } else {
          alert(<?php echo json_encode(__('Error deleting right.')); ?>);
        }
      })
      .catch(function() {
        alert(<?php echo json_encode(__('Error deleting right.')); ?>);
      });
    });
  });

  // Inherit rights to IOs
  document.querySelectorAll('.inherit-rights-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var rightId = btn.getAttribute('data-right');
      var originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>' + <?php echo json_encode(__('Processing...')); ?>;

      fetch('/api/accession/rights/' + rightId + '/inherit', {
        method: 'POST'
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        btn.disabled = false;
        if (data.success) {
          var count = data.count || 0;
          if (count > 0) {
            btn.innerHTML = '<i class="fas fa-check me-1"></i>' + count + ' ' + <?php echo json_encode(__('IOs updated')); ?>;
            btn.className = 'btn btn-sm btn-success';
            setTimeout(function() {
              btn.innerHTML = originalText;
              btn.className = 'btn btn-sm btn-outline-success';
            }, 3000);
          } else {
            btn.innerHTML = originalText;
            alert(<?php echo json_encode(__('No linked information objects found to inherit rights to. Ensure containers have items linked to information objects.')); ?>);
          }
        } else {
          btn.innerHTML = originalText;
          alert(<?php echo json_encode(__('Error inheriting rights.')); ?>);
        }
      })
      .catch(function() {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert(<?php echo json_encode(__('Error inheriting rights.')); ?>);
      });
    });
  });
});
</script>
