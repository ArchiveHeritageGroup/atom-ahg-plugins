# O3DV (Online 3D Viewer) Evaluation

**Date:** 2026-03-14
**Author:** The Archive and Heritage Group
**Context:** Issue #229 Sub-issue 8 — Evaluate O3DV as alternative/complement to model-viewer + Three.js

---

## Overview

[Online 3D Viewer](https://3dviewer.net/) (O3DV) is a free, open-source web solution for visualizing 3D models in the browser. Available as an npm package (`online-3d-viewer`, v0.18.0).

## Comparison

| Criteria | Current Stack (model-viewer + Three.js) | O3DV |
|----------|----------------------------------------|------|
| **License** | Apache 2.0 (model-viewer) / MIT (Three.js) | MIT |
| **Format Support** | 6 formats: GLB, GLTF, OBJ, STL, PLY, USDZ | 18+ formats: 3dm, 3ds, 3mf, amf, bim, brep, dae, fbx, fcstd, gltf, ifc, iges, step, stl, obj, off, ply, wrl |
| **Bundle Size** | ~1.5MB (Three.js 603KB + model-viewer 902KB + loaders ~58KB) | ~2.5MB (single bundle, includes all loaders) |
| **AR Support** | Native (model-viewer: WebXR, iOS Quick Look, Android Scene Viewer) | None |
| **Hotspot Support** | Native (model-viewer `<button slot="hotspot">`) | None (would need custom overlay) |
| **Web Component** | Yes (model-viewer is a custom element) | No (imperative JS API) |
| **Gaussian Splat** | Supported via GaussianSplats3D library | Not supported |
| **IIIF Integration** | Already integrated in ahg3DModelPlugin | Would need new integration |
| **CAD Formats** | Not supported | IFC, IGES, STEP, BIM, BREP, FCSTD |
| **Maturity** | model-viewer: Google-maintained, stable | Community project, active development |
| **Accessibility** | model-viewer: ARIA labels, keyboard nav | Limited |
| **Mobile** | Excellent (model-viewer optimized for touch) | Good |

## Strengths of O3DV

1. **CAD format support** — IFC, STEP, IGES are valuable for architectural heritage and engineering museums
2. **FBX support** — O3DV includes FBX parsing (our stack lacks FBXLoader.js)
3. **Single library** — One package handles all formats (no separate loaders needed)
4. **Measurement tools** — Built-in measuring capabilities useful for museum/conservation
5. **MIT license** — Permissive, compatible with our stack

## Weaknesses of O3DV

1. **No AR** — Critical gap. model-viewer's AR support is a key GLAM feature
2. **No hotspot system** — Would lose all hotspot functionality (annotation, damage, condition linking)
3. **Larger bundle** — ~2.5MB vs ~1.5MB for current stack
4. **No web component** — Would require significant template refactoring
5. **No Gaussian Splat** — Growing format for photogrammetry in heritage digitization
6. **Migration cost** — All templates, JS code, hotspot rendering, IIIF manifests would need rewriting

## Recommendation

**PARK — Do not adopt O3DV as primary viewer.**

The current model-viewer + Three.js stack is well-suited for GLAM/DAM use cases:
- AR support is a differentiator for museums and exhibitions
- Hotspot system (annotation, damage, condition assessment) is deeply integrated
- Google-backed model-viewer has long-term stability
- Gaussian Splat support aligns with heritage photogrammetry trends

**Consider O3DV for specific use cases only:**
- If CAD format support (IFC/STEP/IGES) becomes a requirement for architectural heritage, O3DV could be loaded as a fallback viewer for those formats only
- This would be a targeted addition, not a replacement

**No code changes needed for this evaluation.**
