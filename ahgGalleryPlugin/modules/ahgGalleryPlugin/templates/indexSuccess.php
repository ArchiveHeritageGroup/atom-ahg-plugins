<?php decorate_with('layout_2col'); ?>
<?php
// Get raw resource for embargo checks
$rawResource = isset($qubitResource) ? sfOutputEscaper::unescape($qubitResource) : (isset($resource) ? sfOutputEscaper::unescape($resource) : null);
?>

<?php slot('sidebar'); ?>

  <!-- Digital Object / Image -->
  <div class="card mb-4">
    <div class="card-body text-center">
      <?php if ($digitalObject && $rawResource && EmbargoHelper::canViewThumbnail($rawResource->id)): ?>
        <?php
          $fullPath = $digitalObject->path . $digitalObject->name;
          $ext = strtolower(pathinfo($digitalObject->name, PATHINFO_EXTENSION));
          $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
          $is3D = in_array($ext, ['obj', 'glb', 'gltf', 'fbx', 'stl', 'ply', 'dae']);
        ?>
        <?php if ($isImage): ?>
          <img src="<?php echo esc_entities($fullPath); ?>" alt="" class="img-fluid" style="max-height: 300px;">
        <?php elseif ($is3D): ?>
          <!-- 3D Preview with model-viewer -->
          <div class="position-relative">
            <?php if (in_array($ext, ['glb', 'gltf'])): ?>
              <!-- Model Viewer for GLB/GLTF -->
              <script type="module" src="/plugins/ahgThemeB5Plugin/js/model-viewer.min.js"></script>
              <model-viewer 
                id="sidebar-model-viewer"
                src="<?php echo esc_entities($fullPath); ?>" 
                camera-controls 
                touch-action="pan-y" 
                auto-rotate
                shadow-intensity="1"
                style="width:100%;height:250px;background:linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);border-radius:8px;">
              </model-viewer>
            <?php else: ?>
              <!-- Three.js preview for OBJ/STL -->
              <div id="sidebar-3d-viewer" style="width:100%;height:250px;background:linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);border-radius:8px;"></div>
              <script type="importmap">
              {
                "imports": {
                  "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
                  "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"
                }
              }
              </script>
              <script type="module">
              import * as THREE from 'three';
              import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
              import { OBJLoader } from 'three/addons/loaders/OBJLoader.js';
              import { STLLoader } from 'three/addons/loaders/STLLoader.js';
              
              const container = document.getElementById('sidebar-3d-viewer');
              if (container) {
                const scene = new THREE.Scene();
                scene.background = new THREE.Color(0x1a1a2e);
                const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
                camera.position.set(0, 1, 3);
                const renderer = new THREE.WebGLRenderer({ antialias: true });
                renderer.setSize(container.clientWidth, container.clientHeight);
                container.appendChild(renderer.domElement);
                const controls = new OrbitControls(camera, renderer.domElement);
                controls.enableDamping = true;
                controls.autoRotate = true;
                scene.add(new THREE.AmbientLight(0xffffff, 0.6));
                const dl = new THREE.DirectionalLight(0xffffff, 0.8);
                dl.position.set(5, 10, 7.5);
                scene.add(dl);
                
                function addModel(obj) {
                  const box = new THREE.Box3().setFromObject(obj);
                  const center = box.getCenter(new THREE.Vector3());
                  const size = box.getSize(new THREE.Vector3());
                  const scale = 2 / Math.max(size.x, size.y, size.z);
                  obj.scale.setScalar(scale);
                  obj.position.sub(center.multiplyScalar(scale));
                  obj.traverse(c => { if (c.isMesh) c.material = new THREE.MeshStandardMaterial({color:0xcccccc,roughness:0.5,metalness:0.3}); });
                  scene.add(obj);
                }
                
                const ext = '<?php echo $ext; ?>';
                const modelPath = '<?php echo esc_entities($fullPath); ?>';
                if (ext === 'obj') new OBJLoader().load(modelPath, addModel, undefined, e => console.error('OBJ load error:', e));
                else if (ext === 'stl') new STLLoader().load(modelPath, g => addModel(new THREE.Mesh(g)), undefined, e => console.error('STL load error:', e));
                
                function animate() { requestAnimationFrame(animate); controls.update(); renderer.render(scene, camera); }
                animate();
              }
              </script>
            <?php endif; ?>
            <!-- Fullscreen button overlay -->
            <button onclick="open3DFullscreen()" class="btn btn-sm btn-primary position-absolute" style="bottom:10px;right:10px;z-index:10;">
              <i class="fas fa-expand me-1"></i><?php echo __('Fullscreen'); ?>
            </button>
          </div>
          <small class="text-muted d-block mt-2">
            <i class="fas fa-mouse me-1"></i><?php echo __('Drag to rotate'); ?> | <?php echo esc_entities($digitalObject->name); ?>
          </small>
        <?php else: ?>
          <div class="p-4">
            <i class="fas fa-file fa-5x text-muted"></i><br>
            <small class="text-muted"><?php echo esc_entities($digitalObject->name); ?></small>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <i class="fas fa-palette fa-5x text-muted"></i>
      <?php endif; ?>
    </div>
  </div>

  <!-- User Actions (compact with tooltips) -->
  <?php
  use Illuminate\Database\Capsule\Manager as DB;
  $userId = $sf_user->getAttribute('user_id');
  $sessionId = session_id();
  if (empty($sessionId) && !$userId) { @session_start(); $sessionId = session_id(); }
  $favoriteId = null;
  $cartId = null;
  if ($userId) {
      $favoriteId = DB::table('favorites')->where('user_id', $userId)->where('archival_description_id', $resource->id)->value('id');
      $cartId = DB::table('cart')->where('user_id', $userId)->where('archival_description_id', $resource->id)->whereNull('completed_at')->value('id');
  } elseif ($sessionId) {
      $cartId = DB::table('cart')->where('session_id', $sessionId)->where('archival_description_id', $resource->id)->whereNull('completed_at')->value('id');
  }
  $hasDigitalObject = DB::table('digital_object')->where('object_id', $resource->id)->exists();
  ?>
  <div class="d-flex flex-wrap gap-1 mb-3">
    <?php if (class_exists('ahgFavoritesPluginConfiguration') && $userId): ?>
      <?php if ($favoriteId): ?>
        <a href="<?php echo url_for(['module' => 'ahgFavorites', 'action' => 'remove', 'id' => $favoriteId]); ?>" class="btn btn-xs btn-outline-danger" title="<?php echo __('Remove from Favorites'); ?>" data-bs-toggle="tooltip"><i class="fas fa-heart-broken"></i></a>
      <?php else: ?>
        <a href="<?php echo url_for(['module' => 'ahgFavorites', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-danger" title="<?php echo __('Add to Favorites'); ?>" data-bs-toggle="tooltip"><i class="fas fa-heart"></i></a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (class_exists('ahgFeedbackPluginConfiguration')): ?>
      <a href="<?php echo url_for(['module' => 'ahgFeedback', 'action' => 'submit', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-secondary" title="<?php echo __('Item Feedback'); ?>" data-bs-toggle="tooltip"><i class="fas fa-comment"></i></a>
    <?php endif; ?>
    <?php if (class_exists('ahgRequestToPublishPluginConfiguration') && $hasDigitalObject): ?>
      <a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'submit', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-primary" title="<?php echo __('Request to Publish'); ?>" data-bs-toggle="tooltip"><i class="fas fa-paper-plane"></i></a>
    <?php endif; ?>
    <?php if (class_exists('ahgCartPluginConfiguration') && $hasDigitalObject): ?>
      <?php if ($cartId): ?>
        <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'browse']); ?>" class="btn btn-xs btn-outline-success" title="<?php echo __('Go to Cart'); ?>" data-bs-toggle="tooltip"><i class="fas fa-shopping-cart"></i></a>
      <?php else: ?>
        <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-success" title="<?php echo __('Add to Cart'); ?>" data-bs-toggle="tooltip"><i class="fas fa-cart-plus"></i></a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <!-- Digital Object Details -->
  <?php if ($digitalObject && $canEdit): ?>
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-image me-2"></i><?php echo __('Digital object'); ?></h5>
      </div>
      <div class="card-body">
        <table class="table table-sm mb-3">
          <tr>
            <td class="text-muted"><?php echo __('Filename'); ?></td>
            <td><?php echo esc_entities($digitalObject->name); ?></td>
          </tr>
          <tr>
            <td class="text-muted"><?php echo __('Media type'); ?></td>
            <td><?php echo esc_entities($digitalObject->mime_type); ?></td>
          </tr>
        </table>
        
        <div class="mt-3">
          <a href="<?php echo url_for([$qubitResource->digitalObjectsRelatedByobjectId[0], 'module' => 'digitalobject', 'action' => 'edit']); ?>" class="btn btn-sm btn-outline-primary w-100 mb-2">
            <i class="fas fa-edit me-1"></i><?php echo __('Edit digital object'); ?>
          </a>
          <a href="<?php echo url_for([$qubitResource->digitalObjectsRelatedByobjectId[0], 'module' => 'digitalobject', 'action' => 'delete']); ?>" class="btn btn-sm btn-outline-danger w-100">
            <i class="fas fa-trash me-1"></i><?php echo __('Delete digital object'); ?>
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Quick Info -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Quick Info'); ?></h5>
    </div>
    <ul class="list-group list-group-flush">
      <?php if ($resource->identifier): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Object #'); ?></span>
          <strong><?php echo esc_entities($resource->identifier); ?></strong>
        </li>
      <?php endif; ?>
      <?php if (!empty(($galleryData['work_type_name'] ?? $galleryData['work_type']))): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Type'); ?></span>
          <span class="badge bg-secondary"><?php echo esc_entities(($galleryData['work_type_name'] ?? $galleryData['work_type'])); ?></span>
        </li>
      <?php endif; ?>
      <?php if ($repositoryName): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Repository'); ?></span>
          <span><?php echo esc_entities($repositoryName); ?></span>
        </li>
      <?php endif; ?>
    </ul>
  </div>


  <!-- Collections Management -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Collections Management'); ?></h5>
    </div>
    <ul class="list-group list-group-flush">
      <li class="list-group-item"><a href="<?php echo url_for(['module' => 'cco', 'action' => 'provenance', 'slug' => $resource->slug]); ?>"><i class="fas fa-sitemap me-2"></i><?php echo __('Provenance'); ?></a></li>
      <li class="list-group-item"><a href="<?php echo url_for(['module' => 'arCondition', 'action' => 'conditionCheck', 'slug' => $resource->slug]); ?>"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Condition assessment'); ?></a></li>
      <li class="list-group-item"><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index', 'slug' => $resource->slug]); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('Spectrum data'); ?></a></li>
      <li class="list-group-item"><a href="<?php echo url_for(['module' => 'grap', 'action' => 'index', 'slug' => $resource->slug]); ?>"><i class="fas fa-file-invoice-dollar me-2"></i><?php echo __('GRAP data'); ?></a></li>
      <li class="list-group-item"><a href="<?php echo url_for(['module' => 'oais', 'action' => 'createSip', 'slug' => $resource->slug]); ?>"><i class="fas fa-archive me-2"></i><?php echo __('Digital Preservation (OAIS)'); ?></a></li>
      <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'cite', 'slug' => $resource->slug]); ?>"><i class="fas fa-quote-left me-2"></i><?php echo __('Cite this Record'); ?></a></li>
      <li class="list-group-item"><a href="<?php echo url_for(['module' => 'display', 'action' => 'browse']); ?>"><i class="fas fa-th me-2"></i><?php echo __('GLAM browser'); ?></a></li>
    </ul>
  </div>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo esc_entities($resource->identifier); ?> - <?php echo esc_entities($resource->title); ?></h1>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgGalleryPlugin', 'action' => 'browse']); ?>"><?php echo __('Gallery'); ?></a></li>
      <li class="breadcrumb-item active"><?php echo esc_entities($resource->title); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- Object Identification -->
  <section class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-palette me-2"></i><?php echo __('Object Identification'); ?></h5>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-3 text-muted"><?php echo __('Title'); ?></div>
        <div class="col-md-9"><strong><?php echo esc_entities($resource->title); ?></strong></div>
      </div>

      <?php if ($resource->identifier): ?>
        <div class="row mb-3">
          <div class="col-md-3 text-muted"><?php echo __('Object Number'); ?></div>
          <div class="col-md-9"><?php echo esc_entities($resource->identifier); ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty(($galleryData['work_type_name'] ?? $galleryData['work_type']))): ?>
        <div class="row mb-3">
          <div class="col-md-3 text-muted"><?php echo __('Work Type'); ?></div>
          <div class="col-md-9"><span class="badge bg-secondary"><?php echo esc_entities(($galleryData['work_type_name'] ?? $galleryData['work_type'])); ?></span></div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Creation -->
  <?php if ($creatorName || !empty($galleryData['creator_display']) || !empty($galleryData['creation_date_display'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Creation'); ?></h5>
      </div>
      <div class="card-body">
        <?php if ($creatorName || !empty($galleryData['creator_display'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Creator'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['creator_display'] ?? $creatorName); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($galleryData['creator_role'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Role'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['creator_role']); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($galleryData['creation_date_display'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Date'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['creation_date_display']); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($galleryData['creation_place'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Place'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['creation_place']); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Physical Description -->
  <?php if (!empty($galleryData['dimensions_display']) || !empty($galleryData['materials_display'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-ruler me-2"></i><?php echo __('Physical Description'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($galleryData['dimensions_display'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Dimensions'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['dimensions_display'])); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($galleryData['materials_display'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Materials'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['materials_display'])); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($galleryData['techniques'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Techniques'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['techniques']); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['support'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Support'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['support']); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['height_value']) || !empty($galleryData['width_value']) || !empty($galleryData['depth_value'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Measurements'); ?></div>
            <div class="col-md-9">
              <?php if (!empty($galleryData['height_value'])): ?>H: <?php echo esc_entities($galleryData['height_value']); ?> <?php endif; ?>
              <?php if (!empty($galleryData['width_value'])): ?>W: <?php echo esc_entities($galleryData['width_value']); ?> <?php endif; ?>
              <?php if (!empty($galleryData['depth_value'])): ?>D: <?php echo esc_entities($galleryData['depth_value']); ?> <?php endif; ?>
              <?php if (!empty($galleryData['weight_value'])): ?>Weight: <?php echo esc_entities($galleryData['weight_value']); ?> <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['dimension_notes'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Dimension Notes'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['dimension_notes'])); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>
  
  <!-- Cultural Context -->
  <?php if (!empty($galleryData['culture']) || !empty($galleryData['style']) || !empty($galleryData['period']) || !empty($galleryData['school_group'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-globe me-2"></i><?php echo __('Cultural Context'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($galleryData['culture'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Culture/People'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['culture']); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['style'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Style'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['style']); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['period'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Period'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['period']); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['school_group'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('School/Group'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['school_group']); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Description -->
  <?php if ($resource->scope_and_content || !empty($galleryData['description'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('Description'); ?></h5>
      </div>
      <div class="card-body">
        <p><?php echo nl2br(esc_entities($galleryData['description'] ?? $resource->scope_and_content)); ?></p>
      </div>
    </section>
  <?php endif; ?>

  <!-- Subject Matter -->
  <?php if (!empty($galleryData['subject_display']) || !empty($galleryData['inscriptions'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-tags me-2"></i><?php echo __('Subject Matter'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($galleryData['subject_display'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Subject'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['subject_display'])); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($galleryData['inscriptions'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Inscriptions'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['inscriptions'])); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['subjects_depicted'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Subjects Depicted'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['subjects_depicted'])); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['iconography'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Iconography'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['iconography'])); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['named_subjects'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Named Subjects'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['named_subjects'])); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['signature'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Signature'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['signature']); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['marks'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Marks/Labels'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['marks'])); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>
  
  <!-- Edition Information (for prints/multiples) -->
  <?php if (!empty($galleryData['edition_number']) || !empty($galleryData['edition_size']) || !empty($galleryData['state'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-copy me-2"></i><?php echo __('Edition Information'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($galleryData['edition_number']) || !empty($galleryData['edition_size'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Edition'); ?></div>
            <div class="col-md-9">
              <?php if (!empty($galleryData['edition_number'])): ?><?php echo esc_entities($galleryData['edition_number']); ?><?php endif; ?>
              <?php if (!empty($galleryData['edition_number']) && !empty($galleryData['edition_size'])): ?> / <?php endif; ?>
              <?php if (!empty($galleryData['edition_size'])): ?><?php echo esc_entities($galleryData['edition_size']); ?><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['state'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('State'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['state']); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['impression_quality'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Impression Quality'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['impression_quality']); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Condition & Rights -->
  <?php if (!empty($galleryData['condition_summary']) || !empty($galleryData['rights_statement']) || !empty($galleryData['credit_line'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Condition & Rights'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($galleryData['condition_summary'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Condition'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['condition_summary'])); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($galleryData['credit_line'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Credit Line'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['credit_line']); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($galleryData['rights_statement'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Rights'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['rights_statement'])); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['condition_notes'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Condition Notes'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['condition_notes'])); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['copyright_holder'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Copyright Holder'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['copyright_holder']); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['reproduction_conditions'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Reproduction Conditions'); ?></div>
            <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['reproduction_conditions'])); ?></div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($galleryData['location_within_repository'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Location'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['location_within_repository']); ?></div>
          </div>
        <?php endif; ?>
  <!-- Item Physical Location -->
  <?php if (!empty($itemLocation)): ?>
  <?php include_partial("informationobject/itemPhysicalLocationView", ["itemLocation" => $itemLocation]); ?>
  <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>
  
  <!-- Related Works -->
  <?php if (!empty($galleryData['related_works'])): ?>
    <section class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Related Works'); ?></h5>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-3 text-muted"><?php echo __('Related Works'); ?></div>
          <div class="col-md-9"><?php echo nl2br(esc_entities($galleryData['related_works'])); ?></div>
        </div>
        <?php if (!empty($galleryData['relationship_type'])): ?>
          <div class="row mb-3">
            <div class="col-md-3 text-muted"><?php echo __('Relationship Type'); ?></div>
            <div class="col-md-9"><?php echo esc_entities($galleryData['relationship_type']); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Action buttons bar -->
  <?php if ($canEdit): ?>
    <section class="actions">
      <ul class="nav nav-pills">
        <li class="nav-item">
          <a class="btn btn-success me-2" href="<?php echo url_for(['module' => 'ahgGalleryPlugin', 'action' => 'edit', 'slug' => $resource->slug]); ?>">
            <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-danger me-2" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'delete', 'slug' => $resource->slug]); ?>">
            <i class="fas fa-trash me-1"></i><?php echo __('Delete'); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-primary me-2" href="<?php echo url_for(['module' => 'ahgGalleryPlugin', 'action' => 'add', 'parent' => $resource->slug]); ?>">
            <i class="fas fa-plus me-1"></i><?php echo __('Add new'); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-secondary me-2" href="<?php echo url_for(['module' => 'object', 'action' => 'copy', 'slug' => $resource->slug]); ?>">
            <i class="fas fa-copy me-1"></i><?php echo __('Duplicate'); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-secondary me-2" href="<?php echo url_for(['module' => 'default', 'action' => 'move', 'slug' => $resource->slug]); ?>">
            <i class="fas fa-arrows-alt me-1"></i><?php echo __('Move'); ?>
          </a>
        </li>
        <li class="nav-item dropup">
          <a class="btn btn-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <?php echo __('More'); ?>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'rename', 'slug' => $resource->slug]); ?>"><i class="fas fa-i-cursor me-2"></i><?php echo __('Rename'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'updatePublicationStatus', 'slug' => $resource->slug]); ?>"><i class="fas fa-globe me-2"></i><?php echo __('Update publication status'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'physicalobject', 'action' => 'link', 'slug' => $resource->slug]); ?>"><i class="fas fa-box me-2"></i><?php echo __('Link physical storage'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <?php if ($digitalObject): ?>
              <li><a class="dropdown-item" href="<?php echo url_for([$qubitResource->digitalObjectsRelatedByobjectId[0], 'module' => 'digitalobject', 'action' => 'edit']); ?>"><i class="fas fa-edit me-2"></i><?php echo __('Edit digital object'); ?></a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="<?php echo url_for([$qubitResource, 'module' => 'object', 'action' => 'addDigitalObject']); ?>"><i class="fas fa-link me-2"></i><?php echo __('Link digital object'); ?></a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'multiFileUpload', 'slug' => $resource->slug]); ?>"><i class="fas fa-upload me-2"></i><?php echo __('Import digital objects'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for([$qubitResource, 'sf_route' => 'slug/default', 'module' => 'right', 'action' => 'edit']); ?>"><i class="fas fa-balance-scale me-2"></i><?php echo __('Create new rights'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resource->slug]); ?>"><i class="fas fa-copyright me-2"></i><?php echo __('Extended Rights'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'grap', 'action' => 'index', 'slug' => $resource->slug]); ?>"><i class="fas fa-file-invoice-dollar me-2"></i><?php echo __('View GRAP data'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'grap', 'action' => 'edit', 'slug' => $resource->slug]); ?>"><i class="fas fa-edit me-2"></i><?php echo __('Edit GRAP data'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index', 'slug' => $resource->slug]); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('View Spectrum data'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'label', 'slug' => $resource->slug]); ?>"><i class="fas fa-barcode me-2"></i><?php echo __('Generate label'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'cco', 'action' => 'provenance', 'slug' => $resource->slug]); ?>"><i class="fas fa-sitemap me-2"></i><?php echo __('Provenance'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $resource->slug]); ?>"><i class="fas fa-tasks me-2"></i><?php echo __('Workflow Status'); ?></a></li>
          </ul>
        </li>
      </ul>
      <!-- EXTENDED RIGHTS AREA -->
    </section>
  <?php endif; ?>

<!-- DEBUG: digitalObject exists: <?php echo $digitalObject ? "YES" : "NO"; ?>, name: <?php echo $digitalObject ? $digitalObject->name : "N/A"; ?> -->
<?php 
if ($digitalObject && in_array(strtolower(pathinfo($digitalObject->name, PATHINFO_EXTENSION)), ['obj', 'glb', 'gltf', 'fbx', 'stl', 'ply', 'dae'])): ?>
<?php
  $fullPath3D = $digitalObject->path . $digitalObject->name;
  $ext3D = strtolower(pathinfo($digitalObject->name, PATHINFO_EXTENSION));
?>
<div id="fullscreen-3d-modal" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="z-index: 9999; background: rgba(0,0,0,0.95);">
  <!-- Header bar -->
  <div class="position-absolute top-0 start-0 w-100 p-3 d-flex justify-content-between align-items-center" style="z-index: 10001; background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, transparent 100%);">
    <div class="text-white">
      <h5 class="mb-0"><i class="fas fa-cube me-2"></i><?php echo esc_entities($resource->title ?? $digitalObject->name); ?></h5>
      <small class="text-muted"><?php echo esc_entities($digitalObject->name); ?></small>
    </div>
    <div>
      <button class="btn btn-outline-light btn-sm me-2" onclick="reset3DCamera()" title="<?php echo __('Reset view'); ?>">
        <i class="fas fa-sync-alt"></i>
      </button>
      <button class="btn btn-outline-light btn-sm me-2" onclick="toggle3DAutoRotate()" title="<?php echo __('Toggle auto-rotate'); ?>">
        <i class="fas fa-redo"></i>
      </button>
      <button class="btn btn-light btn-sm" onclick="close3DFullscreen()">
        <i class="fas fa-times me-1"></i><?php echo __('Close'); ?>
      </button>
    </div>
  </div>
  
  <!-- 3D Viewer Container -->
  <div id="fullscreen-3d-container" class="w-100 h-100">
    <?php if (in_array($ext3D, ['glb', 'gltf'])): ?>
      <script type="module" src="/plugins/ahgThemeB5Plugin/js/model-viewer.min.js"></script>
      <model-viewer 
        id="fullscreen-model-viewer"
        src="<?php echo esc_entities($fullPath3D); ?>" 
        camera-controls 
        touch-action="pan-y" 
        auto-rotate
        shadow-intensity="1"
        exposure="1"
        environment-image="neutral"
        style="width:100%;height:100%;background:transparent;">
        <div slot="poster" class="d-flex flex-column align-items-center justify-content-center h-100 text-white">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <span><?php echo __('Loading 3D model...'); ?></span>
        </div>
        <button slot="ar-button" class="btn btn-primary position-absolute bottom-0 end-0 m-3">
          <i class="fas fa-cube me-1"></i><?php echo __('View in AR'); ?>
        </button>
      </model-viewer>
    <?php else: ?>
      <!-- Three.js viewer for OBJ/FBX/STL -->
      <div id="fullscreen-threejs-container" class="w-100 h-100"></div>
    <?php endif; ?>
  </div>
  
  <!-- Help overlay -->
  <div class="position-absolute bottom-0 start-0 w-100 p-3 text-center" style="z-index: 10001; background: linear-gradient(0deg, rgba(0,0,0,0.8) 0%, transparent 100%);">
    <small class="text-white-50">
      <i class="fas fa-mouse me-2"></i><?php echo __('Drag to rotate'); ?> &nbsp;|&nbsp;
      <i class="fas fa-search-plus me-2"></i><?php echo __('Scroll to zoom'); ?> &nbsp;|&nbsp;
      <i class="fas fa-arrows-alt me-2"></i><?php echo __('Right-click drag to pan'); ?> &nbsp;|&nbsp;
      <kbd>ESC</kbd> <?php echo __('to close'); ?>
    </small>
  </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
#fullscreen-3d-modal { backdrop-filter: blur(10px); }
#fullscreen-model-viewer { --poster-color: transparent; }
#fullscreen-model-viewer::part(default-ar-button) { display: none; }
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
let autoRotateEnabled = true;

function open3DFullscreen() {
  const modal = document.getElementById('fullscreen-3d-modal');
  modal.classList.remove('d-none');
  document.body.style.overflow = 'hidden';
  <?php if (!in_array($ext3D, ['glb', 'gltf'])): ?>
  if (typeof initThreeJsFullscreen === 'function') initThreeJsFullscreen();
  <?php endif; ?>
}

function close3DFullscreen() {
  const modal = document.getElementById('fullscreen-3d-modal');
  modal.classList.add('d-none');
  document.body.style.overflow = '';
  <?php if (!in_array($ext3D, ['glb', 'gltf'])): ?>
  if (typeof cleanupThreeJs === 'function') cleanupThreeJs();
  <?php endif; ?>
}

function reset3DCamera() {
  <?php if (in_array($ext3D, ['glb', 'gltf'])): ?>
  const viewer = document.getElementById('fullscreen-model-viewer');
  if (viewer) { viewer.cameraOrbit = '0deg 75deg 105%'; viewer.fieldOfView = 'auto'; }
  <?php else: ?>
  if (typeof resetThreeJsCamera === 'function') resetThreeJsCamera();
  <?php endif; ?>
}

function toggle3DAutoRotate() {
  autoRotateEnabled = !autoRotateEnabled;
  <?php if (in_array($ext3D, ['glb', 'gltf'])): ?>
  const viewer = document.getElementById('fullscreen-model-viewer');
  if (viewer) viewer.autoRotate = autoRotateEnabled;
  <?php else: ?>
  if (typeof toggleThreeJsAutoRotate === 'function') toggleThreeJsAutoRotate();
  <?php endif; ?>
}

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') close3DFullscreen(); });
</script>

<?php if (!in_array($ext3D, ['glb', 'gltf'])): ?>
<!-- Three.js ES Module for fullscreen OBJ/STL viewer -->
<script type="importmap">
{
  "imports": {
    "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"
  }
}
</script>
<script type="module">
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { OBJLoader } from 'three/addons/loaders/OBJLoader.js';
import { STLLoader } from 'three/addons/loaders/STLLoader.js';

let scene, camera, controls, renderer;
let autoRotate = true;

window.initThreeJsFullscreen = function() {
  const container = document.getElementById('fullscreen-threejs-container');
  if (!container) return;
  if (renderer) { animate(); return; }
  
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x1a1a2e);
  camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
  camera.position.set(0, 1, 3);
  renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(window.devicePixelRatio);
  container.appendChild(renderer.domElement);
  controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.autoRotate = autoRotate;
  controls.autoRotateSpeed = 2;
  
  scene.add(new THREE.AmbientLight(0xffffff, 0.6));
  const dl = new THREE.DirectionalLight(0xffffff, 0.8);
  dl.position.set(5, 10, 7.5);
  scene.add(dl);
  const bl = new THREE.DirectionalLight(0xffffff, 0.3);
  bl.position.set(-5, -5, -5);
  scene.add(bl);
  
  const modelPath = '<?php echo esc_entities($fullPath3D); ?>';
  const ext = modelPath.split('.').pop().toLowerCase();
  
  function addModel(obj) {
    const box = new THREE.Box3().setFromObject(obj);
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3());
    const scale = 2 / Math.max(size.x, size.y, size.z);
    obj.scale.setScalar(scale);
    obj.position.sub(center.multiplyScalar(scale));
    obj.traverse(c => { if (c.isMesh) c.material = new THREE.MeshStandardMaterial({color:0xcccccc,roughness:0.5,metalness:0.3}); });
    scene.add(obj);
  }
  
  if (ext === 'obj') new OBJLoader().load(modelPath, addModel, undefined, e => console.error('Load error:', e));
  else if (ext === 'stl') new STLLoader().load(modelPath, g => addModel(new THREE.Mesh(g)), undefined, e => console.error('Load error:', e));
  
  function animate() {
    if (!document.getElementById('fullscreen-3d-modal').classList.contains('d-none')) {
      requestAnimationFrame(animate);
      controls.update();
      renderer.render(scene, camera);
    }
  }
  animate();
  
  window.addEventListener('resize', () => {
    if (camera && renderer) {
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(window.innerWidth, window.innerHeight);
    }
  });
};

window.resetThreeJsCamera = function() { if (camera && controls) { camera.position.set(0,1,3); controls.reset(); } };
window.toggleThreeJsAutoRotate = function() { autoRotate = !autoRotate; if (controls) controls.autoRotate = autoRotate; };
window.cleanupThreeJs = function() { if (renderer) { renderer.dispose(); renderer = null; } };
</script>
<?php endif; ?>
<?php endif; ?>
<?php end_slot(); ?>
