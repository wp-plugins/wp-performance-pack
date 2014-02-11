<?php
/**
 * Admin settings page
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.1
 */
 
if ( !class_exists( 'WPPP_Admin ' ) ) {
	class WPPP_Admin {
		private $wppp = NULL;
	
		public function __construct($wppp_parent) {
			$this->wppp = $wppp_parent;

			add_action( 'admin_init', array( $this, 'admin_init' ) );
			if ( $this->wppp->is_network ) {
				add_action( 'network_admin_menu', array( $this, 'add_network_page' ) );
			} else {
				add_action( 'admin_menu', array( $this, 'add_page' ) );
			}
		}

		private function e_opt_name ( $opt_name ) {
			echo 'name="'.WP_Performance_Pack::$options_name.'['.$opt_name.']"';
		}

		private function e_checked ( $opt_name, $value = true ) {
			echo $this->wppp->options[$opt_name] === $value ? 'checked="checked" ' : ' ';
		}

		private function e_checked_or ( $opt_name, $value = true, $or_val = true ) {
			echo $this->wppp->options[$opt_name] === $value || $or_val ? 'checked="checked" ' : ' ';
		}

		private function e_checked_and ( $opt_name, $value = true, $and_val = true ) {
			echo $this->wppp->options[$opt_name] === $value && $and_val ? 'checked="checked" ' : ' ';
		}

		public function add_page() {
			global $wppp_options_hook;
			$wppp_options_hook = add_options_page( __('WP Performance Pack','wppp'), __('Performance Pack','wppp'), 'manage_options', 'wppp_options_page', array( $this, 'options_do_page' ) );
			add_action('load-'.$wppp_options_hook, array ( $this, 'add_help_tab' ) );
		}

		public function add_network_page() {
			global $wppp_options_hook;
			$wppp_options_hook = add_submenu_page( 'settings.php', __('WP Performance Pack','wppp'), __('Performance Pack','wppp'), 'manage_options', 'wppp_options_page', array( $this, 'options_do_page' ) );
			add_action('load-'.$wppp_options_hook, array ( $this, 'add_help_tab' ) );
		}

		public function admin_init() {
			register_setting( 'wppp_options', WP_Performance_Pack::$options_name, array( $this, 'validate' ) );
			load_plugin_textdomain( 'wppp', false, $this->wppp->plugin_dir . '/lang');

			if ( $this->wppp->options['disable_backend_translation'] && $this->wppp->options['dbt_allow_user_override'] ) {
				add_action( 'show_user_profile', array ( $this, 'show_wppp_user_settings' ) );
				add_action( 'edit_user_profile', array ( $this, 'show_wppp_user_settings' ) );
				add_action( 'personal_options_update', array ( $this, 'save_wppp_user_settings' ) );
				add_action( 'edit_user_profile_update', array ( $this, 'save_wppp_user_settings' ) );
			}
		}

		function save_wppp_user_settings ( $user_id ) {
			if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }

			if ( isset( $_POST['wppp_translate_backend'] ) && $_POST['wppp_translate_backend'] === 'true' ) {
				$res = update_user_option( $user_id, 'wppp_translate_backend', 'true' );
			} else {
				delete_user_option ( $user_id, 'wppp_translate_backend' );
			}
		}

		public function show_wppp_user_settings ($user) {
		?>
		<h3><?php _e( 'Dashboard language', 'wppp' ); ?></h3>

			<table class="form-table">
				<tr>
					<th scope="row"><?php _e( 'Translate Dashboard', 'wppp' ); ?></th>
					<td>
						<label for="wppp_translate_backend">
							<input type="checkbox" name="wppp_translate_backend" id="wppp_translate_backend" value="true" <?php echo get_user_option( 'wppp_translate_backend', $user->ID ) === 'true' ? 'checked="true"' : ''; ?> /> 
							<?php _e( 'Activate to translate the Dashboard into the blog language.', 'wppp' ); ?>
						</label>
					</td>
				</tr>
			</table>
		<?php
		}

		public function validate( $input ) {
			$output = array();
			if ( isset( $input ) && is_array( $input ) ) {
				foreach( $input as $key => $val ) {
					if ( isset ( $input[$key] ) ) {
						$output[$key] = ( $input[$key] == 'true' ? true : false );
					}
				}
			}
			return $output;
		}

		function add_help_tab () {
			$screen = get_current_screen();
			
			// Add my_help_tab if current screen is My Admin Page
			$screen->add_help_tab( array(
				'id'	=> 'wppp_help_general',
				'title'	=> __('Overview'),
				'content'	=> __('<p>WP Performance Pack is intended to be a collection of performance optimizations for WordPress which don\'t need patching of core files. As of now it features options to improve performance of translated WordPress installations.</p><h4>Optimal settings</h4><ul><li>Use native gettext if available (for details on native gettext support activate <em>Debug Panel</em>).</li><li>MO-Dynamic should always work and is faster than default WordPress translations, but slower than native gettext.</li><li>Enable JIT localize if available.</li></ul>','wppp'),
			) );

			$screen->add_help_tab( array(
				'id'	=> 'wppp_help_modynamic',
				'title'	=> '- '.__('MO-Dynamic','wppp'),
				'content'	=> __('<p><strong>Dynamic loading of translation files, only loading and translating used strings.</strong></p><p>Improves performance and reduces memory consumption. The default WordPress <em>MO</em> implementation loads the complete MO files (e.g. when loaded via <code>load_textdomain</code>) into memory. As a result translation of individual strings is quite fast, but loading times and memory consumption are high. Most of the time only a few strings from a mo file are required within a single page request. Activating translation almost doubles execution time in a typical WordPress installation.</p><p>WPPP uses MO-Dynamic, a complete rewrite of the <em>MO</em> implementation, to speed up translations. .mo files are only loaded if needed. On installations with many translated plugins this alone can dramatically reduce execution time. It doesn\'t load the complete translations into memory, only required ones. This on demand translation is more expensive than translations on fully loaded .mo files but the performance gain by not loading and parsing the complete file outweighs this.</p>','wppp'),
			) );

			$screen->add_help_tab( array(
				'id'	=> 'wppp_help_gettext',
				'title'	=> '- '.__('Native gettext','wppp'),
				'content'	=> __('<p><strong>Use of native gettext if available</strong></p><p>There is probably no faster way for translations than using the native gettext implementation. This requires the <code>php_gettext</code> extension to be installed on the server. This implementation used is based on Bernd Holzmuellers <a href="http://oss.tiggerswelt.net/wordpress/3.3.1/">Translate_GetText_Native</a> implementation.</p><p>For now WPPP only checks if the gettext extension is available, which might not suffice to use native gettext. Further checks will follow.</p><p><em>Native gettext overrides MO-Dynamic if both are enabled!</em></p>','wppp'),
			) );

			$screen->add_help_tab( array(
				'id'	=> 'wppp_help_jit',
				'title'	=> '- ' . __('JIT localize','wppp'),
				'content'	=> __('<p><strong>Just in time localization</strong></p><p>Just in time localization of scripts. By default WordPress localizes all default scripts at each request. Enabling this option will translate localization string only if needed. This might improve performance even if WordPress is not translated, but has the biggest impact when using MO-Dynamic.</p><p><em>As for now only implemented for WordPress version 3.8.1 because of differences in wp_default_scripts (which gets overridden by this feature) between versions.</em></p>','wppp'),
			) );
			
			$screen->add_help_tab( array(
				'id'	=> 'wppp_help_backend',
				'title'	=> '- ' . __('Disable backend translation','wppp'),
				'content'	=> __('<p><strong>Disable backend translation while maintaining frontend translations.</strong></p><p>Speed up the backend by disabling dashboard-translations. Useful if you don\'t mind using an english backend.</p><p><em>AJAX requests on backend pages will still be translated, as I haven\'t figured out how to distinguish requests originating backend pages and requests from frontend pages.</em></p>','wppp'),
			) );
			
			$screen->set_help_sidebar(
                              '<p><a href="http://wordpress.org/support/plugin/wp-performance-pack" target="_blank">'.__('Support Forums').'</a></p>'
                             );
		}
		
		public function options_do_page() {
			if ( $this->wppp->is_network && isset( $_GET['action'] ) && $_GET['action'] === 'update_wppp' ) {
				$this->update_wppp_settings();
			}
			
			?>
			<div class="wrap">
				<h2><?php _e( 'WP Performance Pack - Settings', 'wppp' ); ?></h2>
				<?php if ( $this->wppp->is_network ) : ?>
					<form action="<?php echo network_admin_url('settings.php?page=wppp_options_page&action=update_wppp'); ?>" method="post">
						<?php wp_nonce_field( 'update_wppp', 'wppp_nonce' ); ?>
				<?php else : ?>
					<form method="post" action="options.php">
				<?php endif; ?>
					<?php settings_fields( 'wppp_options' ); ?>
					<h3><?php _e( 'General', 'wppp' ); ?></h3>
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
					
					<h3><?php _e('Translation related','wppp') ?></h3>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								<?php _e( 'Use native gettext', 'wppp' ); ?>
							</th>
							<td>
								<label for="native-gettext-true"><input id="native-gettext-true" type="radio" <?php $this->e_opt_name('use_native_gettext'); ?> value="true" <?php $this->e_checked_and( 'use_native_gettext', true, $this->wppp->gettext_available ); echo !$this->wppp->gettext_available ? 'disabled="true"' : ''; ?>/><?php _e( 'Enabled', 'wppp' ); ?>&nbsp;</label>
								<label for="native-gettext-false"><input id="native-gettext-false" type="radio" <?php $this->e_opt_name('use_native_gettext'); ?> value="false" <?php $this->e_checked_or( 'use_native_gettext', false, !$this->wppp->gettext_available ); echo !$this->wppp->gettext_available ? 'disabled="true"' : ''; ?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
								<p>
									<?php if ( $this->wppp->gettext_available ) : ?>
										<?php _e( 'Gettext extension is <b>available</b>. (For further details on native gettext support activate <em>Debug Panel</em>)', 'wppp' ); ?>
									<?php else : ?>
										<?php _e( 'Gettext extension is <b>not available</b>!', 'wppp' ); ?>
									<?php endif; ?>
								</p>
								<p class="description"><?php _e( 'Use native gettext implementation for translations.', 'wppp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" style="width:15em">
								<?php _e( 'Use MO-Dynamic', 'wppp' ); ?>
							</th>
							<td>
								<label for="mo-dynamic-true"><input id="mo-dynamic-true" type="radio" <?php $this->e_opt_name( 'use_mo_dynamic' ); ?> value="true" <?php $this->e_checked( 'use_mo_dynamic' ); ?>/><?php _e( 'Enabled', 'wppp' ); ?>&nbsp;</label>
								<label for="mo-dynamic-false"><input id="mo-dynamic-false" type="radio" <?php $this->e_opt_name( 'use_mo_dynamic'); ?> value="false" <?php $this->e_checked ( 'use_mo_dynamic', false );?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
								<p class="description"><?php _e( 'Loads translations on demand. Use if native gettext is not available.' ,'wppp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<?php _e( 'Use JIT localize', 'wppp' ); ?>
							</th>
							<td>
								<label for="jit-true"><input id="jit-true" type="radio" <?php $this->e_opt_name('use_jit_localize'); ?> value="true" <?php $this->e_checked_and( 'use_jit_localize', true, $this->wppp->jit_available ); echo !$this->wppp->jit_available ? 'disabled="true"' : '';?>/><?php _e( 'Enabled', 'wppp' ); ?>&nbsp;</label>
								<label for="jit-false"><input id="jit-false" type="radio" <?php $this->e_opt_name('use_jit_localize'); ?> value="false" <?php $this->e_checked_or( 'use_jit_localize', false, !$this->wppp->jit_available ); echo !$this->wppp->jit_available ? 'disabled="true"' : '';?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
								<?php if ( !$this->wppp->jit_available ) : ?>
								<p><strong><?php _e( 'As for now only available for WordPress version 3.8.1!', 'wppp' ); ?></strong></p>
								<?php endif; ?>
								<p class="description"><?php _e( 'Just in time localization of scripts.', 'wppp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<?php _e( 'Disable backend translation', 'wppp' ); ?>
							</td>
							<td>
								<label for="backend-trans-true"><input id="backend-trans-true" type="radio" <?php $this->e_opt_name('disable_backend_translation'); ?> value="true" <?php $this->e_checked( 'disable_backend_translation' ); ?>/><?php _e( 'Enabled', 'wppp' );?>&nbsp;</label>
								<label for="backend-trans-false"><input id="backend-trans-false" type="radio" <?php $this->e_opt_name('disable_backend_translation'); ?> value="false" <?php $this->e_checked( 'disable_backend_translation', false); ?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
								<p class="description"><?php _e('Disables translation of backend texts.', 'wppp' ); ?></p>
								<br/>
								<label for="allow-user-override"><input id="allow-user-override" type="checkbox" <?php $this->e_opt_name( 'dbt_allow_user_override'); ?> value="true" <?php $this->e_checked( 'dbt_allow_user_override' ); ?> /><?php _e( 'Allow user override', 'wppp' ); ?></label>
								<p class="description"><?php  _e( 'Allow users to reactive backend translation in their profile settings.', 'wppp' ); ?></p>
							</td>
						</tr>
					</table>

					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}
		
		function update_wppp_settings () {
			if ( current_user_can( 'manage_network_options' ) ) {
				check_admin_referer( 'update_wppp', 'wppp_nonce' );
				// process your fields from $_POST here and update_site_option
				$input = array();
				foreach ( WP_Performance_Pack::$options_default as $key => $value ) {
					if ( isset( $_POST['wppp_option'][$key] ) ) {
						$input[$key] = sanitize_text_field( $_POST['wppp_option'][$key] );
					}
				}
				$input = $this->validate( $input );
				foreach ( WP_Performance_Pack::$options_default as $key => $value ) {
					if ( !isset( $input[$key] ) ) {
						$this->wppp->options[$key] = false;
					} else {
						$this->wppp->options[$key] = $input[$key];
					}
				}
				update_site_option( WP_Performance_Pack::$options_name, $this->wppp->options );
			}
		}
	}
}
?>