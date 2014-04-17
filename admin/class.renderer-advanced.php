<?php
/**
 * Admin settings advanced renderer class. Functions for advanced settings.
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.9
 */
 
include ( sprintf( '%s/class.admin-renderer.php', dirname( __FILE__ ) ) );
 
class WPPP_Admin_Renderer_Advanced extends WPPP_Admin_Renderer {

	/*
	 * Settings page functions
	 */

	function enqueue_scripts_and_styles () {
		wp_register_script( 'jquery-ui-slider-pips', plugin_dir_url( __FILE__ ) . 'js/jquery-ui-slider-pips.min.js', array ( 'jquery-ui-slider' ), false, true );
		wp_register_script( 'wppp-admin-script', plugin_dir_url( __FILE__ ) . 'js/wppp_advanced.js', array ( 'jquery-ui-slider-pips' ), false, true );
		wp_enqueue_script( 'wppp-admin-script' );

		wp_register_style( 'jquery-ui-slider-pips-styles', plugin_dir_url( __FILE__ ) . 'css/jquery-ui-slider-pips.css' );
		wp_register_style( 'wppp-admin-styles', plugin_dir_url( __FILE__ ) . 'css/styles.css' );
		wp_enqueue_style( 'jquery-ui-slider-pips-styles' );
		wp_enqueue_style( 'wppp-admin-styles' );
	}

	function add_help_tab () {
		$screen = get_current_screen();

		// Add my_help_tab if current screen is My Admin Page
		$screen->add_help_tab( array(
			'id'	=> 'wppp_advanced_general',
			'title'	=> __('Overview'),
			'content'	=> '<p>' . __( "Welcome to WP Performance Pack, your first choice for speeding up WordPress core the easy way. The simple view helps you to easily apply  the optimal settings for your blog. Advanced view offers more in depth control of WPPP settings.", 'wppp' ) . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'	=> 'wppp_advanced_l10n',
			'title'	=> __( 'Improve translation performance', 'wppp' ),
			'content'	=> '<p>' . __( 'WPPP offers different options to significantly improve translation performance. These only affect localization of WordPress core, themes and plugins, not translation of content (e.g. when using plugins like WPML). To automatically apply optimal settings for your blog, use the simple view. For implementation details refer to the WordPress plugin page or the development blog.', 'wppp' ) . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'	=> 'wppp_advanced_debugging',
			'title'	=> __( 'Debugging', 'wppp' ),
			'content'	=> '<p>' . sprintf( __( 'WPPP supports debugging using the %s plugin. When installed and activated, debugging adds a new panel to the Debug Bar showing information about loaded textdomains, used translation implementations and translation calls, as well as information about gettext support and other details.', 'wppp' ), '<a href="http://wordpress.org/plugins/debug-bar/">Debug Bar</a>' ) . '</p>',
		) );

		$screen->set_help_sidebar(
			'<p><a href="http://wordpress.org/support/plugin/wp-performance-pack" target="_blank">' . __( 'Support Forums' ) . '</a></p>'
			. '<p><a href="http://www.bjoernahrens.de/software/wp-performance-pack/" target="_blank">' . __( 'Development Blog (german)', 'wppp' ) . '</a></p>'
		);
	}

	/*
	 * Setting page rendering functions
	 */

		public function on_do_options_page() {
		wp_localize_script( 'wppp-admin-script', 'wpppData', array (
			'dynimg-quality' => $this->wppp->options['dynimg_quality'],
		));
	}

	function render_options () {
		?>
		<input type="hidden" <?php $this->e_opt_name('dynimg_quality'); ?> value="<?php echo $this->wppp->options['dynimg_quality']; ?>" />

		<h3 class="title"><?php _e( 'Improve translation performance', 'wppp' ); ?></h3>
		<div>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Use gettext', 'wppp' ); ?></th>
					<td>
						<?php $this->e_radio_enable( 'native-gettext', 'use_native_gettext', $this->is_native_gettext_available() != 0 ); ?>
						<p class="description"><?php _e( 'Use php gettext extension for translations. This is in most cases the fastest way to translate your blog.', 'wppp' ); ?></p>
						<?php $this->do_hint_gettext( true ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="width:15em"><?php _e( 'Use alternative MO reader', 'wppp' ); ?></th>
					<td>
						<?php $this->e_radio_enable( 'mo-dynamic', 'use_mo_dynamic' ); ?>
						<p class="description"><?php _e( 'Alternative MO reader using on demand translation and loading of translation files. Faster and less memory intense than the default WordPress implementation.' ,'wppp' ); ?></p>
						<br/>
						<?php $this->e_checkbox( 'mo-caching', 'mo_caching', __( 'Use caching', 'wppp' ) ); ?>
						<p class="description"><?php _e( 'Cache translations using WordPress Object Cache API', 'wppp' ); ?></p>
						<?php $this->do_hint_caching(); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php _e( 'Use JIT localize', 'wppp' ); ?>
					</th>
					<td>
						<?php $this->e_radio_enable( 'jit', 'use_jit_localize', !$this->is_jit_available() ); ?>
						<p class="description"><?php _e( 'Just in time localization of scripts.', 'wppp' ); ?></p>
						<?php $this->do_hint_jit( true ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php _e( 'Disable back end translation', 'wppp' ); ?>
					</th>
					<td>
						<?php $this->e_radio_enable( 'backend-trans', 'disable_backend_translation' ); ?>
						<p class="description"><?php _e('Disables translation of back end texts.', 'wppp' ); ?></p>
						<br/>
						<?php $this->e_checkbox( 'allow-user-override', 'dbt_allow_user_override', __( 'Allow user override', 'wppp' ) ); ?>
						<p class="description"><?php  _e( 'Allow users to reactivate back end translation in their profile settings.', 'wppp' ); ?></p>
						<br/>
						<p>
							<?php _e( 'Default user language:', 'wppp' ); ?>&nbsp;
							<label for="user-default-english"><input id="user-default-english" type="radio" <?php $this->e_opt_name( 'dbt_user_default_translated' ); ?> value="false" <?php $this->e_checked( 'dbt_user_default_translated', false ); ?>><?php _e( 'English', 'wppp' ); ?></label>&nbsp;
							<label for="user-default-translated"><input id="user-default-translated" type="radio" <?php $this->e_opt_name( 'dbt_user_default_translated' ); ?> value="true" <?php $this->e_checked( 'dbt_user_default_translated' ); ?>><?php _e( 'Blog language', 'wppp' ); ?></label>
						</p>
						<p class="description"><?php _e( "Default back end language for new and existing users, who haven't updated their profile yet.", 'wppp' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<h3 class="title"><?php _e( 'Improve image handling', 'wppp' ); ?></h3>
		<div>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Dynamic image resizing</th>
					<td>
						<?php $this->e_radio_enable( 'dynimg', 'dynamic_images', !$this->is_dynamic_images_available() ); ?>
						<p class="description">
							If activated intermediate image sizes won't be generated on upload. Instead resizing is done dynamically when an intermediate image is accessed. This improves upload performance and reduces disk usage. <b>Requires pretty permalinks!</b> If you deactive this option after some time of usage you might have to recreate thumbnails using a plugin like Regenerate Thumbnails.
						</p>
						<br/>
						<?php $this->e_checkbox( 'dynimgexif', 'dynamic_images_exif_thumbs', __( 'Use EXIF thumbnail', 'wppp' ), !$this->is_exif_available() ); ?>
						<p class="description">When creating "thumbnail" images try to use the thumbnail contained in EXIF data to create it, if available. As the contained thumbnail is much smaller than the full image this reduces memory consumption and improves performance. But not all image editing tools update the EXIF thumbnail when saving an image. As a result of this, the EXIF thumbnail can look different than the actual image.</p>
						<br/>
						<?php $this->e_checkbox( 'dynimg-save', 'dynamic_images_nosave', __( "Don't save intermediate images", 'wppp' ) ); ?>
						<p class="description">
							By default once created intermediate images are saved to disk and served directly on subsequent requests. Though this already reduces disk space usage you further reduce it by disabling saving, but this will slow down your blog as images get created on each access. Useful e.g. for test environments to reduce disk space usage.
						</p>
						<br/>
						<?php $this->e_checkbox( 'dynimg-cache', 'dynamic_images_cache', __( "Use WP Object Cache for (not saved) intermediate images.", 'wppp' ) ); ?>
						<p class="description">If you aren't using a file based object cache, make sure the cache memory limit isn't too low for this. This is best used in testing environments, not on production sites and is only applied if "<em>Don't save intermediate images</em>" is activated. Created images are cached for 30 minutes.</p>
						<?php $this->do_hint_caching(); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Image quality</th>
					<td>
						<div id="dynimg-quality-slider" style="width:25em; margin-bottom:2em;"></div>
						<p class="description">Quality setting for newly created intermediate images.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Regenerate Thumbnails integration</th>
					<td>
						<?php $this->e_radio_enable( 'dynimgrthook', 'dynamic_images_rthook', !$this->is_regen_thumbs_available() ); ?>
						<p class="description">Activate this option to delete all existing intermediate images using <a href="http://wordpress.org/plugins/regenerate-thumbnails/" target="_blank">Regenerate Thumbnails</a> plugin. To do so, just regenerate thumbnails while this option is activated. This option won't do anything if Regenerate Thumbnails isn't installed.</p>
						<br/>
						<?php $this->e_checkbox( 'dynimg-rtforce', 'dynamic_images_rthook_force', __( 'Force delete of all potential thumbnails', 'wppp' ), !$this->is_regen_thumbs_available() ); ?>
						<p class="description">This option only applies if the Regenerate Thumbnail hook is active. All potential thumbnail files (i.e. those matching the pattern "<em>filename-*x*.ext</em>") will be deleted while regenerating. Use this option to delete old files which aren't referenced from attachment meta data due to earlier thumbnail regeneration. <strong>Use with care as this option might delete files which aren't thumbnails!</strong></p>
					</td>
				</tr>
			</table>
		</div>

<!--		<h3>Selective plugin loading</h3>
		<div>
			<table class="widefat">
				<thead>
					<tr>
						<th>Plugin name</th>
						<th>Front end</th>
						<th>Back end</th>
						<th>AJAX</th>
					</tr>
				</thead>
				<tbody>
				<?php
					/* $plugins = get_option( 'active_plugins' );
					$odd = false;
					foreach ( $plugins as $plugin ) {
						$odd = !$odd;
						$data = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $plugin );
						if ($odd) {
							echo '<tr class="alternate">';
						} else {
							echo '<tr>';
						}
						echo '<td>', $data['Name'],'</td>';
						?>
							<td><input type="checkbox" name="splFronend[]" value="test" /></td>
							<td><input type="checkbox" name="splBackend[]" value="test" /></td>
							<td><input type="checkbox" name="splAjax[]" value="test" /></td>
						</tr>
						<?php
					} */
				?>
				</tbody>
			</table>
		</div>
-->
		<h3 class="title"><?php _e( 'Debugging', 'wppp' ); ?></h3>
		<div>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Debug Panel', 'wppp' ); ?></th>
					<td>
						<?php $this->e_radio_enable( 'debug-panel', 'debug', !class_exists( 'Debug_Bar' ) ); ?>
						<p class="description"><?php _e( 'Enables debugging, requires <a href="http://wordpress.org/plugins/debug-bar/">Debug Bar</a> Plugin.', 'wppp' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
?>