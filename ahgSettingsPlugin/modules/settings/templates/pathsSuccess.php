<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>

  <?php echo get_component('settings', 'menu') ?>

<?php end_slot() ?>

<?php slot('title') ?>

  <h1><?php echo __('Site Paths Setup') ?></h1>

<?php end_slot() ?>

<?php slot('content') ?>

  <form action="<?php echo url_for('settings/paths') ?>" method="post">
<th><?php echo __('Name') ?></th>
    <div id="content">

      <table class="table sticky-enabled">
        <thead>
          <tr>
            <th><?php echo __('Name') ?></th>
            <th><?php echo __('Value') ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?php echo $pathsForm['bulk']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['bulk']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['bulk']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['bulk_index']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['bulk_index']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['bulk_index']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['bulk_optimize_index']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['bulk_optimize_index']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['bulk_optimize_index']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['bulk_rename']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['bulk_rename']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['bulk_rename']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['bulk_verbose']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['bulk_verbose']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['bulk_verbose']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['bulk_output']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['bulk_output']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['bulk_output']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['bulk_skip_duplicates']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['bulk_skip_duplicates']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['bulk_skip_duplicates']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['output_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['output_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['output_path']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['output_filename']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['output_filename']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['output_filename']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['bulk_delete']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['bulk_delete']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['bulk_delete']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['log']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['log']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['log']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['log_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['log_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['log_path']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['log_filename']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['log_filename']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['log_filename']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['move']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['move']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['move']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['move_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['move_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['move_path']->render() ?>
            </td>
          </tr>


          <tr>
            <td><?php echo $pathsForm['upload_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['upload_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['upload_path']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['download_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['download_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['download_path']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['unpublish_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['unpublish_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['unpublish_path']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['publish_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['publish_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['publish_path']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['update_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['update_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['update_path']->render() ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $pathsForm['mq_path']->renderLabel(null,
              array('title' => __('To Do'))) ?></td>
            <td>
              <?php if (strlen($error = $pathsForm['mq_path']->renderError())) { ?>
                <?php echo $error ?>
              <?php } ?>
              <?php echo $pathsForm['mq_path']->render() ?>
            </td>
          </tr>
        </tbody>
      </table>

    </div>

    <section class="actions">
      <ul>
        <li><input class="c-btn c-btn-submit" type="submit" value="<?php echo __('Save') ?>"/></li>
      </ul>
    </section>

  </form>

<?php end_slot() ?>
