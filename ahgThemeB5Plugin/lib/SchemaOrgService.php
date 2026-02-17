<?php
/**
 * Schema.org JSON-LD Service
 *
 * Generates Schema.org structured data for SEO.
 * Embeds JSON-LD in page head for search engine optimization.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgThemeB5Plugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class SchemaOrgService
{
    // Schema.org context
    const CONTEXT = 'https://schema.org';

    // Term type IDs (from AtoM)
    const TERM_CREATION_ID = 111;
    const TERM_PERSON_ID = 160;
    const TERM_CORPORATE_BODY_ID = 131;
    const TERM_FAMILY_ID = 132;
    const ROOT_ID = 1;

    protected $baseUri;

    public function __construct()
    {
        $this->baseUri = rtrim(\sfConfig::get('app_siteBaseUrl', 'https://example.org'), '/');
    }

    /**
     * Get JSON-LD URL for an information object
     *
     * @param object|string $resource Resource object or slug
     * @return string JSON-LD endpoint URL
     */
    public function getJsonLdUrl($resource): string
    {
        $slug = is_object($resource) ? ($resource->slug ?? null) : $resource;

        if (!$slug && is_object($resource) && isset($resource->id)) {
            $slugRow = DB::table('slug')->where('object_id', $resource->id)->first();
            $slug = $slugRow ? $slugRow->slug : null;
        }

        if (!$slug) {
            return '';
        }

        return $this->baseUri . '/' . $slug . '.jsonld';
    }

    /**
     * Get JSON-LD URL for a repository
     *
     * @param object|string $resource Resource object or slug
     * @return string JSON-LD endpoint URL
     */
    public function getRepositoryJsonLdUrl($resource): string
    {
        $slug = is_object($resource) ? ($resource->slug ?? null) : $resource;

        if (!$slug && is_object($resource) && isset($resource->id)) {
            $slugRow = DB::table('slug')->where('object_id', $resource->id)->first();
            $slug = $slugRow ? $slugRow->slug : null;
        }

        if (!$slug) {
            return '';
        }

        return $this->baseUri . '/repository/' . $slug . '.jsonld';
    }

    /**
     * Get JSON-LD URL for an actor
     *
     * @param object|string $resource Resource object or slug
     * @return string JSON-LD endpoint URL
     */
    public function getActorJsonLdUrl($resource): string
    {
        $slug = is_object($resource) ? ($resource->slug ?? null) : $resource;

        if (!$slug && is_object($resource) && isset($resource->id)) {
            $slugRow = DB::table('slug')->where('object_id', $resource->id)->first();
            $slug = $slugRow ? $slugRow->slug : null;
        }

        if (!$slug) {
            return '';
        }

        return $this->baseUri . '/actor/' . $slug . '.jsonld';
    }

    /**
     * Generate Link header value for JSON-LD alternate
     *
     * @param string $jsonldUrl
     * @return string Link header value
     */
    public function getLinkHeader(string $jsonldUrl): string
    {
        if (empty($jsonldUrl)) {
            return '';
        }

        return '<' . $jsonldUrl . '>; rel="alternate"; type="application/ld+json"';
    }

    /**
     * Generate Schema.org JSON-LD for an information object
     *
     * @param object $informationObject The information object
     * @return string JSON-LD script tag
     */
    public function getInformationObjectJsonLd($informationObject): string
    {
        if (!$informationObject || !$informationObject->id) {
            return '';
        }

        $data = $this->buildInformationObjectData($informationObject->id);
        if (empty($data)) {
            return '';
        }

        return $this->renderJsonLd($data);
    }

    /**
     * Generate Schema.org JSON-LD for a repository
     *
     * @param object $repository The repository object
     * @return string JSON-LD script tag
     */
    public function getRepositoryJsonLd($repository): string
    {
        if (!$repository || !$repository->id) {
            return '';
        }

        $data = $this->buildRepositoryData($repository->id);
        if (empty($data)) {
            return '';
        }

        return $this->renderJsonLd($data);
    }

    /**
     * Generate Schema.org JSON-LD for an actor (person/organization)
     *
     * @param object $actor The actor object
     * @return string JSON-LD script tag
     */
    public function getActorJsonLd($actor): string
    {
        if (!$actor || !$actor->id) {
            return '';
        }

        $data = $this->buildActorData($actor->id);
        if (empty($data)) {
            return '';
        }

        return $this->renderJsonLd($data);
    }

    /**
     * Generate organization/website JSON-LD for the site
     *
     * @return string JSON-LD script tag
     */
    public function getSiteJsonLd(): string
    {
        $siteName = \sfConfig::get('app_siteTitle', 'Archive');
        $siteDescription = \sfConfig::get('app_siteDescription', '');

        $data = [
            '@context' => self::CONTEXT,
            '@type' => 'WebSite',
            '@id' => $this->baseUri . '#website',
            'url' => $this->baseUri,
            'name' => $siteName,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->baseUri . '/informationobject/browse?query={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];

        if (!empty($siteDescription)) {
            $data['description'] = $siteDescription;
        }

        return $this->renderJsonLd($data);
    }

    /**
     * Build Schema.org data for an information object
     */
    protected function buildInformationObjectData(int $id): array
    {
        $io = DB::table('information_object')->where('id', $id)->first();
        if (!$io) {
            return [];
        }

        $ioI18n = DB::table('information_object_i18n')
            ->where('id', $id)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->first();

        $slug = DB::table('slug')->where('object_id', $id)->first();
        $uri = $this->baseUri . '/' . ($slug ? $slug->slug : $id);

        // Use ArchiveComponent for archival descriptions
        // Fall back to CreativeWork for general items
        $type = 'ArchiveComponent';

        // Get level of description
        $levelName = null;
        if ($io->level_of_description_id) {
            $level = DB::table('term_i18n')
                ->where('id', $io->level_of_description_id)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->first();
            $levelName = $level ? $level->name : null;

            // Map levels to Schema.org types
            if ($levelName) {
                $levelLower = strtolower($levelName);
                if (in_array($levelLower, ['collection', 'fonds'])) {
                    $type = 'Collection';
                } elseif (in_array($levelLower, ['photograph', 'image', 'photo'])) {
                    $type = 'Photograph';
                } elseif (in_array($levelLower, ['book', 'publication'])) {
                    $type = 'Book';
                } elseif (in_array($levelLower, ['manuscript'])) {
                    $type = 'Manuscript';
                } elseif (in_array($levelLower, ['map'])) {
                    $type = 'Map';
                } elseif (in_array($levelLower, ['audio', 'sound recording'])) {
                    $type = 'AudioObject';
                } elseif (in_array($levelLower, ['video', 'film', 'moving image'])) {
                    $type = 'VideoObject';
                }
            }
        }

        $data = [
            '@context' => self::CONTEXT,
            '@type' => $type,
            '@id' => $uri,
            'url' => $uri
        ];

        // Title/Name
        if (!empty($ioI18n->title)) {
            $data['name'] = $ioI18n->title;
        }

        // Identifier
        if (!empty($io->identifier)) {
            $data['identifier'] = $io->identifier;
        }

        // Description (scope and content)
        if (!empty($ioI18n->scope_and_content)) {
            $data['description'] = $this->truncateText($ioI18n->scope_and_content, 500);
        }

        // Repository (holding institution)
        if ($io->repository_id) {
            $repo = DB::table('actor_i18n')
                ->join('slug', 'actor_i18n.id', '=', 'slug.object_id')
                ->where('actor_i18n.id', $io->repository_id)
                ->where('actor_i18n.culture', 'en')
                ->first();

            if ($repo) {
                $data['holdingArchive'] = [
                    '@type' => 'ArchiveOrganization',
                    '@id' => $this->baseUri . '/repository/' . $repo->slug,
                    'name' => $repo->authorized_form_of_name
                ];
            }
        }

        // Creators
        $creators = $this->getCreators($id);
        if (!empty($creators)) {
            $data['creator'] = count($creators) === 1 ? $creators[0] : $creators;
        }

        // Dates
        $dates = $this->getDates($id);
        if (!empty($dates['dateCreated'])) {
            $data['dateCreated'] = $dates['dateCreated'];
        }
        if (!empty($dates['temporalCoverage'])) {
            $data['temporalCoverage'] = $dates['temporalCoverage'];
        }

        // Subject access points
        $subjects = $this->getSubjects($id);
        if (!empty($subjects)) {
            $data['about'] = $subjects;
        }

        // Place access points
        $places = $this->getPlaces($id);
        if (!empty($places)) {
            $data['spatialCoverage'] = count($places) === 1 ? $places[0] : $places;
        }

        // Digital objects (thumbnails/images)
        $images = $this->getImages($id);
        if (!empty($images)) {
            $data['image'] = count($images) === 1 ? $images[0] : $images;
        }

        // Archival level
        if ($levelName) {
            $data['additionalType'] = $levelName;
        }

        // Parent (part of)
        if ($io->parent_id && $io->parent_id != self::ROOT_ID) {
            $parent = DB::table('information_object')
                ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                ->join('slug', 'information_object.id', '=', 'slug.object_id')
                ->where('information_object.id', $io->parent_id)
                ->where('information_object_i18n.culture', 'en')
                ->first();

            if ($parent) {
                $data['isPartOf'] = [
                    '@type' => 'ArchiveComponent',
                    '@id' => $this->baseUri . '/' . $parent->slug,
                    'name' => $parent->title
                ];
            }
        }

        // Access conditions / license
        if (!empty($ioI18n->access_conditions)) {
            $data['conditionsOfAccess'] = $ioI18n->access_conditions;
        }

        // Language
        if (!empty($io->language)) {
            $data['inLanguage'] = $io->language;
        }

        return $data;
    }

    /**
     * Build Schema.org data for a repository
     */
    protected function buildRepositoryData(int $id): array
    {
        $repo = DB::table('repository')
            ->where('id', $id)
            ->first();

        if (!$repo) {
            return [];
        }

        $repoI18n = DB::table('actor_i18n')
            ->where('id', $id)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->first();

        $slug = DB::table('slug')->where('object_id', $id)->first();
        $uri = $this->baseUri . '/repository/' . ($slug ? $slug->slug : $id);

        $data = [
            '@context' => self::CONTEXT,
            '@type' => 'ArchiveOrganization',
            '@id' => $uri,
            'url' => $uri
        ];

        if (!empty($repoI18n->authorized_form_of_name)) {
            $data['name'] = $repoI18n->authorized_form_of_name;
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
                $data['address'] = $address;
            }

            if (!empty($contact->telephone)) {
                $data['telephone'] = $contact->telephone;
            }
            if (!empty($contact->email)) {
                $data['email'] = $contact->email;
            }
            if (!empty($contact->website)) {
                $data['sameAs'] = $contact->website;
            }
        }

        // Description
        $repoDescI18n = DB::table('repository_i18n')
            ->where('id', $id)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->first();

        if ($repoDescI18n && !empty($repoDescI18n->desc_institution_area)) {
            $data['description'] = $this->truncateText($repoDescI18n->desc_institution_area, 500);
        }

        return $data;
    }

    /**
     * Build Schema.org data for an actor
     */
    protected function buildActorData(int $id): array
    {
        $actor = DB::table('actor')
            ->where('id', $id)
            ->first();

        if (!$actor) {
            return [];
        }

        $actorI18n = DB::table('actor_i18n')
            ->where('id', $id)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
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
            $type = 'Person'; // Schema.org doesn't have Family type
        }

        $data = [
            '@context' => self::CONTEXT,
            '@type' => $type,
            '@id' => $uri,
            'url' => $uri
        ];

        if (!empty($actorI18n->authorized_form_of_name)) {
            $data['name'] = $actorI18n->authorized_form_of_name;
        }

        // Biography/history
        if (!empty($actorI18n->history)) {
            $data['description'] = $this->truncateText($actorI18n->history, 500);
        }

        // Dates
        if (!empty($actor->dates_of_existence)) {
            $data['description'] = ($data['description'] ?? '') . ' Dates: ' . $actor->dates_of_existence;
        }

        return $data;
    }

    /**
     * Get creators for an information object
     */
    protected function getCreators(int $id): array
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
                'name' => $creator->authorized_form_of_name
            ];
        }

        return $result;
    }

    /**
     * Get dates for an information object
     */
    protected function getDates(int $id): array
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
            // If we have specific start/end dates
            if (!empty($event->start_date) && !empty($event->end_date)) {
                if ($event->start_date === $event->end_date) {
                    $result['dateCreated'] = $event->start_date;
                } else {
                    $result['temporalCoverage'] = $event->start_date . '/' . $event->end_date;
                }
            } elseif (!empty($event->start_date)) {
                $result['dateCreated'] = $event->start_date;
            } elseif (!empty($event->date)) {
                // Free-text date
                $result['temporalCoverage'] = $event->date;
            }
        }

        return $result;
    }

    /**
     * Get subject access points
     */
    protected function getSubjects(int $id): array
    {
        $subjects = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $id)
            ->where('term.taxonomy_id', 35) // Subject taxonomy
            ->where('term_i18n.culture', 'en')
            ->select('term_i18n.name')
            ->get();

        $result = [];
        foreach ($subjects as $subject) {
            $result[] = $subject->name;
        }

        return $result;
    }

    /**
     * Get place access points
     */
    protected function getPlaces(int $id): array
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
                'name' => $place->name
            ];
        }

        return $result;
    }

    /**
     * Get images/thumbnails for an information object
     */
    protected function getImages(int $id): array
    {
        $digitalObjects = DB::table('digital_object')
            ->where('object_id', $id)
            ->get();

        $result = [];
        foreach ($digitalObjects as $do) {
            if (!empty($do->path)) {
                // Check if it's an image
                $mimeType = $do->mime_type ?? '';
                if (strpos($mimeType, 'image/') === 0 || empty($mimeType)) {
                    $result[] = $this->baseUri . '/uploads/' . ltrim($do->path, '/');
                }
            }
        }

        return $result;
    }

    /**
     * Render JSON-LD as script tag
     */
    protected function renderJsonLd(array $data): string
    {
        $nonce = \sfConfig::get('csp_nonce', '');
        $nonceAttr = $nonce ? ' nonce="' . preg_replace('/^nonce=/', '', $nonce) . '"' : '';

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<script type="application/ld+json"' . $nonceAttr . '>' . "\n" . $json . "\n" . '</script>';
    }

    /**
     * Truncate text to a maximum length
     */
    protected function truncateText(string $text, int $maxLength): string
    {
        // Strip HTML tags
        $text = strip_tags($text);
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = substr($text, 0, $maxLength);
        // Try to break at a word boundary
        $lastSpace = strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $maxLength - 50) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return $truncated . '...';
    }

    /**
     * Generate breadcrumb JSON-LD for navigation
     */
    public function getBreadcrumbJsonLd(array $breadcrumbs): string
    {
        if (empty($breadcrumbs)) {
            return '';
        }

        $items = [];
        $position = 1;

        foreach ($breadcrumbs as $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $crumb['name'] ?? $crumb['title'] ?? ''
            ];

            if (!empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }

            $items[] = $item;
            $position++;
        }

        $data = [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];

        return $this->renderJsonLd($data);
    }
}
