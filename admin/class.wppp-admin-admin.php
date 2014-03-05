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

	public function __construct($wppp_parent) {
		parent::__construct($wppp_parent);
		register_setting( 'wppp_options', WP_Performance_Pack::$options_name, array( $this, 'validate' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
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
			foreach( $input as $key => $val ) {
				if ( isset ( $input[$key] ) ) {
					$output[$key] = ( $input[$key] == 'true' ? true : false );
				}
			}
		}
		return $output;
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
		$this->load_renderer();
		$this->renderer->enqueue_scripts_and_styles();
		$this->renderer->add_help_tab();
	}

	public function do_options_page() {
		$this->load_renderer();
		$this->renderer->on_do_options_page();
		if ( $this->wppp->is_network ) {
			if ( isset( $_GET['action'] ) && $_GET['action'] === 'update_wppp' ) {
				$this->update_wppp_settings();
			}
			$formaction = network_admin_url('settings.php?page=wppp_options_page&action=update_wppp');
		} else {
			$formaction = 'options.php';
		}

		$this->load_renderer();
		$this->renderer->render_page( $formaction );
	}
}
?>