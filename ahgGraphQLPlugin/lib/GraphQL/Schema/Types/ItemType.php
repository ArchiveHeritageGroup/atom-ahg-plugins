<?php

namespace AhgGraphQLPlugin\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ItemType
{
    private static ?ObjectType $type = null;
    private static ?ObjectType $digitalObjectType = null;
    private static ?ObjectType $eventType = null;

    public static function getType(): ObjectType
    {
        if (self::$type === null) {
            self::$type = new ObjectType([
                'name' => 'Item',
                'description' => 'An archival description (information object)',
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
                        'identifier' => [
                            'type' => Type::string(),
                            'description' => 'Reference code or identifier',
                        ],
                        'title' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'Title of the item',
                        ],
                        'levelOfDescription' => [
                            'type' => TermType::getType(),
                            'description' => 'Level of description (fonds, series, file, item, etc.)',
                            'resolve' => function ($item, $args, $context) {
                                if (!empty($item['level_of_description'])) {
                                    return [
                                        'id' => $item['level_of_description_id'] ?? null,
                                        'name' => $item['level_of_description'],
                                    ];
                                }

                                return null;
                            },
                        ],
                        'sector' => [
                            'type' => Type::string(),
                            'description' => 'Sector (archive, library, museum)',
                        ],
                        'scopeAndContent' => [
                            'type' => Type::string(),
                            'description' => 'Scope and content',
                            'resolve' => fn($item) => $item['scope_and_content'] ?? $item['scopeAndContent'] ?? null,
                        ],
                        'extentAndMedium' => [
                            'type' => Type::string(),
                            'description' => 'Extent and medium',
                            'resolve' => fn($item) => $item['extent_and_medium'] ?? $item['extentAndMedium'] ?? null,
                        ],
                        'archivalHistory' => [
                            'type' => Type::string(),
                            'description' => 'Archival history',
                            'resolve' => fn($item) => $item['archival_history'] ?? $item['archivalHistory'] ?? null,
                        ],
                        'acquisition' => [
                            'type' => Type::string(),
                            'description' => 'Immediate source of acquisition',
                        ],
                        'arrangement' => [
                            'type' => Type::string(),
                            'description' => 'System of arrangement',
                        ],
                        'accessConditions' => [
                            'type' => Type::string(),
                            'description' => 'Conditions governing access',
                            'resolve' => fn($item) => $item['access_conditions'] ?? $item['accessConditions'] ?? null,
                        ],
                        'reproductionConditions' => [
                            'type' => Type::string(),
                            'description' => 'Conditions governing reproduction',
                            'resolve' => fn($item) => $item['reproduction_conditions'] ?? $item['reproductionConditions'] ?? null,
                        ],
                        'repository' => [
                            'type' => RepositoryType::getType(),
                            'description' => 'Repository holding this item',
                            'resolve' => function ($item, $args, $context) {
                                if (!empty($item['repository_id'])) {
                                    return $context['resolvers']->item->resolveRepository($item['repository_id']);
                                }

                                return null;
                            },
                        ],
                        'parent' => [
                            'type' => self::getType(),
                            'description' => 'Parent item in the hierarchy',
                            'resolve' => function ($item, $args, $context) {
                                if (!empty($item['parent_id'])) {
                                    return $context['resolvers']->item->resolveById($item['parent_id']);
                                }

                                return null;
                            },
                        ],
                        'children' => [
                            'type' => Type::nonNull(ConnectionTypes::connection('Item', self::getType())),
                            'description' => 'Child items',
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
                            'complexity' => fn($childrenComplexity, $args) => 10 + $childrenComplexity * ($args['first'] ?? 10),
                            'resolve' => function ($item, $args, $context) {
                                $first = min($args['first'] ?? 10, 100);
                                $offset = ScalarTypes::decodeCursor($args['after'] ?? null);

                                return $context['resolvers']->item->resolveChildren(
                                    $item['id'],
                                    $first,
                                    $offset
                                );
                            },
                        ],
                        'ancestors' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getType()))),
                            'description' => 'Ancestor items (path from root)',
                            'resolve' => function ($item, $args, $context) {
                                return $context['resolvers']->item->resolveAncestors($item['id']);
                            },
                        ],
                        'dates' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getEventType()))),
                            'description' => 'Date events associated with this item',
                            'resolve' => function ($item, $args, $context) {
                                if (isset($item['dates']) && is_array($item['dates'])) {
                                    return $item['dates'];
                                }

                                return $context['resolvers']->item->resolveDates($item['id']);
                            },
                        ],
                        'subjects' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(TermType::getType()))),
                            'description' => 'Subject access points',
                            'resolve' => function ($item, $args, $context) {
                                if (isset($item['subjects']) && is_array($item['subjects'])) {
                                    return $item['subjects'];
                                }

                                return $context['resolvers']->item->resolveSubjects($item['id']);
                            },
                        ],
                        'places' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(TermType::getType()))),
                            'description' => 'Place access points',
                            'resolve' => function ($item, $args, $context) {
                                if (isset($item['places']) && is_array($item['places'])) {
                                    return $item['places'];
                                }

                                return $context['resolvers']->item->resolvePlaces($item['id']);
                            },
                        ],
                        'creators' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(ActorType::getType()))),
                            'description' => 'Creators and related actors',
                            'resolve' => function ($item, $args, $context) {
                                if (isset($item['names']) && is_array($item['names'])) {
                                    return $item['names'];
                                }

                                return $context['resolvers']->item->resolveCreators($item['id']);
                            },
                        ],
                        'digitalObjects' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getDigitalObjectType()))),
                            'description' => 'Digital objects attached to this item',
                            'resolve' => function ($item, $args, $context) {
                                if (isset($item['digital_objects']) && is_array($item['digital_objects'])) {
                                    return $item['digital_objects'];
                                }

                                return $context['resolvers']->item->resolveDigitalObjects($item['id']);
                            },
                        ],
                        'childrenCount' => [
                            'type' => Type::nonNull(Type::int()),
                            'description' => 'Number of child items',
                            'resolve' => function ($item, $args, $context) {
                                return $item['children_count'] ?? $context['resolvers']->item->countChildren($item['id']);
                            },
                        ],
                    ];
                },
            ]);
        }

        return self::$type;
    }

    public static function getDigitalObjectType(): ObjectType
    {
        if (self::$digitalObjectType === null) {
            self::$digitalObjectType = new ObjectType([
                'name' => 'DigitalObject',
                'description' => 'A digital object (file) attached to an item',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'Unique identifier',
                    ],
                    'name' => [
                        'type' => Type::string(),
                        'description' => 'File name',
                    ],
                    'mimeType' => [
                        'type' => Type::string(),
                        'description' => 'MIME type',
                        'resolve' => fn($obj) => $obj['mime_type'] ?? $obj['mimeType'] ?? null,
                    ],
                    'byteSize' => [
                        'type' => Type::int(),
                        'description' => 'File size in bytes',
                        'resolve' => fn($obj) => $obj['byte_size'] ?? $obj['byteSize'] ?? null,
                    ],
                    'checksum' => [
                        'type' => Type::string(),
                        'description' => 'File checksum',
                    ],
                    'thumbnailUrl' => [
                        'type' => Type::string(),
                        'description' => 'URL to thumbnail image',
                        'resolve' => fn($obj) => $obj['thumbnail_url'] ?? $obj['thumbnailUrl'] ?? null,
                    ],
                    'masterUrl' => [
                        'type' => Type::string(),
                        'description' => 'URL to master file',
                        'resolve' => fn($obj) => $obj['master_url'] ?? $obj['masterUrl'] ?? null,
                    ],
                ],
            ]);
        }

        return self::$digitalObjectType;
    }

    public static function getEventType(): ObjectType
    {
        if (self::$eventType === null) {
            self::$eventType = new ObjectType([
                'name' => 'Event',
                'description' => 'A date event associated with an item',
                'fields' => [
                    'eventType' => [
                        'type' => Type::string(),
                        'description' => 'Type of event (creation, accumulation, etc.)',
                        'resolve' => fn($event) => $event['event_type'] ?? $event['eventType'] ?? null,
                    ],
                    'dateDisplay' => [
                        'type' => Type::string(),
                        'description' => 'Display date string',
                        'resolve' => fn($event) => $event['date_display'] ?? $event['dateDisplay'] ?? null,
                    ],
                    'startDate' => [
                        'type' => Type::string(),
                        'description' => 'Start date (ISO format)',
                        'resolve' => fn($event) => $event['start_date'] ?? $event['startDate'] ?? null,
                    ],
                    'endDate' => [
                        'type' => Type::string(),
                        'description' => 'End date (ISO format)',
                        'resolve' => fn($event) => $event['end_date'] ?? $event['endDate'] ?? null,
                    ],
                    'description' => [
                        'type' => Type::string(),
                        'description' => 'Event description',
                    ],
                    'actor' => [
                        'type' => ActorType::getType(),
                        'description' => 'Actor associated with this event',
                        'resolve' => function ($event, $args, $context) {
                            if (!empty($event['actor_id'])) {
                                return $context['resolvers']->actor->resolveById($event['actor_id']);
                            }

                            return null;
                        },
                    ],
                ],
            ]);
        }

        return self::$eventType;
    }
}
