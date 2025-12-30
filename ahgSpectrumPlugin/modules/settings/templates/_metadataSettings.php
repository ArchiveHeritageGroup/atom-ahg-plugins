<?php
/**
 * Metadata Extraction Settings Section
 * 
 * Add this to the ahgSettingsSuccess.php template in the switch statement
 * as a new case for 'metadata' and 'faces' sections
 */
?>

<?php // Add these cases to the switch($currentSection) statement: ?>

<?php case 'metadata': ?>
    <!-- Metadata Extraction Settings -->
    <fieldset class="mb-4">
        <legend><?php echo __('Auto-Extraction Settings'); ?></legend>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <?php echo __('Configure automatic metadata extraction when files are uploaded. Extracted metadata will be mapped to AtoM description fields.'); ?>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __('Extract on Upload'); ?></label>
            <div class="col-sm-9">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="meta_extract_on_upload" name="settings[meta_extract_on_upload]" value="true" <?php echo ($settings['meta_extract_on_upload'] ?? true) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="meta_extract_on_upload"><?php echo __('Automatically extract metadata when files are uploaded'); ?></label>
                </div>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __('Auto-Populate Fields'); ?></label>
            <div class="col-sm-9">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="meta_auto_populate" name="settings[meta_auto_populate]" value="true" <?php echo ($settings['meta_auto_populate'] ?? true) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="meta_auto_populate"><?php echo __('Auto-populate AtoM description fields from extracted metadata'); ?></label>
                </div>
            </div>
        </div>
    </fieldset>
    
    <fieldset class="mb-4">
        <legend><?php echo __('File Type Support'); ?></legend>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="meta_extract_images" name="settings[meta_extract_images]" value="true" <?php echo ($settings['meta_extract_images'] ?? true) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="meta_extract_images">
                            <i class="fas fa-image text-success"></i> <?php echo __('Images (EXIF, IPTC, XMP)'); ?>
                        </label>
                    </div>
                    <small class="form-text text-muted ml-4"><?php echo __('JPEG, PNG, TIFF, WebP'); ?></small>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="meta_extract_pdf" name="settings[meta_extract_pdf]" value="true" <?php echo ($settings['meta_extract_pdf'] ?? true) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="meta_extract_pdf">
                            <i class="fas fa-file-pdf text-danger"></i> <?php echo __('PDF Documents'); ?>
                        </label>
                    </div>
                    <small class="form-text text-muted ml-4"><?php echo __('Title, Author, Keywords, Creator'); ?></small>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="meta_extract_office" name="settings[meta_extract_office]" value="true" <?php echo ($settings['meta_extract_office'] ?? true) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="meta_extract_office">
                            <i class="fas fa-file-word text-primary"></i> <?php echo __('Office Documents'); ?>
                        </label>
                    </div>
                    <small class="form-text text-muted ml-4"><?php echo __('DOCX, XLSX, PPTX - Title, Author, Keywords'); ?></small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="meta_extract_video" name="settings[meta_extract_video]" value="true" <?php echo ($settings['meta_extract_video'] ?? true) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="meta_extract_video">
                            <i class="fas fa-video text-info"></i> <?php echo __('Video Files'); ?>
                        </label>
                    </div>
                    <small class="form-text text-muted ml-4"><?php echo __('Duration, Codec, Resolution, Framerate'); ?></small>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="meta_extract_audio" name="settings[meta_extract_audio]" value="true" <?php echo ($settings['meta_extract_audio'] ?? true) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="meta_extract_audio">
                            <i class="fas fa-music text-warning"></i> <?php echo __('Audio Files'); ?>
                        </label>
                    </div>
                    <small class="form-text text-muted ml-4"><?php echo __('ID3 Tags, Duration, Bitrate, Sample Rate'); ?></small>
                </div>
            </div>
        </div>
    </fieldset>
    
    <fieldset class="mb-4">
        <legend><?php echo __('Field Mapping'); ?></legend>
        
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th><?php echo __('Metadata Source'); ?></th>
                        <th><?php echo __('AtoM Field'); ?></th>
                        <th><?php echo __('Notes'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Title (XMP/IPTC/PDF/Office)</td>
                        <td>Title</td>
                        <td>Only if title is empty</td>
                    </tr>
                    <tr>
                        <td>Creator/Author/Artist</td>
                        <td>Name Access Points</td>
                        <td>Creates actor if not exists</td>
                    </tr>
                    <tr>
                        <td>Keywords/Subjects</td>
                        <td>Subject Access Points</td>
                        <td>Creates terms if not exist</td>
                    </tr>
                    <tr>
                        <td>Description/Caption</td>
                        <td>Scope and Content</td>
                        <td>Appended to existing</td>
                    </tr>
                    <tr>
                        <td>Date Created/Taken</td>
                        <td>Creation Event Date</td>
                        <td>Only if no date exists</td>
                    </tr>
                    <tr>
                        <td>Copyright Notice</td>
                        <td>Access Conditions</td>
                        <td>Appended to existing</td>
                    </tr>
                    <tr>
                        <td>Technical Metadata</td>
                        <td>Physical Characteristics</td>
                        <td>Full technical summary</td>
                    </tr>
                    <tr>
                        <td>GPS Coordinates</td>
                        <td>Digital Object Properties</td>
                        <td>Latitude/Longitude</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </fieldset>
<?php break; ?>

<?php case 'faces': ?>
    <!-- Face Detection Settings -->
    <fieldset class="mb-4">
        <legend><?php echo __('Face Detection'); ?></legend>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo __('Face detection is an experimental feature. It requires additional server resources and may not be 100% accurate.'); ?>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __('Enable Face Detection'); ?></label>
            <div class="col-sm-9">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="face_detect_enabled" name="settings[face_detect_enabled]" value="true" <?php echo ($settings['face_detect_enabled'] ?? false) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="face_detect_enabled"><?php echo __('Detect faces in uploaded images'); ?></label>
                </div>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="face_detect_backend"><?php echo __('Detection Backend'); ?></label>
            <div class="col-sm-9">
                <select class="form-control" id="face_detect_backend" name="settings[face_detect_backend]">
                    <option value="local" <?php echo ($settings['face_detect_backend'] ?? 'local') === 'local' ? 'selected' : ''; ?>><?php echo __('Local (OpenCV/Python)'); ?></option>
                    <option value="aws_rekognition" <?php echo ($settings['face_detect_backend'] ?? 'local') === 'aws_rekognition' ? 'selected' : ''; ?>><?php echo __('AWS Rekognition'); ?></option>
                    <option value="azure" <?php echo ($settings['face_detect_backend'] ?? 'local') === 'azure' ? 'selected' : ''; ?>><?php echo __('Azure Face API'); ?></option>
                    <option value="google" <?php echo ($settings['face_detect_backend'] ?? 'local') === 'google' ? 'selected' : ''; ?>><?php echo __('Google Cloud Vision'); ?></option>
                </select>
                <small class="form-text text-muted"><?php echo __('Local requires Python and OpenCV installed. Cloud services require API credentials.'); ?></small>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __('Auto-Match Authorities'); ?></label>
            <div class="col-sm-9">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="face_auto_match" name="settings[face_auto_match]" value="true" <?php echo ($settings['face_auto_match'] ?? true) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="face_auto_match"><?php echo __('Automatically match detected faces to indexed authority records'); ?></label>
                </div>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __('Auto-Link Matches'); ?></label>
            <div class="col-sm-9">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="face_auto_link" name="settings[face_auto_link]" value="true" <?php echo ($settings['face_auto_link'] ?? false) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="face_auto_link"><?php echo __('Automatically create name access points for high-confidence matches'); ?></label>
                </div>
                <small class="form-text text-muted"><?php echo __('Only matches above the confidence threshold will be auto-linked'); ?></small>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="face_confidence_threshold"><?php echo __('Confidence Threshold'); ?></label>
            <div class="col-sm-9">
                <input type="range" class="form-control-range" id="face_confidence_threshold" name="settings[face_confidence_threshold]" min="0.5" max="0.99" step="0.01" value="<?php echo $settings['face_confidence_threshold'] ?? 0.8; ?>">
                <small class="form-text text-muted"><?php echo __('Minimum confidence (0.5-0.99) for auto-linking. Higher = fewer false positives.'); ?></small>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __('Save Face Crops'); ?></label>
            <div class="col-sm-9">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="face_save_crops" name="settings[face_save_crops]" value="true" <?php echo ($settings['face_save_crops'] ?? true) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="face_save_crops"><?php echo __('Save cropped face images for indexing and matching'); ?></label>
                </div>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="face_max_per_image"><?php echo __('Max Faces per Image'); ?></label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="face_max_per_image" name="settings[face_max_per_image]" value="<?php echo $settings['face_max_per_image'] ?? 20; ?>" min="1" max="100">
            </div>
        </div>
    </fieldset>
    
    <fieldset class="mb-4">
        <legend><?php echo __('AWS Rekognition Settings'); ?></legend>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="aws_rekognition_region"><?php echo __('AWS Region'); ?></label>
            <div class="col-sm-9">
                <select class="form-control" id="aws_rekognition_region" name="settings[aws_rekognition_region]">
                    <option value="us-east-1" <?php echo ($settings['aws_rekognition_region'] ?? 'us-east-1') === 'us-east-1' ? 'selected' : ''; ?>>US East (N. Virginia)</option>
                    <option value="us-west-2" <?php echo ($settings['aws_rekognition_region'] ?? '') === 'us-west-2' ? 'selected' : ''; ?>>US West (Oregon)</option>
                    <option value="eu-west-1" <?php echo ($settings['aws_rekognition_region'] ?? '') === 'eu-west-1' ? 'selected' : ''; ?>>EU (Ireland)</option>
                    <option value="eu-central-1" <?php echo ($settings['aws_rekognition_region'] ?? '') === 'eu-central-1' ? 'selected' : ''; ?>>EU (Frankfurt)</option>
                    <option value="ap-southeast-2" <?php echo ($settings['aws_rekognition_region'] ?? '') === 'ap-southeast-2' ? 'selected' : ''; ?>>Asia Pacific (Sydney)</option>
                </select>
                <small class="form-text text-muted"><?php echo __('AWS credentials must be configured via environment variables or IAM role'); ?></small>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="aws_rekognition_collection"><?php echo __('Collection ID'); ?></label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="aws_rekognition_collection" name="settings[aws_rekognition_collection]" value="<?php echo htmlspecialchars($settings['aws_rekognition_collection'] ?? 'atom_faces'); ?>">
                <small class="form-text text-muted"><?php echo __('Face collection for indexing and searching'); ?></small>
            </div>
        </div>
    </fieldset>
    
    <fieldset class="mb-4">
        <legend><?php echo __('Azure Face API Settings'); ?></legend>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="azure_face_endpoint"><?php echo __('API Endpoint'); ?></label>
            <div class="col-sm-9">
                <input type="url" class="form-control" id="azure_face_endpoint" name="settings[azure_face_endpoint]" value="<?php echo htmlspecialchars($settings['azure_face_endpoint'] ?? ''); ?>" placeholder="https://your-resource.cognitiveservices.azure.com">
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="azure_face_key"><?php echo __('API Key'); ?></label>
            <div class="col-sm-9">
                <input type="password" class="form-control" id="azure_face_key" name="settings[azure_face_key]" value="<?php echo htmlspecialchars($settings['azure_face_key'] ?? ''); ?>">
            </div>
        </div>
    </fieldset>
    
    <fieldset class="mb-4">
        <legend><?php echo __('Face Index Management'); ?></legend>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Indexed Faces'); ?></h5>
                        <p class="card-text display-4" id="indexed-face-count">--</p>
                        <a href="<?php echo url_for(['module' => 'actor', 'action' => 'browse']); ?>" class="btn btn-outline-primary btn-sm">
                            <?php echo __('Manage Authority Records'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Detected Faces'); ?></h5>
                        <p class="card-text display-4" id="detected-face-count">--</p>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="runBulkFaceMatch()">
                            <i class="fas fa-sync"></i> <?php echo __('Run Bulk Matching'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
<?php break; ?>

<?php 
// Don't forget to add 'metadata' and 'faces' to the $sections array in ahgSettingsAction.class.php:
/*
'metadata' => [
    'label' => 'Metadata Extraction',
    'icon' => 'fa-tags',
    'description' => 'Auto-extract metadata from uploaded files'
],
'faces' => [
    'label' => 'Face Detection',
    'icon' => 'fa-user-circle',
    'description' => 'Detect and match faces to authority records'
],
*/
?>
