<?php
/**
 * Used to set up and fix common variables and include
 * the  procedural and class library.
 *
 * Allows for some configuration in config.php (see default-constants.php)
 *
 * @package 
 */

/**
 * Stores the location of the  directory of functions, classes, and core content.
 *
 * @since 1.0.0
 */
define( 'INC', 'includes' );

/**
 * Version information for the current  release.
 *
 * These can't be directly globalized in version.php. When updating,
 * we're including version.php from another installation and don't want
 * these values to be overridden if already set.
 *
 * @global string $_version             The  version string.
 * @global int    $_db_version           database version.
 * @global string $tinymce_version        TinyMCE version.
 * @global string $required_php_version   The required PHP version string.
 * @global string $required_mysql_version The required MySQL version string.
 * @global string $_local_package       Locale code of the package.
 */
global $_version, $_db_version, $tinymce_version, $required_php_version, $required_mysql_version, $_local_package;
require ABSPATH . INC . '/version.php';
require ABSPATH . INC . '/load.php';

// Check for the required PHP version and for the MySQL extension or a database drop-in.
_check_php_mysql_versions();

// Include files required for initialization.
require ABSPATH . INC . '/class-paused-extensions-storage.php';
require ABSPATH . INC . '/class-fatal-error-handler.php';
require ABSPATH . INC . '/class-recovery-mode-cookie-service.php';
require ABSPATH . INC . '/class-recovery-mode-key-service.php';
require ABSPATH . INC . '/class-recovery-mode-link-service.php';
require ABSPATH . INC . '/class-recovery-mode-email-service.php';
require ABSPATH . INC . '/class-recovery-mode.php';
require ABSPATH . INC . '/error-protection.php';
require ABSPATH . INC . '/default-constants.php';
require_once ABSPATH . INC . '/plugin.php';

/**
 * If not already configured, `$blog_id` will default to 1 in a single site
 * configuration. In multisite, it will be overridden by default in ms-settings.php.
 *
 * @global int $blog_id
 * @since 2.0.0
 */
global $blog_id;

// Set initial default constants including _MEMORY_LIMIT, _MAX_MEMORY_LIMIT, _DEBUG, SCRIPT_DEBUG, _CONTENT_DIR and _CACHE.
_initial_constants();

// Make sure we register the shutdown handler for fatal errors as soon as possible.
_register_fatal_error_handler();

//  calculates offsets from UTC.
// phpcs:ignore .DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
date_default_timezone_set( 'UTC' );

// Standardize $_SERVER variables across setups.
_fix_server_vars();

// Check if we're in maintenance mode.
_maintenance();

// Start loading timer.
timer_start();

// Check if we're in _DEBUG mode.
_debug_mode();

/**
 * Filters whether to enable loading of the advanced-cache.php drop-in.
 *
 * This filter runs before it can be used by plugins. It is designed for non-web
 * run-times. If false is returned, advanced-cache.php will never be loaded.
 *
 * @since 4.6.0
 *
 * @param bool $enable_advanced_cache Whether to enable loading advanced-cache.php (if present).
 *                                    Default true.
 */
if ( _CACHE && apply_filters( 'enable_loading_advanced_cache_dropin', true ) && file_exists( _CONTENT_DIR . '/advanced-cache.php' ) ) {
	// For an advanced caching plugin to use. Uses a static drop-in because you would only want one.
	include _CONTENT_DIR . '/advanced-cache.php';

	// Re-initialize any hooks added manually by advanced-cache.php.
	if ( $_filter ) {
		$_filter = _Hook::build_preinitialized_hooks( $_filter );
	}
}

// Define _LANG_DIR if not set.
_set_lang_dir();

// Load early  files.
require ABSPATH . INC . '/compat.php';
require ABSPATH . INC . '/class-list-util.php';
require ABSPATH . INC . '/formatting.php';
require ABSPATH . INC . '/meta.php';
require ABSPATH . INC . '/functions.php';
require ABSPATH . INC . '/class-meta-query.php';
require ABSPATH . INC . '/class-matchesmapregex.php';
require ABSPATH . INC . '/class-.php';
require ABSPATH . INC . '/class-error.php';
require ABSPATH . INC . '/pomo/mo.php';

/**
 * @global db $db  database abstraction object.
 * @since 0.71
 */
global $db;
// Include the db class and, if present, a db.php database drop-in.
require__db();

// Set the database table prefix and the format specifiers for database table columns.
$GLOBALS['table_prefix'] = $table_prefix;
_set_db_vars();

// Start the  object cache, or an external object cache if the drop-in is present.
_start_object_cache();

// Attach the default filters.
require ABSPATH . INC . '/default-filters.php';

// Initialize multisite if enabled.
if ( is_multisite() ) {
	require ABSPATH . INC . '/class-site-query.php';
	require ABSPATH . INC . '/class-network-query.php';
	require ABSPATH . INC . '/ms-blogs.php';
	require ABSPATH . INC . '/ms-settings.php';
} elseif ( ! defined( 'MULTISITE' ) ) {
	define( 'MULTISITE', false );
}

register_shutdown_function( 'shutdown_action_hook' );

// Stop most of  from being loaded if we just want the basics.
if ( SHORTINIT ) {
	return false;
}

// Load the L10n library.
require_once ABSPATH . INC . '/l10n.php';
require_once ABSPATH . INC . '/class-locale.php';
require_once ABSPATH . INC . '/class-locale-switcher.php';

// Run the installer if  is not installed.
_not_installed();

// Load most of .
require ABSPATH . INC . '/class-walker.php';
require ABSPATH . INC . '/class-ajax-response.php';
require ABSPATH . INC . '/capabilities.php';
require ABSPATH . INC . '/class-roles.php';
require ABSPATH . INC . '/class-role.php';
require ABSPATH . INC . '/class-user.php';
require ABSPATH . INC . '/class-query.php';
require ABSPATH . INC . '/query.php';
require ABSPATH . INC . '/class-date-query.php';
require ABSPATH . INC . '/theme.php';
require ABSPATH . INC . '/class-theme.php';
require ABSPATH . INC . '/class-theme-json-schema.php';
require ABSPATH . INC . '/class-theme-json.php';
require ABSPATH . INC . '/class-theme-json-resolver.php';
require ABSPATH . INC . '/global-styles-and-settings.php';
require ABSPATH . INC . '/class-block-template.php';
require ABSPATH . INC . '/block-template-utils.php';
require ABSPATH . INC . '/block-template.php';
require ABSPATH . INC . '/theme-templates.php';
require ABSPATH . INC . '/template.php';
require ABSPATH . INC . '/https-detection.php';
require ABSPATH . INC . '/https-migration.php';
require ABSPATH . INC . '/class-user-request.php';
require ABSPATH . INC . '/user.php';
require ABSPATH . INC . '/class-user-query.php';
require ABSPATH . INC . '/class-session-tokens.php';
require ABSPATH . INC . '/class-user-meta-session-tokens.php';
require ABSPATH . INC . '/class-metadata-lazyloader.php';
require ABSPATH . INC . '/general-template.php';
require ABSPATH . INC . '/link-template.php';
require ABSPATH . INC . '/author-template.php';
require ABSPATH . INC . '/robots-template.php';
require ABSPATH . INC . '/post.php';
require ABSPATH . INC . '/class-walker-page.php';
require ABSPATH . INC . '/class-walker-page-dropdown.php';
require ABSPATH . INC . '/class-post-type.php';
require ABSPATH . INC . '/class-post.php';
require ABSPATH . INC . '/post-template.php';
require ABSPATH . INC . '/revision.php';
require ABSPATH . INC . '/post-formats.php';
require ABSPATH . INC . '/post-thumbnail-template.php';
require ABSPATH . INC . '/category.php';
require ABSPATH . INC . '/class-walker-category.php';
require ABSPATH . INC . '/class-walker-category-dropdown.php';
require ABSPATH . INC . '/category-template.php';
require ABSPATH . INC . '/comment.php';
require ABSPATH . INC . '/class-comment.php';
require ABSPATH . INC . '/class-comment-query.php';
require ABSPATH . INC . '/class-walker-comment.php';
require ABSPATH . INC . '/comment-template.php';
require ABSPATH . INC . '/rewrite.php';
require ABSPATH . INC . '/class-rewrite.php';
require ABSPATH . INC . '/feed.php';
require ABSPATH . INC . '/bookmark.php';
require ABSPATH . INC . '/bookmark-template.php';
require ABSPATH . INC . '/kses.php';
require ABSPATH . INC . '/cron.php';
require ABSPATH . INC . '/deprecated.php';
require ABSPATH . INC . '/script-loader.php';
require ABSPATH . INC . '/taxonomy.php';
require ABSPATH . INC . '/class-taxonomy.php';
require ABSPATH . INC . '/class-term.php';
require ABSPATH . INC . '/class-term-query.php';
require ABSPATH . INC . '/class-tax-query.php';
require ABSPATH . INC . '/update.php';
require ABSPATH . INC . '/canonical.php';
require ABSPATH . INC . '/shortcodes.php';
require ABSPATH . INC . '/embed.php';
require ABSPATH . INC . '/class-embed.php';
require ABSPATH . INC . '/class-oembed.php';
require ABSPATH . INC . '/class-oembed-controller.php';
require ABSPATH . INC . '/media.php';
require ABSPATH . INC . '/http.php';
require ABSPATH . INC . '/class-http.php';
require ABSPATH . INC . '/class-http-streams.php';
require ABSPATH . INC . '/class-http-curl.php';
require ABSPATH . INC . '/class-http-proxy.php';
require ABSPATH . INC . '/class-http-cookie.php';
require ABSPATH . INC . '/class-http-encoding.php';
require ABSPATH . INC . '/class-http-response.php';
require ABSPATH . INC . '/class-http-requests-response.php';
require ABSPATH . INC . '/class-http-requests-hooks.php';
require ABSPATH . INC . '/widgets.php';
require ABSPATH . INC . '/class-widget.php';
require ABSPATH . INC . '/class-widget-factory.php';
require ABSPATH . INC . '/nav-menu-template.php';
require ABSPATH . INC . '/nav-menu.php';
require ABSPATH . INC . '/admin-bar.php';
require ABSPATH . INC . '/class-application-passwords.php';
require ABSPATH . INC . '/rest-api.php';
require ABSPATH . INC . '/rest-api/class-rest-server.php';
require ABSPATH . INC . '/rest-api/class-rest-response.php';
require ABSPATH . INC . '/rest-api/class-rest-request.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-posts-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-attachments-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-global-styles-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-post-types-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-post-statuses-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-revisions-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-autosaves-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-taxonomies-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-terms-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-menu-items-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-menus-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-menu-locations-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-users-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-comments-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-search-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-blocks-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-block-types-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-block-renderer-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-settings-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-themes-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-plugins-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-block-directory-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-edit-site-export-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-pattern-directory-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-block-patterns-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-block-pattern-categories-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-application-passwords-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-site-health-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-sidebars-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-widget-types-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-widgets-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-templates-controller.php';
require ABSPATH . INC . '/rest-api/endpoints/class-rest-url-details-controller.php';
require ABSPATH . INC . '/rest-api/fields/class-rest-meta-fields.php';
require ABSPATH . INC . '/rest-api/fields/class-rest-comment-meta-fields.php';
require ABSPATH . INC . '/rest-api/fields/class-rest-post-meta-fields.php';
require ABSPATH . INC . '/rest-api/fields/class-rest-term-meta-fields.php';
require ABSPATH . INC . '/rest-api/fields/class-rest-user-meta-fields.php';
require ABSPATH . INC . '/rest-api/search/class-rest-search-handler.php';
require ABSPATH . INC . '/rest-api/search/class-rest-post-search-handler.php';
require ABSPATH . INC . '/rest-api/search/class-rest-term-search-handler.php';
require ABSPATH . INC . '/rest-api/search/class-rest-post-format-search-handler.php';
require ABSPATH . INC . '/sitemaps.php';
require ABSPATH . INC . '/sitemaps/class-sitemaps.php';
require ABSPATH . INC . '/sitemaps/class-sitemaps-index.php';
require ABSPATH . INC . '/sitemaps/class-sitemaps-provider.php';
require ABSPATH . INC . '/sitemaps/class-sitemaps-registry.php';
require ABSPATH . INC . '/sitemaps/class-sitemaps-renderer.php';
require ABSPATH . INC . '/sitemaps/class-sitemaps-stylesheet.php';
require ABSPATH . INC . '/sitemaps/providers/class-sitemaps-posts.php';
require ABSPATH . INC . '/sitemaps/providers/class-sitemaps-taxonomies.php';
require ABSPATH . INC . '/sitemaps/providers/class-sitemaps-users.php';
require ABSPATH . INC . '/class-block-editor-context.php';
require ABSPATH . INC . '/class-block-type.php';
require ABSPATH . INC . '/class-block-pattern-categories-registry.php';
require ABSPATH . INC . '/class-block-patterns-registry.php';
require ABSPATH . INC . '/class-block-styles-registry.php';
require ABSPATH . INC . '/class-block-type-registry.php';
require ABSPATH . INC . '/class-block.php';
require ABSPATH . INC . '/class-block-list.php';
require ABSPATH . INC . '/class-block-parser.php';
require ABSPATH . INC . '/blocks.php';
require ABSPATH . INC . '/blocks/index.php';
require ABSPATH . INC . '/block-editor.php';
require ABSPATH . INC . '/block-patterns.php';
require ABSPATH . INC . '/class-block-supports.php';
require ABSPATH . INC . '/block-supports/utils.php';
require ABSPATH . INC . '/block-supports/align.php';
require ABSPATH . INC . '/block-supports/border.php';
require ABSPATH . INC . '/block-supports/colors.php';
require ABSPATH . INC . '/block-supports/custom-classname.php';
require ABSPATH . INC . '/block-supports/dimensions.php';
require ABSPATH . INC . '/block-supports/duotone.php';
require ABSPATH . INC . '/block-supports/elements.php';
require ABSPATH . INC . '/block-supports/generated-classname.php';
require ABSPATH . INC . '/block-supports/layout.php';
require ABSPATH . INC . '/block-supports/spacing.php';
require ABSPATH . INC . '/block-supports/typography.php';

$GLOBALS['_embed'] = new _Embed();

// Load multisite-specific files.
if ( is_multisite() ) {
	require ABSPATH . INC . '/ms-functions.php';
	require ABSPATH . INC . '/ms-default-filters.php';
	require ABSPATH . INC . '/ms-deprecated.php';
}

// Define constants that rely on the API to obtain the default value.
// Define must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
_plugin_directory_constants();

$GLOBALS['_plugin_paths'] = array();

// Load must-use plugins.
foreach ( _get_mu_plugins() as $mu_plugin ) {
	$__plugin_file = $mu_plugin;
	include_once $mu_plugin;
	$mu_plugin = $__plugin_file; // Avoid stomping of the $mu_plugin variable in a plugin.

	/**
	 * Fires once a single must-use plugin has loaded.
	 *
	 * @since 5.1.0
	 *
	 * @param string $mu_plugin Full path to the plugin's main file.
	 */
	do_action( 'mu_plugin_loaded', $mu_plugin );
}
unset( $mu_plugin, $__plugin_file );

// Load network activated plugins.
if ( is_multisite() ) {
	foreach ( _get_active_network_plugins() as $network_plugin ) {
		_register_plugin_realpath( $network_plugin );

		$__plugin_file = $network_plugin;
		include_once $network_plugin;
		$network_plugin = $__plugin_file; // Avoid stomping of the $network_plugin variable in a plugin.

		/**
		 * Fires once a single network-activated plugin has loaded.
		 *
		 * @since 5.1.0
		 *
		 * @param string $network_plugin Full path to the plugin's main file.
		 */
		do_action( 'network_plugin_loaded', $network_plugin );
	}
	unset( $network_plugin, $__plugin_file );
}

/**
 * Fires once all must-use and network-activated plugins have loaded.
 *
 * @since 2.8.0
 */
do_action( 'muplugins_loaded' );

if ( is_multisite() ) {
	ms_cookie_constants();
}

// Define constants after multisite is loaded.
_cookie_constants();

// Define and enforce our SSL constants.
_ssl_constants();

// Create common globals.
require ABSPATH . INC . '/vars.php';

// Make taxonomies and posts available to plugins and themes.
// @plugin authors: warning: these get registered again on the init hook.
create_initial_taxonomies();
create_initial_post_types();

_start_scraping_edited_file_errors();

// Register the default theme directory root.
register_theme_directory( get_theme_root() );

if ( ! is_multisite() ) {
	// Handle users requesting a recovery mode link and initiating recovery mode.
	_recovery_mode()->initialize();
}

// Load active plugins.
foreach ( _get_active_and_valid_plugins() as $plugin ) {
	_register_plugin_realpath( $plugin );

	$__plugin_file = $plugin;
	include_once $plugin;
	$plugin = $__plugin_file; // Avoid stomping of the $plugin variable in a plugin.

	/**
	 * Fires once a single activated plugin has loaded.
	 *
	 * @since 5.1.0
	 *
	 * @param string $plugin Full path to the plugin's main file.
	 */
	do_action( 'plugin_loaded', $plugin );
}
unset( $plugin, $__plugin_file );

// Load pluggable functions.
require ABSPATH . INC . '/pluggable.php';
require ABSPATH . INC . '/pluggable-deprecated.php';

// Set internal encoding.
_set_internal_encoding();

// Run _cache_postload() if object cache is enabled and the function exists.
if ( _CACHE && function_exists( '_cache_postload' ) ) {
	_cache_postload();
}

/**
 * Fires once activated plugins have loaded.
 *
 * Pluggable functions are also available at this point in the loading order.
 *
 * @since 1.5.0
 */
do_action( 'plugins_loaded' );

// Define constants which affect functionality if not already defined.
_functionality_constants();

// Add magic quotes and set up $_REQUEST ( $_GET + $_POST ).
_magic_quotes();

/**
 * Fires when comment cookies are sanitized.
 *
 * @since 2.0.11
 */
do_action( 'sanitize_comment_cookies' );

/**
 *  Query object
 *
 * @global _Query $_the_query  Query object.
 * @since 2.0.0
 */
$GLOBALS['_the_query'] = new _Query();

/**
 * Holds the reference to @see $_the_query
 * Use this global for  queries
 *
 * @global _Query $_query  Query object.
 * @since 1.5.0
 */
$GLOBALS['_query'] = $GLOBALS['_the_query'];

/**
 * Holds the  Rewrite object for creating pretty URLs
 *
 * @global _Rewrite $_rewrite  rewrite component.
 * @since 1.5.0
 */
$GLOBALS['_rewrite'] = new _Rewrite();

/**
 *  Object
 *
 * @global  $ Current  environment instance.
 * @since 2.0.0
 */
$GLOBALS[''] = new ();

/**
 *  Widget Factory Object
 *
 * @global _Widget_Factory $_widget_factory
 * @since 2.8.0
 */
$GLOBALS['_widget_factory'] = new _Widget_Factory();

/**
 *  User Roles
 *
 * @global _Roles $_roles  role management object.
 * @since 2.0.0
 */
$GLOBALS['_roles'] = new _Roles();

/**
 * Fires before the theme is loaded.
 *
 * @since 2.6.0
 */
do_action( 'setup_theme' );

// Define the template related constants.
_templating_constants();

// Load the default text localization domain.
load_default_textdomain();

$locale      = get_locale();
$locale_file = _LANG_DIR . "/$locale.php";
if ( ( 0 === validate_file( $locale ) ) && is_readable( $locale_file ) ) {
	require $locale_file;
}
unset( $locale_file );

/**
 *  Locale object for loading locale domain date and various strings.
 *
 * @global _Locale $_locale  date and time locale object.
 * @since 2.1.0
 */
$GLOBALS['_locale'] = new _Locale();

/**
 *  Locale Switcher object for switching locales.
 *
 * @since 4.7.0
 *
 * @global _Locale_Switcher $_locale_switcher  locale switcher object.
 */
$GLOBALS['_locale_switcher'] = new _Locale_Switcher();
$GLOBALS['_locale_switcher']->init();

// Load the functions for the active theme, for both parent and child theme if applicable.
foreach ( _get_active_and_valid_themes() as $theme ) {
	if ( file_exists( $theme . '/functions.php' ) ) {
		include $theme . '/functions.php';
	}
}
unset( $theme );

/**
 * Fires after the theme is loaded.
 *
 * @since 3.0.0
 */
do_action( 'after_setup_theme' );

// Create an instance of _Site_Health so that Cron events may fire.
if ( ! class_exists( '_Site_Health' ) ) {
	require_once ABSPATH . 'admin/includes/class-site-health.php';
}
_Site_Health::get_instance();

// Set up current user.
$GLOBALS['']->init();

/**
 * Fires after  has finished loading but before any headers are sent.
 *
 * Most of  is loaded at this stage, and the user is authenticated.  continues
 * to load on the {@see 'init'} hook that follows (e.g. widgets), and many plugins instantiate
 * themselves on it for all sorts of reasons (e.g. they need a user, a taxonomy, etc.).
 *
 * If you wish to plug an action once  is loaded, use the {@see '_loaded'} hook below.
 *
 * @since 1.5.0
 */
do_action( 'init' );

// Check site status.
if ( is_multisite() ) {
	$file = ms_site_check();
	if ( true !== $file ) {
		require $file;
		die();
	}
	unset( $file );
}

/**
 * This hook is fired once , all plugins, and the theme are fully loaded and instantiated.
 *
 * Ajax requests should use admin/admin-ajax.php. admin-ajax.php can handle requests for
 * users not logged in.
 *
 * @link https://codex..org/AJAX_in_Plugins
 *
 * @since 3.0.0
 */
do_action( '_loaded' );
