<?php
/**
 * Activate override textdomain
 *
 * @author Björn Ahrens
 * @since 0.1
 */
 
function load_textdomain_override( $retval, $domain, $mofile ) {
	global $l10n, $wp_performance_pack;

	if ( $wp_performance_pack->options['disable_backend_translation'] && is_admin() && !defined( 'DOING_AJAX' ) ) {
		if ( $wp_performance_pack->options['dbt_allow_user_override'] ) {
			global $current_user;
			if ( !function_exists('wp_get_current_user'))
				require_once(ABSPATH . "wp-includes/pluggable.php"); 
			wp_cookie_constants();
			$current_user = wp_get_current_user();

			if ( get_user_option ( 'wppp_translate_backend', $current_user->user_ID ) !== 'true' ) {
				$l10n[$domain] = new NOOP_Translations();
				return true;
			}
		} else {
			$l10n[$domain] = new NOOP_Translations();
			return true;
		}
	}
	
	do_action( 'load_textdomain', $domain, $mofile );
	$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

	if ( isset( $l10n[$domain] ) ) {
		$mo = $l10n[$domain];
		if ( $mo instanceof MO_dynamic && $mo->Mo_file_loaded( $mofile ) ) {
			return true;
		}
	}
	if ( !is_readable( $mofile ) ) {
		return false;
	}

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$callers=debug_backtrace();
		$wp_performance_pack->dbg_textdomains[] = array ( 'domain' => $domain, 'mofile' => $mofile, 'caller' => $callers );
	}

	if ($wp_performance_pack->options['use_native_gettext'] && extension_loaded( 'gettext' )) {
		require_once(sprintf( "%s/class.native-gettext.php", dirname( __FILE__ ) ) );
		$mo = new Translate_GetText_Native ();
	} else {
		require_once(sprintf( "%s/class.mo-dynamic.php", dirname( __FILE__ ) ) );
		$mo = new MO_dynamic ();
	}
	if ( !$mo->import_from_file( $mofile ) ) { 
		return false;
	}

	if ( isset( $l10n[$domain] ) )
		$mo->merge_with( $l10n[$domain] );
	$l10n[$domain] = &$mo;

	return true;
}

add_filter( 'override_load_textdomain', 'load_textdomain_override', 0, 3 );
?>