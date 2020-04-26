<?php

/**
 * Definition of Various Attributes of the Plugin
 *
 * This file contains definition of shortcode and formfield attributes of the
 * plugin.
 *
 * @link /lib/wfu_attributes.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 2.1.2
 */

/**
 * Definition of Uploader Form Elements
 *
 * This function defines the elements of the plugin upload form.
 *
 * @since 2.1.2
 *
 * @return array The list of uploader form elements (components).
 */
function wfu_component_definitions() {
	$components = array(
		array(
			"id"				=> "title",
			"name"				=> "Title",
			"mode"				=> "free",
			"dimensions"		=> array("plugin/Plugin", "title/Title"),
			"multiplacements"	=> false,
			"help"				=> "A title text for the plugin"
		),
		array(
			"id"				=> "filename",
			"name"				=> "Filename",
			"mode"				=> "free",
			"dimensions"		=> null,
			"multiplacements"	=> false,
			"help"				=> "It shows the name of the selected file (useful only for single file uploads)."
		),
		array(
			"id"				=> "selectbutton",
			"name"				=> "Select Button",
			"mode"				=> "free",
			"dimensions"		=> null,
			"multiplacements"	=> false,
			"help"				=> "Represents the button to select the files for upload."
		),
		array(
			"id"				=> "uploadbutton",
			"name"				=> "Upload Button",
			"mode"				=> "free",
			"dimensions"		=> null,
			"multiplacements"	=> false,
			"help"				=> "Represents the button to execute the upload after some files have been selected."
		),
		array(
			"id"				=> "subfolders",
			"name"				=> "Subfolders",
			"mode"				=> "free",
			"dimensions"		=> array("uploadfolder_label/Upload Folder Label", "subfolders/Subfolders", "subfolders_label/Subfolders Label", "subfolders_select/Subfolders List"),
			"multiplacements"	=> false,
			"help"				=> "Allows the user to select the upload folder from a dropdown list."
		),
		array(
			"id"				=> "webcam",
			"name"				=> "Webcam",
			"mode"				=> "commercial",
			"dimensions"		=> array("webcam/Webcam Box"),
			"multiplacements"	=> false,
			"help"				=> "Displays video from the device's webcam. The user can capture and upload screenshots or video streams."
		),
		array(
			"id"				=> "progressbar",
			"name"				=> "Progressbar",
			"mode"				=> "free",
			"dimensions"		=> null,
			"multiplacements"	=> false,
			"help"				=> "Displays a simple progress bar, showing total progress of upload."
		),
		array(
			"id"				=> "userdata",
			"name"				=> "User Fields",
			"mode"				=> "free",
			"dimensions"		=> array("userdata/User Fields", "userdata_label/User Fields Label", "userdata_value/User Fields Value"),
			"multiplacements"	=> true,
			"help"				=> "Displays additional fields that the user must fill-in together with the uploaded files."
		),
		array(
			"id"				=> "consent",
			"name"				=> "Consent",
			"mode"				=> "free",
			"dimensions"		=> array("consent/Consent Block"),
			"multiplacements"	=> false,
			"help"				=> "Displays a checkbox asking user's consent for storing personal data."
		),
		array(
			"id"				=> "message",
			"name"				=> "Message",
			"mode"				=> "free",
			"dimensions"		=> null,
			"multiplacements"	=> false,
			"help"				=> "Displays a message block with information about the upload, together with any warnings or errors."
		)
	);
	
	wfu_array_remove_nulls($components);

	return $components;
}

/**
 * Definition of Uploader Form Attribute Categories
 *
 * This function defines the categories of the plugin uploader shortcode 
 * attributes. These categories show up as different tabs of the shortcode
 * composer.
 *
 * @since 2.1.2
 *
 * @return array The list of uploader form attribute categories.
 */
function wfu_category_definitions() {
	$cats = array(
		"general"			=> "General",
		"placements"		=> "Placements",
		"labels"			=> "Labels",
		"notifications"		=> "Notifications",
		"personaldata"		=> "Personal Data",
		"colors"			=> "Colors",
		"dimensions"		=> "Dimensions",
		"userdata"			=> "Additional Fields",
		"interoperability"	=> "Interoperability",
		"webcam"			=> "Webcam"
	);

	return $cats;
}

/**
 * Definition of Uploader Form Custom Fields
 *
 * This function defines the plugin upload form custom fields and their
 * attributes.
 *
 * @since 3.3.0
 *
 * @return array The list of upload form custom fields.
 */
function wfu_formfield_definitions() {
	//field properties have 2 parts separated by "/"; the first part determines if the property will be shown to the user (show or hide); the second part determines default value)
	//when making changes in the structure of formfield definitions, the following are affected:
	//  - wfu_admin_composer.php function wfu_shortcode_composer
	//      variable $fieldprops_basic
	//      variable $fieldprops_default
	//      variable $template
	//      variable wfu_attribute_..._typeprops
	//      variable $from_template
	//  - wfu_functions.php function wfu_parse_userdata_attribute
	//      variable $default
	//      variable $fieldprops
	//  - wfu_blocks.php function wfu_userdata_apply_template
	//      return variable
	//  - wordpress_file_upload_adminfuctions.js function wfu_formdata_type_changed
	//      variable field
	//  - wordpress_file_upload_adminfuctions.js function wfu_formdata_add_field
	//      variable field
	//  - wordpress_file_upload_adminfuctions.js function wfu_formdata_prepare_template
	//      variable fieldprops_basic
	//      variable template
	//  - wordpress_file_upload_adminfuctions.js function wfu_update_formfield_value
	//      variable part
	//  - wordpress_file_upload_adminfuctions.js function wfu_apply_value
	//      variable def
	//      variable fieldprops
	$formfields = array(
		array(
			"type"						=> "text",
			"type_description"			=> "Simple Text",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then this field must have a non-empty value for the file to be uploaded",
			"donotautocomplete"			=> "show/false",
			"donotautocomplete_hint"	=> "if checked, then the field will notify the browsers not to fill it with autocomplete data when the plugin is reloaded",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "complete a list of values to be shown to the user",
			"group"						=> "hide/",
			"group_hint"				=> "if a value is set, then all fields having the same value will belong to the same group",
			"format"					=> "hide/",
			"format_hint"				=> "enter a format to format user selection"
		),
		array(
			"type"						=> "multitext",
			"type_description"			=> "Multiple Lines Text",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then this field must have a non-empty value for the file to be uploaded",
			"donotautocomplete"			=> "hide/true",
			"donotautocomplete_hint"	=> "if checked, then the field will notify the browsers not to fill it with autocomplete data when the plugin is reloaded",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "hide/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "complete a list of values to be shown to the user",
			"group"						=> "hide/",
			"group_hint"				=> "if a value is set, then all fields having the same value will belong to the same group",
			"format"					=> "hide/",
			"format_hint"				=> "enter a format to format user selection"
		),
		array(
			"type"						=> "number",
			"type_description"			=> "Number",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then this field must have a non-empty value for the file to be uploaded",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will notify the browsers not to fill it with autocomplete data when the plugin is reloaded",
			"validate"					=> "show/true",
			"validate_hint"				=> "if checked, then the number entered by the user will be checked if it is a valid number, based on the format defined, before file upload",
			"typehook"					=> "show/false",
			"typehook_hint"				=> "if checked, then only valid characters will be allowed during typing",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "complete a list of values to be shown to the user",
			"group"						=> "hide/",
			"group_hint"				=> "if a non-empty group value is set, then another email confirmation field belonging to the same group must have the same email value",
			"format"					=> "show/d",
			"format_hint"				=> "enter a format for the number:\\r\\n  d for integers\\r\\n  f for floating point numbers\\r\\nthe dot (.) symbol is used as a decimal separator"
		),
		array(
			"type"						=> "email",
			"type_description"			=> "Email",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then this field must have a non-empty value for the file to be uploaded",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will notify the browsers not to fill it with autocomplete data when the plugin is reloaded",
			"validate"					=> "show/true",
			"validate_hint"				=> "if checked, then the email entered by the user will be checked if it is valid before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "complete a list of values to be shown to the user",
			"group"						=> "show/0",
			"group_hint"				=> "if a non-empty group value is set, then another email confirmation field belonging to the same group must have the same email value",
			"format"					=> "hide/",
			"format_hint"				=> "enter a format to format user selection"
		),
		array(
			"type"						=> "confirmemail",
			"type_description"			=> "Confirmation Email",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/true",
			"required_hint"				=> "if checked, then this field must have a non-empty value for the file to be uploaded",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will notify the browsers not to fill it with autocomplete data when the plugin is reloaded",
			"validate"					=> "hide/true",
			"validate_hint"				=> "if checked, then the confirmation email entered by the user will be checked if it is the same with the email belonging to the same group",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "hide/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "complete a list of values to be shown to the user",
			"group"						=> "show/0",
			"group_hint"				=> "enter a non-empty value to match this email confirmation field with another email field",
			"format"					=> "hide/",
			"format_hint"				=> "enter a format to format user selection"
		),
		array(
			"type"						=> "password",
			"type_description"			=> "Password",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/true",
			"required_hint"				=> "if checked, then this field must have a non-empty value for the file to be uploaded",
			"donotautocomplete"			=> "false/true",
			"donotautocomplete_hint"	=> "if checked, then the field will notify the browsers not to fill it with autocomplete data when the plugin is reloaded",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the value entered by the user will be validated",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "hide/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "complete a list of values to be shown to the user",
			"group"						=> "show/0",
			"group_hint"				=> "if a non-empty group value is set, then another password confirmation field belonging to the same group must have the same password",
			"format"					=> "hide/",
			"format_hint"				=> "enter a format to format user selection"
		),
		array(
			"type"						=> "confirmpassword",
			"type_description"			=> "Confirmation Password",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/true",
			"required_hint"				=> "if checked, then this field must have a non-empty value for the file to be uploaded",
			"donotautocomplete"			=> "false/true",
			"donotautocomplete_hint"	=> "if checked, then the field will notify the browsers not to fill it with autocomplete data when the plugin is reloaded",
			"validate"					=> "hide/true",
			"validate_hint"				=> "if checked, then the value entered by the user will be validated",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "hide/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "complete a list of values to be shown to the user",
			"group"						=> "show/0",
			"group_hint"				=> "if a non-empty group value is set, then another password confirmation field belonging to the same group must have the same password",
			"format"					=> "hide/",
			"format_hint"				=> "enter a format to format user selection"
		),
		array(
			"type"						=> "checkbox",
			"type_description"			=> "Checkbox",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then this checkbox field must be checked before file upload",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will not be autocompleted by browsers",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/none",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/false",
			"default_hint"				=> "enter a default value (true or false) for the field or leave it empty if you do not want a default value",
			"data"						=> "show/",
			"data_label"				=> "Description",
			"data_hint"					=> "enter a description for the checkbox",
			"group"						=> "hide/",
			"group_hint"				=> "if a value is set, then all fields having the same value will belong to the same group",
			"format"					=> "show/right",
			"format_hint"				=> "define the location of the description in relation to the check box\\r\\npossible values are: top, right, bottom, left"
		),
		array(
			"type"						=> "radiobutton",
			"type_description"			=> "Radio button",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then a radio button must be selected before file upload",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will not be autocompleted by browsers",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "show/",
			"data_label"				=> "Items",
			"data_hint"					=> "enter a comma delimited list of radio button items",
			"group"						=> "show/0",
			"group_hint"				=> "all radio buttons having the same group id belong to the same group",
			"format"					=> "show/",
			"format_hint"				=> "define the location of the radio labels in relation to their radio buttons (top, right, bottom, left)\\r\\nand the placement of the radio buttons (horizontal, vertical)"
		),
		array(
			"type"						=> "date",
			"type_description"			=> "Date",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then a date must be entered before file upload",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will not be autocompleted by browsers",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default date for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "enter data items",
			"group"						=> "hide/",
			"group_hint"				=> "enter a group value",
			"format"					=> "show/",
			"format_hint"				=> "define the format of the date field as follows:\\r\\n  d - day of month (no leading zero)\\r\\n  dd - day of month (two digit)\\r\\n  o - day of the year (no leading zeros)\\r\\n  oo - day of the year (three digit)\\r\\n  D - day name short\\r\\n  DD - day name long\\r\\n  m - month of year (no leading zero)\\r\\n  mm - month of year (two digit)\\r\\n  M - month name short\\r\\n  MM - month name long\\r\\n  y - year (two digit)\\r\\n  yy - year (four digit)\\r\\n  @ - Unix timestamp (ms since 01/01/1970)\\r\\n  ! - Windows ticks (100ns since 01/01/0001)\\r\\n  &#39;...&#39; - literal text\\r\\n  &#39;&#39; - single quote\\r\\n  anything else - literal text\\r\\nthe format must be in parenthesis ()"
		),
		array(
			"type"						=> "time",
			"type_description"			=> "Time",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then a time must be entered before file upload",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will not be autocompleted by browsers",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default time for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "enter data items",
			"group"						=> "hide/",
			"group_hint"				=> "enter a group value",
			"format"					=> "show/",
			"format_hint"				=> "define the format of the time field as follows:\\r\\n  H - hour with no leading 0 (24 hour)\\r\\n  HH - hour with leading 0 (24 hour)\\r\\n  h - hour with no leading 0 (12 hour)\\r\\n  hh - hour with leading 0 (12 hour)\\r\\n  m - minute with no leading 0\\r\\n  mm - minute with leading 0\\r\\n  s - second with no leading 0\\r\\n  ss - second with leading 0\\r\\n  l - milliseconds always with leading 0\\r\\n  c - microseconds always with leading 0\\r\\n  t - a or p for AM/PM\\r\\n  T - A or P for AM/PM\\r\\n  tt - am or pm for AM/PM\\r\\n  TT - AM or PM for AM/PM\\r\\n  z - timezone as defined by timezoneList\\r\\n  Z - timezone in Iso 8601 format (+04:45)\\r\\n  &#39;...&#39; - literal text\\r\\nthe format must be in parenthesis ()"
		),
		array(
			"type"						=> "datetime",
			"type_description"			=> "DateTime",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then a date and time must be entered before file upload",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will not be autocompleted by browsers",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default date and time for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "enter data items",
			"group"						=> "hide/",
			"group_hint"				=> "enter a group value",
			"format"					=> "show/",
			"format_hint"				=> "define the format of the datetime field as follows:\\r\\n  date(dateformat) where dateformat is:\\r\\n    d - day of month (no leading zero)\\r\\n    dd - day of month (two digit)\\r\\n    o - day of the year (no leading zeros)\\r\\n    oo - day of the year (three digit)\\r\\n    D - day name short\\r\\n    DD - day name long\\r\\n    m - month of year (no leading zero)\\r\\n    mm - month of year (two digit)\\r\\n    M - month name short\\r\\n    MM - month name long\\r\\n    y - year (two digit)\\r\\n    yy - year (four digit)\\r\\n    @ - Unix timestamp (ms since 01/01/1970)\\r\\n    ! - Windows ticks (100ns since 01/01/0001)\\r\\n    &#39;...&#39; - literal text\\r\\n    &#39;&#39; - single quote\\r\\n    anything else - literal text\\r\\n  time(timeformat) where timeformat is:\\r\\n    H - hour with no leading 0 (24 hour)\\r\\n    HH - hour with leading 0 (24 hour)\\r\\n    h - hour with no leading 0 (12 hour)\\r\\n    hh - hour with leading 0 (12 hour)\\r\\n    m - minute with no leading 0\\r\\n    mm - minute with leading 0\\r\\n    s - second with no leading 0\\r\\n    ss - second with leading 0\\r\\n    l - milliseconds always with leading 0\\r\\n    c - microseconds always with leading 0\\r\\n    t - a or p for AM/PM\\r\\n    T - A or P for AM/PM\\r\\n    tt - am or pm for AM/PM\\r\\n    TT - AM or PM for AM/PM\\r\\n    z - timezone as defined by timezoneList\\r\\n    Z - timezone in Iso 8601 format (+04:45)\\r\\n    &#39;...&#39; - literal text"
		),
		array(
			"type"						=> "list",
			"type_description"			=> "Listbox",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then a list item must be selected before file upload",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will not be autocompleted by browsers",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "show/",
			"data_label"				=> "List Items",
			"data_hint"					=> "enter a comma delimited list of items",
			"group"						=> "hide/",
			"group_hint"				=> "all items having the same group id belong to the same group",
			"format"					=> "hide/",
			"format_hint"				=> "enter the format of the list"
		),
		array(
			"type"						=> "dropdown",
			"type_description"			=> "Dropdown",
			//label properties
			"label"						=> "",
			"label_label"				=> "Label",
			"label_hint"				=> "enter the label that will be shown next to the field",
			//checkbox properties
			"required"					=> "show/false",
			"required_hint"				=> "if checked, then an item from the dropdown list must be selected before file upload",
			"donotautocomplete"			=> "show/true",
			"donotautocomplete_hint"	=> "if checked, then the field will not be autocompleted by browsers",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "show/left",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "show/right",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "show/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "show/",
			"data_label"				=> "List Items",
			"data_hint"					=> "enter a comma delimited list of items",
			"group"						=> "hide/",
			"group_hint"				=> "all items having the same group id belong to the same group",
			"format"					=> "hide/",
			"format_hint"				=> "enter the format of the list"
		),
		array(
			"type"						=> "honeypot",
			"type_description"			=> "Hidden Honeypot",
			//label properties
			"label"						=> "website",
			"label_label"				=> "Name",
			"label_hint"				=> "enter the name of the honeypot field; it must be a value that bots can easily recognize, like \'website\' or \'URL\'",
			//checkbox properties
			"required"					=> "hide/false",
			"required_hint"				=> "if checked, then this field must have a non-empty value for the file to be uploaded",
			"donotautocomplete"			=> "hide/true",
			"donotautocomplete_hint"	=> "if checked, then the field will notify the browsers not to fill it with autocomplete data when the plugin is reloaded",
			"validate"					=> "hide/false",
			"validate_hint"				=> "if checked, then the field value entered by the user will be validated before file upload",
			"typehook"					=> "hide/false",
			"typehook_hint"				=> "if checked, then text suggestions will be shown below the field as the user types more text inside",
			//dropdown properties
			"labelposition"				=> "hide/none",
			"labelposition_hint"		=> "select the position of the field&#39;s label; the position is relative to the field",
			"hintposition"				=> "hide/none",
			"hintposition_hint"			=> "select the position of the hint that will be shown to notify the user that something is wrong\\r\\nthe position is relative to the field",
			//text properties
			"default"					=> "hide/",
			"default_hint"				=> "enter a default value for the field or leave it empty if you do not want a default value",
			"data"						=> "hide/",
			"data_label"				=> "Data",
			"data_hint"					=> "complete a list of values to be shown to the user",
			"group"						=> "hide/",
			"group_hint"				=> "if a value is set, then all fields having the same value will belong to the same group",
			"format"					=> "hide/",
			"format_hint"				=> "enter a format to format user selection"
		)
	);
	
	return $formfields;
}

/**
 * Definition of Uploader Form Attributes
 *
 * This function defines the plugin uploader shortcode attributes.
 *
 * @since 2.1.2
 *
 * @return array The list of uploader form attributes.
 */
function wfu_attribute_definitions() {
	$defs = array(
		array(
			"name"		=> "Widget ID",
			"attribute"	=> "widgetid",
			"type"		=> "hidden",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> "",
			"mode"		=> "free",
			"category"	=> "",
			"subcategory"	=> "Basic ",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> ""
		),
		array(
			"name"		=> "Plugin ID",
			"attribute"	=> "uploadid",
			"type"		=> "integer",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_UPLOADID"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Basic Functionalities",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "The unique id of each shortcode. When you have many shortcodes of this plugin in the same page, then you must use different id for each one."
		),
		array(
			"name"		=> "Single Button Operation",
			"attribute"	=> "singlebutton",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_SINGLEBUTTON"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Basic Functionalities",
			"parent"	=> "",
			"dependencies"	=> array("!uploadbutton"),
			"variables"	=> null,
			"help"		=> "When it is activated, no Upload button will be shown, but upload will start automatically as soon as files are selected."
		),
		array(
			"name"		=> "Upload Path",
			"attribute"	=> "uploadpath",
			"type"		=> "ltext",
			"validator"	=> "path",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_UPLOADPATH"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Basic Functionalities",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> array("%userid%", "%username%", "%blogid%", "%pageid%", "%pagetitle%", "%userdataXXX%"),
			"help"		=> "This is the folder where the files will be uploaded. The path is relative to wp-contents folder of your Wordpress website. The path can be dynamic by including variables such as %username% or %blogid%. Please check Documentation on how to use variables inside uploadpath."
		),
		array(
			"name"		=> "Plugin Fit Mode",
			"attribute"	=> "fitmode",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("fixed", "responsive"),
			"value"		=> WFU_VAR("WFU_FITMODE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Basic Functionalities",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "This defines how the plugin's elements will fit inside the page/post. If it is set to fixed, then the plugin's element positions will remain fixed no matter the width of the container page/post. If it is set to responsive, then the plugin's elements will wrap to fit the width of the container page/post."
		),
		array(
			"name"		=> "Allow No File",
			"attribute"	=> "allownofile",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_ALLOWNOFILE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Basic Functionalities",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "When it is activated a user can submit the upload form even if a file is not selected."
		),
		array(
			"name"		=> "Reset Form Mode",
			"attribute"	=> "resetmode",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("always", "onsuccess", "never"),
			"value"		=> WFU_VAR("WFU_RESETMODE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Basic Functionalities",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines whether the form will be reset after upload; 'always' means that it will be reset in any case, 'onsuccess' means that it will be reset only if upload was successful, 'never' means that it will never be reset."
		),
		array(
			"name"		=> "Upload Roles",
			"attribute"	=> "uploadrole",
			"type"		=> "rolelist",
			"validator"	=> "text",
			"listitems"	=> array("default_administrator"),
			"value"		=> WFU_VAR("WFU_UPLOADROLE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Filters",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Defines the categories (roles) of users allowed to upload files. Multiple selections can be made. If 'Select All' is checked, then all logged users can upload files. If 'Include Guests' is checked, then guests (not logged users) can also upload files. Default value is 'all,guests'."
		),
		array(
			"name"		=> "Allowed File Extensions",
			"attribute"	=> "uploadpatterns",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_UPLOADPATTERNS"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Filters",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Defines the allowed file extensions. Multiple extentions can be defined, separated with comma (,)."
		),
		array(
			"name"		=> "Allowed File Size",
			"attribute"	=> "maxsize",
			"type"		=> "float",
			"validator"	=> "float",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_MAXSIZE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Filters",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Defines the allowed file size in MBytes. Files larger than maxsize will not be uploaded. Floating point numbers can be used (e.g. '2.5')."
		),
		array(
			"name"		=> "Create Upload Path",
			"attribute"	=> "createpath",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_CREATEPATH"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If activated then the plugin will attempt to create the upload path, if it does not exist."
		),
		array(
			"name"		=> "Do Not Change Filename",
			"attribute"	=> "forcefilename",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_FORCEFILENAME"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "The plugin by default will modify the filename if it contains invalid or non-english characters. By enabling this attribute the plugin will not change the filename."
		),
		array(
			"name"		=> "Folder Access Method",
			"attribute"	=> "accessmethod",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("normal", "*ftp"),
			"value"		=> WFU_VAR("WFU_ACCESSMETHOD"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "",
			"dependencies"	=> array("ftpinfo", "userftpdomain", "ftppassivemode", "ftpfilepermissions"),
			"variables"	=> null,
			"help"		=> "Some times files cannot be uploaded to the upload folder because of read/write permissions. A workaround is to use ftp to transfer the files, however ftp credentials must be declared, so use carefully and only if necessary."
		),
		array(
			"name"		=> "FTP Access Credentials",
			"attribute"	=> "ftpinfo",
			"type"		=> "ftpinfo",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_FTPINFO"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "accessmethod",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If FTP access method is selected, then FTP credentials must be declared here, in the form username:password@ftpdomain:port, e.g. myusername:mypass@ftpdomain.com:80. Port can be ommitted. The user can use Secure FTP (sftp) by putting the prefix 's' before the port number, e.g. myusername:mypass@ftpdomain.com:s22."
		),
		array(
			"name"		=> "Use FTP Domain",
			"attribute"	=> "useftpdomain",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_USEFTPDOMAIN"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "accessmethod",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If FTP access method is selected, then sometimes the FTP domain is different than the domain of your Wordpress installation. In this case, enable this attribute if upload of files is not successful."
		),
		array(
			"name"		=> "FTP Passive Mode",
			"attribute"	=> "ftppassivemode",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_FTPPASSIVEMODE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "accessmethod",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If files fail to upload to the ftp domain then switching to passive FTP mode may solve the problem."
		),
		array(
			"name"		=> "Permissions of Uploaded File",
			"attribute"	=> "ftpfilepermissions",
			"type"		=> "text",
			"validator"	=> "integer",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_FTPFILEPERMISSIONS"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "accessmethod",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Force the uploaded files to have specific permissions. This is a 4-digit octal number, e.g. 0777. If left empty, then the ftp server will define the permissions."
		),
		array(
			"name"		=> "Show Upload Folder Path",
			"attribute"	=> "showtargetfolder",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_SHOWTARGETFOLDER"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "",
			"dependencies"	=> array("targetfolderlabel"),
			"variables"	=> null,
			"help"		=> "It defines if a label with the upload directory will be shown."
		),
		array(
			"name"		=> "Select Subfolder",
			"attribute"	=> "askforsubfolders",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_ASKFORSUBFOLDERS"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "",
			"dependencies"	=> array("subfoldertree", "subfolderlabel"),
			"variables"	=> null,
			"help"		=> "If enabled then user can select the upload folder from a drop down list. The list is defined in subfoldertree attribute. The folder paths are relative to the path defined in uploadpath."
		),
		array(
			"name"		=> "List of Subfolders",
			"attribute"	=> "subfoldertree",
			"type"		=> "folderlist",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_SUBFOLDERTREE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "askforsubfolders",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "The list of folders a user can select. Please see documentation on how to create the list of folders. If 'Auto-populate list' is selected, then the list will be filled automatically with the first-level subfolders inside the directory defined by uploadpath. If 'List is editable' is selected, then the user will have the capability to type the subfolder and filter the subfolder list and/or define a new subfolder."
		),
		array(
			"name"		=> "File Duplicates Policy",
			"attribute"	=> "duplicatespolicy",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("overwrite", "reject", "*maintain both"),
			"value"		=> WFU_VAR("WFU_DUBLICATESPOLICY"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "",
			"dependencies"	=> array("uniquepattern"),
			"variables"	=> null,
			"help"		=> "It determines what happens when an uploaded file has the same name with an existing file. The uploaded file can overwrite the existing one, be rejected or both can be kept by renaming the uploaded file according to a rule defined in uniquepattern attribute."
		),
		array(
			"name"		=> "File Rename Rule",
			"attribute"	=> "uniquepattern",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("index", "datetimestamp"),
			"value"		=> WFU_VAR("WFU_UNIQUEPATTERN"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Upload Path and Files",
			"parent"	=> "duplicatespolicy",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If duplicatespolicy is set to 'maintain both', then this rule defines how the uploaded file will be renamed, in order not to match an existing file. An incremental index number or a datetime stamp can be included in the uploaded file name to make it unique."
		),
		array(
			"name"		=> "Redirect after Upload",
			"attribute"	=> "redirect",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_REDIRECT"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Redirection",
			"parent"	=> "",
			"dependencies"	=> array("redirectlink"),
			"variables"	=> null,
			"help"		=> "If enabled, the user will be redirected to a url defined in redirectlink attribute upon successful upload of all the files."
		),
		array(
			"name"		=> "Redirection URL",
			"attribute"	=> "redirectlink",
			"type"		=> "ltext",
			"validator"	=> "link",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_REDIRECTLINK"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Redirection",
			"parent"	=> "redirect",
			"dependencies"	=> null,
			"variables"	=> array("%filename%", "%username%"),
			"help"		=> "This is the redirect URL. The URL can be dynamic by using variables. Please see Documentation on how to use variables inside attributes."
		),
		array(
			"name"		=> "Show Detailed Admin Messages",
			"attribute"	=> "adminmessages",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_ADMINMESSAGES"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Other Administrator Options",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If enabled then more detailed messages about upload operations will be shown to administrators for debugging or error detection."
		),
		array(
			"name"		=> "Disable AJAX",
			"attribute"	=> "forceclassic",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_FORCECLASSIC"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Other Administrator Options",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If AJAX is disabled, then upload of files will be performed using HTML forms, meaning that page will refresh to complete the upload. Use it in case that AJAX is causing problems with your page (although the plugin has an auto-detection feature for checking if user's browser supports AJAX or not)."
		),
		array(
			"name"		=> "Test Mode",
			"attribute"	=> "testmode",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_TESTMODE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Other Administrator Options",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If enabled then the plugin will be shown in test mode, meaning that all selected features will be shown but no upload will be possible. Use it to review how the plugin looks like and style it according to your needs."
		),
		array(
			"name"		=> "Debug Mode",
			"attribute"	=> "debugmode",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_DEBUGMODE"),
			"mode"		=> "free",
			"category"	=> "general",
			"subcategory"	=> "Other Administrator Options",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If enabled then the plugin will show to administrators any internal PHP warnings and errors generated during the upload process inside the message box."
		),
		array(
			"name"		=> "Plugin Component Positions",
			"attribute"	=> "placements",
			"type"		=> "placements",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_PLACEMENTS"),
			"mode"		=> "free",
			"category"	=> "placements",
			"subcategory"	=> "Plugin Component Positions",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the positions of the selected plugin components. Drag the components from the right pane and drop them to the left one to define your own component positions."
		),
		array(
			"name"		=> "Plugin Title",
			"attribute"	=> "uploadtitle",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_UPLOADTITLE,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Title",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "A text representing the title of the plugin."
		),
		array(
			"name"		=> "Select Button Caption",
			"attribute"	=> "selectbutton",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_SELECTBUTTON,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Buttons",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "The caption of the button that selects the files for upload."
		),
		array(
			"name"		=> "Upload Button Caption",
			"attribute"	=> "uploadbutton",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_UPLOADBUTTON,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Buttons",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "The caption of the button that starts the upload."
		),
		array(
			"name"		=> "Upload Folder Label",
			"attribute"	=> "targetfolderlabel",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_TARGETFOLDERLABEL"),
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Upload Folder",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "This is the label before the upload folder path, if the path is selected to be shown using the showtargetfolder attribute."
		),
		array(
			"name"		=> "Select Subfolder Label",
			"attribute"	=> "subfolderlabel",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_SUBFOLDERLABEL"),
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Upload Folder",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "This is the label of the subfolder dropdown list. It is active when askforsubfolders attribute is on."
		),
		array(
			"name"		=> "Success Upload Message",
			"attribute"	=> "successmessage",
			"type"		=> "ltext",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_SUCCESSMESSAGE,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Upload Messages",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> array("%filename%", "%filepath%"),
			"help"		=> "This is the message that will be shown for every file that has been uploaded successfully."
		),
		array(
			"name"		=> "Warning Upload Message",
			"attribute"	=> "warningmessage",
			"type"		=> "ltext",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_WARNINGMESSAGE,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Upload Messages",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> array("%filename%", "%filepath%"),
			"help"		=> "This is the message that will be shown for every file that has been uploaded with warnings."
		),
		array(
			"name"		=> "Error Upload Message",
			"attribute"	=> "errormessage",
			"type"		=> "ltext",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_ERRORMESSAGE,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Upload Messages",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> array("%filename%", "%filepath%"),
			"help"		=> "This is the message that will be shown for every file that has failed to upload."
		),
		array(
			"name"		=> "Wait Upload Message",
			"attribute"	=> "waitmessage",
			"type"		=> "ltext",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_WAITMESSAGE,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Upload Messages",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> array("%filename%", "%filepath%"),
			"help"		=> "This is the message that will be shown while file is uploading."
		),
		array(
			"name"		=> "Upload Media Button Caption",
			"attribute"	=> "uploadmediabutton",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_UPLOADMEDIABUTTON,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Webcam Labels",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "The caption of the button that starts the upload when media capture from the webcam has been activated."
		),
		array(
			"name"		=> "Video Filename",
			"attribute"	=> "videoname",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VIDEONAME,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Webcam Labels",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> array("%userid%", "%username%", "%blogid%", "%pageid%", "%pagetitle%", "%userdataXXX%"),
			"help"		=> "This is the file name of the captured video file."
		),
		array(
			"name"		=> "Image Filename",
			"attribute"	=> "imagename",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_IMAGENAME,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Webcam Labels",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> array("%userid%", "%username%", "%blogid%", "%pageid%", "%pagetitle%", "%userdataXXX%"),
			"help"		=> "This is the file name of the captured image file."
		),
		array(
			"name"		=> "Required Fields Suffix",
			"attribute"	=> "requiredlabel",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_USERDATA_REQUIREDLABEL,
			"mode"		=> "free",
			"category"	=> "labels",
			"subcategory"	=> "Other Labels",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "This is the keyword that shows up next to user field labels in order to denote that they are required."
		),
		array(
			"name"		=> "Notify by Email",
			"attribute"	=> "notify",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_NOTIFY"),
			"mode"		=> "free",
			"category"	=> "notifications",
			"subcategory"	=> "Email Notifications",
			"parent"	=> "",
			"dependencies"	=> array("notifyrecipients", "notifysubject", "notifymessage", "notifyheaders", "attachfile"),
			"variables"	=> null,
			"help"		=> "If activated then email will be sent to inform about successful file uploads."
		),
		array(
			"name"		=> "Email Recipients",
			"attribute"	=> "notifyrecipients",
			"type"		=> "mtext",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_NOTIFYRECIPIENTS"),
			"mode"		=> "free",
			"category"	=> "notifications",
			"subcategory"	=> "Email Notifications",
			"parent"	=> "notify",
			"dependencies"	=> null,
			"variables"	=> array("%useremail%", "%userdataXXX%", "%n%", "%dq%"),
			"help"		=> "Defines the recipients of the email notification. Can be dynamic by using variables. Please check Documentation on how to use variables in atributes."
		),
		array(
			"name"		=> "Email Headers",
			"attribute"	=> "notifyheaders",
			"type"		=> "mtext",
			"validator"	=> "emailheaders",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_NOTIFYHEADERS"),
			"mode"		=> "free",
			"category"	=> "notifications",
			"subcategory"	=> "Email Notifications",
			"parent"	=> "notify",
			"dependencies"	=> null,
			"variables"	=> array("%n%", "%dq%"),
			"help"		=> "Defines additional email headers, in case you want to sent an HTML message, or use Bcc list, or use a different From address and name or other more advanced email options."
		),
		array(
			"name"		=> "Email Subject",
			"attribute"	=> "notifysubject",
			"type"		=> "ltext",
			"validator"	=> "emailsubject",
			"listitems"	=> null,
			"value"		=> WFU_NOTIFYSUBJECT,
			"mode"		=> "free",
			"category"	=> "notifications",
			"subcategory"	=> "Email Notifications",
			"parent"	=> "notify",
			"dependencies"	=> null,
			"variables"	=> array("%username%", "%useremail%", "%filename%", "%filepath%", "%blogid%", "%pageid%", "%pagetitle%", "%userdataXXX%", "%dq%"),
			"help"		=> "Defines the email subject. Can be dynamic by using variables. Please check Documentation on how to use variables in atributes."
		),
		array(
			"name"		=> "Email Body",
			"attribute"	=> "notifymessage",
			"type"		=> "mtext",
			"validator"	=> "emailbody",
			"listitems"	=> null,
			"value"		=> WFU_NOTIFYMESSAGE,
			"mode"		=> "free",
			"category"	=> "notifications",
			"subcategory"	=> "Email Notifications",
			"parent"	=> "notify",
			"dependencies"	=> null,
			"variables"	=> array("%username%", "%useremail%", "%filename%", "%filepath%", "%blogid%", "%pageid%", "%pagetitle%", "%userdataXXX%", "%n%", "%dq%"),
			"help"		=> "Defines the email body. Can be dynamic by using variables. Please check Documentation on how to use variables in atributes."
		),
		array(
			"name"		=> "Attach Uploaded Files",
			"attribute"	=> "attachfile",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_ATTACHFILE"),
			"mode"		=> "free",
			"category"	=> "notifications",
			"subcategory"	=> "Email Notifications",
			"parent"	=> "notify",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If activated, then uploaded files will be included in the notification email as attachments. Please use carefully."
		),
		array(
			"name"		=> "Ask for Consent",
			"attribute"	=> "askconsent",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_ASKCONSENT"),
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "General Personal Data Options",
			"parent"	=> "",
			"dependencies"	=> array("personaldatatypes"),
			"variables"	=> null,
			"help"		=> "If activated, then consent from users will be asked for storing their personal data. If users do not give consent, then their data will not be stored in the database, they will only be included in the notification email, if email notifications are active."
		),
		array(
			"name"		=> "Personal Data Types",
			"attribute"	=> "personaldatatypes",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("userdata", "userdata and files"),
			"value"		=> WFU_VAR("WFU_PERSONALDATATYPES"),
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "General Personal Data Options",
			"parent"	=> "askconsent",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Determines which data are considered as personal data. By default only userdata are considered as personal data. If the 2nd option is selected, then files will also be considered as personal data. This means that if the users do not give their consent, then the files will not be uploaded on the website, they will only be inluded in the notification email as attachments, if email notifications are active."
		),
		array(
			"name"		=> "Do Not Remember Consent Answer",
			"attribute"	=> "notrememberconsent",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_NOTREMEMBERCONSENT"),
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "Consent Behaviour",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If activated the plugin will not remember the consent answer provided by the user and the consent question will always show."
		),
		array(
			"name"		=> "Consent Denial Rejects Upload",
			"attribute"	=> "consentrejectupload",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_CONSENTREJECTUPLOAD"),
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "Consent Behaviour",
			"parent"	=> "",
			"dependencies"	=> array("consentrejectmessage"),
			"variables"	=> null,
			"help"		=> "If activated and user has denied consent then the upload will be rejected. If deactivated, then the upload will continue regardless of consent answer."
		),
		array(
			"name"		=> "Reject Message",
			"attribute"	=> "consentrejectmessage",
			"type"		=> "ltext",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_CONSENTREJECTMESSAGE,
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "Consent Behaviour",
			"parent"	=> "consentrejectupload",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the message that will appear to the user if upload cannot continue due to consent denial."
		),
		array(
			"name"		=> "Consent Format",
			"attribute"	=> "consentformat",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("checkbox", "radio", "prompt"),
			"value"		=> WFU_VAR("WFU_CONSENTFORMAT"),
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "Consent Appearance",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Determines how consent question will appear to the user. If 'checkbox' is selected then a checkbox will appear inside the upload form which the user needs to tick. If 'radio' is selected then a radio button with 'Yes' and 'No' answers will appear inside the form (this makes sure that the user will select something after all. If 'prompt' is selected then a dialog will appear on the user when pressing the upload button asking for consent."
		),
		array(
			"name"		=> "Preselected Answer",
			"attribute"	=> "consentpreselect",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("none", "yes", "no"),
			"value"		=> WFU_VAR("WFU_CONSENTPRESELECT"),
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "Consent Appearance",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Determines whether a default answer will be selected."
		),
		array(
			"name"		=> "Consent Question for Checkbox",
			"attribute"	=> "consentquestion",
			"type"		=> "ltext",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_CONSENTQUESTION,
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "Consent Appearance",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Defines the question that will appear to the user next to the checkbox, or radio buttons or inside the prompt dialog. If a word starting and ending with semicolon (:) is added in the question, e.g. :link:, then it will be replaced by a link defined in 'Consent Disclaimer Link' attribute. This way a link to a disclaimer can be added."
		),
		array(
			"name"		=> "Consent Disclaimer Link",
			"attribute"	=> "consentdisclaimer",
			"type"		=> "ltext",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_CONSENTDISCLAIMER"),
			"mode"		=> "free",
			"category"	=> "personaldata",
			"subcategory"	=> "Consent Appearance",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "Defines a link that displays a disclaimer to the user if the user presses the relevant link that is included inside the consent question."
		),
		array(
			"name"		=> "Success Upload Message Color",
			"attribute"	=> "successmessagecolor",
			"type"		=> "hidden",
			"validator"	=> "colors",
			"listitems"	=> null,
			"value"		=> WFU_SUCCESSMESSAGECOLOR,
			"mode"		=> "free",
			"category"	=> "colors",
			"subcategory"	=> "Upload Message Colors",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the color of the success message. This attribute has been replaced by successmessagecolors, however it is kept here for backward compatibility."
		),
		array(
			"name"		=> "Success Message Colors",
			"attribute"	=> "successmessagecolors",
			"type"		=> "color-triplet",
			"validator"	=> "colors",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_SUCCESSMESSAGECOLORS"),
			"mode"		=> "free",
			"category"	=> "colors",
			"subcategory"	=> "Upload Message Colors",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the text, background and border color of the success message."
		),
		array(
			"name"		=> "Warning Message Colors",
			"attribute"	=> "warningmessagecolors",
			"type"		=> "color-triplet",
			"validator"	=> "colors",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_WARNINGMESSAGECOLORS"),
			"mode"		=> "free",
			"category"	=> "colors",
			"subcategory"	=> "Upload Message Colors",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the text, background and border color of the warning message."
		),
		array(
			"name"		=> "Fail Message Colors",
			"attribute"	=> "failmessagecolors",
			"type"		=> "color-triplet",
			"validator"	=> "colors",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_FAILMESSAGECOLORS"),
			"mode"		=> "free",
			"category"	=> "colors",
			"subcategory"	=> "Upload Message Colors",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the text, background and border color of the fail (error) message."
		),
		array(
			"name"		=> "Wait Message Colors",
			"attribute"	=> "waitmessagecolors",
			"type"		=> "hidden",
			"validator"	=> "colors",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_WAITMESSAGECOLORS"),
			"mode"		=> "free",
			"category"	=> "colors",
			"subcategory"	=> "Upload Message Colors",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the text, background and border color of the wait message."
		),
		array(
			"name"		=> "Plugin Component Widths",
			"attribute"	=> "widths",
			"type"		=> "dimensions",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_WIDTHS"),
			"mode"		=> "free",
			"category"	=> "dimensions",
			"subcategory"	=> "Plugin Component Widths",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the widths of the selected plugin components."
		),
		array(
			"name"		=> "Plugin Component Heights",
			"attribute"	=> "heights",
			"type"		=> "dimensions",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_HEIGHTS"),
			"mode"		=> "free",
			"category"	=> "dimensions",
			"subcategory"	=> "Plugin Component Heights",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the heights of the selected plugin components."
		),
		array(
			"name"		=> "Include Additional Data Fields",
			"attribute"	=> "userdata",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_USERDATA"),
			"mode"		=> "free",
			"category"	=> "userdata",
			"subcategory"	=> "Additional Data Fields",
			"parent"	=> "",
			"dependencies"	=> array("userdatalabel"),
			"variables"	=> null,
			"help"		=> "If enabled, then user can send additional information together with uploaded files (e.g. name, email etc), defined in userdatalabel attribute."
		),
		array(
			"name"		=> "Additional Data Fields",
			"attribute"	=> "userdatalabel",
			"type"		=> "formfields",
			"validator"	=> "text",
			"listitems"	=> wfu_formfield_definitions(),
			"value"		=> WFU_USERDATALABEL,
			"mode"		=> "free",
			"category"	=> "userdata",
			"subcategory"	=> "Additional Data Fields",
			"parent"	=> "userdata",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the labels of the additional data fields and whether they are required or not."
		),
		array(
			"name"		=> "WP Filebase Plugin Connection",
			"attribute"	=> "filebaselink",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_FILEBASELINK"),
			"mode"		=> "free",
			"category"	=> "interoperability",
			"subcategory"	=> "Connection With Other Plugins",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If enabled then the WP Filebase Plugin will be informed about new file uploads."
		),
		array(
			"name"		=> "Add Uploaded Files To Media",
			"attribute"	=> "medialink",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_MEDIALINK"),
			"mode"		=> "free",
			"category"	=> "interoperability",
			"subcategory"	=> "Connection With Other Wordpress Features",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If enabled then the uploaded files will be added to the Media library of your Wordpress website. Please note that the upload path must be inside the wp-content/uploads directory (which is the default upload path)."
		),
		array(
			"name"		=> "Attach Uploaded Files To Post",
			"attribute"	=> "postlink",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_POSTLINK"),
			"mode"		=> "free",
			"category"	=> "interoperability",
			"subcategory"	=> "Connection With Other Wordpress Features",
			"parent"	=> "",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "If enabled then the uploaded files will be added to the current post as attachments. Please note that the upload path must be inside the wp-content/uploads directory (which is the default upload path)."
		),
		array(
			"name"		=> "Enable Webcam",
			"attribute"	=> "webcam",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_WEBCAM"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "",
			"dependencies"	=> array("webcammode", "audiocapture", "videowidth", "videoheight", "videoaspectratio", "videoframerate", "camerafacing", "maxrecordtime", "uploadmediabutton", "videoname", "imagename"),
			"variables"	=> null,
			"help"		=> "This enables capturing of video or still pictures from the computer's webcam. It is experimental because it is not supported by all browsers yet."
		),
		array(
			"name"		=> "Capture Mode",
			"attribute"	=> "webcammode",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("capture video", "take photos", "both"),
			"value"		=> WFU_VAR("WFU_WEBCAMMODE"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "webcam",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the webcam capture mode. The webcam can either capture video, still photos or both."
		),
		array(
			"name"		=> "Capture Audio",
			"attribute"	=> "audiocapture",
			"type"		=> "onoff",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_AUDIOCAPTURE"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "webcam",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines whether audio will be captured together with video from the webcam."
		),
		array(
			"name"		=> "Video Width",
			"attribute"	=> "videowidth",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_VIDEOWIDTH"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "webcam",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It requests a preferable video width for the webcam. The plugin will try to match this setting as close as possible depending on webcam capabilities."
		),
		array(
			"name"		=> "Video Height",
			"attribute"	=> "videoheight",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_VIDEOHEIGHT"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "webcam",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It requests a preferable video height for the webcam. The plugin will try to match this setting as close as possible depending on webcam capabilities."
		),
		array(
			"name"		=> "Video Aspect Ratio",
			"attribute"	=> "videoaspectratio",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_VIDEOASPECTRATIO"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "webcam",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It requests a preferable video aspect ratio for the webcam. The plugin will try to match this setting as close as possible depending on webcam capabilities."
		),
		array(
			"name"		=> "Video Frame Rate",
			"attribute"	=> "videoframerate",
			"type"		=> "text",
			"validator"	=> "text",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_VIDEOFRAMERATE"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "webcam",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It requests a preferable video frame rate for video recording. The plugin will try to match this setting as close as possible depending on webcam capabilities."
		),
		array(
			"name"		=> "Camera Facing Mode",
			"attribute"	=> "camerafacing",
			"type"		=> "radio",
			"validator"	=> "text",
			"listitems"	=> array("any", "front", "back"),
			"value"		=> WFU_VAR("WFU_CAMERAFACING"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "webcam",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines if the front or back camera will be preferred (for mobile devices with 2 cameras). The plugin will try to match this setting depending on webcam capabilities."
		),
		array(
			"name"		=> "Max Record Time",
			"attribute"	=> "maxrecordtime",
			"type"		=> "integer",
			"validator"	=> "integer",
			"listitems"	=> null,
			"value"		=> WFU_VAR("WFU_MAXRECORDTIME"),
			"mode"		=> "free",
			"category"	=> "webcam",
			"subcategory"	=> "Capture from Webcam (experimental)",
			"parent"	=> "webcam",
			"dependencies"	=> null,
			"variables"	=> null,
			"help"		=> "It defines the maximum time of video recording (in seconds). If it is set to -1, then there is no time limit."
		),
		null
	);

	wfu_array_remove_nulls($defs);
	

	return $defs;
}


?>
