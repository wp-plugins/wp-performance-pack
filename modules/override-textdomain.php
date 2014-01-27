<?php

function load_textdomain_override( $retval, $domain, $mofile ) {
	global $l10n, $wp_performance_pack;

	if ( $wp_performance_pack->options['disable_backend_translation'] && is_admin() && !defined( 'DOING_AJAX' ) ) {
		$l10n[$domain] = new NOOP_Translations();
		return true;
	} else {
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
}

add_filter( 'override_load_textdomain', 'load_textdomain_override', 0, 3 );

?>