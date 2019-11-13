<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // использую docker, у каждого образа свой индификатор, он какрас в /etc/hostname, не использую git ткк .git апка не выгружается
     'release' => trim(exec('cat /etc/hostname')),

    // Capture bindings on SQL queries
    'breadcrumbs.sql_bindings' => true,
];
