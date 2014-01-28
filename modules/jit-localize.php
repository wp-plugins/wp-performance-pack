<?php
/**
 * Activate JIT localize if available
 *
 * @author Björn Ahrens
 * @since 0.2
 */
 
global $wp_version;
$jit_available = false;

if ( $wp_version == '3.8.1' ) {
	include( sprintf( "%s/class.labelsobject.php", dirname( __FILE__ ) ) );
	include( sprintf( "%s/jit-by-version/wp3.8.1.php", dirname( __FILE__ ) ) );
	$jit_available = true;
}

if ( $jit_available ) {
	include( sprintf( "%s/class.wp-scripts-override.php", dirname( __FILE__ ) ) );
	global $wp_scripts;
	
	if ( !isset( $wp_scripts ) ) {
		remove_action( 'wp_default_scripts', 'wp_default_scripts' );
		add_action( 'wp_default_scripts', 'wp_jit_default_scripts' );
		$wp_scripts = new WP_Scripts_Override();
	}
}
?>