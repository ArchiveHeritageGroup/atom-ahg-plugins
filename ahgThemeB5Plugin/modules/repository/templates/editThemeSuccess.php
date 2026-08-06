<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
  <?php echo get_component('repository', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo render_title($resource); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php echo $form->renderGlobalErrors(); ?>

  <?php echo $form->renderFormTag(url_for([$resource, 'module' => 'repository', 'action' => 'editTheme'])); ?>

    <?php // NB: do NOT add the M12 _ahg_csrf_token field here - this is a base-AtoM
          // Symfony form with strict field validation (rejects unknown fields as
          // "Unexpected extra form field"), and its action is locked base code that
          // cannot set allow_extra_fields. The form is already CSRF-protected by base
          // AtoM's own _csrf_token in renderHiddenFields; the M12 log-mode "missing"
          // note for this endpoint is harmless. ?>
    <?php echo $form->renderHiddenFields(); ?>

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="style-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#style-collapse" aria-expanded="false" aria-controls="style-collapse">
            <?php echo __('Style'); ?>
          </button>
        </h2>
        <div id="style-collapse" class="accordion-collapse collapse" aria-labelledby="style-heading">
          <div class="accordion-body">
            <?php echo render_field($form->backgroundColor); ?>

            <?php echo render_field($form->banner); ?>

            <?php echo render_field($form->logo); ?>
          </div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header" id="content-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#content-collapse" aria-expanded="false" aria-controls="content-collapse">
            <?php echo __('Page content'); ?>
          </button>
        </h2>
        <div id="content-collapse" class="accordion-collapse collapse" aria-labelledby="content-heading">
          <div class="accordion-body">
            <?php echo render_field($form->htmlSnippet
                ->label(__('Description'))
                ->help(__('Content in this area will appear below an uploaded banner and above the institution\'s description areas. It can be used to offer a summary of the institution\'s mandate, include a tag line or important information, etc. HTML and inline CSS can be used to style the contents.')), $resource, ['class' => 'resizable']); ?>
          </div>
        </div>
      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Cancel'), [$resource, 'module' => 'repository'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
    </ul>

  </form>

  <?php
  // Keep the open accordion panel open across a save.
  //
  // Both panels are markup-collapsed, so submitting the form reloads the page
  // with everything shut and the section just edited hidden again. On a form
  // this long that means hunting for your place after every save.
  //
  // Held in sessionStorage rather than on the server: it is a display
  // preference, it should not outlive the browser session, and it needs no
  // round trip. Inline script carries the CSP nonce - without it the policy
  // drops the block silently and the page merely goes back to its old
  // behaviour with nothing in the console to explain why.
  $n = sfConfig::get('csp_nonce', '');
  ?>
  <script <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
    (function () {
      var KEY = 'ahgRepositoryThemeAccordion';
      var accordion = document.querySelector('.accordion');

      if (!accordion || !window.sessionStorage) {
        return;
      }

      function remembered() {
        try {
          return window.sessionStorage.getItem(KEY);
        } catch (e) {
          return null;
        }
      }

      function remember(id) {
        try {
          if (id) {
            window.sessionStorage.setItem(KEY, id);
          } else {
            window.sessionStorage.removeItem(KEY);
          }
        } catch (e) {
          // Private browsing and quota failures are not worth breaking over.
        }
      }

      var open = remembered();

      if (open) {
        var panel = document.getElementById(open);
        var button = accordion.querySelector('[data-bs-target="#' + open + '"]');

        if (panel && button) {
          panel.classList.add('show');
          button.classList.remove('collapsed');
          button.setAttribute('aria-expanded', 'true');
        } else {
          // The panel has been renamed or removed since it was recorded.
          remember(null);
        }
      }

      accordion.addEventListener('shown.bs.collapse', function (event) {
        remember(event.target.id);
      });

      accordion.addEventListener('hidden.bs.collapse', function (event) {
        if (remembered() === event.target.id) {
          remember(null);
        }
      });
    })();
  </script>
<?php end_slot(); ?>
