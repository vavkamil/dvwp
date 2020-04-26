<?php

/**
 * Personal Data Definitions
 *
 * This file contains definitions for personal data policies.
 *
 * @link /lib/wfu_pd_definitions.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 4.5.0
 */

/**
 * Define Personal Data Types.
 *
 * This function defines the personal data types.
 *
 * @since 4.5.0
 *
 * @return array An array of personal data types definitions.
 */
function wfu_personal_data_types() {
	$types = array(
		array(		"ID"			=> 1,
					"Name"			=> "File",
					"Slug"			=> "file",
					"Description"	=> "This type refers to uploaded files, which may be considered as personal data.",
					"Generic"		=> 0,
					"Default"		=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 2,
					"Name"			=> "File Data",
					"Slug"			=> "filedata",
					"Description"	=> "This type refers to data captured by the plugin during file upload: upload time, page ID, blog ID and shortcode ID. Though not related to the user, they may be considered as personal data.",
					"Generic"		=> 1,
					"Default"		=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 3,
					"Name"			=> "User Profile Data",
					"Slug"			=> "profiledata",
					"Description"	=> "This type refers to user data (user ID, user name, user email etc.) that the plugin reads from user's profile. By default they are considered as personal data.",
					"Generic"		=> 1,
					"Default"		=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 4,
					"Name"			=> "User Provided Data",
					"Slug"			=> "provideddata",
					"Description"	=> "This type refers to data provided by the user during file upload through the additional fields that may be added to the upload form. They may be considered as personal data.",
					"Generic"		=> 0,
					"Default"		=> 1,
					"Data"			=> ""
		)
	);
	
	/**
	 * Let Custom Scripts Modify Personal Data Types.
	 *
	 * This filter allows custom scripts to modify personal data types
	 * definitions.
	 *
	 * @since 4.5.0
	 *
	 * @param array $types The personal data types definitions.
	 */
	return apply_filters("_wfu_personal_data_types", $types);
}

/**
 * Define Personal Data Operations.
 *
 * This function defines the personal data operations.
 *
 * @since 4.5.0
 *
 * @return array An array of personal data operations definitions.
 */
function wfu_personal_data_operations() {
	$operations = array(
		array(		"ID"			=> 1,
					"Name"			=> "All",
					"Slug"			=> "all",
					"Description"	=> "The top-most level grouped operation covering all other operations.",
					"Order"			=> 1,
					"Parent"		=> 0,
					"Datatypes"		=> array(),
					"Condition"		=> "",
					"DefAllowed"	=> 0,
					"DefConsent"	=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 2,
					"Name"			=> "Store",
					"Slug"			=> "store",
					"Description"	=> "2nd level grouped operation covering all store operations.",
					"Order"			=> 1,
					"Parent"		=> 1,
					"Datatypes"		=> array(),
					"Condition"		=> "",
					"DefAllowed"	=> 0,
					"DefConsent"	=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 3,
					"Name"			=> "Locally",
					"Slug"			=> "store_local",
					"Description"	=> "3rd level grouped operation covering all local storage operations.",
					"Order"			=> 1,
					"Parent"		=> 2,
					"Datatypes"		=> array(),
					"Condition"		=> "",
					"DefAllowed"	=> 0,
					"DefConsent"	=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 4,
					"Name"			=> "in File System",
					"Slug"			=> "store_fs",
					"Description"	=> "4th level atomic operation for storage in the file system.",
					"Order"			=> 1,
					"Parent"		=> 3,
					"Datatypes"		=> array(1),
					"Condition"		=> "",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 5,
					"Name"			=> "in Database",
					"Slug"			=> "store_db",
					"Description"	=> "4th level atomic operation for storage in the database.",
					"Order"			=> 2,
					"Parent"		=> 3,
					"Datatypes"		=> array(2, 3, 4),
					"Condition"		=> "",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 6,
					"Name"			=> "in Session",
					"Slug"			=> "store_session",
					"Description"	=> "4th level atomic operation for storage in session/cookies.",
					"Order"			=> 3,
					"Parent"		=> 3,
					"Datatypes"		=> array(2, 3, 4),
					"Condition"		=> "",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 7,
					"Name"			=> "Externally",
					"Slug"			=> "store_external",
					"Description"	=> "3rd level grouped operation covering all external storage operations.",
					"Order"			=> 2,
					"Parent"		=> 2,
					"Datatypes"		=> array(),
					"Condition"		=> "",
					"DefAllowed"	=> 0,
					"DefConsent"	=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 8,
					"Name"			=> "in FTP",
					"Slug"			=> "store_ftp",
					"Description"	=> "4th level atomic operation for storage in an external FTP server.",
					"Order"			=> 1,
					"Parent"		=> 7,
					"Datatypes"		=> array(1),
					"Condition"		=> "",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 9,
					"Name"			=> "in Dropbox",
					"Slug"			=> "store_dropbox",
					"Description"	=> "4th level atomic operation for storage in a Dropbox account.",
					"Order"			=> 2,
					"Parent"		=> 7,
					"Datatypes"		=> array(1),
					"Condition"		=> "",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 10,
					"Name"			=> "Use",
					"Slug"			=> "use",
					"Description"	=> "2nd level grouped operation covering all use operations.",
					"Order"			=> 2,
					"Parent"		=> 1,
					"Datatypes"		=> array(),
					"Condition"		=> "",
					"DefAllowed"	=> 0,
					"DefConsent"	=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 11,
					"Name"			=> "in Back-end",
					"Slug"			=> "use_backend",
					"Description"	=> "3rd level grouped operation covering all use operations executed in back-end (Dashboard) by admins.",
					"Order"			=> 1,
					"Parent"		=> 10,
					"Datatypes"		=> array(),
					"Condition"		=> "",
					"DefAllowed"	=> 0,
					"DefConsent"	=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 12,
					"Name"			=> "List",
					"Slug"			=> "list_backend",
					"Description"	=> "4th level atomic operation for listing / showing data in back-end (Dashboard) by admins.",
					"Order"			=> 1,
					"Parent"		=> 11,
					"Datatypes"		=> array(1, 2, 3, 4),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 13,
					"Name"			=> "Modify",
					"Slug"			=> "modify_backend",
					"Description"	=> "4th level atomic operation for renaming / modifying data in back-end (Dashboard) by admins.",
					"Order"			=> 2,
					"Parent"		=> 11,
					"Datatypes"		=> array(1, 4),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 14,
					"Name"			=> "Download",
					"Slug"			=> "download_backend",
					"Description"	=> "4th level atomic operation for downloading / exporting data in back-end (Dashboard) by admins.",
					"Order"			=> 3,
					"Parent"		=> 11,
					"Datatypes"		=> array(1, 2, 3, 4),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 15,
					"Name"			=> "Delete",
					"Slug"			=> "delete_backend",
					"Description"	=> "4th level atomic operation for deleting data in back-end (Dashboard) by admins.",
					"Order"			=> 4,
					"Parent"		=> 11,
					"Datatypes"		=> array(1),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 16,
					"Name"			=> "in Hooks",
					"Slug"			=> "use_hooks",
					"Description"	=> "4th level atomic operation for using data in hooks.",
					"Order"			=> 5,
					"Parent"		=> 11,
					"Datatypes"		=> array(1, 2, 3, 4),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 17,
					"Name"			=> "in Hooks",
					"Slug"			=> "use_hooks",
					"Description"	=> "4th level atomic operation for using data in hooks.",
					"Order"			=> 5,
					"Parent"		=> 11,
					"Datatypes"		=> array(3),
					"Condition"		=> "!2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 18,
					"Name"			=> "in Front-end",
					"Slug"			=> "use_frontend",
					"Description"	=> "3rd level grouped operation covering all use operations executed in front-end (posts, pages) by users.",
					"Order"			=> 2,
					"Parent"		=> 10,
					"Datatypes"		=> array(),
					"Condition"		=> "",
					"DefAllowed"	=> 0,
					"DefConsent"	=> 0,
					"Data"			=> ""
		), array(	"ID"			=> 19,
					"Name"			=> "List",
					"Slug"			=> "list_frontend",
					"Description"	=> "4th level atomic operation for listing / showing data in front-end (posts, pages) by users.",
					"Order"			=> 1,
					"Parent"		=> 18,
					"Datatypes"		=> array(1, 2, 3, 4),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 20,
					"Name"			=> "Preview",
					"Slug"			=> "preview_frontend",
					"Description"	=> "4th level atomic operation for previewing files / data (show thumbnails) in front-end (posts, pages) by users.",
					"Order"			=> 2,
					"Parent"		=> 18,
					"Datatypes"		=> array(1),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 21,
					"Name"			=> "Open",
					"Slug"			=> "open_frontend",
					"Description"	=> "4th level atomic operation for opening files / data (opening their links) in front-end (posts, pages) by users.",
					"Order"			=> 3,
					"Parent"		=> 18,
					"Datatypes"		=> array(1),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 22,
					"Name"			=> "Download",
					"Slug"			=> "download_frontend",
					"Description"	=> "4th level atomic operation for downloading files / data in front-end (posts, pages) by users.",
					"Order"			=> 4,
					"Parent"		=> 18,
					"Datatypes"		=> array(1),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 23,
					"Name"			=> "Delete",
					"Slug"			=> "delete_frontend",
					"Description"	=> "4th level atomic operation for deleting data in front-end (posts, pages) by users.",
					"Order"			=> 5,
					"Parent"		=> 18,
					"Datatypes"		=> array(1),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 24,
					"Name"			=> "in Notification Email",
					"Slug"			=> "use_email",
					"Description"	=> "3rd level atomic operation for including data in the notification email sent when a file is uploaded.",
					"Order"			=> 3,
					"Parent"		=> 10,
					"Datatypes"		=> array(1, 2, 3, 4),
					"Condition"		=> "2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 25,
					"Name"			=> "in Notification Email",
					"Slug"			=> "use_email",
					"Description"	=> "3rd level atomic operation for including data in the notification email sent when a file is uploaded.",
					"Order"			=> 3,
					"Parent"		=> 10,
					"Datatypes"		=> array(3),
					"Condition"		=> "!2",
					"DefAllowed"	=> 1,
					"DefConsent"	=> 1,
					"Data"			=> ""
		)
	);
	
	/**
	 * Let Custom Scripts Modify Personal Data Operations.
	 *
	 * This filter allows custom scripts to modify personal data operations
	 * definitions.
	 *
	 * @since 4.5.0
	 *
	 * @param array $types The personal data operations definitions.
	 */
	return apply_filters("_wfu_personal_data_operations", $operations);
}

/**
 * Define Personal Data Locations.
 *
 * This function defines the personal data locations.
 *
 * @since 4.5.0
 *
 * @return array An array of personal data locations definitions.
 */
function wfu_personal_data_locations() {
	$locations = array(
		array(		"ID"			=> 1,
					"Name"			=> "Plugin Area",
					"Slug"			=> "plugin_area",
					"Description"	=> "This location refers to a separate section of the plugin in Dashboard.",
					"Data"			=> ""
		), array(	"ID"			=> 2,
					"Name"			=> "User Profile",
					"Slug"			=> "user_profile",
					"Description"	=> "This location refers to the user profile section in Dashboard.",
					"Data"			=> ""
		), array(	"ID"			=> 3,
					"Name"			=> "Request to Admin",
					"Slug"			=> "admin_request",
					"Description"	=> "This location refers to a request from a user to the admin, through email, form or another location, for executing actions on personal data.",
					"Data"			=> ""
		), array(	"ID"			=> 4,
					"Name"			=> "Code",
					"Slug"			=> "code",
					"Description"	=> "This location refers to executing actions on personal data programmatically.",
					"Data"			=> ""
		)
	);
	
	/**
	 * Let Custom Scripts Modify Personal Data Locations.
	 *
	 * This filter allows custom scripts to modify personal data locations
	 * definitions.
	 *
	 * @since 4.5.0
	 *
	 * @param array $types The personal data locations definitions.
	 */
	return apply_filters("_wfu_personal_data_locations", $locations);
}

/**
 * Define Personal Data Permissions.
 *
 * This function defines the personal data permissions.
 *
 * @since 4.5.0
 *
 * @return array An array of personal data permissions definitions.
 */
function wfu_personal_data_permissions() {
	$permissions = array(
		array(		"ID"			=> 1,
					"Name"			=> "User",
					"Slug"			=> "user",
					"Description"	=> "This is a grouped permission referring to all permissions of users.",
					"Order"			=> 1,
					"Parent"		=> 0,
					"Locations"		=> array(),
					"Default"		=> array(),
					"Data"			=> ""
		), array(	"ID"			=> 2,
					"Name"			=> "Consent",
					"Slug"			=> "user_consent",
					"Description"	=> "This is a grouped permission referring to all consent operations (grant, review, revoke) of users.",
					"Order"			=> 1,
					"Parent"		=> 1,
					"Locations"		=> array(),
					"Default"		=> array(),
					"Data"			=> ""
		), array(	"ID"			=> 3,
					"Name"			=> "Review",
					"Slug"			=> "user_review_consent",
					"Description"	=> "This is an atomic permission for users to review consent.",
					"Order"			=> 1,
					"Locations"		=> array(1, 2, 3),
					"Default"		=> array(1),
					"Parent"		=> 2,
					"Data"			=> ""
		), array(	"ID"			=> 4,
					"Name"			=> "Revoke",
					"Slug"			=> "user_revoke_consent",
					"Description"	=> "This is an atomic permission for users to revoke consent.",
					"Order"			=> 2,
					"Locations"		=> array(1, 2, 3),
					"Default"		=> array(1),
					"Parent"		=> 2,
					"Data"			=> ""
		), array(	"ID"			=> 5,
					"Name"			=> "Grant",
					"Slug"			=> "user_grant_consent",
					"Description"	=> "This is an atomic permission for users to grant consent.",
					"Order"			=> 3,
					"Locations"		=> array(3),
					"Default"		=> array(3),
					"Parent"		=> 2,
					"Data"			=> ""
		), array(	"ID"			=> 6,
					"Name"			=> "Personal Data",
					"Slug"			=> "user_personaldata",
					"Description"	=> "This is a grouped permission referring to all personal data operations (preview, get, delete) of users.",
					"Order"			=> 2,
					"Locations"		=> array(),
					"Default"		=> array(),
					"Parent"		=> 1,
					"Data"			=> ""
		), array(	"ID"			=> 7,
					"Name"			=> "Preview",
					"Slug"			=> "user_preview_personaldata",
					"Description"	=> "This is an atomic permission for users to preview personal data.",
					"Order"			=> 1,
					"Locations"		=> array(1, 2, 3),
					"Default"		=> array(1),
					"Parent"		=> 6,
					"Data"			=> ""
		), array(	"ID"			=> 8,
					"Name"			=> "Get",
					"Slug"			=> "user_get_personaldata",
					"Description"	=> "This is an atomic permission for users to get / export personal data.",
					"Order"			=> 2,
					"Locations"		=> array(1, 2, 3),
					"Default"		=> array(1),
					"Parent"		=> 6,
					"Data"			=> ""
		), array(	"ID"			=> 9,
					"Name"			=> "Delete",
					"Slug"			=> "user_delete_personaldata",
					"Description"	=> "This is an atomic permission for users to delete personal data.",
					"Order"			=> 3,
					"Locations"		=> array(1, 2, 3),
					"Default"		=> array(3),
					"Parent"		=> 6,
					"Data"			=> ""
		), array(	"ID"			=> 10,
					"Name"			=> "API",
					"Slug"			=> "api",
					"Description"	=> "This is a grouped permission referring to all API permissions.",
					"Order"			=> 2,
					"Parent"		=> 0,
					"Locations"		=> array(),
					"Default"		=> array(),
					"Data"			=> ""
		), array(	"ID"			=> 11,
					"Name"			=> "Consent",
					"Slug"			=> "api_consent",
					"Description"	=> "This is a grouped permission referring to all consent operations (grant, review, revoke) of API.",
					"Order"			=> 1,
					"Parent"		=> 10,
					"Locations"		=> array(),
					"Default"		=> array(),
					"Data"			=> ""
		), array(	"ID"			=> 12,
					"Name"			=> "Review",
					"Slug"			=> "api_review_consent",
					"Description"	=> "This is an atomic permission for API to review consent.",
					"Order"			=> 1,
					"Locations"		=> array(4),
					"Default"		=> array(4),
					"Parent"		=> 11,
					"Data"			=> ""
		), array(	"ID"			=> 13,
					"Name"			=> "Revoke",
					"Slug"			=> "api_revoke_consent",
					"Description"	=> "This is an atomic permission for API to revoke consent.",
					"Order"			=> 2,
					"Locations"		=> array(4),
					"Default"		=> array(4),
					"Parent"		=> 11,
					"Data"			=> ""
		), array(	"ID"			=> 14,
					"Name"			=> "Grant",
					"Slug"			=> "api_grant_consent",
					"Description"	=> "This is an atomic permission for API to grant consent.",
					"Order"			=> 3,
					"Locations"		=> array(4),
					"Default"		=> array(4),
					"Parent"		=> 11,
					"Data"			=> ""
		), array(	"ID"			=> 15,
					"Name"			=> "Personal Data",
					"Slug"			=> "api_personaldata",
					"Description"	=> "This is a grouped permission referring to all personal data operations (preview, get, delete) of API.",
					"Order"			=> 2,
					"Locations"		=> array(),
					"Default"		=> array(),
					"Parent"		=> 10,
					"Data"			=> ""
		), array(	"ID"			=> 16,
					"Name"			=> "Preview",
					"Slug"			=> "api_preview_personaldata",
					"Description"	=> "This is an atomic permission for API to preview personal data.",
					"Order"			=> 1,
					"Locations"		=> array(4),
					"Default"		=> array(4),
					"Parent"		=> 15,
					"Data"			=> ""
		), array(	"ID"			=> 17,
					"Name"			=> "Get",
					"Slug"			=> "api_get_personaldata",
					"Description"	=> "This is an atomic permission for API to get / export personal data.",
					"Order"			=> 2,
					"Locations"		=> array(4),
					"Default"		=> array(4),
					"Parent"		=> 15,
					"Data"			=> ""
		), array(	"ID"			=> 18,
					"Name"			=> "Delete",
					"Slug"			=> "api_delete_personaldata",
					"Description"	=> "This is an atomic permission for API to delete personal data.",
					"Order"			=> 3,
					"Locations"		=> array(4),
					"Default"		=> array(4),
					"Parent"		=> 15,
					"Data"			=> ""
		)
	);
	
	/**
	 * Let Custom Scripts Modify Personal Data Permissions.
	 *
	 * This filter allows custom scripts to modify personal data permissions
	 * definitions.
	 *
	 * @since 4.5.0
	 *
	 * @param array $types The personal data permissions definitions.
	 */
	return apply_filters("_wfu_personal_data_permissions", $permissions);
}

/**
 * Define Personal Data Entities.
 *
 * This function defines the personal data entities.
 *
 * @since 4.5.0
 *
 * @return array An array of personal data entities definitions.
 */
function wfu_personal_data_entities() {
	$entities = array(
		array(		"ID"			=> 1,
					"Name"			=> "Admin",
					"Slug"			=> "admin",
					"Description"	=> "This entity refers to administrators.",
					"Data"			=> ""
		), array(	"ID"			=> 2,
					"Name"			=> "User",
					"Slug"			=> "user",
					"Description"	=> "This entity refers to logged users.",
					"Data"			=> ""
		), array(	"ID"			=> 3,
					"Name"			=> "Guest",
					"Slug"			=> "guest",
					"Description"	=> "This entity refers to non-logged users (guests).",
					"Data"			=> ""
		), array(	"ID"			=> 4,
					"Name"			=> "API",
					"Slug"			=> "api",
					"Description"	=> "This entity refers to API executing actions on personal data.",
					"Data"			=> ""
		)
	);
	
	/**
	 * Let Custom Scripts Modify Personal Data Entities.
	 *
	 * This filter allows custom scripts to modify personal data entities
	 * definitions.
	 *
	 * @since 4.5.0
	 *
	 * @param array $types The personal data entities definitions.
	 */
	return apply_filters("_wfu_personal_data_entities", $entities);
}

/**
 * Define Personal Data Log Actions.
 *
 * This function defines the personal data log actions.
 *
 * @since 4.5.0
 *
 * @return array An array of personal data log actions definitions.
 */
function wfu_personal_data_logactions() {
	$logactions = array(
		array(		"ID"			=> 1,
					"Name"			=> "All Log Actions",
					"Slug"			=> "all_logactions",
					"Description"	=> "This is a grouped log action referring to all log actions.",
					"Order"			=> 1,
					"Parent"		=> 0,
					"Entities"		=> array(),
					"Default"		=> array(),
					"Data"			=> ""
		), array(	"ID"			=> 2,
					"Name"			=> "Personal Data Policy",
					"Slug"			=> "policy_logactions",
					"Description"	=> "This is a grouped log action referring to all actions on personal data policies.",
					"Order"			=> 1,
					"Parent"		=> 1,
					"Entities"		=> array(),
					"Default"		=> array(),
					"Data"			=> ""
		), array(	"ID"			=> 3,
					"Name"			=> "Create",
					"Slug"			=> "create_policy",
					"Description"	=> "This is an atomic log action when a new personal data policy is created.",
					"Order"			=> 1,
					"Parent"		=> 2,
					"Entities"		=> array(1),
					"Default"		=> array(1),
					"Data"			=> ""
		), array(	"ID"			=> 4,
					"Name"			=> "Modify",
					"Slug"			=> "modify_policy",
					"Description"	=> "This is an atomic log action when a new personal data policy is modified.",
					"Order"			=> 2,
					"Parent"		=> 2,
					"Entities"		=> array(1),
					"Default"		=> array(1),
					"Data"			=> ""
		), array(	"ID"			=> 5,
					"Name"			=> "Delete",
					"Slug"			=> "delete_policy",
					"Description"	=> "This is an atomic log action when a new personal data policy is deleted.",
					"Order"			=> 3,
					"Parent"		=> 2,
					"Entities"		=> array(1),
					"Default"		=> array(1),
					"Data"			=> ""
		), array(	"ID"			=> 6,
					"Name"			=> "Consent",
					"Slug"			=> "consent_logactions",
					"Description"	=> "This is a grouped log action referring to all actions on consents.",
					"Order"			=> 2,
					"Parent"		=> 1,
					"Entities"		=> array(),
					"Default"		=> array(),
					"Data"			=> ""
		), array(	"ID"			=> 7,
					"Name"			=> "Grant",
					"Slug"			=> "grant_consent",
					"Description"	=> "This is an atomic log action when a consent is granted.",
					"Order"			=> 1,
					"Parent"		=> 6,
					"Entities"		=> array(1, 2, 4),
					"Default"		=> array(1, 2, 4),
					"Data"			=> ""
		), array(	"ID"			=> 8,
					"Name"			=> "Revoke",
					"Slug"			=> "revoke_consent",
					"Description"	=> "This is an atomic log action when a consent is revoked.",
					"Order"			=> 2,
					"Parent"		=> 6,
					"Entities"		=> array(1, 2, 4),
					"Default"		=> array(1, 2, 4),
					"Data"			=> ""
		), array(	"ID"			=> 9,
					"Name"			=> "Personal Data",
					"Slug"			=> "personaldata_logactions",
					"Description"	=> "This is a grouped log action referring to all actions on personal data.",
					"Order"			=> 3,
					"Parent"		=> 1,
					"Entities"		=> array(),
					"Default"		=> array(),
					"Data"			=> ""
		), array(	"ID"			=> 10,
					"Name"			=> "Download",
					"Slug"			=> "download_personaldata",
					"Description"	=> "This is an atomic log action when personal data are downloaded.",
					"Order"			=> 1,
					"Parent"		=> 9,
					"Entities"		=> array(1, 2, 4),
					"Default"		=> array(1, 2, 4),
					"Data"			=> ""
		), array(	"ID"			=> 11,
					"Name"			=> "Delete",
					"Slug"			=> "delete_personaldata",
					"Description"	=> "This is an atomic log action when personal data are deleted.",
					"Order"			=> 2,
					"Parent"		=> 9,
					"Entities"		=> array(1, 2, 4),
					"Default"		=> array(1, 2, 4),
					"Data"			=> ""
		), array(	"ID"			=> 12,
					"Name"			=> "Custom Actions",
					"Slug"			=> "custom_logactions",
					"Description"	=> "This refers to custom log actions entered manually in the log.",
					"Order"			=> 4,
					"Parent"		=> 1,
					"Entities"		=> array(1),
					"Default"		=> array(1),
					"Data"			=> ""
		)
	);
	
	/**
	 * Let Custom Scripts Modify Personal Data Log Actions.
	 *
	 * This filter allows custom scripts to modify personal data log actions
	 * definitions.
	 *
	 * @since 4.5.0
	 *
	 * @param array $types The personal data log actions definitions.
	 */
	return apply_filters("_wfu_personal_data_logactions", $logactions);
}

?>