<?php

/*
 * AHG Core Plugin - aclGroup tabs component stub.
 * Overrides qbAclPlugin component for base AtoM module decoupling (WP-S8).
 */

class aclGroupTabsComponent extends sfComponent
{
    public function execute($request)
    {
        // Get parent menu
        $criteria = new Criteria();
        $criteria->add(QubitMenu::NAME, 'groups');

        $this->groupsMenu = QubitMenu::getOne($criteria);
    }
}
