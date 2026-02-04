<?php

namespace AhgGraphQLPlugin\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ActorType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type === null) {
            self::$type = new ObjectType([
                'name' => 'Actor',
                'description' => 'An authority record (person, family, or corporate body)',
                'fields' => function () {
                    return [
                        'id' => [
                            'type' => Type::nonNull(Type::id()),
                            'description' => 'Unique identifier',
                        ],
                        'slug' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'URL slug',
                        ],
                        'authorizedFormOfName' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'Authorized form of name',
                            'resolve' => fn($actor) => $actor['authorized_form_of_name'] ?? $actor['authorizedFormOfName'] ?? '',
                        ],
                        'entityType' => [
                            'type' => TermType::getType(),
                            'description' => 'Entity type (person, family, corporate body)',
                            'resolve' => function ($actor, $args, $context) {
                                if (!empty($actor['entity_type'])) {
                                    return [
                                        'id' => $actor['entity_type_id'] ?? null,
                                        'name' => $actor['entity_type'],
                                    ];
                                }

                                return null;
                            },
                        ],
                        'datesOfExistence' => [
                            'type' => Type::string(),
                            'description' => 'Dates of existence',
                            'resolve' => fn($actor) => $actor['dates_of_existence'] ?? $actor['datesOfExistence'] ?? null,
                        ],
                        'history' => [
                            'type' => Type::string(),
                            'description' => 'History/biography',
                        ],
                        'places' => [
                            'type' => Type::string(),
                            'description' => 'Places associated with the actor',
                        ],
                        'functions' => [
                            'type' => Type::string(),
                            'description' => 'Functions, occupations, or activities',
                        ],
                        'relatedItems' => [
                            'type' => Type::nonNull(ConnectionTypes::connection('Item', ItemType::getType())),
                            'description' => 'Items related to this actor',
                            'args' => [
                                'first' => [
                                    'type' => Type::int(),
                                    'defaultValue' => 10,
                                    'description' => 'Number of items to return',
                                ],
                                'after' => [
                                    'type' => Type::string(),
                                    'description' => 'Cursor for pagination',
                                ],
                            ],
                            'resolve' => function ($actor, $args, $context) {
                                $first = min($args['first'] ?? 10, 100);
                                $offset = ScalarTypes::decodeCursor($args['after'] ?? null);

                                return $context['resolvers']->actor->resolveRelatedItems(
                                    $actor['id'],
                                    $first,
                                    $offset
                                );
                            },
                        ],
                    ];
                },
            ]);
        }

        return self::$type;
    }
}
