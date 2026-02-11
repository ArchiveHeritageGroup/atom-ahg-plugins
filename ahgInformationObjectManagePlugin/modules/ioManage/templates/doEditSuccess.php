<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">
      <?php echo __('Edit digital object'); ?>
    </h1>
    <span class="small">
      <?php echo esc_specialchars($digitalObject['name'] ?: __('Untitled')); ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php $rawDo = $sf_data->getRaw('digitalObject'); ?>
  <?php $rawProps = $sf_data->getRaw('properties'); ?>
  <?php $rawMediaTypes = $sf_data->getRaw('mediaTypes'); ?>
  <?php $rawMetadata = $sf_data->getRaw('metadata'); ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <div class="row">

    <!-- Preview column -->
    <?php if ($thumbnailUrl || $referenceUrl) { ?>
      <div class="col-md-4 mb-3">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Preview'); ?></h5>
          </div>
          <div class="card-body text-center">
            <?php if ($referenceUrl) { ?>
              <img src="<?php echo $referenceUrl; ?>" alt="<?php echo esc_specialchars($rawProps['altText']); ?>" class="img-fluid rounded" style="max-height: 400px;">
            <?php } elseif ($thumbnailUrl) { ?>
              <img src="<?php echo $thumbnailUrl; ?>" alt="<?php echo esc_specialchars($rawProps['altText']); ?>" class="img-fluid rounded">
            <?php } ?>
          </div>
        </div>
      </div>
    <?php } ?>

    <!-- Form column -->
    <div class="<?php echo ($thumbnailUrl || $referenceUrl) ? 'col-md-8' : 'col-12'; ?>">

      <form method="post" action="<?php echo url_for('@io_do_edit?id=' . $rawDo['id']); ?>">

        <?php echo $form->renderHiddenFields(); ?>

        <!-- Read-only file information -->
        <div class="card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('File information'); ?></h5>
          </div>
          <div class="card-body">

            <div class="row mb-2">
              <div class="col-sm-4 fw-bold"><?php echo __('Filename'); ?></div>
              <div class="col-sm-8"><?php echo esc_specialchars($rawDo['name']); ?></div>
            </div>

            <div class="row mb-2">
              <div class="col-sm-4 fw-bold"><?php echo __('MIME type'); ?></div>
              <div class="col-sm-8"><?php echo esc_specialchars($rawDo['mimeType']); ?></div>
            </div>

            <div class="row mb-2">
              <div class="col-sm-4 fw-bold"><?php echo __('File size'); ?></div>
              <div class="col-sm-8"><?php echo esc_specialchars($fileSizeFormatted); ?></div>
            </div>

            <?php if (!empty($rawDo['checksum'])) { ?>
              <div class="row mb-2">
                <div class="col-sm-4 fw-bold"><?php echo __('Checksum'); ?></div>
                <div class="col-sm-8">
                  <code class="small"><?php echo esc_specialchars($rawDo['checksum']); ?></code>
                  <?php if (!empty($rawDo['checksumType'])) { ?>
                    <span class="text-muted small">(<?php echo esc_specialchars($rawDo['checksumType']); ?>)</span>
                  <?php } ?>
                </div>
              </div>
            <?php } ?>

            <div class="row mb-2">
              <div class="col-sm-4 fw-bold"><?php echo __('Usage'); ?></div>
              <div class="col-sm-8"><?php echo esc_specialchars($usageName); ?></div>
            </div>

            <div class="row mb-2">
              <div class="col-sm-4 fw-bold"><?php echo __('Path'); ?></div>
              <div class="col-sm-8"><code class="small"><?php echo esc_specialchars($rawDo['path']); ?></code></div>
            </div>

          </div>
        </div>

        <!-- Editable fields -->
        <div class="card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><?php echo __('Editable properties'); ?></h5>
          </div>
          <div class="card-body">

            <div class="mb-3">
              <label for="altText" class="form-label"><?php echo __('Alternative text'); ?></label>
              <input type="text" class="form-control" id="altText" name="altText"
                     value="<?php echo esc_specialchars($rawProps['altText']); ?>"
                     placeholder="<?php echo __('Describe the image for accessibility'); ?>">
              <div class="form-text text-muted small">
                <?php echo __('Used for screen readers and when the image cannot be displayed.'); ?>
              </div>
            </div>

            <div class="mb-3">
              <label for="mediaTypeId" class="form-label"><?php echo __('Media type'); ?></label>
              <select class="form-select" id="mediaTypeId" name="mediaTypeId">
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawMediaTypes as $mt) { ?>
                  <option value="<?php echo $mt->id; ?>"
                          <?php echo ($mt->id == $rawDo['mediaTypeId']) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($mt->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="displayAsCompound" name="displayAsCompound" value="1"
                     <?php echo $rawProps['displayAsCompound'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="displayAsCompound">
                <?php echo __('Display as compound digital object'); ?>
              </label>
              <div class="form-text text-muted small">
                <?php echo __('When enabled, child digital objects are displayed with a pager.'); ?>
              </div>
            </div>

          </div>
        </div>

        <!-- Derivatives list -->
        <?php if (!empty($rawDo['derivatives'])) { ?>
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="mb-0"><?php echo __('Derivatives'); ?></h5>
            </div>
            <div class="card-body">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th><?php echo __('Type'); ?></th>
                    <th><?php echo __('Filename'); ?></th>
                    <th><?php echo __('MIME type'); ?></th>
                    <th><?php echo __('Size'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rawDo['derivatives'] as $type => $deriv) { ?>
                    <?php if ($deriv) { ?>
                      <tr>
                        <td><?php echo esc_specialchars(ucfirst($type)); ?></td>
                        <td><?php echo esc_specialchars($deriv['name']); ?></td>
                        <td><?php echo esc_specialchars($deriv['mimeType']); ?></td>
                        <td><?php echo \AhgInformationObjectManage\Services\DigitalObjectService::formatFileSize($deriv['byteSize']); ?></td>
                      </tr>
                    <?php } ?>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php } ?>

        <!-- Extended metadata (from digital_object_metadata) -->
        <?php if (!empty($rawMetadata)) { ?>
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="mb-0"><?php echo __('Extracted metadata'); ?></h5>
            </div>
            <div class="card-body">
              <?php
              $displayFields = [
                  'file_type' => __('File type'),
                  'title' => __('Title'),
                  'creator' => __('Creator'),
                  'description' => __('Description'),
                  'keywords' => __('Keywords'),
                  'copyright' => __('Copyright'),
                  'date_created' => __('Date created'),
                  'image_width' => __('Width'),
                  'image_height' => __('Height'),
                  'camera_make' => __('Camera make'),
                  'camera_model' => __('Camera model'),
                  'page_count' => __('Page count'),
                  'duration_formatted' => __('Duration'),
                  'video_codec' => __('Video codec'),
                  'audio_codec' => __('Audio codec'),
                  'resolution' => __('Resolution'),
                  'sample_rate' => __('Sample rate'),
                  'artist' => __('Artist'),
                  'album' => __('Album'),
              ];
              ?>
              <?php foreach ($displayFields as $key => $label) { ?>
                <?php if (!empty($rawMetadata[$key])) { ?>
                  <div class="row mb-1">
                    <div class="col-sm-4 fw-bold"><?php echo $label; ?></div>
                    <div class="col-sm-8"><?php echo esc_specialchars((string) $rawMetadata[$key]); ?></div>
                  </div>
                <?php } ?>
              <?php } ?>
            </div>
          </div>
        <?php } ?>

        <ul class="actions mb-3 nav gap-2">
          <li>
            <?php
            $cancelUrl = $ioSlug ? '/' . $ioSlug : '/';
            echo link_to(
                __('Cancel'),
                $cancelUrl,
                ['class' => 'btn atom-btn-outline-light', 'role' => 'button']
            );
            ?>
          </li>
          <li>
            <input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>">
          </li>
        </ul>

      </form>

    </div>
  </div>

<?php end_slot(); ?>
