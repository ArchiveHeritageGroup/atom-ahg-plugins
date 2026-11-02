# Mirador with the annotation editor

Builds `web/public/mirador/mirador-annotations.min.js`: Mirador 3.4.3 with
mirador-annotations 0.4.0 (the React 16 release; 0.5.0 needs React 17, which
Mirador 3.4.3 does not support) and `src/AhgAnnotationAdapter.js`, which saves
to this plugin's `/iiif/annotations` endpoints (catalog #294).

The record viewer loads this build only for users who may edit the record. Everyone
else gets the stock `mirador.min.js`.

    npm ci
    npx webpack --mode production
    cp dist/mirador-annotations.min.js* ../../web/public/mirador/

After a rebuild, change the `?v=` on the script URL in `web/js/iiif-viewer-manager.js`,
or browsers keep the old copy (static files are served immutable for 30 days).
