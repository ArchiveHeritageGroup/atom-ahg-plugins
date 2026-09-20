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
 * Action Handler for FullWidth TreeView.
 *
 * @author Andy Koch <koch.andy@gmail.com>
 */
class InformationObjectFullWidthTreeViewAction extends DefaultFullTreeViewAction
{
    public function execute($request)
    {
        parent::execute($request);

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

        // Check user authorization
        if (!QubitAcl::check($this->resource, 'read')) {
            QubitAcl::forwardUnauthorized();
        }

        // Impose limit to what nodeLimit parameter can be set to
        $maxItemsPerPage = sfConfig::get('app_treeview_items_per_page_max', 10000);
        if (
            !intval($request->nodeLimit)
            || $request->nodeLimit < 1
            || $request->nodeLimit > $maxItemsPerPage
        ) {
            $request->nodeLimit = $maxItemsPerPage;
        }

        // Allow the ability to page through children
        $options = [
            'skip' => $request->skip,
            'limit' => $request->nodeLimit,
        ];

        // On first load, retrieve the ancestors of the selected resource, the
        // resource and its siblings, otherwise get only the resource's siblings
        if (
            filter_var(
                $request->getParameter('firstLoad', false),
                FILTER_VALIDATE_BOOLEAN
            )
        ) {
            $data = $this->getAncestorsAndSiblings($options);
        } else {
            $data = $this->getChildren($this->resource->id, $options);
        }

        return $this->renderText(json_encode($data));
    }

    /**
     * Count children without calling IndexWrapper::count(), which does not exist.
     *
     * CH-000104. Base's DefaultFullTreeViewAction::countChildren()
     * (apps/qubit/modules/default/actions/fullTreeViewAction.class.php:241) ends in
     * ->getIndex('QubitInformationObject')->count(...). The OpenSearch migration
     * replaced Elastica with arOpenSearchPlugin's IndexWrapper, which exposes
     * search() but never got a count(), so every call raised
     * "Call to undefined method IndexWrapper::count()". It logged while the page
     * still returned HTTP 200, which is why nobody reported it.
     *
     * Both the caller and IndexWrapper are locked base AtoM. This class already
     * extends DefaultFullTreeViewAction, so overriding the one broken method here
     * fixes it from the plugin side without touching base or shadowing a module.
     *
     * search() with limit 0 returns no documents and a populated total, which is
     * exactly what count() was being asked for.
     *
     * @param mixed $id
     * @param array $options
     *
     * @return int number of children
     */
    protected function countChildren($id, $options = [])
    {
        $term = new \Elastica\Query\Term(['parentId' => $id]);
        $options['limit'] = 0;

        $query = $this->getElasticSearchQuery($term, $options);

        return QubitSearch::getInstance()
            ->index
            ->getIndex('QubitInformationObject')
            ->search($query->getQuery(false, false))
            ->getTotalHits();
    }
}
