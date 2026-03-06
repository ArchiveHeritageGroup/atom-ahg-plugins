<?php

/*
 * AHG Core Plugin - aclGroup termAclForm component stub.
 * Overrides qbAclPlugin component for base AtoM module decoupling (WP-S8).
 */

class AclGroupTermAclFormComponent extends sfComponent
{
    public function execute($request)
    {
        // List of actions without read or translate
        $this->termActions = QubitAcl::$ACTIONS;
        unset($this->termActions['read'], $this->termActions['translate']);

        // Build separate list of permissions by taxonomy and by object
        $this->taxonomyPermissions = [];
        $this->rootPermissions = [];

        if (0 < count($this->permissions)) {
            foreach ($this->permissions as $item) {
                if ('createTerm' == $item->action) {
                    if (QubitTaxonomy::ROOT_ID == $item->objectId || null === $item->objectId) {
                        $this->rootPermissions['create'] = $item;
                    } else {
                        $this->taxonomyPermissions[$item->objectId]['create'] = $item;
                    }
                } elseif (null === ($taxonomy = $item->getConstants(['name' => 'taxonomy']))) {
                    $this->rootPermissions[$item->action] = $item;
                } else {
                    $this->taxonomyPermissions[$taxonomy][$item->action] = $item;
                }
            }
        }
    }
}
