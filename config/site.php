<?php

return [

    'fonts' => [
        'instrument-sans' => 'Instrument Sans',
        'source-serif' => 'Source Serif 4',
        'system' => 'System',
    ],

    'radii' => ['0', '8', '12', '16', '24'],

    'containers' => ['960', '1120', '1280', 'full'],

    'columns' => [1, 2, 3, 4],

    'listing_modes' => [
        'pagination' => 'Pagination',
        'load_more' => 'Load more',
        'infinite' => 'Infinite scroll',
    ],

    'button_styles' => [
        'primary' => 'Primary',
        'secondary' => 'Secondary',
        'outline' => 'Outline',
    ],

    'card_styles' => [
        'raised' => 'Raised',
        'flat' => 'Flat',
        'outline' => 'Outline',
    ],

    'header_styles' => [
        'solid' => 'Solid',
        'muted' => 'Muted',
    ],

    'modes' => [
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'Match system',
    ],

    'logo_sizes' => [
        'sm' => 28,
        'md' => 40,
        'lg' => 56,
    ],

    'components' => [
        'header' => ['logo', 'menu', 'search', 'login', 'cta', 'custom_text'],
        'main' => ['hero', 'search', 'category_filter', 'content_grid', 'custom_text'],
        'footer' => ['logo', 'description', 'menu', 'contact', 'social', 'copyright', 'newsletter', 'custom_text'],
    ],

    'social_platforms' => ['facebook', 'instagram', 'x', 'youtube', 'linkedin', 'github'],

    'theme' => [
        'primary' => '#0f766e',
        'secondary' => '#115e59',
        'background' => '#fafaf9',
        'text' => '#0f172a',
        'button' => '#0f766e',
        'font' => 'instrument-sans',
        'radius' => '16',
        'card_style' => 'raised',
        'header_style' => 'solid',
        'footer_style' => 'solid',
        'container' => 'full',
        'columns' => 4,
        'mode' => 'light',
        'listing' => 'pagination',
        'per_page' => 16,
        'hover' => true,
        'card_fade' => true,
        'smooth_scroll' => true,
        'button_press' => true,
        'image_hover' => true,
        'page_fade' => true,
    ],

    'site' => [
        'logo_size' => 'md',
        'show_login' => true,
        'show_search' => true,
        'cta_label' => '',
        'cta_url' => '',
        'cta_new_tab' => false,
        'footer_description' => '',
        'copyright' => '',
        'contact_email' => '',
        'contact_phone' => '',
        'contact_address' => '',
        'newsletter' => true,
        'newsletter_heading' => 'Newsletter',
        'social' => [],
        'custom_header' => '',
        'custom_footer' => '',
    ],

    'default_blocks' => [
        ['region' => 'header', 'row' => 1, 'sort_order' => 0, 'component' => 'logo', 'col_desktop' => 1, 'col_tablet' => 1, 'col_mobile' => 1],
        ['region' => 'header', 'row' => 1, 'sort_order' => 1, 'component' => 'menu', 'col_desktop' => 3, 'col_tablet' => 1, 'col_mobile' => 1],
        ['region' => 'main', 'row' => 1, 'sort_order' => 0, 'component' => 'hero', 'col_desktop' => 4, 'col_tablet' => 2, 'col_mobile' => 1],
        ['region' => 'main', 'row' => 2, 'sort_order' => 0, 'component' => 'content_grid', 'col_desktop' => 4, 'col_tablet' => 2, 'col_mobile' => 1],
        ['region' => 'footer', 'row' => 1, 'sort_order' => 0, 'component' => 'description', 'col_desktop' => 2, 'col_tablet' => 2, 'col_mobile' => 1],
        ['region' => 'footer', 'row' => 1, 'sort_order' => 1, 'component' => 'menu', 'col_desktop' => 1, 'col_tablet' => 1, 'col_mobile' => 1],
        ['region' => 'footer', 'row' => 1, 'sort_order' => 2, 'component' => 'contact', 'col_desktop' => 1, 'col_tablet' => 1, 'col_mobile' => 1],
        ['region' => 'footer', 'row' => 2, 'sort_order' => 0, 'component' => 'social', 'col_desktop' => 2, 'col_tablet' => 1, 'col_mobile' => 1],
        ['region' => 'footer', 'row' => 2, 'sort_order' => 1, 'component' => 'newsletter', 'col_desktop' => 2, 'col_tablet' => 1, 'col_mobile' => 1],
        ['region' => 'footer', 'row' => 3, 'sort_order' => 0, 'component' => 'copyright', 'col_desktop' => 4, 'col_tablet' => 2, 'col_mobile' => 1],
    ],

];
