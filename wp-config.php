<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbs13602143' );

/** Database username */
define( 'DB_USER', 'dbu2357142' );

/** Database password */
define( 'DB_PASSWORD', 'LilaHerzen0102' );

/** Database hostname */
define( 'DB_HOST', 'db5016844180.hosting-data.io' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'v>R/5s!j5KLVzy]D7?,YO~@P63Pqs{h3vgs&Kd6p`=~{^a)T}wE`:s[#l6j]W-1D' );
define( 'SECURE_AUTH_KEY',  '/c>DlG~1nK+1A{F2Y4W}^dpB:`-ooY9h7*`a+}OU[`2:ta- *VX0Ul6(6V`|cRqw' );
define( 'LOGGED_IN_KEY',    'dphU7hv~Vg^:[8EFN_{Tc46DI!JDh({i-fxnhV0perNTOx]?CVPcnBXv?%Tf1D3*' );
define( 'NONCE_KEY',        'di:|D sn^bQx^6*8ydf-#q>792_dlFjIs4Ri14O3X>C|m_6:/3?q1OM@^ehZ%kk2' );
define( 'AUTH_SALT',        'U(?P{&3me=^W$_|z.|zUr6D-Qwr{$Du<P@-/Th2.oB-%%av*(2M!JBt2Y5#>HpBu' );
define( 'SECURE_AUTH_SALT', '9*c.@D{n1a59n^rS=RDr<XTo5^`A_ Tm}GHO$8`XzTO<i+>7R=sX[u}lB3]*|YqF' );
define( 'LOGGED_IN_SALT',   '(?3(^4YroC9cwbZI~?qBwf~<kqxV?MDTLeepb9pezE{}M9vRD3yYk!%M7?BOG-1M' );
define( 'NONCE_SALT',       'y{Sec8Vlu?PYN.=3Ht9&Ti@;ph3o53Fi5#;xY:j/72_EQS|RR |bFPA`^)to,P;.' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
