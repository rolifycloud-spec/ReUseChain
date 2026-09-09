<?php

add_shortcode('rc_co2_gauge_v3', function() {

    return '
    <div class="rc-gauge-v3">
        <div class="rc-bar">
            <div class="rc-fill"></div>
        </div>
        <div class="rc-value">
            <span class="rc-current">0</span> /
            <span class="rc-max">0</span>
        </div>
    </div>
    ';
});