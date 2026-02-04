<?php

namespace AhgGraphQLPlugin\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class TermType
{
    private static ?ObjectType $type = null;
    private static ?ObjectType $taxonomyType = null;

    public static function getType(): ObjectType
    {
        if (self::$type === null) {
            self::$type = new ObjectType([
                'name' => 'Term',
                'description' => 'A controlled vocabulary term',
                'fields' => function () {
                    return [
                        'id' => [
                            'type' => Type::nonNull(Type::id()),
                            'description' => 'Unique identifier',
                        ],
                        'name' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'Term name',
                            'resolve' => fn($term) => $term['name'] ?? 'Unnamed Term #' . ($term['id'] ?? '?'),
                        ],
                        'code' => [
                            'type' => Type::string(),
                            'description' => 'Term code',
                        ],
                        'taxonomy' => [
                            'type' => self::getTaxonomyType(),
                            'description' => 'Parent taxonomy',
                            'resolve' => function ($term, $args, $context) {
                                if (isset($term['taxonomy_id'])) {
                                    return $context['resolvers']->taxonomy->resolveById($term['taxonomy_id']);
                                }

                                return null;
                            },
                        ],
                        'parent' => [
                            'type' => self::getType(),
                            'description' => 'Parent term',
                            'resolve' => function ($term, $args, $context) {
                                if (isset($term['parent_id']) && $term['parent_id']) {
                                    return $context['resolvers']->taxonomy->resolveTermById($term['parent_id']);
                                }

                                return null;
                            },
                        ],
                        'children' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getType()))),
                            'description' => 'Child terms',
                            'resolve' => function ($term, $args, $context) {
                                if (isset($term['id'])) {
                                    return $context['resolvers']->taxonomy->resolveTermChildren($term['id']);
                                }

                                return [];
                            },
                        ],
                    ];
                },
            ]);
        }

        return self::$type;
    }

    public static function getTaxonomyType(): ObjectType
    {
        if (self::$taxonomyType === null) {
            self::$taxonomyType = new ObjectType([
                'name' => 'Taxonomy',
                'description' => 'A controlled vocabulary taxonomy',
                'fields' => function () {
                    return [
                        'id' => [
                            'type' => Type::nonNull(Type::id()),
                            'description' => 'Unique identifier',
                        ],
                        'name' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'Taxonomy name',
                            'resolve' => fn($taxonomy) => $taxonomy['name'] ?? 'Unnamed Taxonomy #' . ($taxonomy['id'] ?? '?'),
                        ],
                        'usage' => [
                            'type' => Type::string(),
                            'description' => 'Taxonomy usage description',
                        ],
                        'terms' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getType()))),
                            'description' => 'Terms in this taxonomy',
                            'resolve' => function ($taxonomy, $args, $context) {
                                return $context['resolvers']->taxonomy->resolveTerms($taxonomy['id']);
                            },
                        ],
                    ];
                },
            ]);
        }

        return self::$taxonomyType;
    }
}
