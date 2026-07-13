<?php

return [
    'schema_version' => 1,
    'categories' => [
        'request' => 'Requests', 'creator' => 'Creator', 'achievement' => 'Achievements',
        'announcement' => 'Announcements', 'account' => 'Account', 'billing' => 'Billing', 'system' => 'System',
    ],
    'audiences' => ['all', 'guide', 'creator', 'super_admin', 'account', 'billing'],
    'severities' => ['info', 'success', 'warning', 'danger'],
    'icons' => ['bell', 'check-circle', 'list-check', 'star', 'trophy', 'megaphone', 'user', 'credit-card', 'alert-triangle', 'settings', 'shield'],
];
