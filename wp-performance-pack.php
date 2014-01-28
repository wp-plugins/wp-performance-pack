<?php
/*
	Plugin Name: WP Performance Pack
	Plugin URI: http://www.bjoernahrens.de
	Description: A collection of performance optimizations for WordPress
	Version: 0.2.4.1
	Author: Bj&ouml;rn Ahrens
	Author URI: http://www.bjoernahrens.de
	License: GPL2 or later
*/ 
	
/*
	Copyright 2014 Björn Ahrens (email : bjoern@ahrens.net) 
	This program is free software; you can redistribute it and/or modify 
	it under the terms of the GNU General Public License, version 2 or 
	later, as published by the Free Software Foundation. This program is 
	distributed in the hope that it will be useful, but WITHOUT ANY 
	WARRANTY; without even the implied warranty of MERCHANTABILITY or 
	FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License 
	for more details. You should have received a copy of the GNU General
	Public License along with this program; if not, write to the Free 
	Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, 
	MA 02110-1301 USA 
*/

if( !class_exists( 'WP_Performance_Pack' ) ) {
	class WP_Performance_Pack { 
		protected static $options_name = 'wppp_option';
		protected static $options_default = array(
			'use_mo_dynamic' => true,
			'use_jit_localize' => false,
			'disable_backend_translation' => false,
			'use_native_gettext' => false,
		);

		public $options = NULL;

		private function load_options () {
			if ( $this->options == NULL ) {
				$this->options = get_option( self::$options_name );
				foreach ( self::$options_default as $key => $value ) {
					if ( !isset( $this->options[$key] ) ) {
						$this->options[$key] = false;
					}
				}
			}
		}

		public function add_page() {
			add_options_page( 'WPPP Options', 'WPPP Options', 'manage_options', 'wppp_options_page', array( $this, 'options_do_page' ) );
		}

		public function admin_init() {
			register_setting( 'wppp_options', self::$options_name, array( $this, 'validate' ) );
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

		public function options_do_page() {
			$this->load_options();
			global $wp_version;
			?>
			<div class="wrap">
				<h2>WP Performance Pack Options</h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'wppp_options' ); ?>
					<table class="form-table">
						<tr valign="top"><th scope="row">Translation related</th>
							<td>
								<input type="checkbox" name="<?php echo self::$options_name;?>[use_mo_dynamic]" value="true" <?php echo $this->options['use_mo_dynamic'] == true ? 'checked="checked"' : '';?>/> Use MO-Dynamic <br/>
								<p class="description">Texts will get translated on demand. Reduces memory consumption and speeds up page load on non englisch WordPress installations. Translation files will be loaded only when needed and only required strings will be translated. The default MO class loads all translations on startup.</p>
								<br/>
								<input type="checkbox" name="<?php echo self::$options_name;?>[use_native_gettext]" value="true" <?php echo ( $this->options['use_native_gettext'] && extension_loaded( 'gettext' ) ) == true ? 'checked="checked" ' : ' '; echo extension_loaded( 'gettext' ) == false ? 'disabled="true"' : ''; ?>/> Use native gettext<br/>
								<p>Gettext extension is <b><?php if ( !extension_loaded( 'gettext' ) ) : ?>not <?php endif; ?>available</b>! <?php if ( extension_loaded( 'gettext' ) ) : ?>(But this doesn't means it will work...)<?php endif; ?></p>
								<p class="description">Use native gettext implementation for translations. The fastest and most memory efficient method for translations. Requires gettext extension to be installed. <b>Native gettext overrides MO-Dynamic if both are enabled.</b></p>
								<br/>
								<input type="checkbox" name="<?php echo self::$options_name;?>[use_jit_localize]" value="true" <?php echo ( $this->options['use_jit_localize'] && in_array( $wp_version, array ( '3.8.1' ) ) ) ? 'checked="checked" ' : ' '; echo !in_array( $wp_version, array( '3.8.1' ) ) ? 'disabled="true"' : '';?>/> Use JIT localize <br/>
								<p><b>As for now only implemented for WordPress version 3.8.1</b> because of differences in wp_default_scripts (which gets overridden by this feature) between versions.</p>
								<p class="description">Just in time localization of scripts. By default WordPress localizes all default scripts at each request. Enabling this option will translate localization string only if needed. This might improve performance even if WordPress is not translated, but has the biggest impact when using MO-Dynamic.</p>
								<br/>
								<input type="checkbox" name="<?php echo self::$options_name;?>[disable_backend_translation]" value="true" <?php echo $this->options['disable_backend_translation'] == true ? 'checked="checked"' : '';?>/> Disable backend translation <br/>
								<p class="description">Disables translation of backend texts. Even using MO-Dynamic translation is still very time consuming. The WordPress Dashboard has much more texts to translate. So disabling backend translation can speed up working with wordpress significantly, if you don't mind working with the english interface. <b>AJAX requests on backend pages will still be translated, as I haven't figured out how to distinguish requests originating backend pages and requests from frontend pages.</b></p>
								<br/>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
					</p>
				</form>
			</div>
			<?php
		}
		
		public function __construct() { 
			// add actions
			add_action( 'activated_plugin', array ( &$this, 'plugin_load_first' ) );

			if ( is_admin() ) {
				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_action( 'admin_menu', array( $this, 'add_page' ) );
			}

			$this->load_options();
			global $wp_version;

			// load modules
			if ( $this->options['use_mo_dynamic'] || ( $this->options['use_native_gettext'] && extension_loaded( 'gettext' ) ) ) {
				include( sprintf( "%s/modules/override-textdomain.php", dirname( __FILE__ ) ) );
			} else if ( $this->options['disable_backend_translation'] ) {
				include( sprintf( "%s/modules/disable-backend.php", dirname( __FILE__ ) ) );
			}

			if ( $this->options['use_jit_localize'] && in_array( $wp_version, array( '3.8.1' ) ) ) {
				include( sprintf( "%s/modules/jit-localize.php", dirname( __FILE__ ) ) );
			}
		}

		/**
		 * Make sure WPPP is loaded as first plugin. Important for e.g. usage of dynamic MOs with all text domains.
		 */
		public static function plugin_load_first() {
			$path = str_replace( str_replace ( '\\', '/', WP_PLUGIN_DIR ) . '/', '', str_replace ( '\\', '/', __FILE__ ) );
			
			if ( $plugins = get_option( 'active_plugins' ) ) {
				if ( $key = array_search( $path, $plugins ) ) {
					array_splice( $plugins, $key, 1 );
					array_unshift( $plugins, $path );
					update_option( 'active_plugins', $plugins );
				}
			}
		}
		
		public static function activate() { 
			update_option( self::$options_name, self::$options_default );
			self::plugin_load_first();
		}
		
		public static function deactivate() { 
			delete_option( self::$options_name );
		}
	}
}

if(class_exists('WP_Performance_Pack')) { 
	// installation and uninstallation hooks 
	register_activation_hook( __FILE__, array( 'WP_Performance_Pack', 'activate' ) ); 
	register_deactivation_hook( __FILE__, array( 'WP_Performance_Pack', 'deactivate' ) ); 
	// instantiate the plugin class 
	global $wp_performance_pack;
	$wp_performance_pack = new WP_Performance_Pack(); 
}

?>