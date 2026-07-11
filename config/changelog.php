<?php

return [
    'path' => storage_path('app/changelog.json'),
    'limit' => 50,
    'subject_max_length' => 180,
    'excluded_prefixes' => [
        'wip',
        'temp',
        'debug',
        'fix typo',
        'merge',
        'chore(deps)',
    ],
    'excluded_patterns' => [
        '/\b(lockfile|automated deploy|formatting only)\b/i',
    ],
];
