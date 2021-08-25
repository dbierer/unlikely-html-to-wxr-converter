<?php
// provides configuration for WP import/export tool

use Unlikely\Import\Extract;
use Unlikely\Import\Transform\{CleanAttributes,Clean,RemoveAttributes};
$config = [
    // main config for WP export
    'export' => [
        'rss' => [
            // RSS tag attribs
            'version' => '2.0',
            'xmlns' => [
                'excerpt' => 'http://wordpress.org/export/1.2/excerpt/',
                'content' => 'http://purl.org/rss/1.0/modules/content/',
                'wfw' => 'http://wellformedweb.org/CommentAPI/',
                'dc' => 'http://purl.org/dc/elements/1.1/',
                'wp' => 'http://wordpress.org/export/1.2/',
            ],
        ],
        'channel' => [
            'title' => 'mercurysafeandmercuryfree.com',
            'link' => 'https://mercurysafeandmercuryfree.com',
            'description' => 'Dr. Tom McGuire’s Mercury Safe Dentist Internet Directory',
            'pubDate' => date(DATE_RSS),
            'language' => 'en-US',
            'wp:wxr_version' => '1.2',
            'wp:base_site_url' => 'https://mercurysafeandmercuryfree.com',
            'wp:base_blog_url' => 'https://mercurysafeandmercuryfree.com',
            'wp:author' => [
                'wp:author_id' => '1',
                'wp:author_login' => ['CDATA' => 'tom_wp'],
                'wp:author_email' => ['CDATA' => 'tom@tommcguiredds.com'],
                'wp:author_display_name' => ['CDATA' => 'tom_wp'],
                'wp:author_first_name' => ['CDATA' => 'Tom'],
                'wp:author_last_name' => ['CDATA' => 'McGuire'],
            ],
            'generator' => 'https://wordpress.org/?v=5.8',
        ],
    ],
    'item' => [
        'title' => ['CDATA' =>
            ['callback' => [
                'class' => Extract::class,
                'method' => 'getTitle']
            ]
        ],
        'link' =>  [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getWpLink',
                'args' => 'https://mercurysafeandmercuryfree.com'
            ]
        ],
        'pubDate' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getCreateDate'
            ]
        ],
        'dc:creator' => ['CDATA' => 'tom_wp'],
        // 'guid isPermaLink="false"' => https://mercurysafeandmercuryfree.com/?p=1, guid' =>
        'description' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getExcerpt'
            ]
        ],
        'content:encoded' =>  [
            'CDATA' => [
                'callback' => [
                    'class' => Extract::class,
                    'method' => 'getHtml'
                ]
            ]
        ],
        'excerpt:encoded' => [
            'CDATA' => [
                'callback' => [
                    'class' => Extract::class,
                    'method' => 'getExcerpt'
                ]
            ]
        ],
        //'wp:post_id' => 'CALLBACK',
        'wp:post_date' => [
            'CDATA' => [
                'callback' => [
                    'class' => Extract::class,
                    'method' => 'getCreateDate'
                ]
            ]
        ],
        'wp:post_date_gmt' => [
            'CDATA' => [
                'callback' => [
                    'class' => Extract::class,
                    'method' => 'getCreateDate',
                    'args' => 'UTC'
                ]
            ]
        ],
        'wp:post_modified' => [
            'CDATA' => [
                'callback' => [
                    'class' => Extract::class,
                    'method' => 'getModifyDate'
                ]
            ]
        ],
        'wp:post_modified_gmt' => [
            'CDATA' => [
                'callback' => [
                    'class' => Extract::class,
                    'method' => 'getModifyDate',
                    'args' => 'UTC'
                ]
            ]
        ],
        'wp:comment_status' => ['CDATA' => 'open'],
        'wp:ping_status' => ['CDATA' => 'open'],
        'wp:post_name' => [
            'CDATA' => [
                'callback' => [
                    'class' => Extract::class,
                    'method' => 'getWpFilename',
                ]
            ]
        ],
        'wp:status' => ['CDATA' => 'publish'],
        'wp:post_parent' => 0,
        'wp:menu_order' => 0,
        'wp:post_type' => ['CDATA' => 'post'],
        'wp:post_password' => ['CDATA' => ''],
        'wp:is_sticky' => 0,
        'category' => [
            // category tag attributes
            'domain' => 'category',
            'nicename' => [Extract::class => 'getLastDir'],
            // category tag value
            ['CDATA' => [Extract::class => 'getLastDir']],
        ],
        'wp:postmeta' => [
          ['CDATA' => '_edit_last', ['CDATA' => '1']],
        ],
    ],
    //**********************************************
    // main extraction
    //**********************************************
    Extract::class => [
        'attrib_list'  => Extract::DEFAULT_ATTR_LIST,    // list of attributes to strip
        'delim_start'  => Extract::DELIM_START,          // marks beginning of contents to extract
        'delim_stop'   => Extract::DELIM_STOP,           // marks end of contents to extract
        'title_regex'  => Extract::TITLE_REGEX,          // regex to extract title
        'excerpt_tags' => Extract::EXCERPT_TAGS,       // tags(s) to search for to locate extract
        'transform' => [
            'clean' => [
                'callback' => new Clean(),
                'params' => ['bodyOnly' => TRUE]
            ],
            'attribs_remove' => [
                'callback' => new RemoveAttributes(),
                'params' => ['attributes' => Extract::DEFAULT_ATTR_LIST]
            ],
        ],
    ],
    //**********************************************
    // other callback classes can be registered here
    //**********************************************
];

return $config;
