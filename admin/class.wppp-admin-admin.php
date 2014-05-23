<?php
/**
 * Admin settings base class (abstract). Adds admin menu and contains functions for both
 * simple and advanced view.
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.8
 */

include( sprintf( '%s/class.wppp-admin-user.php', dirname( __FILE__ ) ) );
 
class WPPP_Admin_Admin extends WPPP_Admin_User {

	private $renderer = NULL;
	private $show_update_info = false;

	public function __construct($wppp_parent) {
		parent::__construct($wppp_parent);
		register_setting( 'wppp_options', WP_Performance_Pack_Commons::$options_name, array( $this, 'validate' ) );
		if ( $this->wppp->is_network ) {
			add_action( 'network_admin_menu', array( $this, 'add_menu_page' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		}
		add_action('wp_ajax_wpppsupport', array($this, 'support_dialog'));
		add_action('wp_ajax_hidewpppsupportbox', array($this, 'hide_support_box'));
	}

	function hide_support_box () {
		$today = new DateTime();
		set_transient( 'wppp-support-box', $today->format('Y-m-d'), DAY_IN_SECONDS );
	}

	function support_dialog () {
		?>
		<p>You can include the following information along with your bug / issue / question when posting in the support forums:</p>
		<textarea rows="25" style="width:100%">
WPPP version: <?php echo WP_Performance_Pack_Commons::wppp_version; ?>

WPPP settings: <?php
	foreach ( $this->wppp->options as $opt => $val ) {
		echo $opt, ' = "', $val, '", ';
	}
?>

----------
WordPress version: <?php global $wp_version; echo $wp_version; ?>

Multisite: <?php echo is_multisite() ? 'yes' : 'no'; ?>

Plugins: <?php
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$all_plugins = get_plugins();
	foreach ( $all_plugins as $plugin => $plugin_data ) {
		if ( is_plugin_active( $plugin ) ) {
			echo '** ', $plugin_data['Name'], ' **';
		} else {
			echo $plugin_data['Name'];
		}
		echo ', ';
	}
?>

----------
OS: <?php echo php_uname(); ?>

PHP version: <?php echo phpversion(); ?>

Loaded extensions: <?php
	$exts = get_loaded_extensions();
	asort( $exts );
	echo implode( ', ', $exts );
?>
		</textarea>
		<?php
		exit();
	}

	public function add_menu_page() {
		if ( $this->wppp->is_network ) {
			$wppp_options_hook = add_submenu_page( 'settings.php', __( 'WP Performance Pack', 'wppp' ), __( 'Performance Pack', 'wppp' ), 'manage_options', 'wppp_options_page', array( $this, 'do_options_page' ) );
		} else {
			$wppp_options_hook = add_options_page( __( 'WP Performance Pack', 'wppp' ), __( 'Performance Pack', 'wppp' ), 'manage_options', 'wppp_options_page', array( $this, 'do_options_page' ) );
		}
		add_action('load-'.$wppp_options_hook, array ( $this, 'load_admin_page' ) );
	}

	/*
	 * Save and validate settings functions
	 */

	public function validate( $input ) {
		$output = array();
		if ( isset( $input ) && is_array( $input ) ) {

			// test if view mode has changed. if so, leave all other settings as they are
			if ( isset( $input['advanced_admin_view'] ) ) {
				$view = $input['advanced_admin_view'] == 'true' ? true : false;
				if ( $view != $this->wppp->options['advanced_admin_view'] ) {
					$output = $this->wppp->options;
					$output['advanced_admin_view'] = $view;
					return $output;
				}
			}

			foreach ( WP_Performance_Pack_Commons::$options_default as $key => $val ) {
				if ( isset( $input[$key] ) ) {
					// validate set input values
					switch ( $key ) {
						case 'advanced_admin_view' 	: $output[$key] = $this->wppp->options['advanced_admin_view'];
													  break;
						case 'dynimg_quality'		: $output[$key] = ( is_numeric( $input[$key] ) && $input[$key] >= 10 && $input[$key] <= 100 ) ? $input[ $key] : $val;
													  break;
						case 'cdn'					: $value = trim( sanitize_text_field( $input[$key] ) );
													  switch ( $value ) {
														case 'coralcdn'  :
														case 'maxcdn' 	 :
														case 'customcdn' : $output[$key] = $value;
																		   break;
														default			 : $output[$key] = false;
																		   break;
													  }
													  break;
						case 'cdnurl'				: $value = trim( sanitize_text_field( $input[$key] ) );
													  if ( !empty( $value ) ) {
														$scheme = parse_url( $value, PHP_URL_SCHEME );
														if ( empty( $scheme ) ) {
															$value = 'http://' . $value;
														}
													  }
													  $output[$key] = $value;
													  break;
						case 'cdn_images'			: $value = trim( sanitize_text_field( $input[$key] ) );
													  switch ( $value ) {
														case 'front'	:
														case 'back'		: $output[$key] = $value;
																		  break;
														default			: $output[$key] = 'both';
																		  break;
													  }
													  break;
						default						: $output[$key] = ( $input[$key] == 'true' ? true : false );
													  break;
					}
				} else {
					// not set values are assumed as false or the respective value (not necessary the default value)
					switch ( $key ) {
						case 'advanced_admin_view' 	: $output[$key] = $this->wppp->options['advanced_admin_view'];
													  break;
						case 'dynimg_quality'		: $output[$key] = $val;
													  break;
						case 'dynimg_cdnurl'		: $output[$key] = '';
													  break;
						case 'cdn_images'			: $output[$key] = $val;
													  break;
						default						: $output[$key] = false;
													  break;
					}
				} // if isset...
			} // foreach
			
			// postprocessing of values
			if ( $output['cdn'] !== 'customcdn' 
				&& $output['cdn'] !== 'maxcdn' )  {
				$output['cdnurl'] = '';
			}
		}
		delete_transient( 'wppp_cdntest' ); // cdn settings might have changed, so delete last test result
		return $output;
	}

	function update_wppp_settings () {
		if ( current_user_can( 'manage_network_options' ) ) {
			check_admin_referer( 'update_wppp', 'wppp_nonce' );
			$input = array();
			foreach ( WP_Performance_Pack_Commons::$options_default as $key => $value ) {
				if ( isset( $_POST['wppp_option'][$key] ) ) {
					$input[$key] = sanitize_text_field( $_POST['wppp_option'][$key] );
				}
			}
			$this->wppp->options = $this->validate( $input );
			update_site_option( WP_Performance_Pack_Commons::$options_name, $this->wppp->options );
		}
	}

	/*
	 * Settings page functions
	 */

	private function load_renderer () {
		if ( $this->renderer == NULL) {
			if ( $this->wppp->options['advanced_admin_view'] ) {
				include( sprintf( "%s/class.renderer-advanced.php", dirname( __FILE__ ) ) );
				$this->renderer = new WPPP_Admin_Renderer_Advanced( $this->wppp );
			} else {
				include( sprintf( "%s/class.renderer-simple.php", dirname( __FILE__ ) ) );
				$this->renderer = new WPPP_Admin_Renderer_Simple( $this->wppp );
			}
		}
	}

	function load_admin_page () {
		if ( $this->wppp->is_network ) {
			if ( isset( $_GET['action'] ) && $_GET['action'] === 'update_wppp' ) {
				$this->update_wppp_settings();
				$this->show_update_info = true;
			}
		}
		$this->load_renderer();
		$this->renderer->enqueue_scripts_and_styles();
		$this->renderer->add_help_tab();
	}

	public function do_options_page() {
		if ( $this->wppp->is_network ) {
			$formaction = network_admin_url('settings.php?page=wppp_options_page&action=update_wppp');
		} else {
			$formaction = 'options.php';
		}

		if ( $this->show_update_info ) {
			echo '<div class="updated"><p>', __( 'Settings saved.' ), '</p></div>';
		}

		$this->load_renderer();
		$this->renderer->on_do_options_page();
		$this->renderer->render_page( $formaction );
	}
}
?>