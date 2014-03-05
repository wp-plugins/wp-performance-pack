<?php
/**
 * Activate override textdomain
 *
 * @author Björn Ahrens
 * @package WP Performance Pack
 * @since 0.1
 */
 
function wppp_load_textdomain_override( $retval, $domain, $mofile ) {
	global $l10n, $wp_performance_pack;

	$result = false;
	$mo = NULL;

	if ( $wp_performance_pack->options['disable_backend_translation'] 
		 && is_admin() 
		 && !( defined( 'DOING_AJAX' ) && DOING_AJAX && false === strpos( wp_get_referer(), '/wp-admin/' ) ) ) {
		if ( $wp_performance_pack->options['dbt_allow_user_override'] ) {
			global $current_user;
			if ( !function_exists('wp_get_current_user')) {
				require_once(ABSPATH . "wp-includes/pluggable.php");
			}
			wp_cookie_constants();
			$current_user = wp_get_current_user();

			$user_setting = get_user_option ( 'wppp_translate_backend', $current_user->user_ID );
			$user_override = $user_setting === 'true' || ( $wp_performance_pack->options['dbt_user_default_translated'] && $user_setting === false );
			if ( !$user_override ) {
				$mo = $l10n['WPPP_NOOP'];
				$result = true;
			}
		} else {
			$mo = $l10n['WPPP_NOOP'];
			$result = true;
		}
	}


	if ( $mo === NULL ) {
		do_action( 'load_textdomain', $domain, $mofile );
		$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

		if ( isset( $l10n[$domain] ) ) {
			if ( $l10n[$domain] instanceof MO_dynamic && $l10n[$domain]->Mo_file_loaded( $mofile ) ) {
				return true;
			}
		}

		if ( $wp_performance_pack->options['debug'] ) {
			$callers=debug_backtrace();
			$wp_performance_pack->dbg_textdomains[$domain]['mofiles'][] = $mofile;
			$wp_performance_pack->dbg_textdomains[$domain]['callers'][] = $callers;
		}

		if ( !is_readable( $mofile ) ) {
			if ( $wp_performance_pack->options['debug'] ) {
				$wp_performance_pack->dbg_textdomains[$domain]['mofileexists'][] = 'no';
			}
			return false; // return false is important so load_plugin_textdomain/load_theme_textdomain/... can call load_textdomain for different locations
		} elseif ( $wp_performance_pack->options['debug'] ) {
			$wp_performance_pack->dbg_textdomains[$domain]['mofileexists'][] = 'yes';
		}
	} else {
		if ( $wp_performance_pack->options['debug'] ) {
			$callers=debug_backtrace();
			$wp_performance_pack->dbg_textdomains[$domain]['mofiles'][] = $mofile;
			$wp_performance_pack->dbg_textdomains[$domain]['mofileexists'][] = 'disabled';
			$wp_performance_pack->dbg_textdomains[$domain]['callers'][] = $callers;
		}
	}


	if ( $mo === NULL && $wp_performance_pack->options['use_native_gettext'] && extension_loaded( 'gettext' ) ) {
		require_once(sprintf( "%s/class.native-gettext.php", dirname( __FILE__ ) ) );
		$mo = new Translate_GetText_Native ();
		if ( $mo->import_from_file( $mofile ) ) { 
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
			$result = true;
		} else {
			$mo->unhook_and_close();
			$mo = NULL;
		}
	}

	if ( $mo !== NULL ) {
		if ( isset( $l10n[$domain] ) ) {
			$mo->merge_with( $l10n[$domain] );
			if ( $l10n[$domain] instanceof MO_dynamic ) {
				$l10n[$domain]->unhook_and_close();
			}
		}
		$l10n[$domain] = $mo;
	}

	return $result;
}
?>