<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System role templates
    |--------------------------------------------------------------------------
    |
    | Copied onto each tenant at creation. Custom roles can be added later
    | from the tenant role screen. "grant" of * includes every permission
    | belonging to enabled modules, minus except_modules.
    |
    | Module maps list the actions granted when that module is enabled.
    | Newly enabled modules pick up these actions for system roles.
    |
    */

    'templates' => [
        'tenant-owner' => [
            'name' => 'Tenant Owner',
            'description' => 'Full access to every module enabled for the workspace.',
            'grant' => '*',
        ],
        'tenant-admin' => [
            'name' => 'Tenant Admin',
            'description' => 'Manages people, content, and workspace settings.',
            'grant' => '*',
            'except_modules' => ['backup'],
        ],
        'tenant-manager' => [
            'name' => 'Tenant Manager',
            'description' => 'Publishes content and manages day-to-day records.',
            'modules' => [
                'users' => ['view', 'create', 'edit'],
                'categories' => ['view', 'create', 'edit'],
                'posts' => ['view', 'create', 'edit', 'publish'],
                'media' => ['view', 'create', 'edit', 'delete'],
                'pages' => ['view', 'create', 'edit', 'publish'],
                'form-builder' => ['view', 'create', 'edit'],
                'layout-builder' => ['view', 'create', 'edit'],
                'menu-manager' => ['view', 'create', 'edit'],
                'header-footer' => ['view', 'update'],
                'theme-settings' => ['view'],
                'seo' => ['view', 'update'],
                'analytics' => ['view'],
                'email' => ['view'],
                'feedback' => ['view', 'update', 'delete'],
            ],
        ],
        'tenant-editor' => [
            'name' => 'Tenant Editor',
            'description' => 'Creates and edits content. Publishing and deletion stay with managers.',
            'modules' => [
                'categories' => ['view', 'create', 'edit'],
                'posts' => ['view', 'create', 'edit'],
                'media' => ['view', 'create', 'edit'],
                'pages' => ['view', 'create', 'edit'],
                'feedback' => ['view'],
            ],
        ],
        'tenant-user' => [
            'name' => 'Tenant User',
            'description' => 'Read-only access to published content modules.',
            'modules' => [
                'categories' => ['view'],
                'posts' => ['view'],
                'media' => ['view'],
                'pages' => ['view'],
                'analytics' => ['view'],
            ],
        ],
    ],

];
