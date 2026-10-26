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

// One class, two files: base AtoM ships the same component at
// apps/qubit/modules/term/actions/. Symfony renders the component from base's
// module folder, while its class map can load this copy first (term/relatedAuthorities
// uses the class before rendering the component), and PHP then fails with
// "Cannot declare class TermNavigateRelatedComponent" (RARI, 26 Oct 2026). So
// while base's file is there, that is the one loaded, whoever asks first; this
// copy only declares the class once base's module is gone. The declaration sits
// inside the if on purpose: an unconditional one is bound when the file is
// compiled, before any check runs.
$baseComponent = sfConfig::get('sf_app_module_dir').'/term/actions/navigateRelatedComponent.class.php';
if (is_file($baseComponent)):
    require_once $baseComponent;
else:
class TermNavigateRelatedComponent extends sfComponent
{
    // Arrays not allowed in class constants
    public static $TAXONOMY_ES_FIELDS = [
        QubitTaxonomy::PLACE_ID => 'places.id',
        QubitTaxonomy::SUBJECT_ID => 'subjects.id',
        QubitTaxonomy::GENRE_ID => 'genres.id',
    ];
    public static $TAXONOMY_ES_DIRECT_FIELDS = [
        QubitTaxonomy::PLACE_ID => 'directPlaces',
        QubitTaxonomy::SUBJECT_ID => 'directSubjects',
        QubitTaxonomy::GENRE_ID => 'directGenres',
    ];

    public function execute($request)
    {
        if (!isset(self::$TAXONOMY_ES_FIELDS[$this->resource->taxonomyId])) {
            return sfView::NONE;
        }

        // Take note of counts of Elasticsearch documents related to term
        $this->relatedIoCount = self::getEsDocsRelatedToTerm('QubitInformationObject', $this->resource)->getTotalHits();
        $this->relatedActorCount = self::getEsDocsRelatedToTerm('QubitActor', $this->resource)->getTotalHits();
    }

    public static function getEsDocsRelatedToTerm($relatedModelClass, $term, $options = [])
    {
        if (!isset(self::$TAXONOMY_ES_FIELDS[$term->taxonomyId])) {
            throw new sfException('Unsupported taxonomy.');
        }

        // Allow for options to override default behavior
        $search = !empty($options['search']) ? $options['search'] : new arElasticSearchPluginQuery();
        $esFields = !empty($options['direct']) ? self::$TAXONOMY_ES_DIRECT_FIELDS : self::$TAXONOMY_ES_FIELDS;

        // Search for related resources using appropriate field
        $query = new \Elastica\Query\Term();
        $query->setTerm($esFields[$term->taxonomyId], $term->id);
        $search->query->setQuery($search->queryBool->addMust($query));

        // Filter out drafts if querying descriptions
        if ('QubitInformationObject' == $relatedModelClass) {
            QubitAclSearch::filterDrafts($search->queryBool);
        }

        return QubitSearch::getInstance()->index->getIndex($relatedModelClass)->search($search->getQuery(false));
    }

    public static function getEsDocsRelatedToTermCount($relatedModelClass, $termId, $search = null)
    {
        $term = QubitTerm::getById($termId);

        $resultSet = self::getEsDocsRelatedToTerm($relatedModelClass, $term, $search);

        return $resultSet->getTotalHits();
    }
}
endif;
