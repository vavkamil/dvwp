<?php

/**
 * SWP_Ooption_Select: The class used to create select options.
 *
 * This class is used to create each select option needed on the options page.
 *
 * @since  3.0.0   | Created | 02 MAR 2017
 * @access public
 *
 */
class SWP_Option_Select extends SWP_Option {


	/**
	* Choices
	*
	* Contains a key->value array designating the available
	* options that the plugin user can select from the select
    * dropdown box.
	*
	* @var array
	*
	*/
	public $choices = array();


    /**
    * The required constructor for PHP classes.
    *
    * @param string $name The display name for the toggle.
    * @param string $key The database key for the user setting.
    *
    */
    public function __construct( $name, $key ) {
        parent::__construct( $name, $key );


        $this->choices = array();
    }


    public function register_available_values( $values ) {
        $values[$this->key] = array(
            'type'  => 'select',
            'values'    => $this->choices
        );

        return $values;
    }


    /**
    * Add an option to the select.
    *
    * Additional addons may want to expand the choices available for
    * a given option.
    *
    * @since 3.0.0 | 02 MAR 2018 | Created
    * @param string $choice The choice to add to the select.
    * @return SWP_Option_Select $this The calling object with an updated chocies array.
    */
    public function add_choice( $choice ) {
        if ( !is_string( $choice ) ) {
            $this->_throw( "Please provide a choice to add to the select. The choice must be passed as a string." );
        }

        array_push( $this->choices, __( $choice, 'social-warfare' ) );

        return $this;
    }


    /**
    * Create the options for a select dropdown.
    *
    * @since 3.0.0 | 02 MAR 2018 | Created
    * @param array $choices Array of strings to be translated and made into options.
    * @return SWP_Option_Select $this The calling instance, for method chaining.
    *
    */
    public function add_choices( $choices )  {

        if ( !is_array( $choices ) ) {
            $this->_throw( "Please provide an array of choices. If you want to add a single choice, use add_choice()." );
        }

        foreach( $choices as $choice ) {
            $this->add_choice( $choice );
        }

        return $this;
    }

    /**
    * Render the HTML
    *
    * Renders the HTML to the options page based on what
    * the properties of this object have been set to.
    *
    * @since 3.0.0 | 02 MAR 2018 | Created
    * @param none
    * @return string The rendered HTML of this option.
    * @TODO: Make this method render soem HTML.
    *
    */
    public function render_HTML() {
        $html = '<div class="sw-grid ' . $this->parent_size . ' sw-option-container ' . $this->key . '_wrapper" ';
        $html .= $this->render_dependency();
        $html .= $this->render_premium();
        $html .= '>';

            $html .= '<div class="sw-grid ' . $this->size . '">';
                $html .= '<p class="sw-input-label">' . $this->name . '</p>';
            $html .= '</div>';

            $html .= '<div class="sw-grid ' . $this->size . ' ">';

                $html .= $this->render_HTML_element();

            $html .= '</div>';
        $html .= '</div>';

        $this->html = $html;

        return $html;
    }

    /**
    * Renders just the <select> part of the HTML.
    *
    * Pulled out from render_HTML for SWP_Section_HTML.
    *
    * @return string $html The fully qualified HTML for a select.
    */
    public function render_HTML_element() {
        $value = $this->get_value();


        if ( isset( $value) ) :
            //* As of 4-24-18, 'active_networks' is the only array.
            $value = is_array( $value ) ? '' : $value;
        else:
            $value = $this->default;
        endif;

        $html = '<select name=' . $this->key . '>';

        foreach ( $this->choices as $key => $display_name ) {
            $selected = selected( $key, $value, false );
            $html .= '<option value="' . $key . '"' . $selected .  ' >' . $display_name . '</option>';
        }

        $html .= '</select>';

        return $html;
    }


	/**
	* A method for setting the available choices for this option.
	*
	* Accepts a $key->value set of options which will later be used to
	* generate the select dropdown boxes from which the plugin user can select.
	*
	* This method will overwrite any existing choices previously set. If you
	* want to add a choice, use add_choice() or add_choices() instead.
	*
	* @since 3.0.0 | 02 MAR 2018 | Created
	* @param array $choices
	* @return object $this Allows for method chaining
	*
	*/
    public function set_choices( $choices )  {
        if ( !is_array( $choices ) ) :
            $this->_throw( "You must provide an array of choices to go into the select." );
        endif;

        $this->choices = $choices;

        return $this;
    }


    /**
    * Defines the default value among this select's choices.
    *
    *
    * @param string $value The key associated with the default option.
    * @return SWP_Option_Select $this The calling instance, for method chaining.
    *
    */
    public function set_default( $value ) {
        if ( is_bool( $value ) || is_numeric( $value ) ) :
            settype( $value, 'string' );
        endif;

        if ( !is_string( $value )  ) :
            $this->_throw( 'Please provide a default value as a string.' );
        endif;

        return parent::set_default( $value );
    }

}
