<?php
use AtomExtensions\Services\AclService;
use AtomExtensions\Services\SettingService;

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
 * Display a list of recently updates to the db.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class reportsReportUserAction extends BaseReportAction
{
    public static $NAMES = ['dateStart', 'dateEnd', 'chkSummary', 'actionUser', 'userAction', 'userActivity', 'cbAuditTrailSummary', 'limit'];

    public function execute($request)
    {
        // Check authorization
        if ((!$this->context->user->isAdministrator())) {
            AclService::forwardUnauthorized();
        }

        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);
        foreach ($this::$NAMES as $name) {
            $this->addField($name);
        }

         // Initialize resource array to prevent null access
        $this->resource = $this->resource ?? [];
        // Get CSRF token from form or generate a fallback
        $csrfToken = $this->form->getCSRFToken();
        if ('' == $csrfToken) {
            $csrfToken = md5(session_id() . microtime(true) . mt_rand());
        }
        $defaults = [
            'dateStart' => date('Y-m-d', strtotime('-1 month')),
            'dateEnd' => date('Y-m-d'),
            'limit' => '10',
            '_csrf_token' => $csrfToken]; // JJP SITA to find out where the token is stored.

        $this->form->bind($request->getRequestParameters() + $request->getGetParameters() + $defaults);
        if ($this->form->isValid()) {
        $this->doSearch($request->limit, $request->page);
        }
    }

    public function doSearch($limit, $page)
    {
        $this->sort = $this->request->getParameter('sort', 'updatedDown');

        $criteria = new Criteria();
        if ('on' == $this->form->getValue('chkSummary')) {
            $criteria->addSelectColumn(QubitAuditObject::ACTION);
            $criteria->addSelectColumn(QubitAuditObject::DB_TABLE);
            $criteria->addSelectColumn(QubitAuditObject::USER);
            // $criteria->addSelectColumn("count(*) as count");
            $criteria->addSelectColumn(' COUNT(DISTINCT ACTION_DATE_TIME) as count ');
        } else {
            BaseAuditObject::addSelectColumns($criteria);
        }
        if ('delete' != $this->form->getValue('userAction')) {
            $dbTable = $this->form->getValue('userActivity');

            $criteria->addJoin(QubitAuditObject::RECORD_ID, QubitObject::ID);
            if ('on' != $this->form->getValue('chkSummary')) {
                $criteria->addSelectColumn(QubitObject::CLASS_NAME);
                // This join seems to be necessary to avoid cross joining the local table with the QubitObject table
            }
            if ('QubitUser' == $dbTable) {
                $criteria->addSelectColumn(QubitActor::ID);
                $criteria->addJoin(QubitActor::ID, QubitAuditObject::RECORD_ID);
            }
            $criteria->add(QubitAuditObject::ID, null, Criteria::ISNOTNULL);
            if ('0' != $this->form->getValue('userAction')) {
                $criteria->add(QubitAuditObject::ACTION, $this->form->getValue('userAction'), Criteria::EQUAL);
            }
            if ('0' == $this->form->getValue('userActivity')) {
                $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'information_object', Criteria::EQUAL);
                $criteria5 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'repository', Criteria::EQUAL);
                $criteria6 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'researcher', Criteria::EQUAL);
                $criteria7 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'service_provider', Criteria::EQUAL);
                $criteria8 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'physical_object', Criteria::EQUAL);
                $criteria9 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'registry', Criteria::EQUAL);
                $criteria10 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'actor', Criteria::EQUAL);
                $criteria11 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'donor', Criteria::EQUAL);
                $criteria12 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'user', Criteria::EQUAL);
                $criteria13 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'access_object', Criteria::EQUAL);
                $criteria14 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitTerm', Criteria::EQUAL);
                $criteria15 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'bookin_object', Criteria::EQUAL);
                $criteria16 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'bookout_object', Criteria::EQUAL);
                $criteria17 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'presevation_object', Criteria::EQUAL);
                $criteria18 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'taxonom,taxonomy', Criteria::EQUAL);
                $criteria19 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'digital_object', Criteria::EQUAL);
                // $criteria20 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitObjectTermRelation', Criteria::EQUAL);
                $criteria21 = $criteria->getNewCriterion(QubitAuditObject::USER, '', Criteria::NOT_EQUAL);

                $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitResearcher', Criteria::NOT_EQUAL);
                $criteria23 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitServiceProvider', Criteria::NOT_EQUAL);
                $criteria24 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitPhysicalObject', Criteria::NOT_EQUAL);
                $criteria25 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitRepository', Criteria::NOT_EQUAL);
                $criteria26 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitActor', Criteria::NOT_EQUAL);
                $criteria27 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitInformationObject', Criteria::NOT_EQUAL);
                $criteria28 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitAccessObject', Criteria::NOT_EQUAL);
                $criteria29 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitRelation', Criteria::NOT_EQUAL);
                $criteria30 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'status', Criteria::NOT_EQUAL);
                $criteria31 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'access_log', Criteria::NOT_EQUAL);
                $criteria32 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitPresevationObject', Criteria::NOT_EQUAL);
                $criteria33 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'property', Criteria::NOT_EQUAL);
                $criteria34 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'property_i18n', Criteria::NOT_EQUAL);
                $criteria35 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'acl_permission', Criteria::NOT_EQUAL);
                $criteria36 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'access_object_i18n', Criteria::NOT_EQUAL);
                $criteria37 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'acl_user_group', Criteria::NOT_EQUAL);
                $criteria38 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'actor_i18n', Criteria::NOT_EQUAL);
                $criteria39 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'bookin_object_i18n', Criteria::NOT_EQUAL);
                $criteria40 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'bookout_object_i18n', Criteria::NOT_EQUAL);
                $criteria41 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'event', Criteria::NOT_EQUAL);
                $criteria42 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'event_i18n', Criteria::NOT_EQUAL);
                $criteria43 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'information_object_i18n', Criteria::NOT_EQUAL);
                $criteria44 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'note', Criteria::NOT_EQUAL);
                $criteria45 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'note_i18n', Criteria::NOT_EQUAL);
                $criteria46 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'object_term_relation', Criteria::NOT_EQUAL);
                $criteria47 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'other_name', Criteria::NOT_EQUAL);
                $criteria48 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'other_name_i18n', Criteria::NOT_EQUAL);
                $criteria49 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'physical_object_i18n', Criteria::NOT_EQUAL);
                $criteria50 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'property', Criteria::NOT_EQUAL);
                $criteria51 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'property_i18n', Criteria::NOT_EQUAL);
                $criteria52 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitBookinObject', Criteria::NOT_EQUAL);
                $criteria53 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitDigitalObject', Criteria::NOT_EQUAL);
                $criteria54 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitDonor', Criteria::NOT_EQUAL);
                $criteria55 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitEvent', Criteria::NOT_EQUAL);
                $criteria56 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitObjectTermRelation', Criteria::NOT_EQUAL);
                $criteria57 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitRegistry', Criteria::NOT_EQUAL);
                $criteria58 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'relation_i18n', Criteria::NOT_EQUAL);
                $criteria59 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitServiceProvider', Criteria::NOT_EQUAL);
                $criteria60 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitTerm', Criteria::NOT_EQUAL);
                $criteria61 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitUser', Criteria::NOT_EQUAL);
                $criteria62 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'relation', Criteria::NOT_EQUAL);
                $criteria63 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'repository_i18n', Criteria::NOT_EQUAL);
                $criteria64 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'term_i18n', Criteria::NOT_EQUAL);
                $criteria65 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'term', Criteria::NOT_EQUAL);
                $criteria66 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, '', Criteria::NOT_EQUAL);

/*
                $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitInformationObject', Criteria::EQUAL);
                $criteria5 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitRepository', Criteria::EQUAL);
                $criteria6 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitResearcher', Criteria::EQUAL);
                $criteria7 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitServiceProvider', Criteria::EQUAL);
                $criteria8 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitPhysicalObject', Criteria::EQUAL);
                $criteria9 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitRegistry', Criteria::EQUAL);
                $criteria10 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitActor', Criteria::EQUAL);
                $criteria11 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitDonor', Criteria::EQUAL);
                $criteria12 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitUser', Criteria::EQUAL);
                $criteria13 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitAccessObject', Criteria::EQUAL);
                $criteria14 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitTerm', Criteria::EQUAL);
                $criteria15 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitBookinObject', Criteria::EQUAL);
                $criteria16 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitBookoutObject', Criteria::EQUAL);
                $criteria17 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitPresevationObject', Criteria::EQUAL);
                $criteria18 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitTaxonomy', Criteria::EQUAL);
                $criteria19 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitDigitalObject', Criteria::EQUAL);
                $criteria20 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitObjectTermRelation', Criteria::EQUAL);
                $criteria21 = $criteria->getNewCriterion(QubitAuditObject::USER, '', Criteria::NOT_EQUAL);
*/
                // $criteria->addOr($criteria4);
                $criteria4->addOr($criteria5);
                $criteria4->addOr($criteria6);
                $criteria4->addOr($criteria7);
                $criteria4->addOr($criteria8);
                $criteria4->addOr($criteria9);
                $criteria4->addOr($criteria10);
                $criteria4->addOr($criteria11);
                $criteria4->addOr($criteria12);
                $criteria4->addOr($criteria13);
                $criteria4->addOr($criteria14);
                $criteria4->addOr($criteria15);
                $criteria4->addOr($criteria16);
                $criteria4->addOr($criteria17);
                $criteria4->addOr($criteria18);
                $criteria4->addOr($criteria19);
                // $criteria4->addOr($criteria20);
                $criteria4->addAnd($criteria21);
                $criteria4->addAnd($criteria22);
                $criteria4->addAnd($criteria23);
                $criteria4->addAnd($criteria24);
                $criteria4->addAnd($criteria25);
                $criteria4->addAnd($criteria26);
                $criteria4->addAnd($criteria27);
                $criteria4->addAnd($criteria28);
                $criteria4->addAnd($criteria29);
                $criteria4->addAnd($criteria30);
                $criteria4->addAnd($criteria31);
                $criteria4->addAnd($criteria32);
                $criteria4->addAnd($criteria33);
                $criteria4->addAnd($criteria34);
                $criteria4->addAnd($criteria35);
                $criteria4->addAnd($criteria36);
                $criteria4->addAnd($criteria37);
                $criteria4->addAnd($criteria38);
                $criteria4->addAnd($criteria39);
                $criteria4->addAnd($criteria40);
                $criteria4->addAnd($criteria41);
                $criteria4->addAnd($criteria42);
                $criteria4->addAnd($criteria43);
                $criteria4->addAnd($criteria44);
                $criteria4->addAnd($criteria45);
                $criteria4->addAnd($criteria46);
                $criteria4->addAnd($criteria47);
                $criteria4->addAnd($criteria48);
                $criteria4->addAnd($criteria49);
                $criteria4->addAnd($criteria50);
                $criteria4->addAnd($criteria51);
                $criteria4->addAnd($criteria52);
                $criteria4->addAnd($criteria53);
                $criteria4->addAnd($criteria54);
                $criteria4->addAnd($criteria55);
                $criteria4->addAnd($criteria56);
                $criteria4->addAnd($criteria57);
                $criteria4->addAnd($criteria58);
                $criteria4->addAnd($criteria59);
                $criteria4->addAnd($criteria60);
                $criteria4->addAnd($criteria61);
                $criteria4->addAnd($criteria62);
                $criteria4->addAnd($criteria63);
                $criteria4->addAnd($criteria64);
                $criteria4->addAnd($criteria65);
                $criteria4->addAnd($criteria66);
                $criteria->addAnd($criteria4);
            } else {
                if ('QubitInformationObject' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'information_object', Criteria::EQUAL);
                } elseif ('QubitActor' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'actor', Criteria::EQUAL);
                } elseif ('QubitBookinObject' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'bookin_object', Criteria::EQUAL);
                } elseif ('QubitBookoutObject' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'bookout_object', Criteria::EQUAL);
                } elseif ('QubitDigitalObject' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'digital_object', Criteria::EQUAL);
                } elseif ('QubitDonor' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'donor', Criteria::EQUAL);
                } elseif ('QubitAccessObject' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'access_object', Criteria::EQUAL);
                } elseif ('QubitPhysicalObject' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'physical_object', Criteria::EQUAL);
                } elseif ('QubitPresevationObject' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'presevation_object', Criteria::EQUAL);
                } elseif ('QubitRegistry' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'registry', Criteria::EQUAL);
                } elseif ('QubitRepository' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'repository', Criteria::EQUAL);
                } elseif ('QubitResearcher' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'researcher', Criteria::EQUAL);
                } elseif ('QubitServiceProvider' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'service_provider', Criteria::EQUAL);
                } elseif ('QubitUser' == $dbTable) {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'user', Criteria::EQUAL);
                } else {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                }
/*
                if ($dbTable == 'QubitInformationObject') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'information_object', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitActor') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'actor', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitBookinObject') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'bookin_object', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitBookoutObject') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'bookout_object', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitDigitalObject') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'digital_object', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitDonor') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'donor', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitAccessObject') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'access_object', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitPhysicalObject') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'physical_object', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitPresevationObject') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'presevation_object', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitRegistry') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'registry', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitRepository') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'repository', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitResearcher') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'researcher', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitServiceProvider') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'service_provider', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else if ($dbTable == 'QubitUser') {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                    $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'user', Criteria::EQUAL);
                    $criteria4->addOr($criteria22);
                } else {
                    $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$dbTable.'', Criteria::EQUAL);
                }
*/
                $criteria->addAnd($criteria4);
            }
        } else { // delete
            if ('0' != $this->form->getValue('userActivity')) {
                $criteria4 = new Criteria();
                $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, ''.$this->form->getValue('userActivity').'', Criteria::EQUAL);
            } else {
                $criteria4 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitInformationObject', Criteria::EQUAL);
                $criteria22 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitActor', Criteria::EQUAL);
                $criteria4->addOr($criteria22);
                $criteria23 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitRepository', Criteria::EQUAL);
                $criteria4->addOr($criteria23);
                $criteria24 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitUser', Criteria::EQUAL);
                $criteria4->addOr($criteria24);
                $criteria25 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitServiceProvider', Criteria::EQUAL);
                $criteria4->addOr($criteria25);
                $criteria26 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitPhysicalObject', Criteria::EQUAL);
                $criteria4->addOr($criteria26);
                $criteria27 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitTaxonomy', Criteria::EQUAL);
                $criteria4->addOr($criteria27);
                $criteria28 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitTerm', Criteria::EQUAL);
                $criteria4->addOr($criteria28);
                $criteria29 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitRightsHolder', Criteria::EQUAL);
                $criteria4->addOr($criteria29);
                $criteria30 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitAccession', Criteria::EQUAL);
                $criteria4->addOr($criteria30);
                $criteria31 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitDeaccession', Criteria::EQUAL);
                $criteria4->addOr($criteria31);
                $criteria32 = $criteria->getNewCriterion(QubitAuditObject::DB_TABLE, 'QubitFunction', Criteria::EQUAL);
                $criteria4->addOr($criteria32);
            }

            $criteria->addAnd($criteria4);

            if (!$this->context->user->isAdministrator()) {
                $repos = new QubitUser();
                $userRepos = $repos->getRepositoriesById($this->context->user->getAttribute('user_id'));
                QubitUser::addSelectColumns($criteria);
                $criteria->add(QubitAuditObject::REPOSITORY_ID, $userRepos, Criteria::IN);
            }
            $criteria->add(QubitAuditObject::ACTION, 'delete', Criteria::EQUAL);
        }

        if (null !== $this->form->getValue('dateStart')) {
            $startDate = $this->form->getValue('dateStart');
            if (0 == strpos($startDate, '/', 0)) {
                $startDate = date('d/m/Y', strtotime('-3 months'));
            }
            $vDay = substr($startDate, 0, strpos($startDate, '/'));
            $vRes = substr($startDate, strpos($startDate, '/') + 1);
            $vMonth = substr($vRes, 0, strpos($vRes, '/'));
            $vYear = substr($vRes, strpos($vRes, '/') + 1);
            if ((int) $vMonth < 10) {
                $vMonth = '0'.$vMonth;
            }
            if (checkdate((int) $vMonth, (int) $vDay, (int) $vYear)) {
                $startDate = date_create($vYear.'-'.$vMonth.'-'.$vDay.' 00.00.00');
                $startDate = date_format($startDate, 'Y-m-d H:i:s');
            } else {
                $startDate = date('Y-m-d 23:59:59');
            }
            $startDate = $vYear.'-'.$vMonth.'-'.$vDay.' 00.00.00';
            $c1 = $criteria->getNewCriterion(QubitAuditObject::ACTION_DATE_TIME, $startDate, Criteria::GREATER_EQUAL);
            $criteria->addAnd($c1);
        }

        // End date at midnight
        if (null != $this->form->getValue('dateEnd')) {
            $vDay = substr($this->form->getValue('dateEnd'), 0, strpos($this->form->getValue('dateEnd'), '/'));
            $vRes = substr($this->form->getValue('dateEnd'), strpos($this->form->getValue('dateEnd'), '/') + 1);
            $vMonth = substr($vRes, 0, strpos($vRes, '/'));
            if ((int) $vMonth < 10) {
                $vMonth = '0'.$vMonth;
            }
            $vYear = substr($vRes, strpos($vRes, '/') + 1, 4);
            if (checkdate((int) $vMonth, (int) $vDay, (int) $vYear)) {
                $dateEnd = date_create($vYear.'-'.$vMonth.'-'.$vDay.' 23.59.59');
                $dateEnd = date_format($dateEnd, 'Y-m-d H:i:s');
            } else {
                $dateEnd = date('Y-m-d 23:59:59');
            }
            $c1 = $criteria->getNewCriterion(QubitAuditObject::ACTION_DATE_TIME, $dateEnd, Criteria::LESS_EQUAL);
            $criteria->addAnd($c1);
        }

        if ('0' != $this->form->getValue('actionUser')) {
            $c2 = $criteria->getNewCriterion(QubitAuditObject::USER, $this->form->getValue('actionUser'), Criteria::EQUAL);
            $criteria->addAnd($c2);
        } else {
            // filter users per users linked to repository
            if (!$this->context->user->isAdministrator()) { // if not administrator only filter users in linked to repository
                $criteriaUsers = new Criteria();
                $repos = new QubitUser();
                $userRepos = $repos->getRepositoriesById($this->context->user->getAttribute('user_id'));
                if (isset($userRepos[0]) && 6 == $userRepos[0]) { // remove repo from user list
                    unset($userRepos[0]);
                }
                QubitUser::addSelectColumns($criteriaUsers);
                $criteriaUsers->addJoin(QubitAclPermission::USER_ID, QubitUser::ID, Criteria::LEFT_JOIN);
                $criteriaUsers->add(QubitAclPermission::OBJECT_ID, $userRepos, Criteria::IN);

                $criteriaUsers->addGroupByColumn(QubitUser::USERNAME);
                $criteriaUsers->addAscendingOrderByColumn(QubitUser::USERNAME);

                foreach (QubitUser::get($criteriaUsers) as $user) {
                    $valuesUsers[$user->username] = $user->username;
                }
                $c2 = $criteria->getNewCriterion(QubitAuditObject::USER, $valuesUsers, Criteria::IN);
                $criteria->addAnd($c2);
            }
        }

        if (!isset($limit)) {
          $limit = sfConfig::get('app_hits_per_page');
        }

        if ('on' == $this->form->getValue('chkSummary')) {
            if ('0' != $this->form->getValue('actionUser')) {   // the user
                $criteria->addGroupByColumn(QubitAuditObject::USER);
                $criteria->addGroupByColumn(QubitAuditObject::DB_TABLE);
                $criteria->addGroupByColumn(QubitAuditObject::ACTION);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::DB_TABLE);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::ACTION);
            } elseif ('0' != $this->form->getValue('userAction')) { // the action taken - insert, edit and delete
                $criteria->addGroupByColumn(QubitAuditObject::DB_TABLE);
                $criteria->addGroupByColumn(QubitAuditObject::USER);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::DB_TABLE);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::USER);
            } elseif ('0' != $this->form->getValue('userActivity')) { // on what area was action taken
                $criteria->addGroupByColumn(QubitAuditObject::ACTION);
                $criteria->addGroupByColumn(QubitAuditObject::USER);
                $criteria->addGroupByColumn(QubitAuditObject::DB_TABLE);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::ACTION);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::USER);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::DB_TABLE);
            } else {
                $criteria->addGroupByColumn(QubitAuditObject::USER);
                $criteria->addGroupByColumn(QubitAuditObject::ACTION);
                $criteria->addGroupByColumn(QubitAuditObject::DB_TABLE);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::USER);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::ACTION);
                $criteria->addDescendingOrderByColumn(QubitAuditObject::DB_TABLE);
            }
        } else {
            $criteria->addGroupByColumn(QubitAuditObject::ACTION);
            $criteria->addGroupByColumn(QubitAuditObject::USER);
            $criteria->addGroupByColumn(QubitAuditObject::ACTION_DATE_TIME);
            $criteria->addDescendingOrderByColumn(QubitAuditObject::ACTION_DATE_TIME);
            $criteria->addDescendingOrderByColumn(QubitAuditObject::ACTION);
            $criteria->addDescendingOrderByColumn(QubitAuditObject::USER);
        }
        if ('-1' != SettingService::getByName('max_row_report')) {
            $rowToReturn = SettingService::getByName('max_row_report');
            $rowToReturn = preg_replace('/\s+/', '', $rowToReturn);
            $rowToReturn = (int) $rowToReturn;

            $criteria->setLimit($rowToReturn); // bug not working
        }

        // Page results
        $this->pager = new QubitPagerAudit('QubitUserActions');
        $this->pager->setCriteria($criteria);
        $this->pager->setMaxPerPage($limit);
        $this->pager->setPage($page ? $page : 1);

        // echo $criteria->toString()."<br>";
    }

    protected function addField($name)
    {
        switch ($name) {
        case 'dateStart':
            $this->form->setDefault('dateStart', Qubit::renderDate($this->resource['dateStart'] ?? null));
            if (!isset($this->resource->id)) {
                $dt = new DateTime();
                $this->form->setDefault('dateStart', $dt->format('Y-m-d'));
            }
            $this->form->setValidator('dateStart', new sfValidatorString());
            $this->form->setWidget('dateStart', new sfWidgetFormInput());

            break;

        case 'dateEnd':
            $this->form->setDefault('dateEnd', Qubit::renderDate($this->resource['dateEnd'] ?? null));
            if (!isset($this->resource->id)) {
                $dt = new DateTime();
                $this->form->setDefault('dateEnd', $dt->format('Y-m-d'));
            }
            $this->form->setValidator('dateEnd', new sfValidatorString());
            $this->form->setWidget('dateEnd', new sfWidgetFormInput());

            break;

        case 'chkSummary':
            $this->form->setDefault($name, false);
            $this->form->setValidator($name, new sfValidatorBoolean());
            $this->form->setWidget($name, new sfWidgetFormInputCheckbox());

            break;

        case 'userAction':
            $choices = ['0' => '', 'insert' => 'Insert', 'update' => 'Update', 'delete' => 'Delete'];

            $this->form->setValidator($name, new sfValidatorString());
            $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $choices]));

            break;

        case 'userActivity':
            $choices = ['0' => '',
                'QubitAccessObject' => 'Access',
                'QubitInformationObject' => 'Archival Description',
                'QubitRepository' => 'Archival Institution',
                'QubitActor' => 'Authority Record',
                'QubitBookinObject' => 'Book in',
                'QubitBookoutObject' => 'Book out',
                'QubitDigitalObject' => 'Digital Object',
                'QubitDonor' => 'Donor',
                'QubitPhysicalObject' => 'Physical Storage',
                'QubitPresevationObject' => 'Preservation',
                'QubitRegistry' => 'Registry',
                'QubitResearcher' => 'Researcher',
                'QubitServiceProvider' => 'Service Provider',
                // 'QubitTaxonomy' => "Taxonomy",
                // 'QubitTerm' => "Term",
                // 'QubitObjectTermRelation' => "Term Relation",
                'QubitUser' => 'Users',
            ];

            $this->form->setValidator($name, new sfValidatorString());
            $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $choices]));

            break;

        case 'actionUser':
            $values = [];
            $values[] = null;
            $criteria = new Criteria();
            // filter users per users linked to repository
            if (!$this->context->user->isAdministrator()) {
                $repos = new QubitUser();
                $userRepos = $repos->getRepositoriesById($this->context->user->getAttribute('user_id'));
                if (isset($userRepos[0]) && 6 == $userRepos[0]) { // remove repo from user list
                    unset($userRepos[0]);
                }
                QubitUser::addSelectColumns($criteria);
                $criteria->addJoin(QubitAclPermission::USER_ID, QubitUser::ID, Criteria::LEFT_JOIN);
                $criteria->add(QubitAclPermission::OBJECT_ID, $userRepos, Criteria::IN);
            }

            $criteria->addGroupByColumn(QubitUser::USERNAME);
            $criteria->addAscendingOrderByColumn(QubitUser::USERNAME);

            foreach (QubitUser::get($criteria) as $user) {
                $values[$user->username] = $user->username;
            }

            if ((!$this->context->user->isAdministrator()) && $this->context->user->isSuperUser()) {
                $this->form->setDefault('actionUser', $this->context->user->getAttribute('username'));
            }
            $this->form->setValidator('actionUser', new sfValidatorChoice(['choices' => array_keys($values), 'required' => false]));
            $this->form->setWidget('actionUser', new sfWidgetFormSelect(['choices' => $values]));

            break;

        case 'limit':
            $this->form->setValidator($name, new sfValidatorString());
            $this->form->setWidget($name, new sfWidgetFormInputHidden());

            break;

        case 'sort':
            $this->form->setValidator($name, new sfValidatorString());
            $this->form->setWidget($name, new sfWidgetFormInputHidden());

            break;
        }
    }
}
