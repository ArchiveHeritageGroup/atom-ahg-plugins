<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$citations = sfOutputEscaper::unescape($citations ?? []);
$styleColors = [
    'chicago' => 'primary',
    'mla' => 'success', 
    'turabian' => 'info',
    'apa' => 'warning',
    'harvard' => 'danger',
    'unisa' => 'dark'
];
$styleNames = [
    'chicago' => 'CHICAGO Style',
    'mla' => 'MLA Style',
    'turabian' => 'TURABIAN Style',
    'apa' => 'APA Style',
    'harvard' => 'HARVARD Style',
    'unisa' => 'UNISA HARVARD Style'
];
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Citation Generator</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-quote-right text-primary me-2"></i>Citation Generator</h1>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<?php
// §2.1 Per-record citation manager export picker.
$slugForExport = sfContext::getInstance()->getRequest()->getParameter('slug');
$exportFormats = [
    'ris'     => ['label' => 'RIS',       'desc' => 'Zotero, Mendeley, EndNote',  'icon' => 'file-export'],
    'bibtex'  => ['label' => 'BibTeX',    'desc' => 'LaTeX, JabRef',              'icon' => 'file-code'],
    'endnote' => ['label' => 'EndNote',   'desc' => 'EndNote XML',                'icon' => 'file-alt'],
    'apa'     => ['label' => 'APA 7',     'desc' => 'Plain-text APA citation',    'icon' => 'paragraph'],
    'mla'     => ['label' => 'MLA 9',     'desc' => 'Plain-text MLA citation',    'icon' => 'paragraph'],
    'chicago' => ['label' => 'Chicago',   'desc' => 'Notes-Bibliography 17',      'icon' => 'paragraph'],
];
?>
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-file-download me-2"></i>Copy in citation manager format</h5>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">Download a file in your citation manager's native format. Import into Zotero / Mendeley / EndNote / JabRef / LaTeX in one click.</p>
        <div class="row g-2">
            <?php foreach ($exportFormats as $fmt => $info): ?>
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo url_for(['module' => 'research', 'action' => 'citeExport', 'slug' => $slugForExport, 'format' => $fmt]); ?>"
                       class="btn btn-outline-primary w-100 text-start d-flex align-items-center">
                        <i class="fas fa-<?php echo htmlspecialchars($info['icon']); ?> me-2"></i>
                        <span>
                            <strong><?php echo htmlspecialchars($info['label']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($info['desc']); ?></small>
                        </span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row">
    <?php foreach ($citations as $style => $data): ?>
        <?php if (!isset($data['error'])): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-<?php echo $styleColors[$style] ?? 'secondary'; ?> text-white">
                        <h5 class="mb-0"><?php echo $styleNames[$style] ?? strtoupper($style) . ' Style'; ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="citation-text" id="cite-<?php echo $style; ?>"><?php echo $data['citation']; ?></p>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('cite-<?php echo $style; ?>')">
                            <i class="fas fa-copy me-1"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php 
$firstCitation = reset($citations);
if ($firstCitation && !isset($firstCitation['error'])): 
?>
<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Citation Information</h5></div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr><th width="150">Title:</th><td><?php echo htmlspecialchars($firstCitation['object_title'] ?? ''); ?></td></tr>
            <tr><th>URL:</th><td><a href="<?php echo $firstCitation['url'] ?? '#'; ?>" target="_blank"><?php echo htmlspecialchars($firstCitation['url'] ?? ''); ?></a></td></tr>
            <tr><th>Accessed:</th><td><?php echo date('F j, Y'); ?></td></tr>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card mt-4">
    <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Citation Style Guide</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><span class="badge bg-danger">Harvard</span></h6>
                <p class="small text-muted">Standard Harvard referencing style used internationally.</p>
            </div>
            <div class="col-md-6">
                <h6><span class="badge bg-dark">UNISA Harvard</span></h6>
                <p class="small text-muted">University of South Africa's specific Harvard referencing format, commonly used in South African academic institutions.</p>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function copyToClipboard(id) {
    var el = document.getElementById(id);
    // Strip HTML tags for plain text copy
    var text = el.innerText || el.textContent;
    navigator.clipboard.writeText(text).then(function() {
        // Show feedback
        var btn = event.target.closest('button');
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        setTimeout(function() {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}
</script>
