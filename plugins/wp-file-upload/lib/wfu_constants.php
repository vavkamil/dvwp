<?php

/**
 * Constants and Strings of Plugin
 *
 * This file initializes all constants and translatable strings of the plugin.
 *
 * @link /lib/wfu_constants.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 2.1.2
 */

$siteurl = site_url();

/**
 * Translatable Strings Initialization.
 *
 * This function initializes all translatable strings of the plugin.
 *
 * @since 4.7.0
 */
function wfu_initialize_i18n_strings() {
	if ( defined("WFU_I18_LOADED") ) return;
	DEFINE("WFU_I18_LOADED", 1);
	//plugin default values
	DEFINE("WFU_UPLOADTITLE", __('Upload files', 'wp-file-upload'));
	DEFINE("WFU_SELECTBUTTON", __('Select File', 'wp-file-upload'));
	DEFINE("WFU_UPLOADBUTTON", __('Upload File', 'wp-file-upload'));
	DEFINE("WFU_NOTIFYSUBJECT", __('File Upload Notification', 'wp-file-upload'));
	DEFINE("WFU_NOTIFYMESSAGE", __("Dear Recipient,%n%%n%   This is an automatic delivery message to notify you that a new file has been uploaded.%n%%n%Best Regards", 'wp-file-upload'));
	DEFINE("WFU_SUCCESSMESSAGE", __('File %filename% uploaded successfully', 'wp-file-upload'));
	DEFINE("WFU_WARNINGMESSAGE", __('File %filename% uploaded successfully but with warnings', 'wp-file-upload'));  
	DEFINE("WFU_ERRORMESSAGE", __('File %filename% not uploaded', 'wp-file-upload'));
	DEFINE("WFU_WAITMESSAGE", __('File %filename% is being uploaded', 'wp-file-upload'));  
	DEFINE("WFU_USERDATALABEL", __('Your message', 'wp-file-upload')."|t:text|s:left|r:0|a:0|p:inline|d:");
	DEFINE("WFU_CAPTCHAPROMPT", __('Please fill in the above words: ', 'wp-file-upload'));
	DEFINE("WFU_UPLOADMEDIABUTTON", __('Upload Media', 'wp-file-upload'));
	DEFINE("WFU_VIDEONAME", __('videostream', 'wp-file-upload'));
	DEFINE("WFU_IMAGENAME", __('screenshot', 'wp-file-upload'));
	DEFINE("WFU_CONSENTQUESTION", __('By activating this option I agree to let the website keep my personal data', 'wp-file-upload'));
	DEFINE("WFU_CONSENTREJECTMESSAGE", __('You have denied to let the website keep your personal data. Upload cannot continue!', 'wp-file-upload'));
	//browser default values
	DEFINE("WFU_FILETITLE", __('File', 'wp-file-upload'));
	DEFINE("WFU_DATETITLE", __('Date', 'wp-file-upload'));
	DEFINE("WFU_SIZETITLE", __('Size', 'wp-file-upload'));
	DEFINE("WFU_USERTITLE", __('User', 'wp-file-upload'));
	DEFINE("WFU_POSTTITLE", __('Page', 'wp-file-upload'));
	DEFINE("WFU_FIELDSTITLE", __('User Fields', 'wp-file-upload'));
	DEFINE("WFU_DOWNLOADLABEL", __('Download', 'wp-file-upload'));
	DEFINE("WFU_DOWNLOADTITLE", __('Download this file', 'wp-file-upload'));
	DEFINE("WFU_DELETELABEL", __('Delete', 'wp-file-upload'));
	DEFINE("WFU_DELETETITLE", __('Delete this file', 'wp-file-upload'));
	DEFINE("WFU_SORTTITLE", __('Sort list based on this column', 'wp-file-upload'));
	DEFINE("WFU_GUESTTITLE", __('guest', 'wp-file-upload'));
	DEFINE("WFU_UNKNOWNTITLE", __('unknown', 'wp-file-upload'));
	//error messages
	DEFINE("WFU_ERROR_ADMIN_FTPDIR_RESOLVE", __("Error. Could not resolve ftp target filedir. Check the domain in 'ftpinfo' attribute.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FTPINFO_INVALID", __("Error. Invalid ftp information. Check 'ftpinfo' attribute.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FTPINFO_EXTRACT", __("Error. Could not extract ftp information from 'ftpinfo' attribute. Check its syntax.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FTPFILE_RESOLVE", __("Error. Could not resolve ftp target filename. Check the domain in 'ftpinfo' attribute.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FTPSOURCE_FAIL", __("Error. Could not open source file for ftp upload. Check if file is accessible.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FTPTRANSFER_FAIL", __("Error. Could not send data to ftp target file.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FTPHOST_FAIL", __("Error. Could not connect to ftp host. Check the domain in 'ftpinfo' attribute.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FTPLOGIN_FAIL", __("Error. Could not authenticate to ftp host. Check username and password in 'ftpinfo' attribute.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_SFTPINIT_FAIL", __("Error. Could not initialize sftp subsystem. Please check if the server supports sftp.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_SFTP_UNSUPPORTED", __("Error. The web server does not support sftp.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FILE_PHP_SIZE", __("Error. The upload size limit of PHP directive upload_max_filesize is preventing the upload of big files.\nPHP directive upload_max_filesize limit is: ".ini_get("upload_max_filesize").".\nTo increase the limit change the value of the directive from php.ini.\nIf you don't have access to php.ini, then try adding the following line to your .htaccess file:\n\nphp_value upload_max_filesize 10M\n\n(adjust the size according to your needs)\n\nThe file .htaccess is found in your website root directory (where index.php is found).\nIf your don't have this file, then create it.\nIf this does not work either, then contact your domain provider.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FILE_PHP_TIME", __("The upload time limit of PHP directive max_input_time is preventing the upload of big files.\nPHP directive max_input_time limit is: ".ini_get("max_input_time")." seconds.\nTo increase the limit change the value of the directive from php.ini.\nIf you don't have access to php.ini, then add the following line to your .htaccess file:\n\nphp_value max_input_time 500\n\n(adjust the time according to your needs)\n\nThe file .htaccess is found in your website root directory (where index.php is found).\nIf your don't have this file, then create it.\nIf this does not work either, then contact your domain provider.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_DIR_PERMISSION", __("Error. Permission denied to write to target folder.\nCheck and correct read/write permissions of target folder.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_FILE_WRONGEXT", __("Error. This file was rejected because its extension is not correct. Its proper filename is: ", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_DOS_ATTACK", __("Too many files are uploaded in a short period of time. This may be a Denial-Of-Service attack, so file was rejected. Please check the upload log for suspicious behaviour.", "wp-file-upload"));
	DEFINE("WFU_ERROR_DOS_ATTACK", __("File not uploaded in order to prevent overflow of the website. Please contact administrator.", "wp-file-upload"));
	DEFINE("WFU_ERROR_DIR_EXIST", __("Target folder doesn't exist.", "wp-file-upload"));
	DEFINE("WFU_ERROR_DIR_NOTEMP", __("Upload failed! Missing a temporary folder.", "wp-file-upload"));
	DEFINE("WFU_ERROR_DIR_PERMISSION", __("Upload failed! Permission denied to write to target folder.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_ALLOW", __("File not allowed.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_REJECT", __("File is suspicious and was rejected.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_PLUGIN_SIZE", __("The uploaded file exceeds the file size limit.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_PLUGIN_2GBSIZE", __("The uploaded file exceeds 2GB and is not supported by this server.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_PHP_SIZE", __("Upload failed! The uploaded file exceeds the file size limit of the server. Please contact the administrator.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_PHP_TIME", __("Upload failed! The duration of the upload exceeded the time limit of the server. Please contact the administrator.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_HTML_SIZE", __("Upload failed! The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_PARTIAL", __("Upload failed! The uploaded file was only partially uploaded.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_NOTHING", __("Upload failed! No file was uploaded.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_WRITE", __("Upload failed! Failed to write file to disk.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_MOVE", __("Upload failed! Error occured while moving temporary file. Please contact administrator.", "wp-file-upload"));
	DEFINE("WFU_ERROR_FILE_CANCELLED", __("Upload cancelled!", "wp-file-upload"));
	DEFINE("WFU_ERROR_UPLOAD_STOPPED", __("Upload failed! A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.", "wp-file-upload"));
	DEFINE("WFU_ERROR_UPLOAD_FAILED_WHILE", __("Upload failed! Error occured while attemting to upload the file.", "wp-file-upload"));
	DEFINE("WFU_ERROR_UPLOAD_FAILED", __("Upload failed!", "wp-file-upload"));
	DEFINE("WFU_ERROR_UPLOAD_NOFILESELECTED", __("No file!", "wp-file-upload"));
	DEFINE("WFU_ERROR_UPLOAD_CANCELLED", __("Upload failed! The upload has been canceled by the user or the browser dropped the connection.", "wp-file-upload"));
	DEFINE("WFU_ERROR_UNKNOWN", __("Upload failed! Unknown error.", "wp-file-upload"));
	DEFINE("WFU_ERROR_CONTACT_ADMIN", __("Please contact the administrator.", "wp-file-upload"));
	DEFINE("WFU_ERROR_REMOTESERVER_NORESULT", __("No result from remote server!", "wp-file-upload"));
	DEFINE("WFU_ERROR_JSONPARSE_FILEMESSAGE", __(" but with warnings", "wp-file-upload"));
	DEFINE("WFU_ERROR_JSONPARSE_MESSAGE", __("Warning: JSON parse error.", "wp-file-upload"));
	DEFINE("WFU_ERROR_JSONPARSE_ADMINMESSAGE", __("Upload parameters of this file, passed as JSON string to the handler, could not be parsed.", "wp-file-upload"));
	DEFINE("WFU_ERROR_JSONPARSE_HEADERMESSAGE", __("Warning: JSON parse error.", "wp-file-upload"));
	DEFINE("WFU_ERROR_JSONPARSE_HEADERADMINMESSAGE", __("UploadStates, passed as JSON string to the handler, could not be parsed.", "wp-file-upload"));
	DEFINE("WFU_ERROR_REDIRECTION_ERRORCODE0", __("Redirection to classic form functionality occurred due to unknown error.", "wp-file-upload"));
	DEFINE("WFU_ERROR_REDIRECTION_ERRORCODE1", __("Redirection to classic form functionality occurred because AJAX is not supported.", "wp-file-upload"));
	DEFINE("WFU_ERROR_REDIRECTION_ERRORCODE2", __("Redirection to classic form functionality occurred because HTML5 is not supported.", "wp-file-upload"));
	DEFINE("WFU_ERROR_REDIRECTION_ERRORCODE3", __("Redirection to classic form functionality occurred due to JSON parse error.", "wp-file-upload"));
	DEFINE("WFU_ERROR_ENABLE_POPUPS", __("Please enable popup windows from the browser's settings!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATA_EMPTY", __("cannot be empty!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATANUMBER_INVALID", __("number not valid!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATAEMAIL_INVALID", __("email not valid!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATACONFIRMEMAIL_NOMATCH", __("emails do not match!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATACONFIRMEMAIL_NOBASE", __("no base email field in group!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATACONFIRMPASSWORD_NOMATCH", __("passwords do not match!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATACONFIRMPASSWORD_NOBASE", __("no base password field in group!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATACHECKBOX_NOTCHECKED", __("checkbox unchecked!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATARADIO_NOTSELECTED", __("no option selected!", "wp-file-upload"));
	DEFINE("WFU_ERROR_USERDATALIST_NOITEMSELECTED", __("no item selected!", "wp-file-upload"));
	DEFINE("WFU_ERROR_SAME_PLUGINID", __("There are more than one instances of the plugin in this page with the same id. Please change it.", "wp-file-upload"));
	DEFINE("WFU_ERROR_PAGE_OBSOLETE", __("Cannot edit the shortcode because the page has been modified. Please reload the page.", "wp-file-upload"));
	DEFINE("WFU_ERROR_WEBCAM_NOTSUPPORTED", __("Your browser does not support webcam capture!", "wp-file-upload"));
	DEFINE("WFU_ERROR_WEBCAM_VIDEO_NOTSUPPORTED", __("Your browser does not support video recording from the webcam!", "wp-file-upload"));
	DEFINE("WFU_ERROR_WEBCAM_VIDEO_NOTHINGRECORDED", __("No video was recorded!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_OLDPHP", __("ERROR: Captcha not supported! You have an old PHP version. Upgrade your PHP or use RecaptchaV2 (no account).", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_MULTIPLE_NOTALLOWED", __("ERROR: Only one instance of RecaptchaV1 can exist on the same page. Please notify administrator.", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_MULTIPLE_NOTALLOWED_ADMIN", __("ERROR: Only one instance of RecaptchaV1 can exist on the same page. Please use RecaptchaV1 (no account).", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_NOSITEKEY", __("ERROR: No site key. Please contact administrator!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_NOSITEKEY_ADMIN", __("ERROR: No site key defined! Please go to the plugin settings in Dashboard to define Google Recaptcha keys.", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_NOCHALLENGE", __("Bad captcha image!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_NOINPUT", __("No input!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_EMPTY", __("Captcha not completed!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_WRONGCAPTCHA", __("Wrong captcha!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_REFRESHING", __("Error refreshing captcha!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_UNKNOWNERROR", __("Unknown captcha error!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_NOTSUPPORTED", __("Captcha not supported by your browser!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_MISSINGINPUTSECRET", __("the secret parameter is missing", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_INVALIDINPUTSECRET", __("the secret parameter is invalid or malformed", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_MISSINGINPUTRESPONSE", __("the response parameter is missing", "wp-file-upload"));
	DEFINE("WFU_ERROR_CAPTCHA_INVALIDINPUTRESPONSE", __("the response parameter is invalid or malformed", "wp-file-upload"));
	DEFINE("WFU_ERROR_REDIRECTION_NODRAGDROP", __("Please do not use drag drop due to an internal problem.", "wp-file-upload"));
	DEFINE("WFU_ERROR_CHUNKEDUPLOAD_UNIQUEIDEMPTY", __("Error during chunked upload. Unique ID empty in chunk %d", "wp-file-upload"));
	DEFINE("WFU_ERROR_CHUNKEDUPLOAD_NOTALLOWED", __("Chunked upload is not allowed!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CHUNKEDUPLOAD_ABORTED", __("Chunked upload aborted due to error in previous chunk!", "wp-file-upload"));
	DEFINE("WFU_ERROR_CHUNKEDUPLOAD_CONCATFAILED", __("Chunked upload failed, final file could not be created!", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_CHUNKWRITEFAILED", __("Could not write file chuck to destination on chunk %d", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_CHUNKENLARGEFAILED", __("Could not enlarge destination file on chunk %d", "wp-file-upload"));
	DEFINE("WFU_ERROR_ADMIN_CHUNKHANDLEFAILED", __("Could not open file handles on chunk %d", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DOWNLOADFILE_NOTALLOWED", __("You are not allowed to download this file!", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DOWNLOADFILE_NOTEXIST", __("File does not exist!", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DOWNLOADFILE_FAILED", __("Could not download file!", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DELETEFILE_NOTALLOWED", __("You are not allowed to delete this file!", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DELETEFILE_FAILED", __("File was not deleted!", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DELETEFILES_ALLFAILED", __("No file was deleted!", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DELETEFILES_SOMEFAILED", __("Some files were not deleted!", "wp-file-upload"));
	//warning messages
	DEFINE("WFU_WARNING_FILE_EXISTS", __("Upload skipped! File already exists.", "wp-file-upload"));
	DEFINE("WFU_WARNING_FILE_SUSPICIOUS", __("The extension of the file does not match its contents.", "wp-file-upload"));
	DEFINE("WFU_WARNING_ADMIN_FILE_SUSPICIOUS", __("Upload succeeded but the file is suspicious because its contents do not match its extension. Its proper filename is: ", "wp-file-upload"));
	DEFINE("WFU_WARNING_NOFILES_SELECTED", __("No files have been selected!", "wp-file-upload"));
	DEFINE("WFU_WARNING_CONSENT_NOTCOMPLETED", __("Please complete the consent question before continuing the upload!", "wp-file-upload"));
	DEFINE("WFU_WARNING_WPFILEBASE_NOTUPDATED_NOFILES", __("WPFilebase Plugin not updated because there were no files uploaded.", "wp-file-upload"));
	DEFINE("WFU_WARNING_NOTIFY_NOTSENT_NOFILES", __("Notification email was not sent because there were no files uploaded.", "wp-file-upload"));
	DEFINE("WFU_WARNING_NOTIFY_NOTSENT_NORECIPIENTS", __("Notification email was not sent because no recipients were defined. Please check notifyrecipients attribute in the shortcode.", "wp-file-upload"));
	DEFINE("WFU_WARNING_NOTIFY_NOTSENT_UNKNOWNERROR", __("Notification email was not sent due to an error. Please check notifyrecipients, notifysubject and notifymessage attributes for errors.", "wp-file-upload"));
	DEFINE("WFU_WARNING_REDIRECT_NOTEXECUTED_EMPTY", __("Redirection not executed because redirection link is empty. Please check redirectlink attribute.", "wp-file-upload"));
	DEFINE("WFU_WARNING_REDIRECT_NOTEXECUTED_FILESFAILED", __("Redirection not executed because not all files were successfully uploaded.", "wp-file-upload"));
	DEFINE("WFU_WARNING_POTENTIAL_DOS_EMAIL_SUBJECT", __("Potential Denial-Of-Service Attack on {SITE}", "wp-file-upload"));
	DEFINE("WFU_WARNING_POTENTIAL_DOS_EMAIL_MESSAGE", __("Hello admin\n\nThis is a message from Wordpress File Upload Plugin to notify you that a potential Denial-Of-Service attack has been detected on {SITE}.\n\nThe plugin detected more than {FILENUM} uploads within {INTERVAL} seconds.\n\nAll file uploads that exceed this limit are rejected to protect the website from overflowing.\n\nPlease check the upload history log in the plugin's area in Dashboard for any suspicious behaviour.\n\nA new message will follow if the situation remains.\n\nThanks", "wp-file-upload"));
	DEFINE("WFU_WARNING_ALT_IPTANUS_SERVER_ACTIVATED", __("You have activated an alternative insecure Iptanus Services Server. For details please contact info@iptanus.com.", "wp-file-upload"));
	DEFINE("WFU_WARNING_IPTANUS_SERVER_UNREACHABLE", __("Iptanus Services Server is unreachable. This may cause problems on some plugin functions. Please read this :article: for resolution.", "wp-file-upload"));
	//admin area messages
	DEFINE("WFU_DASHBOARD_ADD_SHORTCODE_REJECTED", __("Failed to add the shortcode to the page/post. Please try again. If the message persists, contact administrator.", "wp-file-upload"));
	DEFINE("WFU_DASHBOARD_EDIT_SHORTCODE_REJECTED", __("Failed to edit the shortcode because the contents of the page changed. Try again to edit the shortcode.", "wp-file-upload"));
	DEFINE("WFU_DASHBOARD_DELETE_SHORTCODE_REJECTED", __("Failed to delete the shortcode because the contents of the page changed. Try again to delete it.", "wp-file-upload"));
	DEFINE("WFU_DASHBOARD_PAGE_OBSOLETE", __("The page containing the shortcode has been modified and it is no longer valid. Please go back to reload the shortcode.", "wp-file-upload"));
	DEFINE("WFU_DASHBOARD_UPDATE_SHORTCODE_REJECTED", __("Failed to update the shortcode because the contents of the page changed. Go back to reload the shortcode.", "wp-file-upload"));
	DEFINE("WFU_DASHBOARD_UPDATE_SHORTCODE_FAILED", __("Failed to update the shortcode. Please try again. If the problem persists, go back and reload the shortcode.", "wp-file-upload"));
	//test messages
	DEFINE("WFU_TESTMESSAGE_MESSAGE", __('This is a test message', 'wp-file-upload'));
	DEFINE("WFU_TESTMESSAGE_ADMINMESSAGE", __('This is a test administrator message', 'wp-file-upload'));
	DEFINE("WFU_TESTMESSAGE_FILE1_HEADER", __('File testfile 1 under test', 'wp-file-upload'));
	DEFINE("WFU_TESTMESSAGE_FILE1_MESSAGE", __('File testfile 1 message', 'wp-file-upload'));
	DEFINE("WFU_TESTMESSAGE_FILE1_ADMINMESSAGE", __('File testfile 1 administrator message', 'wp-file-upload'));
	DEFINE("WFU_TESTMESSAGE_FILE2_HEADER", __('File testfile 2 under test', 'wp-file-upload'));
	DEFINE("WFU_TESTMESSAGE_FILE2_MESSAGE", __('File testfile 2 message', 'wp-file-upload'));
	DEFINE("WFU_TESTMESSAGE_FILE2_ADMINMESSAGE", __('File testfile 2 administrator message', 'wp-file-upload'));
	//variables tool-tips
	DEFINE("WFU_VARIABLE_TITLE_USERID", __("Insert variable %userid% inside text. It will be replaced by the id of the current user.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_USERNAME", __("Insert variable %username% inside text. It will be replaced by the username of the current user.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_USEREMAIL", __("Insert variable %useremail% inside text. It will be replaced by the email of the current user.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_FILENAME", __("Insert variable %filename% inside text. It will be replaced by the filename of the uploaded file.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_FILEPATH", __("Insert variable %filepath% inside text. It will be replaced by the full filepath of the uploaded file.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_BLOGID", __("Insert variable %blogid% inside text. It will be replaced by the blog id of the website.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_PAGEID", __("Insert variable %pageid% inside text. It will be replaced by the id of the current page.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_PAGETITLE", __("Insert variable %pagetitle% inside text. It will be replaced by the title of the current page.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_USERDATAXXX", __("Insert variable %userdataXXX% inside text. Select the user field from the drop-down list. It will be replaced by the value that the user entered in this field.", "wp-file-upload"));
	DEFINE("WFU_VARIABLE_TITLE_N", __("Insert variable %n% inside text to denote a line change.", "wp-file-upload"));
	//other plugin values
	DEFINE("WFU_WARNINGMESSAGE_NOSAVE", __('File %filename% uploaded successfully but not saved', 'wp-file-upload'));  
	DEFINE("WFU_NOTIFY_TESTMODE", __("Test Mode", "wp-file-upload"));
	DEFINE("WFU_SUBDIR_SELECTDIR", __("select dir...", "wp-file-upload"));
	DEFINE("WFU_SUBDIR_TYPEDIR", __("type dir", "wp-file-upload"));
	DEFINE("WFU_SUCCESSMESSAGE_DETAILS", __('Upload path: %filepath%', 'wp-file-upload'));
	DEFINE("WFU_FAILMESSAGE_DETAILS", __('Failed upload path: %filepath%', 'wp-file-upload'));
	DEFINE("WFU_USERDATA_REQUIREDLABEL", __(' (required)', 'wp-file-upload'));
	DEFINE("WFU_PAGEEXIT_PROMPT", __('Files are being uploaded. Are you sure you want to exit the page?', 'wp-file-upload'));
	DEFINE("WFU_MESSAGE_CAPTCHA_CHECKING", __("checking captcha...", "wp-file-upload"));
	DEFINE("WFU_MESSAGE_CAPTCHA_REFRESHING", __("refreshing...", "wp-file-upload"));
	DEFINE("WFU_MESSAGE_CAPTCHA_OK", __("correct captcha", "wp-file-upload"));
	DEFINE("WFU_CONFIRMBOX_CAPTION", __("click to continue the upload", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DELETEFILE_PROMPT", __("Are you sure you want to delete this file?", "wp-file-upload"));
	DEFINE("WFU_BROWSER_DELETEFILES_PROMPT", __("Are you sure you want to delete these files?", "wp-file-upload"));
	DEFINE("WFU_BROWSER_BULKACTION_TITLE", __("Bulk Actions", "wp-file-upload"));
	DEFINE("WFU_BROWSER_BULKACTION_LABEL", __("Apply", "wp-file-upload"));
	DEFINE("WFU_PAGINATION_PAGE", __("Page", "wp-file-upload"));
	DEFINE("WFU_PAGINATION_OF", __("of ", "wp-file-upload"));
	DEFINE("WFU_CANCEL_UPLOAD_PROMPT", __("Are you sure that you want to cancel the upload?", "wp-file-upload"));
	DEFINE("WFU_FILE_CANCEL_HINT", __("cancel upload of this file", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE0", __("Upload in progress", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE1", __("Upload in progress with warnings!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE2", __("Upload in progress but some files already failed!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE3", __("Upload in progress but no files uploaded so far!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE4", __("All files uploaded successfully", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE5", __("All files uploaded successfully but there are warnings!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE5_SINGLEFILE", __("File uploaded successfully but there are warnings!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE6", __("Some files failed to upload!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE7", __("All files failed to upload", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE7_SINGLEFILE", __("File failed to upload", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE8", __("There are no files to upload!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE9", __("Test upload message", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE10", __("JSON parse warning!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE11", __("please wait while redirecting...", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE12", __("Upload failed!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE13", __("Submitting data", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE14", __("Data submitted successfully!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE15", __("Data were not submitted!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE16", __("Cancelling upload", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE17", __("Upload cancelled!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE18", __("Upload succeeded!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE19", __("Upload completed but no files were saved!", "wp-file-upload"));
	DEFINE("WFU_UPLOAD_STATE19_SINGLEFILE", __("File was not saved due to personal data policy!", "wp-file-upload"));
	DEFINE("WFU_PAGE_PLUGINEDITOR_BUTTONTITLE", __("Open visual shortcode editor in new window", "wp-file-upload"));
	DEFINE("WFU_PAGE_PLUGINEDITOR_LOADING", __("loading visual editor", "wp-file-upload"));
	DEFINE("WFU_CONFIRM_CLEARFILES", __("Clear file list?", "wp-file-upload"));
	DEFINE("WFU_DROP_HERE_MESSAGE", __('DROP HERE', 'wp-file-upload'));
	//webcam values
	DEFINE("WFU_WEBCAM_RECVIDEO_BTN", __('record video', 'wp-file-upload'));
	DEFINE("WFU_WEBCAM_TAKEPIC_BTN", __('take a picture', 'wp-file-upload'));
	DEFINE("WFU_WEBCAM_TURNONOFF_BTN", __('turn webcam on/off', 'wp-file-upload'));
	DEFINE("WFU_WEBCAM_GOLIVE_BTN", __('go live again', 'wp-file-upload'));
	DEFINE("WFU_WEBCAM_STOPREC_BTN", __('end recording', 'wp-file-upload'));
	DEFINE("WFU_WEBCAM_PLAY_BTN", __('play', 'wp-file-upload'));
	DEFINE("WFU_WEBCAM_PAUSE_BTN", __('pause', 'wp-file-upload'));
	DEFINE("WFU_WEBCAM_GOBACK_BTN", __('go to the beginning', 'wp-file-upload'));
	DEFINE("WFU_WEBCAM_GOFWD_BTN", __('go to the end', 'wp-file-upload'));
	//widget values
	DEFINE("WFU_WIDGET_PLUGINFORM_TITLE", __('Wordpress File Upload Form', 'wp-file-upload'));
	DEFINE("WFU_WIDGET_PLUGINFORM_DESCRIPTION", __('Wordpress File Upload plugin uploader for sidebars', 'wp-file-upload'));
	DEFINE("WFU_WIDGET_SIDEBAR_DEFAULTTITLE", __('Upload Files', 'wp-file-upload'));
}

/*********** Environment Variables ************/
//plugin default values
$GLOBALS["WFU_GLOBALS"] = array(
	"WFU_UPLOADID" => array( "Default Upload ID", "string", "1", "The default upload ID of the uploader shortcode. It can be any integer from 1 and above." ),
	"WFU_SINGLEBUTTON" => array( "Default Single-Button Status", "string", "false", "The default single-button status of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_UPLOADROLE" => array( "Default Upload Role", "string", "all,guests", "The default upload role of the uploader shortcode. It can be a comma-separated list of role slugs, including keywords 'all' and 'guests'." ),
	"WFU_UPLOADPATH" => array( "Default Upload Path", "string", "uploads", "The default upload path of the uploader shortcode. It must be a folder relative to wp-content dir." ),
	"WFU_FITMODE" => array( "Default Fit Mode", "string", "fixed", "The default fit mode of the uploader shortcode. It can be 'fixed' or 'responsive'." ),
	"WFU_ALLOWNOFILE" => array( "Default Allow No File Mode", "string", "false", "The default mode for allowing no file uploads. If it is set to 'true' then an upload form can be submitted even if a file has not been selected. It can be 'true' or 'false'." ),
	"WFU_ALLOWNOFILE" => array( "Default Allow No File Mode", "string", "false", "The default mode for allowing no file uploads. If it is set to 'true' then an upload form can be submitted even if a file has not been selected. It can be 'true' or 'false'." ),
	"WFU_RESETMODE" => array( "Default Reset Form Mode", "string", "always", "The default reset mode of the upload form. It can be 'always', 'onsuccess' or 'never'." ),
	"WFU_FORCEFILENAME" => array( "Default Force Filename State", "string", "false", "The default force filename state (force plugin to leave filename unchanged) of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_UPLOADPATTERNS" => array( "Default Upload Extensions", "string", "*.*", "The default allowed file extensions of the uploader shortcode. It can be a comma-separated list of wildcard extensions." ),
	"WFU_MAXSIZE" => array( "Default Maximum File Size", "string", "50", "The default maximum allowed file size of the uploader shortcode in Megabytes. It can be any positive number." ),
	"WFU_ACCESSMETHOD" => array( "Default Access Method", "string", "normal", "The default access method (of the website filesystem) of the uploader shortcode. It can be 'normal' or 'ftp'." ),
	"WFU_FTPINFO" => array( "Default FTP Access Information", "string", "", "The default FTP access parameters of the uploader shortcode. It's syntax is 'username:password@ftp_domain'." ),
	"WFU_USEFTPDOMAIN" => array( "Default Use FTP Domain State", "string", "false", "The default use FTP domain state (use the FTP domain defined in ftpinfo to store the uploaded files) of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_FTPPASSIVEMODE" => array( "Default FTP Passive Mode State", "string", "false", "The default FTP passive mode (use passive mode or not for FTP access) of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_FTPFILEPERMISSIONS" => array( "Default FTP File Permissions", "string", "", "The default FTP passive mode (use passive mode or not for FTP access) of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_DUBLICATESPOLICY" => array( "Default Duplicate File Action", "string", "overwrite", "The default duplicate file action of the uploader shortcode. It can be 'overwrite', 'reject' or 'mantain both'." ),
	"WFU_UNIQUEPATTERN" => array( "Default Duplicate File Pattern", "string", "index", "The default duplicate file pattern of the uploader shortcode. It can be 'index' or 'datetimestamp'." ),
	"WFU_FILEBASELINK" => array( "Default WPFilebase Update State", "string", "false", "The default WPFilebase plugin update state of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_NOTIFY" => array( "Default Email Notification State", "string", "false", "The default email notification state of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_NOTIFYRECIPIENTS" => array( "Default Email Recipients", "string", "", "The default email recipients of the uploader shortcode. It can be a comma-separated list of email addresses." ),
	"WFU_NOTIFYHEADERS" => array( "Default Email Headers", "string", "", "The default email headers of the uploader shortcode." ),
	"WFU_ATTACHFILE" => array( "Default Attach File State", "string", "false", "The default attach file to email state of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_REDIRECT" => array( "Default Redirection State", "string", "false", "The default redirection state of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_REDIRECTLINK" => array( "Default Redirect URL", "string", "", "The default redirect URL of the uploader shortcode." ),
	"WFU_ADMINMESSAGES" => array( "Default State for Admin Messages", "string", "false", "The default state of displaying or not admin messages of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_SUCCESSMESSAGECOLORS" => array( "Default Colors for Success Message", "string", "#006600,#EEFFEE,#006666", "The default color triplet (text, background and border colors) of success message of the uploader shortcode." ),
	"WFU_WARNINGMESSAGECOLORS" => array( "Default Colors for Warning Message", "string", "#F88017,#FEF2E7,#633309", "The default color triplet (text, background and border colors) of warning message of the uploader shortcode." ),
	"WFU_FAILMESSAGECOLORS" => array( "Default Colors for Fail Message", "string", "#660000,#FFEEEE,#666600", "The default color triplet (text, background and border colors) of fail message of the uploader shortcode." ),
	"WFU_WAITMESSAGECOLORS" => array( "Default Colors for Wait Message", "string", "#666666,#EEEEEE,#333333", "The default color triplet (text, background and border colors) of wait message of the uploader shortcode." ),
	"WFU_SHOWTARGETFOLDER" => array( "Default State for Target Folder", "string", "false", "The default state of displaying or not the target folder of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_TARGETFOLDERLABEL" => array( "Default Text for Target Folder Label", "string", "Upload Directory", "The default text of the target folder label of the uploader shortcode." ),
	"WFU_ASKFORSUBFOLDERS" => array( "Default Subfolders State", "string", "false", "The default state of displaying or not a list of subfolders of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_SUBFOLDERLABEL" => array( "Default Text of Subfolders Label", "string", "Select Subfolder", "The default text of subfolders label of the uploader shortcode." ),
	"WFU_SUBFOLDERTREE" => array( "Default Subfolders List", "string", "", "The default list of subfolders of the uploader shortcode. Check plugin's support page for syntax." ),
	"WFU_FORCECLASSIC" => array( "Default Disable AJAX State", "string", "false", "The default state of disabling or not AJAX functionality of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_TESTMODE" => array( "Default Test Mode State", "string", "false", "The default state of test mode of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_DEBUGMODE" => array( "Default Debug Mode State", "string", "false", "The default state of debug mode of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_WIDTHS" => array( "Default Element Widths", "string", "", "The default widths of the elements of the uploader shortcode. It is a comma-separated list of element widths." ),
	"WFU_HEIGHTS" => array( "Default Element Heights", "string", "", "The default heights of the elements of the uploader shortcode. It is a comma-separated list of element heights." ),
	"WFU_PLACEMENTS" => array( "Default Element Placements", "string", "title/filename+selectbutton+uploadbutton/subfolders"."/userdata"."/message", "The default placements of the elements of the uploader shortcode. Check plugin's support page for syntax." ),
	"WFU_USERDATA" => array( "Default User Fields State", "string", "false", "The default state of custom user fields of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_MEDIALINK" => array( "Default Add to Media State", "string", "false", "The default state for adding files to Media of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_POSTLINK" => array( "Default Attachment to Post State", "string", "false", "The default state for attaching files to current post of the uploader shortcode. It can be 'true' or 'false'." ),
	"WFU_WEBCAM" => array( "Default Webcam State", "string", "false", "The default state for webcam capture. It can be 'true' or 'false'." ),
	"WFU_AUDIOCAPTURE" => array( "Default Capture Audio State", "string", "false", "The default state of audio capture. If it is set to 'true' then audio will be captured, together with video from the webcam. It can be 'true' or 'false'." ),
	"WFU_WEBCAMMODE" => array( "Default Webcam Mode", "string", "capture video", "The default webcam capture mode. It can be 'capture video', 'take photos' or 'both'." ),
	"WFU_VIDEOWIDTH" => array( "Default Video Width", "string", "", "The default preferable video width for webcam capture. It can be any positive integer in pixels." ),
	"WFU_VIDEOHEIGHT" => array( "Default Video Height", "string", "", "The default preferable video height for webcam capture. It can be any positive integer in pixels." ),
	"WFU_VIDEOASPECTRATIO" => array( "Default Video Aspect Ratio", "string", "", "The default preferable video aspect ratio for webcam video capture. It can be any positive value." ),
	"WFU_VIDEOFRAMERATE" => array( "Default Video Frame Rate", "string", "", "The default preferable video frame rate for webcam video capture. It can be any positive value in frames/sec." ),
	"WFU_CAMERAFACING" => array( "Default Camera Facing Mode", "string", "any", "The default preferable camera to be used for video/screenshot capture. It can be 'any', 'front' or 'back'." ),
	"WFU_MAXRECORDTIME" => array( "Default Maximum Record Time", "string", "10", "The default maximum video recording time in seconds. The default value is 10 seconds." ),
	"WFU_ASKCONSENT" => array( "Default Ask Consent State", "string", "false", "The default state of personal data consent request. The default value is false." ),
	"WFU_PERSONALDATATYPES" => array( "Default Personal Data Types", "string", "userdata", "The default personal data types. The default value is 'userdata'." ),
	"WFU_NOTREMEMBERCONSENT" => array( "Default Do Not Remember Consent Answer State", "string", "false", "The default state about remembering or not user's answer on consent question. The default value is true." ),
	"WFU_CONSENTREJECTUPLOAD" => array( "Default Reject Upload on Consent Denial State", "string", "false", "The default state of continuing or rejecting the upload depending on consent answer. The default value is true." ),
	"WFU_CONSENTFORMAT" => array( "Default Consent Format", "string", "radio", "The default format of consent question. The default value is 'checkbox'." ),
	"WFU_CONSENTPRESELECT" => array( "Default Consent Preselect State", "string", "none", "The default preselect state of consent question when checkbox format is active. The default value is false." )
);
//other plugin values
$GLOBALS["WFU_GLOBALS"] += array(
	"WFU_DEBUG" => array( "Plugin Debug Mode", "string", "OFF", "If DEBUG mode is activated then advanced hook of plugin's function can be performed. This option may make the plugin slower, so use it very carefully. It can be 'OFF' or 'ON'." ),
	"WFU_RESTRICT_FRONTEND_LOADING" => array( "Restrict Front-End Loading", "string", "false", "It defines whether the plugin will load on all pages or specific ones. If it is 'false' then it will load on all pages. To restrict loading only on specific pages set a comma-separated list of page or post IDs." ),
	"WFU_UPLOADPROGRESS_MODE" => array( "Upload Progress Mode", "string", "incremental", "Defines how the upload progress is calculated. It can be 'incremental' or 'absolute'. Default value is 'incremental'." ),
	"WFU_DOS_ATTACKS_CHECK" => array( "Check for Denial-Of-Service Attacks", "string", "true", "If it is true then then plugin will check if the number of files uploaded within a specific amount of time exceeds the limit, thus protecting from DOS attacks. It can be 'true' or 'false'." ),
	"WFU_DOS_ATTACKS_FILE_LIMIT" => array( "Denial-Of-Service File Limit", "integer", 10000, "Defines the maximum number of files that are allowed to be uploaded within a specific amount of time. It can be any positive integer." ),
	"WFU_DOS_ATTACKS_TIME_INTERVAL" => array( "Denial-Of-Service Time Interval", "integer", 3600, "Defines the time interval for DOS attacks check. The time interval is given in seconds." ),
	"WFU_DOS_ATTACKS_ADMIN_EMAIL_FREQUENCY" => array( "Denial-Of-Service Admin Email Frequency", "integer", 3600, "Defines how frequently an email will be sent to administrator notifying for Denial-Of-Service attacks. The time interval is given in seconds." ),
	"WFU_SANITIZE_FILENAME_MODE" => array( "Filename Sanitization Mode", "string", "strict", "The sanitization mode for filenames. It can be 'strict' or 'loose'." ),
	"WFU_SANITIZE_FILENAME_DOTS" => array( "Sanitize Filename Dots", "string", "true", "Convert dot symbols (.) in filename into dashes, in order to avoid double extensions. It can be 'true' or 'false'." ),
	"WFU_WILDCARD_ASTERISK_MODE" => array( "Wildcard Asterisk Mode", "string", "strict", "The mode of wildcard pattern asterisk symbol. If it is strict, then the asterisk will not match dot (.) characters. It can be 'strict' or 'loose'." ),
	"WFU_PHP_ARRAY_MAXLEN" => array( "Max PHP Array Length", "string", "10000", "The maximum allowable number of items of a PHP array." ),
	"WFU_ADMINBROWSER_TABLE_MAXROWS" => array( "Admin Browser Rows Per Page", "integer", 25, "The number of rows per page of the admin browser. A value equal to zero or less denotes no pagination." ),
	"WFU_HISTORYLOG_TABLE_MAXROWS" => array( "History Log Table Rows Per Page", "integer", 25, "The number of rows per page of the History Log table." ),
	"WFU_UPLOADEDFILES_TABLE_MAXROWS" => array( "Uploaded Files Table Rows Per Page", "integer", 25, "The number of rows per page of the Uploaded Files table." ),
	"WFU_ALTERNATIVE_RANDOMIZER" => array( "Use Alternative Randomizer", "string", "false", "On fast web servers the plugin's generator of random strings may not work properly causing various problems. If it is set to true, an alternative randomizer method is employed that works for fast web servers. It can be 'true' or 'false'." ),
	"WFU_FORCE_NOTIFICATIONS" => array( "Force Email Notifications", "string", "false", "Send email notifications (if they are activated) even if no file has been uploaded. It can be 'true' or 'false'." ),
	"WFU_UPDATE_MEDIA_ON_DELETE" => array( "Update Media on Delete", "string", "true", "When an uploaded file is deleted then delete also the corresponding Media Library item if exists. It can be 'true' or 'false'." ),
	"WFU_DASHBOARD_PROTECTED" => array( "Dashboard Is Protected", "string", "false", "If /wp-admin folder is password protected then this variable should be set to 'true' so that internal operations of the plugin can work. The username and password should also be set." ),
	"WFU_DASHBOARD_USERNAME" => array( "Protected Dashboard Username", "string", "", "Username entry for accessing protected /wp-admin folder." ),
	"WFU_DASHBOARD_PASSWORD" => array( "Protected Dashboard Password", "string", "", "Password entry for accessing protected /wp-admin folder." ),
	"WFU_EXPORT_DATA_SEPARATOR" => array( "Export Data Separator", "string", ",", "This is the delimiter of the exported file data columns. It can be any symbol. Default value is comma (,)." ),
	"WFU_EXPORT_USERDATA_SEPARATOR" => array( "Export User Data Separator", "string", ";", "This is the delimiter of the exported user data of each file. It can be any symbol. Default value is semicolon (;)." ),
	"WFU_DISABLE_VERSION_CHECK" => array( "Disable Version Check", "string", "false", "If it is set to 'true' then the plugin will not check if there are any new versions available. This is a temporary solution to problems having some users accessing Iptanus Services server causing the plugin to stall. It can be 'true' or 'false'." ),
	"WFU_RELAX_CURL_VERIFY_HOST" => array( "Relax cURL Host Verification", "string", "false", "If it is set to 'true' then CURLOPT_SSL_VERIFYHOST will be disabled when executing a cURL POST request. This is required in some cases so that the plugin can reach https://services2.iptanus.com, because on some servers it fails with a file_get_contents warning. It can be 'true' or 'false'." ),
	"WFU_USE_ALT_IPTANUS_SERVER" => array( "Use Alternative Iptanus Server", "string", "false", "If it is set to 'true' then the alternative Iptanus server will be used. This is a work-around in some cases where the website cannot reach https://services2.iptanus.com. It can be 'true' or 'false'." ),
	"WFU_ALT_IPTANUS_SERVER" => array( "Alternative Iptanus Server", "string", "https://iptanusservices.appspot.com", "If it is set then this is the URL of the alternative Iptanus server." ),
	"WFU_ALT_VERSION_SERVER" => array( "Alternative Version Server", "string", "https://iptanusservices.appspot.com/g79xo30q8s", "If the alternative Iptanus server is used and this variable is not empty, then it will be used as the alternative Version Server URL." ),
	"WFU_MINIFY_INLINE_CSS" => array( "Minify Inline CSS Code", "string", "true", "Defines whether the inline CSS code will be minified. It can be 'true' or 'false'." ),
	"WFU_MINIFY_INLINE_JS" => array( "Minify Inline JS Code", "string", "true", "Defines whether the inline Javascript code will be minified. It can be 'true' or 'false'." ),
	"WFU_US_SESSION_LEGACY" => array( "Session Legacy Option", "string", "true", "Defines whether the old (legacy) operation of reading and storing session data (using session_start in header) will be used. By default it is set to 'true' to maintain backward compatibility." ),
	"WFU_US_COOKIE_LIFE" => array( "Session Cookie Life", "integer", 48, "Defines the life of session cookie, in hours." ),
	"WFU_US_DBOPTION_BASE" => array( "DB Option User State Base", "string", "cookies", "Defines how DB option defines the unique user state key. It can take the values 'session' or 'cookies'." ),
	"WFU_US_DBOPTION_CHECK" => array( "DB Option User State Check Interval", "integer", 7200, "Defines how often (in seconds) the plugin will update user state list, when user state is saved in DB option table." ),
	"WFU_US_DBOPTION_LIFE" => array( "DB Option User State Life", "integer", 1800, "Defines the maximum time of inactivity of a user state, when user state is saved in DB option table." ),
	"WFU_US_HANDLER_CHANGED" => array( "User State Handler Changed", "string", "false", "Defines whether the plugin changed automatically the user state handler during installation." ),
	"WFU_US_DBOPTION_USEOLD" => array( "Use Old DB Option Handler", "string", "false", "Defines whether the old DBOption user state handlers will be used." ),
	"WFU_US_DEADLOCK_TIMEOUT" => array( "Database Deadlock Timeout", "integer", 10, "Defines for how long a deadlocked database transaction will be repeated." ),
	"WFU_US_LOG_DBERRORS" => array( "Log Database Errors", "string", "false", "Defines whether database errors will be logged." ),
	"WFU_QUEUE_ACTIVE" => array( "Enable Queue Functionality", "string", "true", "Defines whether queue operation is active." ),
	"WFU_QUEUE_THREAD_TIMEOUT" => array( "Queue Thread Timeout", "integer", 5, "Defines for how long, in seconds, a queue will wait for a thread to finish before aborting the operation." ),
	"WFU_QUEUE_LOOP_DELAY" => array( "Queue Loop Delay", "integer", 100, "Defines the time, in milliseconds, a wait loop will sleep before continuing." ),
	"WFU_PD_VISIBLE_OPLEVELS" => array( "Personal Data Visible Operation Levels", "integer", 3, "Defines how deep administrators can go into personal data operation details. A value of -1 denotes that there is no limit." ),
	"WFU_PD_VISIBLE_PERLEVELS" => array( "Personal Data Visible Permission Levels", "integer", 2, "Defines how deep administrators can go into personal data permission details. A value of -1 denotes that there is no limit." ),
	"WFU_PD_VISIBLE_LOGLEVELS" => array( "Personal Data Visible Log Action Levels", "integer", 2, "Defines how deep administrators can go into personal data log action details. A value of -1 denotes that there is no limit." ),
	"WFU_UPLOADEDFILES_MENU" => array( "Uploaded Files Menu State", "string", "true", "Defines whether the Uploaded Files Dashboard menu item will be shown or not. It can be 'true' or 'false'." ),
	"WFU_UPLOADEDFILES_DEFACTION" => array( "Uploaded Files Default Action", "string", "adminbrowser", "Defines the default action that will be executed when a file link is pressed in Uploaded Files page. It can be 'details', 'adminbrowser', 'historylog', 'link', 'download' and 'none'." ),
	"WFU_UPLOADEDFILES_COLUMNS" => array( "Uploaded Files Columns", "string", "#, file, upload_date, user, properties, remarks, actions", "Defines the visible columns of the Uploaded Files list as well as their order. It is noted that 'File' column is always visible and it is the second column if '#' column is visible, or the first one if '#' column is hidden." ),
	"WFU_UPLOADEDFILES_ACTIONS" => array( "Uploaded Files Actions", "string", "details, media, adminbrowser, historylog, link, download", "Defines the allowable actions and their order for each file in Uploaded Files list. It is noted that the actions shown for each file depend on its properties." ),
	"WFU_UPLOADEDFILES_HIDEINVALID" => array( "Hide Invalid Uploaded Files", "string", "false", "Defines whether all uploaded file records will be shown in Uploaded File menu or only the valid ones. Invalid are the records who are obsolete or their files do not exist anymore." ),
	"WFU_UPLOADEDFILES_RESET_TIME" => array( "Uploaded Files Reset Time", "integer", 5, "Defines the interval in seconds before the unread uploaded files can be marked as read. A value of -1 denotes that there is no interval." ),
	"WFU_UPLOADEDFILES_BARMENU" => array( "Uploaded Files Toolbar Menu State", "string", "true", "Defines whether the Uploaded Files Toolbar (Admin Bar) menu item will be shown or not. It can be 'true' or 'false'." ),
	"WFU_UPLOADEDFILES_BARAUTOHIDE" => array( "Uploaded Files Auto-Hide on Toolbar", "string", "false", "Defines whether the Uploaded Files Toolbar (Admin Bar) menu item will be hidden when there are no new uploads. It can be 'true' or 'false'." ),
	"WFU_SHORTCODECOMPOSER_NOADMIN" => array( "Show Shortcode Composer to Non-Admins", "string", "true", "Defines whether the shortcode composer will be visible to non-admin users who can edit posts or pages. It can be 'true' or 'false'." )
);
//color definitions
$GLOBALS["WFU_GLOBALS"] += array(
	"WFU_TESTMESSAGECOLORS" => array( "Colors for Message in Test Mode", "string", "#666666,#EEEEEE,#333333", "The color triplet (text, background and border colors) of message in Test mode of the uploader shortcode." ),
	"WFU_DEFAULTMESSAGECOLORS" => array( "Defaults Message Colors", "string", "#666666,#EEEEEE,#333333", "The default color triplet (text, background and border colors) of message of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE0" => array( "State 0 Message Colors", "string", "#666666,#EEEEEE,#333333", "The color triplet (text, background and border colors) of message of upload state 0 (upload in progress with no messages) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE1" => array( "State 1 Message Colors", "string", "#F88017,#FEF2E7,#633309", "The color triplet (text, background and border colors) of message of upload state 1 (upload in progress with messages) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE2" => array( "State 2 Message Colors", "string", "#660000,#FFEEEE,#666600", "The color triplet (text, background and border colors) of message of upload state 2 (upload in progress with some files not uploaded) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE3" => array( "State 3 Message Colors", "string", "#660000,#FFEEEE,#666600", "The color triplet (text, background and border colors) of message of upload state 3 (upload in progress with no files uploaded so far) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE4" => array( "State 4 Message Colors", "string", "#006600,#EEFFEE,#006666", "The color triplet (text, background and border colors) of message of upload state 4 (all files uploaded) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE5" => array( "State 5 Message Colors", "string", "#F88017,#FEF2E7,#633309", "The color triplet (text, background and border colors) of message of upload state 5 (all files uploaded with messages) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE6" => array( "State 6 Message Colors", "string", "#660000,#FFEEEE,#666600", "The color triplet (text, background and border colors) of message of upload state 6 (some files not uploaded) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE7" => array( "State 7 Message Colors", "string", "#660000,#FFEEEE,#666600", "The color triplet (text, background and border colors) of message of upload state 7 (no files uploaded) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE8" => array( "State 8 Message Colors", "string", "#660000,#FFEEEE,#666600", "The color triplet (text, background and border colors) of message of upload state 8 (there are no files to upload) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE9" => array( "State 9 Message Colors", "string", "#666666,#EEEEEE,#333333", "The color triplet (text, background and border colors) of message of upload state 9 (test state) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE10" => array( "State 10 Message Colors", "string", "#F88017,#FEF2E7,#633309", "The color triplet (text, background and border colors) of message of upload state 10 (JSON parse error) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE11" => array( "State 11 Message Colors", "string", "#666666,#EEEEEE,#333333", "The color triplet (text, background and border colors) of message of upload state 11 (redirecting) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE12" => array( "State 12 Message Colors", "string", "#660000,#FFEEEE,#666600", "The color triplet (text, background and border colors) of message of upload state 12 (upload failed) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE13" => array( "State 13 Message Colors", "string", "#666666,#EEEEEE,#333333", "The color triplet (text, background and border colors) of message of upload state 13 (sending data) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE14" => array( "State 14 Message Colors", "string", "#006600,#EEFFEE,#006666", "The color triplet (text, background and border colors) of message of upload state 14 (data submit succeeded) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE15" => array( "State 15 Message Colors", "string", "#660000,#FFEEEE,#666600", "The color triplet (text, background and border colors) of message of upload state 15 (data submit failed) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE16" => array( "State 16 Message Colors", "string", "#666666,#EEEEEE,#333333", "The color triplet (text, background and border colors) of message of upload state 16 (cancelling upload) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE17" => array( "State 17 Message Colors", "string", "#660000,#FFEEEE,#666600", "The color triplet (text, background and border colors) of message of upload state 17 (upload cancelled) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE18" => array( "State 18 Message Colors", "string", "#006600,#EEFFEE,#006666", "The color triplet (text, background and border colors) of message of upload state 18 (upload succeeded) of the uploader shortcode." ),
	"WFU_HEADERMESSAGECOLORS_STATE19" => array( "State 19 Message Colors", "string", "#F88017,#FEF2E7,#633309", "The color triplet (text, background and border colors) of message of upload state 19 (upload completed but no files were saved due to personal data policy) of the uploader shortcode." )
);
//insert saved values to array
$envars = get_option("wfu_environment_variables", array());
foreach ( $GLOBALS["WFU_GLOBALS"] as $ind => $envar ) {
	if ( isset($envars[$ind]) ) {
		if ( $envar[1] == "integer" ) $saved = (int)$envars[$ind];
		else $saved = (string)$envars[$ind];
	}
	else $saved = $envar[2];
	array_splice($GLOBALS["WFU_GLOBALS"][$ind], 3, 0, array( $saved ));
	//add visibility
	$GLOBALS["WFU_GLOBALS"][$ind][5] = true;
}

//hide unwanted environment variables
$GLOBALS["WFU_GLOBALS"]["WFU_RELAX_CURL_VERIFY_HOST"][5] = false;
$GLOBALS["WFU_GLOBALS"]["WFU_USE_ALT_IPTANUS_SERVER"][5] = false;

/************** Constant Values ***************/
//other plugin values
DEFINE("WFU_SUCCESSMESSAGECOLOR", "green");
DEFINE("WFU_MAX_TIME_LIMIT", ini_get("max_input_time"));
DEFINE("WFU_RESPONSE_URL", WPFILEUPLOAD_DIR."wfu_response.php");
DEFINE("WFU_AJAX_URL", $siteurl."/wp-admin/admin-ajax.php");
DEFINE("WFU_SERVICES_SERVER_URL", 'https://services2.iptanus.com');
DEFINE("WFU_VERSION_SERVER_URL", WFU_SERVICES_SERVER_URL.'/wp-admin/admin-ajax.php');
DEFINE("WFU_VERSION_HASH", '9npWpXMhAQ5e6AGJ5zqbaPxLk9ePD3eSu3WKeN9p89E9wmgL2PHtrqXPzBVpStzh');
DEFINE("WFU_DOWNLOADER_URL", WPFILEUPLOAD_DIR."wfu_file_downloader.php");
DEFINE("WFU_IPTANUS_SERVER_UNREACHABLE_ARTICLE", 'https://www.iptanus.com/iptanus-services-server-unreachable-error-wfu-plugin/');
//alternative insecure server
DEFINE("WFU_SERVICES_SERVER_ALT_URL", 'http://services.iptanus.com');
DEFINE("WFU_VERSION_SERVER_ALT_URL", WFU_SERVICES_SERVER_ALT_URL.'/wp-admin/admin-ajax.php');
DEFINE("WFU_PRO_VERSION_URL", 'https://www.iptanus.com/product/wordpress-file-upload-pro/');
//define images
DEFINE("WFU_IMAGE_ADMIN_HELP", WPFILEUPLOAD_DIR.'images/help_16.png');
DEFINE("WFU_IMAGE_ADMIN_RESTOREDEFAULT", WPFILEUPLOAD_DIR.'images/restore_16.png');
DEFINE("WFU_IMAGE_ADMIN_USERDATA_ADD", WPFILEUPLOAD_DIR.'images/add_12.png');
DEFINE("WFU_IMAGE_ADMIN_USERDATA_REMOVE", WPFILEUPLOAD_DIR.'images/remove_12.png');
DEFINE("WFU_IMAGE_ADMIN_USERDATA_UP", WPFILEUPLOAD_DIR.'images/up_12.png');
DEFINE("WFU_IMAGE_ADMIN_USERDATA_DOWN", WPFILEUPLOAD_DIR.'images/down_12.png');
DEFINE("WFU_IMAGE_ADMIN_SUBFOLDER_BROWSE", WPFILEUPLOAD_DIR.'images/tree_16.gif');
DEFINE("WFU_IMAGE_ADMIN_SUBFOLDER_OK", WPFILEUPLOAD_DIR.'images/ok_12.gif');
DEFINE("WFU_IMAGE_ADMIN_SUBFOLDER_CANCEL", WPFILEUPLOAD_DIR.'images/cancel_12.gif');
DEFINE("WFU_IMAGE_ADMIN_SUBFOLDER_LOADING", WPFILEUPLOAD_DIR.'images/refresh_16.gif');
DEFINE("WFU_IMAGE_SIMPLE_PROGBAR", WPFILEUPLOAD_DIR.'images/progbar.gif');
DEFINE("WFU_IMAGE_OVERLAY_EDITOR", WPFILEUPLOAD_DIR.'images/pencil.svg');
DEFINE("WFU_IMAGE_OVERLAY_LOADING", WPFILEUPLOAD_DIR.'images/loading_icon.gif');
DEFINE("WFU_IMAGE_FILE_CANCEL", WPFILEUPLOAD_DIR.'images/cancel_16.png');
DEFINE("WFU_IMAGE_MEDIA_BUTTONS", WPFILEUPLOAD_DIR.'images/open-iconic.svg');
DEFINE("WFU_IMAGE_VERSION_COMPARISON", WPFILEUPLOAD_DIR.'images/Version Comparison.png');

/**
 * Front-End Constants Initialization
 *
 * This function initializes all constants that need to be passed to the front-
 * end scripts of the upload form.
 *
 * @since 2.1.2
 */
function wfu_set_javascript_constants() {
	$consts = array(
		"notify_testmode" => WFU_NOTIFY_TESTMODE,
		"nofilemessage" => WFU_ERROR_UPLOAD_NOFILESELECTED,
		"enable_popups" => WFU_ERROR_ENABLE_POPUPS,
		"remoteserver_noresult" => WFU_ERROR_REMOTESERVER_NORESULT,
		"message_header" => WFU_ERRORMESSAGE,
		"message_failed" => WFU_ERROR_UPLOAD_FAILED_WHILE,
		"message_cancelled" => WFU_ERROR_UPLOAD_CANCELLED,
		"message_unknown" => WFU_ERROR_UNKNOWN,
		"adminmessage_unknown" => WFU_FAILMESSAGE_DETAILS,
		"message_timelimit" => WFU_ERROR_FILE_PHP_TIME,
		"message_admin_timelimit" => WFU_ERROR_ADMIN_FILE_PHP_TIME,
		"cancel_upload_prompt" => WFU_CANCEL_UPLOAD_PROMPT,
		"file_cancelled" => WFU_ERROR_FILE_CANCELLED,
		"jsonparse_filemessage" => WFU_ERROR_JSONPARSE_FILEMESSAGE,
		"jsonparse_message" => WFU_ERROR_JSONPARSE_MESSAGE,
		"jsonparse_adminmessage" => WFU_ERROR_JSONPARSE_ADMINMESSAGE,
		"jsonparse_headermessage" => WFU_ERROR_JSONPARSE_HEADERMESSAGE,
		"jsonparse_headeradminmessage" => WFU_ERROR_JSONPARSE_HEADERADMINMESSAGE,
		"same_pluginid" => WFU_ERROR_SAME_PLUGINID,
		"webcam_video_notsupported" => WFU_ERROR_WEBCAM_VIDEO_NOTSUPPORTED,
		"webcam_video_nothingrecorded" => WFU_ERROR_WEBCAM_VIDEO_NOTHINGRECORDED,
		"default_colors" => WFU_VAR("WFU_DEFAULTMESSAGECOLORS"),
		"fail_colors" => WFU_VAR("WFU_FAILMESSAGECOLORS"),
		"max_time_limit" => WFU_MAX_TIME_LIMIT,
		"response_url" => WFU_RESPONSE_URL,
		"ajax_url" => wfu_ajaxurl(),
		"wfu_pageexit_prompt" => WFU_PAGEEXIT_PROMPT,
		"wfu_subdir_typedir" => WFU_SUBDIR_TYPEDIR,
		"wfu_uploadprogress_mode" => WFU_VAR("WFU_UPLOADPROGRESS_MODE"),
		"wfu_consent_notcompleted" => WFU_WARNING_CONSENT_NOTCOMPLETED
	);
	$consts_txt = "";
	foreach ( $consts as $key => $val )
		$consts_txt .= ( $consts_txt == "" ? "" : ";" ).wfu_plugin_encode_string($key).":".wfu_plugin_encode_string($val);

	return $consts_txt;
}

?>
