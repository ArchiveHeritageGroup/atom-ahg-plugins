<div class="modal fade" id="digitalObjectHelpModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">📷 Importing Digital Objects (Images/Files)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6>Vernon CMS / Museum Systems Export</h6>
        <p>Most collection management systems export digital objects in two parts:</p>
        <ol>
          <li><strong>Metadata CSV/XML</strong> - Contains a column with filename or path</li>
          <li><strong>Media folder</strong> - Contains the actual image/document files</li>
        </ol>
        
        <h6 class="mt-4">AtoM Digital Object Fields</h6>
        <table class="table table-sm">
          <thead>
            <tr><th>Field</th><th>Use For</th><th>Example</th></tr>
          </thead>
          <tbody>
            <tr>
              <td><code>digitalObjectPath</code></td>
              <td>Local file path (relative to import folder)</td>
              <td><code>images/photo001.jpg</code></td>
            </tr>
            <tr>
              <td><code>digitalObjectURI</code></td>
              <td>URL to remote file</td>
              <td><code>https://cdn.example.com/photo001.jpg</code></td>
            </tr>
          </tbody>
        </table>
        
        <h6 class="mt-4">Folder Structure for Import</h6>
        <pre class="bg-light p-2 rounded"><code>/uploads/imports/my_import/
├── import.csv
└── media/
    ├── IMG_001.jpg
    ├── IMG_002.tif
    └── DOC_003.pdf</code></pre>
        
        <h6 class="mt-4">CSV Example</h6>
        <pre class="bg-light p-2 rounded"><code>identifier,title,digitalObjectPath
2024-001,"Historic Photo","media/IMG_001.jpg"
2024-002,"Map of Region","media/IMG_002.tif"</code></pre>
        
        <h6 class="mt-4">Vernon CMS Specific</h6>
        <p>Common Vernon export fields to map:</p>
        <ul>
          <li><code>object_number</code> → <code>identifier</code></li>
          <li><code>title</code> → <code>title</code></li>
          <li><code>image_filename</code> → <code>digitalObjectPath</code></li>
          <li><code>description</code> → <code>scopeAndContent</code></li>
          <li><code>creator</code> → <code>eventActors</code></li>
          <li><code>date_created</code> → <code>eventDates</code></li>
        </ul>
        
        <div class="alert alert-info mt-4">
          <strong>Tip:</strong> If your source has full paths like <code>C:\Vernon\Images\photo.jpg</code>, 
          use the <strong>Constant</strong> field with <strong>Prepend</strong> unchecked to transform paths,
          or process your CSV beforehand to extract just the filename.
        </div>
        
        <h6 class="mt-4">Supported File Types</h6>
        <div class="row">
          <div class="col-md-4">
            <strong>Images</strong>
            <ul class="small">
              <li>JPEG, PNG, GIF, TIFF</li>
              <li>BMP, WebP</li>
            </ul>
          </div>
          <div class="col-md-4">
            <strong>Documents</strong>
            <ul class="small">
              <li>PDF, PDF/A</li>
              <li>Word, Excel</li>
            </ul>
          </div>
          <div class="col-md-4">
            <strong>Audio/Video</strong>
            <ul class="small">
              <li>MP3, WAV, MP4</li>
              <li>MOV, AVI</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
