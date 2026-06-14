<?php

namespace AhgGraphQLPlugin\GraphQL\Resolvers;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Resolvers for the research domain. Exposes only public/approved, non-sensitive
 * data: approved researcher profiles, public projects, public annotations.
 * Sensitive columns (api_key, ORCID tokens, ID numbers) are never selected.
 */
class ResearchResolver extends BaseResolver
{
    private const RESEARCHER_COLS = [
        'id', 'first_name', 'last_name', 'email', 'institution', 'department',
        'position', 'research_interests', 'orcid_id', 'orcid_verified', 'status', 'created_at',
    ];

    private const PROJECT_COLS = [
        'id', 'title', 'description', 'project_type', 'institution', 'supervisor',
        'funding_source', 'grant_number', 'status', 'visibility', 'start_date',
        'expected_end_date', 'created_at',
    ];

    private const ANNOTATION_COLS = [
        'id', 'object_id', 'entity_type', 'annotation_type', 'title', 'content',
        'content_format', 'tags', 'visibility', 'created_at',
    ];

    // ---- researchers ------------------------------------------------------

    public function resolveResearcherById(int $id): ?array
    {
        $row = DB::table('research_researcher')
            ->where('id', $id)
            ->where('status', 'approved')
            ->first(self::RESEARCHER_COLS);

        return $row ? (array) $row : null;
    }

    public function resolveResearcherByOrcid(string $orcid): ?array
    {
        $row = DB::table('research_researcher')
            ->where('orcid_id', $orcid)
            ->where('status', 'approved')
            ->first(self::RESEARCHER_COLS);

        return $row ? (array) $row : null;
    }

    public function resolveResearchers(int $first, int $offset): array
    {
        $base = DB::table('research_researcher')->where('status', 'approved');
        $total = (clone $base)->count();
        $rows = $base->orderBy('last_name')->orderBy('first_name')
            ->offset($offset)->limit($first)
            ->get(self::RESEARCHER_COLS);

        return $this->buildConnection(array_map(fn($r) => (array) $r, $rows->all()), $total, $offset, $first);
    }

    // ---- projects (public only) ------------------------------------------

    public function resolveProjectById(int $id): ?array
    {
        $row = DB::table('research_project')
            ->where('id', $id)
            ->where('visibility', 'public')
            ->first(self::PROJECT_COLS);

        return $row ? (array) $row : null;
    }

    public function resolveProjects(int $first, int $offset): array
    {
        $base = DB::table('research_project')->where('visibility', 'public');
        $total = (clone $base)->count();
        $rows = $base->orderByDesc('created_at')
            ->offset($offset)->limit($first)
            ->get(self::PROJECT_COLS);

        return $this->buildConnection(array_map(fn($r) => (array) $r, $rows->all()), $total, $offset, $first);
    }

    // ---- annotations (public only) ---------------------------------------

    public function resolveAnnotations(int $first, int $offset, ?int $objectId = null): array
    {
        $base = DB::table('research_annotation')
            ->where('visibility', 'public')
            ->where('is_private', 0);
        if ($objectId !== null) {
            $base->where('object_id', $objectId);
        }
        $total = (clone $base)->count();
        $rows = $base->orderByDesc('created_at')
            ->offset($offset)->limit($first)
            ->get(self::ANNOTATION_COLS);

        return $this->buildConnection(array_map(fn($r) => (array) $r, $rows->all()), $total, $offset, $first);
    }
}
