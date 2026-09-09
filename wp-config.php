<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wp_gjufm');
/** Database username */
define('DB_USER', 'wp_issvq');
/** Database password */
define('DB_PASSWORD', 'nLHu^2_i00ji5&dQ');
/** Database hostname */
define('DB_HOST', 'localhost:3306');
/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');
/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'd(0g4H95;hOR4lms50!J37wk6-#Yl0b-3-wgJOtg_95q5DV|6#w59N+/3+|S1afp');
define('SECURE_AUTH_KEY', '3-+fzNZ6J0D1Pc_f2;x9BKt8d@84yE474nRd&_Q9wj!N9+/Ad[-P)_]L7g;r5H9T');
define('LOGGED_IN_KEY', '~%D6)%m/hgCoObM#K3B5~9PT1x6wNq~_TdI(46ynRZX3T4-8e2#_y~493Z)]]6]p');
define('NONCE_KEY', '@o&W98+g6yLdoDi&VIGW;/FJC&9]B9z#6m78UL0_3@ZOi_!ndfTA42rpH58G|:3C');
define('AUTH_SALT', '(Lr3%Z&dHn(-TfBc9j:03[loG*2-]lX0Mn47KN8mj|j1eL66~chO-+cs4z6p5g|n');
define('SECURE_AUTH_SALT', '7W#4R]6GB@Nil(4cax9e+ZmrQJn352K+mz0M~1)hPF@JUmlhJ9+Lt8Rq##7G5wO!');
define('LOGGED_IN_SALT', '56#7j)6|a2f9-#)lf|4|I5prq2K3&c969PG59FWh|8TQ+NNt~TwIp7Bn2pt9d3:M');
define('NONCE_SALT', '9@UcuvO%6l5BOQz:kI0!X5Z3|[M1@A5m1-2+jb-n:!QP@]t3|l&9p*6Xi5ov/d7m');
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'DZxAzY_';
/* Add any custom values between this line and the "stop editing" line. */
define('WP_ALLOW_MULTISITE', true);
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
@ini_set('log_errors', 1);
@ini_set('display_errors', 0);
define ('WP_MEMORY_LIMIT', '1024M'); 
define( 'WP_CACHE_KEY_SALT', '7f135e05f9f8800a62aaf217ba3c0237' );
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';