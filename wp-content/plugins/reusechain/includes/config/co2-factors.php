<?php
if (!defined('ABSPATH')) exit;

function rc_get_co2_factors() {
    return [
        'Plastika'             => 2.5,
        'Metalni otpad'        => 9.0,
        'Stakleni otpad'       => 0.7,
        'Papirni otpad'        => 1.8,
        'Tekstilni otpad'      => 5.0,
        'Elektronski otpad'    => 20.0,
        'Drvni otpad'          => 4.0,
        'Poljoprivredni otpad' => 3.0,
        'Prehrambeni otpad'    => 1.5,
        'Guma'                 => 7.5,
    ];
}
