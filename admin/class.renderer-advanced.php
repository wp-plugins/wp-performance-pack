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
		wp_register_style( 'wppp-admin-styles', plugin_dir_url( __FILE__ ) . 'css/styles.css' );
		wp_enqueue_style( 'wppp-admin-styles' );
		wp_register_script( 'wppp-admin-script', plugin_dir_url( __FILE__ ) . 'js/wppp_advanced.js', array ( 'jquery-ui-accordion' ), false, true );
		wp_enqueue_script( 'wppp-admin-script' );
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

	function render_options () {
		?>
		<h3><?php _e( 'Improve translation performance', 'wppp' ); ?></h3>
		<div>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Use gettext', 'wppp' ); ?></th>
					<td>
						<label for="native-gettext-true"><input id="native-gettext-true" type="radio" <?php $this->e_opt_name('use_native_gettext'); ?> value="true" <?php $this->e_checked_and( 'use_native_gettext', true, $this->is_native_gettext_available() ); echo $this->is_native_gettext_available() != 0 ? 'disabled="true"' : ''; ?>/><?php _e( 'Enabled', 'wppp' ); ?></label>&nbsp;
						<label for="native-gettext-false"><input id="native-gettext-false" type="radio" <?php $this->e_opt_name('use_native_gettext'); ?> value="false" <?php $this->e_checked_or( 'use_native_gettext', false, $this->is_native_gettext_available() != 0 ); echo $this->is_native_gettext_available() != 0 ? 'disabled="true"' : ''; ?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
						<p class="description"><?php _e( 'Use php gettext extension for translations. This is in most cases the fastest way to translate your blog.', 'wppp' ); ?></p>
						<?php $this->do_hint_gettext( true ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="width:15em"><?php _e( 'Use alternative MO reader', 'wppp' ); ?></th>
					<td>
						<label for="mo-dynamic-true"><input id="mo-dynamic-true" type="radio" <?php $this->e_opt_name( 'use_mo_dynamic' ); ?> value="true" <?php $this->e_checked( 'use_mo_dynamic' ); ?>/><?php _e( 'Enabled', 'wppp' ); ?></label>&nbsp;
						<label for="mo-dynamic-false"><input id="mo-dynamic-false" type="radio" <?php $this->e_opt_name( 'use_mo_dynamic'); ?> value="false" <?php $this->e_checked ( 'use_mo_dynamic', false );?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
						<p class="description"><?php _e( 'Alternative MO reader using on demand translation and loading of translation files. Faster and less memory intense than the default WordPress implementation.' ,'wppp' ); ?></p>
						<br/>
						<label for="mo-caching"><input id="mo-caching" type="checkbox" <?php $this->e_opt_name( 'mo_caching' ); ?> value="true" <?php $this->e_checked( 'mo_caching' ); ?>/><?php _e( 'Use caching', 'wppp' ); ?></label>
						<p class="description"><?php _e( 'Cache translations using WordPress Object Cache API', 'wppp' ); ?></p>
						<?php $this->do_hint_mo_cache(); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php _e( 'Use JIT localize', 'wppp' ); ?>
					</th>
					<td>
						<label for="jit-true"><input id="jit-true" type="radio" <?php $this->e_opt_name('use_jit_localize'); ?> value="true" <?php $this->e_checked_and( 'use_jit_localize', true, $this->is_jit_available() ); echo !$this->is_jit_available() ? 'disabled="true"' : '';?>/><?php _e( 'Enabled', 'wppp' ); ?></label>&nbsp;
						<label for="jit-false"><input id="jit-false" type="radio" <?php $this->e_opt_name('use_jit_localize'); ?> value="false" <?php $this->e_checked_or( 'use_jit_localize', false, !$this->is_jit_available() ); echo !$this->is_jit_available() ? 'disabled="true"' : '';?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
						<p class="description"><?php _e( 'Just in time localization of scripts.', 'wppp' ); ?></p>
						<?php $this->do_hint_jit( true ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php _e( 'Disable back end translation', 'wppp' ); ?>
					</th>
					<td>
						<label for="backend-trans-true"><input id="backend-trans-true" type="radio" <?php $this->e_opt_name('disable_backend_translation'); ?> value="true" <?php $this->e_checked( 'disable_backend_translation' ); ?>/><?php _e( 'Enabled', 'wppp' );?></label>&nbsp;
						<label for="backend-trans-false"><input id="backend-trans-false" type="radio" <?php $this->e_opt_name('disable_backend_translation'); ?> value="false" <?php $this->e_checked( 'disable_backend_translation', false); ?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
						<p class="description"><?php _e('Disables translation of back end texts.', 'wppp' ); ?></p>
						<br/>
						<label for="allow-user-override"><input id="allow-user-override" type="checkbox" <?php $this->e_opt_name( 'dbt_allow_user_override'); ?> value="true" <?php $this->e_checked( 'dbt_allow_user_override' ); ?> /><?php _e( 'Allow user override', 'wppp' ); ?></label>
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
		<h3><?php _e( 'Debugging', 'wppp' ); ?></h3>
		<div>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Debug Panel', 'wppp' ); ?></th>
					<td>
						<label for="debug-panel-true"><input id="debug-panel-true" type="radio" <?php $this->e_opt_name( 'debug' ); ?> value="true" <?php $this->e_checked( 'debug' ); echo class_exists( 'Debug_Bar' ) ? '' : 'disabled="true"'; ?> /><?php _e ( 'Enabled', 'wppp' ); ?>&nbsp;</label>
						<label for="debug-panel-false"><input id="debug-panel-false" type="radio" <?php $this->e_opt_name( 'debug' ); ?> value="false" <?php $this->e_checked( 'debug', false ); echo class_exists( 'Debug_Bar' ) ? '' : 'disabled="true"'; ?> /><?php _e ( 'Disabled', 'wppp' ); ?>&nbsp;</label>
						<p class="description"><?php _e( 'Enables debugging, requires <a href="http://wordpress.org/plugins/debug-bar/">Debug Bar</a> Plugin.', 'wppp' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
?>