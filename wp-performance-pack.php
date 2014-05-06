<?php
/*
	Plugin Name: WP Performance Pack
	Plugin URI: http://wordpress.org/plugins/wp-performance-pack
	Description: A collection of performance optimizations for WordPress. As of now it features options to improve performance of translated WordPress installations. 
	Version: 1.6.3
	Text Domain: wppp
	Domain Path: /languages/
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
	include ( sprintf( "%s/common.php", dirname( __FILE__ ) ) );

	class WP_Performance_Pack extends WP_Performance_Pack_Commons {
		const cache_group = 'wppp1.0'; 	// WPPP cache group name = wppp + version of last change to cache. 
										// This way no cache conflicts occur while old cache entries just expire.

		public static $options_name = 'wppp_option';
		public static $options_default = NULL;
		public static $jit_versions = array(
			'3.8.1',
			'3.8.2',
			'3.8.3',
			'3.9'
		);

		private $admin_opts = NULL;
		private $modules = array();

		public $plugin_dir = NULL;
		public $dbg_textdomains = array ();

		function is_jit_available () {
			global $wp_version;
			return in_array( $wp_version, self::$jit_versions );
		}

		public function __construct() {
			spl_autoload_register( array( $this, 'wppp_autoloader' ) );

			// initialize fields
			global $wp_version;
			$this->plugin_dir = dirname(plugin_basename(__FILE__));
			if ( !function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			$this->is_network = is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) );

			$this->check_update();

			$this->load_options();

			// add actions
			add_action( 'activated_plugin', array ( 'WP_Performance_Pack', 'plugin_load_first' ) );
			add_action( 'init', array ( $this, 'init' ) );
			add_filter( 'update_option_' . self::$options_name, array( $this, 'do_options_changed' ), 10, 2 );

			// load early modules
			if ( $this->options['use_mo_dynamic'] 
				|| $this->options['use_native_gettext']
				|| $this->options['disable_backend_translation'] ) {
				
				global $l10n;
				$l10n['WPPP_NOOP'] = new NOOP_Translations;
				include( sprintf( "%s/modules/override-textdomain.php", dirname( __FILE__ ) ) );
				add_filter( 'override_load_textdomain', 'wppp_load_textdomain_override', 0, 3 );
			}

			if ( $this->is_jit_available() && $this->options['use_jit_localize'] ) {
				global $wp_scripts;
				if ( !isset( $wp_scripts ) && !defined('IFRAME_REQUEST') ) {
					include( sprintf( "%s/modules/jit-by-version/wp".$wp_version.".php", dirname( __FILE__ ) ) );
					remove_action( 'wp_default_scripts', 'wp_default_scripts' );
					add_action( 'wp_default_scripts', 'wp_jit_default_scripts' );
					$wp_scripts = new WPPP_Scripts_Override();
				}
			}
		}

		function do_options_changed( $old_value, $new_value )
		{
			// flush rewrite rules if dynamic images setting changed
			if ( $old_value['dynamic_images'] !== $new_value['dynamic_images'] ) {
				WPPP_Dynamic_Images::flush_rewrite_rules( $new_value['dynamic_images'] );
			}
		}

		public function init () {
			//wp_enqueue_script( 'wppp-media-manager-sizes', plugins_url( '/js/test.js', __FILE__ ), array( 'media-editor' ) );

			// execute "late" updates
			foreach ( $this->late_updates as $updatefunc ) {
				$updatefunc['func']();
			}

			if ( $this->options['debug'] ) {
				add_filter( 'debug_bar_panels', array ( $this, 'add_debug_bar_wppp' ), 10 );
			}

			if ( $this->options['dynamic_images'] && !is_multisite() ) {
				$this->modules[] = new WPPP_Dynamic_Images ();
			}

			// admin pages
			if ( is_admin() ) {
				if ( current_user_can ( 'manage_options' ) ) {
					include( sprintf( "%s/admin/class.wppp-admin-admin.php", dirname( __FILE__ ) ) );
					$this->admin_opts = new WPPP_Admin_Admin ($this);
				} else if ( $this->options['disable_backend_translation'] && $this->options['dbt_allow_user_override']) {
					include( sprintf( "%s/admin/class.wppp-admin-user.php", dirname( __FILE__ ) ) );
					$this->admin_opts = new WPPP_Admin_User ($this);
				}
			}
		}

		function add_debug_bar_wppp ( $panels ) {
			if ( class_exists( 'Debug_Bar' ) ) {
				include( sprintf( "%s/admin/class.debug-bar-wppp.php", dirname( __FILE__ ) ) );
				$panel = new Debug_Bar_WPPP ();
				$panel->textdomains = &$this->dbg_textdomains;
				$panel->plugin_base = plugin_basename( __FILE__ );
				$panels[] = $panel;
				return $panels;
			}
		}

		/**
		 * Make sure WPPP is loaded as first plugin. Important for e.g. usage of dynamic MOs with all text domains.
		 */
		public static function plugin_load_first() {
			$path = plugin_basename( __FILE__ );

			if ( $plugins = get_option( 'active_plugins' ) ) {
				if ( 0 != ( $key = array_search( $path, $plugins ) ) ) {
					array_splice( $plugins, $key, 1 );
					array_unshift( $plugins, $path );
					update_option( 'active_plugins', $plugins );
				}
			}
		}

		public function activate() { 
			if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) { 
				deactivate_plugins( basename(__FILE__) ); // Deactivate self - does that really work at this stage?
				wp_die( 'WP Performance pack requries PHP version >= 5.3' );
			}

			// if is active in network of multisite
			if ( is_multisite() && isset( $_GET['networkwide'] ) && 1 == $_GET['networkwide'] ) {
				add_site_option( self::$options_name, self::$options_default );
			} else {
				add_option( self::$options_name, self::$options_default );
			}
			self::plugin_load_first();
		}

		public function deactivate() {
			if ( $this->options['dynamic_images'] ) {
				// Delete rewrite rules from htaccess
				WPPP_Dynamic_Images::flush_rewrite_rules( false ); // hopefully WPPP_Dynamic_images didn't get initialized elsewhere. Not shure at which point deactivation occurs, but I think it's save to assume DynImg didn't get initialized so rewrite rules didn't get set.
			}

			if ( is_multisite() && isset( $_GET['networkwide'] ) && 1 == $_GET['networkwide'] ) {
				delete_site_option( self::$options_name );
			} else {
				delete_option( self::$options_name );
			}
			delete_option( 'wppp_dynimg_sizes' );
			delete_option( 'wppp_version' );
		}

		function wppp_autoloader ( $class ) {
			$class = strtolower( $class );
			if ( strncmp( $class, 'wppp_', 5 ) === 0 || $class == 'labelsobject' ) {
				include( sprintf( "%s/classes/class.$class.php", dirname( __FILE__ ) ) );
			}
		}

		private $late_updates = array();

		function check_update () {
			$installed = $this->get_option( 'wppp_version' );
			if ( version_compare( $installed, self::wppp_version, '!=' ) ) {
				// if installed version differs from version saved in options update
				// it is assumed that the options-version is always less or equal to the installed version
				if ( $installed === false || empty( $installed ) ) {
					// pre 1.6.3 version didn't have the wppp_version option

					// server-dynamic-images.php location has changed, so update rewrite-rules
					$opts = $this->get_option( self::$options_name );
					if ( isset( $opts['dynamic_images'] ) && $opts['dynamic_images'] ) {
						$this->late_updates[] = array( 'func' => function () {
							WPPP_Dynamic_Images::flush_rewrite_rules( true );
						} );
					}
					$installed = '1.6.3';
				}

				$this->update_option ( 'wppp_version', self::wppp_version );
			}
		}
	} 
}

if ( class_exists( 'WP_Performance_Pack' ) ) { 
	// instantiate the plugin
	global $wp_performance_pack;
	$wp_performance_pack = new WP_Performance_Pack(); 
	register_activation_hook( __FILE__, array( $wp_performance_pack, 'activate' ) ); 
	register_deactivation_hook( __FILE__, array( $wp_performance_pack, 'deactivate' ) ); 
}

?>