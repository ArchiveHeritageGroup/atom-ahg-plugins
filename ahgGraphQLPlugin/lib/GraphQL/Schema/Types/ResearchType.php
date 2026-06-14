<?php

namespace AhgGraphQLPlugin\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * GraphQL types for the research domain (ahgResearchPlugin): researcher
 * profiles, research projects, and annotations.
 *
 * Resolvers return associative arrays keyed by DB column; only public/approved,
 * non-sensitive fields are exposed (no API keys, ID numbers, ORCID tokens).
 * Email is gated to admins / the user themselves.
 */
class ResearchType
{
    private static ?ObjectType $researcher = null;
    private static ?ObjectType $project = null;
    private static ?ObjectType $annotation = null;

    public static function getResearcherType(): ObjectType
    {
        if (self::$researcher === null) {
            self::$researcher = new ObjectType([
                'name' => 'Researcher',
                'description' => 'A registered researcher profile',
                'fields' => [
                    'id' => ['type' => Type::nonNull(Type::id())],
                    'firstName' => ['type' => Type::string(), 'resolve' => fn($r) => $r['first_name'] ?? null],
                    'lastName' => ['type' => Type::string(), 'resolve' => fn($r) => $r['last_name'] ?? null],
                    'fullName' => [
                        'type' => Type::string(),
                        'resolve' => fn($r) => trim((string) ($r['first_name'] ?? '') . ' ' . (string) ($r['last_name'] ?? '')) ?: null,
                    ],
                    'institution' => ['type' => Type::string()],
                    'department' => ['type' => Type::string()],
                    'position' => ['type' => Type::string()],
                    'researchInterests' => ['type' => Type::string(), 'resolve' => fn($r) => $r['research_interests'] ?? null],
                    'orcid' => ['type' => Type::string(), 'resolve' => fn($r) => $r['orcid_id'] ?? null],
                    'orcidVerified' => ['type' => Type::boolean(), 'resolve' => fn($r) => (bool) ($r['orcid_verified'] ?? false)],
                    'status' => ['type' => Type::string()],
                    'createdAt' => ['type' => Type::string(), 'resolve' => fn($r) => $r['created_at'] ?? null],
                    'email' => [
                        'type' => Type::string(),
                        'description' => 'Email (admin or self only)',
                        'resolve' => function ($r, $args, $context) {
                            if (!empty($context['isAdmin'])) {
                                return $r['email'] ?? null;
                            }

                            return null;
                        },
                    ],
                ],
            ]);
        }

        return self::$researcher;
    }

    public static function getProjectType(): ObjectType
    {
        if (self::$project === null) {
            self::$project = new ObjectType([
                'name' => 'ResearchProject',
                'description' => 'A research project',
                'fields' => [
                    'id' => ['type' => Type::nonNull(Type::id())],
                    'title' => ['type' => Type::nonNull(Type::string())],
                    'description' => ['type' => Type::string()],
                    'projectType' => ['type' => Type::string(), 'resolve' => fn($r) => $r['project_type'] ?? null],
                    'institution' => ['type' => Type::string()],
                    'supervisor' => ['type' => Type::string()],
                    'fundingSource' => ['type' => Type::string(), 'resolve' => fn($r) => $r['funding_source'] ?? null],
                    'grantNumber' => ['type' => Type::string(), 'resolve' => fn($r) => $r['grant_number'] ?? null],
                    'status' => ['type' => Type::string()],
                    'visibility' => ['type' => Type::string()],
                    'startDate' => ['type' => Type::string(), 'resolve' => fn($r) => $r['start_date'] ?? null],
                    'expectedEndDate' => ['type' => Type::string(), 'resolve' => fn($r) => $r['expected_end_date'] ?? null],
                    'createdAt' => ['type' => Type::string(), 'resolve' => fn($r) => $r['created_at'] ?? null],
                ],
            ]);
        }

        return self::$project;
    }

    public static function getAnnotationType(): ObjectType
    {
        if (self::$annotation === null) {
            self::$annotation = new ObjectType([
                'name' => 'Annotation',
                'description' => 'A research annotation on an archival object',
                'fields' => [
                    'id' => ['type' => Type::nonNull(Type::id())],
                    'annotationType' => ['type' => Type::string(), 'resolve' => fn($r) => $r['annotation_type'] ?? null],
                    'title' => ['type' => Type::string()],
                    'content' => ['type' => Type::string()],
                    'contentFormat' => ['type' => Type::string(), 'resolve' => fn($r) => $r['content_format'] ?? null],
                    'objectId' => ['type' => Type::id(), 'resolve' => fn($r) => $r['object_id'] ?? null],
                    'entityType' => ['type' => Type::string(), 'resolve' => fn($r) => $r['entity_type'] ?? null],
                    'tags' => ['type' => Type::string()],
                    'visibility' => ['type' => Type::string()],
                    'createdAt' => ['type' => Type::string(), 'resolve' => fn($r) => $r['created_at'] ?? null],
                ],
            ]);
        }

        return self::$annotation;
    }
}
