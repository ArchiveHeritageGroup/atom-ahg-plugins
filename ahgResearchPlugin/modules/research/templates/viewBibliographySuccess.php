<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'bibliographies']); ?>">Bibliographies</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($bibliography->name); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2"><?php echo htmlspecialchars($bibliography->name); ?></h1>
        <?php if ($bibliography->description): ?>
            <p class="text-muted"><?php echo htmlspecialchars($bibliography->description); ?></p>
        <?php endif; ?>
        <span class="badge bg-secondary"><?php echo count($entries); ?> entries</span>
        <span class="badge bg-light text-dark"><?php echo ucfirst($bibliography->citation_style ?? 'chicago'); ?> style</span>
    </div>
    <div class="d-flex gap-2">
        <?php include_partial('research/favoriteResearchButton', [
            'objectId' => $bibliography->id,
            'objectType' => 'research_bibliography',
            'title' => $bibliography->name,
            'url' => '/research/bibliography/' . $bibliography->id,
        ]); ?>
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importBibliographyModal">
            <i class="fas fa-upload me-1"></i> <?php echo __('Import'); ?>
        </button>
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-download me-1"></i> <?php echo __('Export'); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportBibliography', 'id' => $bibliography->id, 'format' => 'ris']); ?>">RIS (EndNote, Zotero)</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportBibliography', 'id' => $bibliography->id, 'format' => 'bibtex']); ?>">BibTeX (LaTeX)</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportBibliography', 'id' => $bibliography->id, 'format' => 'zotero']); ?>">Zotero RDF</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportBibliography', 'id' => $bibliography->id, 'format' => 'mendeley']); ?>">Mendeley JSON</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportBibliography', 'id' => $bibliography->id, 'format' => 'csl']); ?>">CSL-JSON</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Entries</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                    <i class="fas fa-plus me-1"></i> Add Entry
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($entries)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($entries as $entry): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($entry->title ?: 'Untitled'); ?></strong>
                                        <?php if ($entry->authors): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($entry->authors); ?></small>
                                        <?php endif; ?>
                                        <?php if ($entry->publication_date): ?>
                                            <span class="ms-2 badge bg-light text-dark"><?php echo $entry->publication_date; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="form_action" value="remove_entry">
                                        <input type="hidden" name="entry_id" value="<?php echo $entry->id; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this entry?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php if ($entry->formatted_citation): ?>
                                    <div class="mt-2 small text-muted fst-italic"><?php echo htmlspecialchars($entry->formatted_citation); ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-list fa-2x mb-2"></i>
                        <p>No entries yet</p>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                            Add your first entry
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>About</h6></div>
            <div class="card-body">
                <p class="mb-2"><strong>Created:</strong> <?php echo date('M j, Y', strtotime($bibliography->created_at)); ?></p>
                <p class="mb-0"><strong>Last updated:</strong> <?php echo date('M j, Y', strtotime($bibliography->updated_at ?? $bibliography->created_at)); ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-cog me-2"></i>Actions</h6></div>
            <div class="card-body">
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this bibliography?')">
                    <input type="hidden" name="form_action" value="delete">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="fas fa-trash me-1"></i> Delete Bibliography
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tom Select CSS/JS -->
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<style <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.ts-dropdown { background: #fff !important; color: #212529 !important; }
.ts-dropdown .option { color: #212529 !important; }
</style>

<!-- Add Entry Modal -->
<div class="modal fade" id="addEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="add_entry">
                <div class="modal-header">
                    <h5 class="modal-title">Add Entry from Archive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Search Archive'); ?> *</label>
                        <select id="bibObjectSearch" name="object_id" required></select>
                        <small class="text-muted"><?php echo __('Type to search by title or reference code'); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#bibObjectSearch', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title', 'identifier'],
        placeholder: '<?php echo __("Search archive items..."); ?>',
        loadThrottle: 300,
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            var url = '<?php echo url_for(["module" => "research", "action" => "searchEntities"]); ?>';
            var sep = url.indexOf('?') >= 0 ? '&' : '?';
            fetch(url + sep + 'type=information_object&q=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(json) { callback(json.items || []); })
                .catch(function() { callback(); });
        },
        render: {
            option: function(item, escape) {
                return '<div class="py-1"><strong>' + escape(item.title) + '</strong>' +
                    (item.identifier ? '<br><small class="text-muted">' + escape(item.identifier) + '</small>' : '') +
                    '</div>';
            },
            item: function(item, escape) {
                return '<div>' + escape(item.title) + '</div>';
            }
        }
    });
});
</script>

<!-- Import Bibliography Modal -->
<div class="modal fade" id="importBibliographyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" action="<?php echo url_for(['module' => 'research', 'action' => 'importBibliography', 'id' => $bibliography->id]); ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i><?php echo __('Import Citations'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Format'); ?> *</label>
                        <select name="format" class="form-select" required>
                            <option value="bibtex">BibTeX (.bib)</option>
                            <option value="ris">RIS (.ris)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Upload File'); ?></label>
                        <input type="file" name="import_file" class="form-control" accept=".bib,.ris,.txt">
                        <small class="text-muted"><?php echo __('Upload a .bib or .ris file'); ?></small>
                    </div>
                    <div class="text-center text-muted my-2">&mdash; <?php echo __('or'); ?> &mdash;</div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Paste Content'); ?></label>
                        <textarea name="import_content" class="form-control" rows="8" placeholder="<?php echo __('Paste BibTeX or RIS content here...'); ?>"></textarea>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <?php echo __('Entries will be added to this bibliography. Duplicate titles will be skipped.'); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-upload me-1"></i> <?php echo __('Import'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
