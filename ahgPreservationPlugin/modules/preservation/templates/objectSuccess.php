<?php decorate_with('layout_1col.php') ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .pres-font-size-0-8em-c402 { font-size: 0.8em; }
</style>
<?php slot('title') ?>
<h1><i class="fas fa-file-lines text-primary me-2"></i><?php echo __('Preservation Details'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>"><?php echo __('Preservation'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($digitalObject->name ?? 'Object'); ?></li>
    </ol>
</nav>

<!-- Object Info -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-circle-info me-2"></i><?php echo __('Digital Object Information'); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="150"><?php echo __('ID'); ?></th>
                        <td><?php echo $digitalObject->id; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo __('Filename'); ?></th>
                        <td><?php echo htmlspecialchars($digitalObject->name ?? 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo __('Parent Object'); ?></th>
                        <td>
                            <?php if ($digitalObject->slug): ?>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $digitalObject->slug]); ?>">
                                    <?php echo htmlspecialchars($digitalObject->object_title ?? 'View'); ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __('File Size'); ?></th>
                        <td><?php echo number_format($digitalObject->byte_size ?? 0); ?> bytes</td>
                    </tr>
                    <tr>
                        <th><?php echo __('MIME Type'); ?></th>
                        <td><?php echo htmlspecialchars($digitalObject->mime_type ?? 'Unknown'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <?php if ($formatInfo): ?>
                <div class="alert <?php echo $formatInfo->risk_level === 'low' ? 'alert-success' : ($formatInfo->risk_level === 'high' || $formatInfo->risk_level === 'critical' ? 'alert-danger' : 'alert-warning'); ?>">
                    <h6><i class="fas fa-file-code me-1"></i><?php echo __('Format Information'); ?></h6>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($formatInfo->format_name); ?></strong></p>
                    <p class="mb-1">Risk Level: <strong><?php echo ucfirst($formatInfo->risk_level ?? 'unknown'); ?></strong></p>
                    <?php if ($formatInfo->is_preservation_format): ?>
                        <span class="badge bg-success"><?php echo __('Preservation Format'); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary" data-ahg-action="generateChecksums" data-ahg-args='[<?php echo $digitalObject->id; ?>]'>
                        <i class="fas fa-arrows-rotate me-1"></i><?php echo __('Regenerate Checksums'); ?>
                    </button>
                    <button class="btn btn-outline-primary" data-ahg-action="verifyFixity" data-ahg-args='[<?php echo $digitalObject->id; ?>]'>
                        <i class="fas fa-circle-check me-1"></i><?php echo __('Verify Fixity Now'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checksums -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-fingerprint me-2"></i><?php echo __('Checksums'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Algorithm'); ?></th>
                    <th><?php echo __('Value'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Generated'); ?></th>
                    <th><?php echo __('Last Verified'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($checksums)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <?php echo __('No checksums generated yet'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($checksums as $cs): ?>
                    <tr>
                        <td><strong><?php echo strtoupper($cs->algorithm); ?></strong></td>
                        <td><code class="pres-font-size-0-8em-c402"><?php echo $cs->checksum_value; ?></code></td>
                        <td>
                            <?php if ($cs->verification_status === 'valid'): ?>
                                <span class="badge bg-success">Valid</span>
                            <?php elseif ($cs->verification_status === 'invalid'): ?>
                                <span class="badge bg-danger">Invalid</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($cs->verification_status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo date('Y-m-d H:i', strtotime($cs->generated_at)); ?></small></td>
                        <td><small><?php echo $cs->verified_at ? date('Y-m-d H:i', strtotime($cs->verified_at)) : '-'; ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Fixity History -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-clock-rotate-left me-2"></i><?php echo __('Fixity Check History'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Algorithm'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Checked By'); ?></th>
                    <th><?php echo __('Duration'); ?></th>
                    <th><?php echo __('Checked At'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fixityHistory)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <?php echo __('No fixity checks performed yet'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($fixityHistory as $check): ?>
                    <tr>
                        <td><?php echo strtoupper($check->algorithm); ?></td>
                        <td>
                            <?php if ($check->status === 'pass'): ?>
                                <span class="badge bg-success">Pass</span>
                            <?php elseif ($check->status === 'fail'): ?>
                                <span class="badge bg-danger">Fail</span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?php echo ucfirst($check->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($check->checked_by); ?></td>
                        <td><?php echo $check->duration_ms; ?>ms</td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($check->checked_at)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Preservation Events -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-calendar-day me-2"></i><?php echo __('Preservation Events (PREMIS)'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Event Type'); ?></th>
                    <th><?php echo __('Detail'); ?></th>
                    <th><?php echo __('Outcome'); ?></th>
                    <th><?php echo __('Agent'); ?></th>
                    <th><?php echo __('Date/Time'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <?php echo __('No preservation events recorded'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?php echo str_replace('_', ' ', $event->event_type); ?></span></td>
                        <td><?php echo htmlspecialchars(substr($event->event_detail ?? '', 0, 50)); ?></td>
                        <td>
                            <?php if ($event->event_outcome === 'success'): ?>
                                <span class="text-success"><i class="fas fa-circle-check"></i> Success</span>
                            <?php elseif ($event->event_outcome === 'failure'): ?>
                                <span class="text-danger"><i class="fas fa-circle-xmark"></i> Failure</span>
                            <?php else: ?>
                                <span class="text-muted"><?php echo ucfirst($event->event_outcome); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($event->linking_agent_value ?? '-'); ?></small></td>
                        <td><small><?php echo date('Y-m-d H:i:s', strtotime($event->event_datetime)); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function generateChecksums(id) {
    if (!confirm('Generate new checksums for this object?')) return;

    fetch('/api/preservation/checksum/' + id + '/generate', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Checksums generated successfully');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(e => alert('Error: ' + e));
}

function verifyFixity(id) {
    fetch('/api/preservation/fixity/' + id + '/verify', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let msg = 'Fixity verification complete:\n';
            for (let algo in data.results) {
                msg += algo.toUpperCase() + ': ' + data.results[algo].status + '\n';
            }
            alert(msg);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(e => alert('Error: ' + e));
}
</script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Attach the CSRF token to this page's non-GET fetches. The framework rejects any
// mutating request without it, and these templates issue their calls through plain
// fetch() with no token - so every save, build and delete returned
// {"error":"CSRF token validation failed"}. Wrapping once is safer than editing each
// call site, and it leaves GETs untouched.
(function () {
    if (window.__ahgCsrfFetchWrapped) { return; }
    window.__ahgCsrfFetchWrapped = true;

    var token = '<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>';
    if (!token) { return; }

    var original = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || (typeof input === 'object' && input && input.method) || 'GET').toUpperCase();
        var url = (typeof input === 'string') ? input : (input && input.url) || '';
        var sameOrigin = !/^https?:\/\//i.test(url) || 0 === url.indexOf(window.location.origin);

        if ('GET' !== method && 'HEAD' !== method && sameOrigin) {
            var headers = new Headers(init.headers || (typeof input === 'object' && input ? input.headers : undefined) || {});
            if (!headers.has('X-CSRF-TOKEN')) { headers.set('X-CSRF-TOKEN', token); }
            init.headers = headers;
        }
        return original.call(this, input, init);
    };
})();
</script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Dispatches the buttons on this page.
//
// They used inline onclick handlers, and script-src here carries no
// 'unsafe-inline', so the browser refused to run them - silently, with nothing in
// the console pointing at the cause. Build & Export, validate, convert, export,
// delete and the object controls therefore did nothing at all, which is why every
// package sat in Draft: the request was never made.
//
// One delegated listener, dispatching by name to the functions defined above. No
// eval, so it works under the same policy that blocked the originals.
(function () {
    if (window.__ahgPreservationActionsWired) { return; }
    window.__ahgPreservationActionsWired = true;

    document.addEventListener('click', function (event) {
        var el = event.target.closest('[data-ahg-action]');
        if (!el) { return; }

        var fn = window[el.getAttribute('data-ahg-action')];
        if ('function' !== typeof fn) { return; }

        event.preventDefault();

        var args = [];
        try { args = JSON.parse(el.getAttribute('data-ahg-args') || '[]'); } catch (e) { args = []; }

        fn.apply(null, args);
    });
})();
</script>

<?php end_slot() ?>
