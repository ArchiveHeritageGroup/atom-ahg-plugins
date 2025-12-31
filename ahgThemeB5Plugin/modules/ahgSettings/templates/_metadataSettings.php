<?php
/**
 * Metadata Extraction Settings
 * AHG Settings Dashboard - Metadata Section
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

// Load current settings
$settings = [];
$rows = DB::table('ahg_settings')
    ->where('setting_key', 'like', 'meta_%')
    ->get();
foreach ($rows as $row) {
    $settings[$row->setting_key] = $row->setting_value;
}

// Default mappings for each standard
$defaultMappings = [
    'title' => ['isad' => 'title', 'spectrum' => 'title', 'dam' => 'title'],
    'creator' => ['isad' => 'name_access_points', 'spectrum' => 'production_person', 'dam' => 'creator'],
    'keywords' => ['isad' => 'subject_access_points', 'spectrum' => 'object_category', 'dam' => 'keywords'],
    'description' => ['isad' => 'scope_and_content', 'spectrum' => 'brief_description', 'dam' => 'caption'],
    'date_created' => ['isad' => 'creation_event_date', 'spectrum' => 'production_date', 'dam' => 'date_created'],
    'copyright' => ['isad' => 'access_conditions', 'spectrum' => 'rights_notes', 'dam' => 'copyright_notice'],
    'location' => ['isad' => 'place_access_points', 'spectrum' => 'field_collection_place', 'dam' => 'location'],
    'dimensions' => ['isad' => 'physical_characteristics', 'spectrum' => 'dimension_measurements', 'dam' => 'dimensions'],
    'file_format' => ['isad' => 'physical_characteristics', 'spectrum' => 'technical_description', 'dam' => 'file_format'],
    'camera_model' => ['isad' => 'archivists_notes', 'spectrum' => 'technical_description', 'dam' => 'camera_info'],
    'gps_coordinates' => ['isad' => 'place_access_points', 'spectrum' => 'field_collection_place', 'dam' => 'gps_location']
];

// Get saved mappings or use defaults
$savedMappings = $settings['meta_field_mappings'] ?? null;
$fieldMappings = $savedMappings ? (json_decode($savedMappings, true) ?: $defaultMappings) : $defaultMappings;

// Available target fields for each standard
$isadFields = [
    '' => '-- Do not map --',
    'title' => 'Title',
    'scope_and_content' => 'Scope and Content',
    'name_access_points' => 'Name Access Points',
    'subject_access_points' => 'Subject Access Points',
    'place_access_points' => 'Place Access Points',
    'access_conditions' => 'Access Conditions',
    'reproduction_conditions' => 'Reproduction Conditions',
    'physical_characteristics' => 'Physical Characteristics',
    'archivists_notes' => "Archivist's Notes",
    'creation_event_date' => 'Creation Event Date',
    'general_note' => 'General Note',
    'arrangement' => 'Arrangement',
    'appraisal' => 'Appraisal',
    'accruals' => 'Accruals',
    'finding_aids' => 'Finding Aids',
    'location_of_originals' => 'Location of Originals',
    'location_of_copies' => 'Location of Copies',
    'related_units' => 'Related Units of Description',
    'publication_note' => 'Publication Note',
    'revision_history' => 'Revision History'
];

$spectrumFields = [
    '' => '-- Do not map --',
    'title' => 'Title',
    'brief_description' => 'Brief Description',
    'production_person' => 'Production Person',
    'production_date' => 'Production Date',
    'object_category' => 'Object Category',
    'object_name' => 'Object Name',
    'object_number' => 'Object Number',
    'material' => 'Material',
    'technique' => 'Technique',
    'dimension_measurements' => 'Dimension Measurements',
    'dimension_unit' => 'Dimension Unit',
    'inscription_description' => 'Inscription Description',
    'condition_description' => 'Condition Description',
    'technical_description' => 'Technical Description',
    'field_collection_place' => 'Field Collection Place',
    'field_collection_date' => 'Field Collection Date',
    'rights_notes' => 'Rights Notes',
    'usage_notes' => 'Usage Notes',
    'content_description' => 'Content Description',
    'cultural_affinity' => 'Cultural Affinity'
];

$damFields = [
    '' => '-- Do not map --',
    'title' => 'Title / Filename',
    'caption' => 'Caption / Description',
    'creator' => 'Creator / Photographer',
    'keywords' => 'Keywords / Tags',
    'date_created' => 'Date Created / Taken',
    'copyright_notice' => 'Copyright Notice',
    'credit_line' => 'Credit Line',
    'location' => 'Location / Place',
    'gps_location' => 'GPS Coordinates',
    'dimensions' => 'Image Dimensions',
    'file_format' => 'File Format / Type',
    'camera_info' => 'Camera / Equipment Info',
    'color_space' => 'Color Space / Profile',
    'resolution' => 'Resolution (DPI)',
    'file_size' => 'File Size',
    'duration' => 'Duration (Audio/Video)',
    'aspect_ratio' => 'Aspect Ratio',
    'codec' => 'Codec Information',
    'usage_rights' => 'Usage Rights',
    'collection' => 'Collection / Project',
    'job_identifier' => 'Job / Assignment ID',
    'source' => 'Source',
    'instructions' => 'Special Instructions',
    'urgency' => 'Priority / Urgency'
];

// Metadata source fields (what we extract)
$metadataSources = [
    'title' => ['icon' => 'bi-fonts', 'label' => 'Title'],
    'creator' => ['icon' => 'bi-person', 'label' => 'Creator/Author'],
    'keywords' => ['icon' => 'bi-tags', 'label' => 'Keywords'],
    'description' => ['icon' => 'bi-text-paragraph', 'label' => 'Description'],
    'date_created' => ['icon' => 'bi-calendar', 'label' => 'Date Created'],
    'copyright' => ['icon' => 'bi-c-circle', 'label' => 'Copyright'],
    'location' => ['icon' => 'bi-geo-alt', 'label' => 'Location'],
    'dimensions' => ['icon' => 'bi-rulers', 'label' => 'Dimensions'],
    'file_format' => ['icon' => 'bi-file-earmark', 'label' => 'File Format'],
    'camera_model' => ['icon' => 'bi-camera', 'label' => 'Camera/Equipment'],
    'gps_coordinates' => ['icon' => 'bi-pin-map', 'label' => 'GPS Coordinates']
];
?>

<!-- Metadata Extraction -->
<h5 class="mb-3">Metadata Extraction</h5>

<div class="alert alert-info d-flex align-items-center mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <span>Configure automatic metadata extraction.</span>
</div>

<!-- Extract on Upload -->
<div class="row mb-4">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Extract on Upload</label>
    </div>
    <div class="col-md-8">
        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" id="meta_extract_on_upload" 
                   name="settings[meta_extract_on_upload]" value="true" 
                   <?php echo ($settings['meta_extract_on_upload'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="meta_extract_on_upload">Auto-extract metadata</label>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="meta_auto_populate" 
                   name="settings[meta_auto_populate]" value="true" 
                   <?php echo ($settings['meta_auto_populate'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="meta_auto_populate">Populate description fields</label>
        </div>
    </div>
</div>

<!-- File Types -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">File Types</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_images" 
                           name="settings[meta_images]" value="true" 
                           <?php echo ($settings['meta_images'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_images">
                        <i class="bi bi-image text-success me-1"></i> Images
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_pdf" 
                           name="settings[meta_pdf]" value="true" 
                           <?php echo ($settings['meta_pdf'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_pdf">
                        <i class="bi bi-file-pdf text-danger me-1"></i> PDF
                    </label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="meta_office" 
                           name="settings[meta_office]" value="true" 
                           <?php echo ($settings['meta_office'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_office">
                        <i class="bi bi-file-earmark-word text-primary me-1"></i> Office
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_video" 
                           name="settings[meta_video]" value="true" 
                           <?php echo ($settings['meta_video'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_video">
                        <i class="bi bi-camera-video text-info me-1"></i> Video
                    </label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="meta_audio" 
                           name="settings[meta_audio]" value="true" 
                           <?php echo ($settings['meta_audio'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_audio">
                        <i class="bi bi-music-note-beamed text-warning me-1"></i> Audio
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Field Mapping -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Field Mapping</h6>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="resetFieldMappings">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Defaults
        </button>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">Configure where extracted metadata is saved:</p>
        
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%;">Metadata Source</th>
                        <th style="width: 26%;">Archives (ISAD)</th>
                        <th style="width: 27%;">Museum (Spectrum)</th>
                        <th style="width: 27%;">DAM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metadataSources as $sourceKey => $sourceInfo): ?>
                    <tr>
                        <td>
                            <i class="<?php echo $sourceInfo['icon']; ?> me-2 text-muted"></i>
                            <?php echo $sourceInfo['label']; ?>
                        </td>
                        <td>
                            <select class="form-select form-select-sm field-mapping" 
                                    name="settings[meta_mapping_<?php echo $sourceKey; ?>_isad]"
                                    data-source="<?php echo $sourceKey; ?>" data-standard="isad">
                                <?php foreach ($isadFields as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo ($fieldMappings[$sourceKey]['isad'] ?? '') === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm field-mapping" 
                                    name="settings[meta_mapping_<?php echo $sourceKey; ?>_spectrum]"
                                    data-source="<?php echo $sourceKey; ?>" data-standard="spectrum">
                                <?php foreach ($spectrumFields as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo ($fieldMappings[$sourceKey]['spectrum'] ?? '') === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm field-mapping" 
                                    name="settings[meta_mapping_<?php echo $sourceKey; ?>_dam]"
                                    data-source="<?php echo $sourceKey; ?>" data-standard="dam">
                                <?php foreach ($damFields as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo ($fieldMappings[$sourceKey]['dam'] ?? '') === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Hidden field to store combined mappings as JSON -->
        <input type="hidden" name="settings[meta_field_mappings]" id="meta_field_mappings" 
               value="<?php echo htmlspecialchars(json_encode($fieldMappings)); ?>">
    </div>
</div>

<!-- Advanced Extraction Options -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Advanced Options</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_extract_gps" 
                           name="settings[meta_extract_gps]" value="true" 
                           <?php echo ($settings['meta_extract_gps'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_extract_gps">
                        <i class="bi bi-geo-alt me-1"></i> Extract GPS coordinates
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_extract_technical" 
                           name="settings[meta_extract_technical]" value="true" 
                           <?php echo ($settings['meta_extract_technical'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_extract_technical">
                        <i class="bi bi-gear me-1"></i> Extract technical metadata (EXIF, etc.)
                    </label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="meta_extract_xmp" 
                           name="settings[meta_extract_xmp]" value="true" 
                           <?php echo ($settings['meta_extract_xmp'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_extract_xmp">
                        <i class="bi bi-file-code me-1"></i> Extract XMP metadata
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_extract_iptc" 
                           name="settings[meta_extract_iptc]" value="true" 
                           <?php echo ($settings['meta_extract_iptc'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_extract_iptc">
                        <i class="bi bi-newspaper me-1"></i> Extract IPTC metadata
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_overwrite_existing" 
                           name="settings[meta_overwrite_existing]" value="true" 
                           <?php echo ($settings['meta_overwrite_existing'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_overwrite_existing">
                        <i class="bi bi-pencil-square me-1"></i> Overwrite existing values
                    </label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="meta_create_access_points" 
                           name="settings[meta_create_access_points]" value="true" 
                           <?php echo ($settings['meta_create_access_points'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_create_access_points">
                        <i class="bi bi-diagram-3 me-1"></i> Auto-create access points from keywords
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DAM-Specific Options -->
<div class="card mb-4">
    <div class="card-header bg-info bg-opacity-10">
        <h6 class="mb-0"><i class="bi bi-images me-2"></i>DAM-Specific Options</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_dam_batch_mode" 
                           name="settings[meta_dam_batch_mode]" value="true" 
                           <?php echo ($settings['meta_dam_batch_mode'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_dam_batch_mode">
                        <i class="bi bi-collection me-1"></i> Enable batch processing mode
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_dam_preserve_filename" 
                           name="settings[meta_dam_preserve_filename]" value="true" 
                           <?php echo ($settings['meta_dam_preserve_filename'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_dam_preserve_filename">
                        <i class="bi bi-file-earmark-text me-1"></i> Preserve original filename as title
                    </label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="meta_dam_extract_color" 
                           name="settings[meta_dam_extract_color]" value="true" 
                           <?php echo ($settings['meta_dam_extract_color'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_dam_extract_color">
                        <i class="bi bi-palette me-1"></i> Extract dominant colors
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_dam_extract_faces" 
                           name="settings[meta_dam_extract_faces]" value="true" 
                           <?php echo ($settings['meta_dam_extract_faces'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_dam_extract_faces">
                        <i class="bi bi-people me-1"></i> Detect faces (requires AI service)
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="meta_dam_auto_tag" 
                           name="settings[meta_dam_auto_tag]" value="true" 
                           <?php echo ($settings['meta_dam_auto_tag'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_dam_auto_tag">
                        <i class="bi bi-robot me-1"></i> AI auto-tagging (requires AI service)
                    </label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="meta_dam_generate_thumbnail" 
                           name="settings[meta_dam_generate_thumbnail]" value="true" 
                           <?php echo ($settings['meta_dam_generate_thumbnail'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="meta_dam_generate_thumbnail">
                        <i class="bi bi-aspect-ratio me-1"></i> Generate DAM thumbnails (multiple sizes)
                    </label>
                </div>
            </div>
        </div>
        
        <!-- DAM Thumbnail Sizes -->
        <div class="mt-3">
            <label class="form-label fw-semibold">Thumbnail Sizes (DAM)</label>
            <div class="row g-2">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Small</span>
                        <input type="number" class="form-control" name="settings[meta_dam_thumb_small]" 
                               value="<?php echo $settings['meta_dam_thumb_small'] ?? '150'; ?>" placeholder="150">
                        <span class="input-group-text">px</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Medium</span>
                        <input type="number" class="form-control" name="settings[meta_dam_thumb_medium]" 
                               value="<?php echo $settings['meta_dam_thumb_medium'] ?? '300'; ?>" placeholder="300">
                        <span class="input-group-text">px</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Large</span>
                        <input type="number" class="form-control" name="settings[meta_dam_thumb_large]" 
                               value="<?php echo $settings['meta_dam_thumb_large'] ?? '600'; ?>" placeholder="600">
                        <span class="input-group-text">px</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Preview</span>
                        <input type="number" class="form-control" name="settings[meta_dam_thumb_preview]" 
                               value="<?php echo $settings['meta_dam_thumb_preview'] ?? '1200'; ?>" placeholder="1200">
                        <span class="input-group-text">px</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Update hidden JSON field when dropdowns change
    const fieldMappingSelects = document.querySelectorAll('.field-mapping');
    const hiddenField = document.getElementById('meta_field_mappings');
    
    function updateMappingsJson() {
        const mappings = {};
        fieldMappingSelects.forEach(function(select) {
            const source = select.dataset.source;
            const standard = select.dataset.standard;
            if (!mappings[source]) {
                mappings[source] = {};
            }
            mappings[source][standard] = select.value;
        });
        hiddenField.value = JSON.stringify(mappings);
    }
    
    fieldMappingSelects.forEach(function(select) {
        select.addEventListener('change', updateMappingsJson);
    });
    
    // Reset to defaults button
    document.getElementById('resetFieldMappings').addEventListener('click', function() {
        if (confirm('Reset all field mappings to default values?')) {
            const defaults = {
                'title': {'isad': 'title', 'spectrum': 'title', 'dam': 'title'},
                'creator': {'isad': 'name_access_points', 'spectrum': 'production_person', 'dam': 'creator'},
                'keywords': {'isad': 'subject_access_points', 'spectrum': 'object_category', 'dam': 'keywords'},
                'description': {'isad': 'scope_and_content', 'spectrum': 'brief_description', 'dam': 'caption'},
                'date_created': {'isad': 'creation_event_date', 'spectrum': 'production_date', 'dam': 'date_created'},
                'copyright': {'isad': 'access_conditions', 'spectrum': 'rights_notes', 'dam': 'copyright_notice'},
                'location': {'isad': 'place_access_points', 'spectrum': 'field_collection_place', 'dam': 'location'},
                'dimensions': {'isad': 'physical_characteristics', 'spectrum': 'dimension_measurements', 'dam': 'dimensions'},
                'file_format': {'isad': 'physical_characteristics', 'spectrum': 'technical_description', 'dam': 'file_format'},
                'camera_model': {'isad': 'archivists_notes', 'spectrum': 'technical_description', 'dam': 'camera_info'},
                'gps_coordinates': {'isad': 'place_access_points', 'spectrum': 'field_collection_place', 'dam': 'gps_location'}
            };
            
            fieldMappingSelects.forEach(function(select) {
                const source = select.dataset.source;
                const standard = select.dataset.standard;
                if (defaults[source] && defaults[source][standard]) {
                    select.value = defaults[source][standard];
                }
            });
            
            updateMappingsJson();
        }
    });
});
</script>
