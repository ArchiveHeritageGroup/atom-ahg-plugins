<?php
/**
 * Quick Links Menu Component - Hardcoded (no database)
 * Overrides core AtoM quickLinksMenuComponent
 */
class menuQuickLinksMenuComponent extends AhgComponents
{
    public function execute($request)
    {
        // No database query - template is fully hardcoded
        // Empty $quickLinks to prevent errors if template references it
        $this->quickLinks = [];
    }
}
