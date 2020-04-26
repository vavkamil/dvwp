<?php

class SWP_Option_Toggle extends SWP_Option {


	/**
	* Default
	*
	* The default value for this input type="checkbox".
	*
	* @var bool $default
	*
	*/
	public $default = true;


	/**
	* The required constructor for PHP classes.
	*
	* @param string $name The display name for the toggle.
	* @param string $key The database key for the user setting.
	*/
	public function __construct( $name, $key ) {
		parent::__construct( $name, $key );
		$this->default = true;
	}


	public function register_available_values( $values ) {
		$values[$this->key] = array(
			'type'      => 'boolean',
			'values'    => array( true, false )
		);

		return $values;
	}


	/**
	* Creates ready-to-print HTML for the checkbox/toggle module.
	*
	* @return SWP_Option_Toggle $this The calling object, for method chaining.
	*/
	public function render_HTML() {
		//* Map the default boolean to on/off.
		$status = $this->default ? 'on' : 'off';

		if ( isset( $this->user_options[$this->key] ) ) :
			$status = $this->user_options[$this->key] === true ? 'on' : 'off';
		endif;

		$checked = $status === 'on' ? ' checked ' : '';

		$html = '<div class="sw-grid ' . $this->parent_size . ' sw-fit sw-option-container ' . $this->key . '_wrapper" ';
		$html .= $this->render_dependency();
		$html .= $this->render_premium();
		$html .= '>';

			$html .= '<div class="sw-grid ' . $this->size . '">';
				$html .= '<p class="sw-checkbox-label">' . $this->name . '</p>';
			$html .= '</div>';

			$html .= '<div class="sw-grid ' . $this->size . '">';
				$html .= '<div class="sw-checkbox-toggle" status="' . $status . '" field="#' . $this->key . '">';
					$html .= '<div class="sw-checkbox-on">' . __( 'ON', 'social-warfare' ) . '</div>';
					$html .= '<div class="sw-checkbox-off">' . __( 'OFF', 'social-warfare' ) . '</div>';
				$html .= '</div>';

				$html .= '<input type="checkbox" id="' . $this->key . '" class="sw-hidden" name="' . $this->key . '"' . $checked . '/>';
			$html .= '</div>';

		$html .= '</div>';

		$this->html = $html;

		return $html;
	}


	/**
	* Override parent method to make this boolean-specific.
	*
	* @param boolean $value The boolean value to set as default.
	* @return SWP_Option_Toggle $this The calling object, for method chaining.
	*/
	public function set_default( $value ) {
		if ( !is_bool( $value ) ||  !isset( $value ) ) {
			$this->_throw( 'Please provide a default value as a boolean.' );
		}

		return parent::set_default( $value );
	}
}
