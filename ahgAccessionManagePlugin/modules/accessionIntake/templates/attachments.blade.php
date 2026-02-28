@php decorate_with('layout_1col') @endphp

@php
  $accId = $accession->id ?? $accession->accession_id ?? 0;
  $identifier = $accession->identifier ?? '--';

  $catBadges = [
      'general'        => 'secondary',
      'deed_of_gift'   => 'success',
      'photo'          => 'info',
      'correspondence' => 'primary',
      'inventory'      => 'warning',
      'other'          => 'dark',
  ];
@endphp

@slot('title')
  <h1>
    <i class="fas fa-paperclip me-2"></i><?php echo __('Attachments'); ?>
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
      <li class="breadcrumb-item active"><?php echo __('Attachments'); ?></li>
    </ol>
  </nav>
@endslot

@slot('content')
  {{-- Upload form --}}
  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-upload me-1"></i><?php echo __('Upload attachment'); ?>
    </div>
    <div class="card-body">
      <form id="attachment-upload-form" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label"><?php echo __('File'); ?> <span class="text-danger">*</span></label>
            <input type="file" name="file" id="attachment-file" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Category'); ?></label>
            <select name="category" id="attachment-category" class="form-select">
              <option value="general"><?php echo __('General'); ?></option>
              <option value="deed_of_gift"><?php echo __('Deed of gift'); ?></option>
              <option value="photo"><?php echo __('Photo'); ?></option>
              <option value="correspondence"><?php echo __('Correspondence'); ?></option>
              <option value="inventory"><?php echo __('Inventory'); ?></option>
              <option value="other"><?php echo __('Other'); ?></option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Description'); ?></label>
            <input type="text" name="description" id="attachment-description" class="form-control"
                   placeholder="<?php echo __('Optional description'); ?>">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100" id="upload-btn">
              <i class="fas fa-upload me-1"></i><?php echo __('Upload'); ?>
            </button>
          </div>
        </div>
      </form>
      <div id="upload-progress" class="mt-2 d-none">
        <div class="progress" style="height: 6px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>
        <small class="text-muted"><?php echo __('Uploading...'); ?></small>
      </div>
    </div>
  </div>

  {{-- Attachments table --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-list me-1"></i><?php echo __('Uploaded attachments'); ?></span>
      <span class="badge bg-secondary">{{ count($attachments ?? []) }} <?php echo __('file(s)'); ?></span>
    </div>
    <div class="card-body p-0">
      @if (count($attachments ?? []) > 0)
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Filename'); ?></th>
                <th><?php echo __('Category'); ?></th>
                <th><?php echo __('Size'); ?></th>
                <th><?php echo __('Uploaded by'); ?></th>
                <th><?php echo __('Date'); ?></th>
                <th class="text-end"><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody id="attachments-table-body">
              @foreach ($attachments as $att)
                <tr id="attachment-row-{{ $att->id }}">
                  <td>
                    @if (!empty($att->file_path))
                      <a href="{{ e($att->file_path) }}" target="_blank" title="<?php echo __('Download'); ?>">
                        <i class="fas fa-file me-1"></i>{{ e($att->filename ?? $att->original_filename ?? 'Download') }}
                      </a>
                    @else
                      <i class="fas fa-file me-1"></i>{{ e($att->filename ?? $att->original_filename ?? '--') }}
                    @endif
                    @if (!empty($att->description))
                      <br><small class="text-muted">{{ e($att->description) }}</small>
                    @endif
                  </td>
                  <td>
                    @php $cat = $att->category ?? 'general'; @endphp
                    <span class="badge bg-{{ $catBadges[$cat] ?? 'secondary' }}">
                      {{ ucfirst(str_replace('_', ' ', $cat)) }}
                    </span>
                  </td>
                  <td>
                    @if (!empty($att->file_size))
                      @php
                        $bytes = (int) $att->file_size;
                        if ($bytes >= 1048576) { $sizeStr = round($bytes / 1048576, 1) . ' MB'; }
                        elseif ($bytes >= 1024) { $sizeStr = round($bytes / 1024, 1) . ' KB'; }
                        else { $sizeStr = $bytes . ' B'; }
                      @endphp
                      {{ $sizeStr }}
                    @else
                      --
                    @endif
                  </td>
                  <td>{{ e($att->uploaded_by_name ?? '--') }}</td>
                  <td>
                    @if (!empty($att->created_at))
                      {{ date('d M Y H:i', strtotime($att->created_at)) }}
                    @else
                      --
                    @endif
                  </td>
                  <td class="text-end">
                    @if (!empty($att->file_path))
                      <a href="{{ e($att->file_path) }}" target="_blank"
                         class="btn btn-sm btn-outline-primary me-1" title="<?php echo __('Download'); ?>">
                        <i class="fas fa-download"></i>
                      </a>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-danger attachment-delete-btn"
                            data-attachment-id="{{ $att->id }}"
                            title="<?php echo __('Delete'); ?>">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-center py-5 text-muted">
          <i class="fas fa-paperclip fa-3x mb-3"></i>
          <p class="mb-0"><?php echo __('No attachments uploaded yet.'); ?></p>
          <p class="small"><?php echo __('Use the form above to upload deed of gift, photos, correspondence, or other documents.'); ?></p>
        </div>
      @endif
    </div>
  </div>
@endslot

@slot('after-content')
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_intake_detail?id=' . $accId); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to intake detail'); ?>
    </a>
  </section>
@endslot

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Attachment upload
  var uploadForm = document.getElementById('attachment-upload-form');
  var uploadProgress = document.getElementById('upload-progress');
  var uploadBtn = document.getElementById('upload-btn');

  if (uploadForm) {
    uploadForm.addEventListener('submit', function(e) {
      e.preventDefault();

      var fileInput = document.getElementById('attachment-file');
      if (!fileInput.files.length) {
        alert('<?php echo __("Please select a file."); ?>');
        return;
      }

      var formData = new FormData();
      formData.append('file', fileInput.files[0]);
      formData.append('accession_id', '<?php echo $accId; ?>');
      formData.append('category', document.getElementById('attachment-category').value);

      var descInput = document.getElementById('attachment-description');
      if (descInput && descInput.value) {
        formData.append('description', descInput.value);
      }

      // Show progress
      uploadProgress.classList.remove('d-none');
      uploadBtn.disabled = true;

      fetch('<?php echo url_for("@accession_api_attachment_upload"); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        uploadProgress.classList.add('d-none');
        uploadBtn.disabled = false;

        if (data.success) {
          location.reload();
        } else {
          alert(data.error || '<?php echo __("Upload failed."); ?>');
        }
      })
      .catch(function() {
        uploadProgress.classList.add('d-none');
        uploadBtn.disabled = false;
        alert('<?php echo __("An error occurred during upload."); ?>');
      });
    });
  }

  // Attachment delete
  document.querySelectorAll('.attachment-delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('<?php echo __("Delete this attachment? This cannot be undone."); ?>')) {
        return;
      }

      var attId = this.dataset.attachmentId;
      var row = document.getElementById('attachment-row-' + attId);

      fetch('<?php echo url_for("@accession_api_attachment_delete?id="); ?>' + attId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.success) {
          if (row) {
            row.style.transition = 'opacity 0.3s';
            row.style.opacity = '0';
            setTimeout(function() { row.remove(); }, 300);
          }
        } else {
          alert('<?php echo __("Failed to delete attachment."); ?>');
        }
      })
      .catch(function() {
        alert('<?php echo __("An error occurred."); ?>');
      });
    });
  });
});
</script>
