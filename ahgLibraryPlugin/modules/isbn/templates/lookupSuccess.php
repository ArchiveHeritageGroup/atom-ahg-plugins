<?php
/**
 * ISBN / DOI / ISSN Resolver — lookup page.
 *
 * Accepts ISBN (10 or 13), DOI (10.xxxx/...), or ISSN (xxxx-xxxx).
 * Resolution via /isbn/resolve?id=<identifier>
 */
?>
<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Identifier Resolver'); ?></h1>
<?php end_slot(); ?>

<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><?php echo __('Look up bibliographic metadata'); ?></h5>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">
      <?php echo __('Enter an ISBN (10 or 13 digits), DOI (e.g. 10.1000/xyz123), or ISSN (e.g. 1234-5678).'); ?>
    </p>

    <form method="get" action="<?php echo url_for(['module' => 'isbn', 'action' => 'resolve']); ?>" id="resolver-form">
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
          <label for="identifier-input" class="form-label"><?php echo __('Identifier'); ?></label>
          <input type="text"
                 class="form-control"
                 name="id"
                 id="identifier-input"
                 placeholder="<?php echo __('e.g. 9780134685991, 10.1000/xyz123, 1234-5678'); ?>"
                 required>
        </div>
        <div class="col-md-auto">
          <button type="submit" class="btn btn-primary" id="lookup-btn">
            <i class="fas fa-search me-1"></i><?php echo __('Resolve'); ?>
          </button>
        </div>
        <div class="col-md-auto">
          <button type="button" class="btn btn-outline-secondary" id="clear-btn">
            <?php echo __('Clear'); ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="resolver-result" class="d-none">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><?php echo __('Result'); ?></h5>
      <span id="result-source" class="badge bg-info"></span>
    </div>
    <div class="card-body" id="result-body"></div>
  </div>
</div>

<div id="resolver-error" class="d-none">
  <div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <span id="error-message"></span>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? 'nonce="'.$n.'"' : ''; ?>>
(function () {
  var form = document.getElementById('resolver-form');
  var input = document.getElementById('identifier-input');
  var resultDiv = document.getElementById('resolver-result');
  var resultBody = document.getElementById('result-body');
  var resultSource = document.getElementById('result-source');
  var errorDiv = document.getElementById('resolver-error');
  var errorMsg = document.getElementById('error-message');
  var clearBtn = document.getElementById('clear-btn');

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    var d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
  }

  function renderPreview(data) {
    var rows = [
      ['Type', escapeHtml(data.type || '')],
      ['Identifier', escapeHtml(data.identifier || '')],
      ['Title', escapeHtml(data.preview && data.preview.title ? data.preview.title : '')],
      ['Authors / Creators', escapeHtml(data.preview && data.preview.creators ? data.preview.creators : '')],
      ['Publisher', escapeHtml(data.preview && data.preview.publisher ? data.preview.publisher : '')],
      ['Publication Date', escapeHtml(data.preview && data.preview.publication_date ? data.preview.publication_date : '')],
      ['ISBN-13', escapeHtml(data.preview && data.preview.identifiers && data.preview.identifiers['ISBN-13'] ? data.preview.identifiers['ISBN-13'] : '')],
      ['ISBN-10', escapeHtml(data.preview && data.preview.identifiers && data.preview.identifiers['ISBN-10'] ? data.preview.identifiers['ISBN-10'] : '')],
      ['ISSN', escapeHtml(data.data && data.data.issn ? data.data.issn : '')],
      ['DOI', escapeHtml(data.data && data.data.doi ? data.data.doi : '')],
      ['Language', escapeHtml(data.preview && data.preview.language ? data.preview.language : '')],
      ['Extent', escapeHtml(data.preview && data.preview.extent ? data.preview.extent : '')],
      ['Call Number', escapeHtml(data.preview && data.preview.call_number ? data.preview.call_number : '')],
      ['Subjects', escapeHtml(data.preview && data.preview.subjects ? data.preview.subjects.join('; ') : '')],
      ['Description', escapeHtml(data.preview && data.preview.description ? data.preview.description : '')],
    ];

    var html = '<table class="table table-sm table-borderless mb-0">';
    for (var i = 0; i < rows.length; i++) {
      var val = rows[i][1];
      if (!val) continue;
      html += '<tr><th style="width:30%" class="text-end text-muted">' + rows[i][0] + ':</th>';
      html += '<td>' + val + '</td></tr>';
    }
    html += '</table>';
    return html;
  }

  function doLookup(id) {
    resultDiv.classList.add('d-none');
    errorDiv.classList.add('d-none');
    var btn = document.getElementById('lookup-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><?php echo __('Resolving...'); ?>';

    fetch('<?php echo url_for(['module' => 'isbn', 'action' => 'resolve']); ?>?id=' + encodeURIComponent(id))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search me-1"></i><?php echo __('Resolve'); ?>';

        if (data.success) {
          resultSource.textContent = data.type.toUpperCase() + ' — ' + (data.source || data.type);
          resultBody.innerHTML = renderPreview(data);
          resultDiv.classList.remove('d-none');
        } else {
          errorMsg.textContent = data.error || '<?php echo __('Resolution failed'); ?>';
          errorDiv.classList.remove('d-none');
        }
      })
      .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search me-1"></i><?php echo __('Resolve'); ?>';
        errorMsg.textContent = err.message;
        errorDiv.classList.remove('d-none');
      });
  }

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var id = input.value.trim();
    if (!id) return;
    doLookup(id);
  });

  clearBtn.addEventListener('click', function() {
    input.value = '';
    resultDiv.classList.add('d-none');
    errorDiv.classList.add('d-none');
    input.focus();
  });

  // Auto-resolve if identifier already in URL params
  var urlParams = new URLSearchParams(window.location.search);
  var urlId = urlParams.get('id') || urlParams.get('identifier');
  if (urlId) {
    input.value = urlId;
    doLookup(urlId);
  }
})();
</script>
