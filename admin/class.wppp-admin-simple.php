<?php
/**
 * Admin settings simple view class. Functions for simplified settings.
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.8
 */
 
require_once( sprintf( '%s/class.wppp-admin-base.php', dirname( __FILE__ ) ) );

class WPPP_Admin_Simple extends WPPP_Admin_Base {

	/*
	 * Settings page functions
	 */

	function load_admin_page () {
		$this->enqueue_scripts_and_styles();
		$this->add_help_tab();
	}

	function enqueue_scripts_and_styles () {
		wp_register_script( 'jquery-ui-slider-pips', plugin_dir_url( __FILE__ ) . 'js/jquery-ui-slider-pips.js', array ( 'jquery-ui-slider' ), false, true );
		wp_register_script( 'wppp-admin-script', plugin_dir_url( __FILE__ ) . 'js/wppp_simple.js', array ( 'jquery-ui-slider-pips', 'jquery-ui-accordion' ), false, true );
		wp_enqueue_script( 'wppp-admin-script' );

		wp_register_style( 'jquery-ui-slider-pips-styles', plugin_dir_url( __FILE__ ) . 'css/jquery-ui-slider-pips.css' );
		wp_register_style( 'wppp-admin-styles', plugin_dir_url( __FILE__ ) . 'css/styles.css' );
		wp_enqueue_style( 'jquery-ui-slider-pips-styles' );
		wp_enqueue_style( 'wppp-admin-styles' );

	}

	function add_help_tab () {
		$screen = get_current_screen();

		// Add my_help_tab if current screen is My Admin Page
		$screen->add_help_tab( array(
			'id'	=> 'wppp_help_general',
			'title'	=> __('Overview'),
			'content'	=> __('<p>WP Performance Pack is intended to be a collection of performance optimizations for WordPress which don\'t need patching of core files. As of now it features options to improve performance of translated WordPress installations.</p><h4>Optimal settings</h4><ul><li>Use native gettext if available (for details on native gettext support activate <em>Debug Panel</em>).</li><li>MO-Dynamic should always work and is faster than default WordPress translations, but slower than native gettext.</li><li>Enable JIT localize if available.</li></ul>','wppp'),
		) );

		$screen->add_help_tab( array(
			'id'	=> 'wppp_help_modynamic',
			'title'	=> '- '.__('MO-Dynamic','wppp'),
			'content'	=> __('<p><strong>Dynamic loading of translation files, only loading and translating used strings.</strong></p><p>Improves performance and reduces memory consumption. The default WordPress <em>MO</em> implementation loads the complete MO files (e.g. when loaded via <code>load_textdomain</code>) into memory. As a result translation of individual strings is quite fast, but loading times and memory consumption are high. Most of the time only a few strings from a mo file are required within a single page request. Activating translation almost doubles execution time in a typical WordPress installation.</p><p>WPPP uses MO-Dynamic, a complete rewrite of the <em>MO</em> implementation, to speed up translations. .mo files are only loaded if needed. On installations with many translated plugins this alone can dramatically reduce execution time. It doesn\'t load the complete translations into memory, only required ones. This on demand translation is more expensive than translations on fully loaded .mo files but the performance gain by not loading and parsing the complete file outweighs this.</p>','wppp'),
		) );

		$screen->add_help_tab( array(
			'id'	=> 'wppp_help_gettext',
			'title'	=> '- '.__('Native gettext','wppp'),
			'content'	=> __('<p><strong>Use of native gettext if available</strong></p><p>There is probably no faster way for translations than using the native gettext implementation. This requires the <code>php_gettext</code> extension to be installed on the server. This implementation used is based on Bernd Holzmuellers <a href="http://oss.tiggerswelt.net/wordpress/3.3.1/">Translate_GetText_Native</a> implementation.</p><p>For now WPPP only checks if the gettext extension is available, which might not suffice to use native gettext. Further checks will follow.</p><p><em>Native gettext overrides MO-Dynamic if both are enabled!</em></p>','wppp'),
		) );

		$screen->add_help_tab( array(
			'id'	=> 'wppp_help_jit',
			'title'	=> '- ' . __('JIT localize','wppp'),
			'content'	=> __('<p><strong>Just in time localization</strong></p><p>Just in time localization of scripts. By default WordPress localizes all default scripts at each request. Enabling this option will translate localization string only if needed. This might improve performance even if WordPress is not translated, but has the biggest impact when using MO-Dynamic.</p><p><em>As for now only implemented for WordPress version 3.8.1 because of differences in wp_default_scripts (which gets overridden by this feature) between versions.</em></p>','wppp'),
		) );

		$screen->add_help_tab( array(
			'id'	=> 'wppp_help_backend',
			'title'	=> '- ' . __('Disable backend translation','wppp'),
			'content'	=> __('<p><strong>Disable backend translation while maintaining frontend translations.</strong></p><p>Speed up the backend by disabling dashboard-translations. Useful if you don\'t mind using an english backend.</p><p><em>AJAX requests on backend pages will still be translated, as I haven\'t figured out how to distinguish requests originating backend pages and requests from frontend pages.</em></p>','wppp'),
		) );

		$screen->set_help_sidebar(
			'<p><a href="http://wordpress.org/support/plugin/wp-performance-pack" target="_blank">'.__('Support Forums').'</a></p>'
		);
	}

	/*
	 * Setting page rendering functions
	 */

	public function do_options_page() {
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

		parent::do_options_page();
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
							$native = $this->is_native_gettext_available();
							if ( $native != 0 ) : ?>
								<div class="ui-state-highlight ui-corner-all" style="padding:.5em">
									<span class="ui-icon ui-icon-info" style="float:left; margin-right:.3em;"></span>
									<?php
									switch ( $native ) {
										case 0 :	break;
										case 1 :	_e( 'Native Gettext support requires the <a href="http://www.php.net/gettext">php_gettext</a> extension.', 'wppp' );
													break;
										case 2 :
										case 3 :	_e( 'Native Gettext support requires the language dir <code>wp-content/languages</code> to exists and to be writeable for php.', 'wppp' );
													break;
										case 4 :	_e( 'Native Gettext test failed. Activate debugging for additional info.', 'wppp' );
													break;
									}
									?>
								</div>
							<?php endif; ?>
							<?php if ( $native != 0 && !$this->is_object_cache_installed() ) : ?>
								<div class="ui-state-highlight ui-corner-all" style="padding:.5em">
									<span class="ui-icon ui-icon-info" style="float:left; margin-right:.3em;"></span>
									<?php _e( 'MO-Dynamic caching requires a persisten object cache to be effective. Different <a href="http://wordpress.org/plugins/search.php?q=object+cache">object cache plugins</a> are available for Wordpress.', 'wppp' ); ?>
								</div>
							<?php endif; ?>
						</div>
						<div class="wppp-l10n-hint" style="display:none">
							<?php
							$native = $this->is_native_gettext_available();
							if ( $native != 0 ) : ?>
								<div class="ui-state-highlight ui-corner-all" style="padding:.5em">
									<span class="ui-icon ui-icon-info" style="float:left; margin-right:.3em;"></span>
									<?php
									switch ( $native ) {
										case 0 :	break;
										case 1 :	_e( 'Native Gettext support requires the <a href="http://www.php.net/gettext">php_gettext</a> extension.', 'wppp' );
													break;
										case 2 :
										case 3 :	_e( 'Native Gettext support requires the language dir <code>wp-content/languages</code> to exists and to be writeable for php.', 'wppp' );
													break;
										case 4 :	_e( 'Native Gettext test failed. Activate debugging for additional info.', 'wppp' );
													break;
									}
									?>
								</div>
							<?php endif; ?>
							<?php if ( $native != 0 && !$this->is_object_cache_installed() ) : ?>
								<div class="ui-state-highlight ui-corner-all" style="padding:.5em">
									<span class="ui-icon ui-icon-info" style="float:left; margin-right:.3em;"></span>
									<?php _e( 'MO-Dynamic caching requires a persisten object cache to be effective. Different <a href="http://wordpress.org/plugins/search.php?q=object+cache">object cache plugins</a> are available for Wordpress.', 'wppp' ); ?>
								</div>
							<?php endif; ?>
							<?php if ( !$this->is_jit_available() ) : ?>
								<div class="ui-state-highlight ui-corner-all" style="padding:.5em">
									<span class="ui-icon ui-icon-info" style="float:left; margin-right:.3em;"></span>
									<?php printf( __( 'JIT localization of scripts is only available for WordPress versions %s .', 'wppp' ), implode( ', ', WP_Performance_Pack::$jit_versions ) ); ?>
								</div>
							<?php endif; ?>
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
			echo '<li style="color:red">' . __( 'All translation settings turned off.', 'wppp' ) . '</li>';
		} else if ( $level < 3 ) {
			// Stable and Speed
			if ( $this->is_native_gettext_available() == 0 ) {
				echo '<li style="color:green">' . __( 'Native gettext activated.', 'wppp' ) . '</li>';
			} else {
				echo '<li style="color:red">' . __( 'Native gettext not available.', 'wppp' ) . '</li>';
				echo '<li style="color:green">' . __( 'MO-Dynamic activated.', 'wppp' ) . '</li>';
				if ( $this->is_object_cache_installed() ) {
					echo '<li style="color:green">' . __( 'MO-Dynamic caching activated.', 'wppp' ) . '</li>';
				} else {
					echo '<li style="color:red">' . __( 'No persistent object cache installed.', 'wppp' ) . '</li>';
				}
			}

			if ( $level > 1 ) {
				if ( $this->is_jit_available() ) {
					echo '<li style="color:green">' . __( 'JIT script localization activated', 'wppp' ) . '</li>';
				} else {
					echo '<li style="color:red">' . __( 'JIT script localization not available', 'wppp' ) . '</li>';
				}

				echo '<li style="color:green">' . __( 'Backend translation disabled (per user override via user profile allowed).', 'wppp' ) . '</li>';
			}
		} else {
			// Custom
			if ( $this->wppp->options['use_native_gettext'] ) {
				echo '<li>' . __( 'Native gettext activated.', 'wppp' ) . '</li>';
			}
			if ( $this->wppp->options['use_mo_dynamic'] ) {
				echo '<li>' . __( 'MO-Dynamic activated.', 'wppp' ) . '</li>';
			}
			if ( $this->wppp->options['mo_caching'] ) {
				echo '<li>' . __( 'MO-Dynamic caching activated.', 'wppp' ) . '</li>';
			}
			if ( $this->wppp->options['use_jit_localize'] ) {
				echo '<li>' . __( 'JIT script localization activated', 'wppp' ) . '</li>';
			}
			if ( $this->wppp->options['disable_backend_translation'] ) {
				if ( $this->wppp->options['dbt_allow_user_override'] ) {
					echo '<li>' . __( 'Backend translation disabled (per user override via user profile allowed).', 'wppp' ) . '</li>';
				} else {
					echo '<li>' . __( 'Backend translation disabled.', 'wppp' ) . '</li>';
				}
			}
		}
		echo '</ul>';
	}
}
?>