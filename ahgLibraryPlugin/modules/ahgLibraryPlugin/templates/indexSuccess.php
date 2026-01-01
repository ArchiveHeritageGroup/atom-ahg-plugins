<?php decorate_with('layout_1col'); ?>
<?php
// Check if a plugin is enabled
if (!function_exists('checkPluginEnabled')) {
    function checkPluginEnabled($pluginName) {
        static $plugins = null;
        if ($plugins === null) {
            try {
                $conn = Propel::getConnection();
                $stmt = $conn->prepare('SELECT name FROM atom_plugin WHERE is_enabled = 1');
                $stmt->execute();
                $plugins = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
            } catch (Exception $e) {
                $plugins = [];
            }
        }
        return isset($plugins[$pluginName]);
    }
}
?>

<?php slot('title'); ?>
  <h1><?php echo esc_entities($resource->getTitle(['cultureFallback' => true])); ?></h1>
<?php end_slot(); ?>

<div class="row">
  <div class="col-md-8">

    <!-- Basic Information -->
    <section class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-book me-2"></i><?php echo __('Basic Information'); ?></h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4"><?php echo __('Title'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($resource->getTitle(['cultureFallback' => true])); ?></dd>

          <?php if (!empty($libraryData['subtitle'])): ?>
            <dt class="col-sm-4"><?php echo __('Subtitle'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['subtitle']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['responsibility_statement'])): ?>
            <dt class="col-sm-4"><?php echo __('Statement of responsibility'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['responsibility_statement']); ?></dd>
          <?php endif; ?>

          <?php if ($resource->identifier): ?>
            <dt class="col-sm-4"><?php echo __('Identifier'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($resource->identifier); ?></dd>
          <?php endif; ?>

          <?php if ($resource->levelOfDescription): ?>
            <dt class="col-sm-4"><?php echo __('Level of description'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($resource->levelOfDescription->getName(['cultureFallback' => true])); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['material_type'])): ?>
            <dt class="col-sm-4"><?php echo __('Material type'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities(ucfirst($libraryData['material_type'])); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['language'])): ?>
            <dt class="col-sm-4"><?php echo __('Language'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['language']); ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </section>

    <!-- Creators -->
    <?php if (!empty($libraryData['creators'])): ?>
    <section class="card mb-4">
      <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i><?php echo __('Creators / Authors'); ?></h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <?php foreach ($libraryData['creators'] as $creator): ?>
            <li class="mb-2">
              <strong><?php echo esc_entities($creator['name']); ?></strong>
              <span class="badge bg-secondary ms-2"><?php echo esc_entities(ucfirst($creator['role'])); ?></span>
              <?php if (!empty($creator['authority_uri'])): ?>
                <a href="<?php echo esc_entities($creator['authority_uri']); ?>" target="_blank" class="ms-2" title="<?php echo __('View authority record'); ?>">
                  <i class="fas fa-external-link-alt"></i>
                </a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
    <?php endif; ?>

    <!-- Standard Identifiers -->
    <?php if (!empty($libraryData['isbn']) || !empty($libraryData['issn']) || !empty($libraryData['doi']) || !empty($libraryData['lccn']) || !empty($libraryData['oclc_number'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-barcode me-2"></i><?php echo __('Standard Identifiers'); ?></h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <?php if (!empty($libraryData['isbn'])): ?>
            <dt class="col-sm-4"><?php echo __('ISBN'); ?></dt>
            <dd class="col-sm-8"><code><?php echo esc_entities($libraryData['isbn']); ?></code></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['issn'])): ?>
            <dt class="col-sm-4"><?php echo __('ISSN'); ?></dt>
            <dd class="col-sm-8"><code><?php echo esc_entities($libraryData['issn']); ?></code></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['doi'])): ?>
            <dt class="col-sm-4"><?php echo __('DOI'); ?></dt>
            <dd class="col-sm-8">
              <a href="https://doi.org/<?php echo esc_entities($libraryData['doi']); ?>" target="_blank">
                <?php echo esc_entities($libraryData['doi']); ?>
              </a>
            </dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['lccn'])): ?>
            <dt class="col-sm-4"><?php echo __('LCCN'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['lccn']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['oclc_number'])): ?>
            <dt class="col-sm-4"><?php echo __('OCLC'); ?></dt>
            <dd class="col-sm-8">
              <a href="https://www.worldcat.org/oclc/<?php echo esc_entities($libraryData['oclc_number']); ?>" target="_blank">
                <?php echo esc_entities($libraryData['oclc_number']); ?>
              </a>
            </dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['barcode'])): ?>
            <dt class="col-sm-4"><?php echo __('Barcode'); ?></dt>
            <dd class="col-sm-8"><code><?php echo esc_entities($libraryData['barcode']); ?></code></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['openlibrary_id'])): ?>
            <dt class="col-sm-4"><?php echo __('Open Library'); ?></dt>
            <dd class="col-sm-8">
              <a href="https://openlibrary.org/books/<?php echo esc_entities($libraryData['openlibrary_id']); ?>" target="_blank">
                <?php echo esc_entities($libraryData['openlibrary_id']); ?>
              </a>
            </dd>
          <?php endif; ?>
        </dl>
      </div>
    </section>
    <?php endif; ?>

    <!-- Classification -->
    <?php if (!empty($libraryData['call_number']) || !empty($libraryData['dewey_decimal']) || !empty($libraryData['shelf_location'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i><?php echo __('Classification'); ?></h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <?php if (!empty($libraryData['call_number'])): ?>
            <dt class="col-sm-4"><?php echo __('Call number'); ?></dt>
            <dd class="col-sm-8"><code><?php echo esc_entities($libraryData['call_number']); ?></code></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['dewey_decimal'])): ?>
            <dt class="col-sm-4"><?php echo __('Dewey Decimal'); ?></dt>
            <dd class="col-sm-8"><code><?php echo esc_entities($libraryData['dewey_decimal']); ?></code></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['classification_scheme'])): ?>
            <dt class="col-sm-4"><?php echo __('Classification scheme'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities(strtoupper($libraryData['classification_scheme'])); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['shelf_location'])): ?>
            <dt class="col-sm-4"><?php echo __('Shelf location'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['shelf_location']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['copy_number'])): ?>
            <dt class="col-sm-4"><?php echo __('Copy'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['copy_number']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['volume_designation'])): ?>
            <dt class="col-sm-4"><?php echo __('Volume'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['volume_designation']); ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </section>
    <?php endif; ?>

    <!-- Publication Information -->
    <?php if (!empty($libraryData['publisher']) || !empty($libraryData['publication_date'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-building me-2"></i><?php echo __('Publication Information'); ?></h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <?php if (!empty($libraryData['publisher'])): ?>
            <dt class="col-sm-4"><?php echo __('Publisher'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['publisher']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['publication_place'])): ?>
            <dt class="col-sm-4"><?php echo __('Place'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['publication_place']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['publication_date'])): ?>
            <dt class="col-sm-4"><?php echo __('Date'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['publication_date']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['edition'])): ?>
            <dt class="col-sm-4"><?php echo __('Edition'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['edition']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['edition_statement'])): ?>
            <dt class="col-sm-4"><?php echo __('Edition statement'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['edition_statement']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['series_title'])): ?>
            <dt class="col-sm-4"><?php echo __('Series'); ?></dt>
            <dd class="col-sm-8">
              <?php echo esc_entities($libraryData['series_title']); ?>
              <?php if (!empty($libraryData['series_number'])): ?>
                <span class="text-muted">(<?php echo esc_entities($libraryData['series_number']); ?>)</span>
              <?php endif; ?>
            </dd>
          <?php endif; ?>
        </dl>
      </div>
    </section>
    <?php endif; ?>

    <!-- Physical Description -->
    <?php if (!empty($libraryData['pagination']) || !empty($libraryData['dimensions'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-ruler me-2"></i><?php echo __('Physical Description'); ?></h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <?php if (!empty($libraryData['pagination'])): ?>
            <dt class="col-sm-4"><?php echo __('Extent'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['pagination']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['dimensions'])): ?>
            <dt class="col-sm-4"><?php echo __('Dimensions'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['dimensions']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($libraryData['physical_details'])): ?>
            <dt class="col-sm-4"><?php echo __('Physical details'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_entities($libraryData['physical_details']); ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </section>
    <?php endif; ?>

    <!-- Subjects -->
    <?php if (!empty($libraryData['subjects'])): ?>
    <section class="card mb-4">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-tags me-2"></i><?php echo __('Subjects'); ?></h5>
      </div>
      <div class="card-body">
        <?php foreach ($libraryData['subjects'] as $subject): ?>
          <?php if (!empty($subject['uri'])): ?>
            <a href="<?php echo esc_entities($subject['uri']); ?>" target="_blank" class="badge bg-secondary text-decoration-none me-1 mb-1">
              <?php echo esc_entities($subject['heading']); ?>
            </a>
          <?php else: ?>
            <span class="badge bg-secondary me-1 mb-1"><?php echo esc_entities($subject['heading']); ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Content -->
    <?php 
      $summary = $libraryData['summary'] ?? '';
      $scopeAndContent = $resource->getScopeAndContent(['cultureFallback' => true]);
      $contentsNote = $libraryData['contents_note'] ?? '';
    ?>
    <?php if (!empty($summary) || !empty($scopeAndContent) || !empty($contentsNote)): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-align-left me-2"></i><?php echo __('Content'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($summary)): ?>
          <h6><?php echo __('Summary'); ?></h6>
          <p><?php echo nl2br(esc_entities($summary)); ?></p>
        <?php endif; ?>

        <?php if (!empty($scopeAndContent)): ?>
          <h6><?php echo __('Scope and content'); ?></h6>
          <p><?php echo nl2br(esc_entities($scopeAndContent)); ?></p>
        <?php endif; ?>

        <?php if (!empty($contentsNote)): ?>
          <h6><?php echo __('Table of contents'); ?></h6>
          <p><?php echo nl2br(esc_entities($contentsNote)); ?></p>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Notes -->
    <?php if (!empty($libraryData['general_note']) || !empty($libraryData['bibliography_note'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i><?php echo __('Notes'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($libraryData['general_note'])): ?>
          <p><?php echo nl2br(esc_entities($libraryData['general_note'])); ?></p>
        <?php endif; ?>

        <?php if (!empty($libraryData['bibliography_note'])): ?>
          <p class="text-muted"><em><?php echo nl2br(esc_entities($libraryData['bibliography_note'])); ?></em></p>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

  </div>

  <div class="col-md-4">
    <!-- Cover Image -->
    <?php if (isset($digitalObject) && $digitalObject): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-image me-2"></i><?php echo __('Cover'); ?></h5>
      </div>
      <div class="card-body text-center">
        <?php
          $mimeType = $digitalObject->mimeType ?? '';
          $thumbObj = $digitalObject->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);
          $refObj = $digitalObject->getRepresentationByUsage(QubitTerm::REFERENCE_ID);
          $thumbPath = $thumbObj ? $thumbObj->getFullPath() : null;
          $refPath = $refObj ? $refObj->getFullPath() : null;
          $masterPath = $digitalObject->getFullPath();
          $displayPath = $refPath ?: $thumbPath ?: $masterPath;
          $thumbObj = $digitalObject->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);
          $refObj = $digitalObject->getRepresentationByUsage(QubitTerm::REFERENCE_ID);
          $thumbPath = $thumbObj ? $thumbObj->getFullPath() : null;
          $refPath = $refObj ? $refObj->getFullPath() : null;
          $masterPath = $digitalObject->getFullPath();
          $displayPath = $refPath ?: $thumbPath ?: $masterPath;
          $thumbObj = $digitalObject->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);
          $refObj = $digitalObject->getRepresentationByUsage(QubitTerm::REFERENCE_ID);
          $thumbPath = $thumbObj ? $thumbObj->getFullPath() : null;
          $refPath = $refObj ? $refObj->getFullPath() : null;
          $masterPath = $digitalObject->getFullPath();
          $displayPath = $refPath ?: $thumbPath ?: $masterPath;
          $displayPath = $refPath ?: $thumbPath ?: $masterPath;
        ?>
        <?php if (strpos($mimeType, 'image') !== false && $displayPath): ?>
          <a href="<?php echo $masterPath; ?>" target="_blank">
            <img src="<?php echo $displayPath; ?>" alt="Cover" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
          </a>
        <?php else: ?>
          <a href="<?php echo $masterPath; ?>" target="_blank" class="btn btn-outline-primary">
            <i class="fas fa-file me-2"></i><?php echo __('View file'); ?>
          </a>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Actions -->
    <section class="card mb-4">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Actions'); ?></h5>
      </div>
      <div class="card-body">
        <a href="<?php echo url_for(['module' => 'ahgLibraryPlugin', 'action' => 'edit', 'slug' => $resource->slug]); ?>" class="btn btn-primary w-100 mb-2">
          <i class="fas fa-edit me-2"></i><?php echo __('Edit'); ?>
        </a>
        <a href="<?php echo url_for([$resource, 'module' => 'informationobject', 'action' => 'delete']); ?>" class="btn btn-danger w-100 mb-2">
          <i class="fas fa-trash me-2"></i><?php echo __('Delete'); ?>
        </a>
        <a href="<?php echo url_for([$resource, 'module' => 'default', 'action' => 'move']); ?>" class="btn btn-success w-100 mb-2">
          <i class="fas fa-arrows-alt me-2"></i><?php echo __('Move'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'ahgLibraryPlugin', 'action' => 'browse']); ?>" class="btn btn-outline-secondary w-100 mb-2">
          <i class="fas fa-list me-2"></i><?php echo __('Browse library'); ?>
        </a>
        <div class="dropdown">
          <button type="button" class="btn btn-outline-dark w-100 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-ellipsis-h me-2"></i><?php echo __('More'); ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end w-100">
            <li><a class="dropdown-item" href="<?php echo url_for([$resource, 'module' => 'informationobject', 'action' => 'rename']); ?>"><i class="fas fa-i-cursor me-2"></i><?php echo __('Rename'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo url_for([$resource, 'module' => 'informationobject', 'action' => 'updatePublicationStatus']); ?>"><i class="fas fa-eye me-2"></i><?php echo __('Update publication status'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for([$resource, 'module' => 'object', 'action' => 'editPhysicalObjects']); ?>"><i class="fas fa-box me-2"></i><?php echo __('Link physical storage'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for([$resource->digitalObjectsRelatedByobjectId[0], 'module' => 'digitalobject', 'action' => 'edit']); ?>"><i class="fas fa-file-upload me-2"></i><?php echo __('Edit digital object'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for([$resource, 'sf_route' => 'slug/default', 'module' => 'right', 'action' => 'edit']); ?>"><i class="fas fa-copyright me-2"></i><?php echo __('Create new rights'); ?></a></li>
            <?php if (checkPluginEnabled('ahgExtendedRightsPlugin')): ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resource->slug]); ?>"><i class="fas fa-balance-scale me-2"></i><?php echo __('Extended Rights'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            <?php if (checkPluginEnabled('ahgGrapPlugin')): ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'grap', 'action' => 'index', 'slug' => $resource->slug]); ?>"><i class="fas fa-file-invoice me-2"></i><?php echo __('View GRAP data'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'grap', 'action' => 'edit', 'slug' => $resource->slug]); ?>"><i class="fas fa-file-invoice me-2"></i><?php echo __('Edit GRAP data'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            <?php if (checkPluginEnabled('ahgSpectrumPlugin') || checkPluginEnabled('sfMuseumPlugin')): ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'label', 'slug' => $resource->slug]); ?>"><i class="fas fa-tag me-2"></i><?php echo __('Generate label'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </section>


    <!-- Barcode / ISBN -->
    <?php
      $isbn = $libraryData['isbn'] ?? '';
      $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
    ?>
    <?php if (!empty($cleanIsbn)): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-barcode me-2"></i><?php echo __('ISBN Barcode'); ?></h5>
      </div>
      <div class="card-body text-center">
        <svg id="isbn-barcode"></svg>
        <p class="text-muted small mt-2 mb-0"><?php echo esc_entities($isbn); ?></p>
      </div>
    </section>
    <script src="/plugins/ahgLibraryPlugin/js/JsBarcode.all.min.js"></script>
    <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
      document.addEventListener('DOMContentLoaded', function() {
        try {
          JsBarcode("#isbn-barcode", "<?php echo $cleanIsbn; ?>", {
            format: "<?php echo strlen($cleanIsbn) === 13 ? 'EAN13' : 'EAN8'; ?>",
            width: 2,
            height: 60,
            displayValue: false,
            margin: 10
          });
        } catch(e) {
          // Fallback to CODE128 if ISBN format fails
          JsBarcode("#isbn-barcode", "<?php echo $cleanIsbn; ?>", {
            format: "CODE128",
            width: 2,
            height: 60,
            displayValue: false,
            margin: 10
          });
        }
      });
    </script>
    <?php endif; ?>

    <!-- Related Records -->
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Related records'); ?></h5>
      </div>
      <div class="card-body">
        <a href="<?php echo url_for([$resource, 'module' => 'informationobject']); ?>" class="btn btn-outline-primary w-100 mb-2">
          <i class="fas fa-archive me-2"></i><?php echo __('View archival description'); ?>
        </a>
        <?php if ($resource->parent && $resource->parent->id != QubitInformationObject::ROOT_ID): ?>
        <a href="<?php echo url_for([$resource->parent, 'module' => 'informationobject']); ?>" class="btn btn-outline-secondary w-100 mb-2">
          <i class="fas fa-level-up-alt me-2"></i><?php echo __('Parent record'); ?>
        </a>
        <?php endif; ?>
        <?php
          $childCount = $resource->getDescendants()->count();
          if ($childCount > 0):
        ?>
        <a href="<?php echo url_for([$resource, 'module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-outline-secondary w-100">
          <i class="fas fa-sitemap me-2"></i><?php echo __('%1% child records', ['%1%' => $childCount]); ?>
        </a>
        <?php endif; ?>
      </div>
    </section>

    <!-- Physical Storage -->
    <!-- Physical Storage -->
    <?php
      $criteria = new Criteria();
      $criteria->add(QubitRelation::OBJECT_ID, $resource->id);
      $criteria->add(QubitRelation::TYPE_ID, QubitTerm::HAS_PHYSICAL_OBJECT_ID);
      $criteria->addJoin(QubitRelation::SUBJECT_ID, QubitPhysicalObject::ID);
      $physicalObjects = QubitPhysicalObject::get($criteria);
      
      // Load extended data repository
      require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/PhysicalObjectExtendedRepository.php';
      $poRepo = new \AtomFramework\Repositories\PhysicalObjectExtendedRepository();
    ?>
    <?php if (count($physicalObjects) > 0): ?>
    <section class="card mb-4">
      <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i><?php echo __('Physical storage'); ?></h5>
      </div>
      <div class="card-body">
        <?php foreach ($physicalObjects as $po): ?>
        <?php $extData = $poRepo->getExtendedData($po->id) ?? []; ?>
        <div class="mb-3 pb-3 border-bottom">
          <strong>
            <a href="<?php echo url_for([$po, 'module' => 'physicalobject']); ?>">
              <?php echo render_title($po); ?>
            </a>
          </strong>
          <?php if ($po->type): ?>
            <span class="badge bg-secondary ms-2"><?php echo render_value($po->type); ?></span>
          <?php endif; ?>
          <?php if (!empty($extData['status']) && $extData['status'] !== 'active'): ?>
            <span class="badge bg-<?php echo $extData['status'] === 'full' ? 'danger' : 'warning'; ?> ms-1"><?php echo ucfirst($extData['status']); ?></span>
          <?php endif; ?>
          
          <?php if (!empty($extData)): ?>
          <div class="mt-2">
            <?php
              $locationParts = array_filter([
                $extData['building'] ?? null,
                !empty($extData['floor']) ? 'Floor ' . $extData['floor'] : null,
                !empty($extData['room']) ? 'Room ' . $extData['room'] : null,
              ]);
              $shelfParts = array_filter([
                !empty($extData['aisle']) ? 'Aisle ' . $extData['aisle'] : null,
                !empty($extData['bay']) ? 'Bay ' . $extData['bay'] : null,
                !empty($extData['rack']) ? 'Rack ' . $extData['rack'] : null,
                !empty($extData['shelf']) ? 'Shelf ' . $extData['shelf'] : null,
                !empty($extData['position']) ? 'Pos ' . $extData['position'] : null,
              ]);
            ?>
            <?php if (!empty($locationParts)): ?>
            <small class="text-muted d-block"><i class="fas fa-building me-1"></i><?php echo implode(' &gt; ', $locationParts); ?></small>
            <?php endif; ?>
            <?php if (!empty($shelfParts)): ?>
            <small class="text-primary d-block"><i class="fas fa-th me-1"></i><?php echo implode(' &gt; ', $shelfParts); ?></small>
            <?php endif; ?>
            <?php if (!empty($extData['barcode'])): ?>
            <small class="text-muted d-block"><i class="fas fa-barcode me-1"></i><?php echo esc_entities($extData['barcode']); ?></small>
            <?php endif; ?>
            <?php if (!empty($extData['total_capacity'])): ?>
            <?php
              $used = (int)($extData['used_capacity'] ?? 0);
              $total = (int)$extData['total_capacity'];
              $available = $total - $used;
              $percent = $total > 0 ? round(($used / $total) * 100) : 0;
              $barClass = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success');
            ?>
            <div class="mt-1">
              <small class="text-muted"><?php echo __('Capacity'); ?>: <?php echo $used; ?>/<?php echo $total; ?> <?php echo esc_entities($extData['capacity_unit'] ?? 'items'); ?></small>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo $percent; ?>%;"></div>
              </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($extData['climate_controlled'])): ?>
            <small class="text-info d-block mt-1"><i class="fas fa-thermometer-half me-1"></i><?php echo __('Climate controlled'); ?></small>
            <?php endif; ?>
            <?php if (!empty($extData['security_level'])): ?>
            <small class="text-danger d-block"><i class="fas fa-lock me-1"></i><?php echo ucfirst(esc_entities($extData['security_level'])); ?></small>
            <?php endif; ?>
          </div>
          <?php elseif ($po->getLocation(['cultureFallback' => true])): ?>
          <small class="text-muted d-block mt-1"><i class="fas fa-map-marker-alt me-1"></i><?php echo $po->getLocation(['cultureFallback' => true]); ?></small>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Item Physical Location -->
    <?php if (!empty($itemLocation)): ?>
    <?php include_partial("informationobject/itemPhysicalLocationView", ["itemLocation" => $itemLocation]); ?>
    <?php endif; ?>
    <!-- E-book Access -->
    <?php if (!empty($libraryData['ebook_preview_url'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-tablet-alt me-2"></i><?php echo __('E-book Access'); ?></h5>
      </div>
      <div class="card-body">
        <a href="<?php echo esc_entities($libraryData['ebook_preview_url']); ?>" target="_blank" class="btn btn-outline-primary w-100">
          <i class="fas fa-book-reader me-2"></i><?php echo __('Preview on Archive.org'); ?>
        </a>
      </div>
    </section>
    <?php endif; ?>

    <!-- External Links -->
    <?php if (!empty($libraryData['openlibrary_url']) || !empty($libraryData['goodreads_id']) || !empty($libraryData['librarything_id'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-external-link-alt me-2"></i><?php echo __('External Links'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($libraryData['openlibrary_url'])): ?>
          <a href="<?php echo esc_entities($libraryData['openlibrary_url']); ?>" target="_blank" class="btn btn-outline-secondary w-100 mb-2">
            <i class="fas fa-book me-2"></i>Open Library
          </a>
        <?php endif; ?>

        <?php if (!empty($libraryData['goodreads_id'])): ?>
          <a href="https://www.goodreads.com/book/show/<?php echo esc_entities($libraryData['goodreads_id']); ?>" target="_blank" class="btn btn-outline-secondary w-100 mb-2">
            <i class="fas fa-star me-2"></i>Goodreads
          </a>
        <?php endif; ?>

        <?php if (!empty($libraryData['librarything_id'])): ?>
          <a href="https://www.librarything.com/work/<?php echo esc_entities($libraryData['librarything_id']); ?>" target="_blank" class="btn btn-outline-secondary w-100">
            <i class="fas fa-books me-2"></i>LibraryThing
          </a>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

  </div>
</div>
