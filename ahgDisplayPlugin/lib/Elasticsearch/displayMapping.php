<?php
/**
 * Elasticsearch 7 Mapping Extension for ahgDisplayPlugin
 * 
 * Adds display-specific fields to the information_object index
 */

return [
    // Display object type (archive, museum, gallery, library, dam)
    'display_object_type' => [
        'type' => 'keyword',
    ],
    
    // Display profile code
    'display_profile' => [
        'type' => 'keyword',
    ],
    
    // Extended level of description (from display_level table)
    'display_level_code' => [
        'type' => 'keyword',
    ],
    
    // Collection type
    'display_collection_type' => [
        'type' => 'keyword',
    ],
    
    // Domain for faceting (archive, museum, gallery, library, dam, universal)
    'display_domain' => [
        'type' => 'keyword',
    ],
    
    // Nested object for display-specific fields
    'display' => [
        'type' => 'object',
        'properties' => [
            // Identity fields
            'identifier' => ['type' => 'keyword', 'copy_to' => 'autocomplete'],
            'title' => ['type' => 'text', 'copy_to' => 'autocomplete'],
            'level' => ['type' => 'keyword'],
            'extent' => ['type' => 'text'],
            
            // Creator/dates
            'creator' => ['type' => 'text', 'copy_to' => 'autocomplete'],
            'creator_keyword' => ['type' => 'keyword'],
            'date_display' => ['type' => 'text'],
            'date_start' => ['type' => 'date', 'format' => 'yyyy-MM-dd||yyyy-MM||yyyy||epoch_millis'],
            'date_end' => ['type' => 'date', 'format' => 'yyyy-MM-dd||yyyy-MM||yyyy||epoch_millis'],
            
            // Description
            'scope_content' => ['type' => 'text'],
            'description' => ['type' => 'text'],
            
            // Museum-specific
            'object_number' => ['type' => 'keyword', 'copy_to' => 'autocomplete'],
            'object_name' => ['type' => 'text'],
            'materials' => ['type' => 'text'],
            'technique' => ['type' => 'keyword'],
            'dimensions' => ['type' => 'text'],
            
            // Gallery-specific
            'artist' => ['type' => 'text', 'copy_to' => 'autocomplete'],
            'artist_keyword' => ['type' => 'keyword'],
            'medium' => ['type' => 'text'],
            
            // Library-specific
            'call_number' => ['type' => 'keyword', 'copy_to' => 'autocomplete'],
            'isbn' => ['type' => 'keyword'],
            'author' => ['type' => 'text', 'copy_to' => 'autocomplete'],
            'author_keyword' => ['type' => 'keyword'],
            'publisher' => ['type' => 'text'],
            
            // DAM-specific
            'photographer' => ['type' => 'text'],
            'photographer_keyword' => ['type' => 'keyword'],
            'location_taken' => ['type' => 'text'],
            
            // Digital object info (for grid/gallery views)
            'has_digital_object' => ['type' => 'boolean'],
            'thumbnail_path' => ['type' => 'keyword', 'index' => false],
            'master_path' => ['type' => 'keyword', 'index' => false],
            'mime_type' => ['type' => 'keyword'],
            'media_type' => ['type' => 'keyword'], // image, video, audio, document
            
            // Access
            'access_conditions' => ['type' => 'text'],
            'rights_statement' => ['type' => 'keyword'],
            
            // Hierarchy info
            'ancestor_ids' => ['type' => 'integer'],
            'ancestor_slugs' => ['type' => 'keyword'],
            'parent_title' => ['type' => 'text'],
            'child_count' => ['type' => 'integer'],
            
            // Classification/subjects for faceting
            'classification' => ['type' => 'keyword'],
            'subjects' => ['type' => 'keyword'],
            'genres' => ['type' => 'keyword'],
            'places' => ['type' => 'keyword'],
        ],
    ],
];
