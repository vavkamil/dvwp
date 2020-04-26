<?php

/**
 * SWP_Abstract: The abstract class used for all other options classes.
 *
 * @package   SocialWarfare\Functions\Utilities
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.0.0  | 02 MAR 2018 | Created
 * @since     3.1.0 | 14 JUN 2018 | Added the set_key() method.
 * @access    public
 *
 */
class SWP_Option_Abstract {


	/**
	 * SWP_Debug_Trait provides useful tool like error handling.
	 *
	 */
	use SWP_Debug_Trait;


	/**
	* Name
	*
	* The name of this option. This is a "pretty" name that the plugin user will see.
	*
	* @var string
	*
	*/
	public $name;


	/**
	* Type
	*
	* The type property refers to the type of option this represents (e.g. input,
	* textarea, checkbox, etc.)
	*
	* @var string
	*
	*/
	public $type;


	/**
	* Default
	*
	* The default property refers to the default value for this option. This is
	* what the option will be set to until the user changes it.
	*
	* @var mixed This var is dependant on what type of option is being generated.
	*
	*/
	public $default;


	/**
	* Premium
	*
	* This property determines whether or not this option is a premium option. By
	* default this property is set to false. The set_premium() method can be called
	* to change this property. When called, the set_premium() method will accept a
	* string corresponding to the registration key of the premium plugin on which
	* this option relies. It will set the $premium_addon property to that string and
	* switch this property to true.
	*
	* @var bool
	*
	*/
	public $premium = false;

	/**
	 *  Addon
	 *
	 * This propety is set iff $premium === true. The value of $addon is the
	 * code for the corresponding addon. Permissable values are:
	 *
	 * pro
	 *
	 * @var string
	 *
	 */
	public $addon = '';


	/**
	* Priority
	*
	* The priority property is used to determine the order in which the options are
	* presented to the user. These options will be sorted prior to the rendering of
	* the HTML in ascending order. That is to say, an option with a priority of 10
	* will appear before an option with a priority of 20.
	*
	* @var integer
	*
	*/
	public $priority;


	/**
	 * The Construct Method
	 *
	 * Pull in the users options from the database and pull in the global network objects.
	 *
	 * @since 3.0.0 | 24 APR 2018 | Created
	 * @param string $name The name of the option
	 * @return none
	 *
	 */
	public function __construct( $name ) {
		$this->set_name( $name );
		$this->user_options = get_option( 'social_warfare_settings' );

		add_action('plugins_loaded', array( $this , 'load_social_networks' ) , 1000 );
	}


	/**
	 * A function to pull the global social networks into a local property.
	 *
	 * @since  3.0.0 | 24 APR 2018 | Created
	 * @param  none
	 * @return none
	 *
	 */
	public function load_social_networks() {
		global $swp_social_networks;
		$this->networks = $swp_social_networks;
	}


	public function get_property( $property ) {
		if ( property_exists( __CLASS__, $property ) ) {
			return $this->$property;
		}

		$this->_throw("Property $property does not exist in " . __CLASS__ . "." );
	}


	public function get_all_networks() {
		return $this->networks;
	}


	public function get_user_icons() {
		// $options = get_option( 'social_warfare_settings' );
		$user_icons = SWP_Utility::get_option( 'order_of_icons' );

		if ( false === $user_icons ) {
			return array(
				'google_plus' => 'google_plus',
				'twitter'     => 'twitter',
				'facebook'    => 'facebook',
				'linkedin'    => 'linkedin',
				'pinterest'   => 'pinterest'
			);
		}

		// For legacy code below 2.3.5
		if ( is_array( $user_icons ) && array_key_exists( 'active', $user_icons) ) :
			return $user_icons['active'];
		endif;

		return $user_icons;
	}


	/**
	 * Set the Name
	 *
	 * @since  3.0.0 | 25 APR 2018 | Created
	 * @param  string $name The name of this option.
	 * @return object $this Allows method chaining.
	 *
	 */
	public function set_name( $name ) {
		if ( !is_string($name) ) {
			$this->_throw("Please provide a string for your object's name." );
		}

		$this->name = $name;

		return $this;
	}


	public function set_priority( $priority ) {
		if ( ! intval( $priority ) || $priority < 1) {
			$this->_throw("Requires an integer greater than 0.");
		}

		$this->priority = $priority;

		return $this;
	}


	/**
	* Creates a Javscript selector keyname  based on the object's name.
	*
	* @param string $name The name to be converted to a key. Usually the objects name.
	* @return string $key A valid PHP and jQuery target keyname.
	*/
	public function name_to_key( $name ) {
		if ( !is_string( $name ) ) :
			$this->_throw( 'Please provide a string to get a key.' );
		endif;

		//* Remove all non-word character symbols.
		$key = preg_replace( '#[^\w\s]#i', '', $name );

		//* Replace spaces with underscores.
		$key = preg_replace( '/\s+/', '_', $name );


		return strtolower( $key );
	}


	/**
	* Set the premium status of the object.
	*
	* Since there are going to be multiple addons, it's not sufficient to set premium to simply true or
	* false. Instead, it will be false by default. Unless this method is called and a string corresponding
	* the registration key of the corresponding premium addon is passed. Example: $SWP_Option->set_premium('pro');
	*
	* This will then set the premium property to true and place the registration key into the premium_addon property.
	*
	* This method does not need to be called unless it is a premium option.
	*
	* @since 3.0.0 | 02 MAR 2018 | Created
	* @param string String corresponding to the registration key of premium plugin if true.
	* @return $this Return the object to allow method chaining.
	*
	*/
	public function set_premium( $premium_addon ) {
		if ( !is_string( $premium_addon ) ) {
			$addons = [ 'pro' ];
			$addon_string = PHP_EOL;

			foreach( $addons as $addon ) {
				$addon_string . $addon . PHP_EOL;
			}
			$this->_throw( "Please provide a string that is one of the following: " . var_export($addons ) );
		}

		$this->premium = $premium_addon;

		return $this;
	}


	public function get_priority_map( $object) {

		return array_values( $this->object_to_array( $object ) );
	}


	public function object_to_array ( $object ) {
		if(!is_object($object) && !is_array($object)):
			return $object;
		endif;

		return array_map( [$this, 'object_to_array'], (array) $object);
	}


	/**
	* Sorts all core, premium, and third-party items by their designated priority.
	*
	* This is pretty hacky.
	* Ideally, the code would be pure as demonstrated in the andrewbaxter link below.
	* However, because we use objects with named keys to store our data, we can iterate
	* the objects as a numeric index. (E.g., $array[0] throws an error).
	* To resolve this, we have to
	*/
	//* Logic: http://interactivepython.org/runestone/static/pythonds/SortSearch/TheQuickSort.html
	//* Code: http://andrewbaxter.net/quicksort.php
	public function sort_by_priority( $object ) {

		if (is_object($object)) {
			$array = $this->get_priority_map( $object) ; //get_object_vars($object);
		} else {
			$array = $object;
		}

		$length =  count( $array );

		if ( $length < 2 ) {
			return $array;
		}

		if ( $length === 2 ) :
			$first;
			$second;
			$index = 0;

			foreach( $array as $name => $object) {
				if ( $index === 2) break;

				if ( $index === 0) {
					$first = $object;
				} else {
					$second = $object;
				}

				$index++;
			}

			if ($first['priority'] > $second['priority']) {
				return [$second, $first];
			}

			return [$first, $second];
		endif;

		$left = $right = array();

		$pivot = $array[0];

		for ($i = 1; $i < $length; $i++) {
			$item = $array[$i];

			$item['priority'] < $pivot['priority'] ? $left[] = $item : $right[] = $item;
		}

		return array_merge( $this->sort_by_priority($left), [$pivot], $this->sort_by_priority($right) );
	}


	/**
	* Adds the SWP dependency attributes, if this object has a dependency set.
	*
	* @return string The HTML attributes if the object has dependency, or an empty string.
	*/
	protected function render_dependency() {
		if ( !empty( $this->dependency) ) :
			return ' data-dep="' . $this->dependency->parent . '" data-dep_val=\'' . json_encode($this->dependency->values) . '\'';
		endif;

		return ' ';
	}


	/**
	* Adds the SWP premium attributes, if this object is premium.
	*
	* @return string The HTML attribute if the object has dependency, or an empty string.
	*/
	protected function render_premium() {
		return;
		if ( isset( $this->premium ) ) :
			return ' premium="true" ';
		endif;

		return ' ';
	}


	/**
	* Sets the key used by dependent sections and options.
	*
	* @since 3.0.0  | 01 MAR 2018 | Created
	* @since 3.1.0 | 14 JUN 2018 | Migrated from child class to here.
	* @param string $key The unique key being assigned to this section.
	* @return SWP_Options_Page_Section $this The updated object.
	*
	*/
	public function set_key( $key ) {
		if ( !is_string($key) ) {
			$this->_throw("Please provide a string for your object's key." );
		}

		$this->key = $key;

		return $this;
	}
}
