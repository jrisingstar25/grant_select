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
define( 'DB_NAME', 'dbh8ydrmngjls7' );

/** Database username */
define( 'DB_USER', 'uvgzpvd7etoas' );

/** Database password */
define( 'DB_PASSWORD', '5owadbxnyynr' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'O+Cq.rXwx&33SJ2XVwwQgCYn:Lz)|d{a|,DPNQ7/1Vwo9P0AmCh&t/q8lcBYGAGd' );
define( 'SECURE_AUTH_KEY',   '{Jt j`:gy;bUC9z26V{9N*xu6#R1Wh-#Sr m0z9X<8rOaFk$|26tJP73UNhO~E)G' );
define( 'LOGGED_IN_KEY',     'NbJu`@Jvea(,+Pkqeh`G?P!]t?u?]lJS!Of(XBp@!&H~P l3fo*][o>Q>:xa#Ty`' );
define( 'NONCE_KEY',         'R,.8,~I)4*_sv&{zubQ*#rfBr|*$sn;{J[.FL,]44*A|CM3lBGQSfG:CTw([9sq?' );
define( 'AUTH_SALT',         'gG]1=`|?D!x@.jnGD?ESp[%W<+V8gbA<:`*4{Ive4V~n.%3z3MwEzL4gr&W` G.<' );
define( 'SECURE_AUTH_SALT',  'b(Ak*8@`Hcr8.kb>}}2X4>VaI5tc|OW*-mY#L14H/6YrCT&mI`T2{JUbEXvbT7Xv' );
define( 'LOGGED_IN_SALT',    'I,:oh|ax)`;!3::lC&lykCx6U/Wn}Z--II~=4225O6p4C3w#}.Ltj+c4]>%3P~(a' );
define( 'NONCE_SALT',        '$-7.`O.NrSMp ;v~T#mzq_t]{nU<=$>@(L!)ggIKT_J0A@o!_<|-/c+AS@ia**l]' );
define( 'WP_CACHE_KEY_SALT', '}O|fgr/X5I:yW5_[!|^R5>6E(Oh#Fl2wRhYbRUQ XR7>~]Skf:6>n[1DUsw+cn-l' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'qpl_';

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
ini_set('display_errors','Off');
ini_set('error_reporting', E_ALL );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
