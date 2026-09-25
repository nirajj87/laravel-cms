<?php

return [

    'robots' => [
        'index,follow',
        'index,nofollow',
        'noindex,follow',
        'noindex,nofollow',
    ],

    'twitter_cards' => [
        'summary' => 'Summary',
        'summary_large_image' => 'Summary with large image',
    ],

    'schema_types' => [
        'book' => 'Book',
        'product' => 'Product',
        'ai-tool' => 'SoftwareApplication',
        'article' => 'Article',
        'dharmic-post' => 'Article',
        'portfolio' => 'CreativeWork',
        'service' => 'Service',
    ],

    'email_templates' => [
        'contact' => [
            'name' => 'Contact form',
            'subject' => 'New message from {{name}}',
            'body' => "Name: {{name}}\nEmail: {{email}}\n\n{{message}}",
        ],
        'feedback' => [
            'name' => 'Feedback',
            'subject' => 'New feedback from {{name}}',
            'body' => "{{name}} ({{email}}) rated {{rating}} out of 5.\n\n{{comment}}",
        ],
        'registration' => [
            'name' => 'Registration',
            'subject' => 'Welcome to {{tenant}}',
            'body' => "Hello {{name}},\n\nYour workspace account at {{tenant}} is ready.",
        ],
        'password_reset' => [
            'name' => 'Password reset',
            'subject' => 'Reset your {{tenant}} password',
            'body' => "Hello {{name}},\n\nUse the password reset page to choose a new password. The link expires soon.",
        ],
        'notification' => [
            'name' => 'Notification',
            'subject' => '{{tenant}}: {{subject}}',
            'body' => '{{message}}',
        ],
    ],

    'captcha_providers' => [
        'none' => 'Off',
        'turnstile' => 'Cloudflare Turnstile',
        'recaptcha' => 'Google reCAPTCHA',
    ],

];
