<?php
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Gallery Service
 *
 * Provides gallery-specific functionality:
 * - Loans (incoming/outgoing)
 * - Valuations
 * - Artists
 * - Venues/Spaces (for gallery use)
 *
 * Note: Exhibition functionality moved to standalone ahgExhibitionPlugin
 */
class GalleryService
{
    // =========================================================================
    // LOANS
    // =========================================================================

    public function getLoans(array $filters = []): array
    {
        $query = DB::table('gallery_loan');
        if (!empty($filters['type'])) $query->where('loan_type', $filters['type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    public function getLoan(int $id): ?object
    {
        $loan = DB::table('gallery_loan')->where('id', $id)->first();
        if ($loan) {
            $loan->objects = DB::table('gallery_loan_object as lo')
                ->leftJoin('information_object_i18n as i18n', function($j) {
                    $j->on('lo.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'lo.object_id', '=', 'slug.object_id')
                ->where('lo.loan_id', $id)
                ->select('lo.*', 'i18n.title as object_title', 'slug.slug')
                ->get()->toArray();
            $loan->facility_report = DB::table('gallery_facility_report')->where('loan_id', $id)->first();
        }
        return $loan;
    }

    public function createLoan(array $data): int
    {
        $loanNumber = $data['loan_type'] === 'incoming' ? 'LI-' : 'LO-';
        $loanNumber .= date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        return DB::table('gallery_loan')->insertGetId([
            'loan_number' => $loanNumber,
            'loan_type' => $data['loan_type'],
            'status' => 'inquiry',
            'purpose' => $data['purpose'] ?? null,
            'exhibition_id' => $data['exhibition_id'] ?? null,
            'institution_name' => $data['institution_name'],
            'institution_address' => $data['institution_address'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'request_date' => date('Y-m-d'),
            'loan_start_date' => $data['loan_start_date'] ?? null,
            'loan_end_date' => $data['loan_end_date'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateLoan(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return DB::table('gallery_loan')->where('id', $id)->update($data) > 0;
    }

    public function addLoanObject(int $loanId, int $objectId, array $data = []): int
    {
        return DB::table('gallery_loan_object')->insertGetId([
            'loan_id' => $loanId,
            'object_id' => $objectId,
            'insurance_value' => $data['insurance_value'] ?? null,
            'packing_instructions' => $data['packing_instructions'] ?? null,
            'display_requirements' => $data['display_requirements'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function createFacilityReport(int $loanId, array $data): int
    {
        return DB::table('gallery_facility_report')->insertGetId(array_merge($data, [
            'loan_id' => $loanId,
            'created_at' => date('Y-m-d H:i:s'),
        ]));
    }

    // =========================================================================
    // VALUATIONS
    // =========================================================================

    public function getValuations(int $objectId): array
    {
        return DB::table('gallery_valuation')
            ->where('object_id', $objectId)
            ->orderBy('valuation_date', 'desc')->get()->toArray();
    }

    public function getCurrentValuation(int $objectId, string $type = 'insurance'): ?object
    {
        return DB::table('gallery_valuation')
            ->where('object_id', $objectId)
            ->where('valuation_type', $type)
            ->where('is_current', 1)
            ->first();
    }

    public function createValuation(array $data): int
    {
        // Mark previous valuations as not current
        if ($data['is_current'] ?? true) {
            DB::table('gallery_valuation')
                ->where('object_id', $data['object_id'])
                ->where('valuation_type', $data['valuation_type'])
                ->update(['is_current' => 0]);
        }
        return DB::table('gallery_valuation')->insertGetId([
            'object_id' => $data['object_id'],
            'valuation_type' => $data['valuation_type'] ?? 'insurance',
            'value_amount' => $data['value_amount'],
            'currency' => $data['currency'] ?? 'ZAR',
            'valuation_date' => $data['valuation_date'] ?? date('Y-m-d'),
            'valid_until' => $data['valid_until'] ?? null,
            'appraiser_name' => $data['appraiser_name'] ?? null,
            'appraiser_credentials' => $data['appraiser_credentials'] ?? null,
            'appraiser_organization' => $data['appraiser_organization'] ?? null,
            'methodology' => $data['methodology'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_current' => $data['is_current'] ?? 1,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getInsurancePolicies(): array
    {
        return DB::table('gallery_insurance_policy')
            ->orderBy('end_date', 'desc')->get()->toArray();
    }

    // =========================================================================
    // ARTISTS
    // =========================================================================

    public function getArtists(array $filters = []): array
    {
        $query = DB::table('gallery_artist');
        if (!empty($filters['represented'])) $query->where('represented', 1);
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('display_name', 'LIKE', '%'.$filters['search'].'%')
                  ->orWhere('nationality', 'LIKE', '%'.$filters['search'].'%');
            });
        }
        return $query->orderBy('sort_name')->get()->toArray();
    }

    public function getArtist(int $id): ?object
    {
        $artist = DB::table('gallery_artist')->where('id', $id)->first();
        if ($artist) {
            $artist->bibliography = DB::table('gallery_artist_bibliography')
                ->where('artist_id', $id)->orderBy('publication_date', 'desc')->get()->toArray();
            $artist->exhibitions = DB::table('gallery_artist_exhibition_history')
                ->where('artist_id', $id)->orderBy('start_date', 'desc')->get()->toArray();
            // Get works from information_object if actor_id is linked
            if ($artist->actor_id) {
                $artist->works = DB::table('event as e')
                    ->join('information_object_i18n as i18n', function($j) {
                        $j->on('e.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->leftJoin('slug', 'e.object_id', '=', 'slug.object_id')
                    ->where('e.actor_id', $artist->actor_id)
                    ->where('e.type_id', 111) // Creation
                    ->select('e.object_id', 'i18n.title', 'slug.slug')
                    ->limit(50)->get()->toArray();
            }
        }
        return $artist;
    }

    public function createArtist(array $data): int
    {
        return DB::table('gallery_artist')->insertGetId([
            'actor_id' => $data['actor_id'] ?? null,
            'display_name' => $data['display_name'],
            'sort_name' => $data['sort_name'] ?? $data['display_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'death_date' => $data['death_date'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'artist_type' => $data['artist_type'] ?? 'individual',
            'medium_specialty' => $data['medium_specialty'] ?? null,
            'biography' => $data['biography'] ?? null,
            'represented' => $data['represented'] ?? 0,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateArtist(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return DB::table('gallery_artist')->where('id', $id)->update($data) > 0;
    }

    public function addBibliography(int $artistId, array $data): int
    {
        return DB::table('gallery_artist_bibliography')->insertGetId([
            'artist_id' => $artistId,
            'entry_type' => $data['entry_type'] ?? 'article',
            'title' => $data['title'],
            'author' => $data['author'] ?? null,
            'publication' => $data['publication'] ?? null,
            'publisher' => $data['publisher'] ?? null,
            'publication_date' => $data['publication_date'] ?? null,
            'pages' => $data['pages'] ?? null,
            'url' => $data['url'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function addExhibitionHistory(int $artistId, array $data): int
    {
        return DB::table('gallery_artist_exhibition_history')->insertGetId([
            'artist_id' => $artistId,
            'exhibition_type' => $data['exhibition_type'] ?? 'group',
            'title' => $data['title'],
            'venue' => $data['venue'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'curator' => $data['curator'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function getDashboardStats(): array
    {
        // Use unified exhibition table from ahgExhibitionPlugin
        return [
            'exhibitions_open' => DB::table('exhibition')->where('status', 'open')->count(),
            'exhibitions_planning' => DB::table('exhibition')->where('status', 'planning')->count(),
            'loans_active' => DB::table('gallery_loan')->whereIn('status', ['on_loan', 'in_transit_out', 'in_transit_return'])->count(),
            'loans_pending' => DB::table('gallery_loan')->whereIn('status', ['inquiry', 'requested'])->count(),
            'artists_represented' => DB::table('gallery_artist')->where('represented', 1)->count(),
            'artists_total' => DB::table('gallery_artist')->count(),
            'upcoming_exhibitions' => DB::table('exhibition')
                ->where('opening_date', '>', date('Y-m-d'))
                ->whereNotIn('status', ['cancelled', 'canceled'])
                ->orderBy('opening_date')->limit(5)->get()->toArray(),
            'expiring_loans' => DB::table('gallery_loan')
                ->where('status', 'on_loan')
                ->where('loan_end_date', '<=', date('Y-m-d', strtotime('+30 days')))
                ->orderBy('loan_end_date')->limit(5)->get()->toArray(),
        ];
    }
}
