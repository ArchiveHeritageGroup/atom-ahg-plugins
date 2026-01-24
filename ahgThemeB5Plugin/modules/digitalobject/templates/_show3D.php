<?php
/**
 * 3D Model Viewer Component - AHG Theme
 * Supports GLB, GLTF, OBJ, STL, FBX, PLY, DAE
 */

$fullPath = $resource->path . $resource->name;
$ext = strtolower(pathinfo($resource->name, PATHINFO_EXTENSION));
$viewerId = 'viewer-' . uniqid();
?>

<div class="digitalObject3D">
  <div class="d-flex flex-column align-items-center">
    <div class="mb-2">
      <span class="badge bg-primary"><i class="fas fa-cube me-1"></i><?php echo esc_entities($resource->name); ?> (3D)</span>
    </div>
    
    <div id="<?php echo $viewerId; ?>-container" style="width: 100%; height: 400px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 8px; position: relative;">
      <?php if (in_array($ext, ['glb', 'gltf'])): ?>
        <script type="module" src="https://unpkg.com/@google/model-viewer@3.4.0/dist/model-viewer.min.js"></script>
        <model-viewer 
          id="<?php echo $viewerId; ?>"
          src="<?php echo esc_entities($fullPath); ?>" 
          camera-controls 
          touch-action="pan-y" 
          auto-rotate
          shadow-intensity="1"
          exposure="1"
          style="width:100%;height:100%;background:transparent;border-radius:8px;">
          <div slot="poster" class="d-flex flex-column align-items-center justify-content-center h-100 text-white">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <span><?php echo __('Loading 3D model...'); ?></span>
          </div>
        </model-viewer>
      <?php else: ?>
        <div id="<?php echo $viewerId; ?>-threejs" style="width:100%;height:100%;border-radius:8px;"></div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/OBJLoader.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
        <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        (function() {
          const container = document.getElementById('<?php echo $viewerId; ?>-threejs');
          if (!container) return;
          const scene = new THREE.Scene();
          scene.background = new THREE.Color(0x1a1a2e);
          const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
          camera.position.set(0, 1, 3);
          const renderer = new THREE.WebGLRenderer({ antialias: true });
          renderer.setSize(container.clientWidth, container.clientHeight);
          renderer.setPixelRatio(window.devicePixelRatio);
          container.appendChild(renderer.domElement);
          const controls = new THREE.OrbitControls(camera, renderer.domElement);
          controls.enableDamping = true;
          controls.autoRotate = true;
          scene.add(new THREE.AmbientLight(0xffffff, 0.6));
          const dl = new THREE.DirectionalLight(0xffffff, 0.8);
          dl.position.set(5, 10, 7.5);
          scene.add(dl);
          
          function centerAndScale(obj) {
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
          if (ext === 'obj') new THREE.OBJLoader().load('<?php echo esc_entities($fullPath); ?>', centerAndScale);
          else if (ext === 'stl') new THREE.STLLoader().load('<?php echo esc_entities($fullPath); ?>', g => centerAndScale(new THREE.Mesh(g)));
          
          (function animate() { requestAnimationFrame(animate); controls.update(); renderer.render(scene, camera); })();
          window.addEventListener('resize', () => { camera.aspect = container.clientWidth / container.clientHeight; camera.updateProjectionMatrix(); renderer.setSize(container.clientWidth, container.clientHeight); });
        })();
        </script>
      <?php endif; ?>
      
      <button onclick="open3DFullscreen('<?php echo esc_entities($fullPath); ?>', '<?php echo $ext; ?>')" class="btn btn-sm btn-primary position-absolute" style="bottom: 10px; right: 10px; z-index: 10;">
        <i class="fas fa-expand me-1"></i><?php echo __('Fullscreen'); ?>
      </button>
    </div>
    
    <small class="text-muted mt-2">
      <i class="fas fa-mouse me-1"></i><?php echo __('Drag to rotate'); ?> | <i class="fas fa-search-plus me-1"></i><?php echo __('Scroll to zoom'); ?>
    </small>
  </div>
</div>
