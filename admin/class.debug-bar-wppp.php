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
	var $textdomains = array ();

	private function get_caller ( $stacktrace ) {
		static $excludes = array (
			'call_user_func_array',
			'load_textdomain_override',
			'apply_filters',
			'load_textdomain',
			'load_theme_textdomain',
			'load_plugin_textdomain',
		);
		for ( $i = 0, $max = count( $stacktrace ); $i < $max; $i++) {
			if ( !in_array ( $stacktrace[$i]['function'], $excludes ) ) {
				if ( isset( $stacktrace[$i]['class'] ) ) {
					return $stacktrace[$i]['class'] . $stacktrace[$i]['type'] . $stacktrace[$i]['function'];
				} else {
					return $stacktrace[$i]['function'];
				}
			}
		}
	}

	function init() {
		$this->title( __('WP Performance Pack', 'wppp') );
	}

	static function isAvailable($func) {
		if (ini_get('safe_mode')) return false;
		$disabled = ini_get('disable_functions');
		if ($disabled) {
			$disabled = explode(',', $disabled);
			$disabled = array_map('trim', $disabled);
			return !in_array($func, $disabled);
		}
		return true;
	}

	function render() {
		$locale=get_locale();
		$Path = WP_LANG_DIR . '/' . $locale . '/LC_MESSAGES';
		$direxists = false;
		?>
		<div id="debug-bar-wppp">
			<h3>textdomains</h3>
			<table class="widefat">
				<thead>
					<tr>
						<th>textdomain</th>
						<th>mofile</th>
						<th>caller</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($this->textdomains as $td) {
						?>
						<tr>
							<td><?php echo $td['domain']; ?></td>
							<td><?php echo substr ( $td['mofile'], strlen ( ABSPATH . 'wp-content' ) ); ?></td>
							<td><code><?php echo $this->get_caller( $td['caller'] ); ?></code></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>

			<h3>native gettext support</h3>
			<table class="widefat">
				<tr>
					<th scope="row">PHP gettext extension is</th>
					<td><?php echo extension_loaded('gettext') ? 'Available' : 'Not available'; ?></td>
				</tr>
				<tr>
					<th scope="row">WordPress locale</th>
					<td><?php echo $locale; ?></td>
				</tr>
				<tr>
					<th scope="row">LC_MESSAGES defined</th>
					<td><?php echo defined( 'LC_MESSAGES' ) ? 'Yes' : 'No'; ?></td>
				</tr>
				<tr>
					<th scope="row">System locales (LC_MESSAGES)</th>
					<td><?php
						$l = setlocale (LC_MESSAGES, "0");
						echo join( '<br/>', explode( ';', $l ) );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">Putenv available</th>
					<td><?php echo self::isAvailable( 'putenv' ) ? 'Yes' : 'No'; ?></td>
				</tr>
				<tr>
					<th scope="row">Locale writeable (<?php  echo $locale . '.UTF-8'; ?>)</th>
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
