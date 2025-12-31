<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">

    <!-- Format Selection -->
    <section class="sidebar-section">
        <h4><?php echo __('Export Format'); ?></h4>
        <div class="format-list">
            <?php foreach ($formats as $formatId => $formatDef): ?>
                <label class="format-option <?php echo $format === $formatId ? 'active' : ''; ?>">
                    <input type="radio" name="format" value="<?php echo $formatId; ?>" 
                           <?php echo $format === $formatId ? 'checked' : ''; ?>
                           onchange="updateFormat(this.value)">
                    <span class="format-label"><?php echo $formatDef['label']; ?></span>
                    <span class="format-ext">.<?php echo $formatDef['extension']; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Options -->
    <section class="sidebar-section">
        <h4><?php echo __('Options'); ?></h4>
        <div class="form-check">
            <input type="checkbox" id="linkedData" name="linkedData" value="1" 
                   <?php echo $includeLinkedData ? 'checked' : ''; ?>>
            <label for="linkedData"><?php echo __('Include Linked Data URIs'); ?></label>
            <p class="help-text"><?php echo __('Add Getty vocabulary (AAT, TGN, ULAN) URIs where available'); ?></p>
        </div>
    </section>

    <!-- CIDOC-CRM Reference -->
    <section class="sidebar-section">
        <h4><?php echo __('CIDOC-CRM Reference'); ?></h4>
        <p class="small"><?php echo __('This export uses CIDOC Conceptual Reference Model v7.1'); ?></p>
        <a href="https://www.cidoc-crm.org/" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-external-link"></i> <?php echo __('CIDOC-CRM Website'); ?>
        </a>
    </section>

</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>
        <i class="fa fa-share-alt"></i>
        <?php echo __('CIDOC-CRM Export'); ?>
    </h1>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="cidoc-export-page">

    <!-- Introduction -->
    <div class="cidoc-intro">
        <p><?php echo __('Export your collection data in CIDOC Conceptual Reference Model (CRM) format for interoperability with other museum and cultural heritage systems.'); ?></p>
    </div>

    <!-- Export Scope Selection -->
    <div class="export-scope section-box">
        <h3><?php echo __('Export Scope'); ?></h3>
        
        <div class="scope-options">
            <div class="scope-option">
                <h4><?php echo __('Single Object'); ?></h4>
                <p><?php echo __('Export a specific object by entering its slug or identifier.'); ?></p>
                <form method="get" action="<?php echo url_for(['module' => 'cidoc', 'action' => 'export']); ?>" class="form-inline">
                    <input type="hidden" name="download" value="1">
                    <input type="hidden" name="format" value="<?php echo $format; ?>">
                    <input type="hidden" name="linkedData" value="<?php echo $includeLinkedData ? '1' : '0'; ?>">
                    <div class="input-group">
                        <input type="text" name="slug" class="form-control" placeholder="<?php echo __('Object slug...'); ?>"
                               value="<?php echo $objectSlug; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-download"></i> <?php echo __('Export'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="scope-divider">
                <span><?php echo __('or'); ?></span>
            </div>

            <div class="scope-option">
                <h4><?php echo __('Collection/Repository'); ?></h4>
                <p><?php echo __('Export all objects from a repository or the entire collection.'); ?></p>
                <form method="get" action="<?php echo url_for(['module' => 'cidoc', 'action' => 'export']); ?>">
                    <input type="hidden" name="download" value="1">
                    <input type="hidden" name="format" value="<?php echo $format; ?>">
                    <input type="hidden" name="linkedData" value="<?php echo $includeLinkedData ? '1' : '0'; ?>">
                    <div class="form-group">
                        <select name="repository" class="form-control">
                            <option value=""><?php echo __('All repositories'); ?></option>
                            <?php foreach ($repositories as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo $repositoryId == $id ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-download"></i> <?php echo __('Export Collection'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- CIDOC-CRM Class Mapping -->
    <div class="class-mapping section-box">
        <h3><?php echo __('CIDOC-CRM Class Mapping'); ?></h3>
        <p><?php echo __('AtoM entities are mapped to the following CIDOC-CRM classes:'); ?></p>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo __('AtoM Entity'); ?></th>
                    <th><?php echo __('CIDOC-CRM Class'); ?></th>
                    <th><?php echo __('Description'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Information Object</td>
                    <td><code>E22_Man-Made_Object</code></td>
                    <td><?php echo $crmClasses['E22_Man-Made_Object']; ?></td>
                </tr>
                <tr>
                    <td>Person (Actor)</td>
                    <td><code>E21_Person</code></td>
                    <td><?php echo $crmClasses['E21_Person']; ?></td>
                </tr>
                <tr>
                    <td>Corporate Body</td>
                    <td><code>E74_Group</code></td>
                    <td><?php echo $crmClasses['E74_Group']; ?></td>
                </tr>
                <tr>
                    <td>Repository</td>
                    <td><code>E40_Legal_Body</code></td>
                    <td><?php echo $crmClasses['E40_Legal_Body']; ?></td>
                </tr>
                <tr>
                    <td>Place Access Point</td>
                    <td><code>E53_Place</code></td>
                    <td><?php echo $crmClasses['E53_Place']; ?></td>
                </tr>
                <tr>
                    <td>Creation Event</td>
                    <td><code>E12_Production</code></td>
                    <td><?php echo $crmClasses['E12_Production']; ?></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td><code>E52_Time-Span</code></td>
                    <td><?php echo $crmClasses['E52_Time-Span']; ?></td>
                </tr>
                <tr>
                    <td>Subject/Term</td>
                    <td><code>E55_Type</code></td>
                    <td><?php echo $crmClasses['E55_Type']; ?></td>
                </tr>
                <tr>
                    <td>Digital Object</td>
                    <td><code>E36_Visual_Item</code></td>
                    <td><?php echo $crmClasses['E36_Visual_Item']; ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Property Mapping -->
    <div class="property-mapping section-box">
        <h3><?php echo __('Key Property Mappings'); ?></h3>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo __('AtoM Field'); ?></th>
                    <th><?php echo __('CIDOC-CRM Property'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Identifier</td><td><code>P1_is_identified_by → E42_Identifier</code></td></tr>
                <tr><td>Title</td><td><code>P102_has_title → E35_Title</code></td></tr>
                <tr><td>Level of Description</td><td><code>P2_has_type → E55_Type</code></td></tr>
                <tr><td>Creator</td><td><code>P108i_was_produced_by → E12_Production → P14_carried_out_by</code></td></tr>
                <tr><td>Date</td><td><code>P4_has_time-span → E52_Time-Span</code></td></tr>
                <tr><td>Repository</td><td><code>P50_has_current_keeper</code></td></tr>
                <tr><td>Location</td><td><code>P55_has_current_location</code></td></tr>
                <tr><td>Subject</td><td><code>P129_is_about</code></td></tr>
                <tr><td>Depicted Person</td><td><code>P62_depicts</code></td></tr>
                <tr><td>Part of</td><td><code>P46i_forms_part_of</code></td></tr>
                <tr><td>Digital Object</td><td><code>P138i_has_representation</code></td></tr>
                <tr><td>Access Conditions</td><td><code>P104_is_subject_to → E30_Right</code></td></tr>
            </tbody>
        </table>
    </div>

    <!-- Preview -->
    <?php if (isset($preview)): ?>
    <div class="preview-section section-box">
        <h3><?php echo __('Preview'); ?></h3>
        <pre class="code-preview"><code><?php echo htmlspecialchars($preview); ?></code></pre>
    </div>
    <?php endif; ?>

    <!-- Linked Data -->
    <div class="linked-data-info section-box">
        <h3><?php echo __('Linked Data Integration'); ?></h3>
        <p><?php echo __('When "Include Linked Data URIs" is enabled, the export includes references to:'); ?></p>
        <ul>
            <li><strong>Art &amp; Architecture Thesaurus (AAT)</strong> - Subject terms and object types</li>
            <li><strong>Thesaurus of Geographic Names (TGN)</strong> - Place references</li>
            <li><strong>Union List of Artist Names (ULAN)</strong> - Creator references</li>
        </ul>
        <p><?php echo __('These URIs are included as <code>owl:sameAs</code> properties when vocabulary IDs are stored in AtoM.'); ?></p>
    </div>

</div>

<style>
.cidoc-export-page .section-box {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.cidoc-export-page .section-box h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #3498db;
    color: #2c3e50;
}

.export-scope .scope-options {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.export-scope .scope-option {
    flex: 1;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.export-scope .scope-option h4 {
    margin-top: 0;
    color: #2c3e50;
}

.export-scope .scope-divider {
    display: flex;
    align-items: center;
    padding: 0 10px;
    color: #7f8c8d;
    font-style: italic;
}

.format-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.format-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.format-option:hover {
    border-color: #3498db;
}

.format-option.active {
    border-color: #3498db;
    background: #e8f4fd;
}

.format-option input {
    margin: 0;
}

.format-label {
    font-weight: 600;
}

.format-ext {
    margin-left: auto;
    color: #7f8c8d;
    font-family: monospace;
}

.code-preview {
    background: #2c3e50;
    color: #ecf0f1;
    padding: 15px;
    border-radius: 6px;
    max-height: 400px;
    overflow: auto;
    font-size: 12px;
}

.code-preview code {
    white-space: pre-wrap;
}

table code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function updateFormat(format) {
    document.querySelectorAll('.format-option').forEach(function(opt) {
        opt.classList.remove('active');
    });
    document.querySelector('.format-option input[value="' + format + '"]').closest('.format-option').classList.add('active');
    
    // Update hidden format fields in forms
    document.querySelectorAll('input[type="hidden"][name="format"]').forEach(function(input) {
        input.value = format;
    });
}
</script>

<?php end_slot(); ?>
