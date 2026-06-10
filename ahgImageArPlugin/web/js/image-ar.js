/* #147 — 2D image AR: WebXR hit-test placement of a flat textured plane.
 * Uses three.js core only (no addons) to stay CSP/CDN-light. */
import * as THREE from 'three';

(function () {
  'use strict';

  var data = {};
  try { data = JSON.parse(document.getElementById('ar-data').textContent || '{}'); } catch (e) { data = {}; }

  var btnEnter = document.getElementById('ar-enter');
  var unsupported = document.getElementById('ar-unsupported');
  var statusEl = document.getElementById('ar-status');
  var overlay = document.getElementById('ar-overlay');
  var btnExit = document.getElementById('ar-exit');
  var hint = document.getElementById('ar-hint');

  function setStatus(t) { if (statusEl) { statusEl.textContent = t || ''; } }

  // Feature-detect WebXR immersive-ar.
  if (!('xr' in navigator) || !navigator.xr || !navigator.xr.isSessionSupported) {
    if (unsupported) { unsupported.classList.remove('d-none'); }
    return;
  }
  navigator.xr.isSessionSupported('immersive-ar').then(function (ok) {
    if (ok) { btnEnter.classList.remove('d-none'); }
    else if (unsupported) { unsupported.classList.remove('d-none'); }
  }).catch(function () { if (unsupported) { unsupported.classList.remove('d-none'); } });

  var renderer, scene, camera, reticle, controller;
  var hitTestSource = null, hitTestRequested = false;
  var placedMesh = null, planeGeoAspect = 1;
  var texture = null;

  function initGL() {
    var canvas = document.createElement('canvas');
    renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.xr.enabled = true;
    document.body.appendChild(renderer.domElement);
    renderer.domElement.style.display = 'none';

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(70, window.innerWidth / window.innerHeight, 0.01, 40);

    var light = new THREE.HemisphereLight(0xffffff, 0xbbbbff, 1.1);
    scene.add(light);

    // Reticle: a thin ring laid flat, shown where a surface is detected.
    var ringGeo = new THREE.RingGeometry(0.07, 0.09, 32).rotateX(-Math.PI / 2);
    reticle = new THREE.Mesh(ringGeo, new THREE.MeshBasicMaterial({ color: 0x4caf50 }));
    reticle.matrixAutoUpdate = false;
    reticle.visible = false;
    scene.add(reticle);

    // Image texture for the plane to place.
    var loader = new THREE.TextureLoader();
    loader.setCrossOrigin('anonymous');
    texture = loader.load(data.url, function (tex) {
      if (tex.image && tex.image.height) { planeGeoAspect = tex.image.width / tex.image.height; }
      tex.colorSpace = THREE.SRGBColorSpace;
    });

    controller = renderer.xr.getController(0);
    controller.addEventListener('select', onSelect);
    scene.add(controller);
  }

  function onSelect() {
    if (!reticle.visible || !texture) { return; }
    var w = 0.6;                       // 0.6 m wide by default
    var h = w / (planeGeoAspect || 1);
    var geo = new THREE.PlaneGeometry(w, h);
    var mat = new THREE.MeshBasicMaterial({ map: texture, side: THREE.DoubleSide, transparent: true });
    var mesh = new THREE.Mesh(geo, mat);
    // Place at reticle pose; stand the plane upright (it lies flat by default on the ring).
    mesh.position.setFromMatrixPosition(reticle.matrix);
    mesh.quaternion.setFromRotationMatrix(reticle.matrix);
    mesh.rotateX(Math.PI / 2); // lift from floor-plane to face the viewer
    scene.add(mesh);
    placedMesh = mesh;
    if (hint) { hint.textContent = 'Placed. Tap again to move it.'; }
    // subsequent taps re-place
    if (placedMesh && placedMesh !== mesh) { scene.remove(placedMesh); }
  }

  function onXRFrame(t, frame) {
    var session = renderer.xr.getSession();
    var refSpace = renderer.xr.getReferenceSpace();

    if (!hitTestRequested) {
      session.requestReferenceSpace('viewer').then(function (viewerSpace) {
        session.requestHitTestSource({ space: viewerSpace }).then(function (src) { hitTestSource = src; });
      });
      session.addEventListener('end', cleanup);
      hitTestRequested = true;
    }

    if (hitTestSource) {
      var results = frame.getHitTestResults(hitTestSource);
      if (results.length) {
        var pose = results[0].getPose(refSpace);
        reticle.visible = !placedMesh; // hide reticle once placed
        reticle.matrix.fromArray(pose.transform.matrix);
      } else {
        reticle.visible = false;
      }
    }
    renderer.render(scene, camera);
  }

  function cleanup() {
    hitTestSource = null; hitTestRequested = false; placedMesh = null;
    if (overlay) { overlay.style.display = 'none'; }
    if (renderer) { renderer.setAnimationLoop(null); renderer.domElement.style.display = 'none'; }
  }

  function startAR() {
    if (!renderer) { initGL(); }
    var opts = { requiredFeatures: ['hit-test'] };
    if (overlay) { opts.optionalFeatures = ['dom-overlay']; opts.domOverlay = { root: overlay }; }
    navigator.xr.requestSession('immersive-ar', opts).then(function (session) {
      overlay.style.display = 'block';
      renderer.domElement.style.display = 'block';
      renderer.xr.setReferenceSpaceType('local');
      renderer.xr.setSession(session);
      renderer.setAnimationLoop(onXRFrame);
      setStatus('');
    }).catch(function (err) {
      setStatus('Could not start AR: ' + (err && err.message ? err.message : err));
    });
  }

  btnEnter.addEventListener('click', startAR);
  if (btnExit) {
    btnExit.addEventListener('click', function () {
      var s = renderer && renderer.xr.getSession();
      if (s) { s.end(); }
    });
  }
  window.addEventListener('resize', function () {
    if (!renderer) { return; }
    camera.aspect = window.innerWidth / window.innerHeight; camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
  });
})();
