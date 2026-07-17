<?php

$make = static fn (
    string $key,
    string $subject,
    string $track,
    int $level,
    string $name,
    string $description,
    int $threshold,
    string $icon,
    string $style,
    int $order,
): array => [
    'key' => $key,
    'subject_type' => $subject,
    'track' => $track,
    'level' => $level,
    'name' => $name,
    'description' => $description,
    'threshold' => $threshold,
    'icon_key' => $icon,
    'badge_style_key' => $style,
    'public_visibility_default' => true,
    'display_order' => $order,
    'active' => true,
    'version' => 1,
];

return [
    'icons' => ['footprint', 'trail-marker', 'binoculars', 'route', 'summit', 'boots', 'map', 'compass', 'gps', 'globe', 'handshake', 'ripple', 'path', 'community', 'ear', 'calendar', 'network'],
    'styles' => ['emerald', 'sky', 'violet', 'amber', 'rose', 'indigo', 'slate'],
    'tracks' => [
        'guide_creator_exploration' => ['label' => 'Creator Exploration', 'subject_type' => 'guide', 'display_order' => 10, 'icon_key' => 'globe', 'accent' => 'violet'],
        'guide_influence' => ['label' => 'Influence', 'subject_type' => 'guide', 'display_order' => 20, 'icon_key' => 'ripple', 'accent' => 'amber'],
        'guide_requests_published' => ['label' => 'Published Requests', 'subject_type' => 'guide', 'display_order' => 30, 'icon_key' => 'route', 'accent' => 'emerald'],
        'guide_requests_submitted' => ['label' => 'Request Participation', 'subject_type' => 'guide', 'display_order' => 40, 'icon_key' => 'trail-marker', 'accent' => 'emerald'],
        'guide_supported_publications' => ['label' => 'Supported Requests', 'subject_type' => 'guide', 'display_order' => 50, 'icon_key' => 'boots', 'accent' => 'sky'],
        'creator_community_publications' => ['label' => 'Community Publications', 'subject_type' => 'creator'],
        'creator_consistency' => ['label' => 'Publishing Consistency', 'subject_type' => 'creator'],
        'creator_community_reach' => ['label' => 'Community Reach', 'subject_type' => 'creator'],
    ],
    'definitions' => [
        $make('guide.requests_submitted.tenderfoot', 'guide', 'guide_requests_submitted', 1, 'Tenderfoot', 'Submitted your first request.', 1, 'footprint', 'emerald', 10),

        $make('guide.published_requests.trailblazer', 'guide', 'guide_requests_published', 1, 'Trailblazer', 'Your first request was published.', 1, 'trail-marker', 'emerald', 20),
        $make('guide.published_requests.scout', 'guide', 'guide_requests_published', 2, 'Scout', 'Five of your requests were published.', 5, 'binoculars', 'emerald', 21),
        $make('guide.published_requests.tracker', 'guide', 'guide_requests_published', 3, 'Tracker', 'Ten of your requests were published.', 10, 'route', 'emerald', 22),
        $make('guide.published_requests.pathfinder', 'guide', 'guide_requests_published', 4, 'Pathfinder', 'Twenty-five of your requests were published.', 25, 'summit', 'emerald', 23),

        $make('guide.supported_publications.hiking_boots', 'guide', 'guide_supported_publications', 1, 'Hiking Boots', 'A request you supported was published.', 1, 'boots', 'sky', 30),
        $make('guide.supported_publications.trail_map', 'guide', 'guide_supported_publications', 2, 'Trail Map', 'Five requests you supported were published.', 5, 'map', 'sky', 31),
        $make('guide.supported_publications.compass', 'guide', 'guide_supported_publications', 3, 'Compass', 'Ten requests you supported were published.', 10, 'compass', 'sky', 32),
        $make('guide.supported_publications.gps_navigator', 'guide', 'guide_supported_publications', 4, 'GPS Navigator', 'Twenty-five requests you supported were published.', 25, 'gps', 'sky', 33),

        $make('guide.creator_exploration.explorer', 'guide', 'guide_creator_exploration', 1, 'Explorer', 'Favorited your first creator.', 1, 'globe', 'violet', 40),
        $make('guide.creator_exploration.community_connector', 'guide', 'guide_creator_exploration', 2, 'Community Connector', 'Reached three favorite creators.', 3, 'handshake', 'violet', 41),
        $make('guide.creator_exploration.ambassador', 'guide', 'guide_creator_exploration', 3, 'Ambassador', 'Reached five favorite creators.', 5, 'compass', 'violet', 42),

        $make('guide.influence.first_footprint', 'guide', 'guide_influence', 1, 'First Footprint', 'Influenced your first published request.', 1, 'footprint', 'amber', 50),
        $make('guide.influence.ripple_maker', 'guide', 'guide_influence', 2, 'Ripple Maker', 'Influenced ten published requests.', 10, 'ripple', 'amber', 51),
        $make('guide.influence.journey_shaper', 'guide', 'guide_influence', 3, 'Journey Shaper', 'Influenced twenty-five published requests.', 25, 'path', 'amber', 52),
        $make('guide.influence.community_navigator', 'guide', 'guide_influence', 4, 'Community Navigator', 'Influenced fifty published requests.', 50, 'compass', 'amber', 53),
        $make('guide.influence.legacy_guide', 'guide', 'guide_influence', 5, 'Legacy Guide', 'Influenced one hundred published requests.', 100, 'summit', 'amber', 54),
        $make('creator.community_publications.first_step', 'creator', 'creator_community_publications', 1, 'First Step', 'Published your first community request.', 1, 'footprint', 'indigo', 60),
        $make('creator.community_publications.listener', 'creator', 'creator_community_publications', 2, 'Listener', 'Published five community requests.', 5, 'ear', 'indigo', 61),
        $make('creator.community_publications.community_builder', 'creator', 'creator_community_publications', 3, 'Community Builder', 'Published ten community requests.', 10, 'community', 'indigo', 62),
        $make('creator.community_publications.journey_partner', 'creator', 'creator_community_publications', 4, 'Journey Partner', 'Published twenty-five community requests.', 25, 'handshake', 'indigo', 63),
        $make('creator.community_publications.community_champion', 'creator', 'creator_community_publications', 5, 'Community Champion', 'Published fifty community requests.', 50, 'summit', 'indigo', 64),

        $make('creator.consistency.momentum', 'creator', 'creator_consistency', 1, 'Momentum', 'Published community requests in three different months.', 3, 'calendar', 'rose', 70),
        $make('creator.consistency.on_the_trail', 'creator', 'creator_consistency', 2, 'On the Trail', 'Published community requests in six different months.', 6, 'route', 'rose', 71),
        $make('creator.consistency.long_haul_creator', 'creator', 'creator_consistency', 3, 'Long-Haul Creator', 'Published community requests in twelve different months.', 12, 'summit', 'rose', 72),

        $make('creator.community_reach.gathering_crowd', 'creator', 'creator_community_reach', 1, 'Gathering Crowd', 'Connected with twenty-five unique Guides.', 25, 'community', 'slate', 80),
        $make('creator.community_reach.growing_community', 'creator', 'creator_community_reach', 2, 'Growing Community', 'Connected with one hundred unique Guides.', 100, 'network', 'slate', 81),
        $make('creator.community_reach.community_hub', 'creator', 'creator_community_reach', 3, 'Community Hub', 'Connected with five hundred unique Guides.', 500, 'network', 'slate', 82),
        $make('creator.community_reach.movement_maker', 'creator', 'creator_community_reach', 4, 'Movement Maker', 'Connected with one thousand unique Guides.', 1000, 'summit', 'slate', 83),
    ],
];
