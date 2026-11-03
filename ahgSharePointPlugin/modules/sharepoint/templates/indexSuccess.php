<h1><?php echo __('SharePoint') ?></h1>

<p><?php echo __('One-way integration: SharePoint document libraries are ingested into AtoM.') ?></p>

<div class="list-group" style="max-width: 40rem">
    <a class="list-group-item list-group-item-action" href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'drives']) ?>"><?php echo __('Drives') ?></a>
    <a class="list-group-item list-group-item-action" href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'rules']) ?>"><?php echo __('Ingest rules') ?></a>
    <a class="list-group-item list-group-item-action" href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappings']) ?>"><?php echo __('Mapping templates') ?></a>
    <a class="list-group-item list-group-item-action" href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'userMappings']) ?>"><?php echo __('User mappings') ?></a>
    <a class="list-group-item list-group-item-action" href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'events']) ?>"><?php echo __('Events') ?></a>
    <a class="list-group-item list-group-item-action" href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'subscriptions']) ?>"><?php echo __('Webhook subscriptions') ?></a>
</div>
