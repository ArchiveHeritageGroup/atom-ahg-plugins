<?php decorate_with('layout_1col.php'); ?>
<?php use_helper('Javascript'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Reset Your Password'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <?php echo $form->renderFormTag(url_for(['module' => 'user', 'action' => 'passwordResetConfirm', 'token' => $sf_request->getParameter('token')]), ['id' => 'passwordResetConfirmForm']); ?>

    <?php echo $form->renderHiddenFields(); ?>

    <section id="content">
      <fieldset class="collapsible">
        <legend><?php echo __('Enter your new password'); ?></legend>

        <?php $settings = json_encode([
            'password' => [
                'strengthTitle' => __('Password strength:'),
                'hasWeaknesses' => __('To make your password stronger:'),
                'tooShort' => __('Make it at least six characters'),
                'addLowerCase' => __('Add lowercase letters'),
                'addUpperCase' => __('Add uppercase letters'),
                'addNumbers' => __('Add numbers'),
                'addPunctuation' => __('Add punctuation'),
                'confirmSuccess' => __('Yes'),
                'confirmFailure' => __('No'),
                'confirmTitle' => __('Passwords match:'),
            ], ]); ?>

        <?php echo javascript_tag(<<<EOF
jQuery.extend(Drupal.settings, {$settings});
EOF
); ?>

        <?php echo $form->password->renderError(); ?>

        <div class="form-item password-parent">
          <?php echo $form->password
              ->label(__('New Password'))
              ->renderLabel(); ?>
          <?php echo $form->password->render(['class' => 'password-field']); ?>
        </div>

        <div class="form-item confirm-parent">
          <?php echo $form->confirmPassword
              ->label(__('Confirm Password'))
              ->renderLabel(); ?>
          <?php echo $form->confirmPassword->render(['class' => 'password-confirm']); ?>
        </div>

      </fieldset>
    </section>

    <section class="actions">
      <ul>
        <?php echo link_to(__('Cancel'), ['module' => 'user', 'action' => 'login'], ['class' => 'c-btn']); ?>
        <input class="c-btn c-btn-submit" type="submit" value="<?php echo __('Reset Password'); ?>"/>
      </ul>
    </section>

  </form>

<?php end_slot(); ?>