<?php
/**
 * Admin settings simple renderer class. Functions for simplified settings.
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.9
 */
 
include( sprintf( '%s/class.admin-renderer.php', dirname( __FILE__ ) ) );

class WPPP_Admin_Renderer_Simple extends WPPP_Admin_Renderer {

	/*
	 * Settings page functions
	 */

	function enqueue_scripts_and_styles () {
		wp_register_script( 'jquery-ui-slider-pips', plugin_dir_url( __FILE__ ) . 'js/jquery-ui-slider-pips.js', array ( 'jquery-ui-slider' ), false, true );
		wp_register_script( 'wppp-admin-script', plugin_dir_url( __FILE__ ) . 'js/wppp_simple.js', array ( 'jquery-ui-slider-pips' ), false, true );
		wp_enqueue_script( 'wppp-admin-script' );

		wp_register_style( 'jquery-ui-slider-pips-styles', plugin_dir_url( __FILE__ ) . 'css/jquery-ui-slider-pips.css' );
		wp_register_style( 'wppp-admin-styles', plugin_dir_url( __FILE__ ) . 'css/styles.css' );
		wp_enqueue_style( 'jquery-ui-slider-pips-styles' );
		wp_enqueue_style( 'wppp-admin-styles' );

	}

	function add_help_tab () {
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'	=> 'wppp_simple_general',
			'title'	=> __('Overview'),
			'content'	=> '<p>' . __( "Welcome to WP Performance Pack, your first choice for speeding up WordPress core the easy way. The simple view helps you to easily apply  the optimal settings for your blog. Advanced view offers more in depth control of WPPP settings.", 'wppp' ) .'</p>',
		) );

		$screen->add_help_tab( array(
			'id'	=> 'wppp_simple_l10n',
			'title'	=> __( 'Improve translation performance', 'wppp' ),
			'content'	=>	'<p>' . __( "WPPP offers different levels of improving translation performance. When you select an option, optimal settings will be applied. Applied settings will be displayed. If some settings couldn't be applied, e.g. due to missing requirements, these will be displayed in red and the next best setting (if available) will be chosen. Also hints as to why a setting couldn't be applied will be displayed.", 'wppp' ) . '</p>',
		) );

		$screen->set_help_sidebar(
			'<p><a href="http://wordpress.org/support/plugin/wp-performance-pack" target="_blank">' . __( 'Support Forums' ) . '</a></p>'
			. '<p><a href="http://www.bjoernahrens.de/software/wp-performance-pack/" target="_blank">' . __( 'Development Blog (german)', 'wppp' ) . '</a></p>'
		);
	}

	/*
	 * Setting page rendering functions
	 */

	public function on_do_options_page() {
		$option_keys = array_keys( WP_Performance_Pack::$options_default );
		unset ( $option_keys [ array_search( 'advanced_admin_view', $option_keys ) ] );
		wp_localize_script( 'wppp-admin-script', 'wpppData', array (
			'l10nSetting' => $this->detect_current_setting(),
			'l10nStableSettings' => implode( ',', $this->get_settings_for_level( 1 ) ),
			'l10nSpeedSettings' => implode( ',', $this->get_settings_for_level( 2 ) ),
			'l10nCustomSettings' => implode( ',', $this->get_settings_for_level( 3 ) ),
			'l10nAllSettings' => implode( ',', $option_keys ),

			'l10nLabelOff' => __( 'Off', 'wppp' ),
			'l10nLabelStable' => __( 'Stable', 'wppp' ),
			'l10nLabelSpeed' => __( 'Speed', 'wppp' ),
			'l10nLabelCustom' => __( 'Custom', 'wppp' ),
		));
	}

	function render_options () {
		?>
		<h3><?php _e( 'Improve translation performance', 'wppp' ); ?></h3>
		<div>
			<input type="hidden" <?php $this->e_opt_name('use_mo_dynamic'); ?> value="<?php echo $this->wppp->options['use_mo_dynamic'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('use_jit_localize'); ?> value="<?php echo $this->wppp->options['use_jit_localize'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('disable_backend_translation'); ?> value="<?php echo $this->wppp->options['disable_backend_translation'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('dbt_allow_user_override'); ?> value="<?php echo $this->wppp->options['dbt_allow_user_override'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('use_native_gettext'); ?> value="<?php echo $this->wppp->options['use_native_gettext'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('mo_caching'); ?> value="<?php echo $this->wppp->options['mo_caching'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('debug'); ?> value="<?php echo $this->wppp->options['debug'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('dynamic_images'); ?> value="<?php echo $this->wppp->options['dynamic_images'] ? 'true' : 'false' ?>" />
			<input type="hidden" <?php $this->e_opt_name('dynamic_images_nosave'); ?> value="<?php echo $this->wppp->options['dynamic_images_nosave'] ? 'true' : 'false' ?>" />
			<table style="empty-cells:show; width:100%;">
				<tr>
					<td valign="top" style="width:8em; height:12em;"><div id="l10n-slider" style="height:8em; margin-top:2em;"></div></td>
					<td valign="top">
						<div class="wppp-l10n-desc" style="display:none;">
							<h4 style="margin-top:0;"><?php _e( 'Translation improvements turned off', 'wppp' ); ?></h4>
							<?php $this->output_active_settings( 0 ); ?>
						</div>
						<div class="wppp-l10n-desc" style="display:none;">
							<h4 style="margin-top:0;"><?php _e( 'Fast WordPress translation', 'wppp' ); ?></h4>
							<p class="description"><?php _e( 'Safe settings that should work with any WordPress install.', 'wppp' );?></p>
							<?php $this->output_active_settings( 1 ); ?>
						</div>
						<div class="wppp-l10n-desc" style="display:none;">
							<h4 style="margin-top:0;"><?php _e( 'Fastest WordPress translation', 'wppp' ); ?></h4>
							<p class="description"><?php _e( 'Fastest translation settings. If any problems occur after activating, switch to stable setting.', 'wppp' ); ?></p>
							<?php $this->output_active_settings( 2 ); ?>
						</div>
						<div class="wppp-l10n-desc" style="display:none;">
							<h4 style="margin-top:0;"><?php _e( 'Custom settings', 'wppp' ); ?></h4>
							<p class="description"><?php _e( 'Select your own settings. Customize via advanced view.', 'wppp' ); ?></p>
							<?php $this->output_active_settings( 3 ); ?>
						</div>
					</td>
					<td valign="top" style="width:30%">
						<div class="wppp-l10n-hint" style="display:none"></div>
						<div class="wppp-l10n-hint" style="display:none">
							<?php 
								$native = $this->do_hint_gettext( false );
								if ( $native != 0 ) {
									$this->do_hint_mo_cache();
								}
							?>
						</div>
						<div class="wppp-l10n-hint" style="display:none">
							<?php 
								$this->do_hint_gettext( false ); 
								if ( $native != 0 ) {
									$this->do_hint_mo_cache();
								}
								$this->do_hint_jit( false );
							?>
						</div>
						<div class="wppp-l10n-hint" style="display:none"></div>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/*
	 * Simple view helper functions
	 */

	function detect_current_setting () {
		// off - all options turned off
		if ( !$this->wppp->options['use_mo_dynamic']
			&& !$this->wppp->options['use_jit_localize']
			&& !$this->wppp->options['disable_backend_translation']
			&& !$this->wppp->options['dbt_allow_user_override']
			&& !$this->wppp->options['use_native_gettext']
			&& !$this->wppp->options['mo_caching'] )
			return 0;

		// stable - mo-dynamic/native, caching
		if ( ( $this->wppp->options['use_mo_dynamic'] || $this->is_native_gettext_available() === 0 )
			&& !$this->wppp->options['use_jit_localize']
			&& !$this->wppp->options['disable_backend_translation']
			&& !$this->wppp->options['dbt_allow_user_override']
			&& ( $this->wppp->options['use_native_gettext'] || $this->is_native_gettext_available() !== 0 )
			&& ( $this->wppp->options['mo_caching'] || !$this->is_object_cache_installed() || $this->is_native_gettext_available() === 0 ) )
			return 1;

		// faster - mo-dynamic/native, caching, jit, disable backend, allow user override
		if ( ( $this->wppp->options['use_mo_dynamic'] || $this->is_native_gettext_available() === 0 )
			&& ( $this->wppp->options['use_jit_localize'] || !$this->is_jit_available() )
			&& $this->wppp->options['disable_backend_translation']
			&& $this->wppp->options['dbt_allow_user_override']
			&& ( $this->wppp->options['use_native_gettext'] || $this->is_native_gettext_available() !== 0 )
			&& ( $this->wppp->options['mo_caching'] || !$this->is_object_cache_installed() || $this->is_native_gettext_available() === 0 ) )
			return 2;

		// else custom 
		return 3;
	}

	function get_settings_for_level ( $level ) {
		$result = array();
		if ( $level == 0 ) {
			// Off
			return $result;
		} else if ( $level < 3 ) {
			// Stable and Speed
			if ( $this->is_native_gettext_available() == 0 ) {
				$result[] = 'use_native_gettext';
			} else {
				$result[] = 'use_mo_dynamic';
				if ( $this->is_object_cache_installed() ) {
					$result[] = 'mo_caching';
				}
			}

			if ( $level > 1 ) {
				// Speed
				if ( $this->is_jit_available() ) {
					$result[] = 'use_jit_localize';
				}
				$result[] = 'disable_backend_translation';
				$result[] = 'dbt_allow_user_override';
			}
		} else {
			// Custom
			foreach ( $this->wppp->options as $key => $value ) {
				if ( $value )
					$result[] = $key;
			}
		}
		return $result;
	}

	function output_active_settings ( $level ) {
		echo '<ul>';
		if ( $level == 0 ) {
			// Off
			echo '<li class="ui-state-error" style="border:none; background:none;"><span class="ui-icon ui-icon-closethick" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'All translation settings turned off.', 'wppp' ) . '</li>';
		} else if ( $level < 3 ) {
			// Stable and Speed
			if ( $this->is_native_gettext_available() == 0 ) {
				echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Use gettext', 'wppp' ) . '</li>';
			} else {
				echo '<li class="ui-state-error" style="border:none; background:none;"><span class="ui-icon ui-icon-closethick" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Gettext not available.', 'wppp' ) . '</li>';
				echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Use alternative MO reader', 'wppp' ) . '</li>';
				if ( $this->is_object_cache_installed() ) {
					echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Use caching', 'wppp' ) . '</li>';
				} else {
					echo '<li class="ui-state-error" style="border:none; background:none;"><span class="ui-icon ui-icon-closethick" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'No persistent object cache installed.', 'wppp' ) . '</li>';
				}
			}

			if ( $level > 1 ) {
				if ( $this->is_jit_available() ) {
					echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Use JIT localize', 'wppp' ) . '</li>';
				} else {
					echo '<li class="ui-state-error" style="border:none; background:none;"><span class="ui-icon ui-icon-closethick" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'JIT localize not available', 'wppp' ) . '</li>';
				}

				echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Disable back end translation', 'wppp' ) . ' (' . __( 'Allow user override', 'wppp' ) . ')</li>';
			}
		} else {
			// Custom
			if ( $this->wppp->options['use_native_gettext'] ) {
				echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Use gettext', 'wppp' ) . '</li>';
			}
			if ( $this->wppp->options['use_mo_dynamic'] ) {
				echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Use alternative MO reader', 'wppp' ) . '</li>';
			}
			if ( $this->wppp->options['mo_caching'] ) {
				echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Use caching', 'wppp' ) . '</li>';
			}
			if ( $this->wppp->options['use_jit_localize'] ) {
				echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Use JIT localize', 'wppp' ) . '</li>';
			}
			if ( $this->wppp->options['disable_backend_translation'] ) {
				echo '<li class="ui-state-highlight" style="border:none; background:none;"><span class="ui-icon ui-icon-check" style="float:left; margin-top:.2ex; margin-right:.5ex;"></span>' . __( 'Disable back end translation', 'wppp' );
				if ( $this->wppp->options['dbt_allow_user_override'] ) {
					echo  ' (' . __( 'Allow user override', 'wppp' ) . ')';
				} 
				echo '</li>';
			}
		}
		echo '</ul>';
	}
}
?>