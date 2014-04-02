<?php
/**
 * Serve intermediate images on demand. Is called via mod_rewrite rule.
 * Based on Dynamic Image Resizer (http://ottopress.com) by Samuel Wood (http://ottodestruct.com).
 *
 * @author Björn Ahrens
 * @package WP Performance Pack
 * @since 1.1
 */

if ( preg_match( '/(.*)-([0-9]+)x([0-9]+)(c)?\.(jpeg|jpg|png|gif)/i', $_SERVER['REQUEST_URI'], $matches ) ) {
	// should always match as this file is called using mod_rewrite using the exact same regexp
	
	define( 'SHORTINIT', true );
	require( '../../../../wp-load.php' );

	// dummy add_shortcode required for media.php - we don't need any shortcodes so don't load that file and use a dummy instead
	function add_shortcode() {}
	require( ABSPATH . 'wp-includes/media.php' );

	//require( ABSPATH . 'wp-includes/formatting.php' );
	// untrailingslashit from wp-includes/formatting.php. is required for get_option - formatting.php is more than 120kb big - too big to include for just one small function
	function untrailingslashit($string) {
		return rtrim($string, '/');
	}
	if ( ! defined( 'WP_CONTENT_URL' ) ) {
		define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
	}

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
		$wppp_opts = get_option( 'wppp_option' );

		if ( isset( $wppp_opts['dynamic_images_cache'] ) && $wppp_opts['dynamic_images_cache'] && ( false !== ( $data = wp_cache_get ( $basefile . $suffix, 'wppp' ) ) ) ) {
			header( 'Content-Type: ' . $data['mimetype'] );
			echo $data['data'];
			exit;
		}
		
		$image = wp_get_image_editor( $basefile );
		if ( ! is_wp_error( $image ) ) {
			if ( !$crop ) {
				// test if cropping is needed
				$base_size = $image->get_size();
				$new_size = wp_constrain_dimensions($base_size['width'], $base_size['height'], $width, $height);
				if ( $new_size[0] !== $width || $new_size[1] !== $height ) {
					$crop = true;
				}
			}
			$image->set_quality( 80 );
			$image->resize( $width, $height, $crop );
			if ( !isset( $wppp_opts['dynamic_images_nosave'] ) || !$wppp_opts['dynamic_images_nosave'] ) {
				$image->save( $image->generate_filename( $suffix ) );
			} else {
				if ( isset( $wppp_opts['dynamic_images_cache'] ) && $wppp_opts['dynamic_images_cache'] ) {
					$data = array();
					// get image mime type - WP_Image_Editor has functions for this, but they are all protected :(
					// so use the code from get_mime_type directly
					$mime_types = wp_get_mime_types();
					$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
					$extensions = array_keys( $mime_types );
					foreach( $extensions as $_extension ) {
						if ( preg_match( "/{$extension}/i", $_extension ) ) {
							$data['mimetype'] = $mime_types[$_extension];
						}
					}
					ob_start();
					$image->stream();
					$data['data'] = ob_get_contents(); // read from buffer
					ob_end_clean();
					wp_cache_set( $basefile . $suffix, $data, 'wppp', 30 * MINUTE_IN_SECONDS );
				}

				// if intermediate images are not saved, explicitly set cache headers for browser caching
				header( 'Pragma: public' );
				header( 'Cache-Control: max-age=' . HOUR_IN_SECONDS );
				header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + HOUR_IN_SECONDS) . ' GMT' );
			}
			$image->stream();
			exit;
		}
	}
}

// return 404 else
header("Status: 404 Not Found");

?>