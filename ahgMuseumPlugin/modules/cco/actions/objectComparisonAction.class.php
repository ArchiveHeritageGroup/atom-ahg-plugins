<?php

use Illuminate\Database\Capsule\Manager as DB;

class ccoObjectComparisonAction extends sfAction
{
    public $objects = [];
    public $comparisonData = [];
    public $fieldGroups;

    // ACL Group IDs
    private const GROUP_ADMINISTRATOR = 100;
    private const GROUP_EDITOR = 101;
    private const GROUP_CONTRIBUTOR = 102;
    private const GROUP_TRANSLATOR = 103;

    // Publication status
    private const STATUS_TYPE_PUBLICATION_ID = 158;
    private const PUBLICATION_STATUS_PUBLISHED = 160;

    // Event type for creation
    private const TERM_CREATION_ID = 111;

    /** Comparable field groups */
    public const FIELD_GROUPS = [
        'identification' => [
            'label' => 'Identification',
            'fields' => ['identifier', 'title', 'level_of_description'],
        ],
        'creation' => [
            'label' => 'Creation',
            'fields' => ['creator', 'creation_date', 'creation_place'],
        ],
        'physical' => [
            'label' => 'Physical Description',
            'fields' => ['object_type', 'materials', 'techniques', 'dimensions', 'inscriptions'],
        ],
        'style' => [
            'label' => 'Style & Classification',
            'fields' => ['style_period', 'school', 'culture', 'subject'],
        ],
        'provenance' => [
            'label' => 'Provenance',
            'fields' => ['provenance', 'acquisition_source', 'acquisition_date'],
        ],
        'condition' => [
            'label' => 'Condition',
            'fields' => ['condition_rating', 'condition_notes'],
        ],
        'valuation' => [
            'label' => 'Valuation',
            'fields' => ['insurance_value', 'valuation_date'],
        ],
    ];

    public function execute($request)
    {
        // Check if user can read (general access check)
        if (!$this->checkGeneralReadAccess()) {
            $this->forwardUnauthorized();
        }

        $this->fieldGroups = self::FIELD_GROUPS;

        // Get object IDs from request
        $objectIds = $request->getParameter('objects', []);

        if (is_string($objectIds)) {
            $objectIds = array_filter(explode(',', $objectIds));
        }

        foreach ($objectIds as $id) {
            $objectId = (int) $id;
            if ($objectId > 0 && $this->checkObjectReadAccess($objectId)) {
                $object = $this->getInformationObject($objectId);
                if ($object) {
                    $this->objects[] = $object;
                }
            }
        }

        // Build comparison data
        if (count($this->objects) >= 2) {
            $this->comparisonData = $this->buildComparisonData();
        }
    }

    /**
     * Check if user has general read access
     */
    protected function checkGeneralReadAccess(): bool
    {
        $user = $this->getUser();

        // Anonymous users can access published content
        if (!$user->isAuthenticated()) {
            return true;
        }

        return true; // Authenticated users can always try to read
    }

    /**
     * Check if user can read specific object
     */
    protected function checkObjectReadAccess(int $objectId): bool
    {
        $user = $this->getUser();

        // Check if object is published
        $publicationStatus = DB::table('status')
            ->where('object_id', $objectId)
            ->where('type_id', self::STATUS_TYPE_PUBLICATION_ID)
            ->value('status_id');

        // Published objects are readable by everyone
        if ($publicationStatus == self::PUBLICATION_STATUS_PUBLISHED) {
            return true;
        }

        // Non-published objects require authentication
        if (!$user->isAuthenticated()) {
            return false;
        }

        $userId = $user->getAttribute('user_id');
        if (!$userId) {
            return false;
        }

        // Get user groups
        $userGroups = DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        // Admin, Editor, Contributor, Translator can read unpublished
        $readGroups = [
            self::GROUP_ADMINISTRATOR,
            self::GROUP_EDITOR,
            self::GROUP_CONTRIBUTOR,
            self::GROUP_TRANSLATOR,
        ];

        return count(array_intersect($userGroups, $readGroups)) > 0;
    }

    /**
     * Forward to unauthorized page
     */
    protected function forwardUnauthorized(): void
    {
        $this->forward('admin', 'secure');
    }

    /**
     * Get information object by ID using Laravel
     */
    protected function getInformationObject(int $id): ?object
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('information_object_i18n as ioi_en', function ($join) {
                $join->on('io.id', '=', 'ioi_en.id')
                    ->where('ioi_en.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $id)
            ->select([
                'io.*',
                'slug.slug',
                DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
                DB::raw('COALESCE(ioi.extent_and_medium, ioi_en.extent_and_medium) as extent_and_medium'),
                DB::raw('COALESCE(ioi.scope_and_content, ioi_en.scope_and_content) as scope_and_content'),
            ])
            ->first();
    }

    /**
     * Get term name by ID with culture fallback
     */
    protected function getTermName(?int $termId): ?string
    {
        if (!$termId) {
            return null;
        }

        $culture = $this->getUser()->getCulture() ?? 'en';

        $name = DB::table('term_i18n')
            ->where('id', $termId)
            ->where('culture', $culture)
            ->value('name');

        if ($name === null) {
            $name = DB::table('term_i18n')
                ->where('id', $termId)
                ->where('culture', 'en')
                ->value('name');
        }

        return $name;
    }

    /**
     * Get creators for information object
     */
    protected function getCreators(int $objectId): string
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        $creators = DB::table('event as e')
            ->join('actor as a', 'e.actor_id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as ai_en', function ($join) {
                $join->on('a.id', '=', 'ai_en.id')
                    ->where('ai_en.culture', '=', 'en');
            })
            ->where('e.object_id', $objectId)
            ->where('e.type_id', self::TERM_CREATION_ID)
            ->select(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'))
            ->get();

        $names = [];
        foreach ($creators as $creator) {
            if ($creator->name) {
                $names[] = $creator->name;
            }
        }

        return implode('; ', $names);
    }

    /**
     * Get creation date for information object
     */
    protected function getCreationDate(int $objectId): ?string
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        $event = DB::table('event as e')
            ->leftJoin('event_i18n as ei', function ($join) use ($culture) {
                $join->on('e.id', '=', 'ei.id')
                    ->where('ei.culture', '=', $culture);
            })
            ->leftJoin('event_i18n as ei_en', function ($join) {
                $join->on('e.id', '=', 'ei_en.id')
                    ->where('ei_en.culture', '=', 'en');
            })
            ->where('e.object_id', $objectId)
            ->where('e.type_id', self::TERM_CREATION_ID)
            ->select([
                'e.start_date',
                'e.end_date',
                DB::raw('COALESCE(ei.date, ei_en.date) as date_display'),
            ])
            ->first();

        if (!$event) {
            return null;
        }

        if ($event->date_display) {
            return $event->date_display;
        }

        if ($event->start_date && $event->end_date) {
            return $event->start_date === $event->end_date
                ? $event->start_date
                : $event->start_date . ' - ' . $event->end_date;
        }

        return $event->start_date ?? $event->end_date;
    }

    /**
     * Get museum data for information object
     */
    protected function getMuseumData(int $objectId): ?object
    {
        return DB::table('museum_object')
            ->where('information_object_id', $objectId)
            ->first();
    }

    protected function buildComparisonData(): array
    {
        $data = [];

        foreach ($this->fieldGroups as $groupKey => $group) {
            $data[$groupKey] = [
                'label' => $group['label'],
                'fields' => [],
            ];

            foreach ($group['fields'] as $field) {
                $values = [];
                foreach ($this->objects as $object) {
                    $values[] = $this->getFieldValue($object, $field);
                }
                $data[$groupKey]['fields'][$field] = $values;
            }
        }

        return $data;
    }

    protected function getFieldValue($object, $field)
    {
        // Lazy load museum data
        static $museumDataCache = [];

        switch ($field) {
            case 'identifier':
                return $object->identifier;

            case 'title':
                return $object->title;

            case 'level_of_description':
                return $this->getTermName($object->level_of_description_id ?? null);

            case 'creator':
                return $this->getCreators($object->id);

            case 'creation_date':
                return $this->getCreationDate($object->id);

            case 'creation_place':
                // Get from museum data if available
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->creation_place ?? null;

            case 'object_type':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->object_type ?? null;

            case 'materials':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];
                if ($museum && $museum->materials) {
                    $materials = json_decode($museum->materials, true);
                    if (is_array($materials)) {
                        return implode(', ', $materials);
                    }
                }

                return null;

            case 'techniques':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];
                if ($museum && $museum->techniques) {
                    $techniques = json_decode($museum->techniques, true);
                    if (is_array($techniques)) {
                        return implode(', ', $techniques);
                    }
                }

                return null;

            case 'dimensions':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->dimensions ?? null;

            case 'inscriptions':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->inscription ?? null;

            case 'style_period':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->style_period ?? null;

            case 'school':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->school ?? null;

            case 'culture':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->cultural_context ?? null;

            case 'subject':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->subject_display ?? null;

            case 'provenance':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->provenance ?? null;

            case 'acquisition_source':
                // Get from information_object_i18n
                $culture = $this->getUser()->getCulture() ?? 'en';

                return DB::table('information_object_i18n')
                    ->where('id', $object->id)
                    ->where(function ($query) use ($culture) {
                        $query->where('culture', $culture)
                            ->orWhere('culture', 'en');
                    })
                    ->orderByRaw("CASE WHEN culture = ? THEN 0 ELSE 1 END", [$culture])
                    ->value('acquisition');

            case 'acquisition_date':
                // Get from event table with acquisition type
                $acquisitionTypeId = 112; // TERM_ACQUISITION_ID
                $event = DB::table('event')
                    ->where('object_id', $object->id)
                    ->where('type_id', $acquisitionTypeId)
                    ->select('start_date', 'end_date')
                    ->first();

                return $event ? ($event->start_date ?? $event->end_date) : null;

            case 'condition_rating':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->condition_term ?? null;

            case 'condition_notes':
                if (!isset($museumDataCache[$object->id])) {
                    $museumDataCache[$object->id] = $this->getMuseumData($object->id);
                }
                $museum = $museumDataCache[$object->id];

                return $museum->condition_notes ?? null;

            case 'insurance_value':
                // Get from GRAP data if available
                $grap = DB::table('grap_heritage_asset')
                    ->where('information_object_id', $object->id)
                    ->first();

                return $grap && $grap->insurance_coverage_actual
                    ? 'R ' . number_format($grap->insurance_coverage_actual, 2)
                    : null;

            case 'valuation_date':
                $grap = DB::table('grap_heritage_asset')
                    ->where('information_object_id', $object->id)
                    ->first();

                return $grap->last_revaluation_date ?? null;

            default:
                return null;
        }
    }
}