<?php

/**
 * The parent class for all Option_X types.
 *
 * This class is used to create each individual option throughout the options page.
 * It provides the framework for each of type of option that is available: input,
 * select, checkbox, and textarea. Each of these options is instantiated through
 * their respective class and then added to the option page section.
 *
 * @package   SocialWarfare\Functions\Social-Networks
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.0.0   | Created | 02 MAR 2017
 * @access    public
 *
 */
class SWP_Option extends SWP_Option_Abstract {


    /**
    * The type of HTML input element.
    *
    * Valid types are:
    * text, select, checkbox, textarea
    *
    * @var string $type
    *
    */
    public $type;


    /**
    * The CSS class representing the size (width) of the input.
    *
    * @see set_size()
    * @var string $size
    *
    */
    public $size;


    /**
    * The key for this option in the database.
    *
    * @var string $key
    *
    */
    public $key;


    /**
    * The default value for the given input.
    *
    * @var mixed $default. See the corresponding class's set_default() method.
    *
    */
    public $default = false;


    /**
    * The string of HTML which creates the element.
    *
    * @var string $html
    *
    */
    public $html;


    /**
    * Boolean indicating whether the plugin is registered or not.
    *
    * @var bool $swp_registration
    *
    */
    public $swp_registration;


    /**
    * The required constructor for PHP classes.
    *
    * @since  3.0.0 | 02 MAR 2018 | Created
    * @param  string $name The display name for the toggle.
    * @param  string $key The database key for the user setting.
    * @return void
    *
    */
    public function __construct( $name, $key ) {
        parent::__construct( $name );

        $this->swp_registration = true;
        $this->set_key( $key );
        $this->parent_size = ' sw-col-940 ';
    }

    /**
     * Child classes will add a call to apply_filters( 'swp_options_page_values')
     * to add their own values as valid options.
     *
     */
     public function register_available_values( $values ) {
         $values[$this->key] = array(
             'type' => 'none',
             'values'   => array()
         );

         return $values;
     }


    /**
    * Fetches the css class to match a given size given as a string.
    *
    * @since  3.0.0 | 02 MAR 2018 | Created
    * @param  string $size Optional: The size of the element using SWP sizing.
    * @return object $this Allows for method chaining.
    *
    */
    protected function get_css_size( $size = '' ) {
        $size = '' === $size ? $this->size : $size;

        $map = [
            'two-fourths'   => ' sw-col-460 ',
            'two-thirds'    => ' sw-col-300 ',
            'four-fourths'  => ' sw-col-620 ',
        ];

        if ( empty($size) ) :
            return $map['two-thirds'];
        endif;

        return $map[$size];
    }


    /**
    * Get the pre-defined value of the option.
    *
    * @since  3.0.0 | April 15 2018 | Created
    * @param  void
    * @return mixed The current value of this option.
    *
    */
    protected function get_value() {
        if ( isset($this->value) ) {
            return $this->value;
        }

        if ( false != SWP_Utility::get_option( $this->key ) ) {
            return SWP_Utility::get_option( $this->key );
        }

        return $this->default;
    }


    /**
    * Creates HTML based on the option's properties and user settings.
    *
    * @since  3.0.0 | 02 MAR 2018 | Created
    * @param  void
    * @return void
    *
    */
    public function render_HTML() {
        //* Intentionally left blank.
        //* Each child class should override this method.
        $this->_throw( "Should not be called from the parent class." );
    }


    /**
    * Set the default value of this option. This value will be used until the plugin user changes the value
    * to something else and saves the options.
    *
    * @since  3.0.0 | 02 MAR 2018 | Created
    * @param  mixed The default value will vary based on the kind of option being generated.
    * @return object $this Allows for method chaining.
    *
    */
    public function set_default( $value ) {
        global $swp_user_options;
        $this->default = $value;

        // Add this to our global list of defaults
        add_filter( 'swp_options_page_defaults', array( $this , 'register_default' ) );
        add_filter( 'swp_options_page_values', array( $this, 'register_available_values' ) );

        return $this;
    }


    /**
     * Register Default
     *
     * Add this to a global list of defaults so that if an option isn't set in the database,
     * then the method that pulls out the user option can just fall back to using this option's
     * default value.
     *
     * @since  3.0.0 | 24 APR 2018 | Created
     * @param  array  $defaults The array of defaults
     * @return array  $defaults The modifed array of defaults.
     *
     */
    public function register_default( $defaults = array() ) {
        $defaults[$this->key] = $this->default;
        return $defaults;
    }


    /**
    * Force a child option to depend on a parent option.
    *
    * If the parent's value is one of the values passed in as $values,
    * the option will be visible ont the Settings page. Otherwise, the option
    * is hidden until the dependency is set to that value.
    *
    * @since  3.0.0 | 02 MAR 2018 | Created
    * @param  string $parent The parent option's key.
    * @param  array $values Values which enable this option to exist.
    * @return object $this Allows for method chaining.
    *
    */
    public function set_dependency( $parent, $values ) {
        if ( !is_string( $parent ) ) {
            $this->_throw( 'Argument $parent needs to be a string matching the key of another option.' );
        }

        if ( !isset( $values) ) {
            $this->_throw( 'Dependency values must passed in as the second argument.' );
        }

        if ( !is_array( $values ) ) {
            $values = array( $values );

        }

        $this->dependency = new stdClass();
        $this->dependency->parent = $parent;
        $this->dependency->values = $values;

        return $this;
    }


    /**
    * Assign the database key for this element.
    *
    * @since  3.0.0 | 02 MAR 2018 | Created
    * @param  string $key The key which correlates to the input.
    * @return object $this The calling instance, for method chaining.
    *
    */
    public function set_key( $key ) {
        if ( !is_string( $key ) ) {
            $this->_throw( 'Please provide a key to the database as a string.' );
        }

        $this->key = $key;

        return $this;
    }


    /**
    * Some option types have multiple sizes that will determine their visual layout on the option
    * page. This setter allows you to declare which one you want to use.
    *
    * @since  3.0.0 | 02 MAR 2018 | Created
    * @param  string The size of the option on the page (e.g. 'two-thirds').
    * @return object $this The calling instance, for method chaining.
    *
    */
    public function set_size( $size, $parent_size = ' sw-col-940 ') {

        if ( 0 !== strpos( $size, 'sw-col' ) ) {
            $sizes = PHP_EOL;


            $this->_throw( "Please enter a valid size. The string must begin with 'sw-col-', followed by either: 300, 460, or 620.'" );
        }

        $this->size = $size;
        $this->parent_size = $parent_size;

        return $this;
    }


    /**
     * Defines the placeholder for text inputs.
     *
     * @since  3.0.0 | 02 MAR 2018 | Created
     * @param  string $placeholder The text to display as a placeholder.
     * @return object $this The calling instance, for method chaining.
     *
     */
    public function set_placeholder( $placeholder ) {
        if (!is_string( $placeholder ) && !is_numeric( $placeholder ) ) :
            $this->_throw( "Please set a string or number for the placeholder." );
        endif;

        $this->placeholder = $placeholder;

        return $this;
    }


    /**
     * Creates the HTML placeholder attribute if a placeholder is defined.
     *
     * @since  3.0.0 | 02 MAR 2018 | Created
     * @param  void
     * @return string $placeholder The qualified HTML placeholder attribute.
     */
    public function render_placeholder() {
        if ( empty( $this->placeholder) ) :
            return "";
        endif;

        return ' placeholder="' . $this->placeholder . '"';

    }

}
