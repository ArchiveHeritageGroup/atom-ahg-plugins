<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * InstitutionalShareService - Inter-Institutional Project Sharing
 *
 * Manages institution registry, share links, external collaborators,
 * and token-based access for cross-institutional research.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class InstitutionalShareService
{
    // =========================================================================
    // INSTITUTION MANAGEMENT
    // =========================================================================

    public function getInstitutions(bool $activeOnly = true): array
    {
        $query = DB::table('research_institution');
        if ($activeOnly) {
            $query->where('is_active', 1);
        }
        return $query->orderBy('name')->get()->toArray();
    }

    public function getInstitution(int $id): ?object
    {
        return DB::table('research_institution')->where('id', $id)->first();
    }

    public function createInstitution(array $data): int
    {
        return DB::table('research_institution')->insertGetId([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'logo_path' => $data['logo_path'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateInstitution(int $id, array $data): bool
    {
        $update = [];
        foreach (['name', 'code', 'description', 'url', 'contact_name', 'contact_email', 'logo_path', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        return DB::table('research_institution')->where('id', $id)->update($update) > 0;
    }

    // =========================================================================
    // SHARE MANAGEMENT
    // =========================================================================

    /**
     * Create a share link for a project.
     */
    public function createShare(int $projectId, int $sharedBy, array $data): int
    {
        return DB::table('research_institutional_share')->insertGetId([
            'project_id' => $projectId,
            'institution_id' => $data['institution_id'] ?? null,
            'share_token' => bin2hex(random_bytes(32)),
            'share_type' => $data['share_type'] ?? 'view',
            'shared_by' => $sharedBy,
            'status' => 'pending',
            'message' => $data['message'] ?? null,
            'permissions' => isset($data['permissions']) ? json_encode($data['permissions']) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get shares for a project.
     */
    public function getShares(int $projectId): array
    {
        return DB::table('research_institutional_share as s')
            ->leftJoin('research_institution as i', 's.institution_id', '=', 'i.id')
            ->leftJoin('research_researcher as r', 's.shared_by', '=', 'r.id')
            ->where('s.project_id', $projectId)
            ->select('s.*', 'i.name as institution_name', 'r.first_name as sharer_first_name', 'r.last_name as sharer_last_name')
            ->orderBy('s.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get a share by token.
     */
    public function getShareByToken(string $token): ?object
    {
        $share = DB::table('research_institutional_share as s')
            ->leftJoin('research_institution as i', 's.institution_id', '=', 'i.id')
            ->leftJoin('research_project as p', 's.project_id', '=', 'p.id')
            ->leftJoin('research_researcher as r', 's.shared_by', '=', 'r.id')
            ->where('s.share_token', $token)
            ->select('s.*', 'i.name as institution_name', 'p.title as project_title', 'p.description as project_description',
                'r.first_name as sharer_first_name', 'r.last_name as sharer_last_name')
            ->first();

        if ($share && $share->expires_at && strtotime($share->expires_at) < time()) {
            DB::table('research_institutional_share')
                ->where('id', $share->id)
                ->update(['status' => 'expired']);
            $share->status = 'expired';
        }

        return $share;
    }

    /**
     * Accept a share link.
     */
    public function acceptShare(int $shareId, int $acceptedBy): bool
    {
        return DB::table('research_institutional_share')
            ->where('id', $shareId)
            ->where('status', 'pending')
            ->update([
                'status' => 'active',
                'accepted_by' => $acceptedBy,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Revoke a share.
     */
    public function revokeShare(int $shareId): bool
    {
        return DB::table('research_institutional_share')
            ->where('id', $shareId)
            ->update([
                'status' => 'revoked',
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    // =========================================================================
    // EXTERNAL COLLABORATORS
    // =========================================================================

    /**
     * Add an external collaborator to a share.
     */
    public function addExternalCollaborator(int $shareId, array $data): int
    {
        return DB::table('research_external_collaborator')->insertGetId([
            'share_id' => $shareId,
            'name' => $data['name'],
            'email' => $data['email'],
            'institution' => $data['institution'] ?? null,
            'orcid_id' => $data['orcid_id'] ?? null,
            'access_token' => bin2hex(random_bytes(32)),
            'role' => $data['role'] ?? 'viewer',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Authenticate an external collaborator by access token.
     */
    public function authenticateExternal(string $accessToken): ?object
    {
        $collab = DB::table('research_external_collaborator as ec')
            ->join('research_institutional_share as s', 'ec.share_id', '=', 's.id')
            ->where('ec.access_token', $accessToken)
            ->where('s.status', 'active')
            ->select('ec.*', 's.project_id', 's.share_type', 's.permissions')
            ->first();

        if ($collab) {
            DB::table('research_external_collaborator')
                ->where('id', $collab->id)
                ->update(['last_accessed_at' => date('Y-m-d H:i:s')]);
        }

        return $collab;
    }

    /**
     * Get external collaborators for a share.
     */
    public function getExternalCollaborators(int $shareId): array
    {
        return DB::table('research_external_collaborator')
            ->where('share_id', $shareId)
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}
