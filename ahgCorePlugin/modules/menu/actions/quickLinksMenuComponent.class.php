<?php

/**
 * Quick links menu, populated from the database.
 *
 * This overrides AtoM's own component. The previous version set
 * $this->quickLinks = [] on the reasoning that "the template is fully hardcoded"
 * - true only of ahgThemeB5Plugin, which ships its own override and its own
 * template with the links baked in.
 *
 * On any other theme the override still applied while the hardcoded template did
 * not exist, so the stock template rendered an empty menu and Home, About,
 * Privacy Policy, Help and General Feedback vanished from the site. Measured on
 * RARI against the live Wits instance, 18 August 2026: the menu rows were all
 * present in the database the whole time and none of them reached the page.
 *
 * Reading the rows here means the menu follows the database - which is where the
 * labels belong - and any theme renders it, hardcoded or not.
 */
class menuQuickLinksMenuComponent extends AhgComponents
{
    public function execute($request)
    {
        $this->quickLinks = [];

        if (!class_exists('QubitMenu')) {
            return;
        }

        $menu = \QubitMenu::getById(\QubitMenu::QUICK_LINKS_ID);
        if (!$menu instanceof \QubitMenu) {
            return;
        }

        foreach ($menu->getChildren() as $child) {
            $url = $child->getPath([
                'getUrl' => true,
                'resolveAlias' => true,
                'removeIndex' => true,
            ]);

            // Same test base AtoM applies: keep external URLs, and internal paths
            // only where the action actually exists. That is what stops a menu row
            // left behind by a removed plugin from rendering as a dead link.
            $parsed = parse_url((string) $url);

            if (isset($parsed['scheme']) || \QubitObject::actionExistsForUrl($url)) {
                $this->quickLinks[] = $child;
            }
        }
    }
}
