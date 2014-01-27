<?php

global $wp_version;
$jit_available = false;

if ( $wp_version == '3.8.1' ) {
	include( sprintf( "%s/jit-by-version/wp3.8.1.php", dirname( __FILE__ ) ) );
	$jit_available = true;
} else if ( $wp_version == '3.6' ) {
	include( sprintf( "%s/jit-by-version/wp3.6.php", dirname( __FILE__ ) ) );
	$jit_available = true;
}

if ( $jit_available ) {
	remove_action( 'wp_default_scripts', 'wp_default_scripts' );
	add_action( 'wp_default_scripts', 'wp_jit_default_scripts' );

	//remove_filter( 'wp_print_scripts', 'wp_just_in_time_script_localization' );
	add_filter( 'wp_print_scripts', 'wp_jit_localization' );
}
?>