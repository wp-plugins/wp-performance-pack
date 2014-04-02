<?php
/**
 * Activate JIT localize if available
 *
 * @author Björn Ahrens
 * @since 0.2
 */
 
global $wp_version, $wp_performance_pack;
$jit_available = false;
if ( in_array( $wp_version, WP_Performance_Pack::$jit_versions) ) {
	require( sprintf( "%s/class.labelsobject.php", dirname( __FILE__ ) ) );
	require( sprintf( "%s/jit-by-version/wp".$wp_version.".php", dirname( __FILE__ ) ) );
	$jit_available = true;
}

if ( $jit_available ) {
	require( sprintf( "%s/class.wp-scripts-override.php", dirname( __FILE__ ) ) );
	global $wp_scripts;
	if ( !isset( $wp_scripts ) && !defined('IFRAME_REQUEST') ) {
		remove_action( 'wp_default_scripts', 'wp_default_scripts' );
		add_action( 'wp_default_scripts', 'wp_jit_default_scripts' );
		$wp_scripts = new WP_Scripts_Override();
	}
}
?>