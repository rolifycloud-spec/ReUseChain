<?php
if (!defined('ABSPATH')) exit;

function rc_get_co2_categories_config() {
    return [
        'plastika' => [
            'label'       => 'Plastika',
            'icon'        => '🧴',
            'co2_factor'  => 1.5,
            'avg_weight'  => 0.5,
        ],

        'metalni-otpad' => [
            'label'       => 'Metalni otpad',
            'icon'        => '🔩',
            'co2_factor'  => 1.4,
            'avg_weight'  => 2.0,
        ],

        'stakleni-otpad' => [
            'label'       => 'Stakleni otpad',
            'icon'        => '🧪',
            'co2_factor'  => 0.3,
            'avg_weight'  => 1.0,
        ],

        'papirni-otpad' => [
            'label'       => 'Papirni otpad',
            'icon'        => '📄',
            'co2_factor'  => 1.0,
            'avg_weight'  => 0.2,
        ],

        'tekstilni-otpad' => [
            'label'       => 'Tekstilni otpad',
            'icon'        => '👕',
            'co2_factor'  => 2.0,
            'avg_weight'  => 0.4,
        ],

        'elektronski-otpad' => [
            'label'       => 'Elektronski otpad',
            'icon'        => '💻',
            'co2_factor'  => 10.0,
            'avg_weight'  => 3.0,
        ],

        'drvni-otpad' => [
            'label'       => 'Drvni otpad',
            'icon'        => '🪵',
            'co2_factor'  => 0.5,
            'avg_weight'  => 5.0,
        ],

        'poljoprivredni-otpad' => [
            'label'       => 'Poljoprivredni otpad',
            'icon'        => '🌾',
            'co2_factor'  => 0.2,
            'avg_weight'  => 1.5,
        ],

        'prehrambeni-otpad' => [
            'label'       => 'Prehrambeni otpad',
            'icon'        => '🍎',
            'co2_factor'  => 0.2,
            'avg_weight'  => 0.3,
        ],

        'guma' => [
            'label'      => 'Guma',
            'icon'       => '🛞',
            'co2_factor' => 0.4,
            'special'    => [
                'auto_weight'  => 10,
                'truck_weight' => 50,
            ],
        ],
    ];
}
