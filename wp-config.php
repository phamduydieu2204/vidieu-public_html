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
define( 'DB_NAME', 'vidieu_db' );

/** Database username */
define( 'DB_USER', 'vidieu' );

/** Database password */
define( 'DB_PASSWORD', 'Vidieu0204@#&6' );

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
define( 'AUTH_KEY',         'Q<E9R60MBokV(.J!%65lxs?Dy7l*N~#z[53|NRnoe5[u]q9I?5U]QI9zHX9L5J_}' );
define( 'SECURE_AUTH_KEY',  'Ug,2Q5vOEYS?{%}eH1FD|UiD{6{)rjf{*zrRhL=:D|/r3PUPKI7!aIQ!K#;q2@Mf' );
define( 'LOGGED_IN_KEY',    ':TL/bH<8.R3.x3_^#~-ijF7-+!,L~U%yO%G{#F$:jcs|[@yS{U+Von(;c|U08mT6' );
define( 'NONCE_KEY',        ' R=v#&8e/%>mG>M?,LVb)5~v+vIE+6vQh[8DCHO&2T#|M#/Y S{r1-C#E,E0ro^}' );
define( 'AUTH_SALT',        ':=WLf_o&_+whw;Cm-7</Em$WPrq2P{2N8Y(#ISQ(=<QLA*D/C))as#fqgLA4A0Jr' );
define( 'SECURE_AUTH_SALT', 'm|q%%z]9DY4[{2dfZ&x3cFO`S1vj-Xkb2isC1/>(OT,`8]`C%WvYiB]^~K0=pwMh' );
define( 'LOGGED_IN_SALT',   'QvfZ{X.%JB//}zvKIQ%LJL2u{u.s#tb-U;]eFn6N1!@Or$,+Ra*Yd|d()``@L1hM' );
define( 'NONCE_SALT',       'g^z$7OuG/<,rR}yRpX@nV&w-k,R#$}Qt*Mww7 *2XWwAAH(4KRfCcjB;n>$h5UKe' );

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
$table_prefix = 'bz_';

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

// Force WP Mail SMTP to use admin@vidieu.vn for all emails
define( 'WPMS_MAIL_FROM', 'admin@vidieu.vn' );
define( 'WPMS_MAIL_FROM_FORCE', true );
define( 'WPMS_MAIL_FROM_NAME', 'Vidieu.vn' );
define( 'WPMS_MAIL_FROM_NAME_FORCE', true );



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
