<?php

/**
 * Schema.org JSON-LD Exporter
 *
 * Exports archival entities as Schema.org JSON-LD for linked data publishing.
 * Supports content negotiation and dedicated .jsonld endpoints.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

use Illuminate\Database\Capsule\Manager as DB;

class SchemaOrgExporter extends AbstractRdfExporter
{
    /**
     * Schema.org context URL
     */
    public const CONTEXT_URL = 'https://schema.org';

    /**
     * Term type IDs
     */
    protected const TERM_CREATION_ID = 111;
    protected const TERM_PERSON_ID = 160;
    protected const TERM_CORPORATE_BODY_ID = 131;
    protected const TERM_FAMILY_ID = 132;
    protected const ROOT_ID = 1;

    /**
     * {@inheritdoc}
     */
    public function getFormatCode(): string
    {
        return 'schema-org';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'Schema.org JSON-LD';
    }

    /**
     * {@inheritdoc}
     */
    protected function initializePrefixes(): void
    {
        $this->prefixes = [
            'schema' => 'https://schema.org/',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContext(): void
    {
        $this->context = self::CONTEXT_URL;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedResourceTypes(): array
    {
        return ['QubitInformationObject', 'QubitRepository', 'QubitActor'];
    }

    /**
     * {@inheritdoc}
     */
    protected function buildGraph($resource): array
    {
        // Detect resource type
        $className = is_object($resource) ? get_class($resource) : '';

        if ($className === 'QubitRepository' || (isset($resource->class_name) && $resource->class_name === 'QubitRepository')) {
            return $this->buildRepositoryGraph($resource);
        }

        if ($className === 'QubitActor' || (isset($resource->class_name) && $resource->class_name === 'QubitActor')) {
            return $this->buildActorGraph($resource);
        }

        return $this->buildInformationObjectGraph($resource);
    }

    /**
     * Build Schema.org graph for an information object
     */
    protected function buildInformationObjectGraph($resource): array
    {
        $id = is_object($resource) ? $resource->id : ($resource->id ?? null);
        if (!$id) {
            return [];
        }

        // Get data from database
        $io = DB::table('information_object')->where('id', $id)->first();
        if (!$io) {
            return [];
        }

        $ioI18n = DB::table('information_object_i18n')
            ->where('id', $id)
            ->where('culture', 'en')
            ->first();

        $slug = DB::table('slug')->where('object_id', $id)->first();
        $uri = $this->baseUri . '/' . ($slug ? $slug->slug : $id);

        // Determine Schema.org type based on level of description
        $type = $this->determineType($io->level_of_description_id);

        $graph = [
            '@type' => $type,
            '@id' => $uri,
            'url' => $uri,
        ];

        // Title/Name
        if (!empty($ioI18n->title)) {
            $graph['name'] = $ioI18n->title;
        }

        // Identifier
        if (!empty($io->identifier)) {
            $graph['identifier'] = [
                '@type' => 'PropertyValue',
                'propertyID' => 'reference_code',
                'value' => $io->identifier,
            ];
        }

        // Description (scope and content)
        if (!empty($ioI18n->scope_and_content)) {
            $graph['description'] = $this->truncateText(strip_tags($ioI18n->scope_and_content), 1000);
        }

        // Repository (holding institution)
        if ($io->repository_id) {
            $repo = DB::table('actor_i18n')
                ->join('slug', 'actor_i18n.id', '=', 'slug.object_id')
                ->where('actor_i18n.id', $io->repository_id)
                ->where('actor_i18n.culture', 'en')
                ->first();

            if ($repo) {
                $graph['holdingArchive'] = [
                    '@type' => 'ArchiveOrganization',
                    '@id' => $this->baseUri . '/repository/' . $repo->slug,
                    'name' => $repo->authorized_form_of_name,
                ];
            }
        }

        // Creators
        $creators = $this->getCreatorsForGraph($id);
        if (!empty($creators)) {
            $graph['creator'] = count($creators) === 1 ? $creators[0] : $creators;
        }

        // Dates
        $dates = $this->getDatesForGraph($id);
        if (!empty($dates['dateCreated'])) {
            $graph['dateCreated'] = $dates['dateCreated'];
        }
        if (!empty($dates['temporalCoverage'])) {
            $graph['temporalCoverage'] = $dates['temporalCoverage'];
        }

        // Subject access points
        $subjects = $this->getSubjectsForGraph($id);
        if (!empty($subjects)) {
            $graph['about'] = $subjects;
        }

        // Place access points
        $places = $this->getPlacesForGraph($id);
        if (!empty($places)) {
            $graph['spatialCoverage'] = count($places) === 1 ? $places[0] : $places;
        }

        // Digital objects (images)
        $images = $this->getImagesForGraph($id);
        if (!empty($images)) {
            $graph['image'] = count($images) === 1 ? $images[0] : $images;
        }

        // Archival level
        $levelName = $this->getLevelName($io->level_of_description_id);
        if ($levelName) {
            $graph['additionalType'] = $levelName;
        }

        // Parent (part of)
        if ($io->parent_id && $io->parent_id != self::ROOT_ID) {
            $parent = DB::table('information_object')
                ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                ->join('slug', 'information_object.id', '=', 'slug.object_id')
                ->where('information_object.id', $io->parent_id)
                ->where('information_object_i18n.culture', 'en')
                ->first();

            if ($parent && !empty($parent->title)) {
                $graph['isPartOf'] = [
                    '@type' => 'ArchiveComponent',
                    '@id' => $this->baseUri . '/' . $parent->slug,
                    'name' => $parent->title,
                ];
            }
        }

        // Access conditions
        if (!empty($ioI18n->access_conditions)) {
            $graph['conditionsOfAccess'] = $ioI18n->access_conditions;
        }

        // External identifiers (Wikidata, VIAF)
        $externalIds = $this->getExternalIdentifiers($id);
        if (!empty($externalIds)) {
            $graph['sameAs'] = $externalIds;
        }

        return $graph;
    }

    /**
     * Build Schema.org graph for a repository
     */
    protected function buildRepositoryGraph($resource): array
    {
        $id = is_object($resource) ? $resource->id : ($resource->id ?? null);
        if (!$id) {
            return [];
        }

        $repoI18n = DB::table('actor_i18n')
            ->where('id', $id)
            ->where('culture', 'en')
            ->first();

        $slug = DB::table('slug')->where('object_id', $id)->first();
        $uri = $this->baseUri . '/repository/' . ($slug ? $slug->slug : $id);

        $graph = [
            '@type' => 'ArchiveOrganization',
            '@id' => $uri,
            'url' => $uri,
        ];

        if (!empty($repoI18n->authorized_form_of_name)) {
            $graph['name'] = $repoI18n->authorized_form_of_name;
        }

        // Contact info
        $contact = DB::table('contact_information')
            ->where('actor_id', $id)
            ->first();

        if ($contact) {
            if (!empty($contact->city) || !empty($contact->country_code)) {
                $address = ['@type' => 'PostalAddress'];
                if (!empty($contact->street_address)) {
                    $address['streetAddress'] = $contact->street_address;
                }
                if (!empty($contact->city)) {
                    $address['addressLocality'] = $contact->city;
                }
                if (!empty($contact->region)) {
                    $address['addressRegion'] = $contact->region;
                }
                if (!empty($contact->postal_code)) {
                    $address['postalCode'] = $contact->postal_code;
                }
                if (!empty($contact->country_code)) {
                    $address['addressCountry'] = $contact->country_code;
                }
                $graph['address'] = $address;
            }

            if (!empty($contact->telephone)) {
                $graph['telephone'] = $contact->telephone;
            }
            if (!empty($contact->email)) {
                $graph['email'] = $contact->email;
            }
            if (!empty($contact->website)) {
                $graph['sameAs'] = $contact->website;
            }
        }

        // Description
        $repoDescI18n = DB::table('repository_i18n')
            ->where('id', $id)
            ->where('culture', 'en')
            ->first();

        if ($repoDescI18n && !empty($repoDescI18n->desc_institution_area)) {
            $graph['description'] = $this->truncateText(strip_tags($repoDescI18n->desc_institution_area), 500);
        }

        return $graph;
    }

    /**
     * Build Schema.org graph for an actor
     */
    protected function buildActorGraph($resource): array
    {
        $id = is_object($resource) ? $resource->id : ($resource->id ?? null);
        if (!$id) {
            return [];
        }

        $actor = DB::table('actor')->where('id', $id)->first();
        if (!$actor) {
            return [];
        }

        $actorI18n = DB::table('actor_i18n')
            ->where('id', $id)
            ->where('culture', 'en')
            ->first();

        $slug = DB::table('slug')->where('object_id', $id)->first();
        $uri = $this->baseUri . '/actor/' . ($slug ? $slug->slug : $id);

        // Determine type
        $type = 'Thing';
        if ($actor->entity_type_id == self::TERM_PERSON_ID) {
            $type = 'Person';
        } elseif ($actor->entity_type_id == self::TERM_CORPORATE_BODY_ID) {
            $type = 'Organization';
        } elseif ($actor->entity_type_id == self::TERM_FAMILY_ID) {
            $type = 'Person';
        }

        $graph = [
            '@type' => $type,
            '@id' => $uri,
            'url' => $uri,
        ];

        if (!empty($actorI18n->authorized_form_of_name)) {
            $graph['name'] = $actorI18n->authorized_form_of_name;
        }

        if (!empty($actorI18n->history)) {
            $graph['description'] = $this->truncateText(strip_tags($actorI18n->history), 500);
        }

        // External identifiers
        $externalIds = $this->getExternalIdentifiers($id, 'actor');
        if (!empty($externalIds)) {
            $graph['sameAs'] = $externalIds;
        }

        return $graph;
    }

    /**
     * Determine Schema.org type from level of description
     */
    protected function determineType(?int $levelId): string
    {
        if (!$levelId) {
            return 'ArchiveComponent';
        }

        $level = DB::table('term_i18n')
            ->where('id', $levelId)
            ->where('culture', 'en')
            ->first();

        if (!$level) {
            return 'ArchiveComponent';
        }

        $levelLower = strtolower($level->name);

        $typeMap = [
            'collection' => 'Collection',
            'fonds' => 'Collection',
            'photograph' => 'Photograph',
            'image' => 'Photograph',
            'photo' => 'Photograph',
            'book' => 'Book',
            'publication' => 'Book',
            'manuscript' => 'Manuscript',
            'map' => 'Map',
            'audio' => 'AudioObject',
            'sound recording' => 'AudioObject',
            'video' => 'VideoObject',
            'film' => 'VideoObject',
            'moving image' => 'VideoObject',
        ];

        foreach ($typeMap as $keyword => $schemaType) {
            if (strpos($levelLower, $keyword) !== false) {
                return $schemaType;
            }
        }

        return 'ArchiveComponent';
    }

    /**
     * Get level of description name
     */
    protected function getLevelName(?int $levelId): ?string
    {
        if (!$levelId) {
            return null;
        }

        $level = DB::table('term_i18n')
            ->where('id', $levelId)
            ->where('culture', 'en')
            ->first();

        return $level ? $level->name : null;
    }

    /**
     * Get creators for an information object
     */
    protected function getCreatorsForGraph(int $id): array
    {
        $creators = DB::table('event')
            ->join('actor_i18n', 'event.actor_id', '=', 'actor_i18n.id')
            ->join('actor', 'event.actor_id', '=', 'actor.id')
            ->leftJoin('slug', 'actor.id', '=', 'slug.object_id')
            ->where('event.object_id', $id)
            ->where('event.type_id', self::TERM_CREATION_ID)
            ->where('actor_i18n.culture', 'en')
            ->select('actor.*', 'actor_i18n.authorized_form_of_name', 'slug.slug')
            ->get();

        $result = [];
        foreach ($creators as $creator) {
            $type = 'Thing';
            if ($creator->entity_type_id == self::TERM_PERSON_ID) {
                $type = 'Person';
            } elseif ($creator->entity_type_id == self::TERM_CORPORATE_BODY_ID) {
                $type = 'Organization';
            }

            $result[] = [
                '@type' => $type,
                '@id' => $this->baseUri . '/actor/' . ($creator->slug ?? $creator->id),
                'name' => $creator->authorized_form_of_name,
            ];
        }

        return $result;
    }

    /**
     * Get dates for an information object
     */
    protected function getDatesForGraph(int $id): array
    {
        $event = DB::table('event')
            ->leftJoin('event_i18n', function ($join) {
                $join->on('event.id', '=', 'event_i18n.id')
                    ->where('event_i18n.culture', '=', 'en');
            })
            ->where('event.object_id', $id)
            ->where('event.type_id', self::TERM_CREATION_ID)
            ->first();

        $result = [];

        if ($event) {
            if (!empty($event->start_date) && !empty($event->end_date)) {
                if ($event->start_date === $event->end_date) {
                    $result['dateCreated'] = $event->start_date;
                } else {
                    $result['temporalCoverage'] = $event->start_date . '/' . $event->end_date;
                }
            } elseif (!empty($event->start_date)) {
                $result['dateCreated'] = $event->start_date;
            } elseif (!empty($event->date)) {
                $result['temporalCoverage'] = $event->date;
            }
        }

        return $result;
    }

    /**
     * Get subject access points
     */
    protected function getSubjectsForGraph(int $id): array
    {
        $subjects = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $id)
            ->where('term.taxonomy_id', 35) // Subject taxonomy
            ->where('term_i18n.culture', 'en')
            ->select('term_i18n.name')
            ->get();

        return $subjects->pluck('name')->toArray();
    }

    /**
     * Get place access points
     */
    protected function getPlacesForGraph(int $id): array
    {
        $places = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $id)
            ->where('term.taxonomy_id', 42) // Place taxonomy
            ->where('term_i18n.culture', 'en')
            ->select('term_i18n.name')
            ->get();

        $result = [];
        foreach ($places as $place) {
            $result[] = [
                '@type' => 'Place',
                'name' => $place->name,
            ];
        }

        return $result;
    }

    /**
     * Get images for an information object
     */
    protected function getImagesForGraph(int $id): array
    {
        $digitalObjects = DB::table('digital_object')
            ->where('object_id', $id)
            ->get();

        $result = [];
        foreach ($digitalObjects as $do) {
            if (!empty($do->path)) {
                $mimeType = $do->mime_type ?? '';
                if (strpos($mimeType, 'image/') === 0 || empty($mimeType)) {
                    $result[] = $this->baseUri . '/uploads/' . ltrim($do->path, '/');
                }
            }
        }

        return $result;
    }

    /**
     * Get external identifiers (Wikidata, VIAF)
     */
    protected function getExternalIdentifiers(int $id, string $type = 'informationobject'): array
    {
        $identifiers = [];

        // Check property table for external links
        $properties = DB::table('property')
            ->where('object_id', $id)
            ->whereIn('name', ['wikidata_uri', 'viaf_uri', 'sameAs', 'wikidata_id', 'viaf_id'])
            ->get();

        foreach ($properties as $prop) {
            $value = $prop->value ?? ($prop->i18n_value ?? null);
            if (!empty($value)) {
                // Convert IDs to URIs if needed
                if ($prop->name === 'wikidata_id' && !str_starts_with($value, 'http')) {
                    $value = 'https://www.wikidata.org/entity/' . $value;
                } elseif ($prop->name === 'viaf_id' && !str_starts_with($value, 'http')) {
                    $value = 'https://viaf.org/viaf/' . $value;
                }
                $identifiers[] = $value;
            }
        }

        // Check semantic search plugin tables if available
        try {
            if (DB::getSchemaBuilder()->hasTable('semantic_entity_link')) {
                $links = DB::table('semantic_entity_link')
                    ->where('object_id', $id)
                    ->where('object_type', $type)
                    ->whereIn('source', ['wikidata', 'viaf'])
                    ->get();

                foreach ($links as $link) {
                    if (!empty($link->external_uri)) {
                        $identifiers[] = $link->external_uri;
                    }
                }
            }
        } catch (\Exception $e) {
            // Table doesn't exist, ignore
        }

        return array_unique($identifiers);
    }

    /**
     * Truncate text to specified length
     */
    protected function truncateText(string $text, int $maxLength): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = substr($text, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $maxLength - 50) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return $truncated . '...';
    }
}
