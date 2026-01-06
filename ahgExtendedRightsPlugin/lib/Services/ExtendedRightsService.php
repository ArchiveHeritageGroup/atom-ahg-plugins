<?php

declare(strict_types=1);

namespace ahgExtendedRightsPlugin\Services;

use AtomExtensions\Helpers\CultureHelper;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

class ExtendedRightsService
{
    protected string $culture = 'en';

    public function setCulture(string $culture): self
    {
        $this->culture = $culture;
        return $this;
    }

    /**
     * Get Rights Statements grouped by category
     */
    public function getRightsStatementsByCategory(): array
    {
        $statements = DB::table('rights_statement as rs')
            ->join('rights_statement_i18n as rs_i18n', 'rs.id', '=', 'rs_i18n.rights_statement_id')
            ->where('rs_i18n.culture', $this->culture)
            ->where('rs.is_active', 1)
            ->select('rs.id', 'rs.code', 'rs.uri', 'rs.category', 'rs.icon_filename', 'rs_i18n.name', 'rs_i18n.definition')
            ->orderBy('rs.sort_order')
            ->get();

        $grouped = [];
        foreach ($statements as $stmt) {
            $category = $stmt->category ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $stmt;
        }

        return $grouped;
    }

    /**
     * Get Creative Commons Licenses
     */
    public function getCreativeCommonsLicenses(): Collection
    {
        return DB::table('creative_commons_license')
            ->where('is_active', 1)
            ->select('id', 'code', 'uri', 'icon_url', 'icon_filename')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($item) {
                $item->name = 'CC ' . strtoupper($item->code);
                return $item;
            });
    }

    /**
     * Get TK Labels grouped by category
     */
    public function getTkLabelsByCategory(): array
    {
        $labels = DB::table('tk_label as tk')
            ->leftJoin('tk_label_category as cat', 'tk.tk_label_category_id', '=', 'cat.id')
            ->where('tk.is_active', 1)
            ->select('tk.id', 'tk.code', 'tk.uri', 'tk.icon_url', 'tk.icon_filename', 'cat.code as category_code', 'cat.code as category_name')
            ->orderBy('cat.sort_order')
            ->orderBy('tk.sort_order')
            ->get();

        $grouped = [];
        foreach ($labels as $label) {
            $category = $label->category_code ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'name' => $label->category_name ?? ucfirst($category),
                    'labels' => [],
                ];
            }
            $label->name = $label->code; // Use code as name if no i18n
            $grouped[$category]['labels'][] = $label;
        }

        return $grouped;
    }

    /**
     * Get rights for a specific object
     */
    public function getObjectRights(int $objectId): ?object
    {
        return DB::table('extended_rights as er')
            ->leftJoin('rights_statement as rs', 'er.rights_statement_id', '=', 'rs.id')
            ->leftJoin('rights_statement_i18n as rs_i18n', function ($join) {
                $join->on('rs.id', '=', 'rs_i18n.rights_statement_id')
                    ->where('rs_i18n.culture', '=', $this->culture);
            })
            ->leftJoin('creative_commons_license as cc', 'er.creative_commons_license_id', '=', 'cc.id')
            ->where('er.object_id', $objectId)
            ->where('er.is_primary', 1)
            ->select([
                'er.*',
                'rs.code as rights_statement_code',
                'rs.uri as rights_statement_uri',
                'rs.icon_filename as rights_statement_icon',
                'rs_i18n.name as rights_statement_name',
                'cc.code as cc_license_code',
                'cc.uri as cc_license_uri',
                'cc.icon_url as cc_icon_url',
            ])
            ->first();
    }

    /**
     * Get all rights for an object (including non-primary)
     */
    public function getAllObjectRights(int $objectId): Collection
    {
        return DB::table('extended_rights as er')
            ->leftJoin('rights_statement as rs', 'er.rights_statement_id', '=', 'rs.id')
            ->leftJoin('rights_statement_i18n as rs_i18n', function ($join) {
                $join->on('rs.id', '=', 'rs_i18n.rights_statement_id')
                    ->where('rs_i18n.culture', '=', $this->culture);
            })
            ->leftJoin('creative_commons_license as cc', 'er.creative_commons_license_id', '=', 'cc.id')
            ->where('er.object_id', $objectId)
            ->select([
                'er.*',
                'rs.code as rights_statement_code',
                'rs_i18n.name as rights_statement_name',
                'cc.code as cc_license_code',
            ])
            ->orderByDesc('er.is_primary')
            ->orderByDesc('er.created_at')
            ->get();
    }

    /**
     * Get active embargo for an object
     */
    public function getActiveEmbargo(int $objectId): ?object
    {
        return DB::table('extended_rights')
            ->where('object_id', $objectId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', date('Y-m-d'))
            ->first();
    }

    /**
     * Assign rights to an object
     */
    public function assignRights(int $objectId, array $data, ?int $userId = null): int
    {
        $record = [
            'object_id' => $objectId,
            'rights_statement_id' => $data['rights_statement_id'] ?? null,
            'creative_commons_license_id' => $data['creative_commons_license_id'] ?? null,
            'rights_date' => $data['rights_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'rights_holder' => $data['rights_holder'] ?? null,
            'rights_holder_uri' => $data['rights_holder_uri'] ?? null,
            'is_primary' => $data['is_primary'] ?? true,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = DB::table('extended_rights')->insertGetId($record);

        // Handle TK Labels
        if (!empty($data['tk_label_ids'])) {
            foreach ($data['tk_label_ids'] as $tkLabelId) {
                DB::table('extended_rights_tk_label')->insert([
                    'extended_rights_id' => $id,
                    'tk_label_id' => (int) $tkLabelId,
                ]);
            }
        }

        return $id;
    }

    /**
     * Update existing rights
     */
    public function updateRights(int $rightsId, array $data, ?int $userId = null): void
    {
        $record = [
            'rights_statement_id' => $data['rights_statement_id'] ?? null,
            'creative_commons_license_id' => $data['creative_commons_license_id'] ?? null,
            'rights_date' => $data['rights_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'rights_holder' => $data['rights_holder'] ?? null,
            'rights_holder_uri' => $data['rights_holder_uri'] ?? null,
            'is_primary' => $data['is_primary'] ?? true,
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        DB::table('extended_rights')->where('id', $rightsId)->update($record);

        // Update TK Labels
        DB::table('extended_rights_tk_label')->where('extended_rights_id', $rightsId)->delete();
        if (!empty($data['tk_label_ids'])) {
            foreach ($data['tk_label_ids'] as $tkLabelId) {
                DB::table('extended_rights_tk_label')->insert([
                    'extended_rights_id' => $rightsId,
                    'tk_label_id' => (int) $tkLabelId,
                ]);
            }
        }
    }

    /**
     * Remove rights
     */
    public function removeRights(int $rightsId): void
    {
        DB::table('extended_rights_tk_label')->where('extended_rights_id', $rightsId)->delete();
        DB::table('extended_rights')->where('id', $rightsId)->delete();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'objectsWithRights' => DB::table('extended_rights')->count(),
            'activeEmbargoes' => DB::table('extended_rights')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '>', date('Y-m-d'))
                ->count(),
        ];
    }

    /**
     * Get all active embargoes
     */
    public function getActiveEmbargoes(): Collection
    {
        return DB::table('embargo as e')
            ->join('information_object as io', 'io.id', '=', 'e.object_id')
            ->leftJoin('information_object_i18n as ioi', function($j) {
                $j->on('ioi.id', '=', 'io.id')->where('ioi.culture', '=', $this->culture);
            })
            ->where('e.end_date', '>=', date('Y-m-d'))
            ->where('e.is_active', 1)
            ->select(['e.*', 'ioi.title as object_title'])
            ->orderBy('e.end_date')
            ->limit(20)
            ->get();
    }

    /**
     * Get rights statements (alias for getRightsStatementsByCategory)
     */
    public function getRightsStatements(): array
    {
        return $this->getRightsStatementsByCategory();
    }

    /**
     * Get TK labels (alias for getTkLabelsByCategory)
     */
    public function getTkLabels(): array
    {
        return $this->getTkLabelsByCategory();
    }

    /**
     * Get donors/rights holders for batch assignment
     */
    public function getDonors(int $limit = 100): Collection
    {
        try {
            return DB::table("actor as a")
                ->leftJoin("actor_i18n as ai", function($j) {
                    $j->on("ai.id", "=", "a.id")->where("ai.culture", "=", $this->culture);
                })
                ->select(["a.id", "ai.authorized_form_of_name as name"])
                ->whereNotNull("ai.authorized_form_of_name")
                ->orderBy("ai.authorized_form_of_name")
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get top level records for batch selection
     */
    public function getTopLevelRecords(int $limit = 100): Collection
    {
        try {
            return DB::table("information_object as io")
                ->leftJoin("information_object_i18n as ioi", function($j) {
                    $j->on("ioi.id", "=", "io.id")->where("ioi.culture", "=", $this->culture);
                })
                ->where("io.parent_id", 1)
                ->select(["io.id", "ioi.title"])
                ->whereNotNull("ioi.title")
                ->orderBy("ioi.title")
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Assign rights statement to object
     */
    public function assignRightsStatement(int $objectId, int $statementId): void
    {
        DB::table("extended_rights")->updateOrInsert(
            ["object_id" => $objectId],
            ["rights_statement_id" => $statementId, "updated_at" => now()]
        );
    }

    /**
     * Assign Creative Commons license to object
     */
    public function assignCreativeCommons(int $objectId, int $licenseId): void
    {
        DB::table("extended_rights")->updateOrInsert(
            ["object_id" => $objectId],
            ["creative_commons_license_id" => $licenseId, "updated_at" => now()]
        );
    }

    /**
     * Assign TK Label to object
     */
    public function assignTkLabel(int $objectId, int $labelId): void
    {
        DB::table("extended_rights_tk_label")->insertOrIgnore([
            "extended_rights_id" => $objectId,
            "tk_label_id" => $labelId,
            "created_at" => now()
        ]);
    }

    /**
     * Assign rights holder to object
     */
    public function assignRightsHolder(int $objectId, int $holderId): void
    {
        DB::table("extended_rights")->updateOrInsert(
            ["object_id" => $objectId],
            ["rights_holder_id" => $holderId, "updated_at" => now()]
        );
    }

    /**
     * Create embargo on object
     */
    public function createEmbargo(int $objectId, string $type, string $startDate, ?string $endDate = null): int
    {
        return DB::table("embargo")->insertGetId([
            "object_id" => $objectId,
            "embargo_type" => $type,
            "start_date" => $startDate,
            "end_date" => $endDate,
            "status" => "active",
            "created_at" => now()
        ]);
    }

    /**
     * Clear all rights from object
     */
    public function clearRights(int $objectId): void
    {
        DB::table("extended_rights")->where("object_id", $objectId)->delete();
        DB::table("extended_rights_tk_label")
            ->whereIn("extended_rights_id", function($q) use ($objectId) {
                $q->select("id")->from("extended_rights")->where("object_id", $objectId);
            })
            ->delete();
    }
}
