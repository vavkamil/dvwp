<?php

/**
 * A button with a CTA. The immediate use case is for network authorizations.
 *
 */
class SWP_Option_Button extends SWP_Option {


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
	public function __construct( $name, $key, $class, $link, $new_tab = false ) {
		parent::__construct( $name, $key );
		$this->new_tab = $new_tab;
		$this->link = isset( $link ) ? $link : '';
		$this->class = isset( $class ) ? $class : '';

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
		$target = $this->new_tab ? 'target="_blank"' : '';

		$html = '<div class="sw-grid ' . $this->parent_size . ' sw-fit sw-option-container ' . $this->key . '_wrapper" ';
		$html .= $this->render_dependency();
		$html .= $this->render_premium();
		$html .= '>';

			$html .= '<div class="sw-grid ' . $this->size . '">';
				$html .= '<p class="sw-input-label">' . ucfirst( $this->key ) . '</p>';
			$html .= '</div>';

			$html .= '<div class="sw-grid ' . $this->size . '">';
				if ( !empty( $this->link ) ) {
					// Apply a wrapper anchor tag.
					$html .= '<a href="' . $this->link .'" class="' . $this->class . '" ' . $target .'>' ;
					$html .= '<div id="' . strtolower($this->key) . '" field="#' . $this->key . '">' . $this->name . '</div>';
					$html .= '</a>';
				}
				else {
					// Just show a button. Use the id to target it with JS.
					$html .= '<div id="' . strtolower($this->key) . '" class="' . $this->class . '" field="#' . $this->key . '">' . $this->name . '</div>';
				}
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
