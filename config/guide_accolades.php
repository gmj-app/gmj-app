<?php

return [
    'early_guide_tiers' => [
        [
            'key' => 'founding_guide',
            'label' => 'Founding Guide',
            'min' => 1,
            'max' => 100,
            'variant' => 'gold',
            'short_label' => 'Founding Guide',
            'css_class' => 'accolade-founding',
            'description' => 'First 100 guides to join Guide My Journey.',
            'priority' => 100,
        ],
        [
            'key' => 'og_guide',
            'label' => 'OG Guide',
            'min' => 101,
            'max' => 500,
            'variant' => 'silver',
            'short_label' => 'OG',
            'css_class' => 'accolade-og',
            'description' => 'Guides 101 through 500 to join Guide My Journey.',
            'priority' => 90,
        ],
    ],
];
