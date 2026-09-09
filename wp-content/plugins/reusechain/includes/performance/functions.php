<?php
if (!defined('ABSPATH')) exit;

// === DEFER JS ===
function reusechain_safe_defer( $tag, $handle, $src ) { ... }

// === CSS OPTIMIZE ===
function reusechain_optimize_css( $tag, $handle, $href ) { ... }

// === STRIP HOME SCRIPTS ===
function reusechain_strip_heavy_scripts_on_home( $html ) { ... }

// === DELAY FB ===
function reusechain_delay_fb_scripts($tag, $handle, $src){ ... }

// === DASHICONS ===
function reusechain_disable_dashicons_for_guests() { ... }

// === HERO PRELOAD ===
function reusechain_preload_hero_bg_image() { ... }

// === JQUERY FOOTER ===
function reusechain_move_jquery_to_footer() { ... }

// === JET REVIEWS HARD BLOCK ===
function rc_block_jet_reviews_scripts($tag, $handle, $src) { ... }
function rc_block_jet_reviews_css($html, $handle, $href, $media) { ... }
