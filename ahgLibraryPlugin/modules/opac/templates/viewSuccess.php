<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Catalog Record'); ?></h1>
<?php end_slot(); ?>

<?php
  $detail   = $sf_data->getRaw('detail');
  $item     = $detail['item'];
  $creators = $detail['creators'];
  $subjects = $detail['subjects'];
  $copies   = $detail['copies'];
  $holdCount = $detail['hold_count'];
?>

<!-- Back to search -->
<div class="mb-3">
  <a href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>" class="text-decoration-none">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to catalog'); ?>
  </a>
</div>

<!-- Title & Author -->
<h2 class="mb-1"><?php echo esc_entities($item->title ?? __('Untitled')); ?></h2>
<?php if (!empty($creators)): ?>
  <p class="lead text-muted mb-4">
    <?php
      $creatorNames = array_map(function ($c) { return esc_entities($c->name); }, $creators);
      echo implode('; ', $creatorNames);
    ?>
  </p>
<?php endif; ?>

<div class="row">
  <!-- Left Column: Details -->
  <div class="col-md-8">
    <div class="card mb-4">
      <div class="card-header fw-bold"><?php echo __('Bibliographic Details'); ?></div>
      <div class="card-body">
        <table class="table table-borderless mb-0">
          <tbody>
            <?php if (!empty($item->material_type)): ?>
              <tr>
                <th style="width: 200px;"><?php echo __('Material Type'); ?></th>
                <td><span class="badge bg-info text-dark"><?php echo esc_entities($item->material_type); ?></span></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->edition)): ?>
              <tr>
                <th><?php echo __('Edition'); ?></th>
                <td><?php echo esc_entities($item->edition); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->publisher)): ?>
              <tr>
                <th><?php echo __('Publisher'); ?></th>
                <td><?php echo esc_entities($item->publisher); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->publication_place)): ?>
              <tr>
                <th><?php echo __('Place of Publication'); ?></th>
                <td><?php echo esc_entities($item->publication_place); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->publication_date)): ?>
              <tr>
                <th><?php echo __('Date'); ?></th>
                <td><?php echo esc_entities($item->publication_date); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->pagination)): ?>
              <tr>
                <th><?php echo __('Pagination'); ?></th>
                <td><?php echo esc_entities($item->pagination); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->dimensions)): ?>
              <tr>
                <th><?php echo __('Dimensions'); ?></th>
                <td><?php echo esc_entities($item->dimensions); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->series_title)): ?>
              <tr>
                <th><?php echo __('Series'); ?></th>
                <td><?php echo esc_entities($item->series_title); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->isbn)): ?>
              <tr>
                <th><?php echo __('ISBN'); ?></th>
                <td><?php echo esc_entities($item->isbn); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->issn)): ?>
              <tr>
                <th><?php echo __('ISSN'); ?></th>
                <td><?php echo esc_entities($item->issn); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->call_number)): ?>
              <tr>
                <th><?php echo __('Call Number'); ?></th>
                <td><strong><?php echo esc_entities($item->call_number); ?></strong></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->classification_scheme)): ?>
              <tr>
                <th><?php echo __('Classification'); ?></th>
                <td><?php echo esc_entities($item->classification_scheme); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->notes)): ?>
              <tr>
                <th><?php echo __('Notes'); ?></th>
                <td><?php echo nl2br(esc_entities($item->notes)); ?></td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($item->scope_and_content)): ?>
              <tr>
                <th><?php echo __('Summary'); ?></th>
                <td><?php echo nl2br(esc_entities($item->scope_and_content)); ?></td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Subjects -->
    <?php if (!empty($subjects)): ?>
      <div class="card mb-4">
        <div class="card-header fw-bold"><?php echo __('Subjects'); ?></div>
        <div class="card-body">
          <?php foreach ($subjects as $subj): ?>
            <a href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($subj->heading ?? ''); ?>&search_type=subject"
               class="badge bg-secondary text-decoration-none me-1 mb-1" style="font-size: 0.9em;">
              <?php echo esc_entities($subj->heading ?? ''); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Citation -->
    <div class="card mb-4">
      <div class="card-header fw-bold"><?php echo __('Citation (APA)'); ?></div>
      <div class="card-body">
        <p class="mb-0 fst-italic">
          <?php
            // Build APA citation: Author(s). (Year). Title. Place: Publisher.
            $authorStr = '';
            if (!empty($creators)) {
                $names = [];
                foreach ($creators as $c) {
                    $names[] = esc_entities($c->name);
                }
                $authorStr = implode(', ', $names);
            }
            $year = !empty($item->publication_date) ? substr($item->publication_date, 0, 4) : 'n.d.';
            $title = esc_entities($item->title ?? '');
            $place = !empty($item->publication_place) ? esc_entities($item->publication_place) : '';
            $pub   = !empty($item->publisher) ? esc_entities($item->publisher) : '';

            $citation = '';
            if ($authorStr) {
                $citation .= $authorStr . '. ';
            }
            $citation .= '(' . $year . '). ';
            $citation .= '<em>' . $title . '</em>. ';
            if ($place && $pub) {
                $citation .= $place . ': ' . $pub . '.';
            } elseif ($pub) {
                $citation .= $pub . '.';
            }
            echo $citation;
          ?>
        </p>
      </div>
    </div>
  </div>

  <!-- Right Column: Availability & Hold -->
  <div class="col-md-4">
    <!-- Availability -->
    <div class="card mb-4">
      <div class="card-header fw-bold"><?php echo __('Availability'); ?></div>
      <div class="card-body">
        <?php if (!empty($copies)): ?>
          <table class="table table-sm table-striped mb-3">
            <thead>
              <tr>
                <th><?php echo __('Copy'); ?></th>
                <th><?php echo __('Barcode'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Location'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($copies as $copy): ?>
                <tr>
                  <td><?php echo (int) ($copy->copy_number ?? 0); ?></td>
                  <td><small><?php echo esc_entities($copy->barcode ?? ''); ?></small></td>
                  <td>
                    <?php
                      $status = $copy->copy_status ?? 'unknown';
                      $badgeClass = 'bg-secondary';
                      if ($status === 'available') {
                          $badgeClass = 'bg-success';
                      } elseif ($status === 'checked_out' || $status === 'on_loan') {
                          $badgeClass = 'bg-warning text-dark';
                      } elseif ($status === 'on_hold' || $status === 'in_transit') {
                          $badgeClass = 'bg-info text-dark';
                      } elseif ($status === 'damaged' || $status === 'lost' || $status === 'missing') {
                          $badgeClass = 'bg-danger';
                      }
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $status))); ?></span>
                  </td>
                  <td><small><?php echo esc_entities($copy->location ?? ''); ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted mb-0"><?php echo __('No copies recorded.'); ?></p>
        <?php endif; ?>

        <?php if ($holdCount > 0): ?>
          <p class="text-muted small mb-3">
            <i class="fas fa-clock me-1"></i><?php echo __('%1% hold(s) pending', ['%1%' => $holdCount]); ?>
          </p>
        <?php endif; ?>

        <!-- Place Hold -->
        <?php if ($sf_user->isAuthenticated()): ?>
          <form method="post" action="<?php echo url_for(['module' => 'opac', 'action' => 'hold']); ?>">
            <input type="hidden" name="library_item_id" value="<?php echo (int) $item->id; ?>">
            <div class="mb-2">
              <input type="text" name="hold_notes" class="form-control form-control-sm" placeholder="<?php echo __('Notes (optional)'); ?>">
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-hand-paper me-1"></i><?php echo __('Place Hold'); ?>
            </button>
          </form>
        <?php else: ?>
          <p class="text-muted small">
            <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>"><?php echo __('Log in'); ?></a>
            <?php echo __('to place a hold on this item.'); ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
