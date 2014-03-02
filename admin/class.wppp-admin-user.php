<?php
/**
 * Admin base class. Contains functions for all users (i.e. user without manage_options rights).
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.8
 */
 
class WPPP_Admin_User {
	protected $wppp = NULL;

	public function __construct($wppp_parent) {
		$this->wppp = $wppp_parent;
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_init() {
		load_plugin_textdomain( 'wppp', false, $this->wppp->plugin_dir . '/lang');

		if ( $this->wppp->options['disable_backend_translation'] && $this->wppp->options['dbt_allow_user_override'] ) {
			add_action( 'show_user_profile', array ( $this, 'show_wppp_user_settings' ) );
			add_action( 'edit_user_profile', array ( $this, 'show_wppp_user_settings' ) );
			add_action( 'personal_options_update', array ( $this, 'save_wppp_user_settings' ) );
			add_action( 'edit_user_profile_update', array ( $this, 'save_wppp_user_settings' ) );
		}
	}

	/*
	 * User override of disable  backend translation
	 */

	function save_wppp_user_settings ( $user_id ) {
		if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }

		if ( isset( $_POST['wppp_translate_backend'] ) && $_POST['wppp_translate_backend'] === 'true' ) {
			update_user_option( $user_id, 'wppp_translate_backend', 'true' );
		} else {
			update_user_option( $user_id, 'wppp_translate_backend', 'false' );
		}
	}

	public function show_wppp_user_settings ($user) {
		$user_setting = get_user_option( 'wppp_translate_backend', $user->ID );
		$user_override = $user_setting === 'true' || ( $this->wppp->options['dbt_user_default_translated'] && $user_setting === false );
		?>
		<h3><?php _e( 'Back end language', 'wppp' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e( 'Translate back end', 'wppp' ); ?></th>
					<td>
						<label for="wppp-translate-backend-enabled"><input type="radio" name="wppp_translate_backend" id="wppp-translate-backend-enabled" value="true" <?php echo  $user_override ? 'checked="true"' : ''; ?> /><?php _e( 'Enabled', 'wppp' ); ?></label>&nbsp;
						<label for="wppp-translate-backend-disabled"><input type="radio" name="wppp_translate_backend" id="wppp-translate-backend-disabled" value="false" <?php echo !$user_override ? 'checked="true"' : ''; ?> /><?php _e( 'Disabled', 'wppp' ); ?></label>
						<p class="description"><?php _e( 'Enable or disable back end translation. When disabled, back end will be displayed in english, else it will be translated to the blog language.', 'wppp' ); ?></p>
					</td>
				</tr>
			</table>
		<?php
	}
}
?>