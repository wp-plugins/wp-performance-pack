<?php
/**
 * Serve intermediate images on demand. Is called via mod_rewrite rule.
 * Based on Dynamic Image Resizer (http://ottopress.com) by Otto42 (http://ottodestruct.com).
 *
 * @author Björn Ahrens
 * @package WP Performance Pack
 * @since 1.1
 */

define( 'SHORTINIT', true );

require( '../../../../wp-load.php' );

// dummy add_shortcode required for media.php - we don't need any shortcodes so don't load that file and use a dummy instead
function add_shortcode() {}

require( ABSPATH . 'wp-includes/media.php' );

// untrailingslashit from wp-includes/formatting.php. is required for get_option - formatting.php is more than 120kb big - too big to include for just one small function
function untrailingslashit($string) {
	return rtrim($string, '/');
}

if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
}

if ( preg_match( '/(.*)-([0-9]+)x([0-9]+)(c)?\.(jpeg|jpg|png|gif)/i', $_SERVER['REQUEST_URI'], $matches ) ) {
	// should always match as this file is called using mod_rewrite using the exact same regexp

	$filename = urldecode( $matches[1] . '.' . $matches[5] );
	$width = $matches[2];
	$height = $matches[3];
	$crop = !empty( $matches[4] );

	$uploads_dir = wp_upload_dir();
	$temp = parse_url( $uploads_dir['baseurl'] );
	$upload_path = $temp['path'];
	$findfile = str_replace( $upload_path, '', $filename );
	$basefile = $uploads_dir['basedir'] . $findfile;
	$suffix = $width . 'x' . $height;
	/*if ( $crop ) {
		$suffix .= 'c';
	}*/

	if ( file_exists( $basefile ) ) {
		// we have the file, so call the wp function to actually resize the image
		$image = wp_get_image_editor( $basefile );
		if ( ! is_wp_error( $image ) ) {
			if ( !$crop ) {
				// could be an old link - only after activating the plugin cropped images get the "c" suffix
				// "old" links don't conatin the "c" but could be cropped
				$base_size = $image->get_size();
				$new_size = wp_constrain_dimensions($base_size['width'], $base_size['height'], $width, $height);
				if ( $new_size[0] !== $width || $new_size[1] !== $height ) {
					$crop = true;
				}
			}
			$image->set_quality( 80 );
			$image->resize( $width, $height, $crop );
			$wppp_opts = get_option( 'wppp_option' );
			if ( !isset( $wppp_opts['dynamic_images_nosave'] ) || !$wppp_opts['dynamic_images_nosave'] ) {
				$image->save( $image->generate_filename( $suffix ) );
			}
			$image->stream();
			exit;
		}
	}
}

?>