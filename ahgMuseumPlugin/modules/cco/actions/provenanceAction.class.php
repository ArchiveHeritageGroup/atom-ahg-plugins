<?php

use Illuminate\Database\Capsule\Manager as DB;

class ccoProvenanceAction extends sfAction
{
    public function preExecute()
    {
        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }
    }

    // ACL Group IDs
    private const GROUP_ADMINISTRATOR = 100;
    private const GROUP_EDITOR = 101;
    private const GROUP_CONTRIBUTOR = 102;
    private const GROUP_TRANSLATOR = 103;

    // Publication status
    private const STATUS_TYPE_PUBLICATION_ID = 158;
    private const PUBLICATION_STATUS_PUBLISHED = 160;

    public function execute($request)
    {
        // Get resource from slug parameter
        $slug = $request->getParameter('slug');
        
        if ($slug) {
            $this->resource = $this->getInformationObjectBySlug($slug);
        } else {
            // Try to get from route or ID parameter
            $id = $request->getParameter('id');
            if ($id) {
                $this->resource = $this->getInformationObjectById((int) $id);
            } else {
                $this->resource = null;
            }
        }
        
        $this->informationObject = $this->resource;

        if (!$this->resource) {
            $this->forward404();
        }

        if (!$this->checkAcl($this->resource, 'read')) {
            $this->forwardUnauthorized();
        }

        $this->canEdit = $this->checkAcl($this->resource, 'update');

        // Load provenance data
        try {
            $entries = DB::table('provenance_entry')
                ->where('information_object_id', $this->resource->id)
                ->orderBy('sequence', 'asc')
                ->get();
        } catch (\Exception $e) {
            $entries = collect([]);
        }

        $this->provenanceChain = [];
        foreach ($entries as $entry) {
            $entry->owner_type_label = ucfirst(str_replace('_', ' ', $entry->owner_type));
            $entry->date_display = $this->formatDateRange($entry);
            $this->provenanceChain[] = $entry;
        }

        // Generate timeline data for D3.js
        $this->timelineData = $this->generateTimelineData();
    }

    /**
     * Get information object by slug using Laravel
     */
    protected function getInformationObjectBySlug(string $slug): ?object
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        return DB::table('information_object as io')
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('information_object_i18n as ioi_en', function ($join) {
                $join->on('io.id', '=', 'ioi_en.id')
                    ->where('ioi_en.culture', '=', 'en');
            })
            ->where('slug.slug', $slug)
            ->select([
                'io.*',
                'slug.slug',
                DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
            ])
            ->first();
    }

    /**
     * Get information object by ID using Laravel
     */
    protected function getInformationObjectById(int $id): ?object
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        return DB::table('information_object as io')
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('information_object_i18n as ioi_en', function ($join) {
                $join->on('io.id', '=', 'ioi_en.id')
                    ->where('ioi_en.culture', '=', 'en');
            })
            ->where('io.id', $id)
            ->select([
                'io.*',
                'slug.slug',
                DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
            ])
            ->first();
    }

    /**
     * Check ACL permission using Laravel
     */
    protected function checkAcl(?object $resource, string $action): bool
    {
        if (!$resource) {
            return false;
        }

        $user = $this->getUser();

        // For read action, check if resource is published (public access)
        if ($action === 'read') {
            $publicationStatus = DB::table('status')
                ->where('object_id', $resource->id)
                ->where('type_id', self::STATUS_TYPE_PUBLICATION_ID)
                ->value('status_id');

            // If published, anyone can read
            if ($publicationStatus == self::PUBLICATION_STATUS_PUBLISHED) {
                return true;
            }
        }

        // Otherwise, must be authenticated
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

        // Administrator can do everything
        if (in_array(self::GROUP_ADMINISTRATOR, $userGroups)) {
            return true;
        }

        // Editor can read, update, delete
        if (in_array(self::GROUP_EDITOR, $userGroups)) {
            if (in_array($action, ['read', 'update', 'delete', 'create', 'publish'])) {
                return true;
            }
        }

        // Contributor can read and update own resources
        if (in_array(self::GROUP_CONTRIBUTOR, $userGroups)) {
            if ($action === 'read') {
                return true;
            }
            if ($action === 'update') {
                // Check if user created this resource
                $createdBy = DB::table('object')
                    ->where('id', $resource->id)
                    ->value('created_by');
                if ($createdBy == $userId) {
                    return true;
                }
            }
        }

        // Translator can read and translate
        if (in_array(self::GROUP_TRANSLATOR, $userGroups)) {
            if (in_array($action, ['read', 'translate'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Forward to unauthorized page
     */
    protected function forwardUnauthorized(): void
    {
        $this->forward('admin', 'secure');
    }

    protected function formatDateRange($entry): string
    {
        $start = $entry->start_date ?? '';
        $end = $entry->end_date ?? '';

        if ($start && $end) {
            return $start . ' - ' . $end;
        } elseif ($start) {
            return $start . ' - present';
        } elseif ($end) {
            return 'until ' . $end;
        }

        return 'Unknown';
    }

    protected function generateTimelineData(): array
    {
        $nodes = [];
        $links = [];
        $events = [];
        $minYear = null;
        $maxYear = null;

        foreach ($this->provenanceChain as $i => $entry) {
            $startYear = $entry->start_date ? (int) substr($entry->start_date, 0, 4) : null;
            $endYear = $entry->end_date ? (int) substr($entry->end_date, 0, 4) : null;

            if ($startYear) {
                $minYear = $minYear ? min($minYear, $startYear) : $startYear;
                $maxYear = $maxYear ? max($maxYear, $startYear) : $startYear;
            }
            if ($endYear) {
                $maxYear = $maxYear ? max($maxYear, $endYear) : $endYear;
            }

            $nodes[] = [
                'id' => $entry->id,
                'label' => $entry->owner_name,
                'ownerType' => $entry->owner_type,
                'startYear' => $startYear,
                'endYear' => $endYear,
                'location' => $entry->owner_location,
                'certainty' => $entry->certainty,
                'certaintyValue' => $this->getCertaintyValue($entry->certainty),
                'isGap' => (bool) $entry->is_gap,
            ];

            if ($i > 0) {
                $prevEntry = $this->provenanceChain[$i - 1];
                $links[] = [
                    'source' => $prevEntry->id,
                    'target' => $entry->id,
                    'transferType' => $entry->transfer_type,
                    'certaintyValue' => $this->getCertaintyValue($entry->certainty),
                ];

                $events[] = [
                    'year' => $startYear,
                    'label' => ucfirst(str_replace('_', ' ', $entry->transfer_type ?? '')),
                    'transferType' => $entry->transfer_type,
                    'details' => $entry->transfer_details ?? null,
                    'salePrice' => $entry->sale_price ?? null,
                    'saleCurrency' => $entry->sale_currency ?? null,
                    'auctionHouse' => $entry->auction_house ?? null,
                    'auctionLot' => $entry->auction_lot ?? null,
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
            'events' => $events,
            'dateRange' => [
                'min' => $minYear ?? 1900,
                'max' => $maxYear ?? (int) date('Y'),
            ],
        ];
    }

    protected function getCertaintyValue(?string $certainty): int
    {
        $values = [
            'certain' => 100,
            'probable' => 75,
            'possible' => 50,
            'uncertain' => 25,
            'unknown' => 0,
        ];

        return $values[$certainty ?? 'unknown'] ?? 0;
    }
}