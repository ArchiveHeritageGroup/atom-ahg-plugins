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

class RightIndexAction extends sfAction
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        // Guard the class: the catch-all route /:slug/:module/:action resolves any
        // object's slug with no class check, and the members read below exist only on
        // QubitRights. Any other class reaches BaseObject::__get / __isset and throws.
        // See CH-000066 and
        // docs/sessions/2026-09-18-informationobject-reports-slug-class-guard.md
        if (!$this->resource instanceof QubitRights) {
            $this->forward404();
        }

        $value = [];

        // act and restriction live on `granted_right`, not `rights` - they moved there
        // in AtoM 2.x and this action was never updated, so __isset threw
        // Unknown record property "act" and the endpoint 500d for EVERY QubitRights,
        // not just for a wrong-class slug. CH-000105 / CH-000108.
        //
        // Base's own right/_right.php template shows the correct source: iterate
        // $resource->grantedRights and read ->act and ->restriction off each one.
        // A right may carry several; this endpoint's JSON shape holds a single value,
        // so the first is emitted rather than silently changing the contract.
        foreach ($this->resource->grantedRights as $grantedRight) {
            if (isset($grantedRight->act)) {
                $value['act'] = $this->context->routing->generate(null, [$grantedRight->act, 'module' => 'term']);
            }

            $value['restriction'] = $grantedRight->restriction;

            break;
        }

        $value['startDate'] = Qubit::renderDate($this->resource->startDate);

        $value['endDate'] = Qubit::renderDate($this->resource->endDate);

        if (isset($this->resource->rightsHolder)) {
            $value['rightsHolder'] = $this->context->routing->generate(null, [$this->resource->rightsHolder, 'module' => 'rightsholder']);
        }

        if (isset($this->resource->rightsNote)) {
            $value['rightsNote'] = $this->resource->rightsNote;
        }

        if (isset($this->resource->basis)) {
            $value['basis'] = $this->context->routing->generate(null, [$this->resource->basis, 'module' => 'term']);
        }

        // Basis: copyright.
        if (isset($this->resource->copyrightStatus)) {
            $value['copyrightStatus'] = $this->context->routing->generate(null, [$this->resource->copyrightStatus, 'module' => 'term']);
        }

        if (isset($this->resource->copyrightStatusDate)) {
            $value['copyrightStatusDate'] = $this->resource->copyrightStatusDate;
        }

        if (isset($this->resource->copyrightJurisdiction)) {
            $value['copyrightJurisdiction'] = $this->resource->copyrightJurisdiction;
        }

        if (isset($this->resource->copyrightNote)) {
            $value['copyrightNote'] = $this->resource->copyrightNote;
        }

        // Basis: license.
        // The column is identifier_value, not license_identifier - base's own
        // right/_right.php renders "License identifier" from getIdentifierValue().
        // Reading licenseIdentifier threw and 500d the endpoint. The JSON key is kept
        // as licenseIdentifier so the response contract does not change. CH-000105.
        if (isset($this->resource->identifierValue)) {
            $value['licenseIdentifier'] = $this->resource->identifierValue;
        }

        if (isset($this->resource->licenseTerms)) {
            $value['licenseTerms'] = $this->resource->licenseTerms;
        }

        if (isset($this->resource->licenseNote)) {
            $value['licenseNote'] = $this->resource->licenseNote;
        }

        // Basis: statute.
        if (isset($this->resource->statuteJurisdiction)) {
            $value['statuteJurisdiction'] = $this->resource->statuteJurisdiction;
        }

        if (isset($this->resource->statuteCitation)) {
            $value['statuteCitation'] = $this->resource->statuteCitation;
        }

        if (isset($this->resource->statuteDeterminationDate)) {
            $value['statuteDeterminationDate'] = $this->resource->statuteDeterminationDate;
        }

        if (isset($this->resource->statuteNote)) {
            $value['statuteNote'] = $this->resource->statuteNote;
        }

        return $this->renderText(json_encode($value));
    }
}
