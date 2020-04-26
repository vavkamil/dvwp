/**
 *
 * Social Warfare - The Javascript
 *
 * @since 1.0.0 | 01 JAN 2016 | Created
 * @since 3.4.0 | 19 OCT 2018 | Cleaned, Refactored, Simplified, Docblocked.
 * @package   SocialWarfare\Assets\JS\
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 *
 *
 * This is the primary javascript file used by the Social Warfare plugin. It is
 * loaded both on the frontend and the backend. It is used to control all client
 * side manipulation of the HTML.
 *
 *
 * Table of Contents:
 *
 *     #1. Initialization Functions
 *        Property: socialWarfare.paddingTop
 *        Property: socialWarfare.paddingBottom
 *        Function: socialWarfare.initPlugin()
 *        Function: socialWarfare.establishPanels()
 *
 *     #2. Static Horizontal Button Panel Controls
 *        Function: socialWarfare.activateHoverStates()
 *        Function: socialWarfare.resetStaticPanel()
 *        Function: socialWarfare.handleButtonClicks()
 *
 *     #3. Floating Buttons Panel Controls
 *        Function: socialWarfare.createFloatHorizontalPanel()
 *        Function: socialWarfare.staticPanelIsVisible()
 *        Function: socialWarfare.updateFloatingButtons()
 *        Function: socialWarfare.toggleMobileButtons()
 *        Function: socialWarfare.toggleFloatingVerticalPanel()
 *        Function: socialWarfare.toggleFloatingHorizontalPanel()
 *        Function: socialWarfare.positionFloatSidePanel()
 *
 *     #4. Pinterest Image Hover Save Buttons
 *        Function: socialWarfare.enablePinterestSaveButtons()
 *        Function: socialWarfare.renderPinterestSaveButton()
 *        Function: socialWarfare.findPinterestBrowserSaveButtons()
 *        Function: socialWarfare.removePinterestBrowserSaveButtons()
 *
 *     #5. Facebook Share Count Functions
 *        Function: socialWarfare.fetchFacebookShares()
 *        Function: socialWarfare.parseFacebookShares()
 *
 *     #6. Utility/Helper Functions
 *        Function: socialWarfare.throttle()
 *        Function: socialWarfare.trigger()
 *        Function: socialWarfare.trackClick()
 *        Function: socialWarfare.checkListeners()
 *        Function: socialWarfare.establishBreakpoint()
 *        Function: socialWarfare.isMobile()
 *
 *
 * Javascript variables created on the server:
 *
 *     bool   	swpClickTracking (SWP_Script.php)
 *     bool   	socialWarfare.floatBeforeContent
 *     object 	swpPinIt
 *     string 	swp_admin_ajax
 *     string 	swp_post_url
 *     string 	swp_post_recovery_url
 *
 */


/**
 * The first thing we want to do is to declare our socialWarfare object. We are
 * going to use this object to store all functions that our plugin uses. This will
 * allow us to avoid any naming collisions as well as allowing us to keep things
 * more neatly organized.
 *
 */
window.socialWarfare = window.socialWarfare || {};


/**
 * This allows us to scope all variables and functions to within this anonymous
 * function. However, since we are using a global object, socialWarfare, we will
 * still be able to access our functions and variables from anywhere.
 *
 */
(function(window, $) {
	'use strict';

	if ( typeof $ != 'function' ) {
		if ( typeof jQuery == 'function' ) {
			var $ = jQuery;
		}

		else {
			console.log("Social Warfare requires jQuery, or $ as an alias of jQuery. Please make sure your theme provides access to jQuery before activating Social Warfare.");
			return;
		}
	}

	/***************************************************************************
	 *
	 *
	 *    SECTION #1: INITIALIZATION FUNCTIONS
	 *
	 *
	 ***************************************************************************/


	/**
	 * These variables measure the amount of padding at the top and bottom of
	 * the page upon the dom loaded event. We grab these early on and keep them
	 * stored so that we can add 50 pixels of padding whenever the floating
	 * horizontal buttons are displayed. This will allow us to avoid having our
	 * buttons hover over menus or copyright information in the footer.
	 *
	 */
	socialWarfare.paddingTop    = parseInt($('body').css('padding-top'));
	socialWarfare.paddingBottom = parseInt($('body').css('padding-bottom'));


	/**
	 * Initializes the buttons provided that they exist.
	 *
	 * This function will activate the hover effects for the buttons, it will
	 * create the floting buttons, center vertically the side panel, handle
	 * and set up the button clicks, and monitor the scroll activity in order to
	 * show and hide any floating buttons.
	 *
	 * @param  void
	 * @return void
	 *
	 */
	socialWarfare.initPlugin = function() {
		$("body").css({
			paddingTop: socialWarfare.paddingTop,
			paddingBottom: socialWarfare.paddingBottom
		});

		socialWarfare.establishPanels();
		socialWarfare.establishBreakpoint();

		// Bail out if no buttons panels exist.
		if (!socialWarfare.panels.staticHorizontal && !socialWarfare.panels.floatingSide && !socialWarfare.panels.floatingHorizontal) {
			return;
		}

		socialWarfare.createFloatHorizontalPanel();
		socialWarfare.positionFloatSidePanel();
		socialWarfare.activateHoverStates();
		socialWarfare.handleButtonClicks();
		socialWarfare.updateFloatingButtons();


		/**
		 * In some instances, the click bindings were not being instantiated
		 * properly when they were run as the DOM was loaded. So we built this
		 * checkListeners() function to recheck every 2 seconds, 5 total times, to
		 * ensure that the buttons panel exist and activate the click bindings.
		 *
		 */
		setTimeout(function() {
			socialWarfare.checkListeners(0, 5)
		}, 2000);


		/**
		 * This will allow us to monitor whether or not the static horizontal
		 * buttons are inside the viewport as a user is scrolling the page. If
		 * they are not in the viewport, we will display the floating buttons.
		 * The throttle is to prevent it from firing non stop and causing the
		 * floating buttons to flicker.
		 *
		 */
		var time = Date.now();
		var scrollDelay = 50;
		$(window).scroll(function() {
			if ((time + scrollDelay - Date.now()) < 0) {
				socialWarfare.updateFloatingButtons();
				time = Date.now();
			}
		});
	}


	/**
	 * This will cause our resize event to wait until the user is fully done
	 * resizing the window prior to resetting and rebuilding the buttons and
	 * their positioning and re-initializing the plugin JS functions.
	 *
	 */
	var resizeWait;
	socialWarfare.onWindowResize = function(){
	  clearTimeout(resizeWait);
	  resizeWait = setTimeout(socialWarfare.initPlugin, 100 );
	}


	/**
	 * Finds each kind of buttons panel, if it exists, and stores it to the
	 * socialWarfare object for later reference. This is useful for reading data
	 * attributes of buttons panels without needing to fetch the panel every time.
	 *
	 * @return object The object which holds each of the kinds of buttons panels.
	 */
	socialWarfare.establishPanels = function() {
		//* Initialize the panels object with the three known panel types.
		socialWarfare.panels = {
			staticHorizontal: null,
			floatingSide: null,
			floatingHorizontal: null
		};

		// Set each type of panel as a jQuery object (with 0 or more panels)
		socialWarfare.panels.staticHorizontal = $(".swp_social_panel").not(".swp_social_panelSide");
		socialWarfare.panels.floatingSide = $(".swp_social_panelSide");

		return socialWarfare.panels;
	}


	/***************************************************************************
	 *
	 *
	 *    SECTION #2: STATIC HORIZONTAL BUTTON PANEL CONTROLS
	 *
	 *
	 ***************************************************************************/


	/**
	 * This triggers the hover effect that you see when you hover over the
	 * buttons in the panel. It measures the space needed to expand the button
	 * to reveal the call to action for that network and then uses flex to
	 * expand it and to shrink the other buttons to make room for the expansion.
	 *
	 * @since 2.1.0
	 * @param  void
	 * @return void
	 *
	 */
	socialWarfare.activateHoverStates = function() {
		socialWarfare.trigger('pre_activate_buttons');

		$('.swp_social_panel:not(.swp_social_panelSide) .nc_tweetContainer').on('mouseenter', function() {

			if ($(this).hasClass('swp_nohover')) {
				return;
			}

			socialWarfare.resetStaticPanel();
			var termWidth      = $(this).find('.swp_share').outerWidth();
			var iconWidth      = $(this).find('i.sw').outerWidth();
			var containerWidth = $(this).width();
			var change         = 1 + ((termWidth + 35) / containerWidth);

			$(this).find('.iconFiller').width(termWidth + iconWidth + 25 + 'px');
			$(this).css("flex", change + ' 1 0%');
		});

		$('.swp_social_panel:not(.swp_social_panelSide)').on('mouseleave', socialWarfare.resetStaticPanel);
	}


	/**
	 * Resets the static panels to their default styles. After they've been
	 * expanded by activateHoverStates(), this function returns the buttons to
	 * their normal state once a user is no longer hovering over the buttons.
	 *
	 * @see activateHoverStates().
	 * @param  void
	 * @return void
	 *
	 */
	socialWarfare.resetStaticPanel = function() {
		$(".swp_social_panel:not(.swp_social_panelSide) .nc_tweetContainer:not(.swp_nohover) .iconFiller").removeAttr("style");
		$(".swp_social_panel:not(.swp_social_panelSide) .nc_tweetContainer:not(.swp_nohover)").removeAttr("style");
	}


	/**
	 * Handle clicks on the buttons that open share windows. It fetches the
	 * share link, it opens the share link into a new window, it sizes the
	 * popout window, and makes sure the user is able to share the content.
	 *
	 * This also handles sending the events to Google Analytics and Google Tag
	 * Manager if the user has that feature enabled.
	 *
	 * @since  1.0.0 | 01 JAN 2018 | Created
	 * @param  void
	 * @return bool Returns false on failure.
	 *
	 */
	socialWarfare.handleButtonClicks = function() {


		/**
		 * In order to avoid the possibility that this function may be called
		 * more than once, we remove all click handlers from our buttons prior
		 * to activating the new click handler. Prior to this, there were some
		 * unique instances where clicking on a button would cause multiple
		 * share windows to pop out.
		 *
		 */
		$('.nc_tweet, a.swp_CTT').off('click');
		$('.nc_tweet, a.swp_CTT').on('click', function(event) {


			/**
			 * Some buttons that don't have popout share windows can use the
			 * 'nopop' class to disable this click handler. This will then make
			 * that button behave like a standard link and allow the browser's
			 * default click handler to handle it. This is for things like the
			 * email button.
			 *
			 * This used to return false, but that cancels the default event
			 * from firing. The whole purpose of this exclusion is to allow the
			 * original event to fire so returning without a value allows it to
			 * work.
			 *
			 */
			if ($(this).hasClass('noPop')) {
				return event;
			}


			/**
			 * Our click handlers will use the data-link html attribute on the
			 * button as the share URL when opening the share window. Therefore,
			 * we need to make sure that this attribute exists.
			 *
			 */
			if ('undefined' == typeof $(this).data('link')) {
				return event;
			}


			/**
			 * This needs to run after all of the bail out conditions above have
			 * been run. We don't want to preventDefault if a condition exists
			 * wherein we don't want to take over the event.
			 *
			 */
			event.preventDefault();


			/**
			 * Fetch the share link that we'll use to call the popout share
			 * windows and then declare the variables that we'll be using later.
			 *
			 */
			var href = $(this).data('link').replace('’', '\'');
			var height, width, top, left, instance, windowAttributes, network;


			/**
			 * These are the default dimensions that are used by most of the
			 * popout share windows. Additionally, a few of the windows have
			 * their own javascript that will resize the window dynamically
			 * once loaded.
			 *
			 */
			height = 270;
			width = 500;


			/**
			 * Pinterest, Buffer, and Flipboard use a different size than the
			 * rest so if it's one of those buttons, overwrite the defaults
			 * that we set above.
			 *
			 */
			if ($(this).is('.pinterest, .buffer_link, .flipboard')) {
				height = 550;
				width = 775;
			}

			/**
			 * If a button was clicked, use the data-network attribute to
			 * figure out which network is being shared. If it was a click
			 * to tweet that was clicked on, just use ctt as the network.
			 *
			 */
			if ($(this).hasClass('nc_tweet')) {
				network = $(this).parents('.nc_tweetContainer').data('network');
			} else if ($(this).hasClass('swp_CTT')) {
				network = 'ctt';
			}


			/**
			 * We'll measure the window and then run some calculations to ensure
			 * that our popout share window opens perfectly centered on the
			 * browser window.
			 *
			 */
			top = window.screenY + (window.innerHeight - height) / 2;
			 left = window.screenX + (window.innerWidth - width) / 2;
			 windowAttributes = 'height=' + height + ',width=' + width + ',top=' + top + ',left=' + left;
			 instance = window.open(href, network, windowAttributes);
			// Active Google Analytics event tracking for the button click.
			socialWarfare.trackClick(network);
		});
	}


	/***************************************************************************
	 *
	 *
	 *    SECTION #3: FLOATING BUTTONS PANEL CONTROLS
	 *
	 *
	 ***************************************************************************/


	/**
	*  Clones a copy of the static buttons to use as a floating panel.
	*
	* We clone a set of the static horizontal buttons so that when we create
	* the floating set we can make the position match exactly. This way when
	* they are showing up and disappearing, it will create the allusion that
	* the static buttons are just getting glued to the edge of the screen and
	* following along with the user as they scroll.
	*
	* @since  1.0.0 | 01 JAN 2016 | Created
	* @param  void
	* @return void
	*
	*/
	socialWarfare.createFloatHorizontalPanel = function() {

		//* If a horizontal panel does not exist, we can not create a bar.
		if (!socialWarfare.panels.staticHorizontal.length) {
			return;
		}

		var floatLocation       = socialWarfare.panels.staticHorizontal.data("float");
		var mobileFloatLocation = socialWarfare.panels.staticHorizontal.data("float-mobile");
		var backgroundColor     = socialWarfare.panels.staticHorizontal.data("float-color");
		var wrapper             = $('<div class="nc_wrapper swp_floating_horizontal_wrapper" style="background-color:' + backgroundColor + '"></div>');
		var barLocation         = '';

		//* .swp_social_panelSide is the side floater.
		if ($(".nc_wrapper").length) {
			$(".nc_wrapper").remove();
		}

		//* repeating the code above for the new selector.
		if ($(".swp_floating_horizontal_wrapper").length) {
			$(".swp_floating_horizontal_wrapper").remove();
		}

		//* No floating bars are used at all.
		if (floatLocation != 'top' && floatLocation != 'bottom' && mobileFloatLocation != "top" && mobileFloatLocation != "bottom") {
			return;
		}

		//* Set the location (top or bottom) of the bar depending on
		if (socialWarfare.isMobile()) {
			barLocation = mobileFloatLocation;
		} else {
			barLocation = floatLocation;
		}

		//* Assign a CSS class to the wrapper based on the float-mobile location.
		wrapper.addClass(barLocation).hide().appendTo('body');

		//* Save the new buttons panel to our ${panels} object.
		socialWarfare.panels.floatingHorizontal = socialWarfare.panels.staticHorizontal.first().clone();
		socialWarfare.panels.floatingHorizontal.addClass('nc_floater').appendTo(wrapper);
		socialWarfare.updateFloatingHorizontalDimensions();

		$(".swp_social_panel .swp_count").css({
			transition: "padding .1s linear"
		});
	}

  /**
   * Callback on window resize to update the width and position of a
   * floatingHorizontal panel.
   *
   */
	socialWarfare.updateFloatingHorizontalDimensions = function() {

		// If there is no static set to measure, just bail out.
		if (!socialWarfare.panels.staticHorizontal.length) {
			return;
		}


		// If there is no floating set, just bail.
		if(!socialWarfare.panels.floatingHorizontal) {
			return;
		}


		/**
		 * We'll create the default width and left properties here. Then we'll
		 * attempt to pull these properties from the actual panel that we are
		 * cloning below. If those measurements exist, we clone them. If not,
		 * we use these defaults.
		 *
		 */
		var width = "100%";
		var left  = 0;
		var panel = socialWarfare.panels.staticHorizontal;
		var parent = panel.parent();

		//* Ignore the invisible wrapper div, it has no width.
		if (parent.hasClass("swp-hidden-panel-wrap")) {
			parent = parent.parent();
		}

		if( 'undefined' !== typeof panel.offset().left ) {
			left = panel.offset().left;
		}

		if( 'undefined' !== typeof panel.width() ) {
			width = panel.width();
		}

		if( left == 0 ) {
			left = parent.offset().left;
		}

		//* The panel width is 'auto', which evaluates to 100%
		if (width == 100 || width == 0) {
			width = parent.width();
		}

		//* Give the bar panel the appropriate classname and put it in its wrapper.
		socialWarfare.panels.floatingHorizontal.css({
			width: width,
			left: left
		});
	}


	/**
	 * Determines if a set of static buttons is currenty visible on the screen.
	 *
	 * We will use this to determine whether or not we should display a set of
	 * floating buttons. Whenever the static buttons are visible, we hide the
	 * floating buttons. Whenever the static buttons are not visible, we show
	 * the floating buttons.
	 *
	 * @param  void
	 * @return bool True if a static set of buttons is visible on the screen, else false.
	 *
	 */
	socialWarfare.staticPanelIsVisible = function() {
		var visible = false;
		var scrollPos = $(window).scrollTop();

		//* Iterate each buttons panel, checking each to see if it is currently visible.
		$(".swp_social_panel").not(".swp_social_panelSide, .nc_floater").each(function(index) {
			var offset = $(this).offset();

			//* Do not display floating buttons before the horizontal panel.
			//* PHP json_encode() maps `true` to "1" and `false` to "".
			if (typeof socialWarfare.floatBeforeContent != 'undefined' && "1" != socialWarfare.floatBeforeContent) {
				var theContent = $(".swp-content-locator").parent();

				//* We are in sight of an "Above the content" panel.
				if (index === 0 && theContent.length && theContent.offset().top > (scrollPos + $(window).height())) {
					visible = true;
				}
			}

			//* Do not display floating buttons if a panel is currently visible.
			if ($(this).is(':visible') &&
					offset.top + $(this).height() > scrollPos &&
					offset.top < (scrollPos + $(window).height())) {

				visible = true;
			}
		});

		return visible;
	}


	/**
	 * Handler to toggle the display of either the side or bar floating buttons.
	 *
	 * We only show the floating buttons when the static horizontal buttons are
	 * not in the visible view port. This function is used to toggle their
	 * visibility when they need to be shown or hidden.
	 *
	 * @since  2.0.0 | 01 JAN 2016 | Created
	 * @param  void
	 * @return void
	 *
	 */
	socialWarfare.updateFloatingButtons = function() {
		// If buttons are on the page, there must be either a static horizontal
		if (socialWarfare.panels.staticHorizontal.length) {
			var panel = socialWarfare.panels.staticHorizontal;
		}

		// Or a side floating panel.
		else if (socialWarfare.panels.floatingSide.length) {
			var panel = socialWarfare.panels.floatingSide;
		}

		else {
			return;
		}

		// Adjust the floating bar
		var location = panel.data('float');

		if (true == socialWarfare.isMobile()) {
			var location = panel.data('float-mobile');
		}

		//* There are no floating buttons enabled, hide any that might exist.
		if (location == 'none') {
			return $(".nc_wrapper, .swp_floating_horizontal_wrapper, .swp_social_panelSide").hide();
		}

		if (socialWarfare.isMobile()) {
			socialWarfare.toggleMobileButtons();
			socialWarfare.toggleFloatingHorizontalPanel();
			return;
		}

		if (location == "right" || location == "left") {
			socialWarfare.toggleFloatingVerticalPanel();
		}

		if (location == "bottom" || location == "top") {
			socialWarfare.toggleFloatingHorizontalPanel();
		}
	}


	/**
	 * Toggle the visibilty of a mobile bar.
	 *
	 * @return void
	 *
	 */
	socialWarfare.toggleMobileButtons = function() {

		//* There are never any left/right floating buttons on mobile, so hide them.
		socialWarfare.panels.floatingSide.hide();

		var visibility = socialWarfare.staticPanelIsVisible() ? "collapse" : "visible";
		$(".nc_wrapper, .swp_floating_horizontal_wrapper").css("visibility", visibility);
	}


	/**
	 * Toggle the display of a side panel, depending on static panel visibility.
	 *
	 * @return void
	 *
	 */
	socialWarfare.toggleFloatingVerticalPanel = function() {
		var direction = '';
		var location = socialWarfare.panels.floatingSide.data("float");
		var visible  = socialWarfare.staticPanelIsVisible();
		var offset = "";

		//* This is on mobile and does not use side panels.
		if (socialWarfare.isMobile()) {
			return socialWarfare.panels.floatingSide.hide();
		}

		if (!socialWarfare.panels.floatingSide || !socialWarfare.panels.floatingSide.length) {
			// No buttons panel! Update `visible` to hide floaters.
			visible = true;
		}

		if (socialWarfare.panels.floatingSide.data("transition") == "slide") {
			direction = location;
			offset     = visible ? "-150px" : "5px";
			//* Update the side panel CSS with the direction and amount.
			socialWarfare.panels.floatingSide.css(direction, offset).show();
		}

		else {
			/**
			 * We had problems with the fading buttons flickering rather than having
			 * a smooth fade animation. The workaround was to manually control opacity,
			 * fade, and opacity again.
			 *
			 */
			if (visible) {
				socialWarfare.panels.floatingSide.css("opacity", 1)
					.fadeOut(300)
					.css("opacity", 0);
			}

			else {
				socialWarfare.panels.floatingSide.css("opacity", 0)
					.fadeIn(300)
					.css("display", "flex")
					.css("opacity", 1);
			}
		}
	}


	socialWarfare.hasReferencePanel = function() {
		return typeof socialWarfare.panels.staticHorizontal != 'undefined' &&
					  socialWarfare.panels.staticHorizontal.length > 0
	}


	/**
	 * Toggle the display of a floating bar, depending on static panel visibility.
	 *
	 * @return void
	 *
	 */
	socialWarfare.toggleFloatingHorizontalPanel = function() {
		if (!socialWarfare.hasReferencePanel()) {
			return;
		}

		// If there is no floating set, just bail.
		if(!socialWarfare.panels.floatingHorizontal) {
			return;
		}

		var panel = socialWarfare.panels.floatingHorizontal.first();
		var location = socialWarfare.isMobile() ? $(panel).data("float-mobile") : $(panel).data("float");
		var newPadding = (location == "bottom") ? socialWarfare.paddingBottom : socialWarfare.paddingTop;
		var paddingProp = "padding-" + location;

		if (location == 'off') {
			return;
		}

		//* Restore the padding to initial values.
		if (socialWarfare.staticPanelIsVisible()) {
			$(".nc_wrapper, .swp_floating_horizontal_wrapper").hide();


			if (socialWarfare.isMobile() && $("#wpadminbar").length) {
				$("#wpadminbar").css("top", 0);
			}
		}

		// Add some padding to the page so it fits nicely at the top or bottom.
		else {
			newPadding += 50;
			$(".nc_wrapper, .swp_floating_horizontal_wrapper").show();

			//* Compensate for the margin-top added to <html> by #wpadminbar.
			if (socialWarfare.isMobile() && location == 'top' && $("#wpadminbar").length) {
				$("#wpadminbar").css("top", panel.parent().height());
			}
		}

		//* Update padding to be either initial values, or to use padding for floatingHorizontal panels.
		$("body").css(paddingProp, newPadding);
	}


	/**
	 * This method is used to vertically center the floating buttons when they
	 * are positioned on the left or right of the screen.
	 *
	 * @since  3.4.0 | 18 OCT 2018 | Created
	 * @param  void
	 * @param  void All changes are made to the dom.
	 *
	 */
	socialWarfare.positionFloatSidePanel = function() {
		var panelHeight, windowHeight, offset;
		var sidePanel = socialWarfare.panels.floatingSide;


		/**
		 * If no such element exists, we obviously just need to bail out and
		 * not try to center anything.
		 *
		 */
		if (!sidePanel || !sidePanel.length) {
			return;
		}


		/**
		 * We don't need to center the side panel buttons if the position is set
		 * to top or bottom. This will isntead be directly controlled by the CSS
		 * that is associated with these classes.
		 *
		 */
		if( sidePanel.hasClass('swp_side_top') || sidePanel.hasClass('swp_side_bottom') ) {
			return;
		}


		/**
		 * We'll need the height of the panel itself and the height of the
		 * actual browser window in order to calculate how to center it.
		 *
		 */
		panelHeight = sidePanel.outerHeight();
		windowHeight = window.innerHeight;


		/**
		 * If for some reason the panel is actually taller than the window
		 * itself, just stick it to the top of the window and the bottom will
		 * just have to overflow past the bottom of the screen.
		 *
		 */
		if (panelHeight > windowHeight) {
			return sidePanel.css("top", 0);
		}


		/**
		 * Calculate the center position of panel and then apply the relevant
		 * CSS to the panel.
		 *
		 */
		offset = (windowHeight - panelHeight) / 2;
		sidePanel.css("top", offset);
	}


	/***************************************************************************
	 *
	 *
	 *    SECTION #4: PINTEREST IMAGE HOVER SAVE BUTTONS
	 *
	 *
	 ***************************************************************************/


	 /**
	 * This reactivates and creates new image hover pin buttons when a page has
	 * been loaded via AJAX. The 'load' event is the proper event that theme and
	 * plugin creators are supposed to use when the AJAX load is complete.
	 *
	 */
	$(window).on('load', function() {

		if ('undefined' !== typeof swpPinIt && swpPinIt.enabled) {
			socialWarfare.enablePinterestSaveButtons();
		}
		window.clearCheckID = 0;
	});


	/**
	 * Adds the "Save" button to images when the option is enabled.
	 *
	 * This method will search and destroy any Pinterest save buttons that have
	 * been added by the Pinterest browser extension and then render the html
	 * needed to add our own proprietary Pinterest buttons on top of images.
	 *
	 * @param  void
	 * @return void
	 *
	 */
	socialWarfare.enablePinterestSaveButtons = function() {

		/**
		 * Search and Destroy: This will find any Pinterest buttons that were
		 * added via their browser extension and then destroy them so that only
		 * ours are on the page.
		 *
		 */
		jQuery('img').on('mouseenter', function() {
			var pinterestBrowserButtons = socialWarfare.findPinterestBrowserSaveButtons();
			if (typeof pinterestBrowserButtons != 'undefined' && pinterestBrowserButtons) {
				socialWarfare.removePinterestBrowserSaveButtons(pinterestBrowserButtons);
			}
		});

		/**
		 * Find all images of the images that are in the content area by looking
		 * for the .swp-content-locator div which is an empty div that we add via
		 * the_content() hook just so that we can target it here. Then iterate
		 * through them and determine if we should add a Pinterest save button.
		 *
		 */
		$('.swp-content-locator').parent().find('img').each(socialWarfare.renderPinterestSaveButton);


		/**
		 * Attach a click handler to each of the newly created "Save" buttons,
		 * and trigger the click tracking function.
		 *
		 */
		$('.sw-pinit .sw-pinit-button').on('click', function(event) {
			event.preventDefault();
			window.open($(this).attr('href'), 'Pinterest', 'width=632,height=253,status=0,toolbar=0,menubar=0,location=1,scrollbars=1');
			socialWarfare.trackClick('pin_image');
		});
	}


	/**
	* This function renders the HTML needed to print the save buttons on the images.
	*
	* @param  void
	* @since  void
	*
	*/
	socialWarfare.renderPinterestSaveButton = function() {
		var image, pinMedia, pinDesc, bookmark, imageClasses, imageStyles, shareLink;
		image = $(this);

		/**
		 * This disables the Pinterest save buttosn on images that are anchors/links
		 * if the user has them disabled on them in the options page. So if this
		 * image is a link, we just bail out.
		 *
		 */
		if (typeof swpPinIt.disableOnAnchors != undefined && swpPinIt.disableOnAnchors) {
			if ($(image).parents().filter("a").length) {
				return;
			}
		}


		/**
		 * In the option page, the user can set a minimum width and a minimum
		 * height. Anything that isn't as large as these image dimensions will
		 * be skipped. This is a JS variable that is generated and output by
		 * the server.
		 *
		 */
		if (image.outerHeight() < swpPinIt.minHeight || image.outerWidth() < swpPinIt.minWidth) {
			return;
		}


		/**
		 * We offer users the option to manually opt any image out of having a
		 * Pinterest save button on it by simply adding either the no_pin class
		 * or the no-pin class. There is also a checkbox in the media uploader
		 * that when checked will add one of these classes. If this image has
		 * one of these classes, just bail and skip this image.
		 *
		 */
		if (image.hasClass('no_pin') || image.hasClass('no-pin')) {
			return;
		}


		/**
		 * If the swpPinIt.image_source variable exists, it means that the user
		 * has opted to use their custom Pinterest image rather than having
		 * visitors pin the actual image being hovered.
		 *
		 */
		if ('undefined' !== typeof swpPinIt.image_source && swpPinIt.image_source.length) {

			/**
			 * By creating a temporary image and then using jQuery to fetch the
			 * URL of that image, it will convert any relative paths to
			 * absolute paths. If we send a relative path image to Pinterest, it
			 * will throw errors.
			 *
			 */
			var i = new Image();
			i.src = swpPinIt.image_source;
			pinMedia = $(i).prop('src');


		/**
		 * Both media and lazy-src are data attributes used by some lazy loading
		 * plugins. If we don't look for these, we're not able to add the save
		 * button to lazy loaded images that have not been loaded when the
		 * document has been loaded.
		 *
		 */
		} else if (image.data('media')) {
			pinMedia = image.data('media');
		} else if ($(this).data('lazy-src')) {
			pinMedia = $(this).data('lazy-src');
		} else if (image[0].src) {
			pinMedia = image[0].src;
		}

		// Bail if we don't have any media to pin.
		if (!pinMedia || 'undefined' === typeof pinMedia) {
			return;
		}


		/**
		 * This is where we compute a description that will be used when the
		 * image is shared to Pinterest. In order of precedence, we will use the
		 * image's data-pin-description attribute, the custom Pinterest description
		 * for the post passed from the server, the image title, or the image
		 * description.
		 *
		 */
		if (typeof image.data("pin-description") != 'undefined') {
			pinDesc = image.data("pin-description");
		} else if (typeof image.data("pin-description") == 'string' && swpPinIt.image_description.length) {
			pinDesc = swpPinIt.image_description;
		} else if (image.attr('title')) {
			pinDesc = image.attr('title');
		} else if (image.attr('alt')) {
			pinDesc = image.attr('alt');
		} else if (typeof swpPinIt.post_title == 'string') {
			pinDesc = swpPinIt.post_title;
		}
		shareLink = 'http://pinterest.com/pin/create/bookmarklet/?media=' + encodeURI(pinMedia) + '&url=' + encodeURI(document.URL) + '&is_video=false' + '&description=' + encodeURIComponent(pinDesc);


		/**
		 * In order to preserve all of the layout, positioning and style of the
		 * image, we are going to fetch all of the classes and inline styles of
		 * the image and move them onto the parent container in which we will be
		 * wrapping the image.
		 *
		 */
		imageClasses = image.attr('class');
		imageStyles  = image.attr('style');

		// Remove the image classes and styles. Create the wrapper div.
		image.removeClass().attr('style', '').wrap('<div class="sw-pinit" />');

		// Append the button as the last element inside the wrapper div.
		image.after('<a href="' + shareLink + '" class="sw-pinit-button sw-pinit-' + swpPinIt.vLocation + ' sw-pinit-' + swpPinIt.hLocation + '">Save</a>');

		// Add the removed classes and styles to the wrapper div.
		image.parent('.sw-pinit').addClass(imageClasses).attr('style', imageStyles);
	}


	/**
	 * Looks for a "Save" button created by Pinterest addons.
	 *
	 * @param  void
	 * @return HTMLNode if the Pinterest button is found, else NULL.
	 *
	 */
	socialWarfare.findPinterestBrowserSaveButtons = function() {
		var pinterestRed, pinterestRed2019, pinterestZIndex, pinterestBackgroundSize, button, style;

		//* Known constants used by Pinterest.
		pinterestRed = "rgb(189, 8, 28)";
		pinterestRed2019 = "rgb(230, 0, 35)";
		pinterestZIndex = "8675309";
		pinterestBackgroundSize = "14px 14px";
		button = null;

		//* The Pinterest button is a <span/>, so check each span for a match.
		document.querySelectorAll("span").forEach(function(element, index) {
			style = window.getComputedStyle(element);

			if (style.backgroundColor == pinterestRed || style.backgroundColor == pinterestRed2019) {
				if (style.backgroundSize == pinterestBackgroundSize && style.zIndex == pinterestZIndex) {
					button = element;
				}
			}
		});

		return button;
	}


	/**
	 * Removes the "save" button created by Pinterest Browser Extension.
	 *
	 */
	socialWarfare.removePinterestBrowserSaveButtons = function(button) {
		var pinterestSquare, style, size;
		pinterestSquare = button.nextSibling;

		//* The sibling to the Pinterest button is always a span.
		if (typeof pinterestSquare != 'undefined' && pinterestSquare.nodeName == 'SPAN') {
			style = window.getComputedStyle(pinterestSquare);
			size = "24px";

			//* If the sibling is indeed the correct Pinterest sibling, destory it all.
			if (style.width.indexOf(size) === 0 && style.height.indexOf(size) === 0) {
				pinterestSquare.remove()
			}
		}

		button.remove();
	}


	/***************************************************************************
	 *
	 *
	 *    SECTION #5: FACEBOOK SHARE COUNT FUNCTIONS
	 *
	 *
	 ***************************************************************************/


	/**
	 * Makes external requsts to fetch Facebook share counts. We fetch Facebook
	 * share counts via the frontened Javascript because their API has harsh
	 * rate limits that are IP Address based. So it's very easy for a website to
	 * hit those limits and recieve temporary bans from accessing the share count
	 * data. By using the front end, the IP Addresses are distributed to users,
	 * are therefore spread out, and don't hit the rate limits.
	 *
	 * @param  void
	 * @return void
	 *
	 */
	socialWarfare.fetchFacebookShares = function() {

		// Compile the API links
		var url1 = 'https://graph.facebook.com/?fields=og_object{likes.summary(true).limit(0)},share&id=' + swp_post_url;
		var url2 = swp_post_recovery_url ? 'https://graph.facebook.com/?fields=og_object{likes.summary(true).limit(0)},share&id=' + swp_post_recovery_url : '';

		// Use this to ensure that we wait until the API requests are done.
		$.when( $.get( url1 ), $.get( url2 ) )
		.then(function(response1, response2) {
			var shares, data;

			// Parse the shares and add them up into a running total.
			shares = socialWarfare.parseFacebookShares(response1[0]);
			if (swp_post_recovery_url) {
				shares += socialWarfare.parseFacebookShares(response2[0]);
			}

			// Compile the data and send out the AJAX request to store the count.
			var data   = {
				action: 'swp_facebook_shares_update',
				post_id: swp_post_id,
				share_counts: shares
			};
			$.post(swp_admin_ajax, data);

		});
	}


	/**
	 * Sums the share data from a facebook API response. This is a utility
	 * function used by socialWarfare.fetchFacebookShares to allow easy access
	 * to parsing out the JSON response that we got from Facebook's API and
	 * converting it into an integer that reflects the tally of all activity
	 * on the URl in question including like, comments, and shares.
	 *
	 * @param  object response The API response received from Facebook.
	 * @return number The total shares summed from the request, or 0.
	 *
	 */
	socialWarfare.parseFacebookShares = function(response) {
		var total = 0;

		if ('undefined' !== typeof response.share) {
			total += parseInt(response.share.share_count);
			total += parseInt(response.share.comment_count);
		}

		if (typeof response.og_object != 'undefined') {
			total += parseInt(response.og_object.likes.summary.total_count);
		}

		return total;
	}


	/***************************************************************************
	 *
	 *
	 *    SECTION #6: UTILITY/HELPER FUNCTIONS
	 *
	 *
	 ***************************************************************************/


	/**
	 * The throttle function is used to control how often an event can be fired.
	 * We use this exclusively to control how often scroll events go off. In some
	 * cases, the scroll event which controls when the floating buttons appear
	 * or disappear, was firing so often on scroll that the floating buttons were
	 * rapidly flickering in and out of view. This solves that.
	 *
	 * @param  integer   delay    How often in ms to allow the event to fire.
	 * @param  function  callback The function to run if the timeout period is expired.
	 * @return function           The callback function.
	 *
	 */
	// socialWarfare.throttle = function(delay, callback) {
	// 	var timeoutID = 0;
	// 	// The previous time `callback` was called.
	// 	var lastExec  = 0;
	//
	// 	function wrapper() {
	// 		var wrap    = this;
	// 		var elapsed = +new Date() - lastExec;
	// 		var args    = arguments;
	//
	// 		function exec() {
	// 			lastExec = +new Date();
	// 			callback.apply(wrap, args);
	// 		}
	//
	// 		function clear() {
	// 			timeoutID = 0;
	// 			lastExec = 0;
	// 		}
	//
	// 		timeoutID && clearTimeout(timeoutID);
	//
	// 		if (elapsed > delay) {
	// 			exec();
	// 		} else {
	// 			timeoutID = setTimeout(exec, delay - elapsed);
	// 		}
	// 	}
	//
	// 	if (socialWarfare.guid) {
	// 		wrapper.guid = callback.guid = callback.guid || socialWarfareguid++;
	// 	}
	//
	// 	console.log(wrapper)
	//
	// 	return wrapper;
	// };



	/**
	 * A simple wrapper for easily triggering DOM events. This will allow us to
	 * fire off our own custom events that our addons can then bind to in order
	 * to run their own functions in sequence with ours here.
	 *
	 * @param  string event The name of the event to trigger.
	 * @return void
	 *
	 */
	socialWarfare.trigger = function(event) {
		$(window).trigger($.Event(event));
	}


	/**
	 * Fire an event for Google Analytics and GTM.
	 *
	 * @since  2.4.0 | 18 OCT 2018 | Created
	 * @param  string event A string identifying the button being clicked.
	 * @return void
	 *
	 */
	socialWarfare.trackClick = function(event) {


		/**
		 * If click tracking has been enabled in the user settings, we'll
		 * need to send the event via Googel Analytics. The swpClickTracking
		 * variable will be dynamically generated via PHP and output in the
		 * footer of the page.
		 *
		 */
		if (true === swpClickTracking) {


			/**
			 * If Google Analytics is present on the page, we'll send the
			 * event via their object and methods.
			 *
			 */
			if ('function' == typeof ga) {
				ga('send', 'event', 'social_media', 'swp_' + event + '_share');
			}


			/**
			 * If Google Tag Manager is present on the page, we'll send the
			 * event via their object and methods.
			 *
			 */
			if ('object' == typeof dataLayer) {
				dataLayer.push({ 'event': 'swp_' + event + '_share' });
			}
		}
	}


	/**
	 * Checks to see if we have a buttons panel. If so, forces a re-run of the
	 * handleButtonClicks callback.
	 *
	 * @param  number count The current iteration of the loop cycle.
	 * @param  number limit The maximum number of iterations for the loop cycle.
	 * @return void or function handleButtonClicks().
	 *
	 */
	socialWarfare.checkListeners = function(count, limit) {


		/**
		 * Once we've checked for the buttons panel a certain number of times,
		 * we're simply going to bail out and stop checking. Right now, it is
		 * set to run 5 times for a total of 10 seconds.
		 *
		 */
		if (count > limit) {
			return;
		}


		/**
		 * The primary reason we are doing this is to ensure that a set of
		 * buttons does indeed exist when the click bindings are created. So
		 * this looks for the buttons and check's for their existence. If we
		 * find them, we fire off the handleButtonClicks() function.
		 *
		 */
		var panel = $('.swp_social_panel');
		if (panel.length > 0 && panel.find('.swp_pinterest')) {
			socialWarfare.handleButtonClicks();
			return;
		}


		/**
		 * If we haven't found any buttons panel, then after 2 more seconds,
		 * we'll fire off this function again until the limit has been reached.
		 *
		 */
		setTimeout(function() {
			socialWarfare.checkListeners(++count, limit)
		}, 2000);
	}


	/**
	 * Stores the user-defined mobile breakpoint in the socialWarfare object. In
	 * other functions, if the width of the current browser is smaller than this
	 * breakpoint, we will switch over and use the mobile options for the buttons
	 * panels.
	 *
	 */
	socialWarfare.establishBreakpoint = function() {
		var panel = $('.swp_social_panel');
		socialWarfare.breakpoint = 1100;

		if (panel.length && panel.data('min-width') || panel.data('min-width') == 0) {
			socialWarfare.breakpoint = parseInt( panel.data('min-width') );
		}
	}


	/**
	 * Checks to see if the current viewport is within the defined mobile
	 * breakpoint. The user sets a width in the options page. Any window
	 * viewport that is not as wide as that width will trigger isMobile to
	 * return as true.
	 *
	 */
	socialWarfare.isMobile = function() {
		return $(window).width() < socialWarfare.breakpoint;
	}

	/**
	 * Load the plugin once the DOM has been loaded.
	 *
	 */
	$(document).ready(function() {

		// This is what fires up the entire plugin's JS functionality.
		socialWarfare.initPlugin();
		socialWarfare.panels.floatingSide.hide();


		/**
		 * On resize, we're going to purge and re-init the entirety of the
		 * socialWarfare functions. This will fully reset all of the floating
		 * buttons which will allow for a clean transition if the size change
		 * causes the isMobile() check to flip from true to false or vica versa.
		 *
		 */
		$(window).resize(socialWarfare.onWindowResize);

	});

})(this, jQuery);
