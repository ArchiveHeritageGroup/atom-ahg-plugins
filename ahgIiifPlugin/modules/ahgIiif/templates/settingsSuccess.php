<?php decorate_with('layout_1col') ?>

<?php slot('title') ?>
  <h1>IIIF Viewer Settings</h1>
<?php end_slot() ?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">IIIF Configuration</h5>
  </div>
  <div class="card-body">
    <p>Configure IIIF viewer settings in <code>apps/qubit/config/config.php</code>:</p>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Setting</th>
          <th>Current Value</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><code>app_iiif_enabled</code></td>
          <td><?php echo sfConfig::get('app_iiif_enabled', false) ? 'true' : 'false' ?></td>
          <td>Enable/disable IIIF viewer</td>
        </tr>
        <tr>
          <td><code>app_iiif_cantaloupe_url</code></td>
          <td><?php echo sfConfig::get('app_iiif_cantaloupe_url', 'http://localhost:8182/iiif/2') ?></td>
          <td>Public Cantaloupe URL</td>
        </tr>
        <tr>
          <td><code>app_iiif_cantaloupe_internal_url</code></td>
          <td><?php echo sfConfig::get('app_iiif_cantaloupe_internal_url', 'http://127.0.0.1:8182') ?></td>
          <td>Internal Cantaloupe URL (for server-side requests)</td>
        </tr>
        <tr>
          <td><code>app_iiif_base_url</code></td>
          <td><?php echo sfConfig::get('app_iiif_base_url', 'http://localhost') ?></td>
          <td>Base URL for manifests</td>
        </tr>
        <tr>
          <td><code>app_iiif_default_viewer</code></td>
          <td><?php echo sfConfig::get('app_iiif_default_viewer', 'openseadragon') ?></td>
          <td>Default viewer (openseadragon/mirador)</td>
        </tr>
        <tr>
          <td><code>app_iiif_viewer_height</code></td>
          <td><?php echo sfConfig::get('app_iiif_viewer_height', '600px') ?></td>
          <td>Default viewer height</td>
        </tr>
        <tr>
          <td><code>app_iiif_enable_annotations</code></td>
          <td><?php echo sfConfig::get('app_iiif_enable_annotations', true) ? 'true' : 'false' ?></td>
          <td>Enable annotation support</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
