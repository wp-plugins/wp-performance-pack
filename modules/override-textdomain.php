<?php
/**
 * Activate override textdomain
 *
 * @author Björn Ahrens
 * @package WP Performance Pack
 * @since 0.1
 */
 
function get_default_noop () {
	static $default_NOOP = NULL;
	if ( NULL === $default_NOOP )
		$default_NOOP = new NOOP_Translations();
	return $default_NOOP;
}
 
function load_textdomain_override( $retval, $domain, $mofile ) {
	global $l10n, $wp_performance_pack;

	$result = false;
	$mo = NULL;

	if ( $wp_performance_pack->options['disable_backend_translation'] && is_admin() && !defined( 'DOING_AJAX' ) ) {
		if ( $wp_performance_pack->options['dbt_allow_user_override'] ) {
			global $current_user;
			if ( !function_exists('wp_get_current_user'))
				require_once(ABSPATH . "wp-includes/pluggable.php"); 
			wp_cookie_constants();
			$current_user = wp_get_current_user();

			if ( get_user_option ( 'wppp_translate_backend', $current_user->user_ID ) !== 'true' ) {
				$mo = get_default_noop();
				$result = true;
			}
		} else {
			$mo = get_default_noop();
			$result = true;
		}
	}

	if ( $mo === NULL ) {
		do_action( 'load_textdomain', $domain, $mofile );
		$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

		if ( isset( $l10n[$domain] ) ) {
			$mo = $l10n[$domain];
			if ( $mo instanceof MO_dynamic && $mo->Mo_file_loaded( $mofile ) ) {
				return true;
			}
			$mo = NULL;
		}
		if ( !is_readable( $mofile ) ) {
			return false;
		}
	}

	if ( $wp_performance_pack->options['debug'] ) {
		$callers=debug_backtrace();
		$dbginfo = array ( 'domain' => $domain, 'mofile' => $mofile, 'caller' => $callers );
	}

	if ( $mo === NULL && $wp_performance_pack->options['use_native_gettext'] && extension_loaded( 'gettext' ) ) {
		require_once(sprintf( "%s/class.native-gettext.php", dirname( __FILE__ ) ) );
		$mo = new Translate_GetText_Native ();
		if ( $mo->import_from_file( $mofile ) ) { 
			if ( isset( $l10n[$domain] ) )
				$mo->merge_with( $l10n[$domain] );
			$l10n[$domain] = &$mo;
			$result = true;
		} else {
			$mo = NULL;
		}
	}
	
	if ( $mo === NULL && $wp_performance_pack->options['use_mo_dynamic'] ) {
		require_once(sprintf( "%s/class.mo-dynamic.php", dirname( __FILE__ ) ) );
		if ( $wp_performance_pack->options['debug'] ) {
			$mo = new MO_dynamic_Debug ( $domain, $wp_performance_pack->options['mo_caching'] );
		} else {
			$mo = new MO_dynamic ( $domain, $wp_performance_pack->options['mo_caching'] );
		}
		if ( $mo->import_from_file( $mofile ) ) { 
			if ( isset( $l10n[$domain] ) )
				$mo->merge_with( $l10n[$domain] );
			$l10n[$domain] = $mo;
			$result = true;
		} else {
			$mo = NULL;
		}
	}

	if ( $wp_performance_pack->options['debug'] ) {
		if ( $result) {
			$dbginfo['override'] = &$mo;
		} else {
			$dbginfo['override'] = 'false';
		}
		$wp_performance_pack->dbg_textdomains[] = $dbginfo;
	}

	return $result;
}

function wppp_cache_translations () {
	global $l10n;
	foreach ($l10n as $domain => $mo) {
		if ($mo instanceof MO_dynamic) {
			$mo->save_to_cache();
		}
	}
}

add_filter( 'override_load_textdomain', 'load_textdomain_override', 0, 3 );
?>