<?php

$field = function (
    string $key,
    string $label,
    string $type,
    bool $enabled = true,
    bool $required = false,
    bool $system = false,
    ?array $options = null,
): array {
    return [
        'key' => $key,
        'label' => $label,
        'type' => $type,
        'enabled' => $enabled,
        'required' => $required,
        'system' => $system,
        'options' => $options,
    ];
};

$shared = [
    $field('category', 'Category', 'category'),
    $field('detail_page', 'Detail page', 'detail_page'),
];

return [

    'starters' => [
        'ai-tool',
        'book',
        'product',
        'portfolio',
        'article',
        'service',
        'dharmic-post',
    ],

    'category_tree' => [
        'AI' => ['AI Tools', 'AI Writing', 'AI Image', 'AI Coding'],
        'Books' => ['Technology', 'Education', 'Religious'],
        'Portfolio' => ['Web', 'Mobile', 'Software'],
    ],

    'presets' => [
        'ai-tool' => [
            'name' => 'AI Tool',
            'slug' => 'ai-tool',
            'description' => 'Tools with a logo, pricing, and a link to use them.',
            'fields' => [
                $field('title', 'Tool name', 'text', true, true, true),
                $field('logo', 'Logo', 'image'),
                $field('thumbnail', 'Thumbnail', 'image'),
                $field('short_description', 'Short description', 'long_text'),
                $field('description', 'Description', 'rich_text'),
                $field('website_url', 'Website URL', 'url'),
                $field('pricing', 'Pricing', 'text'),
                $field('rating', 'Rating', 'rating'),
                $field('features', 'Features', 'long_text'),
                $field('use_tool', 'Use tool', 'button'),
                ...$shared,
            ],
        ],
        'book' => [
            'name' => 'Book',
            'slug' => 'book',
            'description' => 'Books with a price, rating, and buy button.',
            'fields' => [
                $field('title', 'Title', 'text', true, true, true),
                $field('thumbnail', 'Thumbnail', 'image'),
                $field('author', 'Author', 'text'),
                $field('price', 'Price', 'price'),
                $field('discount', 'Discount', 'discount'),
                $field('rating', 'Rating', 'rating'),
                $field('short_description', 'Short description', 'long_text'),
                $field('description', 'Long description', 'rich_text'),
                $field('buy_now', 'Buy now', 'button'),
                ...$shared,
            ],
        ],
        'product' => [
            'name' => 'Product',
            'slug' => 'product',
            'description' => 'Products with pricing, a gallery, and a buy button.',
            'fields' => [
                $field('title', 'Title', 'text', true, true, true),
                $field('thumbnail', 'Thumbnail', 'image'),
                $field('price', 'Price', 'price'),
                $field('discount', 'Discount', 'discount'),
                $field('rating', 'Rating', 'rating'),
                $field('short_description', 'Short description', 'long_text'),
                $field('description', 'Description', 'rich_text'),
                $field('gallery', 'Gallery', 'gallery'),
                $field('buy_now', 'Buy now', 'button'),
                ...$shared,
            ],
        ],
        'portfolio' => [
            'name' => 'Portfolio',
            'slug' => 'portfolio',
            'description' => 'Projects with links, a client, and a gallery.',
            'fields' => [
                $field('title', 'Project name', 'text', true, true, true),
                $field('thumbnail', 'Thumbnail', 'image'),
                $field('description', 'Description', 'rich_text'),
                $field('technologies', 'Technologies', 'long_text'),
                $field('project_url', 'Project URL', 'url'),
                $field('github_url', 'GitHub URL', 'url'),
                $field('client', 'Client', 'text'),
                $field('completed_on', 'Date', 'date'),
                $field('gallery', 'Gallery', 'gallery'),
                ...$shared,
            ],
        ],
        'article' => [
            'name' => 'Article',
            'slug' => 'article',
            'description' => 'Articles and general posts.',
            'fields' => [
                $field('title', 'Title', 'text', true, true, true),
                $field('thumbnail', 'Thumbnail', 'image'),
                $field('short_description', 'Short description', 'long_text'),
                $field('description', 'Description', 'rich_text'),
                ...$shared,
            ],
        ],
        'service' => [
            'name' => 'Service',
            'slug' => 'service',
            'description' => 'Services with a summary and a request button.',
            'fields' => [
                $field('title', 'Title', 'text', true, true, true),
                $field('thumbnail', 'Thumbnail', 'image'),
                $field('short_description', 'Short description', 'long_text'),
                $field('description', 'Description', 'rich_text'),
                $field('price', 'Price', 'price'),
                $field('request', 'Request', 'button'),
                ...$shared,
            ],
        ],
        'dharmic-post' => [
            'name' => 'Dharmic Post',
            'slug' => 'dharmic-post',
            'description' => 'Dharmic writing with an optional video.',
            'fields' => [
                $field('title', 'Title', 'text', true, true, true),
                $field('thumbnail', 'Thumbnail', 'image'),
                $field('short_description', 'Short description', 'long_text'),
                $field('description', 'Description', 'rich_text'),
                $field('video_url', 'Video URL', 'video_url'),
                ...$shared,
            ],
        ],
        'custom' => [
            'name' => 'Custom',
            'slug' => 'custom',
            'description' => 'A blank type. Add the fields you need.',
            'fields' => [
                $field('title', 'Title', 'text', true, true, true),
                $field('short_description', 'Short description', 'long_text'),
                $field('thumbnail', 'Thumbnail', 'image'),
                $field('button', 'Button', 'button'),
                ...$shared,
            ],
        ],
    ],

];
