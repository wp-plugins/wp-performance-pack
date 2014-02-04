<?php
/**
 * Disable backend translations
 *
 * @author Björn Ahrens
 * @since 0.1
 */
 
function load_textdomain_override( $retval, $domain, $mofile ) {
	global $l10n;

	if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
		global $wp_performance_pack;
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
	return false;
}

add_filter( 'override_load_textdomain', 'load_textdomain_override', 0, 3 );
?>