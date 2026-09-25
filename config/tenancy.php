<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base domain
    |--------------------------------------------------------------------------
    |
    | Hosts equal to this domain are the platform itself. A single leading
    | label (acme.example.com) is treated as a tenant subdomain. Any other
    | host is matched against tenants.domain.
    |
    */

    'base_domain' => env('TENANCY_BASE_DOMAIN', 'localhost'),

    'reserved_subdomains' => [
        'www',
        'platform',
        'admin',
        'app',
        'mail',
        'api',
        'phpmyadmin',
    ],

];
