<?php

/*
 * AHG Core Plugin - aclGroup aclTable component stub.
 * Overrides qbAclPlugin component for base AtoM module decoupling (WP-S8).
 */

class AclGroupAclTableComponent extends sfComponent
{
    public function execute($request)
    {
        // Cut first 5 chars ("Qubit") to get desired module name
        $this->module = strtolower(substr(get_class($this->object), 5));
        $this->row = 0;
    }
}
