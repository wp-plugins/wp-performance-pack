<?php
/**
 * Don't generate intermediate images on upload, but on first access.
 * Image creation is done via serve-dynamic-images.php.
 * Based on Dynamic Image Resizer (http://ottopress.com) by Samuel Wood (http://ottodestruct.com).
 *
 * @author Björn Ahrens
 * @package WP Performance Pack
 * @since 1.1
 */

class WPPP_Dynamic_Images {

	private $dynimg_image_sizes = NULL;

	function __construct () {
		// this gets called at init
		self::set_rewrite_rules();
		add_filter( 'intermediate_image_sizes_advanced', array ( $this, 'dynimg_image_sizes_advanced' ) );
		add_filter( 'wp_generate_attachment_metadata', array ( $this, 'dynimg_generate_metadata' ) );
		add_action( 'shutdown', array( $this, 'save_preset_image_sizes' ) );

		global $wp_performance_pack;
		if ( $wp_performance_pack->options['dynamic_images_rthook'] ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				add_filter( 'wp_update_attachment_metadata', array ( $this, 'rebuild_thumbnails_delete_hook' ), 100, 2 );
			}
			add_action( 'admin_notices', array( $this, 'rthook_notice') ); 
		}
	}

	public static function set_rewrite_rules () {
		$path = substr( plugins_url( 'modules/serve-dynamic-images.php', dirname( __FILE__ ) ), strlen( site_url() ) + 1 ); // cut wp-content including trailing slash
		add_rewrite_rule( '(.*)-([0-9]+)x([0-9]+)?\.((?i)jpeg|jpg|png|gif)' , $path, 'top' );
		add_filter ( 'mod_rewrite_rules', array ( 'WPPP_Dynamic_Images', 'mod_rewrite_rules' ) );
	}

	public static function flush_rewrite_rules ( $enabled ) {
		if ( $enabled ) {
			self::set_rewrite_rules();
		}
		flush_rewrite_rules();
	}

	public static function mod_rewrite_rules ( $rules ) {
		$lines = explode( "\n", $rules );
		$rules = '';
		for ($i = 0, $max = count($lines); $i<$max; $i++ ) {
			if ( strpos( $lines[$i], 'serve-dynamic-images.php' ) !== false ){
				// extend rewrite rule by conditionals, so if the requested file exist it gets served directly
				$rules .= "RewriteCond %{REQUEST_FILENAME} !-f \n";
			}
			$rules .= $lines[$i] . "\n";
		}
		return $rules;
	}

	// prevent WP from generating resized images on upload
	function dynimg_image_sizes_advanced( $sizes ) {
		// save the sizes to a global, because the next function needs them to lie to WP about what sizes were generated
		$this->dynimg_image_sizes = $sizes;

		// force WP to not create intermediate images by telling it there are no sizes
		return array();
	}

	// trick WP into thinking images were generated anyway
	function dynimg_generate_metadata( $meta ) {
		if ( $this->dynimg_image_sizes != NULL ) {
			foreach ( $this->dynimg_image_sizes as $sizename => $size ) {
				// figure out what size WP would make this:
				$newsize = image_resize_dimensions( $meta['width'], $meta['height'], $size['width'], $size['height'], $size['crop'] );

				if ($newsize) {
					$info = pathinfo( $meta['file'] );
					$ext = $info['extension'];
					$name = wp_basename($meta['file'], ".$ext");

					$suffix = "{$newsize[4]}x{$newsize[5]}";

					// build the fake meta entry for the size in question
					$resized = array(
						'file' => "{$name}-{$suffix}.{$ext}",
						'width' => $newsize[4],
						'height' => $newsize[5],
					);

					$meta['sizes'][$sizename] = $resized;
				}
			}
			$this->dynimg_image_sizes = NULL;
		}
		return $meta;
	}
	
	function save_preset_image_sizes() {
		global $_wp_additional_image_sizes;
 
		$sizes = array();
		foreach ( get_intermediate_image_sizes() as $s ) {
			$sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => false );
			if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) {
				$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
			} else {
				$sizes[$s]['width'] = intval ( get_option( "{$s}_size_w" ) ); // For default sizes set in options
				if ( $sizes[$s]['width'] == 0 ) {
					unset( $sizes[$s] );
					continue;
				}
			}
			if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) {
				$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
			} else {
				$sizes[$s]['height'] = intval ( get_option( "{$s}_size_h" ) ); // For default sizes set in options
				if ( $sizes[$s]['height'] == 0 ) {
					unset( $sizes[$s] );
					continue;
				}
			}
			if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) ) {
				$sizes[$s]['crop'] = $_wp_additional_image_sizes[$s]['crop'] ? true : false; // For theme-added sizes
			} else {
				$sizes[$s]['crop'] = get_option( "{$s}_crop" ) ? true : false; // For default sizes set in options
			}
		}
		add_option ( 'wppp_dynimg_sizes', $sizes );
	}

	function rebuild_thumbnails_delete_hook ($data, $postID) {
		global $wp_current_filter;
		if ( is_array( $wp_current_filter ) && in_array( 'wp_ajax_regeneratethumbnail', $wp_current_filter ) ) {
			if ( $attach_meta = wp_get_attachment_metadata( $postID ) ) {
				global $wp_performance_pack;
				if ( $wp_performance_pack->options['dynamic_images_rthook_force'] ) {
					// delete all potential thumbnail files (filname.ext ~ filename-*x*.ext)
					$upload_dir = wp_upload_dir();
					$filename = $upload_dir['basedir'] . '/' . $attach_meta['file'];
					$info = pathinfo($filename);
					$ext = $info['extension'];
					$pattern = str_replace(".$ext", "-*x*.$ext", $filename);
					foreach (glob($pattern) as $thumbname) {
						@unlink($thumbname);
					}
				} else {
					if ( isset( $attach_meta['sizes'] ) ) {
						$upload_dir = wp_upload_dir();
						$filepath = $upload_dir['basedir'] . '/' . dirname( $attach_meta['file'] ) . '/';
						$filename = wp_basename( $attach_meta['file'] );
						foreach ( $attach_meta['sizes'] as $size => $size_data ) {
							$file = $filepath . $size_data['file'];
							if ( file_exists( $file ) && ( $size_data['file'] != $filename ) ) {
								@unlink( $file );
							}
						}
					}
				}
			}
		}
		return $data;
	}
	
	function rthook_notice () { 
		// display message on Rebuild Thumbnails page
		$screen = get_current_screen(); 
		if ( $screen->id == 'tools_page_regenerate-thumbnails' ) : ?>
			<div class="update-nag"> 
				<p>
					WPPP Regenerate Thumbnails integration active. Existing intermediate images will be deleted while regenerating thumbnails.
					<?php
						global $wp_performance_pack;
						if ( $wp_performance_pack->options['dynamic_images_rthook_force'] ) : 
							?>
							<br/><strong>Force delete option is active!</strong>
							<?php 
						endif;
					?>
				</p> 
			</div>
		<?php endif; 
	} 
}

?>