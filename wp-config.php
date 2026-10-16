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
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

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
define( 'AUTH_KEY',         '*?ywv6xpGF@Z:)y:/xFDr]`|fJ|<@_[N&=jY{|GT}O$0DhY[pSrH:9_+_*;`U^c>' );
define( 'SECURE_AUTH_KEY',  'xi*EP&1D<Zl{Gn.Obf_-hLu44bQ(uXH%c-e)ti,:T<Krg1ZZHA2s`S^v)bCj gjD' );
define( 'LOGGED_IN_KEY',    'ii3<O_~r:o:F;)7^/6Op~$*:M:{TIr$=T(DIT5Oieqv9Y=wCiJ#{MeOG!0t>;8@b' );
define( 'NONCE_KEY',        'g6y31Uj:z-qVkzOE%,5/oL;.K&= I/jyH_LmGak+ {lY>GjP*hOU|u)li1a$>6eW' );
define( 'AUTH_SALT',        'XHFV5Du_QSYQsC^&)>(t] LYF5?+vl[<yN(CJ]uLuTy^z9 NPFfRiOaY$yAf*cV#' );
define( 'SECURE_AUTH_SALT', 'Roei4e=n|+TjAH+RI<G)i7YeXNeISE4JKEgDLO*hkYTAOek:!nksAm<W{XGdXzSZ' );
define( 'LOGGED_IN_SALT',   '!To2FH6a%FtUq.>OY7*F&E/}wpHD5C,{9PB.g*B%BeqS0D1I2nC~o;@YBbCg{G_D' );
define( 'NONCE_SALT',       '8q!UcId7)WI>! ~9&AHbT)X5yATsB4}EEd+*rMYY) x$SpR> y6nm-qo.rDw3Ty!' );

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
