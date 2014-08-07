<?php

abstract class WPPP_Module_Skeleton {
	protected $wppp = NULL;
	public abstract function get_default_options ();
	public abstract function is_active ();
	public abstract function spawn_body ();
	// validate options
	// input contains actual input
	// output contains the output so far
	// unset processed options in input
	public abstract function validate_options( &$input, $output );

	public function __construct ( $parent ) { $this->wppp = $parent; }
	// initializations done at WPPP construction
	public function early_init () {}
	// initializations done at init action
	public function init () {}
	// initializations done at admin_init action
	public function admin_init () {}
}

class WPPP_CDN_Support_Skeleton extends WPPP_Module_Skeleton {
	public static $options_default = array(
		'cdn' => false,
		'cdnurl' => '',
		'cdn_images' => 'both',
		'dyn_links' => false,
	);

	public function get_default_options () { return static::$options_default; }

	function is_active () { 
		// always load cdn support for dynamic links - to keep once substituted urls working even if dyn_links is disabled
		return $this->wppp->options['cdn'] || $this->wppp->options['dyn_links'];
	}

	function spawn_body () {
		return new WPPP_CDN_Support ( $this->wppp );
	}

	function validate_options ( &$input, $output ) {
		// option: cdn
		if ( isset( $input['cdn'] ) ) {
			$value = trim( sanitize_text_field( $input['cdn'] ) );
			unset( $input['cdn'] );
		} else {
			$value = '';
		}
		switch ( $value ) {
			case 'coralcdn'		:
			case 'maxcdn' 		:
			case 'customcdn'	: $output['cdn'] = $value;
								break;
			default				: $output['cdn'] = false;
								break;
		}

		// option: cdnurl
		if ( isset( $input['cdnurl'] ) ) {
			$value = trim( sanitize_text_field( $input['cdnurl'] ) );
			unset( $input['cdnurl'] );
		} else {
			$value = '';
		}
		if ( !empty( $value ) ) {
			$scheme = parse_url( $value, PHP_URL_SCHEME );
			if ( empty( $scheme ) ) {
				$value = 'http://' . $value;
			}
		}
		$output['cdnurl'] = $value;

		// option: cdn_images
		if ( isset ( $input['cdn_images'] ) ) {
			$value = trim( sanitize_text_field( $input['cdn_images'] ) );
			unset( $input['cdn_images'] );
		} else {
			$value = '';
		}
		switch ( $value ) {
			case 'front'	:
			case 'back'		: $output['cdn_images'] = $value;
							break;
			default			: $output['cdn_images'] = 'both';
							break;
		}

		if ( isset( $input['dyn_links'] ) ) {
			$output['dyn_links'] = ( $input['dyn_links'] == 'true' ? true : false );
			unset( $input['dyn_links'] );
		} else {
			$output['dyn_links'] = static::$options_default['dyn_links'];
		}

		// postprocessing of values
		if ( $output['cdn'] !== 'customcdn' 
			&& $output['cdn'] !== 'maxcdn' )  {
			$output['cdnurl'] = '';
		}

		delete_transient( 'wppp_cdntest' ); // cdn settings might have changed, so delete last test result
		return $output;
	}
}

class WPPP_Dynamic_Images_Skeleton extends WPPP_Module_Skeleton {
	public static $options_default = array(
		'dynamic_images' => false,
		'dynamic_images_nosave' => false,
		'dynamic_images_cache' => false,
		'dynamic_images_rthook' => false,
		'dynamic_images_rthook_force' => false,
		'dynamic_images_exif_thumbs' => false,
	);

	public function get_default_options () { return static::$options_default; }

	public function is_active () { 
		return $this->wppp->options['dynamic_images'] === true;
	}

	public function spawn_body () {
		return new WPPP_Dynamic_Images ( $this->wppp );
	}

	public function validate_options ( &$input, $output ) {
		$defopts = $this->get_default_options();
		foreach ( $defopts as $key => $value ) {
			if ( isset( $input[$key] ) ) {
				$output[$key] = ( $input[$key] == 'true' ? true : false );
				unset( $input[$key] );
			} else {
				$output[$key] = $value;
			}
		}
		return $output;
	}
}

class WP_Performance_Pack {
	const cache_group = 'wppp1.0'; 	// WPPP cache group name = wppp + version of last change to cache. 
									// This way no cache conflicts occur while old cache entries just expire.
	const wppp_version = '1.8.1';
	const wppp_options_name = 'wppp_option';

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
		'dynimg_quality' => 80,
	);
	private $available_modules = array(
		'WPPP_CDN_Support_Skeleton',
		'WPPP_Dynamic_Images_Skeleton',
	);
	private $admin_opts = NULL;
	private $late_updates = array();

	public static $jit_versions = array(
		'3.8.1',
		'3.8.2',
		'3.8.3',
		'3.9',
		'3.9.1',
		'3.9.2',
	);

	public $dbg_textdomains = array ();
	public $is_network = false;
	public $modules = array();
	public $options = NULL;
	public $plugin_dir = NULL;

	function get_options_default () {
		$def_opts = static::$options_default;
		foreach ( $this->modules as $module ) {
			$def_opts = array_merge( $def_opts, $module->get_default_options() );
		}
		return $def_opts;
	}

	function load_options () {
		if ( $this->options == NULL ) {
			$this->options = $this->get_option( self::wppp_options_name );
			$def_opts = $this->get_options_default();
			foreach ( $def_opts as $key => $value ) {
				if ( !isset( $this->options[$key] ) ) {
					$this->options[$key] = $def_opts[$key];
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

	function is_jit_available () {
		global $wp_version;
		return in_array( $wp_version, self::$jit_versions );
	}

	public function __construct( $fullinit = true ) {
		spl_autoload_register( array( $this, 'wppp_autoloader' ) );

		// initialize modules
		foreach ( $this->available_modules as $module ) {
			$this->modules[] = new $module( $this );
		}

		// initialize fields
		global $wp_version;
		if ( !function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( $fullinit ) {
			$this->check_update();
			$this->load_options();
			$this->is_network = is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) );
		} else {
			$this->load_options();
			$this->is_network = is_multisite(); // TODO: add is_plugin_active... removed for now, because fullinit is only set to false from serve-dynamic-images which should not be used when multisite is active (and plugin_basename would cause problems)
			return;
		}

		$this->plugin_dir = dirname( plugin_basename(__FILE__) );

		// add actions
		add_action( 'activated_plugin', array ( 'WP_Performance_Pack', 'plugin_load_first' ) );
		add_action( 'init', array ( $this, 'init' ) );
		add_filter( 'update_option_' . self::wppp_options_name, array( $this, 'do_options_changed' ), 10, 2 );

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

		// activate and initialize modules
		foreach ( $this->modules as &$module ) {
			if ( $module->is_active() ) {
				$module = $module->spawn_body();
			}
			$module->early_init();
		}
	}

	function do_options_changed( $old_value, $new_value )
	{
		// flush rewrite rules if dynamic images setting changed
		if ( ( isset( $new_value['dynamic_images'] ) && $this->options['dynamic_images'] !== $new_value['dynamic_images'] )
				|| ( !isset( $new_value['dynamic_images'] ) && $this->options['dynamic_images'] === true ) ) {
			WPPP_Dynamic_Images::flush_rewrite_rules( $new_value['dynamic_images'] );
		}
	}

	public function init () {
		//wp_enqueue_script( 'wppp-media-manager-sizes', plugins_url( '/js/test.js', __FILE__ ), array( 'media-editor' ) );

		// execute "late" updates
		foreach ( $this->late_updates as $updatefunc ) {
			call_user_func( $updatefunc );
		}

		if ( $this->options['debug'] ) {
			add_filter( 'debug_bar_panels', array ( $this, 'add_debug_bar_wppp' ), 10 );
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

		foreach ( $this->modules as $module ) {
			$module->init();
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
		// doesn't fire on update, only on manual activation through admin
		// is called after check_update (which is called at construction)

		if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) { 
			deactivate_plugins( basename(__FILE__) ); // Deactivate self - does that really work at this stage?
			wp_die( 'WP Performance pack requries PHP version >= 5.3' );
		}

		// if is active in network of multisite
		if ( is_multisite() && isset( $_GET['networkwide'] ) && 1 == $_GET['networkwide'] ) {
			add_site_option( self::wppp_options_name, self::$options_default );
			add_site_option( 'wppp_version', self::wppp_version );
		} else {
			add_option( self::wppp_options_name, self::$options_default );
			add_option( 'wppp_version', self::wppp_version );
		}
		self::plugin_load_first();
	}

	public function deactivate() {
		if ( $this->options['dynamic_images'] ) {
			// Delete rewrite rules from htaccess
			WPPP_Dynamic_Images::flush_rewrite_rules( false ); // hopefully WPPP_Dynamic_images didn't get initialized elsewhere. Not shure at which point deactivation occurs, but I think it's save to assume DynImg didn't get initialized so rewrite rules didn't get set.
		}

		if ( is_multisite() && isset( $_GET['networkwide'] ) && 1 == $_GET['networkwide'] ) {
			delete_site_option( self::wppp_options_name );
		} else {
			delete_option( self::wppp_options_name );
		}
		delete_option( 'wppp_dynimg_sizes' );
		delete_option( 'wppp_version' );
		
		// restore static links
		WPPP_CDN_Support::restore_static_links();
	}

	function wppp_autoloader ( $class ) {
		$class = strtolower( $class );
		if ( strncmp( $class, 'wppp_', 5 ) === 0 || $class == 'labelsobject' ) {
			include( sprintf( "%s/classes/class.$class.php", dirname( __FILE__ ) ) );
		}
	}

	function check_update () {
		if ( ! $opts = $this->get_option( self::wppp_options_name ) ) {
			// if get_option fails, this is the activation, so no update necessary
			return;
		}
	
		$installed = $this->get_option( 'wppp_version' );
		if ( version_compare( $installed, self::wppp_version, '!=' ) ) {
			// if installed version differs from version saved in options then do update
			// it is assumed that the options-version is always less or equal to the installed version
			if ( $installed === false || empty( $installed ) ) {
				// pre 1.6.3 version didn't have the wppp_version option

				// serve-dynamic-images.php location has changed, so update rewrite-rules
				if ( isset( $opts['dynamic_images'] ) && $opts['dynamic_images'] ) {
					$this->late_updates[] = array( $this, 'update_163' );
				}
				$installed = '1.6.3';
			}

			$this->update_option ( 'wppp_version', self::wppp_version );
		}
	}
	
	function update_163 () {
		WPPP_Dynamic_Images::flush_rewrite_rules( true );
	}
} 

?>