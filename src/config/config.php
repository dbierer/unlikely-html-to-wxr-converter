<?php
// provides configuration for WP import/export tool

use WP_CLI\Unlikely\Import\Extract;
use WP_CLI\Unlikely\Import\Transform\{
    CleanAttributes,
    Clean,
    RemoveAttributes,
    RemoveBlock,
    TableToDiv
};
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
            'wp:wxr_version' => '1.2',
            'title' => 'website title',
            'link' => 'https://website.com',
            'description' => 'Website description',
            'pubDate' => date(DATE_RSS),
            'language' => 'en-US',
            'wp:base_site_url' => 'https://website.com',
            'wp:base_blog_url' => 'https://blog.website.com',
            'wp:author' => [
                'wp:author_id' => '1',
                'wp:author_login' => ['CDATA' => 'author'],
                'wp:author_email' => ['CDATA' => 'email@website.com'],
                'wp:author_display_name' => ['CDATA' => 'Author'],
                'wp:author_first_name' => ['CDATA' => 'Fred'],
                'wp:author_last_name' => ['CDATA' => 'Flintstone'],
            ],
            'generator' => 'https://wordpress.org/?v=5.8',
        ],
    ],
    'item' => [
        'title' => [
            'CDATA' =>
                ['callback' => [
                    'class' => Extract::class,
                    'method' => 'getTitle'
                ]
            ]
        ],
        'link' =>  [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getWpLink',
                'args' => 'https://website.com'
            ]
        ],
        'pubDate' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getCreateDate'
            ]
        ],
        'dc:creator' => ['CDATA' => 'tom_wp'],
        // 'guid isPermaLink="false"' => https://website.com/?p=1, guid' =>
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
        'wp:post_id' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getNextId'
            ]
        ],
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
            'attributes' => [
                // category tag attributes
                'domain' => 'category',
                'nicename' => [
                    'callback' => [
                        'class' => Extract::class,
                        'method' => 'getLastDir'
                    ]
                ],
            ],
            // category tag value
            'CDATA' => [
                'callback' => [
                    'class' => Extract::class,
                    'method' => 'getLastDir'
                ]
            ],
        ],
    ],
    //**********************************************
    // main extraction
    //**********************************************
    Extract::class => [
        'attrib_list'  => Extract::DEFAULT_ATTR_LIST,                               // list of attributes to strip
        'delim_start'  => '<!--#include virtual="/sidemenu_include.html" -->',     // marks beginning of contents to extract
        'delim_stop'   => '<!--#include virtual="/footer_include.html" -->',       // marks end of contents to extract
        'title_regex'  => Extract::TITLE_REGEX,         // regex to extract title
        'excerpt_tags' => Extract::EXCERPT_TAGS,        // tags(s) to search for to locate extract
        'start_id'     => 101,                          // starting post ID number
        'transform' => [
            'clean' => [
                'callback' => new Clean(),
                'params' => ['bodyOnly' => TRUE]
            ],
            'remove_block' => [
                'callback' => new RemoveBlock(),
                'params' => ['start' => '<tr height="20">','stop' => '</tr>','items' => ['bkgnd_tandk.gif','trans_spacer50.gif','bkgnd_tanlt.gif']],
            ],
            'table_to_row_col_div' => [
                'callback' => new TableToDiv(),
                'params' => ['td' => 'col', 'th' => 'col bold', 'row' => 'row', 'width' => 12],
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
