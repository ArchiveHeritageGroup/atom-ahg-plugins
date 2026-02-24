<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class SettingsRepository
{
    protected string $settingsTable = 'marketplace_settings';
    protected string $currencyTable = 'marketplace_currency';
    protected string $categoryTable = 'marketplace_category';
    protected string $enquiryTable = 'marketplace_enquiry';
    protected string $followTable = 'marketplace_follow';

    // =========================================================================
    // Settings
    // =========================================================================

    public function get(string $key, $default = null)
    {
        $row = DB::table($this->settingsTable)->where('setting_key', $key)->first();

        if (!$row) {
            return $default;
        }

        return match ($row->setting_type) {
            'boolean' => (bool) $row->setting_value,
            'number' => is_numeric($row->setting_value) ? (float) $row->setting_value : $default,
            'json' => json_decode($row->setting_value, true) ?? $default,
            default => $row->setting_value,
        };
    }

    public function set(string $key, $value, string $type = 'text', string $group = 'general', ?string $description = null): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
            $type = 'json';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'boolean';
        }

        $exists = DB::table($this->settingsTable)->where('setting_key', $key)->exists();

        if ($exists) {
            DB::table($this->settingsTable)->where('setting_key', $key)->update([
                'setting_value' => (string) $value,
                'setting_type' => $type,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            DB::table($this->settingsTable)->insert([
                'setting_key' => $key,
                'setting_value' => (string) $value,
                'setting_type' => $type,
                'setting_group' => $group,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getAll(?string $group = null): array
    {
        $query = DB::table($this->settingsTable);
        if ($group) {
            $query->where('setting_group', $group);
        }

        return $query->orderBy('setting_key')->get()->all();
    }

    // =========================================================================
    // Currencies
    // =========================================================================

    public function getCurrencies(bool $activeOnly = true): array
    {
        $query = DB::table($this->currencyTable);
        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('sort_order')->get()->all();
    }

    public function getCurrency(string $code): ?object
    {
        return DB::table($this->currencyTable)->where('code', $code)->first();
    }

    public function updateCurrency(string $code, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->currencyTable)->where('code', $code)->update($data) >= 0;
    }

    public function addCurrency(array $data): int
    {
        return DB::table($this->currencyTable)->insertGetId($data);
    }

    public function convertToZar(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === 'ZAR') {
            return $amount;
        }

        $currency = $this->getCurrency($fromCurrency);
        if (!$currency || $currency->exchange_rate_to_zar <= 0) {
            return $amount;
        }

        return $amount / $currency->exchange_rate_to_zar;
    }

    public function convertFromZar(float $zarAmount, string $toCurrency): float
    {
        if ($toCurrency === 'ZAR') {
            return $zarAmount;
        }

        $currency = $this->getCurrency($toCurrency);
        if (!$currency || $currency->exchange_rate_to_zar <= 0) {
            return $zarAmount;
        }

        return $zarAmount * $currency->exchange_rate_to_zar;
    }

    // =========================================================================
    // Categories
    // =========================================================================

    public function getCategories(?string $sector = null, bool $activeOnly = true): array
    {
        $query = DB::table($this->categoryTable);
        if ($sector) {
            $query->where('sector', $sector);
        }
        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('sector')->orderBy('sort_order')->get()->all();
    }

    public function getCategoryById(int $id): ?object
    {
        return DB::table($this->categoryTable)->where('id', $id)->first();
    }

    public function getCategoryBySlug(string $sector, string $slug): ?object
    {
        return DB::table($this->categoryTable)
            ->where('sector', $sector)
            ->where('slug', $slug)
            ->first();
    }

    public function createCategory(array $data): int
    {
        return DB::table($this->categoryTable)->insertGetId($data);
    }

    public function updateCategory(int $id, array $data): bool
    {
        return DB::table($this->categoryTable)->where('id', $id)->update($data) >= 0;
    }

    public function deleteCategory(int $id): bool
    {
        return DB::table($this->categoryTable)->where('id', $id)->delete() > 0;
    }

    // =========================================================================
    // Enquiries
    // =========================================================================

    public function createEnquiry(array $data): int
    {
        return DB::table($this->enquiryTable)->insertGetId($data);
    }

    public function getEnquiry(int $id): ?object
    {
        return DB::table($this->enquiryTable)->where('id', $id)->first();
    }

    public function updateEnquiry(int $id, array $data): bool
    {
        return DB::table($this->enquiryTable)->where('id', $id)->update($data) >= 0;
    }

    public function getListingEnquiries(int $listingId): array
    {
        return DB::table($this->enquiryTable)
            ->where('listing_id', $listingId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->all();
    }

    public function getSellerEnquiries(int $sellerId, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->enquiryTable . ' as e')
            ->join('marketplace_listing as l', 'e.listing_id', '=', 'l.id')
            ->select('e.*', 'l.title as listing_title', 'l.slug as listing_slug')
            ->where('l.seller_id', $sellerId);

        if ($status) {
            $query->where('e.status', $status);
        }

        $total = $query->count();
        $items = $query->orderBy('e.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    // =========================================================================
    // Follows
    // =========================================================================

    public function isFollowing(int $userId, int $sellerId): bool
    {
        return DB::table($this->followTable)
            ->where('user_id', $userId)
            ->where('seller_id', $sellerId)
            ->exists();
    }

    public function toggleFollow(int $userId, int $sellerId): bool
    {
        $exists = $this->isFollowing($userId, $sellerId);

        if ($exists) {
            DB::table($this->followTable)
                ->where('user_id', $userId)
                ->where('seller_id', $sellerId)
                ->delete();

            return false; // unfollowed
        }

        DB::table($this->followTable)->insert([
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true; // followed
    }

    public function getFollowedSellers(int $userId, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->followTable . ' as f')
            ->join('marketplace_seller as s', 'f.seller_id', '=', 's.id')
            ->select('s.*', 'f.created_at as followed_at')
            ->where('f.user_id', $userId)
            ->where('s.is_active', 1);

        $total = $query->count();
        $items = $query->orderBy('f.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }
}
