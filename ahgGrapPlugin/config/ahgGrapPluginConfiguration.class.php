<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * ahgGrapPlugin Configuration.
 *
 * Provides GRAP 103 financial accounting for heritage assets.
 * Adds standalone GRAP form accessible from "More" menu and
 * a dashboard accessible from the admin menu.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgGrapPluginConfiguration extends sfPluginConfiguration
{
    /**
     * Plugin summary for admin display.
     */
    public static $summary = 'GRAP 103 heritage asset financial accounting with dashboard and reports.';

    /**
     * Plugin version.
     */
    public static $version = '1.1.0';

    /**
     * Initialize plugin.
     */
	public function initialize()
	{
		// Enable plugin modules
		$enabledModules = sfConfig::get('sf_enabled_modules');
		$enabledModules[] = 'grap';
		$enabledModules[] = 'grapReport';
                $enabledModules[] = 'api';
		sfConfig::set('sf_enabled_modules', $enabledModules);

		// Register routes from plugin
		$this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

		// Connect to events
		$this->dispatcher->connect('menu.main.add', [$this, 'addGrapAdminMenu']);
		$this->dispatcher->connect('menu.context.add', [$this, 'addGrapContextMenu']);
	}

	public function loadRoutes(sfEvent $event)
	{
		$routing = $event->getSubject();
		
		// Load routes from the static getRoutes() method
		foreach (self::getRoutes() as $name => $routeConfig) {
			$routeClass = $routeConfig['class'] ?? 'sfRoute';
			if ($routeClass === 'QubitResourceRoute') {
				$routing->prependRoute($name, new QubitResourceRoute(
					$routeConfig['url'],
					$routeConfig['param'],
					$routeConfig['requirements'] ?? [],
					$routeConfig['options'] ?? []
				));
			} else {
				$routing->prependRoute($name, new sfRoute(
					$routeConfig['url'],
					$routeConfig['param'],
					$routeConfig['requirements'] ?? [],
					$routeConfig['options'] ?? []
				));
			}
		}
	}

    /**
     * Add GRAP Dashboard to admin/main menu.
     *
     * @param sfEvent $event Menu event
     */
    public function addGrapAdminMenu(sfEvent $event)
    {
        $menu = $event->getSubject();

        // Only show for authenticated users
        $context = sfContext::getInstance();
        if (!$context->user->isAuthenticated()) {
            return;
        }

        // Find the admin menu or create a reports section
        $adminMenu = null;
        foreach ($menu->getChildren() as $child) {
            if ('admin' === $child->getName() || 'Admin' === $child->getLabel()) {
                $adminMenu = $child;

                break;
            }
        }

        // If no admin menu, add to main menu
        if ($adminMenu) {
            $adminMenu->addChild('GRAP Dashboard', [
                'route' => 'grapReport/index',
            ])->setAttribute('class', 'grap-menu-item');
        } else {
            // Add as top-level menu item
            $menu->addChild('GRAP', [
                'route' => 'grapReport/index',
            ])->setAttribute('class', 'grap-menu-item');
        }
    }

    /**
     * Add GRAP link to context menu (More dropdown).
     *
     * @param sfEvent $event Context menu event
     */
    public function addGrapContextMenu(sfEvent $event)
    {
        $menu = $event->getSubject();

        // Get the resource from context
        $context = sfContext::getInstance();
        $request = $context->getRequest();

        // Check if we're on an information object page
        $module = $request->getParameter('module');
        $ioModules = [
            'informationobject',
            'sfIsadPlugin',
            'sfRadPlugin',
            'sfDcPlugin',
            'sfModsPlugin',
            'sfMuseumPlugin',
            'arMuseumMetadataPlugin',
            'museum',
        ];

        if (!in_array($module, $ioModules)) {
            return;
        }

        try {
            $route = $request->getAttribute('sf_route');
            if (!$route || !isset($route->resource)) {
                return;
            }

            $resource = $route->resource;

            if (!$resource instanceof QubitInformationObject) {
                return;
            }

            // Add divider and GRAP menu items
            $menu->addChild('grap-divider', ['label' => '-'])
                ->setAttribute('class', 'divider');

            $menu->addChild('View GRAP data', [
                'route' => 'grap/index?slug='.$resource->slug,
            ])->setAttribute('class', 'grap-view-link');

            if (($sf_user->isAdministrator() || $sf_user->hasCredential('editor'))) {
                $menu->addChild('Edit GRAP data', [
                    'route' => 'grap/edit?slug='.$resource->slug,
                ])->setAttribute('class', 'grap-edit-link');
            }
        } catch (\Exception $e) {
            // Silently fail if resource not available
        }
    }

    /**
     * Get contextual help text for GRAP fields.
     *
     * @param string $field Field name
     *
     * @return string Help text
     */
    public static function getHelpText($field)
    {
        $help = [
            'recognition_status' => 'GRAP 103.14-21: Whether the asset is recognised in the financial statements. Recognition requires probable future economic benefits or service potential and reliable measurement.',
            'measurement_basis' => 'GRAP 103.22-49: The basis on which the carrying amount is measured. Cost, fair value, or deemed cost for heritage assets.',
            'depreciation_policy' => 'GRAP 103.50-58: Heritage assets are generally not depreciated due to indefinite useful lives, unless a finite useful life can be determined.',
            'heritage_significance' => 'GRAP 103.70-79: Level of heritage significance for disclosure purposes. Affects reporting requirements.',
            'asset_class' => 'GRAP 103.10-13: Classification of the heritage asset type for grouping and reporting purposes.',
            'acquisition_method' => 'How the asset was acquired - affects initial measurement and recognition.',
            'revaluation_frequency' => 'GRAP 103.42-49: How often the asset should be revalued. More significant assets may require more frequent revaluation.',
            'condition_rating' => 'Current physical condition of the asset. Affects impairment assessment and conservation planning.',
        ];

        return $help[$field] ?? '';
    }

    /**
     * Get routes for the plugin.
     *
     * @return array Route definitions
     */
    public static function getRoutes()
    {
        return [
            'grap' => [
                'url' => '/:slug/grap',
                'class' => 'QubitResourceRoute',
                'param' => ['module' => 'grap', 'action' => 'index'],
            ],
            'grap_edit' => [
                'url' => '/:slug/grap/edit',
                'class' => 'QubitResourceRoute',
                'param' => ['module' => 'grap', 'action' => 'edit'],
            ],
            'grap_reports' => [
                'url' => '/grap/reports',
                'param' => ['module' => 'grapReport', 'action' => 'index'],
            ],
            'grap_asset_register' => [
                'url' => '/grap/reports/asset-register',
                'param' => ['module' => 'grapReport', 'action' => 'assetRegister'],
            ],
            'grap_disclosure' => [
                'url' => '/grap/reports/disclosure',
                'param' => ['module' => 'grapReport', 'action' => 'disclosure'],
            ],
            'grap_valuation_schedule' => [
                'url' => '/grap/reports/valuation-schedule',
                'param' => ['module' => 'grapReport', 'action' => 'valuationSchedule'],
            ],
            'grap_insurance_expiry' => [
                'url' => '/grap/reports/insurance-expiry',
                'param' => ['module' => 'grapReport', 'action' => 'insuranceExpiry'],
            ],
            'grap_compliance' => [
                'url' => '/grap/reports/compliance',
                'param' => ['module' => 'grapReport', 'action' => 'compliance'],
            ],
        ];
    }
}
