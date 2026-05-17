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
define( 'DB_NAME', 'civic' );

/** Database username */
define( 'DB_USER', 'civic' );

/** Database password */
define( 'DB_PASSWORD', 'bP9]5Fc9:hn%1=u@' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',         '{Cis5JnC2Op@+S5)>9LLd2Sb./&-)9WwGJ-N4 5m-Mt(+o)pk]v0rN7QBU0|[~yq' );
define( 'SECURE_AUTH_KEY',  '!7((histF(LW$dk5bF$5!kUZ(Oj>-czsD}#BJpaL{nNTT1]TQ6802J4q:39|W+aR' );
define( 'LOGGED_IN_KEY',    '53e,rS!7qYnQMV5%|D$6!Wbbf<th!;z{~zNuF&0h0hp>Q:Eic{K[J2~a5!AXN1T|' );
define( 'NONCE_KEY',        ',w&EW<z;|sL-eCNqhsenE(_TtGgf=ON==A7AC`$8S9xEEVDM_jp9=E>-},1)g{m.' );
define( 'AUTH_SALT',        'NHWy{%PtbM^wScox3UlD:?V>p`Z>`M!EUZj)9~|)-Xvg[CNpuO)Zw=jI:IPeHz/#' );
define( 'SECURE_AUTH_SALT', '-QIn:}<cQ|+Arz*y>R/6qWOm`xjW!W6TQ1M9(m:09Hc%$48c<x,`=Jou.O4(-!6A' );
define( 'LOGGED_IN_SALT',   '8/hh0Ho4vwoM{x[3,#x5U0rGDSB2*Wc-NT|9|c/ERp:~]$5qD>?ajk95pny:a0nb' );
define( 'NONCE_SALT',       'Cf/$=}zuNxAxA3oorvev2]#vNm%z/I;S!.ayXdPa(?>5Ed*<&ZIB%$.Rg*_v9,]o' );

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
