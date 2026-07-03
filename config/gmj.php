<?php

return [
    'beta_feedback_enabled' => (bool) env('ENABLE_BETA_FEEDBACK', false),
    'beta_feedback_email' => env('BETA_FEEDBACK_EMAIL') ?: 'discoveringfilipinomusic@gmail.com',
];
