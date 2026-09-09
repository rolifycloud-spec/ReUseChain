<?php
if (!defined('ABSPATH')) exit;

function rc_is_enabled($key, $default = 1) {
    return (bool) get_option('reusechain_' . $key, $default);
}
