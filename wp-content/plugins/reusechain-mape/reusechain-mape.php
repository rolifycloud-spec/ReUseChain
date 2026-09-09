<?php
/*
Plugin Name: Reusechain Mape
Description: Plugin koji dodaje dodatne funkcionalnosti na registracijsku formu i formu dodavanja oglasa ili donacije.
Version: 1.1
Author: Azra Kadrić
*/

function reusechain_mape_enqueue_scripts() {
    // Define the path to the JSON file
    $json_url = plugin_dir_url(__FILE__) . 'mjesta.json';

    // Register and enqueue the JavaScript file
    wp_register_script('reusechain-mape-js', plugin_dir_url(__FILE__) . 'reusechain-mape.js', array('jquery'), null, true);

    // Pass JSON URL to JavaScript
    wp_localize_script('reusechain-mape-js', 'reusechainMapeVars', array(
        'json_url' => esc_url($json_url)
    ));

    wp_enqueue_script('reusechain-mape-js');
	wp_enqueue_script('turf-js', 'https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'reusechain_mape_enqueue_scripts');


