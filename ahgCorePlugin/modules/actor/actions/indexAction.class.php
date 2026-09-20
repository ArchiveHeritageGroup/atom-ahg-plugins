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

class ActorIndexAction extends sfAction
{
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
        if (!$this->resource instanceof QubitActor) {
            $this->forward404();
        }

        // Check that this isn't the root
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }

        // actor/index has never had an indexSuccess.php template. The base actor
        // module ships browse, delete and autocomplete templates but not index, so
        // everything that used to sit here ran and then fatalled on the missing
        // template - a 500 for EVERY actor, not only for a wrong-class slug.
        // CH-000106 / CH-000107.
        //
        // The action is reachable only through the catch-all route
        // /:slug/:module/:action - nothing links to it, and the canonical actor page
        // is the bare slug, which QubitMetadataRoute dispatches by class.
        //
        // A redirect was tried first and rejected. The routing generator only does
        // object-aware generation when a module is supplied, and the module differs
        // per subclass: QubitActor admits QubitRepository, QubitDonor and
        // QubitRightsHolder, which render through sfIsdiahPlugin, donor and
        // rightsholder. Hardcoding that mapping would duplicate what
        // QubitMetadataRoute already knows and would send three of four classes to
        // the wrong page. 404 is the honest answer for a URL that is not a page.
        //
        // The ACL check, access-log event and related-function lookup that used to
        // follow are dropped with it - they only ever fed the missing template.
        $this->forward404();
    }
}
