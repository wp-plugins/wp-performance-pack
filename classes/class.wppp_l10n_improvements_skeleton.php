<?php

class WPPP_L10n_Improvements_Skeleton extends WPPP_Module_Skeleton {

	protected static $options_default = array(
		'use_mo_dynamic' => true,
		'use_jit_localize' => false,
		'disable_backend_translation' => false,
		'dbt_allow_user_override' => false,
		'dbt_user_default_translated' => false,
		'use_native_gettext' => false,
		'mo_caching' => false,
	);

	public function get_default_options () { return static::$options_default; }

	public function is_active () {
		return ( $this->wppp->options['use_mo_dynamic']
				|| $this->wppp->options['use_jit_localize']
				|| $this->wppp->options['disable_backend_translation']
				|| $this->wppp->options['use_native_gettext'] );
	}

	public function is_available () { return true; }

	public function spawn_body () { return new WPPP_L10n_Improvements( $this->wppp ); }

	public function validate_options( &$input, $output ) {
		$defopts = $this->get_default_options();
		foreach ( $defopts as $key => $value ) {
			if ( isset( $input[$key] ) ) {
				$output[$key] = ( $input[$key] == 'true' ? true : false );
				unset( $input[$key] );
			} else {
				$output[$key] = $value;
			}
		}
		return $output;
	}
}

?>