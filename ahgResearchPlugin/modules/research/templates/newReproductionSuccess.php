<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php include_partial('research/accessibilityHelpers') ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'reproductions']); ?>">Reproductions</a></li>
        <li class="breadcrumb-item active" aria-current="page">New Request</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-copy text-primary me-2" aria-hidden="true"></i>New Reproduction Request</h1>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Purpose of Reproduction *</label>
                        <select name="purpose" class="form-select" required>
                            <option value="">Select purpose...</option>
                            <option value="research">Academic Research</option>
                            <option value="publication">Publication</option>
                            <option value="exhibition">Exhibition</option>
                            <option value="documentary">Documentary/Film</option>
                            <option value="personal">Personal Use</option>
                            <option value="commercial">Commercial Use</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Intended Use</label>
                        <textarea name="intended_use" class="form-control" rows="3" placeholder="Describe how you plan to use the reproductions..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Publication Details</label>
                        <textarea name="publication_details" class="form-control" rows="2" placeholder="If for publication, provide title, publisher, expected date..."></textarea>
                        <small class="text-muted">Required for publication or commercial use</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Delivery Method</label>
                            <select name="delivery_method" class="form-select">
                                <option value="digital">Digital Download</option>
                                <option value="email">Email</option>
                                <option value="physical">Physical Copy (Post)</option>
                                <option value="collect">Collect in Person</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Urgency</label>
                            <select name="urgency" class="form-select">
                                <option value="normal">Normal (10-15 working days)</option>
                                <option value="high">High Priority (5-7 working days)</option>
                                <option value="rush">Rush (2-3 working days) - additional fee</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Special Instructions</label>
                        <textarea name="special_instructions" class="form-control" rows="2" placeholder="Any special requirements or notes..."></textarea>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-muted mb-3">Optional: Add First Item</h6>
                    <div class="mb-3">
                        <label class="form-label">Archive Item</label>
                        <select id="firstItemSearch" name="first_item_id"></select>
                        <small class="text-muted">You can add more items after creating the request.</small>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="first_item_type" class="form-select">
                                <option value="digital_scan">Scan</option>
                                <option value="photocopy">Photocopy</option>
                                <option value="photograph">Photograph</option>
                                <option value="certified_copy">Certified Copy</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Format</label>
                            <select name="first_item_format" class="form-select">
                                <option value="pdf">PDF</option>
                                <option value="tiff">TIFF</option>
                                <option value="jpeg">JPEG</option>
                                <option value="paper">Paper</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Specifications</label>
                            <input type="text" name="first_item_specs" class="form-control" placeholder="e.g. 300dpi colour">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1" aria-hidden="true"></i> Create Request</button>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'reproductions']); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2" aria-hidden="true"></i>Pricing Information</h6></div>
            <div class="card-body">
                <p class="small text-muted">Reproduction fees vary based on:</p>
                <ul class="small">
                    <li>Type of reproduction (scan, photograph, photocopy)</li>
                    <li>Size and resolution</li>
                    <li>Color or black & white</li>
                    <li>Quantity</li>
                    <li>Urgency</li>
                    <li>Intended use (commercial vs non-commercial)</li>
                </ul>
                <p class="small text-muted">A quote will be provided before processing.</p>
            </div>
        </div>
    </div>
</div>

<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">
<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js" <?php echo $na; ?>></script>
<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#firstItemSearch', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title', 'identifier'],
        maxItems: 1,
        placeholder: 'Search archival descriptions...',
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            fetch('/research/ajax/search-entities?type=information_object&q=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    callback((j.items || []).map(function(i) {
                        return { id: String(i.id), title: i.title || ('ID: ' + i.id), identifier: i.identifier || '' };
                    }));
                })
                .catch(function() { callback(); });
        },
        render: {
            option: function(item, escape) {
                return '<div class="py-1"><strong>' + escape(item.title) + '</strong>' +
                    (item.identifier ? '<br><small class="text-muted">' + escape(item.identifier) + '</small>' : '') + '</div>';
            },
            item: function(item, escape) { return '<div>' + escape(item.title) + '</div>'; },
            no_results: function() { return '<div class="no-results p-2 text-muted">No results found</div>'; }
        }
    });
});
</script>
