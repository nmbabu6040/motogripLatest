<?php

return [
    'locales' => ['en' => 'English', 'bn' => 'বাংলা'],
    'default_locale' => env('APP_LOCALE', 'en'),   // default locale has no URL prefix; others use /bn/...

    'csp_enabled' => (bool) env('MOTOGRIP_CSP', true),

    // Data retention (months). Applied by `php artisan motogrip:purge-old-data`.
    'retention' => ['inquiries' => 24, 'spam' => 3, 'dealer_rejected' => 24, 'unsubscribed' => 12, 'ip_hash_days' => 90],

    'backups' => [
        'disk' => env('BACKUP_DISK', 'backups'),
        'keep_days' => 30,
    ],

    'features' => [
        'affiliate'      => (bool) env('MOTOGRIP_FEATURE_AFFILIATE', false),
        'adsense'        => (bool) env('MOTOGRIP_FEATURE_ADSENSE', false),
        'dealer_locator' => (bool) env('MOTOGRIP_FEATURE_DEALER_LOCATOR', true),
        'guest_comments' => (bool) env('MOTOGRIP_GUEST_COMMENTS', true),
    ],

    'integrations' => [
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),
        'adsense_publisher_id' => env('ADSENSE_PUBLISHER_ID'),
    ],
];
