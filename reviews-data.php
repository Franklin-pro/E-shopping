<?php
/**
 * Seed reviews keyed by product id.
 * Later, replace this with:
 *   SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC
 */

$seedReviews = [
    1 => [
        [
            'author'  => 'Aline M.',
            'rating'  => 5,
            'comment' => 'Sound quality is amazing for the price. Battery easily lasts a full day of meetings. Highly recommend.',
            'date'    => date('Y-m-d H:i', strtotime('-3 days')),
        ],
        [
            'author'  => 'Kevin R.',
            'rating'  => 4,
            'comment' => 'Great noise cancellation. The earcups are a little tight for long sessions but overall solid.',
            'date'    => date('Y-m-d H:i', strtotime('-12 days')),
        ],
        [
            'author'  => 'Diane K.',
            'rating'  => 5,
            'comment' => 'Pairs instantly with my laptop and phone at the same time. Love it.',
            'date'    => date('Y-m-d H:i', strtotime('-1 month')),
        ],
    ],
    2 => [
        [
            'author'  => 'Eric N.',
            'rating'  => 5,
            'comment' => 'The heart-rate tracking is spot-on. Battery life is genuinely 7 days with moderate use.',
            'date'    => date('Y-m-d H:i', strtotime('-5 days')),
        ],
        [
            'author'  => 'Sandrine U.',
            'rating'  => 4,
            'comment' => 'Stylish and lightweight. Only wish the screen were a touch brighter outdoors.',
            'date'    => date('Y-m-d H:i', strtotime('-20 days')),
        ],
    ],
    3 => [
        [
            'author'  => 'Patrick B.',
            'rating'  => 5,
            'comment' => 'Leather smells great and the stitching feels premium. Fits my 15" laptop easily.',
            'date'    => date('Y-m-d H:i', strtotime('-8 days')),
        ],
    ],
    5 => [
        [
            'author'  => 'Claudine A.',
            'rating'  => 4,
            'comment' => 'Beautiful mugs, glazing is even. One arrived with a tiny chip but customer service was fast.',
            'date'    => date('Y-m-d H:i', strtotime('-15 days')),
        ],
        [
            'author'  => 'Tom H.',
            'rating'  => 5,
            'comment' => 'Perfect size for coffee and tea. They keep drinks warm longer than my old set.',
            'date'    => date('Y-m-d H:i', strtotime('-2 months')),
        ],
    ],
    8 => [
        [
            'author'  => 'Grace M.',
            'rating'  => 5,
            'comment' => 'Super grippy, no slipping even in hot yoga. Thickness is just right.',
            'date'    => date('Y-m-d H:i', strtotime('-6 days')),
        ],
    ],
];