<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">

    <!-- Actor Info -->
    <section class="sidebar-section">
        <h4><?php echo __('Actor'); ?></h4>
        <div class="actor-summary">
            <strong><?php echo $actor->getAuthorizedFormOfName(['cultureFallback' => true]); ?></strong>
            <?php if ($actorType): ?>
                <span class="actor-type-badge"><?php echo ucfirst(str_replace('_', ' ', $actorType)); ?></span>
            <?php endif; ?>
        </div>
        <a href="<?php echo url_for([$actor, 'module' => 'actor']); ?>" class="btn btn-sm btn-outline-secondary btn-block">
            <i class="fa fa-arrow-left"></i> <?php echo __('Back to Actor'); ?>
        </a>
    </section>

    <!-- Available Sources -->
    <section class="sidebar-section">
        <h4><?php echo __('Authority Sources'); ?></h4>
        <ul class="source-list">
            <?php foreach ($sources as $sourceId => $sourceDef): ?>
                <li class="<?php echo isset($linkedAuthorities[$sourceId]) ? 'linked' : ''; ?>">
                    <i class="fa <?php echo $sourceDef['icon']; ?>"></i>
                    <span class="source-label"><?php echo $sourceDef['label']; ?></span>
                    <?php if (isset($linkedAuthorities[$sourceId])): ?>
                        <i class="fa fa-check-circle text-success"></i>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <!-- Help -->
    <section class="sidebar-section">
        <h4><?php echo __('About Authority Linking'); ?></h4>
        <p class="small"><?php echo __('Link this actor record to external authority files to enable data sharing and enrichment.'); ?></p>
        <p class="small"><?php echo __('Linked authorities provide standardized identifiers for interoperability with other systems.'); ?></p>
    </section>

</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1>
    <i class="fa fa-link"></i>
    <?php echo __('Authority Linkage'); ?>
</h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="authority-linkage-page">

    <!-- Flash Messages -->
    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $sf_user->getFlash('notice'); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $sf_user->getFlash('error'); ?>
        </div>
    <?php endif; ?>

    <!-- Linked Authorities -->
    <div class="section-box">
        <h3><i class="fa fa-check-circle"></i> <?php echo __('Linked Authorities'); ?></h3>
        
        <?php if (empty($linkedAuthorities)): ?>
            <div class="no-authorities">
                <p><?php echo __('No external authorities linked yet. Use the search below to find and link authorities.'); ?></p>
            </div>
        <?php else: ?>
            <div class="linked-authorities-grid">
                <?php foreach ($linkedAuthorities as $sourceId => $auth): ?>
                    <div class="authority-card linked">
                        <div class="authority-header">
                            <i class="fa <?php echo $sources[$sourceId]['icon']; ?>"></i>
                            <span class="authority-source"><?php echo $sources[$sourceId]['label']; ?></span>
                        </div>
                        
                        <div class="authority-id">
                            <a href="<?php echo $auth['uri']; ?>" target="_blank">
                                <?php echo $auth['id']; ?>
                                <i class="fa fa-external-link"></i>
                            </a>
                        </div>

                        <?php if (isset($enrichmentData[$sourceId])): ?>
                            <div class="authority-preview">
                                <?php $data = $enrichmentData[$sourceId]; ?>
                                <?php if (!empty($data['birthDate']) || !empty($data['deathDate'])): ?>
                                    <div class="preview-item">
                                        <i class="fa fa-calendar"></i>
                                        <?php echo ($data['birthDate'] ?? '?') . ' – ' . ($data['deathDate'] ?? '?'); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($data['nationality'])): ?>
                                    <div class="preview-item">
                                        <i class="fa fa-globe"></i>
                                        <?php echo $data['nationality']; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($data['birthPlace'])): ?>
                                    <div class="preview-item">
                                        <i class="fa fa-map-marker"></i>
                                        <?php echo $data['birthPlace']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Enrich Form -->
                            <form method="post" class="enrich-form">
                                <input type="hidden" name="action_type" value="enrich">
                                <input type="hidden" name="enrich_source" value="<?php echo $sourceId; ?>">
                                
                                <div class="enrich-options">
                                    <label class="enrich-option">
                                        <input type="checkbox" name="enrich_fields[]" value="dates" 
                                               <?php echo (empty($data['birthDate']) && empty($data['deathDate'])) ? 'disabled' : ''; ?>>
                                        <span>Dates</span>
                                    </label>
                                    <label class="enrich-option">
                                        <input type="checkbox" name="enrich_fields[]" value="places"
                                               <?php echo (empty($data['birthPlace']) && empty($data['deathPlace'])) ? 'disabled' : ''; ?>>
                                        <span>Places</span>
                                    </label>
                                    <label class="enrich-option">
                                        <input type="checkbox" name="enrich_fields[]" value="biography"
                                               <?php echo (empty($data['biography']) && empty($data['description'])) ? 'disabled' : ''; ?>>
                                        <span>Biography</span>
                                    </label>
                                    <label class="enrich-option">
                                        <input type="checkbox" name="enrich_fields[]" value="nationality"
                                               <?php echo empty($data['nationality']) ? 'disabled' : ''; ?>>
                                        <span>Nationality</span>
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-download"></i> <?php echo __('Import Selected'); ?>
                                </button>
                            </form>

                            <!-- Linked IDs from this authority -->
                            <?php if (!empty($data['linkedAuthorities'])): ?>
                                <div class="linked-from-authority">
                                    <small><?php echo __('Also linked in this authority:'); ?></small>
                                    <?php foreach ($data['linkedAuthorities'] as $linkedSource => $linkedId): ?>
                                        <?php if ($linkedId && !isset($linkedAuthorities[$linkedSource])): ?>
                                            <form method="post" class="inline-link-form">
                                                <input type="hidden" name="action_type" value="link">
                                                <input type="hidden" name="link_source" value="<?php echo $linkedSource; ?>">
                                                <input type="hidden" name="link_id" value="<?php echo $linkedId; ?>">
                                                <button type="submit" class="btn-link-suggestion">
                                                    <i class="fa fa-plus-circle"></i>
                                                    <?php echo strtoupper($linkedSource); ?>: <?php echo $linkedId; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="authority-actions">
                            <form method="post" class="unlink-form" onsubmit="return confirm('Unlink this authority?');">
                                <input type="hidden" name="action_type" value="unlink">
                                <input type="hidden" name="unlink_source" value="<?php echo $sourceId; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fa fa-unlink"></i> <?php echo __('Unlink'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search Authorities -->
    <div class="section-box">
        <h3><i class="fa fa-search"></i> <?php echo __('Search External Authorities'); ?></h3>
        
        <form method="get" action="<?php echo url_for(['module' => 'authority', 'action' => 'link', 'slug' => $actor->slug]); ?>" class="authority-search-form">
            <div class="search-row">
                <div class="search-input-group">
                    <input type="text" name="q" class="form-control" 
                           placeholder="<?php echo __('Search by name...'); ?>"
                           value="<?php echo $searchQuery; ?>">
                </div>
                <div class="search-source-group">
                    <select name="source" class="form-control">
                        <option value="all" <?php echo $searchSource === 'all' ? 'selected' : ''; ?>>
                            <?php echo __('All Sources'); ?>
                        </option>
                        <?php foreach ($sources as $sourceId => $sourceDef): ?>
                            <option value="<?php echo $sourceId; ?>" <?php echo $searchSource === $sourceId ? 'selected' : ''; ?>>
                                <?php echo $sourceDef['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> <?php echo __('Search'); ?>
                </button>
            </div>
            
            <?php if (!$searchQuery): ?>
                <div class="search-suggestion">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.querySelector('input[name=q]').value = '<?php echo addslashes($actor->getAuthorizedFormOfName(['cultureFallback' => true])); ?>'; this.form.submit();">
                        <i class="fa fa-magic"></i> <?php echo __('Search for "%1%"', ['%1%' => $actor->getAuthorizedFormOfName(['cultureFallback' => true])]); ?>
                    </button>
                </div>
            <?php endif; ?>
        </form>

        <!-- Search Results -->
        <?php if ($searchQuery && !empty($searchResults)): ?>
            <div class="search-results">
                <?php foreach ($searchResults as $sourceId => $results): ?>
                    <?php if (isset($results['error'])): ?>
                        <div class="source-results">
                            <h4>
                                <i class="fa <?php echo $sources[$sourceId]['icon']; ?>"></i>
                                <?php echo $sources[$sourceId]['label']; ?>
                            </h4>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i>
                                <?php echo __('Error: %1%', ['%1%' => $results['error']]); ?>
                            </div>
                        </div>
                    <?php elseif (!empty($results)): ?>
                        <div class="source-results">
                            <h4>
                                <i class="fa <?php echo $sources[$sourceId]['icon']; ?>"></i>
                                <?php echo $sources[$sourceId]['label']; ?>
                                <span class="result-count">(<?php echo count($results); ?>)</span>
                            </h4>
                            
                            <div class="results-list">
                                <?php foreach ($results as $result): ?>
                                    <div class="result-item <?php echo isset($linkedAuthorities[$sourceId]) && $linkedAuthorities[$sourceId]['id'] === $result['id'] ? 'already-linked' : ''; ?>">
                                        <div class="result-main">
                                            <div class="result-label">
                                                <strong><?php echo $result['label']; ?></strong>
                                                <?php if (!empty($result['type']) && $result['type'] !== 'unknown'): ?>
                                                    <span class="type-badge"><?php echo ucfirst(str_replace('_', ' ', $result['type'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (!empty($result['description']) || !empty($result['biography'])): ?>
                                                <div class="result-description">
                                                    <?php echo mb_substr($result['description'] ?? $result['biography'], 0, 150); ?>...
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="result-meta">
                                                <?php if (!empty($result['birthDate']) || !empty($result['deathDate'])): ?>
                                                    <span class="meta-item">
                                                        <i class="fa fa-calendar"></i>
                                                        <?php echo ($result['birthDate'] ?? '?') . ' – ' . ($result['deathDate'] ?? '?'); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($result['nationality'])): ?>
                                                    <span class="meta-item">
                                                        <i class="fa fa-globe"></i>
                                                        <?php echo $result['nationality']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="meta-item">
                                                    <a href="<?php echo $result['uri']; ?>" target="_blank">
                                                        <?php echo $result['id']; ?>
                                                        <i class="fa fa-external-link"></i>
                                                    </a>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="result-actions">
                                            <?php if (isset($linkedAuthorities[$sourceId]) && $linkedAuthorities[$sourceId]['id'] === $result['id']): ?>
                                                <span class="already-linked-badge">
                                                    <i class="fa fa-check"></i> <?php echo __('Linked'); ?>
                                                </span>
                                            <?php else: ?>
                                                <form method="post">
                                                    <input type="hidden" name="action_type" value="link">
                                                    <input type="hidden" name="link_source" value="<?php echo $sourceId; ?>">
                                                    <input type="hidden" name="link_id" value="<?php echo $result['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fa fa-link"></i> <?php echo __('Link'); ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php elseif ($searchQuery): ?>
            <div class="no-results">
                <i class="fa fa-search"></i>
                <p><?php echo __('No results found for "%1%"', ['%1%' => $searchQuery]); ?></p>
                <p class="small"><?php echo __('Try a different search term or check the spelling.'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Manual Entry -->
    <div class="section-box">
        <h3><i class="fa fa-keyboard-o"></i> <?php echo __('Manual Entry'); ?></h3>
        <p><?php echo __('If you know the authority ID, you can enter it directly:'); ?></p>
        
        <form method="post" class="manual-entry-form">
            <input type="hidden" name="action_type" value="link">
            <div class="manual-entry-row">
                <select name="link_source" class="form-control" required>
                    <option value=""><?php echo __('Select source...'); ?></option>
                    <?php foreach ($sources as $sourceId => $sourceDef): ?>
                        <option value="<?php echo $sourceId; ?>"><?php echo $sourceDef['label']; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="link_id" class="form-control" 
                       placeholder="<?php echo __('Authority ID (e.g., 500010879, Q762)'); ?>" required>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-link"></i> <?php echo __('Link'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- ISAAR/CIDOC Mapping Info -->
    <div class="section-box info-box">
        <h3><i class="fa fa-info-circle"></i> <?php echo __('Standards Compliance'); ?></h3>
        <div class="standards-info">
            <div class="standard-item">
                <h5>ISAAR(CPF)</h5>
                <p><?php echo __('International Standard Archival Authority Record for Corporate Bodies, Persons and Families. Linked authorities enhance the Control Area by providing standardized identifiers.'); ?></p>
            </div>
            <div class="standard-item">
                <h5>CIDOC-CRM</h5>
                <p><?php echo __('This actor maps to CIDOC-CRM class: %1%', ['%1%' => '<code>' . (new arAuthorityLinkageService())->mapToCIDOCClass($actorType) . '</code>']); ?></p>
            </div>
        </div>
    </div>

</div>

<style>
.authority-linkage-page .section-box {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.authority-linkage-page .section-box h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    color: #2c3e50;
    padding-bottom: 10px;
    border-bottom: 2px solid #3498db;
}

.authority-linkage-page .section-box h3 i {
    margin-right: 10px;
    color: #3498db;
}

/* Actor Summary */
.actor-summary {
    margin-bottom: 15px;
}

.actor-type-badge {
    display: inline-block;
    font-size: 11px;
    padding: 2px 8px;
    background: #3498db;
    color: #fff;
    border-radius: 10px;
    margin-left: 8px;
}

/* Source List */
.source-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.source-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
    color: #7f8c8d;
}

.source-list li.linked {
    color: #2c3e50;
}

.source-list li i:first-child {
    width: 20px;
    text-align: center;
}

.source-label {
    flex: 1;
}

/* Linked Authorities Grid */
.linked-authorities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.authority-card {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 20px;
    background: #f8f9fa;
}

.authority-card.linked {
    border-color: #27ae60;
    border-left-width: 4px;
}

.authority-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.authority-header i {
    font-size: 20px;
    color: #3498db;
}

.authority-source {
    font-weight: 600;
    color: #2c3e50;
}

.authority-id {
    font-family: monospace;
    font-size: 14px;
    margin-bottom: 15px;
}

.authority-id a {
    color: #3498db;
}

.authority-preview {
    background: #fff;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.preview-item {
    font-size: 13px;
    color: #5d6d7e;
    margin-bottom: 6px;
}

.preview-item i {
    width: 20px;
    color: #7f8c8d;
}

.enrich-form {
    margin-bottom: 15px;
}

.enrich-options {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.enrich-option {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    cursor: pointer;
}

.enrich-option input:disabled + span {
    color: #bdc3c7;
}

.linked-from-authority {
    background: #fff3cd;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.linked-from-authority small {
    display: block;
    margin-bottom: 8px;
    color: #856404;
}

.btn-link-suggestion {
    background: none;
    border: 1px dashed #856404;
    color: #856404;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    margin-right: 8px;
    margin-bottom: 5px;
}

.btn-link-suggestion:hover {
    background: #856404;
    color: #fff;
}

.inline-link-form {
    display: inline;
}

/* Search Form */
.authority-search-form {
    margin-bottom: 20px;
}

.search-row {
    display: flex;
    gap: 15px;
}

.search-input-group {
    flex: 1;
}

.search-source-group {
    width: 200px;
}

.search-suggestion {
    margin-top: 10px;
}

/* Search Results */
.search-results {
    margin-top: 25px;
}

.source-results {
    margin-bottom: 25px;
}

.source-results h4 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    color: #2c3e50;
}

.result-count {
    font-weight: normal;
    color: #7f8c8d;
    font-size: 14px;
}

.results-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.result-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.result-item.already-linked {
    background: #e8f8f5;
    border-color: #27ae60;
}

.result-main {
    flex: 1;
    margin-right: 15px;
}

.result-label strong {
    color: #2c3e50;
}

.type-badge {
    display: inline-block;
    font-size: 10px;
    padding: 2px 6px;
    background: #3498db;
    color: #fff;
    border-radius: 8px;
    margin-left: 8px;
    font-weight: normal;
}

.result-description {
    font-size: 13px;
    color: #5d6d7e;
    margin-top: 6px;
}

.result-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 8px;
}

.meta-item {
    font-size: 12px;
    color: #7f8c8d;
}

.meta-item i {
    margin-right: 5px;
}

.result-actions {
    flex-shrink: 0;
}

.already-linked-badge {
    display: inline-block;
    padding: 6px 12px;
    background: #27ae60;
    color: #fff;
    border-radius: 4px;
    font-size: 13px;
}

/* Manual Entry */
.manual-entry-row {
    display: flex;
    gap: 15px;
}

.manual-entry-row select {
    width: 200px;
}

.manual-entry-row input {
    flex: 1;
}

/* No Results */
.no-results, .no-authorities {
    text-align: center;
    padding: 40px;
    color: #7f8c8d;
}

.no-results i, .no-authorities i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Info Box */
.info-box {
    background: #e8f4fd !important;
}

.standards-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.standard-item h5 {
    color: #2980b9;
    margin-bottom: 8px;
}

.standard-item p {
    font-size: 13px;
    color: #34495e;
    margin: 0;
}

.standard-item code {
    background: #d4edda;
    padding: 2px 6px;
    border-radius: 3px;
}

/* Responsive */
@media (max-width: 768px) {
    .search-row {
        flex-direction: column;
    }
    
    .search-source-group {
        width: 100%;
    }
    
    .linked-authorities-grid {
        grid-template-columns: 1fr;
    }
    
    .manual-entry-row {
        flex-direction: column;
    }
    
    .manual-entry-row select {
        width: 100%;
    }
    
    .standards-info {
        grid-template-columns: 1fr;
    }
}
</style>

<?php end_slot(); ?>
