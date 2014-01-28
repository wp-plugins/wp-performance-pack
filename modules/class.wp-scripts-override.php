<?php
/**
 * Override of WordPress WP_Scripts class
 *
 * Delays localization into print_extra_script.
 *
 * @author Björn Ahrens
 * @since 0.2.3
 */

 
class WP_Scripts_Override extends WP_Scripts {
	var $l10ns = array ();

	function print_extra_script( $handle, $echo = true ) {
		if ( isset( $this->l10ns[$handle] ) ) {
			$this->jit_localize ( $handle, $this->l10ns[$handle]['name'], $this->l10ns[$handle]['l10n'] );
			unset( $this->l10ns[$handle] );
		}

		return parent::print_extra_script( $handle, $echo );
	}

	/**
	 * Localizes a script
	 *
	 * Localizes only if the script has already been added
	 */
	function localize ( $handle, $object_name, $l10n) {
		if ( $handle === 'jquery' )
			$handle = 'jquery-core';

		if ( !isset( $this->registered[$handle] ) )
			return false;

		$this->l10ns[$handle] = array ('name' => $object_name, 'l10n' => $l10n);

		return true;
	}
	
	function jit_localize( $handle, $object_name, $l10n ) {
		if ( $handle === 'jquery' )
			$handle = 'jquery-core';

		if ( is_array($l10n) && isset($l10n['l10n_print_after']) ) { // back compat, preserve the code in 'l10n_print_after' if present
			$after = $l10n['l10n_print_after'];
			unset($l10n['l10n_print_after']);
		}

		if ( $l10n instanceof LabelsObject ) {
			$jit_l10n = array();
			foreach ( $l10n as $key => $value ) {
				if ( !is_scalar($value) )
					$jit_l10n[$key] = $value;
				else
					$jit_l10n[$key] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8');
			}
			$script = "var $object_name = " . json_encode($jit_l10n) . ';';
		} else {
			foreach ( (array) $l10n as $key => $value ) {
				if ( !is_scalar($value) )
					continue;
				$jit_l10n[$key] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8');
			}
			$script = "var $object_name = " . json_encode($l10n) . ';';
		}

		if ( !empty($after) )
			$script .= "\n$after;";

		$data = $this->get_data( $handle, 'data' );

		if ( !empty( $data ) )
			$script = "$data\n$script";

		return $this->add_data( $handle, 'data', $script );
	}
}
