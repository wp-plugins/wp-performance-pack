<?php
/**
 * Common code for plugin and modules
 *
 * @author Björn Ahrens
 * @package WP Performance Pack
 * @since 1.6.3
 */

class WP_Performance_Pack_Commons {
	const cache_group = 'wppp1.0'; 	// WPPP cache group name = wppp + version of last change to cache. 
									// This way no cache conflicts occur while old cache entries just expire.

	const wppp_version = '1.7.4';

	public static $options_name = 'wppp_option';
	public static $options_default = array(
			'use_mo_dynamic' => true,
			'use_jit_localize' => false,
			'disable_backend_translation' => false,
			'dbt_allow_user_override' => false,
			'dbt_user_default_translated' => false,
			'use_native_gettext' => false,
			'mo_caching' => false,
			'debug' => false,
			'advanced_admin_view' => false,
			'dynamic_images' => false,
			'dynamic_images_nosave' => false,
			'dynamic_images_cache' => false,
			'dynamic_images_rthook' => false,
			'dynamic_images_rthook_force' => false,
			'dynamic_images_exif_thumbs' => false,
			'dynimg_quality' => 80,
			'dyn_links' => false,
			'cdn' => false,
			'cdnurl' => '',
			'cdn_images' => 'both',
		);

	public $options = NULL;
	public $is_network = false;

	function load_options () {
		if ( $this->options == NULL ) {
			$this->options = $this->get_option( 'wppp_option' );

			foreach ( self::$options_default as $key => $value ) {
				if ( !isset( $this->options[$key] ) ) {
					$this->options[$key] = self::$options_default[$key];
				}
			}
		}
	}

	function get_option( $option_name ) {
		if ( $this->is_network ) {
			return get_site_option( $option_name );
		} else {
			return get_option( $option_name );
		}
	}

	function update_option( $option_name, $data ) {
		if ( $this->is_network ) {
			return update_site_option( $option_name, $data );
		} else {
			return update_option( $option_name, $data );
		}
	}

}


?>