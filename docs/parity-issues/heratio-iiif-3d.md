Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** iiif-3d

### Features to add to Heratio (present in PSIS/AtoM)
- **[low]** Multi-module architecture (iiifCollection, iiifAuth, mediaSettings, media, model3d, model3dSettings, ricExplorer, ricDashboard, ricSemanticSearch) — _PSIS plugin: ahgIiifPlugin/modules/*, ahg3DModelPlugin/modules/*, ahgRicExplorerPlugin/modules/*_: AtoM plugins organize functionality into multiple Symfony 1.4 modules (iiif, iiifAuth, iiifContent, iiifCollection, mediaSettings, media, threeDReports) with separate action classes and templates; Heratio uses single package with Controllers structure

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.