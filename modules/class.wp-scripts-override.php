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
			parent::localize ( $handle, $this->l10ns[$handle]['name'], $this->l10ns[$handle]['l10n'] );
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
}
