<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Reset Password'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <?php echo $form->renderFormTag(url_for(['module' => 'user', 'action' => 'passwordReset']), ['id' => 'passwordResetForm']); ?>

    <?php echo $form->renderHiddenFields(); ?>

    <section id="content">
      <fieldset class="collapsible">
        <legend><?php echo __('Enter your email address'); ?></legend>
        
        <p><?php echo __('Enter the email address associated with your account and we will send you instructions to reset your password.'); ?></p>

        <?php echo $form->email->renderRow(); ?>

      </fieldset>
    </section>

    <section class="actions">
      <ul>
        <?php echo link_to(__('Cancel'), ['module' => 'user', 'action' => 'login'], ['class' => 'c-btn']); ?>
        <input class="c-btn c-btn-submit" type="submit" value="<?php echo __('Send Reset Instructions'); ?>"/>
      </ul>
    </section>

  </form>

<?php end_slot(); ?>