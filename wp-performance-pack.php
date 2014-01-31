<?php
/*
	Plugin Name: WP Performance Pack
	Plugin URI: http://www.bjoernahrens.de
	Description: A collection of performance optimizations for WordPress
	Version: 0.4
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
		private $admin_opts = NULL;
		
		public static $options_name = 'wppp_option';
		public static $options_default = array(
			'use_mo_dynamic' => true,
			'use_jit_localize' => false,
			'disable_backend_translation' => false,
			'use_native_gettext' => false,
		);

		public $jit_available = false;
		public $gettext_available = false;
		public $is_network = false;
		public $options = NULL;

		private function load_options () {
			if ( $this->options == NULL ) {
				if ( $this->is_network ) {
					$this->options = get_site_option( self::$options_name );
				} else {
					$this->options = get_option( self::$options_name );
				}
				foreach ( self::$options_default as $key => $value ) {
					if ( !isset( $this->options[$key] ) ) {
						$this->options[$key] = false;
					}
				}
			}
		}

		public function __construct() { 
			// initialize fields
			global $wp_version;
			$this->jit_available = in_array( $wp_version, array ( '3.8.1' ) );
			$this->gettext_available = extension_loaded( 'gettext' );
			if ( !function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			$this->is_network = is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) );
			$this->load_options();

			// add actions
			add_action( 'activated_plugin', array ( &$this, 'plugin_load_first' ) );

			if ( is_admin() ) {
				load_plugin_textdomain( 'wppp', false, dirname(plugin_basename(__FILE__)) . '/lang');
				include( sprintf( "%s/admin/admin-options.php", dirname( __FILE__ ) ) );
				$this->admin_opts = new WPPP_Admin ($this);
			}

			// load modules
			if ( $this->options['use_mo_dynamic'] || ( $this->options['use_native_gettext'] && $this->gettext_available ) ) {
				include( sprintf( "%s/modules/override-textdomain.php", dirname( __FILE__ ) ) );
			} else if ( $this->options['disable_backend_translation'] ) {
				include( sprintf( "%s/modules/disable-backend.php", dirname( __FILE__ ) ) );
			}

			if ( $this->options['use_jit_localize'] && $this->jit_available ) {
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
			// if is active in network of multisite
			if ( is_multisite() && isset( $_GET['networkwide'] ) && 1 == $_GET['networkwide'] ) {
				add_site_option( self::$options_name, self::$options_default );
			} else {
				add_option( self::$options_name, self::$options_default );
			}
			self::plugin_load_first();
		}
		
		public static function deactivate() { 
			if ( is_multisite() && isset( $_GET['networkwide'] ) && 1 == $_GET['networkwide'] ) {
				delete_site_option( self::$options_name );
			} else {
				delete_option( self::$options_name );
			}
		}
	}
}

if ( class_exists( 'WP_Performance_Pack' ) ) { 
	// installation and uninstallation hooks 
	register_activation_hook( __FILE__, array( 'WP_Performance_Pack', 'activate' ) ); 
	register_deactivation_hook( __FILE__, array( 'WP_Performance_Pack', 'deactivate' ) ); 
	// instantiate the plugin class 
	global $wp_performance_pack;
	$wp_performance_pack = new WP_Performance_Pack(); 
}

?>