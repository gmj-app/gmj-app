<?php

return [
    'beta_feedback_enabled' => (bool) env('ENABLE_BETA_FEEDBACK', false),
    'beta_feedback_email' => env('BETA_FEEDBACK_EMAIL') ?: 'discoveringfilipinomusic@gmail.com',

    'paid_plans_enabled' => env('ENABLE_PAID_PLANS', false),
    'plan_testing_enabled' => env('ENABLE_PLAN_TESTING', false),
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', env('ADMIN_EMAILS', '')),
    ))),
];
