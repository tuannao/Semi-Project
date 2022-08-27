<?php
/**
 * The base configuration for 
 *
 * The config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://.org/support/article/editing-config-php/
 *
 * @package 
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for  */
define( 'DB_NAME', 'db' );

/** Database username */
define( 'DB_USER', 'dbuser' );

/** Database password */
define( 'DB_PASSWORD', 'dbuser' );

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
 * the {@link https://api..org/secret-key/1.1/salt/ .org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '!|t<(Z*W0paY-NMl0;SiRbB<Zo~[tq3&,c}uYI)56m8Z^pW>f.BR6IuQ~WNQw`p3' );
define( 'SECURE_AUTH_KEY',  'B(v.H+3aO1Z_zH*53QDL?<Aet]d3P4hFuy tymtlljs0mIE?t.)rTC:dW(PqZ;*N' );
define( 'LOGGED_IN_KEY',    '#i6_DE{HC.2~sMjVL/v3$pv?O&jDAMYbK92Hd&QT3;T)!ocFPNZos_^vS3Ghn(f<' );
define( 'NONCE_KEY',        'O;~V1dZA{N>;=1wz[;^T!3=(;ELpzSg~j]1;>9I6f,Ah$!vcWAeLsc`;=Ld}|]H*' );
define( 'AUTH_SALT',        'TRqarE-8v$Al#C.1A$pxj*@|zPJ[C2_Czt[F[k4?lSzY*!En0xDWOk*:NY%6CeZ6' );
define( 'SECURE_AUTH_SALT', 'Upmw{1Jj1&Z;H;/GHZCFqHSCu{d=XaBJ?puXy|-gdJ}hiYU [tr;GdC]pmHLE^@E' );
define( 'LOGGED_IN_SALT',   '|nwjn)IDnfAk!:T}p#HrARc{p^lW,-a={9sGLyAx}ns2z|>yhvL.V+ r7Kq>Uc+i' );
define( 'NONCE_SALT',       'B 0}Wj-Gx,~EY!}bnFQ*7W$,EDhT}2f$ZO_8j~wUPs#lzbO/{Oe`lE,d|Hetml[G' );

/**#@-*/

/**
 *  database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '_';

/**
 * For developers:  debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use _DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://.org/support/article/debugging-in-/
 */
define( '_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the  directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up  vars and included files. */
require_once ABSPATH . 'settings.php';
