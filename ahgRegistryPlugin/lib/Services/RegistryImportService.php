<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class RegistryImportService
{
    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Import from WordPress
    // =========================================================================

    /**
     * One-time migration from WordPress data export.
     *
     * @param array $data  Parsed WordPress export data with keys: institutions, vendors, software
     * @return array       Import result with counts
     */
    public function importFromWordPress(array $data): array
    {
        $results = [
            'institutions' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
            'vendors' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
            'software' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
        ];

        // Import institutions
        if (!empty($data['institutions'])) {
            $parsed = $this->parseInstitutions($data['institutions']);
            foreach ($parsed as $inst) {
                try {
                    // Skip if slug already exists
                    if (DB::table('registry_institution')->where('slug', $inst['slug'])->exists()) {
                        $results['institutions']['skipped']++;

                        continue;
                    }
                    DB::table('registry_institution')->insert($inst);
                    $results['institutions']['imported']++;
                } catch (\Exception $e) {
                    $results['institutions']['errors'][] = $inst['name'] . ': ' . $e->getMessage();
                }
            }
        }

        // Import vendors
        if (!empty($data['vendors'])) {
            $parsed = $this->parseVendors($data['vendors']);
            foreach ($parsed as $vendor) {
                try {
                    if (DB::table('registry_vendor')->where('slug', $vendor['slug'])->exists()) {
                        $results['vendors']['skipped']++;

                        continue;
                    }
                    DB::table('registry_vendor')->insert($vendor);
                    $results['vendors']['imported']++;
                } catch (\Exception $e) {
                    $results['vendors']['errors'][] = $vendor['name'] . ': ' . $e->getMessage();
                }
            }
        }

        // Import software
        if (!empty($data['software'])) {
            $parsed = $this->parseSoftware($data['software']);
            foreach ($parsed as $sw) {
                try {
                    if (DB::table('registry_software')->where('slug', $sw['slug'])->exists()) {
                        $results['software']['skipped']++;

                        continue;
                    }
                    DB::table('registry_software')->insert($sw);
                    $results['software']['imported']++;
                } catch (\Exception $e) {
                    $results['software']['errors'][] = $sw['name'] . ': ' . $e->getMessage();
                }
            }
        }

        return $results;
    }

    // =========================================================================
    // Parse Functions
    // =========================================================================

    /**
     * Map WordPress CPT fields to registry_institution schema.
     */
    public function parseInstitutions(array $data): array
    {
        $parsed = [];
        $now = date('Y-m-d H:i:s');

        foreach ($data as $item) {
            $name = trim($item['title'] ?? $item['name'] ?? $item['post_title'] ?? '');
            if (empty($name)) {
                continue;
            }

            $slug = $this->makeSlug($name, $parsed, 'registry_institution');

            $record = [
                'name' => $name,
                'slug' => $slug,
                'institution_type' => $this->mapInstitutionType($item['type'] ?? $item['institution_type'] ?? $item['post_type'] ?? ''),
                'description' => $item['description'] ?? $item['content'] ?? $item['post_content'] ?? null,
                'short_description' => $this->truncate($item['excerpt'] ?? $item['short_description'] ?? $item['post_excerpt'] ?? null, 500),
                'website' => $item['website'] ?? $item['url'] ?? $item['website_url'] ?? null,
                'email' => $item['email'] ?? $item['contact_email'] ?? null,
                'phone' => $item['phone'] ?? $item['telephone'] ?? null,
                'street_address' => $item['address'] ?? $item['street_address'] ?? null,
                'city' => $item['city'] ?? null,
                'province_state' => $item['province'] ?? $item['state'] ?? $item['province_state'] ?? null,
                'postal_code' => $item['postal_code'] ?? $item['zip'] ?? null,
                'country' => $item['country'] ?? null,
                'latitude' => isset($item['latitude']) ? (float) $item['latitude'] : null,
                'longitude' => isset($item['longitude']) ? (float) $item['longitude'] : null,
                'established_year' => isset($item['established']) ? (int) $item['established'] : null,
                'collection_summary' => $item['collection_summary'] ?? $item['holdings_summary'] ?? null,
                'is_active' => 1,
                'is_verified' => 0,
                'is_featured' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Handle GLAM sectors
            if (!empty($item['sectors']) || !empty($item['glam_sectors'])) {
                $sectors = $item['sectors'] ?? $item['glam_sectors'];
                if (is_string($sectors)) {
                    $sectors = array_map('trim', explode(',', $sectors));
                }
                $record['glam_sectors'] = json_encode(array_values(array_filter($sectors)));
            }

            // Handle descriptive standards
            if (!empty($item['standards']) || !empty($item['descriptive_standards'])) {
                $standards = $item['standards'] ?? $item['descriptive_standards'];
                if (is_string($standards)) {
                    $standards = array_map('trim', explode(',', $standards));
                }
                $record['descriptive_standards'] = json_encode(array_values(array_filter($standards)));
            }

            $parsed[] = $record;
        }

        return $parsed;
    }

    /**
     * Map WordPress CPT fields to registry_vendor schema.
     */
    public function parseVendors(array $data): array
    {
        $parsed = [];
        $now = date('Y-m-d H:i:s');

        foreach ($data as $item) {
            $name = trim($item['title'] ?? $item['name'] ?? $item['post_title'] ?? '');
            if (empty($name)) {
                continue;
            }

            $slug = $this->makeSlug($name, $parsed, 'registry_vendor');

            $record = [
                'name' => $name,
                'slug' => $slug,
                'vendor_type' => $this->mapVendorType($item['type'] ?? $item['vendor_type'] ?? ''),
                'description' => $item['description'] ?? $item['content'] ?? $item['post_content'] ?? null,
                'short_description' => $this->truncate($item['excerpt'] ?? $item['short_description'] ?? $item['post_excerpt'] ?? null, 500),
                'website' => $item['website'] ?? $item['url'] ?? null,
                'email' => $item['email'] ?? $item['contact_email'] ?? null,
                'phone' => $item['phone'] ?? null,
                'street_address' => $item['address'] ?? null,
                'city' => $item['city'] ?? null,
                'province_state' => $item['province'] ?? $item['state'] ?? null,
                'postal_code' => $item['postal_code'] ?? $item['zip'] ?? null,
                'country' => $item['country'] ?? null,
                'established_year' => isset($item['established']) ? (int) $item['established'] : null,
                'github_url' => $item['github'] ?? $item['github_url'] ?? null,
                'gitlab_url' => $item['gitlab'] ?? $item['gitlab_url'] ?? null,
                'linkedin_url' => $item['linkedin'] ?? $item['linkedin_url'] ?? null,
                'is_active' => 1,
                'is_verified' => 0,
                'is_featured' => 0,
                'client_count' => 0,
                'average_rating' => 0.00,
                'rating_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Handle specializations
            if (!empty($item['specializations']) || !empty($item['services'])) {
                $specs = $item['specializations'] ?? $item['services'];
                if (is_string($specs)) {
                    $specs = array_map('trim', explode(',', $specs));
                }
                $record['specializations'] = json_encode(array_values(array_filter($specs)));
            }

            // Handle service regions
            if (!empty($item['service_regions']) || !empty($item['regions'])) {
                $regions = $item['service_regions'] ?? $item['regions'];
                if (is_string($regions)) {
                    $regions = array_map('trim', explode(',', $regions));
                }
                $record['service_regions'] = json_encode(array_values(array_filter($regions)));
            }

            $parsed[] = $record;
        }

        return $parsed;
    }

    /**
     * Map WordPress CPT fields to registry_software schema.
     */
    public function parseSoftware(array $data): array
    {
        $parsed = [];
        $now = date('Y-m-d H:i:s');

        foreach ($data as $item) {
            $name = trim($item['title'] ?? $item['name'] ?? $item['post_title'] ?? '');
            if (empty($name)) {
                continue;
            }

            $slug = $this->makeSlug($name, $parsed, 'registry_software');

            $record = [
                'name' => $name,
                'slug' => $slug,
                'category' => $this->mapSoftwareCategory($item['category'] ?? $item['software_category'] ?? ''),
                'description' => $item['description'] ?? $item['content'] ?? $item['post_content'] ?? null,
                'short_description' => $this->truncate($item['excerpt'] ?? $item['short_description'] ?? $item['post_excerpt'] ?? null, 500),
                'website' => $item['website'] ?? $item['url'] ?? $item['homepage'] ?? null,
                'documentation_url' => $item['documentation'] ?? $item['docs_url'] ?? null,
                'license' => $item['license'] ?? null,
                'license_url' => $item['license_url'] ?? null,
                'latest_version' => $item['version'] ?? $item['latest_version'] ?? null,
                'pricing_model' => $this->mapPricingModel($item['pricing'] ?? $item['pricing_model'] ?? ''),
                'pricing_details' => $item['pricing_details'] ?? null,
                'is_active' => 1,
                'is_verified' => 0,
                'is_featured' => 0,
                'institution_count' => 0,
                'average_rating' => 0.00,
                'rating_count' => 0,
                'download_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Handle git URL
            if (!empty($item['git_url']) || !empty($item['github_url']) || !empty($item['gitlab_url'])) {
                $gitUrl = $item['git_url'] ?? $item['github_url'] ?? $item['gitlab_url'] ?? null;
                if ($gitUrl) {
                    $record['git_url'] = $gitUrl;
                    if (stripos($gitUrl, 'github.com') !== false) {
                        $record['git_provider'] = 'github';
                    } elseif (stripos($gitUrl, 'gitlab.com') !== false) {
                        $record['git_provider'] = 'gitlab';
                    } elseif (stripos($gitUrl, 'bitbucket.org') !== false) {
                        $record['git_provider'] = 'bitbucket';
                    }
                }
            }

            // Handle GLAM sectors
            if (!empty($item['sectors']) || !empty($item['glam_sectors'])) {
                $sectors = $item['sectors'] ?? $item['glam_sectors'];
                if (is_string($sectors)) {
                    $sectors = array_map('trim', explode(',', $sectors));
                }
                $record['glam_sectors'] = json_encode(array_values(array_filter($sectors)));
            }

            // Handle supported platforms
            if (!empty($item['platforms']) || !empty($item['supported_platforms'])) {
                $platforms = $item['platforms'] ?? $item['supported_platforms'];
                if (is_string($platforms)) {
                    $platforms = array_map('trim', explode(',', $platforms));
                }
                $record['supported_platforms'] = json_encode(array_values(array_filter($platforms)));
            }

            // Resolve vendor_id by name if provided
            if (!empty($item['vendor_name'])) {
                $vendor = DB::table('registry_vendor')
                    ->where('name', $item['vendor_name'])
                    ->first();
                if ($vendor) {
                    $record['vendor_id'] = $vendor->id;
                }
            } elseif (!empty($item['vendor_id'])) {
                $record['vendor_id'] = (int) $item['vendor_id'];
            }

            $parsed[] = $record;
        }

        return $parsed;
    }

    // =========================================================================
    // Preview & Execute
    // =========================================================================

    /**
     * Preview what will be imported without actually inserting.
     */
    public function preview(array $data): array
    {
        $preview = [
            'institutions' => ['new' => 0, 'duplicate' => 0, 'items' => []],
            'vendors' => ['new' => 0, 'duplicate' => 0, 'items' => []],
            'software' => ['new' => 0, 'duplicate' => 0, 'items' => []],
        ];

        if (!empty($data['institutions'])) {
            $parsed = $this->parseInstitutions($data['institutions']);
            foreach ($parsed as $inst) {
                $exists = DB::table('registry_institution')->where('slug', $inst['slug'])->exists();
                if ($exists) {
                    $preview['institutions']['duplicate']++;
                    $preview['institutions']['items'][] = ['name' => $inst['name'], 'slug' => $inst['slug'], 'status' => 'duplicate'];
                } else {
                    $preview['institutions']['new']++;
                    $preview['institutions']['items'][] = ['name' => $inst['name'], 'slug' => $inst['slug'], 'status' => 'new', 'type' => $inst['institution_type']];
                }
            }
        }

        if (!empty($data['vendors'])) {
            $parsed = $this->parseVendors($data['vendors']);
            foreach ($parsed as $vendor) {
                $exists = DB::table('registry_vendor')->where('slug', $vendor['slug'])->exists();
                if ($exists) {
                    $preview['vendors']['duplicate']++;
                    $preview['vendors']['items'][] = ['name' => $vendor['name'], 'slug' => $vendor['slug'], 'status' => 'duplicate'];
                } else {
                    $preview['vendors']['new']++;
                    $preview['vendors']['items'][] = ['name' => $vendor['name'], 'slug' => $vendor['slug'], 'status' => 'new', 'type' => $vendor['vendor_type']];
                }
            }
        }

        if (!empty($data['software'])) {
            $parsed = $this->parseSoftware($data['software']);
            foreach ($parsed as $sw) {
                $exists = DB::table('registry_software')->where('slug', $sw['slug'])->exists();
                if ($exists) {
                    $preview['software']['duplicate']++;
                    $preview['software']['items'][] = ['name' => $sw['name'], 'slug' => $sw['slug'], 'status' => 'duplicate'];
                } else {
                    $preview['software']['new']++;
                    $preview['software']['items'][] = ['name' => $sw['name'], 'slug' => $sw['slug'], 'status' => 'new', 'category' => $sw['category']];
                }
            }
        }

        return $preview;
    }

    /**
     * Run full import, skip duplicates by slug.
     */
    public function execute(array $data): array
    {
        return $this->importFromWordPress($data);
    }

    // =========================================================================
    // Field Mapping Helpers
    // =========================================================================

    /**
     * Map free-text institution type to enum value.
     */
    private function mapInstitutionType(string $type): string
    {
        $type = strtolower(trim($type));
        $map = [
            'archive' => 'archive',
            'archives' => 'archive',
            'library' => 'library',
            'libraries' => 'library',
            'museum' => 'museum',
            'museums' => 'museum',
            'gallery' => 'gallery',
            'galleries' => 'gallery',
            'dam' => 'dam',
            'digital asset' => 'dam',
            'heritage' => 'heritage_site',
            'heritage_site' => 'heritage_site',
            'research' => 'research_centre',
            'research_centre' => 'research_centre',
            'research_center' => 'research_centre',
            'government' => 'government',
            'university' => 'university',
            'academic' => 'university',
        ];

        return $map[$type] ?? 'other';
    }

    /**
     * Map free-text vendor type to enum value.
     */
    private function mapVendorType(string $type): string
    {
        $type = strtolower(trim($type));
        $map = [
            'developer' => 'developer',
            'development' => 'developer',
            'integrator' => 'integrator',
            'integration' => 'integrator',
            'consultant' => 'consultant',
            'consulting' => 'consultant',
            'service' => 'service_provider',
            'service_provider' => 'service_provider',
            'hosting' => 'hosting',
            'host' => 'hosting',
            'digitization' => 'digitization',
            'scanning' => 'digitization',
            'training' => 'training',
        ];

        return $map[$type] ?? 'other';
    }

    /**
     * Map free-text software category to enum value.
     */
    private function mapSoftwareCategory(string $category): string
    {
        $category = strtolower(trim($category));
        $map = [
            'ims' => 'ims',
            'information management' => 'ims',
            'dam' => 'dam',
            'digital asset management' => 'dam',
            'dams' => 'dams',
            'cms' => 'cms',
            'content management' => 'cms',
            'preservation' => 'preservation',
            'digital preservation' => 'preservation',
            'digitization' => 'digitization',
            'discovery' => 'discovery',
            'access' => 'discovery',
            'utility' => 'utility',
            'tool' => 'utility',
            'plugin' => 'plugin',
            'extension' => 'plugin',
            'integration' => 'integration',
        ];

        return $map[$category] ?? 'other';
    }

    /**
     * Map free-text pricing to enum value.
     */
    private function mapPricingModel(string $pricing): string
    {
        $pricing = strtolower(trim($pricing));
        $map = [
            'free' => 'free',
            'open source' => 'open_source',
            'open_source' => 'open_source',
            'oss' => 'open_source',
            'freemium' => 'freemium',
            'subscription' => 'subscription',
            'saas' => 'subscription',
            'one-time' => 'one_time',
            'one_time' => 'one_time',
            'perpetual' => 'one_time',
            'contact' => 'contact',
            'quote' => 'contact',
        ];

        return $map[$pricing] ?? 'open_source';
    }

    // =========================================================================
    // General Helpers
    // =========================================================================

    /**
     * Generate a unique slug, checking both the existing parsed batch and the database.
     */
    private function makeSlug(string $name, array $existingParsed, string $table): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        if (strlen($slug) > 200) {
            $slug = substr($slug, 0, 200);
            $slug = rtrim($slug, '-');
        }

        // Collect slugs already in this batch
        $batchSlugs = array_column($existingParsed, 'slug');

        $baseSlug = $slug;
        $counter = 1;
        while (in_array($slug, $batchSlugs) || DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Truncate text to a maximum length.
     */
    private function truncate(?string $text, int $maxLength): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim($text);
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $text = substr($text, 0, $maxLength);
        $lastSpace = strrpos($text, ' ');
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            $text = substr($text, 0, $lastSpace);
        }

        return $text . '...';
    }
}
