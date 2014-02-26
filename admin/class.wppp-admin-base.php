<?php
/**
 * Admin settings base class (abstract). Adds admin menu and contains functions for both
 * simple and advanced view.
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.8
 */

require_once( sprintf( '%s/class.wppp-admin.php', dirname( __FILE__ ) ) );
 
abstract class WPPP_Admin_Base extends WPPP_Admin {

	public function __construct($wppp_parent) {
		parent::__construct($wppp_parent);

		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
	}

	public function admin_init() {
		register_setting( 'wppp_options', WP_Performance_Pack::$options_name, array( $this, 'validate' ) );
		parent::admin_init();
	}

	public function add_menu_page() {
		if ( $this->wppp->is_network ) {
			$wppp_options_hook = add_submenu_page( 'settings.php', __('WP Performance Pack','wppp'), __('Performance Pack','wppp'), 'manage_options', 'wppp_options_page', array( $this, 'do_options_page' ) );
		} else {
			$wppp_options_hook = add_options_page( __('WP Performance Pack','wppp'), __('Performance Pack','wppp'), 'manage_options', 'wppp_options_page', array( $this, 'do_options_page' ) );
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
	 * Feature detection functions
	 */

	function is_object_cache_installed () {
		return file_exists ( WP_CONTENT_DIR . '/object-cache.php' );
	}

	function is_native_gettext_available () {
		static $result = NULL;
		if ( $result !== NULL) {
			return $result;
		}

		// gettext extension is required
		if ( !extension_loaded( 'gettext' ) ) {
			$result = 1;
			return 1;
		};

		// language dir must exist (an be writeable...)
		$locale = get_locale();
		$path = WP_LANG_DIR . '/' . $locale . '/LC_MESSAGES';
		if ( !is_dir ( $path ) ) {
			if ( !wp_mkdir_p ( $path ) ) {
				$result = 2;
				return 2;
			}
		}

		// load test translation and test if it translates correct
		require_once( sprintf( '%s/../modules/class.native-gettext.php', dirname( __FILE__ ) ) );
		$mo = new Translate_GetText_Native();
		if ( !$mo->import_from_file( sprintf( '%s/native-gettext-test.mo', dirname( __FILE__ ) ) ) ) {
			$result = 3;
			return 3;
		}

		if ( !$mo->translate( 'native-gettext-test' ) === 'success' ) {
			$result = 4;
			return 4;
		}

		// all tests successful => return 0
		$result = 0;
		return 0;
	}

	function is_jit_available () {
		global $wp_version;
		return in_array( $wp_version, WP_Performance_Pack::$jit_versions );
	}

	/*
	 * Settings page functions
	 */

	abstract function load_admin_page ();

	abstract function render_options ();

	public function do_options_page() {
		if ( $this->wppp->is_network ) {
			if ( isset( $_GET['action'] ) && $_GET['action'] === 'update_wppp' ) {
				$this->update_wppp_settings();
			}
			$formaction = network_admin_url('settings.php?page=wppp_options_page&action=update_wppp');
		} else {
			$formaction = 'options.php';
		}

		?>
		<div class="wrap">
			<h2><?php _e( 'WP Performance Pack - Settings', 'wppp' ); ?></h2>
			<form id="wppp-settings" action="<?php echo $formaction; ?>" method="post">
				<input type="hidden" <?php $this->e_opt_name('advanced_admin_view'); ?> value="<?php echo ( $this->wppp->options['advanced_admin_view'] ) ? 'true' : 'false'; ?>" />
				<?php 
					if ( $this->wppp->is_network ) {
						wp_nonce_field( 'update_wppp', 'wppp_nonce' );
					}
					settings_fields( 'wppp_options' );
				?>
				<div class="accordion">
				<?php
					$this->render_options();
				?>
				</div>
				<?php submit_button(); ?>
			</form>
			<?php $this->do_switch_view_button( $formaction, $this->wppp->options['advanced_admin_view'] ? 'false' : 'true' ); ?>
		</div>
		<?php
	}

	/*
	 * Helper functions
	 */

	function do_switch_view_button ( $formaction, $value ) {
		?>
		<form action="<?php echo $formaction; ?>" method="post">
			<?php if ( $this->wppp->is_network ) : ?>
				<?php wp_nonce_field( 'update_wppp', 'wppp_nonce' ); ?>
			<?php endif; ?>
			<?php settings_fields( 'wppp_options' ); ?>
			<input type="hidden" <?php $this->e_opt_name('advanced_admin_view'); ?> value="<?php echo $value; ?>" />
			<input type="hidden" <?php $this->e_opt_name('use_mo_dynamic'); ?> value="<?php echo $this->wppp->options['use_mo_dynamic'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('use_jit_localize'); ?> value="<?php echo $this->wppp->options['use_jit_localize'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('disable_backend_translation'); ?> value="<?php echo $this->wppp->options['disable_backend_translation'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('dbt_allow_user_override'); ?> value="<?php echo $this->wppp->options['dbt_allow_user_override'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('use_native_gettext'); ?> value="<?php echo $this->wppp->options['use_native_gettext'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('mo_caching'); ?> value="<?php echo $this->wppp->options['mo_caching'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('debug'); ?> value="<?php echo $this->wppp->options['debug'] ? 'true' : 'false' ?>" />
			<input type="submit" class="button" type="submit" value="<?php echo ( $value == 'true' ) ? __( 'Switch to advanced view', 'wppp') : __( 'Switch to simple view', 'wppp' ); ?>" />
		</form>
		<?php
	}

	protected function e_opt_name ( $opt_name ) {
		echo 'name="'.WP_Performance_Pack::$options_name.'['.$opt_name.']"';
	}

	protected function e_checked ( $opt_name, $value = true ) {
		echo $this->wppp->options[$opt_name] === $value ? 'checked="checked" ' : ' ';
	}

	protected function e_checked_or ( $opt_name, $value = true, $or_val = true ) {
		echo $this->wppp->options[$opt_name] === $value || $or_val ? 'checked="checked" ' : ' ';
	}

	protected function e_checked_and ( $opt_name, $value = true, $and_val = true ) {
		echo $this->wppp->options[$opt_name] === $value && $and_val ? 'checked="checked" ' : ' ';
	}
}
?>