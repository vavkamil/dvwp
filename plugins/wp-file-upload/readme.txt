=== Wordpress File Upload ===
Contributors: nickboss
Donate link: http://www.iptanus.com/support/wordpress-file-upload
Tags: file, upload, ajax, form, page, post, sidebar, responsive, widget, webcam, ftp
Requires at least: 2.9.2
Tested up to: 5.3.2
Stable tag: "trunk"
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple yet very powerful plugin to allow users to upload files to your website from any page, post or sidebar and manage the uploaded files

== Description ==

With this plugin you or other users can upload files to your site from any page, post or sidebar easily and securely.

Simply put the shortcode [wordpress_file_upload] to the contents of any WordPress page / post or add the plugin's widget in any sidebar and you will be able to upload files to any directory inside wp-contents of your WordPress site.

You can add custom fields to submit additional data together with the uploaded file.

You can use it to capture screenshots or video from your webcam and upload it to the website (for browsers that support this feature).

You can even use it as a simple contact (or any other type of) form to submit data without including a file.

The plugin displays the list of uploaded files in a separate top-level menu in Dashboard and includes a file browser to access and manage the uploaded files (only for admins currently).

Several filters and actions before and after file upload enable extension of its capabilities.

The characteristics of the plugin are:

* It uses the latest HTML5 technology, however it will also work with old browsers and mobile phones.
* It is compliant with the General Data Protection Regulation (GDPR) of the European Union.
* It can be added in posts, pages or sidebars (as a widget).
* It can capture and upload screenshots or video from the device's camera.
* It supports additional form fields (like checkboxes, text fields, email fields, dropdown lists etc).
* It can be used as a simple contact form to submit data (a selection of file can be optional).
* It produces notification messages and e-mails.
* It supports selection of destination folder from a list of subfolders.
* Upload progress can be monitored with a progress bar.
* Upload process can be cancelled at any time.
* It supports redirection to another url after successful upload.
* There can be more than one instances of the shortcode in the same page or post.
* Uploaded files can be added to Media or be attached to the current page.
* Uploaded files can be saved to an FTP location (ftp and sftp protocols supported).
* It is highly customizable with many (more than 50) options.
* It supports filters and actions before and after file upload.
* It contains a visual editor for customizing the plugin easily without any knowledge of shortcodes or programming
* It supports logging of upload events or management of files, which can be viewed by admins through the Dashboard.
* It includes an Uploaded Files top-level menu item in the Dashboard, from where admins can view the uploaded files.
* It includes a file browser in the Dashboard, from where admins can manage the files.
* It supports multilingual characters and localization.

The plugin is translated in the following languages:

* Portuguese, kindly provided by Rui Alao
* German
* French, kindly provided by Thomas Bastide of http://www.omicronn.fr/ and improved by other contributors
* Serbian, kindly provided by Andrijana Nikolic of http://webhostinggeeks.com/
* Dutch, kindly provided by Ruben Heynderycx
* Chinese, kindly provided by Yingjun Li
* Spanish, kindly provided by Marton
* Italian, kindly provided by Enrico Marcolini https://www.marcuz.it/
* Polish
* Swedish, kindly provided by Leif Persson
* Persian, kindly provided by Shahriyar Modami http://chabokgroup.com
* Greek

Please note that old desktop browsers or mobile browsers may not support all of the above functionalities. In order to get full functionality use the latest versions browsers, supporting HTML5, AJAX and CSS3.

For additional features, such as multiple file upload, very large file upload, drag and drop of files, captcha, detailed upload progress bars, list of uploaded files, image gallery and custom css please consider [Wordpress File Upload Professional](http://www.iptanus.com/support/wordpress-file-upload/ "Wordpress File Upload support page").

Please visit the **Other Notes** section for customization options of this plugin.

== Installation ==

1. First install the plugin using Wordpress auto-installer or download the .zip file from wordpress.org and install it from the Plugins section of your Dashboard or copy wordpress_file_upload directory inside wp-contents/plugins directory of your wordpress site.
1. Activate the plugin from Plugins section of your Dashboard.
1. In order to use the plugin simply go to the Dashboard / Settings / Wordpress File Upload and follow the instructions in Plugin Instances or alternatively put the shortcode [wordpress_file_upload] in the contents of any page.
1. Open the page on your browser and you will see the upload form.
1. You can change the upload directory or any other settings easily by pressing the small edit button found at the left-top corner of the upload form. A new window (or tab) with pop up with plugin options. If you do not see the new window, adjust your browser settings to allow pop-up windows.
1. Full documentation about the plugin options can be found at https://wordpress.org/plugins/wp-file-upload/other_notes/ or at http://www.iptanus.com/wordpress-plugins/wordpress-file-upload/ (including the Pro version)

A getting started guide can be found at http://www.iptanus.com/getting-started-with-wordpress-file-upload-plugin/

== Frequently Asked Questions ==

= Will the plugin work in a mobile browser? =

Yes, the plugins will work in most mobile phones (has been tested in iOS, Android and Symbian browsers as well as Opera Mobile) 

= Do I need to have Flash to use then plugin? =

No, you do not need Flash to use the plugin.

= I get a SAFE MODE restriction error when I try to upload a file. Is there an alternative?  =

Your domain has probably turned SAFE MODE ON and you have restrictions uploading and accessing files. Wordpress File Upload includes an alternative way to upload files, using FTP access. Simply add the attribute **accessmethod="ftp"** inside the shortcode, together with FTP access information in **ftpinfo** attribute.

= Can I see the progress of the upload? =

Yes, you can see the progress of the upload. During uploading a progress bar will appear showing progress info, however this functionality functions only in browsers supporting HTML5 upload progress bar.

= Can I upload many files at the same time? =

Yes, but not in the free version. If you want to allow multiple file uploads, please consider the [Professional](http://www.iptanus.com/support/wordpress-file-upload/ "Wordpress File Upload support page") version.

= Where do files go after upload? =

Files by default are uploaded inside wp-content directory of your Wordpress website. To change it use attribute uploadpath.

= Can I see and download the uploaded files? =

Administrators can view all uploaded files together with associated field data from the plugin's Settings in Dashboard. The [Professional](http://www.iptanus.com/support/wordpress-file-upload/ "Wordpress File Upload support page") version of the plugin allows users to view their uploaded files, either from the Dashboard, or from a page or post.

= Are there filters to restrict uploaded content? =

Yes, you can control allowed file size and file extensions by using the appropriate attribute (see Other Notes section).

= Are there any upload file size limitations? =

Yes, there are file size limitations imposed by the web server or the host. If you want to upload very large files, please consider the [Professional](http://www.iptanus.com/support/wordpress-file-upload/ "Wordpress File Upload support page") version of the plugin, which surpasses size limitations.

= Who can upload files? =

By default all users can upload files. You can define which user roles are allowed to upload files. Even guests can be allowed to upload files. If you want to allow only specific users to upload files, then please consider the [Professional](http://www.iptanus.com/support/wordpress-file-upload/ "Wordpress File Upload support page") version of the plugin.

= What security is used for uploading files? =

The plugin is designed not to expose website sensitive information. It has been tested by experts and verified that protects against CSRF and XSS attacks. All parameters passing from server to client side are encoded and sanitized. For higher protection, like use of captcha, please consider the [Professional](http://www.iptanus.com/support/wordpress-file-upload/ "Wordpress File Upload support page") version of the plugin.

= What happens if connection is lost during a file upload? =

In the free version the upload will fail. However in the Pro version the upload will resume and will continue until the file is fully uploaded. This is especially useful when uploading very large files.

= The plugin does not look nice with my theme. What can I do? =

There is an option in plugin's settings in Dashboard to relax the CSS rules, so that buttons and text boxes inherit the theme's styles. If additional styling is required, this can be done using CSS. The Professional version of the plugin allows CSS rules to be embed in the shortcode.

== Screenshots ==

1. A screenshot of the plugin in its most simple form.
2. A screenshot of the plugin showing the progress bar.
3. A screenshot of the plugin showing the successful upload message.
4. A screenshot of the plugin with additional form fields.
5. A screenshot of the plugin with subfolder selection.
6. A screenshot of the plugin in a sidebar.
7. A screenshot of the shortcode composer.
8. A screenshot of the file browser.

== Changelog ==

= 4.12.2 =
* corrected bug where files could not be downloaded in some server environments when dboption user state handler was enabled

= 4.12.1 =
* corrected bug where files could not be downloaded from Dashboard / Uploaded Files page

= 4.12.0 =
* corrected bug where export data file was not deleted after download
* corrected bug in FTP credentials configurator about double backslash (\\) issue
* added cookies user state handler that has been integrated with dboption as 'Cookies (DBOption)' to comply with Wordpress directives not to use session
* 'Cookies (DBOption)' user state handler has been set as the default one
* added advanced option WFU_US_DBOPTION_BASE so that dboption can also work with session
* added advanced option WFU_US_SESSION_LEGACY to use the old session functionality of the plugin, having session_start() in header
* added auto-adjustment of user state handler to 'dboption' during activation (or update) of the plugin
* bug "Error: [] cURL error 28" in Wordpress Site Health disappears when setting user state handler to 'Cookies (DBOption)' or when WFU_US_SESSION_LEGACY advanced option is false
* added the ability to run PHP processes in queue, which is necessary for correctly handling uploads when user state handler is dboption

= 4.11.2 =
* added easier configuration of FTP Credentials (ftpinfo) attribute of the uploader shortcode

= 4.11.1 =
* corrected bug in functions wfu_manage_mainmenu() and wfu_manage_mainmenu_editor() that were echoing and not returning the generated HTML
* added fix for compatibility with Fast Velocity Minify plugin

= 4.11.0 =
* code improved so that shortcode composer can be used by all users who can edit pages (and not only the admins)
* added environment variable 'Show Shortcode Composer to Non-Admins' to control whether non-admin users can edit the shortcodes
* added filtering of get_users() function in order to handle websites with many users more efficiently
* added notification in shortcode composer if user leaves page without saving
* corrected bug where restricted frontend loading of the plugin was not working for websites installed in localhost due to wrong calculation of request uri

= 4.10.3 =
* added the ability to move one or more files to another folder through the File Browser feature in Dashboard area of the plugin
* improved responsiveness of shortcode composer and Main Dashboard page of the plugin
* bug fix in wfu_revert_log_action

= 4.10.2 =
* added wordpress_file_upload_preload_check() function in main plugin file to avoid conflicts of variable names with Wordpress
* updated webcam code to address createObjectURL Javascript error that prevents webcam feature to work in latest versions of browsers

= 4.10.1 =
* code modified so that vendor libraries are loaded only when necessary
* improved process of deleting all plugin options
* added honeypot field to userdata fields; this is a security feature, in replacement of captchas, invisible to users that prevents bots from uploading files
* added attribute 'Consent Denial Rejects Upload' in uploader shortcode Personal Data tab to stop the upload if the consent answer is no, as well as 'Reject Message' attribute to customize the upload rejection message shown to the user
* added attribute 'Do Not Remember Consent Answer' in uploader shortcode Personal Data tab to show the consent question every time (and not only the first time)
* attribute 'Preselected Answer' in uploader shortcode Personal Data tab modified to be compatible with either checkbox or radio Consent Format
* upload result message adjusted to show the correct upload status in case that files were uploaded but were not saved due to Personal Data policy
* code improved for sftp uploads to handle PECL ssh2 bug #73597

= 4.10.0 =
* plugin code improved to support files containing single quote characters (') in their filename
* corrected bug where plugin was deactivated after update

= 4.9.1 =
* added Maintenance action 'Purge All Data' that entirely erases the plugin from the website and deactivates it
* added advanced option 'Hide Invalid Uploaded Files' so that Uploaded Files page in Dashboard can show only valid uploads
* added advanced option 'Restrict Front-End Loading' to load the plugin only on specific pages or posts in order to reduce unnecessary workload on pages not containing the plugin
* code improved for better operation of the plugin when the website works behind a proxy
* added option in Clean Log to erase the files together with plugin data

= 4.9.0 =
* code further improved to reduce "Iptanus Server unreachable..." errors
* checked Weglot Translate compatibility; /wp-admin/admin-ajax.php needs to be added to Exclusion URL list of Weglot configuration so that uploads can work
* several significant additions in the Pro version, including Microsoft OneDrive integration

= 4.8.0 =
* added item in Admin Bar that displays number of new uploads and redirects to Uploaded Files Dashboard page
* code improved in Uploaded Files Dashboard page so that download action directly downloads the file, instead of redirecting to File Browser
* added Advanced option 'WFU_UPLOADEDFILES_COLUMNS' that controls the order and visibility of Uploaded Files Dashboard page columns
* added Advanced option 'WFU_UPLOADEDFILES_ACTIONS' that controls the order and visibility of Uploaded Files Dashboard page file actions
* added several filters in Uploaded Files Dashboard page to make it more customizable
* PHP function redeclaration system significantly improved to support arguments by reference, execution after the original function and redeclaration of variables
* code improved to reduce "Iptanus Server unreachable..." errors (better operation of verify_peer http context property)
* added a link in Iptanus Unreachable Server error message to an Iptanus article describing how to resolve it

= 4.7.0 =
* added Uploaded Files top-level Dashboard menu item, showing all the uploaded files and highlighting the new ones
* added Portuguese translation from Rui Alao
* checked and verified compatibility with Gutenberg
* plugin initialization actions moved to plugins_loaded filter
* fixed bug clearing userdata fields when Select File is pressed
* File Browser and View Log tables modified to become more responsive especially for small screens

= 4.6.2 =
* corrected consent_status warning when updating user profile and Personal Data is off
* user fields code improved for better data autofill behaviour

= 4.6.1 =
* added uploader shortcode attribute 'resetmode' to control whether the upload form will be reset after an upload
* added pagination in File Browser tab in Dashboard area of the plugin

= 4.6.0 =
* corrected slash (/) parse Javascript error near 'fakepath' appearring on some situations
* added nonces in Maintenance Actions to increase security
* improved code in View Log so that no links appear to invalid files
* improved code in View Log so that when the admin opens a file link to view file details, 'go back' button will lead back to the View Log page and not to File Browser
* improved code in 'Clean Log' button in Maintenance Actions in Dashboard area of the plugin, so that the admin can select the period of clean-up

= 4.5.1 =
* code improved in wfu_js_decode_obj function for better compatibility with Safari browser
* code improved to sanitize all shortcode attributes before uploader form or file viewer is rendered
* removed external references to code.jquery.com and cdnjs.cloudflare.com for better compliance with GDPR

= 4.5.0 =
* added basic compliance with GDPR
* added several shortcode attributes to configure personal data consent appearance and behaviour
* added area in User Profile from where users can review and change their consent status
* added Personal Data option in Settings that enables personal data operations
* added Personal Data tab in plugin's area in Dashboard from where administrators can export and erase users' personal data
* corrected bug not accepting subfolder dimensions when subfolder element was active

= 4.4.0 =
* added alternative user state handler using DB Options table in order to overcome problems with session variables appearing on many web servers

= 4.3.4 =
* all Settings sanitized correctly to prevent XSS attacks - credits to ManhNho for mentioning this problem

= 4.3.3 =
* all shortcode attributes sanitized correctly to close a serious security hole - credits to ManhNho for mentioning this problem

= 4.3.2 =
* fixed bug in wfu_before_upload and wfu_after_upload filters that was breaking JS scripts if they contained a closing bracket ']' symbol

= 4.3.1 =
* added placeholder option in available label positions of additional fields; label will be the placeholder attribute of the field

= 4.3.0 =
* fixed bug where ftp credentials did not work when username or password contained (:) or (@) symbols
* RegExp fix for wfu_js_decode_obj function for improved compatibility with caching plugins
* corrected WFU_Original_Template::get_instance() method because it always returned the original class
* View Log page improved so that displayed additional user fields of an uploaded file are not cropped

= 4.2.0 =
* changed logic of file sanitizer; dots in filename are by default converted to dashes, in order to avoid upload failures caused when the plugin detects double extensions
* corrected bug where a Javascript error was generated when askforsubfolders was disabled and showtargetfolder was active
* added css and js minifier in inline code
* plugin modified so that the shortcodes render correctly either Javascript loads early (in header) or late (in footer)
* plugin modified so that Media record is deleted when the associated uploaded file is deleted from plugin's database
* corrected bug where some plugin images were not loaded while Relax CSS option was inactive

= 4.1.0 =
* changed logic of file sanitizer; dots in filename are by default converted to dashes, in order to avoid upload failures caused when the plugin detects double extensions
* added advanced option WFU_SANITIZE_FILENAME_DOTS that determines whether file sanitizer will sanitize dots or not
* timepicker script and style replaced by most recent version
* timepicker script and style files removed from plugin and loaded from cdn
* json2 script removed from plugin and loaded from Wordpress registered script
* JQuery UI style updated to latest 1.12.1 minified version
* added wfu_before_admin_scripts filter before loading admin scripts and styles in order to control incompatibilities
* removed getElementsByClassName-1.0.1.js file from plugin, getElementsByClassName function was replaced by DOM querySelectorAll
* corrected bug showing warning "Notice: Undefined variable: page_hook_suffix..." when a non-admin user opened Dashboard
* corrected fatal error "func_get_args(): Can't be used as a function parameter" appearing in websites with PHP lower than 5.3
* added _wfu_file_upload_hide_output filter that runs when plugin should not be shown (e.g. for users not inluded in uploadroles), in order to output custom HTML
* corrected bug where email fields were always validated, even if validate option was not activated
* corrected bug where number fields did not allow invalid characters, even if typehook option was not activated
* corrected bug where email fields were not allowed to be ampty when validate option was activated
* corrected error T_PAAMAYIM_NEKUDOTAYIM appearing when PHP version is lower than 5.3
* corrected bug with random upload fails caused when params_index corresponds to more than one params

= 4.0.1 =
* translation of the plugin in Persian, kindly provided by Shahriyar Modami http://chabokgroup.com
* corrected bug where notification email was not sending atachments
* corrected bug not cleaning log in Maintenance Actions

= 4.0.0 =
* huge renovation of the plugin, the UI code has been rewritten to render based on templates
* code modified so that it can correctly handle sites where content dir is explicitly defined
* corrected bug in Dashboard file editor so that it can work when the website is installed in a subdirectory
* corrected warnings showing when editing a file that was included in the plugin's database
* added filter in get_posts so that it does not cause problems when there are too many pages/posts
* bug fixes so that forcefilename works better and does not strip spaces in the filename
* code improved to protect from hackers trying to use the plugin as email spammer
* added advanced variable Force Email Notifications so that email can be sent even if no file was uploaded
* corrected bug not showing sanitized filanames correctly in email
* corrected bug so that dates show-up in local time and not in UTC in Log Viewer, File Browser and File Editor
* fixed bug showing "Warning: Missing argument 2 for wpdb::prepare()" when cleaning up the log in Maintenance Actions
* corrected bug where when configuring subfolders with visual editor the subfolder dialog showed unknown error
* corrected bug where the Select File button was not locked during upload in case of classical HTML (no-ajax) uploads
* added cancel button functionality for classic no-ajax uploads
* added support for Secure FTP (sftp) using SSH2 library
* successmessagecolor and waitmessagecolors attributes are hidden as they are no longer used

= 3.11.0 =
* added the ability to submit the upload form without a file, just like a contact form
* added attribute allownofile in uploader shortcode; if enabled then the upload form can be submitted without selection of a file
* added wfu_before_data_submit and wfu_after_data_submit filters which are invoked when the upload form is submitted without a file
* added advanced debug options for more comprehensive and deep troubleshooting
* added internal filters for advanced hooking of ajax handlers
* fixed several security problems
* fixed bug that was generating an error when automatic subfolders were activated and the upload folder did not exist
* corrected bug where single quote, double quote and backslash characters in user fields were not saved correctly (they were escaped)
* fixed bug where any changes made to the user data (e.g. through a filter) were not included in the email message
* added unique_id variable in wfu_before_file_check and wfu_after_file_upload filters
* changed column titles in the tables of plugin instances in Main tab in Dashboard
* fixed bug where if a user field was modified from the file editor, custom columns were changing order

= 3.10.0 =
* an alternative Iptanus server is launched in Google Cloud for resolving the notorious error "file_get_contents(https://services2.iptanus.com/wp-admin/admin-ajax.php): failed to open stream: Connection timed out."
* added option 'Use Alternative Iptanus Server' in Settings to switch to the alternative Iptanus Server
* added advanced option 'Alternative Iptanus Server' that points to an alternative Iptanus Server
* added advanced option 'Alternative Iptanus Version Server' that points to the alternative Iptanus Server URL returning the latest plugin version
* an error is shown in the Main page of the plugin in Dashboard if Iptanus Server is unreachable
* a warning is shown in the Main page of the plugin in Dashboard if an alternative insecure (http) Iptanus Server is used
* alternative fix of error accessing https://services2.iptanus.com for cURL (by disabling CURLOPT_SSL_VERIFYHOST) and for sockets by employing a better parser of socket response
* added Swedish translation, kindly provided by Leif Persson
* improved ftp functionality so that ftp folders can be created recursively

= 3.9.6 =
* added internal filter _wfu_file_upload_output before echoing uploader shortcode html
* added ability to change the order of additional user fields in shortcode visual editor

= 3.9.5 =
* added environment variable 'Upload Progress Mode' that defines how upload progress is calculated
* improved progress bar calculation
* minor bug fixes in AJAX functions mentioned by Hanneke Hoogstrate http://www.blagoworks.nl/

= 3.9.4 =
* added option to enable admin to change the upload user of a file
* code improvements and bug fixes related to file download feature
* code improvements related to clean database function
* added Italian translation

= 3.9.3 =
* added option to allow loading of plugin's styles and scripts on the front-end only for specific posts/pages through wfu_before_frontpage_scripts filter
* fixed bug where when uploading big files with identical filenames and 'maintain both' option, not all would be saved separately
* two advanced variables were added to let the admin change the export function separators

= 3.9.2 =
* added environment variable to enable or disable version check, due to access problems of some users to Iptanus Services server
* added timeout option to wfu_post_request function
* added Spanish translation, kindly provided by Marton

= 3.9.1 =
* temporary fix to address issue with plugin's Main page in Dashboard not loading, by disabling plugin version check
* correct Safari problem with extra spaces in success message coming from force_close_connection
* correct bug where when extension has capital letters it is rejected

= 3.9.0 =
* a big number of extensions have been blacklisted for preventing upload of potentially dangerous files
* the plugin will not allow inclusion, renaming or downloading of files with blacklisted extensions based on the new list
* if no upload extensions are defined or the uploadpattern is too generic, then the plugin will allow only specific extensions based on a white list of extensions; if the administrator wants to include more extensions he/she must declare them explicitely
* the use of the wildcard asterisk symbol has become stricter, asterisk will match all characters except the dot (.), so the default *.* pattern will allow only one extension in the filename (and not more as happened so far).
* added environment variable 'Wildcard Asterisk Mode' for defining the mode of the wildcard asterisk symbol. If it is 'strict' (default) then the asterisk will not match dot (.) symbol. If it is 'loose' then the asterisk will match any characters (including dot).
* slight bug fixes so that wildcard syntax works correctly with square brackets
* added maximum number of uploads per specific interval in order to avoid DDOS attacks
* added environment variables related to Denial-Of-Service attacks in order to configure the behaviour of the DOS attack checker
* bug fix of wfu_before_file_upload filter that was not working correctly with files larger than 1MB

= 3.8.5 =
* added bulk actions feature in File Browser in Dashboard for admins
* added delete and include bulk actions in File Browser
* improvement of column sort functionality of File Browser
* added environment variable 'Use Alternative Randomizer' in order to make string randomizer function work for fast browsers
* uploadedbyuser and userid fields became int to cope with large user ID numbers on some Wordpress environments

= 3.8.4 =
* dublicatespolicy attribute replaced by grammaticaly correct duplicatespolicy, however backward compatibility with the old attribute is maintained

= 3.8.3 =
* fixed bug of subdirectory selector that was not initializing correctly after upload
* fixed slight widget incompatibility with customiser
* fixed bug of drag-n-drop feature that was not working when singlebutton operation was activated

= 3.8.2 =
* fixed bug in wfu_after_file_loaded filter that was not working and was overriden by obsolete wfu_after_file_completed filter
* added option in plugin's Settings in Dashboard to include additional files in plugin's database
* added feature in Dashboard File Browser for admins to include additional files in plugin's database

= 3.8.1 =
* fixed bug with duplicate userdata IDs in HTML when using more than one userdata occurrences

= 3.8.0 =
* added webcam option that enables webcam capture functionality
* added webcammode atribute to define capture mode (screenshots, video or both)
* added audiocapture attribute to define if audio will be captured together with video
* added videowidth, videoheight, videoaspectratio and videoframerate attributes to constrain video dimensions and frame rate
* added camerafacing attribute to define the camera source (front or back)
* added maxrecordtime attribute to define the maximum record time of video
* added uploadmediabutton, videoname and imagename attributes to define custom webcam-related labels
* fixed bug that strips non-latin characters from filename when downloading files

= 3.7.3 =
* improved filename sanitization function
* added Chinese translation by Yingjun Li

= 3.7.2 =
* added option to cancel upload
* setting added so that upload does not fail when site_url and home_url are different
* added attribute requiredlabel in uploader's shortcode that defines the required keyword
* required keyword can now be styled separately from the user field label
* add user fields in Media together with file
* setting added so that userdata fields are shown in Media Library or not
* added Dutch translation by Ruben Heynderycx

= 3.7.1 =
* internal code modifications and slight bug corrections

= 3.7.0 =
* significant code modifications to make the plugin pluggable, invisible to users
* addition of before and after upload filters
* correction of small bug in Shortcode Composer of File Viewer

= 3.6.1 =
* Iptanus Services server for getting version info and other utilities is now secure (https)
* fixed bug with wfu_path_abs2rel function when ABSPATH is just a slash
* additional fixes and new features in Professional version

= 3.6.0 =
* French translation improved
* correction of minor bug at wfu_functions.php
* code improvements in upload algorithm
* wp_check_filetype_and_ext check moved after completion of file
* added wfu_after_file_complete filter that runs right after is fully uploaded
* improved appearance of plugin's area in Dashboard

= 3.5.0 =
* textdomain changed to wp-file-upload to support the translation feature of wordpress.org
* added option in Maintenance Actions of plugin's area in Dashboard to export uploaded file data
* added pagination of non-admin logged user's Uploaded Files Browser
* added pagination of front-end File List Viewer
* added pagination of user permissions table in plugin's Settings
* added pagination of Log Viewer
* corrected bug in View Log that was not working when pressing on the link
* improvements to View Log feature
* improvements to file download function to avoid corruption of downloaded file due to set_time_limit function that may generate warnings
* added wfu_before_frontpage_scripts filter that executes right before frontpage scripts and styles are loaded
* added functionality to avoid incompatibilities with NextGen Gallery plugin

= 3.4.1 =
* plugin's security improved to reject files that contain .php.js or similar extensions

= 3.4.0 =
* added fitmode attribute to make the plugin responsive
* added widget "Wordpress File Upload Form", so that the uploader can be installed in a sidebar
* changes to Shortcode Composer so that it can edit plugin instances existing in sidebars as widgets
* changes to Uploader Instances in plugin's area in Dashboard to show also instances existing inside sidebars
* added the ability to define dimensions (width and height) for the whole plugin
* dimensioning of plugin's elements improved when fitmode is set to "responsive"
* filter and non-object warnings of front-end file browser, appearing when DEBUG mode is ON, removed
* bug fixed to front-end file browser to hide Shortcode Composer button for non-admin users
* logic changed to front-end file browser to allow users to download files uploaded by other users
* code changed to front-end file browser to show a message when a user attempts to delete a file that was not uploaded by him/her

= 3.3.1 =
* bug corrected that was breaking plugin operation for php versions prior to 5.3
* added a "Maintenance Actions" section in plugin's Dashboard page
* added option in plugin's "Maintenance Actions" to completely clean the database log

= 3.3.0 =
* userdatalabel attribute changed to allow many field types
* added the following user data field types: simple text, multiline text, number, email, confirmation email, password, confirmation password, checkbox, radiobutton, date, time, datetime, listbox and dropdown list
* added several options to configure the new user data fields: label text (to define the label of the field), label position (to define the position of the label in relation to the field), required option (to define if the field needs to be filled before file upload), do-not-autocomplete option (to prevent the browsers for completing the field automatically), validate option (to perform validity checks of the field before file upload depending on its type), default text (to define a default value), group id (to group fields together such as multiple radio buttons), format text (to define field formatting depending on the field type), typehook option (to enable field validation during typing inside the field), hint position (to define the position of the message that will be shown to prompt the user that a required field is empty or is not validated) as well as an option to define additional data depending on the field type (e.g. define list of items of a listbox or dropdown list)
* Shortcode Composer changed to support the new user data fields and options
* placement attribute can accept more than one instances of userdata
* fixed bug not showing date selector of date fields in Shortcode Composer when working with Firefox or IE browsers
* in some cases required userdata input field will turn red if not populated
* shortcode_exists and wp_slash fixes for working before 3.6 Wordpress version
* minor bug fixes

= 3.2.1 =
* removed 'form-field' class from admin table tr elements
* corrected bug that was causing problems in uploadrole and uploaduser attributes when a username or role contained uppercase letters
* uploadrole and uploaduser attributes logic modified; guests are allowed only if 'guests' word is included in the attribute
* modifications to the download functionality script to be more robust
* corrected bug that was not showing options below a line item of admin tables in Internet Explorer
* several feature additions and bug fixes in Professional version

= 3.2.0 =
* added option in plugin's settings to relax CSS rules so that plugin inherits theme styling
* modifications in html and css of editable subfolders feature to look better
* modifications in html and css of prompt message when a required userdata field is empty
* PLUGINDIR was replaced by WP_PLUGIN_DIR so that the plugin can work for websites where the contents dir is other than wp-content
* fixed bug that was not allowing Shortcode Composer to launch when the shortcode was too big
* fixed bug that was causing front-end file list not to work properly when no instance of the plugin existed in the same page / post

= 3.1.2 =
* important bug detected and fixed that was stripping slashes from post or page content when updating the shortcode using the shortcode composer

= 3.1.1 =
* the previous version broke the easy creation of shortcodes through the plugin's settings in Dashboard and it has been corrected, together with some improvements

= 3.1.0 =
* an important feature (front-end file browser) has been added in professional version 3.1.0
* added port number support for uploads using ftp mode
* corrected bug that was not showing correctly in file browser files that were uploaded using ftp mode
* eliminated confirmbox warning showing in page when website's DEBUG mode is ON
* eliminated warning: "Invalid argument supplied for foreach() in ...plugins/wordpress-file-upload-pro/lib/wfu_admin.php on line 384"
* eliminated warning: "Notice: Undefined index: postmethod in /var/www/wordpress/wp-content/plugins/wordpress-file-upload-pro/lib/wfu_functions.php on line 1348"
* eliminated warnings in plugin's settings in Dashboard

= 3.0.0 =
* major version number has advanced because an important feature has been added in Pro version (logged users can browse their uploaded files through their Dashboard)
* several code modifications in file browser to make the plugin more secure against hacking, some functionalities in file browser have slightly changed
* new file browser cannot edit files that were not uploaded with the plugin and it cannot edit or create folders
* upload path cannot be outside the wordpress installation root
* files with extension php, js, pht, php3, php4, php5, phtml, htm, html and htaccess are forbidden for security reasons

= 2.7.6 =
* added functionality in Dashboard to add the plugin to a page automatically
* fixed bug that was not showing the Shortcode Composer because the plugin could not find the plugin instance when the shortcode was nested in other shortcodes

= 2.7.5 =
* added German and Greek translation

= 2.7.4 =
* added Serbian translation thanks to Andrijana Nikolic from http://webhostinggeeks.com/
* bug fix with %blogid%, %pageid% and %pagetitle% that where not implemented in notification emails
* in single button operation selected files are removed in case that a subfolder has not been previously selected or a required user field has not been populated
* bug fixed in single file operation that allowed selection of multiple files through drag-and-drop
* bug fixed with files over 1MB that got corrupted when maintaining files with same filename
* dummy (test) Shortcode Composer button removed from the plugin's Settings as it is no longer useful
* added support for empty (zero size) files
* many code optimizations and security enhancements
* fixed javascript errors in IE8 that were breaking upload operation
* code improvements to avoid display of session warnings
* added %username% in redirect link
* added option in plugin's Settings in Dashboard to select alternative POST Upload method, in order to resolve errors like "http:// wrapper is disabled in the server configuration by allow_url_fopen" or "Call to undefined function curl_init()"
* added filter action wfu_after_upload, where the admin can define additional javascript code to be executed on user's browser after each file is finished

= 2.7.3 =
* important bug fix in Pro version
* added wfu_before_email_notification filter
* corrected bug not showing correctly special characters (double quotes and braces) in email notifications

= 2.7.2 =
* important bug fix in Pro version, very slight changes in free version

= 2.7.1 =
* fixed bug with faulty plugin instances appearing when Woocommerce plugin is also installed
* Upload of javascript (.js) files is not allowed for avoiding security issues
* fixed bug with medialink and postlink attributes that were not working correctly
* when medialink or postlink is activated, the files will be uploaded to the upload folder of WP website
* when medialink or postlink is activated, subfolders will be deactivated
* added option in subfolders to enable the list to populate automatically
* added option in subfolders the user to be able to type the subfolder
* wfu_before_file_check filter can modify the target path (not only the file name)

= 2.7.0 =
* corrected bug when deleting plugin instance from the Dashboard
* corrected bug not finding "loading_icon.gif"

= 2.6.0 =
* full redesign of the upload algorithm to become more robust
* added improved server-side handling of large files
* plugin shortcodes can be edited using the Shortcode Composer
* added visual editor button on the plugin to enable administrators to change the plugin settings easily
* corrected bug causing sometimes database overloads
* slight improvements of subfolder option
* improvements to avoid code breaking in ajax calls when there are php warnings or echo from Wordpress environment or other plugins
* improvements and bug fixes in uploader when classic (no AJAX) upload is selected
* eliminated php warnings in shortcode composer
* corrected bug that was not correctly downloading files from the plugin's File Browser
* added better security when downloading files from the plugin's File Browser
* fixed bug not correctly showing the user that uploaded a file in the plugin's File Browser
* use of curl to perform server http requests was replaced by native php because some web servers do not have CURL installed
* corrected bug in shortcode composer where userdata fields were not shown in variables drop down
* added feature that prevents page closing if an upload is on progress
* added forcefilename attribute to avoid filename sanitization
* added ftppassivemode attribute for enabling FTP passive mode when FTP method is used for uploading
* added ftpfilepermissions attribute for defining the permissions of the uploaded file, when using FTP method
* javascript and css files are minified for faster loading

= 2.5.5 =
* fixed serious bug not uploading files when captcha is enabled
* fixed bug not redirecting files when email notification is enabled

= 2.5.4 =
* mitigated issue with "Session failed" errors appearing randomly in websites
* fixed bug not applying %filename% variable inside redirect link
* fixed bug not applying new filename, which has been modified with wfu_before_file_upload filter, in email notifications and redirects
* fixed bug where when 2 big files were uploaded at the same time and one failed due to failed chunk, then the progress bar would not go to 100% and the file would not be shown as cancelled

= 2.5.3 =
* fixed bug not allowing redirection to work
* fixed bug that was including failed files in email notifications on certain occasions
* default value for uploadrole changed to "all"

= 2.5.2 =
* fixed important bug in free version not correctly showing message after failed upload

= 2.5.1 =
* fixed important bug in free version giving the same name to all uploaded files
* fixed bug in free version not clearing completely the plugin cache from previous file upload

= 2.5.0 =
* major redesign of upload algorithm to address upload issues with Safari for Mac and Firefox
* files are first checked by server before actually uploaded, in order to avoid uploading of large files that are invalid
* modifications to progress bar code to make progress bar smoother
* restrict upload of .php files for security reasons
* fixed bug not showing correctly userdata fields inside email notifications when using ampersand or other special characters in userdata fields

= 2.4.6 =
* variables %blogid%, %pageid% and %pagetitle% added in email notifications and subject and %dq% in subject
* corrected bug that was breaking Shortcode Composer when using more than ten attributes
* corrected bug that was rejecting file uploads when uploadpattern attribute contained blank spaces
* several code corrections in order to eliminate PHP warning messages when DEBUG mode is on
* several code corrections in order to eliminate warning messages in Javascript

= 2.4.5 =
* correction of bug when using userfields inside notifyrecipients

= 2.4.4 =
* intermediate update to make the plugin more immune to hackers

= 2.4.3 =
* correction of bug to allow uploadpath to receive userdata as parameter

= 2.4.2 =
* intermediate update to address some vulnerability issues

= 2.4.1 =
* added filters and actions before and after each file upload - check below Filters/Actions section for instructions how to use them
* added storage of file info, including user data, in database
* added logging of file actions in database - admins can view the log from the Dashboard
* admins can automatically update the database to reflect the current status of files from the Dashboard
* file browser improvements so that more information about each file (including any user data) are shown
* file browser improvements so that files can be downloaded
* filelist improvements to display correctly long filenames (Pro version)
* filelist improvements to distinguish successful uploads from failed uploads (Pro version)
* improvements of chunked uploads so that files that are not allowed to be uploaded are cancelled faster (Pro version)
* corrected wrong check of file size limit for chunked files (Pro version)
* added postlink attribute so that uploaded files are linked to the current page (or post) as attachments
* added subfolderlabel attribute to define the label of the subfolder selection feature
* several improvements to subfolder selection feature
* default value added to subfolder selection feature
* definition of the subfoldertree attribute in the Shortcode Composer is now done visually
* %userid% variable added inside uploadpath attribute
* userdata variables added inside uploadpath and notifyrecipients attributes
* uploadfolder_label added to dimension items
* user fields feature improvements
* user fields label and input box dimensions are customizable
* captcha prompt label dimensions are customizable (Pro version)
* added gallery attribute to allow the uploaded files to be shown as image gallery below the plugin (Pro version)
* added galleryoptions attribute to define options of the image gallery (Pro version)
* added css attribute and a delicate css editor inside Shortcode Composer to allow better styling of the plugin using custom css (Pro version)
* email feature improved in conjunction with redirection
* improved interoperability with WP-Filebase plugin
* improved functionality of free text attributes (like notifymessage or css) by allowing double-quotes and brackets inside the text (using special variables), that were previously breaking the plugin

= 2.3.1 =
* added option to restore default value for each attribute in Shortcode Composer
* added support for multilingual characters
* correction of bug in Shortcode Composer that was not allowing attributes with singular and plural form to be saved
* correction of bug that was not changing errormessage attribute in some cases

= 2.2.3 =
* correction of bug that was freezing the Shortcode Composer in some cases
* correction of bug with successmessage attribute

= 2.2.2 =
* serious bug fixed that was breaking operation of Shortcode Composer and File Browser when the Wordpress website is in a subdirectory

= 2.2.1 =
* added file browser in Dashboard for admins
* added attribute medialink to allow uploaded files to be shown in Media
* serious bug fixed that was breaking the plugin because of preg_replace_callback function
* corrected error in first attempt to upload file when captcha is enabled

= 2.1.3 =
* variables %pagetitle% and %pageid% added in uploadpath.
* bug fixes when working with IE8.
* Shortcode Composer saves selected options
* Easier handling of userdata variables in Shortcode Composer
* correction of bug that allowed debugdata to be shown in non-admin users
* reset.css removed from plugin as it was causing breaks in theme's css
* correction of bug with WPFilebase Manager plugin

= 2.1.2 =
* Several bug fixes and code reconstruction.
* Code modifications so that the plugin can operate even when DEBUG mode is ON.
* New attribute debugmode added to allow better debugging of the plugin when there are errors.

= 2.1.1 =
* Bug fixes with broken images when Wordpress website is in a subdirectory.
* Replacement of glob function because is not allowed by some servers.

= 2.0.2 =
* Bug fixes in Dashboard Settings Shortcode Composer.
* Correction of important bug that was breaking page in some cases.
* Minor improvements of user data fields and notification email attributes.

= 2.0.1 =
This is the initial release of Wordpress File Upload. Since this plugin is the successor of Inline Upload, the whole changelog since the creation of the later is included.

* Name of the plugin changed to Wordpress File Upload.
* Plugin has been completely restructured to allow additional features.
* A new more advanced message box has been included showing information in a more structured way.
* Error detection and reporting has been improved.
* An administration page has been created in the Dashboard Settings, containing a Shortcode Composer.
* Some more options related to configuration of message showing upload results have been added.
* Several bug fixes.

= 1.7.14 =
* Userdata attribute changed to allow the creation of more fields and required ones.
* Spanish translation added thanks to Maria Ramos of WebHostingHub.

= 1.7.13 =
* Added notifyheaders attribute, in order to allow better control of notification email sent (e.g. allow to send HTML email).

= 1.7.12 =
* Added userdata attribute, in order to allow users to send additional text data along with the uploaded file.

= 1.7.11 =
* Added single button operation (file will be automatically uploaded when selected without pressing Upload Button).

= 1.7.10 =
* Fixed bug with functionality of attribute filebaselink for new versions of WP-Filebase plugin.

= 1.7.9 =
* Fixed problem with functionality of attribute filebaselink for new versions of WP-Filebase plugin.

= 1.7.8 =
* More than one roles can now be defined in attribute uploadrole, separated by comma (,).

= 1.7.7 =
* Variable %filename% now works also in redirectlink.

= 1.7.6 =
* Changes in ftp functionality, added useftpdomain attribute so that it can work with external ftp domains as well.
* Improvement of classic upload (used in IE or when setting forceclassic to true) messaging functionality.
* Minor bug fixes.

= 1.7.5 =
* Source modified so that it can work with Wordpress sites that are not installed in root.
* Added variable %blogid% for use with multi-site installations.
* Bug fixes related to showing of messages.

= 1.7.4 =
* Replacement of json2.js with another version.

= 1.7.3 =
* CSS style changes to resolve conflicts with various theme CSS styles.

= 1.7.2 =
* Added variable %useremail% used in notifyrecipients, notifysubject and notifymessage attributes.

= 1.7.1 =
* Added capability to upload files outside wp-content folder.
* Improved error reporting.

= 1.7 =
* Complete restructuring of plugin HTML code, in order to make it more configurable and customizable.
* Appearance of messages has been improved.
* Added option to put the plugin in testmode.
* Added option to configure the colors of success and fail messages.
* Added option to modify the dimensions of the individual objects of the plugin.
* Added option to change the placement of the individual objects of the plugin.
* Improved error reporting.
* Added localization for error messages.
* Minor bug fixes.

= 1.6.3 =
* Bug fixes to correct incompatibilities of the new ajax functionality when uploadrole is set to "all".

= 1.6.2 =
* Bug fixes to correct incompatibilities of the new ajax functionality with redirectlink, filebaselink and adminmessages.

= 1.6.1 =
* Correction of serious bug that prevented the normal operation of the plugin when the browser of the user supports HTML5 functionality.
* Tags added to the plugin Wordpress page.

= 1.6 =
* Major lifting of the whole code.
* Added ajax functionality so that file is uploaded without page reload (works in browsers supporting HTML5).
* Added upload progress bar (works in browsers supporting HTML5).
* Added option to allow user to select if wants to use the old form upload functionality.
* File will not be saved again if user presses the Refresh button (or F5) of the page.
* Translation strings updated.
* Bug fixes for problems when there are more than one instances of the plugin in a single page.

= 1.5 =
* Added option to notify user about upload directory.
* Added option to allow user to select a subfolder to upload the file.

= 1.4.1 =
* css corrections for bug fixes.

= 1.4 =
* Added option to attach uploaded file to notification email.
* Added option to customize message on successful upload (variables %filename% and %filepath% can be used).
* Added option to customize color of message on successful upload.
* "C:\fakepath\" problem resolved.
* warning message about function create_directory() resolved.
* css enhancements for compatibility with more themes.

= 1.3 =
* Additional variables added (%filename% and %filepath%).
* All variables can be used inside message subject and message text.
* Added option to determine how to treat duplicates (overwrite existing file, leave existing file, leave both).
* Added option to determine how to rename the uploaded file, when another file already exists in the target directory.
* Added option to create directories and upload files using ftp access, in order to overcome file owner and SAFE MODE restrictions.
* Added the capability to redirect to another web page when a file is uploaded successfully.
* Added the option to show to administrators additional messages about upload errors.
* Bug fixes related to interoperability with WP_Filebase

= 1.2 =
* Added notification by email when a file is uploaded.
* Added the ability to upload to a variable folder, based on the name of the user currently logged in.

= 1.1 =
Added the option to allow anyone to upload files, by setting the attribute uploadrole to "all".

= 1.0 =
Initial version.

== Upgrade Notice ==

= 4.12.2 =
Minor update to fix some bugs.

= 4.12.1 =
Minor update to fix some bugs.

= 4.12.0 =
Significant update to introduce some improvements, new features and fix some bugs.

= 4.11.2 =
Minor update to introduce some improvements.

= 4.11.1 =
Minor update to introduce some improvements and fix some bugs.

= 4.11.0 =
Significant update to introduce some improvements and fix some bugs.

= 4.10.3 =
Minor update to introduce some improvements and fix some bugs.

= 4.10.2 =
Minor update to introduce some improvements and fix some bugs.

= 4.10.1 =
Regular update to introduce some new features and improvements.

= 4.10.0 =
Regular update to introduce some new features and improvements.

= 4.9.1 =
Regular update to introduce some new features and improvements and fix some bugs.

= 4.9.0 =
Significant update to introduce some new features and improvements and fix some bugs.

= 4.8.0 =
Significant update to introduce some new features and improvements and fix some bugs.

= 4.7.0 =
Significant update to introduce some new features and improvements and fix some bugs.

= 4.6.2 =
Minor update to fix some bugs and introduce some code improvements.

= 4.6.1 =
Regular update to introduce some new features.

= 4.6.0 =
Significant update to introduce some new features.

= 4.5.1 =
Minor update to introduce some new features.

= 4.5.0 =
Significant update to introduce new features and fix some bugs.

= 4.4.0 =
Significant update that enables wider web server compatibility.

= 4.3.4 =
Minor update to fix a serious security hole.

= 4.3.3 =
Minor update to fix a serious security hole.

= 4.3.2 =
Minor update to fix some bugs.

= 4.3.1 =
Minor update to introduce a new feature.

= 4.3.0 =
Significant update to introduce some new features and fix some bugs.

= 4.2.0 =
Significant update to introduce some new features and fix some bugs.

= 4.1.0 =
Significant update to fix several bugs and introduce some new features.

= 4.0.1 =
Minor update to fix some bugs.

= 4.0.0 =
Major update to introduce new features, code improvements and fix some bugs.

= 3.11.0 =
Update to introduce some new features and fix some bugs.

= 3.10.0 =
Update to introduce some new features and fix some bugs.

= 3.9.6 =
Update to introduce some new features.

= 3.9.5 =
Update to introduce some new features and fix some minor bugs.

= 3.9.4 =
Update to introduce some new features and fix some bugs.

= 3.9.3 =
Update to introduce some new features and fix some bugs.

= 3.9.2 =
Significant update to improve a temporary fix to an important problem and fix some minor bugs.

= 3.9.1 =
Significant update to introduce a temporary fix to an important problem.

= 3.9.0 =
Significant update to increase the security of the plugin and address potential threats.

= 3.8.5 =
Upgrade to introduce some new features and code improvements.

= 3.8.4 =
Upgrade to fix some bugs.

= 3.8.3 =
Minor upgrade to fix some bugs.

= 3.8.2 =
Minor upgrade to fix some bugs and introduce some new features.

= 3.8.1 =
Minor upgrade to fix some bugs.

= 3.8.0 =
Significant upgrade to introduce some new features and fix some bugs.

= 3.7.3 =
Upgrade to introduce some improvements and new languages.

= 3.7.2 =
Upgrade to introduce some new features and fix some minor bugs.

= 3.7.1 =
Upgrade to fix some minor bugs.

= 3.7.0 =
Upgrade to introduce some new features and fix some minor bugs.

= 3.6.1 =
Upgrade to introduce some new features and fix some minor bugs.

= 3.6.0 =
Upgrade to introduce some new features and fix some minor bugs.

= 3.5.0 =
Important upgrade to introduce some new features and fix some bugs.

= 3.4.1 =
Important upgrade to address a security hole.

= 3.4.0 =
Important upgrade to introduce some new features and fix some bugs.

= 3.3.1 =
Important upgrade to correct a bug of the previous version and introduce a new feature.

= 3.3.0 =
Major upgrade to add some new featuresand fix some minor bugs.

= 3.2.1 =
Upgrade to fix some bugs and add some features.

= 3.2.0 =
Upgrade to fix some bugs and add some features.

= 3.1.2 =
Upgrade to fix an important bug.

= 3.1.1 =
Upgrade to fix a minor bug.

= 3.1.0 =
Upgrade to fix some minor bugs.

= 3.0.0 =
Upgrade to increase protection against hacking.

= 2.7.6 =
Upgrade to add some new features and address some bugs.

= 2.7.5 =
Upgrade to add some new features.

= 2.7.4 =
Upgrade to add some new features and address some bugs.

= 2.7.3 =
Upgrade to add some new features and address some bugs.

= 2.7.2 =
Upgrade to address some bugs.

= 2.7.1 =
Upgrade to add some new features and address some bugs.

= 2.7.0 =
Upgrade to address some minor bugs.

= 2.6.0 =
Important upgrade to add new features and address some bugs.

= 2.5.5 =
Important upgrade to address some bugs.

= 2.5.4 =
Important upgrade to address some bugs.

= 2.5.3 =
Important upgrade to address some bugs.

= 2.5.2 =
Important upgrade to address some bugs.

= 2.5.1 =
Important upgrade to address some bugs.

= 2.5.0 =
Important upgrade to address some bugs.

= 2.4.6 =
Important upgrade to address some bugs.

= 2.4.5 =
Minor upgrade to address some bugs.

= 2.4.4 =
Important upgrade to address some vulnerability issues.

= 2.4.3 =
Upgrade to address some functionality issues.

= 2.4.2 =
Important upgrade to address some vulnerability issues.

= 2.4.1 =
Upgrade to add many features and address some minor bugs.

= 2.3.1 =
Upgrade to add some features and address some minor bugs.

= 2.2.3 =
Upgrade to address some minor bugs.

= 2.2.2 =
Important upgrade to address some serious bugs.

= 2.2.1 =
Important upgrade to address some serious bugs and include some new features.

= 2.1.3 =
Important upgrade to address some serious bugs.

= 2.1.2 =
Important upgrade to address some bugs.

= 2.1.1 =
Important upgrade to address some serious bugs.

= 2.0.2 =
Important upgrade to address some serious bugs.

= 2.0.1 =
Optional upgrade to add new features.

= 1.7.14 =
Optional upgrade to add new features.

= 1.7.13 =
Optional upgrade to add new features.

= 1.7.12 =
Optional upgrade to add new features.

= 1.7.11 =
Optional upgrade to add new features.

= 1.7.10 =
Important upgrade to correct bug with filebaselink attribute functionality.

= 1.7.9 =
Important upgrade to resolve issue with filebaselink attribute functionality.

= 1.7.8 =
Optional upgrade to add new features.

= 1.7.7 =
Optional upgrade to add new features.

= 1.7.6 =
Optional upgrade to add new features and make minor bug fixes.

= 1.7.5 =
Important upgrade to resolve issues with Wordpress sites not installed in root.

= 1.7.4 =
Important upgrade to resolve issues with json2 functionality.

= 1.7.3 =
Important upgrade to resolve issues with style incompatibilities.

= 1.7.2 =
Optional upgrade to add new features, related to variables.

= 1.7.1 =
Optional upgrade to add new features, related to uploadpath and error reporting.

= 1.7 =
Optional upgrade to add new features, related to appearance of the plugin and error reporting.

= 1.6.3 =
Important upgrade to correct bugs that prevented normal operation of the plugins in some cases.

= 1.6.2 =
Important upgrade to correct bugs that prevented normal operation of the plugins in some cases.

= 1.6.1 =
Important upgrade to correct bug that prevented normal operation of the plugins in some cases.

= 1.6 =
Optional upgrade to add new features, related to ajax functionality and minor bug fixes.

= 1.5 =
Optional upgrade to add new features, related to subfolders.

= 1.4.1 =
Important upgrade to correct a css problem with Firefox.

= 1.4 =
Important upgrade that introduces some bug fixes and some new capabilities.

= 1.3 =
Important upgrade that introduces some bug fixes and a lot of new capabilities.

= 1.2 =
Optional upgrade in order to set additional capabilities.

= 1.1 =
Optional upgrade in order to set additional capabilities.

= 1.0 =
Initial version.

== Plugin Customization Options ==

Please visit the [support page](http://www.iptanus.com/support/wordpress-file-upload/ "Wordpress File Upload support page") of the plugin for detailed description of customization options.

== Requirements ==

The plugin requires to have Javascript enabled in your browser. For Internet Explorer you also need to have Active-X enabled.
Please note that old desktop browsers or mobile browsers may not support all of the plugin's features. In order to get full functionality use the latest versions of browsers, supporting HTML5, AJAX and CSS3.