<?php
/**
 * Abstract admin settings renderer class.
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.9
 */
 
abstract class WPPP_Admin_Renderer {
	protected $wppp = NULL;
	private $admin = NULL;
	
	public function __construct( $wppp_parent ) {
		$this->wppp = $wppp_parent;
	}

	abstract function render_options ();

	abstract function enqueue_scripts_and_styles();

	abstract function add_help_tab();

	public function on_do_options_page() {}

	public function render_page ( $formaction ) {
		?>
		<div class="wrap">
			<img src="<?php echo plugins_url( 'img/wppp_logo_150.png' , __FILE__ ); ?>" style="float:left; margin-right:10px;" />
			<h2 style="height:80px"><?php _e( 'WP Performance Pack - Settings', 'wppp' ); ?></h2>
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
	 * Feature detection functions
	 */

	function is_object_cache_installed () {
		global $wp_object_cache;
		return ( file_exists ( WP_CONTENT_DIR . '/object-cache.php' )
				&& get_class( $wp_object_cache ) != 'WP_Object_Cache' );
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

		if ( $mo->translate( 'native-gettext-test' ) !== 'success' ) {
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
	 * Helper functions
	 */

	function do_hint_gettext ( $as_error ) {
		$native = $this->is_native_gettext_available(); 
		if ( $native != 0 ) {
			if ( $as_error ) {
				echo '<div class="ui-state-error ui-corner-all" style="padding:.5em"><span class="ui-icon ui-icon-alert" style="float:left; margin-right:.3em;"></span>';
			} else {
				echo '<div class="ui-state-highlight ui-corner-all" style="padding:.5em"><span class="ui-icon ui-icon-info" style="float:left; margin-right:.3em;"></span>';
			}

			switch ( $native ) {
				case 0 :	break;
				case 1 :	printf( __( 'Gettext support requires the %s extension.', 'wppp' ), '<a href="http://www.php.net/gettext">PHP Gettext</a>' );
							break;
				case 2 :
				case 3 :	printf( __( 'Gettext support requires the language directory %s to be writeable for php.', 'wppp' ), '<code>wp-content/languages</code>' );
							break;
				case 4 :	_e( 'Gettext test failed. Activate WPPP debugging for additional info.', 'wppp' );
							break;
			}
			echo '</div>';
		}
		return $native;
	}

	function do_hint_mo_cache () {
		if ( !$this->is_object_cache_installed() ) : ?>
			<div class="ui-state-highlight ui-corner-all" style="padding:.5em">
				<span class="ui-icon ui-icon-info" style="float:left; margin-right:.3em;"></span>
				<?php printf( __( 'Caching requires a persisten object cache to be effective. Different %sobject cache plugins%s are available for WordPress.', 'wppp' ), '<a href="http://wordpress.org/plugins/search.php?q=object+cache">', '</a>' ); ?>
			</div>
		<?php endif;
	}

	function do_hint_jit ( $as_error ) {
		if ( !$this->is_jit_available() ) {
			if ( $as_error ) {
				echo '<div class="ui-state-error ui-corner-all" style="padding:.5em"><span class="ui-icon ui-icon-alert" style="float:left; margin-right:.3em;"></span>';
			} else {
				echo '<div class="ui-state-highlight ui-corner-all" style="padding:.5em"><span class="ui-icon ui-icon-info" style="float:left; margin-right:.3em;"></span>';
			}
			printf( __( 'JIT localization of scripts is only available for WordPress versions %s .', 'wppp' ), implode( ', ', WP_Performance_Pack::$jit_versions ) );
			echo '</div>';
		}
	}

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
			<input type="hidden" <?php $this->e_opt_name('dynamic_images'); ?> value="<?php echo $this->wppp->options['dynamic_images'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('dynamic_images_nosave'); ?> value="<?php echo $this->wppp->options['dynamic_images_nosave'] ? 'true' : 'false' ?>" />
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