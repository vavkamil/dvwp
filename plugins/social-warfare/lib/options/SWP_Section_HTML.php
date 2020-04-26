<?php

/**
* For creating markup that does not fit into the existing options.
*
* This extends SWP_Option rather than SWP_Section because it uses many of the
* same methods as an option and is a child of a section, even though this is
* neither necessarily an option or a section.
*
* @package   SocialWarfare\Functions\Social-Networks
* @copyright Copyright (c) 2018, Warfare Plugins, LLC
* @license   GPL-3.0+
* @since     3.0.0 | 01 MAR 2018 | Created
*
*/
class SWP_Section_HTML extends SWP_Option {


	/**
	* HTML
	*
	* The non-conformant markup this object represents. Most of the sections and
	* options can be created using one of the existing SWP_{Item} classes.
	* Sometimes we need something that does not fit those boxes. This class
	* provides native methods for a few of those cases, and an add_HTML() method
	* for everything else.
	*
	* @var string $html
	*
	*/
	public $html = '';


	/**
	* The required constructor for PHP classes.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  string $name Required: An arbitrary name, except for do_bitly_authentication_button
	* @param  string $key  Optional: If the object requires access beyond itself,
	*                      pass it a key. Otherwise $name will be used.
	* @return void
	* @see    $this->do_bitly_authentication_button()
	*
	*/
	public function __construct( $name, $key = null ) {
		$key = $key === null ? $name : $key;

		parent::__construct( $name, $key );

		if (empty( $this->default ) ) :
			$this->set_default( ' ');
		endif;

		$this->html = '';
	}


	/**
	* Allows custom HTML to be added.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  string $html Required: The fully qualified, ready-to-print HTML to display.
	* @return object $this This object for method chaining.
	*
	*/
	public function add_HTML( $html ) {
		if ( !is_string( $html) ) :
			$this->_throw( 'This requires a string of HTML!' );
		endif;

		$this->html .= $html;

		return $this;
	}


	/**
	 * A method for creating the admin sidebar HTML.
	 *
	 * @since  3.0.0 | 01 MAR 2018 | Created
	 * @param  void
	 * @return string The compiled HTML returned as a string.
	 *
	 */
	public function do_admin_sidebar() {
		$status_title =  __( 'Press Ctrl+C to Copy this information.' , 'social-warfare' );

		//* This is an array of fully qualified HTML strings, ready to print.
		$cache = get_option( 'swp_json_cache' );
		$html = '<div class="sw-admin-sidebar sw-grid sw-col-220 sw-fit">';
		$html .= '<div id="swp-admin-sidebar">';

		if ( isset( $cache['sidebar'] ) ) :
			foreach( $cache['sidebar'] as $component ) {
				$component = html_entity_decode ( $component );
				$html .= html_entity_decode( $component);
			}
		endif;

		$html .= '<div class="system-status-wrapper">';
			$html .= '<h4><?= $status_title ?></h4>';
			$html .= '<div class="system-status-container"> '. $this->system_status() . '</div>';
		$html .= '</div></div>';

		return $this->html = $html;
	}


	/**
	 * A method for compiling the system status html.
	 *
	 * @since  3.0.0 | 01 MAR 2018 | Created
	 * @param  void
	 * @return string The compiled html returned as a string.
	 *
	 */
	private function system_status() {
		/**
		 * System Status Generator
		 */
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		$pluginList = '';

		foreach ( $plugins as $plugin ) :
			$pluginList .= '<tr><td><b>' . $plugin['Name'] . '</b></td><td>' . $plugin['Version'] . '</td></tr>';
		endforeach;

		if ( function_exists( 'fsockopen' ) ) :
			$fsockopen = '<span style="color:green;">Enabled</span>';
		else :
			$fsockopen = '<span style="color:red;">Disabled</span>';
		endif;

		if ( function_exists( 'curl_version' ) ) :
			$curl_version = curl_version();
			$curl_status = '<span style="color:green;">Enabled: v' . $curl_version['version'] . '</span>';
		else :
			$curl_status = '<span style="color:red;">Disabled</span>';
		endif;

		$theme = wp_get_theme();

		$system_status = '
			<table style="width:100%;">
				<tr><td><h2>Environment Statuses</h2></td><td></td></tr>
				<tr><td><b>Home URL</b></td><td>' . get_home_url() . '</td></tr>
				<tr><td><b>Site URL</b></td><td>' . get_site_url() . '</td></tr>
				<tr><td><b>WordPress Version</b></td><td>' . get_bloginfo( 'version' ) . '</td></tr>
				<tr><td><b>PHP Version</b></td><td>' . phpversion() . '</td></tr>
				<tr><td><b>WP Memory Limit</b></td><td>' . WP_MEMORY_LIMIT . '</td></tr>
				<tr><td><b>Social Warfare Version</b></td><td>' . SWP_VERSION . '</td></tr>
				<tr><td><h2>Connection Statuses</h2></td><td></td></tr>
				<tr><td><b>fsockopen</b></td><td>' . $fsockopen . '</td></tr>
				<tr><td><b>cURL</b></td><td>' . $curl_status . '</td></tr>
				<tr><td><h2>Plugin Statuses</h2></td><td></td></tr>
				<tr><td><b>Theme Name</b></td><td>' . $theme['Name'] . '</td></tr>
				<tr><td><b>Theme Version</b></td><td>' . $theme['Version'] . '</td></tr>
				<tr><td><b>Active Plugins</b></td><td></td></tr>
				<tr><td><b>Number of Active Plugins</b></td><td>' . count( $plugins ) . '</td></tr>
				' . $pluginList . '
			</table>
			';

		return $system_status;
	}


	/**
	 * A method for compiling the tweet count registration html.
	 *
	 * @since  3.0.0 | 01 MAR 2018 | Created
	 * @param  void
	 * @return string The compiled html returned as a string.
	 *
	 */
	public function do_tweet_count_registration() {
		// Check for a default value
		if ( true == SWP_Utility::get_option( 'twitter_shares' ) ) :
			$status = 'on';
			$selected = 'checked';
		else:
			$status = 'off';
			$selected = '';
		endif;

		$this->default = false;
		$this->key = 'twitter_shares';

		$html = '<div class="sw-grid sw-col-940 sw-fit sw-option-container ' . $this->key . '_wrapper" ' . $this->render_dependency() . '>';

		// Begin Registration Wrapper
		$html .= '<div class="tweet-count-wrapper" registration="false">';

		// Open the IS NOT Activated container
		$html .= '<div class="sw-grid sw-col-940 swp_tweets_not_activated">';

		// The Warning Notice & Instructions
		$html .= '<p class="sw-subtitle sw-registration-text sw-italic">Step 1: <a style="float:none;" class="button sw-navy-button" href="http://twitcount.com" target="_blank">' . __( 'Click here to visit TwitCount.com' , 'social-warfare' ) . '</a><br />' . __( 'Step 2: Follow the prompts on their website to create an account and add your domain to be tracked for share counts. If you see a prompt to customize your button, ignore and click past that. You are not using their button, you are using ours.' , 'social-warfare' ) . '<br />' . __( 'Step 3: Flip the switch below to "ON", select which tracking service the plugin should use, then save your changes.' , 'social-warfare' ) . '</p>';

		// Close the IS NOT ACTIVATED container
		$html .= '</div>';

		// Checkbox Module
		$html .= '<div class="sw-grid sw-col-300"><p class="sw-checkbox-label">Tweet Counts</p></div>';
		$html .= '<div class="sw-grid sw-col-300">';
		$html .= '<div class="sw-checkbox-toggle" status="' . $status . '" field="#twitter_shares"><div class="sw-checkbox-on">' . __( 'ON' , 'social-warfare' ) . '</div><div class="sw-checkbox-off">' . __( 'OFF' , 'social-warfare' ) . '</div></div>';
		$html .= '<input type="checkbox" class="sw-hidden" name="twitter_shares" id="twitter_shares" ' . $selected . ' />';
		$html .= '</div>';
		$html .= '<div class="sw-grid sw-col-300 sw-fit"></div>';

		// Close the Registration Wrapper
		$html .= '</div>';

		$html .= '</div>';

		$this->html = $html;

		return $html;
	}


	/**
	* Render the Bitly connection button on the Advanced tab.
	*
	* @see    https://dev.bitly.com/authentication.html
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return object $this The calling instance, for method chaining.
	*
	*/
	public function do_bitly_authentication_button() {


		if ( SWP_Utility::get_option('bitly_access_token') ) {

			//* Display a confirmation button. On click takes them to bitly settings page.
			$text = __( 'Connected', 'social-warfare' );
			$text .= " for:<br/>" . SWP_Utility::get_option( 'bitly_access_login' );
			$color = 'sw-green-button';
			$link = 'https://app.bitly.com/bitlinks/?actions=accountMain&actions=settings&actions=security';
			$target = "_blank";

		} else {

			//* Display the button, which takes them to a Bitly auth page.
			$text = __( 'Authenticate', 'social-warfare' );
			$color = 'sw-navy-button';
			$target = "";

			//* The base URL for authorizing SW to work on a user's Bitly account.
			$link = "https://bitly.com/oauth/authorize";

			//* client_id: The SWP application id, assigned by Bitly.
			$link .= "?client_id=96c9b292c5503211b68cf4ab53f6e2f4b6d0defb";

			//* state: Optional state to include in the redirect URI.
			$link .= "&state=" . admin_url( 'admin-ajax.php' );

			//* redirect_uri: The page to which a user is redirected upon successfully authenticating.
			$link .= "&redirect_uri=https://warfareplugins.com/bitly_oauth.php";
		}

		ob_start() ?>

			<div class="sw-grid sw-col-940 sw-fit sw-option-container <?= $this->key ?> '_wrapper" data-dep="bitly_authentication" data-dep_val="[true]">
				<div class="sw-grid sw-col-300">
					<p class="sw-authenticate-label"><?php __( 'Bitly Link Shortening', 'social-warfare' ) ?></p>
				</div>
				<div class="sw-grid sw-col-300">
					<a  target="<?= $target ?>" class="button <?= $color ?>" href="<?= $link ?>"><?= $text ?></a>
				</div>
				<div class="sw-grid sw-col-300 sw-fit"></div>
			</div>

		<?php

		$this->html = ob_get_contents();
		ob_end_clean();

		return $this;
	}


	/**
	* The buttons preview as shown on the Display tab.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return object $this The calling instance, for method chaining.
	*
	*/
	public function do_buttons_preview() {
		$style = 'swp_' . SWP_Utility::get_option( 'button_shape' );
		ob_start() ?>

		<div class="swp_social_panel <?= $style ?> swp_flat_fresh swp_default_full_color swp_individual_full_color swp_other_medium_gray" data-position="both" data-count="6" data-floatcolor="#ffffff" data-scale="1" data-align="full_width">
			<div class="nc_tweetContainer swp_share_button swp_google_plus" data-id="2">
				<a target="_blank" href="https://plus.google.com/share?url=http%3A%2F%2Fwfa.re%2F1W28voz" data-link="https://plus.google.com/share?url=http%3A%2F%2Fwfa.re%2F1W28voz" class="nc_tweet swp_share_link">
					<span class="iconFiller">
						<span class="spaceManWilly">
							<i class="sw swp_google_plus_icon"></i>
							<span class="swp_share"><?php __( '+1','social-warfare' ) ?></span>
						</span>
					</span>
					<span class="swp_count">1.2K</span>
				</a>
			</div>
			<div class="nc_tweetContainer swp_share_button swp_twitter" data-id="3">
				<a href="https://twitter.com/share?original_referer=/&text=Ultimate+Social+Share+%23WordPress+plugin%21+Beautiful%2C+super+fast+%26+more+http%3A%2F%2Fwarfareplugins.com+pic.twitter.com%2FA2zcCJwZtO&url=/&via=WarfarePlugins" data-link="https://twitter.com/share?original_referer=/&text=Ultimate+Social+Share+%23WordPress+plugin%21+Beautiful%2C+super+fast+%26+more+http%3A%2F%2Fwarfareplugins.com+pic.twitter.com%2FA2zcCJwZtO&url=/&via=WarfarePlugins" class="nc_tweet swp_share_link">
					<span class="iconFiller">
						<span class="spaceManWilly">
							<i class="sw swp_twitter_icon"></i>
							<span class="swp_share"><?php __( 'Tweet','social-warfare' ) ?></span>
						</span>
					</span>
					<span class="swp_count">280</span>
				</a>
			</div>
			<div class="nc_tweetContainer swp_share_button swp_pinterest" data-id="6">
				<a data-link="https://pinterest.com/pin/create/button/?url=https://warfareplugins.com/&media=https%3A%2F%2Fwarfareplugins.com%2Fwp-content%2Fuploads%2Fget-content-shared-735x1102.jpg&description=Customize+your+Pinterest+sharing+options%2C+create+easy+%22click+to+tweet%22+buttons+within+your+blog+posts%2C+beautiful+sharing+buttons+and+more.+Social+Warfare+is+the+ultimate+social+sharing+arsenal+for+WordPress%21" class="nc_tweet swp_share_link" data-count="0">
					<span class="iconFiller">
						<span class="spaceManWilly">
							<i class="sw swp_pinterest_icon"></i>
							<span class="swp_share"><?php __( 'Pin','social-warfare' ) ?></span>
						</span>
					</span>
					<span class="swp_count">104</span>
				</a>
			</div>
			<div class="nc_tweetContainer swp_share_button swp_facebook" data-id="4">
				<a target="_blank" href="https://www.facebook.com/share.php?u=http%3A%2F%2Fwfa.re%2F1W28vov" data-link="https://www.facebook.com/share.php?u=http%3A%2F%2Fwfa.re%2F1W28vov" class="nc_tweet swp_share_link">
					<span class="iconFiller">
						<span class="spaceManWilly">
							<i class="sw swp_facebook_icon"></i>
							<span class="swp_share"><?php __( 'Share','social-warfare' ) ?></span>
						</span>
					</span>
					<span class="swp_count">157</span>
				</a>
			</div>
			<div class="nc_tweetContainer swp_share_button swp_linkedin" data-id="5">
				<a target="_blank" href="https://www.linkedin.com/cws/share?url=http%3A%2F%2Fwfa.re%2F1W28twH" data-link="https://www.linkedin.com/cws/share?url=http%3A%2F%2Fwfa.re%2F1W28twH" class="nc_tweet swp_share_link">
					<span class="iconFiller">
						<span class="spaceManWilly">
							<i class="sw swp_linkedin_icon"></i>
							<span class="swp_share"><?php __( 'Share','social-warfare' ) ?></span>
						</span>
					</span>
					<span class="swp_count">51</span>
				</a>
			</div>
			<div class="nc_tweetContainer swp_share_button total_shares total_sharesalt" data-id="6" >
			<span class="swp_count">
				<span class="swp_label">Shares</span> 1.8K
			</span>
			</div>
		</div>

		<?php

		$this->html = ob_get_contents();
		ob_end_clean();

		return $this;
	}


	/**
	* Renders the three column table on the Display tab.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @since  3.0.4 | 09 MAY 2018 | Added check for is_numeric to avoid throwing errors.
	* @since  3.0.5 | 09 MAY 2018 | Switched to using an iterator. Many post types are
	*                               being returned with associative keys, not numeric ones.
	* @param  void
	* @return object $this The calling instance, for method chaining.
	*
	*/
	public function do_button_position_table() {
		$post_types = SWP_Utility::get_post_types();

		$panel_locations = [
			'above' => __( 'Above the Content', 'social-warfare' ),
			'below' => __( 'Below the Content',  'social-warfare' ),
			'both'  => __( 'Both Above and Below the Content', 'social-warfare' ),
			'none'  => __( 'None/Manual Placement', 'social-warfare' )
		];

		$float_locations = [
			'on'    => __( 'On','social_warfare'),
			'off'   => __( 'Off', 'social_warfare')
		];

		$html = '<div class="sw-grid sw-col-940 sw-fit sw-option-container" ';
		$html .= $this->render_dependency();
		$html .= $this->render_premium();
		$html .= '>';

		$html .= '<div class="sw-grid sw-col-300">';
			$html .= '<p class="sw-select-label sw-short sw-no-padding">' . __( 'Post Type' ,'social-warfare' ) . '</p>';
		$html .= '</div>';
		$html .= '<div class="sw-grid sw-col-300">';
			$html .= '<p class="sw-select-label sw-short sw-no-padding">' . __( 'Static Buttons' ,'social-warfare' ) . '</p>';
		$html .= '</div>';
		$html .= '<div class="sw-grid sw-col-300 sw-fit">';
			$html .= '<p class="sw-select-label sw-short sw-no-padding">' . __( 'Floating Buttons (If Activated)' ,'social-warfare' ) . '</p>';
		$html .= '</div>';

		//* Some indices are numeric, others are strings.
		$i = 0;
		foreach( $post_types as $index => $post ) {
			$i++;
			$priority = $i * 10;

			$html .= '<div class="sw-grid sw-col-940 sw-fit sw-option-container ' . $post . '_wrapper">';

				$html .= '<div class="sw-grid sw-col-300">';
					$html .= '<p class="sw-input-label">' . str_replace('_', ' & ', ucfirst($post)) . '</p>';
				$html .= '</div>';

				$html .= '<div class="sw-grid sw-col-300">';

					$panel = new SWP_Option_Select( 'Panel '. ucfirst( $post ), 'location_' . $post );
					$panel->set_priority( $priority )
						->set_size( 'sw-col-300' )
						->set_choices( $panel_locations )
						->set_default( 'both' );

					$html .= $panel->render_HTML_element();

				$html .= '</div>';
				$html .= '<div class="sw-grid sw-col-300 sw-fit">';

				if ( $post !== 'home' && $post !== 'archive_categories' ) :

					$float = new SWP_Option_Select( 'Float ' . ucfirst( $post ), 'float_location_' . $post );
					$float->set_priority( $priority + 5 )
						->set_size( 'sw-col-300' )
						->set_choices( $float_locations )
						->set_default( 'on' );

					$html .= $float->render_HTML_element();

				endif;

				$html .= '</div>';

			$html .= '</div>';

		}

		$html .= '</div>';

		$this->html = $html;

		return $this;
	}


	/**
	* Creates the Click To Tweet preview for the Styles tab.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return object $this The calling instance, for method chaining.
	*
	*/
	public function do_ctt_preview() {
		//* Pull these variables out just to make the $html string easier to read.
		$link = "https://twitter.com/share?text=We+couldn%27t+find+one+social+sharing+plugin+that+met+all+of+our+needs%2C+so+we+built+it+ourselves.&amp;url=https://warfareplugins.com&amp;via=warfareplugins";
		$data_link = "https://twitter.com/share?text=We+couldn%27t+find+one+social+sharing+plugin+that+met+all+of+our+needs%2C+so+we+built+it+ourselves.&amp;url=https://wfa.re/1PtqdNM&amp;via=WarfarePlugins";
		$text = "We couldn't find one social sharing plugin that met all of our needs, so we built it ourselves.";

		$html = '<div class="sw-grid sw-col-940 sw-fit sw-option-container ' . $this->key . '_wrapper">';
			$html .= '<a class="swp_CTT style1"  data-style="style1" href="' . $link . '" data-link="' . $data_link . '" target="_blank">';
				$html .= '<span class="sw-click-to-tweet">';
					$html .= '<span class="sw-ctt-text">' . $text . '</span>';
					$html .= '<span class="sw-ctt-btn">Click To Tweet';
						$html .= '<i class="sw swp_twitter_icon"></i>';
					$html .= '</span>';
				$html .= '</span>';
			$html .= '</a>';
		$html .= '</div>';


		$this->html = $html;

		return $this;

	}


	/**
	* Renders the three column table on the Display tab.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return object $this The calling instance, for method chaining.
	*
	*/
	public function do_yummly_display() {
		$html = '<div class="sw-grid sw-col-940 sw-fit sw-option-container ' . $this->key . '_wrapper" ';
		$html .= $this->render_dependency();
		$html .= $this->render_premium();
		$html .= '>';


			//* Table headers
			$html .= '<div class="sw-grid sw-col-300">';
				$html .= '<p class="sw-select-label sw-short sw-no-padding"></p>';
			$html .= '</div>';

			$html .= '<div class="sw-grid sw-col-300">';
				$html .= '<p class="sw-select-label sw-short sw-no-padding">' . __( 'Choose Category', 'social-warfare' ) . '</p>';
			$html .= '</div>';

			$html .= '<div class="sw-grid sw-col-300 sw-fit">';
				$html .= '<p class="sw-select-label sw-short sw-no-padding">' . __( 'Choose Tag', 'social-warfare' ) . '</p>';
			$html .= '</div>';

			$yummly_categories = new SWP_Option_Text( __( 'Yummly Categories', 'social-warfare' ), 'yummly_categories' );
			$categories_html = $yummly_categories->set_priority( 10 )
				->set_default( '' )
				->render_HTML_element();

			$yummly_tags = new SWP_Option_Text( __( 'Yummly Tags', 'social-warfare'), 'yummly_tags' );
			$tags_html = $yummly_tags->set_priority( 10 )
				->set_default( '' )
				->render_HTML_element();

			//* Table body
			$html .= '<div class="sw-grid sw-col-300">';
				$html .= '<p class="sw-select-label sw-short sw-no-padding">' . __( 'Yummly Terms' ,'social-warfare' ) . '</p>';
			$html .= '</div>';

			$html .= '<div class="sw-grid sw-col-300">';
				$html .= '<p class="sw-select-label sw-short sw-no-padding">' . $categories_html . '</p>';
			$html .= '</div>';

			$html .= '<div class="sw-grid sw-col-300 sw-fit">';
				$html .= '<p class="sw-select-label sw-short sw-no-padding">' . $tags_html . '</p>';
			$html .= '</div>';

		$html .= '</div>';

		$this->html = $html;

		return $this;
	}


	public function do_bitly_start_date() {
		$post_types = SWP_Utility::get_post_types();

		$booleans = [
			'on'    => __( 'On','social_warfare'),
			'off'   => __( 'Off', 'social_warfare')
		];

		$html = '<div class="sw-grid sw-col-940 sw-fit sw-option-container ' . $this->key . '_wrapper" ';
		$html .= $this->render_dependency();
		$html .= $this->render_premium();
		$html .= '>';

		$start_date = SWP_Utility::get_option( 'bitly_start_date' );

		if ( !$start_date ) {
			// die(var_dump($start_date));
			$start_date = '';
		}


		$html .= '<p class="sw-subtitle sw-col-620" style="margin: 5px 0 15px">I would like to generate bitly links for content created <b>on or after</b>&nbsp;';
		$html .=     '<input
						 style="float: right;"
						 type="text"
						 id="' . $this->key . '"
						 name="' . $this->key . '"
						 value="' . $start_date . '"
					  />
				  </p>';
		$html .= '<p class="sw-subtitle sw-col-620">Please enter start the date in the following format: <code style="float: right;">YYYY-MM-DD</code></p>';

		$html .= '<div class="sw-grid sw-col-300">';
			$html .= '<p class="sw-select-label sw-short sw-no-padding">' . __( 'Post Type' ,'social-warfare' ) . '</p>';
		$html .= '</div>';

		$html .= '<div class="sw-grid sw-col-300 sw-fit">';
			$html .= '<p class="sw-select-label sw-short sw-no-padding">' . __( 'Create Bitly Links?' ,'social-warfare' ) . '</p>';
		$html .= '</div>';

		//* Some indices are numeric, others are strings.
		$i = 0;
		foreach( $post_types as $index => $post ) {
			$i++;
			$priority = $i * 10;

			$html .= '<div class="sw-grid sw-col-940 sw-fit sw-option-container ' . $post . '_wrapper">';

				$html .= '<div class="sw-grid sw-col-300">';
					$html .= '<p class="sw-input-label">' . str_replace('_', ' & ', ucfirst($post)) . '</p>';
				$html .= '</div>';

				$html .= '<div class="sw-grid sw-col-300 sw-fit">';

				$float = new SWP_Option_Select( ucfirst( $post ), 'bitly_links_' . $post );
				$float->set_priority( $priority )
					->set_size( 'sw-col-300' )
					->set_choices( $booleans )
					->set_default( 'on' );

				$html .= $float->render_HTML_element();

				$html .= '</div>';

			$html .= '</div>';

		}

		$html .= '</div>';

		$this->html = $html;

		return $this;

	}


	/**
	* The rendering method common to all classes.
	*
	* Unlike the other option classes, this class creates its HTML and does not immediately
	* return it. Instead, it stores the HTML inside itself and waits for the render_html
	* method to be called.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string The object's saved HTML.
	*
	*/
	public function render_HTML() {
		return $this->html;
	}
}
