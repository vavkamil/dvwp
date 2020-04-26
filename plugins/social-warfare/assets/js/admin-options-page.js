/* global ajaxurl, swpAdminOptionsData, socialWarfare, wp */
(function(window, $) {
	'use strict';

	window.onload = function() {
		/*********************************************************
			Temporary patch for the custom color se lects.
		*********************************************************/
		/*
		*  Temp patch on the Visual Options colors.
		*  This makes the custom colors appear/disappear as necessary.
		*/
		var panelSelector = "[name=default_colors],[name=hover_colors], [name=single_colors]";
		var floatSelector = "[name=float_default_colors], [name=float_hover_colors], [name=float_single_colors]";

		// Hide the custom color inputs by default.
		jQuery("[name=custom_color],[name=custom_color_outlines],[name=float_custom_color],[name=float_custom_color_outlines]").parent().parent().hide();

		// Show custom fields if they have already been selected.
		jQuery(panelSelector).each(function(index, select) {
			var value = jQuery(select).val();
			var customColor = jQuery("[name=custom_color]").parent().parent();
			var customOutlines = jQuery("[name=custom_color_outlines]").parent().parent();

			if (value.indexOf("custom") !== -1) {
				// A custom value is set for this input.
				if (value.indexOf('outlines') > 0) {
					customOutlines.show();
				} else {
					customColor.show();
				}
			}
		});

		// Same, for floating button options.
		jQuery(floatSelector).each(function(index, select) {
			var value = jQuery(select).val();
			var customColor = jQuery("[name=float_custom_color]").parent().parent();
			var customOutlines = jQuery("[name=float_custom_color_outlines]").parent().parent();

			if (value.indexOf("custom") !== -1) {
				// A custom value is set for this input.
				if (value.indexOf('outlines') > 0) {
					customOutlines.show();
				} else {
					customColor.show();
				}
			}
		});

		// Change handlers for style.
		jQuery(panelSelector).on("change", function(e) {
			var value = e.target.value;
			var customColor = jQuery("[name=custom_color]").parent().parent();
			var customOutlines = jQuery("[name=custom_color_outlines]").parent().parent();

			handleCustomColors(e, panelSelector, customColor, customOutlines, value);
		});

		// Same, for floating button options.
		jQuery(floatSelector).on("change", function(e) {
			var value = e.target.value;
			var customColor = jQuery("[name=float_custom_color]").parent().parent();
			var customOutlines = jQuery("[name=float_custom_color_outlines]").parent().parent();

			customColor.hide();
			customOutlines.hide();

			handleCustomColors(e, floatSelector, customColor, customOutlines, value);
		});
	}

	function handleCustomColors(event, selector, customColor, customOutlines) {
		// Create a notice about the custom colors.
		var colorNotice = '<div id="color-notice"><p><span class="color-dismiss"></span><b>Note:</b> Custom colors will not show up in the preview, but will on your site.</p></div>';
		var visibility = {
			customColor: false,
			customOutlines: false
		};

		jQuery(selector).each(function(index, select) {
			var val = jQuery(select).val();
			// Check to see if this or a sibling input has a custom_color selected.
			if (val.indexOf("custom") !== -1) {
				if (val.indexOf("outlines") > 0) {
					visibility.customOutlines = true;
				} else {
					visibility.customColor = true;
				}
			}
		});

		// Hide or show the inputs based on results from above.
		visibility.customColor ? customColor.slideDown() : customColor.slideUp();
		visibility.customOutlines ? customOutlines.slideDown() : customOutlines.slideUp();

		if (visibility.customColor || visibility.customOutlines) {
			jQuery("body").append(colorNotice);
			jQuery(".color-dismiss").on("click", function() {
				jQuery("#color-notice").fadeOut("slow");
			});
		} else {
			if (jQuery("#color-notice").length) {
				jQuery("#color-notice").fadeOut("slow");
			}
		}
	}


	/*********************************************************
		A Function send the array of setting to ajax.php
	*********************************************************/
	function selectText(element) {
		var	range, selection;

		if (document.body.createTextRange) {
			range = document.body.createTextRange();
			range.moveToElementText(element);
			range.select();

		} else if (window.getSelection) {
			selection = window.getSelection();
			range = document.createRange();

			range.selectNodeContents(element);
			selection.removeAllRanges();
			selection.addRange(range);

		}
	}

	/*********************************************************
		A Function to gather all the settings
	 *********************************************************/
	function fetchAllOptions() {
		// Create an object
		var values = {};

		// Loop through all the inputs
		jQuery('form.sw-admin-settings-form input, form.sw-admin-settings-form select, form.sw-admin-settings-form textarea').each(function() {
			var jQueryfield = jQuery(this);

			var name = jQueryfield.attr('name');
			var value;

			if ('checkbox' === jQueryfield.attr('type')) {
					value = jQueryfield.prop('checked');
			} else {
					value = jQueryfield.val();
			}


			values[name] = value;
		});

		// Create the objects
		values.order_of_icons = {};

		// Loop through each active network
		jQuery('.sw-active i').each(function() {
			var network = jQuery(this).data('network');
			values.order_of_icons[network] = network;
		});

		return values;
	}

	/*********************************************************
		Header Menu
	*********************************************************/
	function headerMenuInit() {
		var offset = jQuery('.sw-top-menu').offset();

		var width = jQuery('.sw-top-menu').width();

		jQuery('.sw-top-menu').css({
			position: 'fixed',
			left: offset.left,
			top: offset.top,
			width: width
		});

		jQuery('.sw-admin-wrapper').css('padding-top', '75px');
	}

	/*********************************************************
		Tab Navigation
	*********************************************************/
	function tabNavInit() {
		jQuery('.sw-tab-selector').on('click', function(event) {
			event.preventDefault();

			jQuery('html, body').animate({ scrollTop: 0 }, 300);

			var tab = jQuery(this).attr('data-link');

			jQuery('.sw-admin-tab').hide();

			jQuery('#' + tab).show();

			jQuery('.sw-header-menu li').removeClass('sw-active-tab');

			jQuery(this).parents('li').addClass('sw-active-tab');

			if ('swp_styles' === tab) {
				socialWarfare.activateHoverStates();
			}

			swpConditionalFields();

		});
	}

	/*********************************************************
		Checkboxes
	*********************************************************/
	function checkboxesInit() {
		jQuery('.sw-checkbox-toggle').on('click', function() {
			var status = jQuery(this).attr('status');

			var elem = jQuery(this).attr('field');

			if ('on' === status) {
				jQuery(this).attr('status', 'off');

				jQuery(elem).prop('checked', false);
			} else {
				jQuery(this).attr('status', 'on');

				jQuery(elem).prop('checked', true);
			}

			saveColorToggle();

			swpConditionalFields();
		});
	}

	function populateOptions() {
		jQuery('form.sw-admin-settings-form input, form.sw-admin-settings-form select').on('change', function() {
			swpConditionalFields();

			socialWarfare.newOptions = fetchAllOptions();

			saveColorToggle();
		});

		socialWarfare.defaultOptions = fetchAllOptions();
	}

	/*********************************************************
		A Function to change the color of the save button
	 *********************************************************/
	function saveColorToggle() {
		socialWarfare.newOptions = fetchAllOptions();

		if (JSON.stringify(socialWarfare.newOptions) !== JSON.stringify(socialWarfare.defaultOptions)) {
			jQuery('.sw-save-settings').removeClass('sw-navy-button').addClass('sw-red-button');
		} else {
			jQuery('.sw-save-settings').removeClass('sw-red-button').addClass('sw-navy-button');
		}
	}

	/*********************************************************
		A Function send the array of setting to ajax.php
	*********************************************************/
	function handleSettingSave() {
		jQuery('.sw-save-settings').on('click', function(event) {
			// Block the default action
			event.preventDefault ? event.preventDefault() : (event.returnValue = false);

			// The loading screen
			loadingScreen();

			// Fetch all the settings
			var settings = fetchAllOptions();

			// Prepare date
			var data = {
				action: 'swp_store_settings',
				security: swpAdminOptionsData.optionsNonce,
				settings: settings
			};

			// Send the POST request
			jQuery.post({
				url: ajaxurl,
				data: data,
				success: function(response) {
					// Clear the loading screen
					clearLoadingScreen(true);

					// Reset the default options variable
					socialWarfare.defaultOptions = fetchAllOptions();

					saveColorToggle();
				}

			});

		});
	}

	function loadingScreen() {
		jQuery('body').append('<div class="sw-loading-bg"><div class="sw-loading-message">Saving Changes</div></div>');
	}

	function clearLoadingScreen(isSuccess) {
		var message = (isSuccess) ? 'Success!' : '';
		jQuery('.sw-loading-message').html(message).removeClass('sw-loading-message').addClass('sw-loading-complete');

		jQuery('.sw-loading-bg').delay(1000).fadeOut(1000);

		setTimeout(function() {
			jQuery('.sw-loading-bg').remove();
		}, 2000);
	}

	function updateCustomColor() {
		var visualTheme  = jQuery('select[name="button_shape"]').val();
		var dColorSet    = jQuery('select[name="default_colors"]').val();
		var iColorSet    = jQuery('select[name="single_colors"]').val();
		var oColorSet    = jQuery('select[name="hover_colors"]').val();

		jQuery('style.swp_customColorStuff').remove();

		var colorCode = jQuery('input[name="custom_color"]').val();
		var customCSS = '';

		if (dColorSet == 'custom_color' || iColorSet == 'custom_color' || oColorSet == 'custom_color') {
			customCSS = '.swp_social_panel.swp_default_customColor a, html body .swp_social_panel.swp_individual_customColor .nc_tweetContainer:hover a, body .swp_social_panel.swp_other_customColor:hover a {color:white} .swp_social_panel.swp_default_customColor .nc_tweetContainer, html body .swp_social_panel.swp_individual_customColor .nc_tweetContainer:hover, body .swp_social_panel.swp_other_customColor:hover .nc_tweetContainer {background-color:' + colorCode + ';border:1px solid ' + colorCode + ';}';
		}

		if (dColorSet == 'custom_color_outlines' || iColorSet == 'custom_color_outlines' || oColorSet == 'custom_color_outlines') {
			customCSS = customCSS + ' .swp_social_panel.swp_default_custom_color_outlines a, html body .swp_social_panel.swp_individual_custom_color_outlines .nc_tweetContainer:hover a, body .swp_social_panel.swp_other_custom_color_outlines:hover a { color:' + colorCode + '; } .swp_social_panel.swp_default_custom_color_outlines .nc_tweetContainer, html body .swp_social_panel.swp_individual_custom_color_outlines .nc_tweetContainer:hover, body .swp_social_panel.swp_other_custom_color_outlines:hover .nc_tweetContainer { background:transparent; border:1px solid ' + colorCode + '; }';
		}

		jQuery('head').append('<style type="text/css" class="swp_customColorStuff">' + customCSS + '</style>');
	}

	// A function for updating the preview
	function updateTheme() {
		var visualTheme  = getParsedValue("button_shape");
		var dColorSet    = getParsedValue("default_colors");
		var iColorSet    = getParsedValue("single_colors");
		var oColorSet    = getParsedValue("hover_colors");

		function getParsedValue(selector) {
			var value = jQuery('select[name="' + selector + '"]').val();

			if (value.indexOf("custom") === 0) {
				var prefix = selector.slice(0, selector.indexOf("_"));
				return prefix + "_full_color";
			}

			return value;
		}

		var buttonsClass = 'swp_' + visualTheme + ' swp_default_' + dColorSet + ' swp_individual_' + iColorSet + ' swp_other_' + oColorSet;

		// Declare a default lastClass based on the default HTML if we haven't declared one
		if ('undefined' === typeof socialWarfare.lastClass) {
			var panel = $(".swp_social_panel");
			if (!panel.length) {
				return;
			}
			socialWarfare.lastClass = panel.get().className;
		}

		// Put together the new classes, remove the old ones, add the new ones, store the new ones for removal next time.
		var buttonsClass = 'swp_' + visualTheme + ' swp_default_' + dColorSet + ' swp_individual_' + iColorSet + ' swp_other_' + oColorSet;


		jQuery('.swp_social_panel').removeClass("swp_other_medium_gray");
		jQuery('.swp_social_panel').removeClass(socialWarfare.lastClass).addClass(buttonsClass);

		socialWarfare.lastClass = buttonsClass;
	}

	/*********************************************************
		A Function to update the preview buttons
	*********************************************************/

	function updateButtonPreviews() {
		// Check if we are on the admin page
		if (0 === jQuery('select[name="button_shape"]').length) {
			return;
		}

		// Maps out the button themes.
		var defaults = {
			full_color: 'Full Color',
			light_gray: 'Light Gray',
			medium_gray: 'Medium Gray',
			dark_gray: 'Dark Gray',
			light_gray_outlines: 'Light Gray Outlines',
			medium_gray_outlines: 'Medium Gray Outlines',
			dark_gray_outlines: 'Dark Gray Outlines',
			color_outlines: 'Color Outlines',
			custom_color: 'Custom Color',
			custom_color_outlines: 'Custom Color Outlines'
		};

		// Defines which themes are available per style.
		var availableOptions = {
			flat_fresh: defaults,
			leaf: defaults,
			pill: defaults,
			three_dee: {
				full_color: 'Full Color',
				light_gray: 'Light Gray',
				medium_gray: 'Medium Gray',
				dark_gray: 'Dark Gray'
			},
			connected: defaults,
			shift: defaults,
			boxed: defaults,
			modern: {
				full_color: 'Full Color',
				light_gray: 'Light Gray',
				medium_gray: 'Medium Gray',
				dark_gray: 'Dark Gray',
				light_gray_outlines: 'Light Gray Outlines',
				medium_gray_outlines: 'Medium Gray Outlines',
				dark_gray_outlines: 'Dark Gray Outlines',
				color_outlines: 'Color Outlines',
				custom_color: 'Custom Color',
				custom_color_outlines: 'Custom Color Outlines'
			},
			dark: {
				light_gray_outlines: 'Light Gray Outlines',
				medium_gray_outlines: 'Medium Gray Outlines',
				dark_gray_outlines: 'Dark Gray Outlines',
				color_outlines: 'Color Outlines',
				custom_color: 'Custom Color',
				custom_color_outlines: 'Custom Color Outlines'
			}
		};

		// Update the items and previews on the initial page load
		var visualTheme = jQuery('select[name="button_shape"]').val();
		var dColorSet   = jQuery('select[name="default_colors"]').val();
		var iColorSet   = jQuery('select[name="single_colors"]').val();
		var oColorSet   = jQuery('select[name="hover_colors"]').val();

		var themeOptions = jQuery('select[name="button_shape"]').find('option').map(function(index, option) {
			return option.value;
		});

		jQuery('select[name="default_colors"] option, select[name="single_colors"] option, select[name="hover_colors"] option').remove();

		jQuery.each(availableOptions[visualTheme], function(index, value) {
			if (index === dColorSet) {
				jQuery('select[name="default_colors"]').append('<option value="' + index + '" selected>' + value + '</option>');
			} else {
				jQuery('select[name="default_colors"]').append('<option value="' + index + '">' + value + '</option>');
			}

			if (index === iColorSet) {
				jQuery('select[name="single_colors"]').append('<option value="' + index + '" selected>' + value + '</option>');
			} else {
				jQuery('select[name="single_colors"]').append('<option value="' + index + '">' + value + '</option>');
			}

			if (index === oColorSet) {
				jQuery('select[name="hover_colors"]').append('<option value="' + index + '" selected>' + value + '</option>');
			} else {
				jQuery('select[name="hover_colors"]').append('<option value="' + index + '">' + value + '</option>');
			}

			if (dColorSet == 'custom_color' || dColorSet == 'custom_color_outlines' || iColorSet == 'custom_color' || iColorSet == 'custom_color_outlines' || oColorSet == 'custom_color' || oColorSet == 'custom_color_outlines') {
				jQuery('.customColor_wrapper').slideDown();

				updateCustomColor();
			} else {
				jQuery('.customColor_wrapper').slideUp();
			}
		});

		// If the color set changes, update the preview with the function
		jQuery('select[name="default_colors"], select[name="single_colors"], select[name="hover_colors"]').on('change', updateTheme);

		// If the visual theme is updated, update the preview manually
		jQuery('select[name="button_shape"]').on('change', function() {
			var visualTheme  = jQuery('select[name="button_shape"]').val();
			var dColorSet    = jQuery('select[name="default_colors"]').val();
			var iColorSet    = jQuery('select[name="single_colors"]').val();
			var oColorSet    = jQuery('select[name="hover_colors"]').val();
			var i = 0;
			var array = availableOptions[visualTheme];
			var dColor = array.hasOwnProperty(dColorSet);
			var iColor = array.hasOwnProperty(iColorSet);
			var oColor = array.hasOwnProperty(oColorSet);

			jQuery('select[name="default_colors"] option, select[name="single_colors"] option, select[name="hover_colors"] option').remove();

			jQuery.each(availableOptions[visualTheme], function(index, value) {
				if (index === dColorSet || (dColor == false && i == 0)) {
						jQuery('select[name="default_colors"]').append('<option value="' + index + '" selected>' + value + '</option>');
				} else {
						jQuery('select[name="default_colors"]').append('<option value="' + index + '">' + value + '</option>');
				}

				if (index === iColorSet || (iColor == false && i == 0)) {
						jQuery('select[name="single_colors"]').append('<option value="' + index + '" selected>' + value + '</option>');
				} else {
						jQuery('select[name="single_colors"]').append('<option value="' + index + '">' + value + '</option>');
				}

				if (index === oColorSet || (oColor == false && i == 0)) {
						jQuery('select[name="hover_colors"]').append('<option value="' + index + '" selected>' + value + '</option>');
				} else {
						jQuery('select[name="hover_colors"]').append('<option value="' + index + '">' + value + '</option>');
				}

				++i;
			});

			// Declare a default lastClass based on the default HTML if we haven't declared one
			if('undefined' === typeof socialWarfare.lastClass){
				socialWarfare.lastClass = 'swp_flat_fresh swp_default_full_color swp_individual_full_color swp_other_full_color';
			}

			// Put together the new classes, remove the old ones, add the new ones, store the new ones for removal next time.
			var buttonsClass = 'swp_' + visualTheme + ' swp_default_' + dColorSet + ' swp_individual_' + iColorSet + ' swp_other_' + oColorSet;

			// Remove the previous theme.
			themeOptions.map(function(index, option) {
				jQuery('.swp_social_panel').removeClass('swp_' + option.value);
			})

			jQuery('.swp_social_panel').removeClass(socialWarfare.lastClass).addClass(buttonsClass);

			socialWarfare.lastClass = buttonsClass;
		});
	}

	/*********************************************************
		A Function to update the button sizing options
	 *********************************************************/
	function updateScale() {
		jQuery('select[name="button_size"],select[name="button_alignment"]').on('change', function() {
			jQuery('.swp_social_panel').css({ width: '100%' });

			var width = jQuery('.swp_social_panel').width();
			var scale = jQuery('select[name="button_size"]').val();
			var align = jQuery('select[name="button_alignment"]').val();
			var newWidth;

			if ((align == 'full_width' && scale != 1) || scale >= 1) {
					newWidth = width / scale;

					jQuery('.swp_social_panel').css('cssText', 'width:' + newWidth + 'px!important;');

					jQuery('.swp_social_panel').css({
						transform: 'scale(' + scale + ')',
						'transform-origin': 'left'
					});
			} else if (align != 'full_width' && scale < 1) {
					newWidth = width / scale;

					jQuery('.swp_social_panel').css({
						transform: 'scale(' + scale + ')',
						'transform-origin': align
					});
			}

			socialWarfare.activateHoverStates();
		});
	}

	/*********************************************************
		Update the Click To Tweet Demo
	 *********************************************************/
	function updateCttDemo() {
		var jQuerycttOptions = jQuery('select[name="ctt_theme"]');

		jQuerycttOptions.on('change', function() {
			var newStyle = jQuery('select[name="ctt_theme"]').val();

			jQuery('.swp_CTT').attr('class', 'swp_CTT').addClass(newStyle);
		});

		jQuerycttOptions.trigger('change');
	}

	function toggleRegistration(status , key) {
		var adminWrapper = jQuery(".sw-admin-wrapper");
		var addons = adminWrapper.attr("swp-addons");
		var registeredAddons = adminWrapper.attr("swp-registrations");

		// Toggle visibility of the registration input field for {key}.
		jQuery('.registration-wrapper.' + key).attr('registration', status);

		if (1 === parseInt(status)) {
			adminWrapper.attr('sw-registered', status);
			jQuery('.sw-top-menu').attr('sw-registered', status);
			addAttrValue(adminWrapper, "swp-registrations", key);
		} else {
			removeAttrValue(adminWrapper, "swp-registrations", key);
		}
	}

	// Removes a string from a given attribute.
	function removeAttrValue(el, attribute, removal) {
		var value = jQuery(el).attr(attribute);
		var startIndex = value.indexOf(removal);
		if (startIndex === -1) return;

		var stopIndex = startIndex + removal.length;
		var newValue = value.slice(0, startIndex) + value.slice(stopIndex);

		jQuery(el).attr(attribute, newValue);
	}

	// Adds a string to a given attribute.
	function addAttrValue(el, attribute, addition) {
		var value = jQuery(el).attr(attribute);
		if (value.includes(addition)) return;

		jQuery(el).attr(attribute, value + addition);
	}

	/*******************************************************
		Register an addon.
	*******************************************************/
	function registerPlugin(key, item_id) {
		var registered = false;
		var data = {
			action: 'swp_register_plugin',
			security: swpAdminOptionsData.registerNonce,
			activity: 'register',
			name_key: key,
			item_id: item_id,
			license_key: jQuery('input[name="' + key + '_license_key"]').val()
		};

		loadingScreen();

		jQuery.post(ajaxurl, data, function(response) {
			// If the response was a failure...
			response = JSON.parse(response);

			if (typeof response != 'object') {
				// bad response
				throw 'Error making addon registration request. Passed in this data ', data, ' and got this response', response;
				return;
			}

			if (!response.success) {
				var message = "This license key is not currently active. Please check the status of your key at https://warfareplugins.com/my-account/license-keys/";
				alert(message)
			} else {
				toggleRegistration('1' , key);
				registered = true;
			}

			clearLoadingScreen(registered);
			window.location.reload(true);
		});

		return registered;
	}

	/*******************************************************
		Unregister the Plugin
	*******************************************************/
	function unregisterPlugin(key,item_id) {
		var unregistered = false;
		var ajaxData = {
			action: 'swp_unregister_plugin',
			security: swpAdminOptionsData.registerNonce,
			activity: 'unregister',
			name_key: key,
			item_id: item_id,
		};

		loadingScreen();

		// Ping the home server to create a registration log
		jQuery.post(ajaxurl, ajaxData, function(response) {
			response = JSON.parse(response);

			if (!response.success) {
				var message = 'Sorry, we had trouble deactivating your key. Please let us know about this at https://warfareplugins.com/subit-ticket';
				alert(message);
			} else {
				// If the response was a success
				jQuery('input[name="'+key+'_license_key"]').val('');
				toggleRegistration('0' , key);
				unregistered = true;
			}

			clearLoadingScreen(unregistered);
			window.location.reload(true);
		});


		return unregistered;
	}

	function handleRegistration() {
		jQuery('.register-plugin').on('click', function() {
			var key = jQuery(this).attr('swp-addon');
			var item_id = jQuery(this).attr('swp-item-id').trim();
			registerPlugin(key,item_id);
			return false;
		});

		jQuery('.unregister-plugin').on('click', function() {
			var key = jQuery(this).attr('swp-addon');
			var item_id = jQuery(this).attr('swp-item-id').trim();
			unregisterPlugin(key,item_id);
			return false;
		});
	}

	/*******************************************************
		Make the buttons sortable
	*******************************************************/
	function sortableInit() {
		jQuery('.sw-buttons-sort.sw-active').sortable({
			connectWith: '.sw-buttons-sort.sw-inactive',
			update: function() {
				saveColorToggle();
			}
		});

		jQuery('.sw-buttons-sort.sw-inactive').sortable({
			connectWith: '.sw-buttons-sort.sw-active',
			update: function() {
				saveColorToggle();
			}
		});
	}

	function getSystemStatus() {
		jQuery('.sw-system-status').on('click', function(event) {
			event.preventDefault();

			jQuery('.system-status-wrapper').slideToggle();

			selectText(jQuery('.system-status-container').get(0));
		});
	}

	/*********************************************************
		A Function for image upload buttons
	*********************************************************/
	function customUploaderInit() {
		var customUploader;

		jQuery('.swp_upload_image_button').click(function(e) {
			e.preventDefault();

			var inputField = jQuery(this).attr('for');

			// If the uploader object has already been created, reopen the dialog
			if (customUploader) {
				customUploader.open();
				return;
			}

			// Extend the wp.media object
			customUploader = wp.media.frames.file_frame = wp.media({
				title: 'Choose Image',
				button: {
					text: 'Choose Image'
				},
				multiple: false
			});

			// When a file is selected, grab the URL and set it as the text field's value
			customUploader.on('select', function() {
				var attachment = customUploader.state().get('selection').first().toJSON();

				jQuery('input[name="' + inputField + '"').val(attachment.url);
			});

			// Open the uploader dialog
			customUploader.open();
		});
	}

	function set_ctt_preview() {
	  var preview = jQuery("#ctt_preview");
	  var select = jQuery("select[name=ctt_theme]");

	  if (!preview.length) {
		preview = jQuery('<style id="ctt_preview"></style>');
		jQuery("head").append(preview);
	  }

	  if (jQuery(select).val() === "none") {
		update_ctt_preview();
	  }

	  jQuery(select).on("change", function(e) {
		if (e.target.value === 'none') {
			update_ctt_preview();
		}
	  });

	  jQuery("textarea[name=ctt_css]").on("keyup", update_ctt_preview);
	}

	function update_ctt_preview() {
		var preview = jQuery("#ctt_preview");
		var textarea = jQuery("textarea[name=ctt_css]");
		jQuery(preview).text(jQuery(textarea).val());
	}

	// Addes a tooltip to a network icon, displaying the network's name.
	function createTooltip(event) {
		var tooltip;
		var icon = event.target;
		var network = jQuery(icon).data("network");
		var networkBounds = icon.getBoundingClientRect();
		var tooltipBounds = {};
		var knownMargin = 4; // Paddig applied by CSS which must be accounted for.
		var css = {
			top: jQuery(icon).position().top - 50,
			left: jQuery(icon).position().left + knownMargin
		}

		// Uppercase each part of a snake_cased name.
		if (network.indexOf("_") > 0) {
			var words = network.split("_").map(function(word) {
				return word[0].toUpperCase() + word.slice(1, word.length)
			});

			network = words.join(" ");
		}

		// Uppercase the first character of the name.
		network = network[0].toUpperCase() + network.slice(1, network.length);

		tooltip = jQuery('<span class="swp-icon-tooltip">' + network + '</span>').css(css).get(0);
		jQuery(this).parents(".sw-grid").first().append(tooltip);

		// When tooltip is wider than icon, center tooltip over the icon.
		if (jQuery(tooltip).outerWidth() > jQuery(icon).outerWidth()) {
			var delta = jQuery(tooltip).outerWidth() - jQuery(icon).outerWidth();
			css.left = css.left - (delta / 2);
			jQuery(tooltip).css(css);
		}

		// Give it a click listener to remove the tooltip after moving the mouse.
		jQuery(icon).on("mousedown", function(e) {

			jQuery("body").mousemove(function() {
				removeTooltip();
				jQuery("body").off("mousemove");
			});
		});
	}

	function removeTooltip(event) {
		jQuery(".swp-icon-tooltip").remove();
	}

	function addIconTooltips() {
		jQuery("[class*='sw-'][class*='-icon']").each(function(index, icon) {
			jQuery(icon).hover(createTooltip, removeTooltip);
		});
	}

	jQuery(document).ready(function() {
		handleSettingSave();
		populateOptions();
		headerMenuInit();
		tabNavInit();
		checkboxesInit();
		updateButtonPreviews();
		swpConditionalFields();
		updateCttDemo();
		updateScale();
		handleRegistration();
		sortableInit();
		getSystemStatus();
		customUploaderInit();
		set_ctt_preview();
	  addIconTooltips();
	});
})(this, jQuery);
