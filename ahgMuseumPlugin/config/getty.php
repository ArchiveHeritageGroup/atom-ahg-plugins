<?php

/**
 * Getty Vocabulary Configuration.
 *
 * Default settings for Getty vocabulary integration.
 * Override in app.yml as needed.
 */

return [
    // Getty SPARQL endpoint
    'sparql_endpoint' => 'http://vocab.getty.edu/sparql',

    // Request timeout in seconds
    'timeout' => 30,

    // Cache settings
    'cache' => [
        'enabled' => true,
        'directory' => null, // Uses sf_cache_dir/getty by default
        'ttl' => [
            'search' => 86400,    // 24 hours for search results
            'term' => 604800,     // 7 days for term details
            'hierarchy' => 604800, // 7 days for hierarchy data
        ],
    ],

    // Auto-linking settings
    'linking' => [
        'auto_confirm_threshold' => 0.9,  // Auto-confirm links with confidence >= 90%
        'batch_size' => 50,               // Process terms in batches
        'rate_limit_delay' => 100000,     // Microseconds between API calls (100ms)
    ],

    // Vocabulary defaults by field type
    'vocabulary_mapping' => [
        'materials' => [
            'vocabulary' => 'aat',
            'category' => 'materials',
        ],
        'techniques' => [
            'vocabulary' => 'aat',
            'category' => 'techniques',
        ],
        'object_types' => [
            'vocabulary' => 'aat',
            'category' => 'object_types',
        ],
        'styles_periods' => [
            'vocabulary' => 'aat',
            'category' => 'styles_periods',
        ],
        'places' => [
            'vocabulary' => 'tgn',
            'category' => null,
        ],
        'creators' => [
            'vocabulary' => 'ulan',
            'category' => null,
        ],
    ],

    // Taxonomy IDs (set after installation)
    'taxonomy_ids' => [
        'materials' => null,      // MUSEUM_MATERIALS taxonomy ID
        'techniques' => null,     // MUSEUM_TECHNIQUES taxonomy ID
        'object_types' => null,   // MUSEUM_OBJECT_TYPES taxonomy ID
        'styles_periods' => null, // STYLES_PERIODS taxonomy ID
    ],

    // UI settings
    'autocomplete' => [
        'min_length' => 2,
        'delay' => 300,
        'limit' => 10,
        'show_uri' => true,
        'show_hierarchy' => false,
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'channel' => 'getty',
        'level' => 'info', // debug, info, warning, error
    ],

    // Attribution (required by Getty license)
    'attribution' => [
        'text' => 'This vocabulary data is made available by the J. Paul Getty Trust under the Open Data Commons Attribution License (ODC-By) 1.0.',
        'show_in_export' => true,
    ],
];
