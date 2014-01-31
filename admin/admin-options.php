<?php

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

		private function e_option ( $opt_name ) {
			echo WP_Performance_Pack::$options_name.'['.$opt_name.']';
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
				'content'	=> __('<p>WP Performance Pack is intended to be a collection of performance optimizations for WordPress which don\'t need patching of core files. As of now it features options to improve performance of translated WordPress installations.</p>','wppp'),
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
					<h3><?php _e('Translation related','wppp') ?></h3>
					<table class="form-table">
						<tr valign="top">
							<th scope="row" style="width:15em">
								<?php _e( 'Use MO-Dynamic', 'wppp' ); ?>
							</th>
							<td>
								<label for="mo-dynamic-true"><input id="mo-dynamic-true" type="radio" name="<?php $this->e_option( 'use_mo_dynamic' ); ?>" value="true" <?php echo $this->wppp->options['use_mo_dynamic'] ? 'checked="checked"' : '';?>/><?php _e( 'Enabled', 'wppp' ); ?>&nbsp;</label>
								<label for="mo-dynamic-false"><input id="mo-dynamic-false" type="radio" name="<?php $this->e_option( 'use_mo_dynamic'); ?>" value="false" <?php echo !$this->wppp->options['use_mo_dynamic'] ? 'checked="checked"' : '';?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
								<p class="description"><?php _e( 'Loads translations on demand. Use if native gettext is not available.' ,'wppp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<?php _e( 'Use native gettext', 'wppp' ); ?>
							</th>
							<td>
								<label for="native-gettext-true"><input id="native-gettext-true" type="radio" name="<?php $this->e_option('use_native_gettext'); ?>" value="true" <?php echo ( $this->wppp->options['use_native_gettext'] && $this->wppp->gettext_available ) ? 'checked="checked" ' : ' '; echo !$this->wppp->gettext_available ? 'disabled="true"' : ''; ?>/><?php _e( 'Enabled', 'wppp' ); ?>&nbsp;</label>
								<label for="native-gettext-false"><input id="native-gettext-false" type="radio" name="<?php $this->e_option('use_native_gettext'); ?>" value="false" <?php echo ( !$this->wppp->options['use_native_gettext'] || !$this->wppp->gettext_available ) ? 'checked="checked" ' : ' '; echo !$this->wppp->gettext_available ? 'disabled="true"' : ''; ?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
								<p>
									<?php if ( $this->wppp->gettext_available ) : ?>
										<?php _e( 'Gettext extension is <b>available</b>. (But this doesn\'t means it will work...)', 'wppp' ); ?>
									<?php else : ?>
										<?php _e( 'Gettext extension is <b>not available</b>!', 'wppp' ); ?>
									<?php endif; ?>
								</p>
								<p class="description"><?php _e( 'Use native gettext implementation for translations.', 'wppp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<?php _e( 'Use JIT localize', 'wppp' ); ?>
							</th>
							<td>
								<label for="jit-true"><input id="jit-true" type="radio" name="<?php $this->e_option('use_jit_localize'); ?>" value="true" <?php echo ( $this->wppp->options['use_jit_localize'] && $this->wppp->jit_available ) ? 'checked="checked" ' : ' '; echo !$this->wppp->jit_available ? 'disabled="true"' : '';?>/><?php _e( 'Enabled', 'wppp' ); ?>&nbsp;</label>
								<label for="jit-false"><input id="jit-false" type="radio" name="<?php $this->e_option('use_jit_localize'); ?>" value="false" <?php echo ( !$this->wppp->options['use_jit_localize'] || !$this->wppp->jit_available ) ? 'checked="checked" ' : ' '; echo !$this->wppp->jit_available ? 'disabled="true"' : '';?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
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
								<label for="backend-trans-true"><input id="backend-trans-true" type="radio" name="<?php $this->e_option('disable_backend_translation'); ?>" value="true" <?php echo $this->wppp->options['disable_backend_translation'] == true ? 'checked="checked"' : '';?>/><?php _e( 'Enabled', 'wppp' );?>&nbsp;</label>
								<label for="backend-trans-false"><input id="backend-trans-false" type="radio" name="<?php $this->e_option('disable_backend_translation'); ?>" value="false" <?php echo !$this->wppp->options['disable_backend_translation'] == true ? 'checked="checked"' : '';?>/><?php _e( 'Disabled', 'wppp' ); ?></label>
								<p class="description"><?php _e('Disables translation of backend texts.', 'wppp' ); ?></p>
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