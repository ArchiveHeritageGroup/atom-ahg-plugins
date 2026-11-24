<?php
$community = $sf_data->getRaw('community');
$stewards = $sf_data->getRaw('stewards');
$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$field = $e($sf_data->getRaw('csrfField'));
$token = $e($sf_data->getRaw('csrfToken'));
$action = url_for('@icip_community_stewards?id='.(int) $community->id);
?>
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard'); ?>">ICIP</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_community_view?id='.(int) $community->id); ?>"><?php echo $e($community->name); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('Stewards'); ?></li>
        </ol>
    </nav>

    <h1><?php echo __('Community stewards'); ?></h1>
    <p><?php echo __('A steward can open records restricted by this community. Gender and initiated status are not recorded: a steward link is the only way those restrictions open.'); ?></p>

    <?php if (!$tableReady) { ?>
        <div class="alert alert-warning" role="alert"><?php echo __('The steward table is not installed yet.'); ?></div>
    <?php } else { ?>
        <?php if ($error) { ?>
            <div class="alert alert-danger" role="alert"><?php echo __($sf_data->getRaw('error')); ?></div>
        <?php } ?>

        <table class="table table-bordered">
            <caption><?php echo __('Stewards of this community'); ?></caption>
            <thead><tr><th scope="col"><?php echo __('User'); ?></th><th scope="col"><?php echo __('Added'); ?></th><th scope="col"><?php echo __('Action'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($stewards as $steward) { ?>
                    <tr>
                        <td><?php echo $e($steward->username ?: '#'.$steward->user_id); ?></td>
                        <td><?php echo $e($steward->created_at); ?></td>
                        <td>
                            <form method="post" action="<?php echo $action; ?>">
                                <input type="hidden" name="<?php echo $field; ?>" value="<?php echo $token; ?>">
                                <input type="hidden" name="form_action" value="remove">
                                <input type="hidden" name="steward_id" value="<?php echo (int) $steward->id; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo __('Remove'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (!$stewards) { ?>
                    <tr><td colspan="3"><?php echo __('No stewards yet.'); ?></td></tr>
                <?php } ?>
            </tbody>
        </table>

        <form method="post" action="<?php echo $action; ?>" class="row g-2 align-items-end">
            <input type="hidden" name="<?php echo $field; ?>" value="<?php echo $token; ?>">
            <input type="hidden" name="form_action" value="add">
            <div class="col-auto">
                <label class="form-label" for="icip-steward-username"><?php echo __('Username'); ?></label>
                <input class="form-control" id="icip-steward-username" name="username" required autocomplete="off">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary"><?php echo __('Add steward'); ?></button></div>
        </form>
    <?php } ?>
</div>
