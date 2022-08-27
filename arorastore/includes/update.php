<?php
/**
 * A simple set of functions to check our version 1.0 update service.
 *
 * @package 
 * @since 2.3.0
 */

/**
 * Check  version against the newest version.
 *
 * The  version, PHP version, and locale is sent.
 *
 * Checks against the  server at api..org. Will only check
 * if  isn't installing.
 *
 * @since 2.3.0
 *
 * @global string $_version       Used to check against the newest  version.
 * @global db   $db              database abstraction object.
 * @global string $_local_package Locale code of the package.
 *
 * @param array $extra_stats Extra statistics to report to the .org API.
 * @param bool  $force_check Whether to bypass the transient cache and force a fresh update check. Defaults to false, true if $extra_stats is set.
 */
function _version_check( $extra_stats = array(), $force_check = false ) {
	global $db, $_local_package;

	if ( _installing() ) {
		return;
	}

	// Include an unmodified $_version.
	require ABSPATH . INC . '/version.php';
	$php_version = phpversion();

	$current      = get_site_transient( 'update_core' );
	$translations = _get_installed_translations( 'core' );

	// Invalidate the transient when $_version changes.
	if ( is_object( $current ) && $_version !== $current->version_checked ) {
		$current = false;
	}

	if ( ! is_object( $current ) ) {
		$current                  = new stdClass;
		$current->updates         = array();
		$current->version_checked = $_version;
	}

	if ( ! empty( $extra_stats ) ) {
		$force_check = true;
	}

	// Wait 1 minute between multiple version check requests.
	$timeout          = MINUTE_IN_SECONDS;
	$time_not_changed = isset( $current->last_checked ) && $timeout > ( time() - $current->last_checked );

	if ( ! $force_check && $time_not_changed ) {
		return;
	}

	/**
	 * Filters the locale requested for  core translations.
	 *
	 * @since 2.8.0
	 *
	 * @param string $locale Current locale.
	 */
	$locale = apply_filters( 'core_version_check_locale', get_locale() );

	// Update last_checked for current to prevent multiple blocking requests if request hangs.
	$current->last_checked = time();
	set_site_transient( 'update_core', $current );

	if ( method_exists( $db, 'db_version' ) ) {
		$mysql_version = preg_replace( '/[^0-9.].*/', '', $db->db_version() );
	} else {
		$mysql_version = 'N/A';
	}

	if ( is_multisite() ) {
		$num_blogs         = get_blog_count();
		$_install        = network_site_url();
		$multisite_enabled = 1;
	} else {
		$multisite_enabled = 0;
		$num_blogs         = 1;
		$_install        = home_url( '/' );
	}

	$query = array(
		'version'            => $_version,
		'php'                => $php_version,
		'locale'             => $locale,
		'mysql'              => $mysql_version,
		'local_package'      => isset( $_local_package ) ? $_local_package : '',
		'blogs'              => $num_blogs,
		'users'              => get_user_count(),
		'multisite_enabled'  => $multisite_enabled,
		'initial_db_version' => get_site_option( 'initial_db_version' ),
	);

	/**
	 * Filters the query arguments sent as part of the core version check.
	 *
	 * WARNING: Changing this data may result in your site not receiving security updates.
	 * Please exercise extreme caution.
	 *
	 * @since 4.9.0
	 *
	 * @param array $query {
	 *     Version check query arguments.
	 *
	 *     @type string $version             version number.
	 *     @type string $php                PHP version number.
	 *     @type string $locale             The locale to retrieve updates for.
	 *     @type string $mysql              MySQL version number.
	 *     @type string $local_package      The value of the $_local_package global, when set.
	 *     @type int    $blogs              Number of sites on this  installation.
	 *     @type int    $users              Number of users on this  installation.
	 *     @type int    $multisite_enabled  Whether this  installation uses Multisite.
	 *     @type int    $initial_db_version Database version of  at time of installation.
	 * }
	 */
	$query = apply_filters( 'core_version_check_query_args', $query );

	$post_body = array(
		'translations' => _json_encode( $translations ),
	);

	if ( is_array( $extra_stats ) ) {
		$post_body = array_merge( $post_body, $extra_stats );
	}

	// Allow for _AUTO_UPDATE_CORE to specify beta/RC/development releases.
	if ( defined( '_AUTO_UPDATE_CORE' )
		&& in_array( _AUTO_UPDATE_CORE, array( 'beta', 'rc', 'development', 'branch-development' ), true )
	) {
		$query['channel'] = _AUTO_UPDATE_CORE;
	}

	$url      = 'http://api..org/core/version-check/1.7/?' . http_build_query( $query, '', '&' );
	$http_url = $url;
	$ssl      = _http_supports( array( 'ssl' ) );

	if ( $ssl ) {
		$url = set_url_scheme( $url, 'https' );
	}

	$doing_cron = _doing_cron();

	$options = array(
		'timeout'    => $doing_cron ? 30 : 3,
		'user-agent' => '/' . $_version . '; ' . home_url( '/' ),
		'headers'    => array(
			'_install' => $_install,
			'_blog'    => home_url( '/' ),
		),
		'body'       => $post_body,
	);

	$response = _remote_post( $url, $options );

	if ( $ssl && is__error( $response ) ) {
		trigger_error(
			sprintf(
				/* translators: %s: Support forums URL. */
				__( 'An unexpected error occurred. Something may be wrong with .org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
				__( 'https://.org/support/forums/' )
			) . ' ' . __( '( could not establish a secure connection to .org. Please contact your server administrator.)' ),
			headers_sent() || _DEBUG ? E_USER_WARNING : E_USER_NOTICE
		);
		$response = _remote_post( $http_url, $options );
	}

	if ( is__error( $response ) || 200 !== _remote_retrieve_response_code( $response ) ) {
		return;
	}

	$body = trim( _remote_retrieve_body( $response ) );
	$body = json_decode( $body, true );

	if ( ! is_array( $body ) || ! isset( $body['offers'] ) ) {
		return;
	}

	$offers = $body['offers'];

	foreach ( $offers as &$offer ) {
		foreach ( $offer as $offer_key => $value ) {
			if ( 'packages' === $offer_key ) {
				$offer['packages'] = (object) array_intersect_key(
					array_map( 'esc_url', $offer['packages'] ),
					array_fill_keys( array( 'full', 'no_content', 'new_bundled', 'partial', 'rollback' ), '' )
				);
			} elseif ( 'download' === $offer_key ) {
				$offer['download'] = esc_url( $value );
			} else {
				$offer[ $offer_key ] = esc_html( $value );
			}
		}
		$offer = (object) array_intersect_key(
			$offer,
			array_fill_keys(
				array(
					'response',
					'download',
					'locale',
					'packages',
					'current',
					'version',
					'php_version',
					'mysql_version',
					'new_bundled',
					'partial_version',
					'notify_email',
					'support_email',
					'new_files',
				),
				''
			)
		);
	}

	$updates                  = new stdClass();
	$updates->updates         = $offers;
	$updates->last_checked    = time();
	$updates->version_checked = $_version;

	if ( isset( $body['translations'] ) ) {
		$updates->translations = $body['translations'];
	}

	set_site_transient( 'update_core', $updates );

	if ( ! empty( $body['ttl'] ) ) {
		$ttl = (int) $body['ttl'];

		if ( $ttl && ( time() + $ttl < _next_scheduled( '_version_check' ) ) ) {
			// Queue an event to re-run the update check in $ttl seconds.
			_schedule_single_event( time() + $ttl, '_version_check' );
		}
	}

	// Trigger background updates if running non-interactively, and we weren't called from the update handler.
	if ( $doing_cron && ! doing_action( '_maybe_auto_update' ) ) {
		/**
		 * Fires during _cron, starting the auto-update process.
		 *
		 * @since 3.9.0
		 */
		do_action( '_maybe_auto_update' );
	}
}

/**
 * Checks for available updates to plugins based on the latest versions hosted on .org.
 *
 * Despite its name this function does not actually perform any updates, it only checks for available updates.
 *
 * A list of all plugins installed is sent to , along with the site locale.
 *
 * Checks against the  server at api..org. Will only check
 * if  isn't installing.
 *
 * @since 2.3.0
 *
 * @global string $_version The  version string.
 *
 * @param array $extra_stats Extra statistics to report to the .org API.
 */
function _update_plugins( $extra_stats = array() ) {
	if ( _installing() ) {
		return;
	}

	// Include an unmodified $_version.
	require ABSPATH . INC . '/version.php';

	// If running blog-side, bail unless we've not checked in the last 12 hours.
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'admin/includes/plugin.php';
	}

	$plugins      = get_plugins();
	$translations = _get_installed_translations( 'plugins' );

	$active  = get_option( 'active_plugins', array() );
	$current = get_site_transient( 'update_plugins' );

	if ( ! is_object( $current ) ) {
		$current = new stdClass;
	}

	$updates               = new stdClass;
	$updates->last_checked = time();
	$updates->response     = array();
	$updates->translations = array();
	$updates->no_update    = array();

	$doing_cron = _doing_cron();

	// Check for update on a different schedule, depending on the page.
	switch ( current_filter() ) {
		case 'upgrader_process_complete':
			$timeout = 0;
			break;
		case 'load-update-core.php':
			$timeout = MINUTE_IN_SECONDS;
			break;
		case 'load-plugins.php':
		case 'load-update.php':
			$timeout = HOUR_IN_SECONDS;
			break;
		default:
			if ( $doing_cron ) {
				$timeout = 2 * HOUR_IN_SECONDS;
			} else {
				$timeout = 12 * HOUR_IN_SECONDS;
			}
	}

	$time_not_changed = isset( $current->last_checked ) && $timeout > ( time() - $current->last_checked );

	if ( $time_not_changed && ! $extra_stats ) {
		$plugin_changed = false;

		foreach ( $plugins as $file => $p ) {
			$updates->checked[ $file ] = $p['Version'];

			if ( ! isset( $current->checked[ $file ] ) || (string) $current->checked[ $file ] !== (string) $p['Version'] ) {
				$plugin_changed = true;
			}
		}

		if ( isset( $current->response ) && is_array( $current->response ) ) {
			foreach ( $current->response as $plugin_file => $update_details ) {
				if ( ! isset( $plugins[ $plugin_file ] ) ) {
					$plugin_changed = true;
					break;
				}
			}
		}

		// Bail if we've checked recently and if nothing has changed.
		if ( ! $plugin_changed ) {
			return;
		}
	}

	// Update last_checked for current to prevent multiple blocking requests if request hangs.
	$current->last_checked = time();
	set_site_transient( 'update_plugins', $current );

	$to_send = compact( 'plugins', 'active' );

	$locales = array_values( get_available_languages() );

	/**
	 * Filters the locales requested for plugin translations.
	 *
	 * @since 3.7.0
	 * @since 4.5.0 The default value of the `$locales` parameter changed to include all locales.
	 *
	 * @param array $locales Plugin locales. Default is all available locales of the site.
	 */
	$locales = apply_filters( 'plugins_update_check_locales', $locales );
	$locales = array_unique( $locales );

	if ( $doing_cron ) {
		$timeout = 30;
	} else {
		// Three seconds, plus one extra second for every 10 plugins.
		$timeout = 3 + (int) ( count( $plugins ) / 10 );
	}

	$options = array(
		'timeout'    => $timeout,
		'body'       => array(
			'plugins'      => _json_encode( $to_send ),
			'translations' => _json_encode( $translations ),
			'locale'       => _json_encode( $locales ),
			'all'          => _json_encode( true ),
		),
		'user-agent' => '/' . $_version . '; ' . home_url( '/' ),
	);

	if ( $extra_stats ) {
		$options['body']['update_stats'] = _json_encode( $extra_stats );
	}

	$url      = 'http://api..org/plugins/update-check/1.1/';
	$http_url = $url;
	$ssl      = _http_supports( array( 'ssl' ) );

	if ( $ssl ) {
		$url = set_url_scheme( $url, 'https' );
	}

	$raw_response = _remote_post( $url, $options );

	if ( $ssl && is__error( $raw_response ) ) {
		trigger_error(
			sprintf(
				/* translators: %s: Support forums URL. */
				__( 'An unexpected error occurred. Something may be wrong with .org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
				__( 'https://.org/support/forums/' )
			) . ' ' . __( '( could not establish a secure connection to .org. Please contact your server administrator.)' ),
			headers_sent() || _DEBUG ? E_USER_WARNING : E_USER_NOTICE
		);
		$raw_response = _remote_post( $http_url, $options );
	}

	if ( is__error( $raw_response ) || 200 !== _remote_retrieve_response_code( $raw_response ) ) {
		return;
	}

	$response = json_decode( _remote_retrieve_body( $raw_response ), true );

	if ( $response && is_array( $response ) ) {
		$updates->response     = $response['plugins'];
		$updates->translations = $response['translations'];
		$updates->no_update    = $response['no_update'];
	}

	// Support updates for any plugins using the `Update URI` header field.
	foreach ( $plugins as $plugin_file => $plugin_data ) {
		if ( ! $plugin_data['UpdateURI'] || isset( $updates->response[ $plugin_file ] ) ) {
			continue;
		}

		$hostname = _parse_url( esc_url_raw( $plugin_data['UpdateURI'] ), PHP_URL_HOST );

		/**
		 * Filters the update response for a given plugin hostname.
		 *
		 * The dynamic portion of the hook name, `$hostname`, refers to the hostname
		 * of the URI specified in the `Update URI` header field.
		 *
		 * @since 5.8.0
		 *
		 * @param array|false $update {
		 *     The plugin update data with the latest details. Default false.
		 *
		 *     @type string $id           Optional. ID of the plugin for update purposes, should be a URI
		 *                                specified in the `Update URI` header field.
		 *     @type string $slug         Slug of the plugin.
		 *     @type string $version      The version of the plugin.
		 *     @type string $url          The URL for details of the plugin.
		 *     @type string $package      Optional. The update ZIP for the plugin.
		 *     @type string $tested       Optional. The version of  the plugin is tested against.
		 *     @type string $requires_php Optional. The version of PHP which the plugin requires.
		 *     @type bool   $autoupdate   Optional. Whether the plugin should automatically update.
		 *     @type array  $icons        Optional. Array of plugin icons.
		 *     @type array  $banners      Optional. Array of plugin banners.
		 *     @type array  $banners_rtl  Optional. Array of plugin RTL banners.
		 *     @type array  $translations {
		 *         Optional. List of translation updates for the plugin.
		 *
		 *         @type string $language   The language the translation update is for.
		 *         @type string $version    The version of the plugin this translation is for.
		 *                                  This is not the version of the language file.
		 *         @type string $updated    The update timestamp of the translation file.
		 *                                  Should be a date in the `YYYY-MM-DD HH:MM:SS` format.
		 *         @type string $package    The ZIP location containing the translation update.
		 *         @type string $autoupdate Whether the translation should be automatically installed.
		 *     }
		 * }
		 * @param array       $plugin_data      Plugin headers.
		 * @param string      $plugin_file      Plugin filename.
		 * @param array       $locales          Installed locales to look translations for.
		 */
		$update = apply_filters( "update_plugins_{$hostname}", false, $plugin_data, $plugin_file, $locales );

		if ( ! $update ) {
			continue;
		}

		$update = (object) $update;

		// Is it valid? We require at least a version.
		if ( ! isset( $update->version ) ) {
			continue;
		}

		// These should remain constant.
		$update->id     = $plugin_data['UpdateURI'];
		$update->plugin = $plugin_file;

		//  needs the version field specified as 'new_version'.
		if ( ! isset( $update->new_version ) ) {
			$update->new_version = $update->version;
		}

		// Handle any translation updates.
		if ( ! empty( $update->translations ) ) {
			foreach ( $update->translations as $translation ) {
				if ( isset( $translation['language'], $translation['package'] ) ) {
					$translation['type'] = 'plugin';
					$translation['slug'] = isset( $update->slug ) ? $update->slug : $update->id;

					$updates->translations[] = $translation;
				}
			}
		}

		unset( $updates->no_update[ $plugin_file ], $updates->response[ $plugin_file ] );

		if ( version_compare( $update->new_version, $plugin_data['Version'], '>' ) ) {
			$updates->response[ $plugin_file ] = $update;
		} else {
			$updates->no_update[ $plugin_file ] = $update;
		}
	}

	$sanitize_plugin_update_payload = static function( &$item ) {
		$item = (object) $item;

		unset( $item->translations, $item->compatibility );

		return $item;
	};

	array_walk( $updates->response, $sanitize_plugin_update_payload );
	array_walk( $updates->no_update, $sanitize_plugin_update_payload );

	set_site_transient( 'update_plugins', $updates );
}

/**
 * Checks for available updates to themes based on the latest versions hosted on .org.
 *
 * Despite its name this function does not actually perform any updates, it only checks for available updates.
 *
 * A list of all themes installed is sent to , along with the site locale.
 *
 * Checks against the  server at api..org. Will only check
 * if  isn't installing.
 *
 * @since 2.7.0
 *
 * @global string $_version The  version string.
 *
 * @param array $extra_stats Extra statistics to report to the .org API.
 */
function _update_themes( $extra_stats = array() ) {
	if ( _installing() ) {
		return;
	}

	// Include an unmodified $_version.
	require ABSPATH . INC . '/version.php';

	$installed_themes = _get_themes();
	$translations     = _get_installed_translations( 'themes' );

	$last_update = get_site_transient( 'update_themes' );

	if ( ! is_object( $last_update ) ) {
		$last_update = new stdClass;
	}

	$themes  = array();
	$checked = array();
	$request = array();

	// Put slug of active theme into request.
	$request['active'] = get_option( 'stylesheet' );

	foreach ( $installed_themes as $theme ) {
		$checked[ $theme->get_stylesheet() ] = $theme->get( 'Version' );

		$themes[ $theme->get_stylesheet() ] = array(
			'Name'       => $theme->get( 'Name' ),
			'Title'      => $theme->get( 'Name' ),
			'Version'    => $theme->get( 'Version' ),
			'Author'     => $theme->get( 'Author' ),
			'Author URI' => $theme->get( 'AuthorURI' ),
			'Template'   => $theme->get_template(),
			'Stylesheet' => $theme->get_stylesheet(),
		);
	}

	$doing_cron = _doing_cron();

	// Check for update on a different schedule, depending on the page.
	switch ( current_filter() ) {
		case 'upgrader_process_complete':
			$timeout = 0;
			break;
		case 'load-update-core.php':
			$timeout = MINUTE_IN_SECONDS;
			break;
		case 'load-themes.php':
		case 'load-update.php':
			$timeout = HOUR_IN_SECONDS;
			break;
		default:
			if ( $doing_cron ) {
				$timeout = 2 * HOUR_IN_SECONDS;
			} else {
				$timeout = 12 * HOUR_IN_SECONDS;
			}
	}

	$time_not_changed = isset( $last_update->last_checked ) && $timeout > ( time() - $last_update->last_checked );

	if ( $time_not_changed && ! $extra_stats ) {
		$theme_changed = false;

		foreach ( $checked as $slug => $v ) {
			if ( ! isset( $last_update->checked[ $slug ] ) || (string) $last_update->checked[ $slug ] !== (string) $v ) {
				$theme_changed = true;
			}
		}

		if ( isset( $last_update->response ) && is_array( $last_update->response ) ) {
			foreach ( $last_update->response as $slug => $update_details ) {
				if ( ! isset( $checked[ $slug ] ) ) {
					$theme_changed = true;
					break;
				}
			}
		}

		// Bail if we've checked recently and if nothing has changed.
		if ( ! $theme_changed ) {
			return;
		}
	}

	// Update last_checked for current to prevent multiple blocking requests if request hangs.
	$last_update->last_checked = time();
	set_site_transient( 'update_themes', $last_update );

	$request['themes'] = $themes;

	$locales = array_values( get_available_languages() );

	/**
	 * Filters the locales requested for theme translations.
	 *
	 * @since 3.7.0
	 * @since 4.5.0 The default value of the `$locales` parameter changed to include all locales.
	 *
	 * @param array $locales Theme locales. Default is all available locales of the site.
	 */
	$locales = apply_filters( 'themes_update_check_locales', $locales );
	$locales = array_unique( $locales );

	if ( $doing_cron ) {
		$timeout = 30;
	} else {
		// Three seconds, plus one extra second for every 10 themes.
		$timeout = 3 + (int) ( count( $themes ) / 10 );
	}

	$options = array(
		'timeout'    => $timeout,
		'body'       => array(
			'themes'       => _json_encode( $request ),
			'translations' => _json_encode( $translations ),
			'locale'       => _json_encode( $locales ),
		),
		'user-agent' => '/' . $_version . '; ' . home_url( '/' ),
	);

	if ( $extra_stats ) {
		$options['body']['update_stats'] = _json_encode( $extra_stats );
	}

	$url      = 'http://api..org/themes/update-check/1.1/';
	$http_url = $url;
	$ssl      = _http_supports( array( 'ssl' ) );

	if ( $ssl ) {
		$url = set_url_scheme( $url, 'https' );
	}

	$raw_response = _remote_post( $url, $options );

	if ( $ssl && is__error( $raw_response ) ) {
		trigger_error(
			sprintf(
				/* translators: %s: Support forums URL. */
				__( 'An unexpected error occurred. Something may be wrong with .org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
				__( 'https://.org/support/forums/' )
			) . ' ' . __( '( could not establish a secure connection to .org. Please contact your server administrator.)' ),
			headers_sent() || _DEBUG ? E_USER_WARNING : E_USER_NOTICE
		);
		$raw_response = _remote_post( $http_url, $options );
	}

	if ( is__error( $raw_response ) || 200 !== _remote_retrieve_response_code( $raw_response ) ) {
		return;
	}

	$new_update               = new stdClass;
	$new_update->last_checked = time();
	$new_update->checked      = $checked;

	$response = json_decode( _remote_retrieve_body( $raw_response ), true );

	if ( is_array( $response ) ) {
		$new_update->response     = $response['themes'];
		$new_update->no_update    = $response['no_update'];
		$new_update->translations = $response['translations'];
	}

	set_site_transient( 'update_themes', $new_update );
}

/**
 * Performs  automatic background updates.
 *
 * Updates  core plus any plugins and themes that have automatic updates enabled.
 *
 * @since 3.7.0
 */
function _maybe_auto_update() {
	include_once ABSPATH . 'admin/includes/admin.php';
	require_once ABSPATH . 'admin/includes/class-upgrader.php';

	$upgrader = new _Automatic_Updater;
	$upgrader->run();
}

/**
 * Retrieves a list of all language updates available.
 *
 * @since 3.7.0
 *
 * @return object[] Array of translation objects that have available updates.
 */
function _get_translation_updates() {
	$updates    = array();
	$transients = array(
		'update_core'    => 'core',
		'update_plugins' => 'plugin',
		'update_themes'  => 'theme',
	);

	foreach ( $transients as $transient => $type ) {
		$transient = get_site_transient( $transient );

		if ( empty( $transient->translations ) ) {
			continue;
		}

		foreach ( $transient->translations as $translation ) {
			$updates[] = (object) $translation;
		}
	}

	return $updates;
}

/**
 * Collect counts and UI strings for available updates
 *
 * @since 3.3.0
 *
 * @return array
 */
function _get_update_data() {
	$counts = array(
		'plugins'      => 0,
		'themes'       => 0,
		''    => 0,
		'translations' => 0,
	);

	$plugins = current_user_can( 'update_plugins' );

	if ( $plugins ) {
		$update_plugins = get_site_transient( 'update_plugins' );

		if ( ! empty( $update_plugins->response ) ) {
			$counts['plugins'] = count( $update_plugins->response );
		}
	}

	$themes = current_user_can( 'update_themes' );

	if ( $themes ) {
		$update_themes = get_site_transient( 'update_themes' );

		if ( ! empty( $update_themes->response ) ) {
			$counts['themes'] = count( $update_themes->response );
		}
	}

	$core = current_user_can( 'update_core' );

	if ( $core && function_exists( 'get_core_updates' ) ) {
		$update_ = get_core_updates( array( 'dismissed' => false ) );

		if ( ! empty( $update_ )
			&& ! in_array( $update_[0]->response, array( 'development', 'latest' ), true )
			&& current_user_can( 'update_core' )
		) {
			$counts[''] = 1;
		}
	}

	if ( ( $core || $plugins || $themes ) && _get_translation_updates() ) {
		$counts['translations'] = 1;
	}

	$counts['total'] = $counts['plugins'] + $counts['themes'] + $counts[''] + $counts['translations'];
	$titles          = array();

	if ( $counts[''] ) {
		/* translators: %d: Number of available  updates. */
		$titles[''] = sprintf( __( '%d  Update' ), $counts[''] );
	}

	if ( $counts['plugins'] ) {
		/* translators: %d: Number of available plugin updates. */
		$titles['plugins'] = sprintf( _n( '%d Plugin Update', '%d Plugin Updates', $counts['plugins'] ), $counts['plugins'] );
	}

	if ( $counts['themes'] ) {
		/* translators: %d: Number of available theme updates. */
		$titles['themes'] = sprintf( _n( '%d Theme Update', '%d Theme Updates', $counts['themes'] ), $counts['themes'] );
	}

	if ( $counts['translations'] ) {
		$titles['translations'] = __( 'Translation Updates' );
	}

	$update_title = $titles ? esc_attr( implode( ', ', $titles ) ) : '';

	$update_data = array(
		'counts' => $counts,
		'title'  => $update_title,
	);
	/**
	 * Filters the returned array of update data for plugins, themes, and  core.
	 *
	 * @since 3.5.0
	 *
	 * @param array $update_data {
	 *     Fetched update data.
	 *
	 *     @type array   $counts       An array of counts for available plugin, theme, and  updates.
	 *     @type string  $update_title Titles of available updates.
	 * }
	 * @param array $titles An array of update counts and UI strings for available updates.
	 */
	return apply_filters( '_get_update_data', $update_data, $titles );
}

/**
 * Determines whether core should be updated.
 *
 * @since 2.8.0
 *
 * @global string $_version The  version string.
 */
function _maybe_update_core() {
	// Include an unmodified $_version.
	require ABSPATH . INC . '/version.php';

	$current = get_site_transient( 'update_core' );

	if ( isset( $current->last_checked, $current->version_checked )
		&& 12 * HOUR_IN_SECONDS > ( time() - $current->last_checked )
		&& $current->version_checked === $_version
	) {
		return;
	}

	_version_check();
}
/**
 * Check the last time plugins were run before checking plugin versions.
 *
 * This might have been backported to  2.6.1 for performance reasons.
 * This is used for the admin to check only so often instead of every page
 * load.
 *
 * @since 2.7.0
 * @access private
 */
function _maybe_update_plugins() {
	$current = get_site_transient( 'update_plugins' );

	if ( isset( $current->last_checked )
		&& 12 * HOUR_IN_SECONDS > ( time() - $current->last_checked )
	) {
		return;
	}

	_update_plugins();
}

/**
 * Check themes versions only after a duration of time.
 *
 * This is for performance reasons to make sure that on the theme version
 * checker is not run on every page load.
 *
 * @since 2.7.0
 * @access private
 */
function _maybe_update_themes() {
	$current = get_site_transient( 'update_themes' );

	if ( isset( $current->last_checked )
		&& 12 * HOUR_IN_SECONDS > ( time() - $current->last_checked )
	) {
		return;
	}

	_update_themes();
}

/**
 * Schedule core, theme, and plugin update checks.
 *
 * @since 3.1.0
 */
function _schedule_update_checks() {
	if ( ! _next_scheduled( '_version_check' ) && ! _installing() ) {
		_schedule_event( time(), 'twicedaily', '_version_check' );
	}

	if ( ! _next_scheduled( '_update_plugins' ) && ! _installing() ) {
		_schedule_event( time(), 'twicedaily', '_update_plugins' );
	}

	if ( ! _next_scheduled( '_update_themes' ) && ! _installing() ) {
		_schedule_event( time(), 'twicedaily', '_update_themes' );
	}
}

/**
 * Clear existing update caches for plugins, themes, and core.
 *
 * @since 4.1.0
 */
function _clean_update_cache() {
	if ( function_exists( '_clean_plugins_cache' ) ) {
		_clean_plugins_cache();
	} else {
		delete_site_transient( 'update_plugins' );
	}

	_clean_themes_cache();

	delete_site_transient( 'update_core' );
}

if ( ( ! is_main_site() && ! is_network_admin() ) || _doing_ajax() ) {
	return;
}

add_action( 'admin_init', '_maybe_update_core' );
add_action( '_version_check', '_version_check' );

add_action( 'load-plugins.php', '_update_plugins' );
add_action( 'load-update.php', '_update_plugins' );
add_action( 'load-update-core.php', '_update_plugins' );
add_action( 'admin_init', '_maybe_update_plugins' );
add_action( '_update_plugins', '_update_plugins' );

add_action( 'load-themes.php', '_update_themes' );
add_action( 'load-update.php', '_update_themes' );
add_action( 'load-update-core.php', '_update_themes' );
add_action( 'admin_init', '_maybe_update_themes' );
add_action( '_update_themes', '_update_themes' );

add_action( 'update_option_LANG', '_clean_update_cache', 10, 0 );

add_action( '_maybe_auto_update', '_maybe_auto_update' );

add_action( 'init', '_schedule_update_checks' );
