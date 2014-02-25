<?php
/**
 * DebugBar panel for WP Performance Pack
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.5
 * @license GNU General Public License version 3 or later
 */
 
class Debug_Bar_WPPP extends Debug_Bar_Panel {
	public $textdomains = array ();
	public $plugin_base = '';

	private function get_caller ( $stacktrace ) {
		static $excludes = array (
			'call_user_func_array',
			'load_textdomain_override',
			'apply_filters',
			'load_textdomain',
			'load_theme_textdomain',
			'load_plugin_textdomain',
		);
		$str = '?';
		for ( $i = 0, $max = count( $stacktrace ); $i < $max; $i++) {
			if ( !in_array ( $stacktrace[$i]['function'], $excludes ) ) {
				if ( isset( $stacktrace[$i]['class'] ) ) {
					$str = $stacktrace[$i]['class'] . $stacktrace[$i]['type'] . $stacktrace[$i]['function'];
				} else {
					$str = $stacktrace[$i]['function'];
				}

				if ( isset( $stacktrace[$i]['file'] ) ) {
					$str = substr ( $stacktrace[$i]['file'], strlen ( ABSPATH ) ) . ': ' . $str;
				}
				break;
			}
		}
		return $str;
	}

	function init() {
		$this->title( __('WP Performance Pack', 'wppp') );
	}

	private function isAvailable($func) {
		if (ini_get('safe_mode')) return false;
		$disabled = ini_get('disable_functions');
		if ($disabled) {
			$disabled = explode(',', $disabled);
			$disabled = array_map('trim', $disabled);
			return !in_array($func, $disabled);
		}
		return true;
	}

	private function WPPP_loaded_first () {
		if ( $plugins = get_option( 'active_plugins' ) ) {
			$key = array_search( $this->plugin_base, $plugins );
			return ( $key === 0 );
		}
	}

	function render() {
		$locale=get_locale();
		$Path = WP_LANG_DIR . '/' . $locale . '/LC_MESSAGES';
		$direxists = false;
		?>
		<div id="debug-bar-wppp">
			<h3>General</h3>
			<table class="widefat">
				<tr>
					<th scope="row">WPPP loaded first?</th>
					<td><?php echo $this->WPPP_loaded_first()===false ? 'No' : 'Yes'; ?></td>
				</tr>
			</table>
			
			<h3>Textdomains</h3>
			<table class="widefat">
				<thead>
					<tr>
						<th>textdomain</th>
						<th>mofile</th>
						<th>caller</th>
						<th>override</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$odd = false;
					foreach ($this->textdomains as $td) {
						$odd = !$odd;
						$mo_class = NULL;
						?>
						<tr <?php echo $odd ? 'class="alternate" ' : ' '; ?> >
							<td><?php echo $td['domain']; ?></td>
							<td><?php echo substr ( $td['mofile'], strlen ( ABSPATH . 'wp-content' ) ); ?></td>
							<td><code><?php echo $this->get_caller( $td['caller'] ); ?></code></td>
							<td><code><?php 
								if ( is_string( $td['override'] ) ) {
									echo $td['override'];
								} else {
									$mo_class = $td['override'];
									if ( $mo_class instanceof MO_dynamic_Debug )
										// Hide use of ...Debug class from user, as it doesn't matter and possibly confuses
										echo get_parent_class ( $mo_class );
									else
										echo get_class( $mo_class ); 
								}
							?></code></td>
						</tr>
						<?php
						if ($mo_class instanceof MO_dynamic_Debug) { ?>
							<tr <?php echo $odd ? 'class="alternate" ' : ' '; ?> >
								<td colspan="4">
									<span class="description">
										translate calls: <strong><?php echo $mo_class->translate_hits; ?></strong> - 
										translate_plural calls: <strong><?php echo $mo_class->translate_plural_hits; ?></strong> - 
										translation searches: <strong><?php echo $mo_class->search_translation_hits; ?></strong>
										&nbsp;(all values estimates, real values might be higher)
									</span>
								</td>
							</tr>
						<?php
						}
					}
					?>
				</tbody>
			</table>

			<h3>Native gettext support</h3>
			<table class="widefat">
				<tr class="alternate">
					<th scope="row">OS</th>
					<td><?php echo php_uname(); ?></td>
				</tr>
				</tr>
				<tr>
					<th scope="row">PHP gettext extension is</th>
					<td><?php echo extension_loaded('gettext') ? 'Available' : 'Not available'; ?></td>
				</tr>
				<tr class="alternate">
					<th scope="row">WordPress locale</th>
					<td><?php echo $locale; ?></td>
				</tr>
				<tr>
					<th scope="row">LC_MESSAGES defined?</th>
					<td><?php echo defined( 'LC_MESSAGES' ) ? 'Yes' : 'No'; ?></td>
				</tr>
				<tr class="alternate">
					<th scope="row">System locales (LC_MESSAGES)</th>
					<td><?php
						if( !defined( 'LC_MESSAGES' ) )
							define( 'LC_MESSAGES', 6 );
						$l = setlocale (LC_MESSAGES, "0");
						echo join( '<br/>', explode( ';', $l ) );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">Putenv available?</th>
					<td><?php echo $this->isAvailable( 'putenv' ) ? 'Yes' : 'No'; ?></td>
				</tr>
				<tr class="alternate">
					<th scope="row">Locale writeable? (<?php  echo $locale . '.UTF-8'; ?>)</th>
					<td><?php echo ( setlocale (LC_MESSAGES, $locale . '.UTF-8' ) == $locale ) ? 'Yes' : 'No' ; ?></td>
				</tr>
				<tr>
					<th scope="row">Directory <code><?php echo $Path; ?></code></th>
					<td><?php
						if ( !is_dir ( $Path ) ) {
							if ( !wp_mkdir_p ( $Path ) ) {
								echo 'Does not exist and could not be created';
							} else {
								echo 'Created';
								$direxists = true;
							}
						} else {
							echo 'Exists';
							$direxists = true;
						}
						?>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
