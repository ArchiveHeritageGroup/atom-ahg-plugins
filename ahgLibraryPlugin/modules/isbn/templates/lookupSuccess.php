<?php
/**
 * ISBN Lookup page template.
 * This page allows users to look up ISBN metadata.
 */
?>
<h1><?php echo __('ISBN Lookup'); ?></h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo url_for(['module' => 'isbn', 'action' => 'lookup']) ?>" id="isbn-lookup-form">
            <div class="row">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" name="isbn" id="isbn-input"
                               placeholder="<?php echo __('Enter ISBN (10 or 13 digits)'); ?>"
                               pattern="[0-9Xx-]{10,17}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i><?php echo __('Lookup'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="lookup-result" class="d-none">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?php echo __('Results'); ?></h5>
        </div>
        <div class="card-body" id="result-content">
        </div>
    </div>
</div>

<script>
document.getElementById('isbn-lookup-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var isbn = document.getElementById('isbn-input').value.replace(/[^0-9Xx]/g, '');
    if (!isbn) return;

    var resultDiv = document.getElementById('lookup-result');
    var resultContent = document.getElementById('result-content');
    resultContent.innerHTML = '<div class="spinner-border spinner-border-sm"></div> <?php echo __('Looking up...'); ?>';
    resultDiv.classList.remove('d-none');

    fetch('<?php echo url_for(['module' => 'isbn', 'action' => 'lookup']) ?>?isbn=' + encodeURIComponent(isbn))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var html = '<dl class="row">';
                if (data.preview) {
                    for (var key in data.preview) {
                        html += '<dt class="col-sm-3">' + key + '</dt>';
                        html += '<dd class="col-sm-9">' + (data.preview[key] || '-') + '</dd>';
                    }
                }
                html += '</dl>';
                html += '<small class="text-muted"><?php echo __('Source'); ?>: ' + data.source + '</small>';
                resultContent.innerHTML = html;
            } else {
                resultContent.innerHTML = '<div class="alert alert-warning mb-0">' + (data.error || '<?php echo __('Lookup failed'); ?>') + '</div>';
            }
        })
        .catch(function(err) {
            resultContent.innerHTML = '<div class="alert alert-danger mb-0">' + err.message + '</div>';
        });
});
</script>
