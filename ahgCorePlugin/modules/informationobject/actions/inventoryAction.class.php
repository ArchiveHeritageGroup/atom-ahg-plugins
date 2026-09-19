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

class InformationObjectInventoryAction extends DefaultBrowseAction
{
    private static $levels;

    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        // Guard the class: the catch-all route /:slug/:module/:action resolves any
        // object's slug with no class check. isset($this->resource->parent) below is
        // NOT a safe probe - BaseObject::__isset throws Unknown record property for a
        // class without that column, so a slug for an accession, right, event,
        // relation, static page, physical object, user, deaccession or function
        // object 500s here. See CH-000066 and
        // docs/sessions/2026-09-18-informationobject-reports-slug-class-guard.md
        if (!$this->resource instanceof QubitInformationObject) {
            $this->forward404();
        }

        // Check that this isn't the root
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }

        // Set title header
        sfContext::getInstance()->getConfiguration()->loadHelpers(['Qubit']);
        $title = strip_markdown($this->resource);
        $this->response->setTitle("{$title} - Inventory list - {$this->response->getTitle()}");

        $limit = sfConfig::get('app_hits_per_page');
        if (isset($request->limit) && ctype_digit($request->limit)) {
            $limit = $request->limit;
        }

        $page = 1;
        if (isset($request->page) && ctype_digit($request->page)) {
            $page = $request->page;
        }

        // Avoid pagination over ES' max result window config (default: 10000)
        $maxResultWindow = arElasticSearchPluginConfiguration::getMaxResultWindow();

        if ((int) $limit * $page > $maxResultWindow) {
            // Show alert
            $message = $this->context->i18n->__(
                "We've redirected you to the first page of results. To avoid using vast amounts of memory, AtoM limits pagination to %1% records. To view the last records in the current result set, try changing the sort direction.",
                ['%1%' => $maxResultWindow]
            );
            $this->getUser()->setFlash('notice', $message);

            // Redirect to first page
            $params = $request->getParameterHolder()->getAll();
            unset($params['page']);
            $this->redirect($params);
        }

        $resultSet = self::getResults($this->resource, $limit, $page, $request->sort);

        // Page results
        $this->pager = new QubitSearchPager($resultSet);
        $this->pager->setPage($page);
        $this->pager->setMaxPerPage($limit);
        $this->pager->init();
    }

    public static function showInventory($resource)
    {
        if (empty(self::getLevels())) {
            return false;
        }

        $resultSet = self::getResults($resource);

        return $resultSet->getTotalHits() > 0;
    }

    /**
     * Configured inventory levels, ALWAYS an array.
     *
     * It used to `return;` - null - on three paths: no setting row, a failed
     * unserialize, and an empty or non-array value. showInventory() tested that with
     * empty() and coped, but execute() passes the result straight to
     * Elastica\Query\Terms, whose second argument is typed array, so on PHP 8 a null
     * is a TypeError rather than an empty query.
     *
     * That is what took /informationobject/inventory down on 13 September 2026:
     * inventory_levels has never been set on this instance. The tab is hidden by
     * showInventory(), so the page is only reachable by typing the URL - which is why
     * it sat unnoticed until someone did.
     *
     * Returning [] keeps every caller's meaning intact: empty([]) is still true for
     * showInventory(), and a Terms query on an empty list simply matches nothing,
     * which is the honest answer when no levels are configured.
     */
    private static function getLevels(): array
    {
        if (null !== self::$levels) {
            return self::$levels;
        }

        $setting = QubitSetting::getByName('inventory_levels');
        // #245: stored setting value is a plain array - refuse object
        // instantiation so a crafted value cannot trigger a destructor chain.
        if (null === $setting
            || false === $value = unserialize($setting->getValue(), ['allowed_classes' => false])) {
            return self::$levels = [];
        }

        if (!is_array($value) || 0 === count($value)) {
            return self::$levels = [];
        }

        self::$levels = $value;

        return $value;
    }

    private static function getResults($resource, $limit = 10, $page = 1, $sort = null)
    {
        $query = new \Elastica\Query();
        $query->setSize($limit);
        if (!empty($page)) {
            $query->setFrom(($page - 1) * $limit);
        }

        $queryBool = new \Elastica\Query\BoolQuery();

        $q1 = new \Elastica\Query\Term();
        $q1->setTerm('ancestors', $resource->id);
        $queryBool->addMust($q1);
        $q2 = new \Elastica\Query\Terms('levelOfDescriptionId', self::getLevels());
        $queryBool->addMust($q2);

        $i18n = sprintf('i18n.%s.', sfContext::getInstance()->getUser()->getCulture());

        switch ($sort) {
            case 'identifierDown':
                $query->setSort(['identifier.untouched' => 'desc']);

                break;

            case 'titleUp':
                $query->setSort([$i18n.'title.alphasort' => 'asc']);

                break;

            case 'titleDown':
                $query->setSort([$i18n.'title.alphasort' => 'desc']);

                break;

            case 'levelUp':
                $query->setSort(['levelOfDescriptionId' => 'asc']);

                break;

            case 'levelDown':
                $query->setSort(['levelOfDescriptionId' => 'desc']);

                break;

            case 'dateUp':
                $query->setSort([
                    'startDateSort' => 'asc',
                    'endDateSort' => 'asc',
                ]);

                break;

            case 'dateDown':
                $query->setSort([
                    'startDateSort' => 'desc',
                    'endDateSort' => 'desc',
                ]);

                break;

            // Avoid sorting when we are just counting records
            case null:
                break;

            case 'identifierUp':
            default:
                $query->setSort(['identifier.untouched' => 'asc']);
        }

        QubitAclSearch::filterDrafts($queryBool);
        $query->setQuery($queryBool);

        return QubitSearch::getInstance()->index->getIndex('QubitInformationObject')->search($query);
    }
}
