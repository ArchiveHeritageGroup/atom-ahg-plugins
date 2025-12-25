<?php use_helper('Date'); ?>
<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-shopping-cart me-2"></i><?php echo __('My Cart'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php echo $form->renderGlobalErrors(); ?>
<?php echo $form->renderFormTag(url_for([$resource, 'module' => 'cart', 'action' => 'browse']), ['id' => 'cartForm']); ?>
<?php echo $form->renderHiddenFields(); ?>

<!-- Cart Items -->
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <i class="fas fa-list me-2"></i><?php echo __('Cart Items'); ?>
    <?php if ($pager->getNbResults() > 0): ?>
      <span class="badge bg-light text-dark ms-2"><?php echo $pager->getNbResults(); ?> <?php echo __('items'); ?></span>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <?php if ($pager->getNbResults() == 0): ?>
      <div class="alert alert-info m-3">
        <i class="fas fa-info-circle me-2"></i><?php echo __('Your cart is empty.'); ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Archival Description'); ?></th>
              <th><?php echo __('Level of Description'); ?></th>
              <th class="text-center" style="width: 120px;"><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pager->getResults() as $item): ?>
              <?php $informationObjectsCart = QubitInformationObject::getById($item->archivalDescriptionId); ?>
              <?php if ($informationObjectsCart): ?>
                <tr>
                  <td>
                    <?php if (isset($informationObjectsCart->identifier)): ?>
                      <i class="fas fa-file-alt me-2 text-muted"></i>
                      <?php echo link_to(render_title($informationObjectsCart), [$informationObjectsCart, 'module' => 'informationobject']); ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($informationObjectsCart->levelOfDescription): ?>
                      <span class="badge bg-secondary"><?php echo $informationObjectsCart->levelOfDescription->getName(['cultureFallback' => true]); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php echo link_to('<i class="fas fa-trash"></i> ' . __('Remove'), [$resource, 'module' => 'cart', 'action' => 'removeCart', 'id' => $item->id], ['class' => 'btn btn-sm btn-outline-danger']); ?>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($pager->getNbResults() > 0): ?>
  <?php echo get_partial('default/pager', ['pager' => $pager]); ?>
<?php endif; ?>

<!-- Request to Publish Form -->
<?php if ($pager->getNbResults() > 0): ?>
  <div class="card mb-4">
    <div class="card-header bg-success text-white">
      <i class="fas fa-paper-plane me-2"></i><?php echo __('Request To Publish'); ?>
    </div>
    <div class="card-body">
      <p class="text-muted mb-4"><?php echo __('Complete the form below to request permission to publish the items in your cart.'); ?></p>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <?php echo $form->rtp_name
            ->label(__('Name'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
        <div class="col-md-6 mb-3">
          <?php echo $form->rtp_surname
            ->label(__('Surname'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <?php echo $form->rtp_phone
            ->label(__('Phone Number'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
        <div class="col-md-6 mb-3">
          <?php echo $form->rtp_email
            ->label(__('e-Mail Address'))
            ->renderRow(['class' => 'form-control', 'type' => 'email']); ?>
        </div>
      </div>
      
      <div class="mb-3">
        <?php echo $form->rtp_institution
          ->label(__('Institution'))
          ->renderRow(['class' => 'form-control']); ?>
      </div>
      
      <div class="mb-3">
        <?php echo $form->rtp_planned_use
          ->label(__('Planned use'))
          ->renderRow(['class' => 'form-control', 'rows' => 3]); ?>
      </div>
      
      <div class="mb-3">
        <?php echo $form->rtp_need_image_by
          ->label(__('Need image/s by'))
          ->renderRow(['class' => 'form-control']); ?>
      </div>
      
      <div class="mb-3">
        <?php echo $form->rtp_motivation
          ->label(__('Motivation'))
          ->renderRow(['class' => 'form-control', 'rows' => 4]); ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Actions -->
<section class="actions">
  <ul class="list-unstyled d-flex flex-wrap gap-2">
    <li><?php echo link_to(__('Back to Requests'), ['module' => 'requesttopublish', 'action' => 'browse'], ['class' => 'btn atom-btn-outline-light']); ?></li>
    <?php if ($pager->getNbResults() > 0): ?>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Submit Request'); ?>"></li>
    <?php endif; ?>
  </ul>
</section>

</form>

<?php end_slot(); ?>
