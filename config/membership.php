<?php

return [
    'default' => 'free',

    'tiers' => [
        'free' => [
            'label' => 'Free',
            'reactors' => 3,
            'suggestions_per_reactor' => 3,
            'votes_per_reactor' => 3,
        ],
        'plus' => [
            'label' => 'Plus',
            'reactors' => 5,
            'suggestions_per_reactor' => 5,
            'votes_per_reactor' => 5,
        ],
        'pro' => [
            'label' => 'Pro',
            'reactors' => 10,
            'suggestions_per_reactor' => 10,
            'votes_per_reactor' => 5,
        ],
    ],
];
