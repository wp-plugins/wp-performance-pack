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
		$l10n[$domain] = new NOOP_Translations();
		return true;
	} else {
		return false;
	}
}

add_filter( 'override_load_textdomain', 'load_textdomain_override', 0, 3 );
?>