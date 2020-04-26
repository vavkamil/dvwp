<?php

/**
 * General Use Functions of Plugin
 *
 * This file contains general use functions of the plugin.
 *
 * @link /lib/wfu_functions.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 2.1.2
 */

//********************* Debug Functions ****************************************

/**
 * Hook on plugin's functions.
 *  
 * This is a very powerful function that enables almost all plugin functions to
 * be redeclared, either in whole or partially. Here is what it can do:
 *
 *   - It can execute a hook, based on the function parameters and then
 *     execute the original function.
 *   - It can execute a hook, based on the function's parameters and then
 *     return without executing the original function. This mode is like
 *     entirely redeclaring the original function.
 *   - It can execute a hook after execution of the original function.
 *   - It can redeclare the function parameters or pass new variables to the
 *     original function.
 *
 * In order to make a function redeclarable we just need to put the
 * following 'magic' code at the top of its function block:
 *
 *   $a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out);
 *   if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v;
 *   switch($a) { case 'R': return $out['output']; break; case 'D':
 *   die($out['output']); }
 *
 * Then the function can be hooked through the filter wfu_debug-{__FUNCTION__}.
 *
 * The hook function takes the same parameters as the original function, plus
 * one, which comes first and determines the behaviour of the hook function.
 *
 * This parameter is an array having three items as follows:
 *
 *   - item 'output' contains the output of the original function (if exists)
 *   - item 'result' has no meaning as input parameter but as returning one
 *   - item 'vars' has no meaning as input parameter but as returning one
 *
 * The hook function must return the same array as follows:
 *
 *   - item 'output' must contain the hook's output
 *   - item 'result' must be either 'X', 'R', or 'D' when the hook is executed
 *     at the beginning of the function, as explained below. It determines how
 *     the hook will be handled, as follows:
 *       - If 'result' is 'X' then the result of the hook function will be
 *         ignored and the original function will be executed afterwards.
 *       - If 'result' is 'R' then the original function will terminate
 *         returning the output of the hook function. So it is like having been
 *         entirely substituted by the hook function.
 *       - If 'result' is 'D' then the original function will die returning the
 *         output of the hook function. This applies to ajax handlers.
 *     In the case that the hook is executed at the end of the function, then
 *     item 'result' must always be 'R'.
 *   - item 'vars' is an associative array that contains any variables that the
 *     hook wants to pass to the original function like this:
 *         $res['output'] = array('varname1' => value1, 'varname2' => value2);
 *     Item 'vars' can be used to redeclare the function arguments and it is a
 *     workaround to handling arguments passed by reference.
 *
 * It is noted that the hook can be executed either before or after execution
 * of the original function, despite the fact that the 'magic' code is added
 * to the beginning of the function.
 *
 *  - To execute the hook before the function a global variable with name
 *    wfu_debug-{__FUNCTION__} must be declared.
 *  - To execute the hook after the function a global variable with name
 *    wfu_debug_end-{__FUNCTION__} must be declared.
 *
 * It is noted that if both of these global variables are declared, or none of
 * them then the hook will not work.
 *
 * Arguments passed by reference: When declaring the hook filter, all arguments
 * are passed by value, even if some of the original function's arguments pass
 * by reference. However no PHP warnings and errors will be generated due to
 * this difference. If the hook wants to change the value of an argument and
 * reflect this change to the original function, it is possible through item
 * 'vars' explained above. For example, if the original function passes
 * argument $var1 by reference (it is declared as &$var1 in the function
 * parameters), we cannot use the syntax $var1 = ...; inside the hook filter
 * but we can use the syntax $res['vars']['var1'] = ...; and this will result
 * $var1 in the original function to get the new value!
 *
 * @since 3.11.0
 *
 * @param string $function The function name of the original function.
 * @param array $args An array of parameters of the original function.
 * @param string $out Tt stores the output of the hook function.
 *
 * @return string Returns how the hook function will be handled ('X': hook
 *         output must be ignored, 'R': the original function must return the
 *         hook's output, 'D': the original function must die returning the
 *         hook's output).
 */
function WFU_FUNCTION_HOOK($function, $args, &$out) {
	// exit if plugin's debug mode is off or the hook has not been declared in
	// global variables;
	if ( WFU_VAR("WFU_DEBUG") != "ON" || !( isset($GLOBALS["wfu_debug-".$function]) xor isset($GLOBALS["wfu_debug_end-".$function]) ) ) return 'X';
	// exit if function name is empty or invalid
	if ( $function == "" || preg_replace("/[^0-9a-zA-Z_]/", "", $function) != $function ) return 'X';
	//if the hook has been declared in global variables with wfu_debug_end-
	//prefix then it will run at the end of the function
	if ( isset($GLOBALS["wfu_debug_end-".$function]) ) {
		$args_count = count($args);
		//if a flag (specific string) is contained in the last position of the
		//arguments list then do not re-execute the hook as this is the second
		//pass
		if ( $args_count > 0 && $args[$args_count - 1] === "wfu_debug_end-".$function."-second_pass" ) return 'X';
		else {
			//create an array of references to the function arguments and pass
			//this to call_user_func_array instead of $args; this is a
			//workaround to avoid PHP warnings when the original function passes
			//arguments by reference
			$args_byref = array();
			foreach ( $args as $key => &$arg ) $args_byref[$key] = &$arg;
			//add a flag (specific string) as the last argument in order to
			//denote that the next execution of the hook is the second pass
			array_push($args_byref, "wfu_debug_end-".$function."-second_pass");
			//call the original function and get the returned value; it will
			//contain the flag in the arguments, so the hook will not be
			//executed again and the whole script will not be put in an infinite
			//loop
			$ret = call_user_func_array($function, $args_byref);
			//pass the original function's output to the hook
			array_splice($args, 0, 0, array( array( "output" => $ret, "result" => "X", "vars" => array() ) ));
			/**
			 * Hook on a Specific Function.
			 *
			 * This filter allows to redeclare, or change the behaviour, of the
			 * original function $function.
			 *
			 * @since 3.11.0
			 *
			 * @param array $args Array of parameters of the original function.
			 */
			$res = apply_filters_ref_array("wfu_debug-".$function, $args);
			if ( !is_array($res) || !isset($res["output"]) || !isset($res["result"]) ) $res = array( "output" => $ret, "result" => "R" );
			if ( $res["result"] != 'R' ) $res["result"] = 'R';
			if ( isset($res["vars"]) && !is_array($res["vars"]) ) $res["vars"] = array();
			$out = $res;
			return $res["result"];
		}
	}
	else {
		// prepare the arguments for the hook
		array_splice($args, 0, 0, array( array( "output" => "", "result" => "X", "vars" => array() ) ));
		/** This hook is decribed above. */
		$res = apply_filters_ref_array("wfu_debug-".$function, $args);
		// exit if $res is invalid
		if ( !is_array($res) || !isset($res["output"]) || !isset($res["result"]) ) $res = array( "output" => "", "result" => "X" );
		if ( $res["result"] != 'X' && $res["result"] != 'R' && $res["result"] != 'D' ) $res["result"] = 'X';
		if ( isset($res["vars"]) && !is_array($res["vars"]) ) $res["vars"] = array();
		$out = $res;
		// if result is 'X' then the caller must ignore the hook
		// if result is 'R' then the caller must return the hook's output
		// if result is 'D' then the caller must die returning the hook's output
		return $res["result"];
	}
}

//********************* String Functions ***************************************

/**
 * Sanitize Filename.
 *
 * This function sanitizes filename so that it is compatible with most file
 * systems. Invalid non-latin characters will be converted into dashes.
 *
 * @since 2.1.2
 *
 * @param string $filename The file name.
 *
 * @return string The sanitized file name.
 */
function wfu_upload_plugin_clean($filename) {
	$clean = sanitize_file_name($filename);
	if ( WFU_VAR("WFU_SANITIZE_FILENAME_MODE") != "loose" ) {
		$name = wfu_filename($clean);
		$ext = wfu_fileext($clean);
		if ( WFU_VAR("WFU_SANITIZE_FILENAME_DOTS") == "true" ) $name_search = array ( '@[^a-zA-Z0-9_]@' );
		else $name_search = array ( '@[^a-zA-Z0-9._]@' );
		$ext_search = array ( '@[^a-zA-Z0-9._]@' );	 
		$replace = array ( '-' );
		$clean_name =  preg_replace($name_search, $replace, remove_accents($name));
		$clean_ext =  preg_replace($ext_search, $replace, remove_accents($ext));
		$clean = $clean_name.".".$clean_ext;
	}

	return $clean;
}

/**
 * Wildcard Conversion Callback.
 *
 * This function is a callback used in a preg_replace_callback() function to
 * convert wildcard syntax to natural expression.
 *
 * @since 3.9.0
 *
 * @global array $wfu_preg_replace_callback_var An array with matches.
 *
 * @param array $matches An array of matches of preg_replace_callback().
 *
 * @return string The result of the callback processing the matches.
 */
function _wildcard_to_preg_preg_replace_callback($matches) {
    global $wfu_preg_replace_callback_var;
    array_push($wfu_preg_replace_callback_var, $matches[0]);
    $key = count($wfu_preg_replace_callback_var) - 1;
    return "[".$key."]";
}

/**
 * Wildcard To Natural Expression Conversion.
 *
 * This function converts wildcard syntax of a pattern to natural expression.
 *
 * @since 2.1.2
 *
 * @global array $wfu_preg_replace_callback_var An array with matches.
 *
 * @param string $pattern The pattern to convert.
 * @param bool $strict Optional. Strict matching. If true, dot symbols (.) will
 *        not be matched.
 *
 * @return The converted natural expression pattern.
 */
function wfu_upload_plugin_wildcard_to_preg($pattern, $strict = false) {
	global $wfu_preg_replace_callback_var;
	$wfu_preg_replace_callback_var = array();
	$pattern = preg_replace_callback("/\[(.*?)\]/", "_wildcard_to_preg_preg_replace_callback", $pattern);
	if ( !$strict ) $pattern = '/^' . str_replace(array('\*', '\?', '\[', '\]'), array('.*', '.', '[', ']'), preg_quote($pattern)) . '$/is';
	else $pattern = '/^' . str_replace(array('\*', '\?', '\[', '\]'), array('[^.]*', '.', '[', ']'), preg_quote($pattern)) . '$/is';
	foreach ($wfu_preg_replace_callback_var as $key => $match)
		$pattern = str_replace("[".$key."]", $match, $pattern);
	return $pattern;
}

/**
 * Wildcard To MySQL Natural Expression Conversion.
 *
 * This function converts wildcard syntax of a pattern to MySQL natural
 * expression.
 *
 * @since 3.2.1
 *
 * @redeclarable
 *
 * @param string $pattern The pattern to convert.
 *
 * @return The converted MySQL natural expression pattern.
 */
function wfu_upload_plugin_wildcard_to_mysqlregexp($pattern) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	if ( substr($pattern, 0, 6) == "regex:" ) return str_replace("\\", "\\\\", substr($pattern, 6));
	else return str_replace("\\", "\\\\", '^'.str_replace(array('\*', '\?', '\[', '\]'), array('.*', '.', '[', ']'), preg_quote($pattern)).'$');
}

/**
 * Match String With Pattern.
 *
 * This function checks if a specific string matches with a pattern.
 *
 * @since 2.1.2
 *
 * @param string $pattern The pattern to match.
 * @param string $str The string to match.
 * @param bool $strict Defines whether strict mode will be used. In strict mode
 *        dot symbols (.) are not considered as normal characters and are not
 *        matched with preg * symbol.
 *
 * @return bool True if there is a match, false otherwise.
 */
function wfu_upload_plugin_wildcard_match($pattern, $str, $strict = false) {
	$pattern = wfu_upload_plugin_wildcard_to_preg($pattern, $strict);
	return preg_match($pattern, $str);
}

/**
 * Convert String to Hex.
 *
 * This function converts every character of a string into a 2-byte hex
 * representation.
 *
 * @since 2.1.2
 *
 * @param string $string The string to convert.
 *
 * @return string The converted hex string.
 */
function wfu_plugin_encode_string($string) {
	$array = unpack('H*', $string);
	return $array[1];

	$array = unpack('C*', $string);
	$new_string = "";	
	for ($i = 1; $i <= count($array); $i ++) {
		$new_string .= sprintf("%02X", $array[$i]);
	}
	return $new_string;
}

/**
 * Convert Hex to String.
 *
 * This function converts a hex string into a normal ASCII string.
 *
 * @since 2.1.2
 *
 * @param string $string The hex string to convert.
 *
 * @return string The converted ASCII string.
 */
function wfu_plugin_decode_string($string) {
	return pack('H*', $string);

	$new_string = "";	
	for ($i = 0; $i < strlen($string); $i += 2 ) {
		$new_string .= sprintf("%c", hexdec(substr($string, $i ,2)));
	}
	return $new_string;
}

/**
 * Create a Random String.
 *
 * This function creates a random string composing of latin letters and numbers.
 *
 * @since 2.1.2
 *
 * @param integer $len The length of the string.
 * @param bool $hex True if a hex string must be generated.
 *
 * @return string The random string.
 */
function wfu_create_random_string($len, $hex = false) {
	$base1 = 'ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
	$base2 = 'ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
	if ( $hex ) {
		$base1 = 'abcdef123456789';
		$base2 = 'abcdef0123456789';
	}
	$max1 = strlen($base1) - 1;
	$max2 = strlen($base2) - 1;
	$activatecode = '';
	if ( WFU_VAR("WFU_ALTERNATIVE_RANDOMIZER") != "true" )
		mt_srand((double)microtime()*1000000);
	else mt_srand((double)substr(uniqid("", true), 15));
	$is_first = true;
	while (strlen($activatecode) < $len) {
		if ( $is_first ) {
			$activatecode .= $base1{mt_rand(0, $max1)};
			$is_first = false;
		}
		else $activatecode .= $base2{mt_rand(0, $max2)};
	}
	return $activatecode;
}

/**
 * Join Two or More Strings.
 *
 * This function joins one or more strings. The strings are passed in the
 * function as 2nd, 3rd, 4rth and so on parameters.
 *
 * @since 2.1.2
 *
 * @param string $delimeter The delimeter to use to join the strings.
 *
 * @return string The resulted joined string.
 */
function wfu_join_strings($delimeter) {
	$arr = func_get_args();
	unset($arr[0]);
	foreach ($arr as $key => $item)
		if ( $item == "" ) unset($arr[$key]);
	return join($delimeter, $arr);
}

/**
 * Create a String of Zeros.
 *
 * This function creates a string filled with zeros. It is designed to be fast
 * even when the length of the string is large.
 *
 * @since 2.1.2
 *
 * @param integer $size The size of the string.
 *
 * @return string The resulted string.
 */
function wfu_create_string($size) {
	$piece = str_repeat("0", 1024);
	$str = "";
	$reps = $size / 1024;
	$rem = $size - 1024 * $reps;
	for ( $i = 0; $i < $reps; $i++ ) $str .= $piece;
	$str .= substr($piece, 0, $rem);
	return $str;
}

/**
 * Prepare String for HTML Output.
 *
 * This function converts newline characters into <br> tags and tabs/spaces into
 * &nbsp; entities, so that they can be property shown in HTML output.
 *
 * @since 2.7.1
 *
 * @param string $output The string to be sent to output.
 *
 * @return string The converted HTML ready string.
 */
function wfu_html_output($output) {
	$output = str_replace(array("\r\n", "\r", "\n"), "<br/>", $output);
	return str_replace(array("\t", " "), "&nbsp;", $output);
}

/**
 * Sanitize a Code.
 *
 * This function sanitizes a code. A code must only contain latin letters and
 * numbers.
 *
 * @since 3.0.0
 *
 * @param string $code The code to sanitize.
 *
 * @return string The sanitized code.
 */
function wfu_sanitize_code($code) {
	return preg_replace("/[^A-Za-z0-9]/", "", $code);
}

/**
 * Sanitize an Integer.
 *
 * This function sanitizes an integer (passed as string). An integer must only
 * contain numbers, plus (+) and minus (-) symbols.
 *
 * @since 3.1.0
 *
 * @param string $code The integer to sanitize passed as string.
 *
 * @return string The sanitized integer returned as string.
 */
function wfu_sanitize_int($code) {
	return preg_replace("/[^0-9+\-]/", "", $code);
}

/**
 * Sanitize a Float.
 *
 * This function sanitizes a float (passed as string). A float must only contain
 * numbers, plus (+), minus (-), dot (.) and comma (,) symbols.
 *
 * @since 4.3.3
 *
 * @param string $code The float to sanitize passed as string.
 *
 * @return string The sanitized float returned as string.
 */
function wfu_sanitize_float($code) {
	return preg_replace("/[^0-9+\-\.,]/", "", $code);
}

/**
 * Sanitize a Color Value.
 *
 * This function sanitizes a color value. A color value must only contain
 * characters a-f or A-F, numbers, number sign (#) and comma (,) symbols.
 *
 * @since 4.3.3
 *
 * @param string $code The color value to sanitize.
 *
 * @return string The sanitized color value.
 */
function wfu_sanitize_colors($code) {
	return preg_replace("/[^A-Fa-f0-9#,]/", "", $code);
}

/**
 * Sanitize a Tag.
 *
 * This function sanitizes a tag. A tag must only contain latin characters,
 * numbers and underscore (_) symbols.
 *
 * @since 3.1.0
 *
 * @param string $code The tag to sanitize.
 *
 * @return string The sanitized tag.
 */
function wfu_sanitize_tag($code) {
	return preg_replace("/[^A-Za-z0-9_]/", "", $code);
}

/**
 * Sanitize a URL.
 *
 * This function sanitizes a URL.
 *
 * @since 3.11.0
 *
 * @param string $url The URL to sanitize.
 *
 * @return string The sanitized URL.
 */
function wfu_sanitize_url($url) {
	return filter_var(strip_tags($url), FILTER_SANITIZE_URL);
}

/**
 * Sanitize a List of URL.
 *
 * This function sanitizes a list of URLs.
 *
 * @since 3.11.0
 *
 * @param string $urls The URLs to sanitize.
 * @param string $separator The delimeter character of the URLs.
 *
 * @return string The sanitized URLs.
 */
function wfu_sanitize_urls($urls, $separator) {
	$urls_arr = explode($separator, $urls);
	foreach( $urls_arr as &$url ) $url = wfu_sanitize_url($url);
	return implode($separator, $urls_arr);
}

/**
 * Sanitize a Shortcode.
 *
 * This function sanitizes a shortcode, that is sanitizes all its attributes.
 *
 * @since 4.3.3
 *
 * @param string $shortcode The shortcode to sanitize.
 * @param string $shortcode_tag The shortcode tag.
 *
 * @return string The sanitized shortcode.
 */
function wfu_sanitize_shortcode($shortcode, $shortcode_tag) {
	$attrs = wfu_shortcode_string_to_array($shortcode);
	$sanitized_attrs = wfu_sanitize_shortcode_array($attrs, $shortcode_tag);
	//reconstruct sanitized shortcode string from array
	$sanitized_shortcode = "";
	foreach ( $sanitized_attrs as $attr => $value )
		$sanitized_shortcode .= ( $sanitized_shortcode == "" ? "" : " " ).$attr.'="'.$value.'"';
	
	return $sanitized_shortcode;
}

/**
 * Sanitize Shortcode Attributes.
 *
 * This function sanitizes an array of shortcode attributes.
 *
 * @since 4.5.1
 *
 * @param array $attrs An array of shortcode attributes to sanitize.
 * @param string $shortcode_tag The shortcode tag.
 *
 * @return array The sanitized array of shortcode attributes.
 */
function wfu_sanitize_shortcode_array($attrs, $shortcode_tag) {
	$sanitized_attrs = array();
	if ( $shortcode_tag == 'wordpress_file_upload' ) $defs = wfu_attribute_definitions();
	else $defs = wfu_browser_attribute_definitions();
	// get validator types for defs
	$def_validators = array();
	foreach ( $defs as $def ) $def_validators[$def['attribute']] = $def['validator'];
	// sanitize each attribute
	foreach ( $attrs as $attr => $value ) {
		//first sanitize the attribute name
		$sanitized = sanitize_text_field($attr);
		//continue only for attributes that sanitization did not crop any
		//characters
		if ( $sanitized == $attr && $attr != "" ) {
			//flatten attributes that have many occurencies
			$flat = preg_replace("/^(.*?)[0-9]*$/", "$1", $attr);
			//get validator type
			$validator = "text";
			if ( isset($def_validators[$flat]) ) $validator = $def_validators[$flat];
			//sanitize value based on validator type
			$new_value = $value;
			switch( $validator ) {
				case "text":
					$new_value = wp_strip_all_tags($value);
					break;
				case "integer":
					$new_value = wfu_sanitize_int($value);
					break;
				case "float":
					$new_value = wfu_sanitize_float($value);
					break;
				case "path":
					$new_value = wp_strip_all_tags($value);
					break;
				case "link":
					$new_value = wp_strip_all_tags($value);
					break;
				case "emailheaders":
					if ( strpos(strtolower($value), "<script") !== false ) $new_value = "";
					break;
				case "emailsubject":
					if ( strpos(strtolower($value), "<script") !== false ) $new_value = "";
					break;
				case "emailbody":
					if ( strpos(strtolower($value), "<script") !== false ) $new_value = "";
					break;
				case "colors":
					$new_value = wfu_sanitize_colors($value);
					break;
				case "css":
					$new_value = wp_strip_all_tags($value);
					break;
				case "datetime":
					$new_value = wp_strip_all_tags($value);
					break;
				case "pattern":
					if ( substr_count($value, "'") > 0 && substr_count($value, "'") > substr_count($value, "\\'") ) $new_value = "";
					break;
				default:
					$new_value = wp_strip_all_tags($value);
			}
			/**
			 * Custom Shortcode Sanitization.
			 *
			 * This filter allows custom actions to change the sanitization
			 * result of shortcode attributes.
			 *
			 * @since 4.3.3
			 *
			 * @param string $new_value New sanitized value of the attribute.
			 * @param string $attr The attribute name.
			 * @param string $validator The type of attribute used to determine
			 *        the type of validator to use.
			 * @param string $value The initial value of the attribute.
			 */
			$new_value = apply_filters("_wfu_sanitize_shortcode", $new_value, $attr, $validator, $value);
			$sanitized_attrs[$attr] = $new_value;
		}
	}
	
	return $sanitized_attrs;
}

/**
 * Escape a Variable.
 *
 * This function escapes (adds backslashes before characters that need to be
 * escaped) a variable, even if it is an array of unlimited depth.
 *
 * @since 3.3.0
 *
 * @param mixed $value The variable to be escaped.
 *
 * @return mixed The escaped variable.
 */
function wfu_slash( $value ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			if ( is_array( $v ) ) {
				$value[$k] = wfu_slash( $v );
			}
			else {
				$value[$k] = addslashes( $v );
			}
		}
	}
	else {
		$value = addslashes( $value );
	}

	return $value;
}

/**
 * Generate a Global Short-Life Token.
 *
 * This function generates a short-life token that is stored in Wordpress
 * Options and has a global scope (is accessible by all users).
 *
 * @since 3.5.0
 *
 * @param integer $timeout The life of the token in seconds.
 *
 * @return string The token.
 */
function wfu_generate_global_short_token($timeout) {
	$token = wfu_create_random_string(16);
	$expire = time() + (int)$timeout;
	update_option('wfu_gst_'.$token, $expire);
	return $token;
}

/**
 * Verify a Global Short-Life Token.
 *
 * This function verifies that a global short-life token exists and it not
 * expired. After verification the token is removed.
 *
 * @since 3.5.0
 *
 * @param string $token The token to verify.
 *
 * @return bool True if verification was successful, false otherwise.
 */
function wfu_verify_global_short_token($token) {
	$timeout = get_option('wfu_gst_'.$token);
	if ( $timeout === false ) return false;
	delete_option('wfu_gst_'.$token);
	return ( $timeout > time() );
}

/**
 * Generate a User Short-Life Token.
 *
 * This function generates a short-life token that is stored in a user's User
 * Space and has a user scope (is accessible only by this user).
 *
 * @since 4.9.0
 *
 * @param integer $timeout The life of the token in seconds.
 *
 * @return string The token.
 */
function wfu_generate_user_short_token($timeout) {
	$token = wfu_create_random_string(16);
	$expire = time() + (int)$timeout;
	WFU_USVAR_store('wfu_ust_'.$token, $expire);
	return $token;
}

/**
 * Verify a User Short-Life Token.
 *
 * This function verifies that a user short-life token exists and it not
 * expired. After verification the token is removed.
 *
 * @since 4.9.0
 *
 * @param string $token The token to verify.
 *
 * @return bool True if verification was successful, false otherwise.
 */
function wfu_verify_user_short_token($token) {
	if ( !WFU_USVAR_exists('wfu_ust_'.$token) ) return false;
	$timeout = WFU_USVAR('wfu_ust_'.$token);
	WFU_USVAR_unset('wfu_ust_'.$token);
	return ( $timeout > time() );
}

//********************* Array Functions ****************************************

/**
 * Encode Array to String.
 *
 * This function converts an array to a JSON string and then encodes it to its
 * hex representation.
 *
 * @since 2.1.2
 *
 * @param array $arr The array to encode.
 *
 * @return string The encoded hex string.
 */
function wfu_encode_array_to_string($arr) {
	$arr_str = json_encode($arr);
	$arr_str = wfu_plugin_encode_string($arr_str);
	return $arr_str;
}

/**
 * Decode Array from String.
 *
 * This function converts a hex string to its ASCII representation, which is a
 * JSON string and then decodes it to an array.
 *
 * @since 2.1.2
 *
 * @param string $arr_str The encoded hex string to decode.
 *
 * @return array The decoded array.
 */
function wfu_decode_array_from_string($arr_str) {
	$arr_str = wfu_plugin_decode_string($arr_str);
	$arr = json_decode($arr_str, true);
	return $arr;
}

/**
 * Decode HTML Entities in Array.
 *
 * This function decodes HTML entities found in array values into their special
 * characters. It is useful when reading a shortcode array.
 *
 * @since 2.1.2
 *
 * @param array $source The source array.
 *
 * @return array The decoded array.
 */
function wfu_plugin_parse_array($source) {
	$keys = array_keys($source);
	$new_arr = array();
	for ($i = 0; $i < count($keys); $i ++) 
		$new_arr[$keys[$i]] = wp_specialchars_decode($source[$keys[$i]]);
	return $new_arr;
}

/**
 * Encode Special Characters in Array.
 *
 * This function converts special characters found in array values into HTML
 * entities.
 *
 * @since 2.1.2
 *
 * @param array $arr The source array.
 *
 * @return array The encoded array.
 */
function wfu_safe_array($arr) {
	return array_map("htmlspecialchars", $arr);
}

/**
 * Remove Nulls from Array.
 *
 * This function removes null items from array.
 *
 * @since 2.1.2
 *
 * @param array $arr The source array.
 *
 * @return array The cleaned array.
 */
function wfu_array_remove_nulls(&$arr) {
	foreach ( $arr as $key => $arri )
		if ( $arri == null )
			array_splice($arr, $key, 1);
}

/**
 * Sanitize a Variable.
 *
 * This function sanitizes (converts special characters into HTML entities) a
 * variable. If the variable is an array it will sanitize all elements
 * recursively regardless of array depth. If the variable is not of an accepted
 * type then its type will be returned.
 *
 * @since 2.4.4
 *
 * @param mixed $var The variable to sanitize.
 *
 * @return mixed The sanitized variable.
 */
function wfu_sanitize($var) {
	$typ = gettype($var);
	if ( $typ == "boolean" || $typ == "integer" || $typ == "double" || $typ == "resource" || $typ == "NULL" )
		return $var;
	elseif ( $typ == "string" )
		return htmlspecialchars($var);
	elseif ( $typ == "array" || $typ == "object" ) {
		foreach ( $var as &$item ) $item = wfu_sanitize($item);
		return $var;
	}
	else
		return $typ;
}

/**
 * Mask a Shortcode.
 *
 * This function is part of a process to safely parse a shortcode string into an
 * associative array. It replaces all attribute values by tokens, so that it is
 * easier and safer for the process to separate the attributes.
 *
 * @since 2.2.1
 *
 * @param string $contents The shortcode.
 * @param string $token The token that replaces the shortcode attribute values.
 *
 * @return array An array of converted attributes.
 */
function _wfu_preg_replace_callback_alt($contents, $token) {
	$in_block = false;
	$prev_pos = 0;
	$new_contents = '';
	$ret['items'] = array();
	$ret['tokens'] = array();
	$ii = 0;
	while ( ($pos = strpos($contents, '"', $prev_pos)) !== false ) {
		if ( !$in_block ) {
			$new_contents .= substr($contents, $prev_pos, $pos - $prev_pos + 1);
			$in_block = true;
		}
		else {
			$ret['items'][$ii] = substr($contents, $prev_pos, $pos - $prev_pos);
			$ret['tokens'][$ii] = $token.sprintf('%03d', $ii);
			$new_contents .= $token.sprintf('%03d', $ii).'"';
			$ii ++;
			$in_block = false;
		}
		$prev_pos = $pos + 1;
	}
	if ( $in_block ) {
		$ret['items'][$ii] = substr($contents, $prev_pos);
		$ret['tokens'][$ii] = $token.sprintf('%03d', $ii);
		$new_contents .= $token.sprintf('%03d', $ii).'"';
	}
	else
		$new_contents .= substr($contents, $prev_pos);
	$ret['contents'] = $new_contents;
	return $ret;
}

/**
 * Parse a Shortcode.
 *
 * This function safely parses a shortcode string into an associative array.
 *
 * @since 2.1.3
 *
 * @param string $shortcode The shortcode.
 *
 * @return array The parsed shortcode as an associative array of attributes.
 */
function wfu_shortcode_string_to_array($shortcode) {
	$i = 0;
	$m1 = array();
	$m2 = array();
	//for some reason preg_replace_callback does not work in all cases, so it has been replaced by a similar custom inline routine
//	$mm = preg_replace_callback('/"([^"]*)"/', function ($matches) use(&$i, &$m1, &$m2) {array_push($m1, $matches[1]); array_push($m2, "attr".$i); return "attr".$i++;}, $shortcode);
	$ret = _wfu_preg_replace_callback_alt($shortcode, "attr");
	$mm = $ret['contents'];
	$m1 = $ret['items'];
	$m2 = $ret['tokens'];
	$arr = explode(" ", $mm);
	$attrs = array();
	foreach ( $arr as $attr ) {
		if ( trim($attr) != "" ) {
			$attr_arr = explode("=", $attr, 2);
			$key = "";
			if ( count($attr_arr) > 0 ) $key = $attr_arr[0];
			$val = "";
			if ( count($attr_arr) > 1 ) $val = $attr_arr[1];
			if ( trim($key) != "" ) $attrs[trim($key)] = str_replace('"', '', $val);
		}
	}
	$attrs2 = str_replace($m2, $m1, $attrs);
	return $attrs2;
}

/**
 * Compare Two Strings in Ascending Order.
 *
 * This function returns the comparison result of two strings. It is part of an
 * array sorting mechanism.
 *
 * @since 3.8.5
 *
 * @param string $a The first string.
 * @param string $b The second string.
 *
 * @return int Returns < 0 if a is less than b; > 0 if a is greater than b
 *         and 0 if they are equal.
 */
function wfu_array_sort_function_string_asc($a, $b) {
	return strcmp(strtolower($a), strtolower($b));
}

/**
 * Compare Two Strings Having a Second Property in Ascending Order.
 *
 * This function returns the comparison result of two strings. If the strings
 * are equal then comparison will be done based on a second property (id0) of
 * the strings, so that 0 is never returned. It is part of an array sorting
 * mechanism. 
 *
 * @since 3.8.5
 *
 * @param array $a The first string. It is passed as an array. 'value' item of
 *        the array is the string. 'id0' item is the second property.
 * @param array $b The second string. It is passed as an array. 'value' item of
 *        the array is the string. 'id0' item is the second property.
 *
 * @return int Returns < 0 if a is less than b; > 0 if a is greater.
 */
function wfu_array_sort_function_string_asc_with_id0($a, $b) {
	$cmp = strcmp(strtolower($a["value"]), strtolower($b["value"]));
	if ( $cmp == 0 ) $cmp = ( (int)$a["id0"] < (int)$b["id0"] ? -1 : 1 );
	return $cmp;
}

/**
 * Compare Two Strings in Descending Order.
 *
 * This function returns the negstive of the comparison result of two strings.
 * It is part of an array sorting mechanism.
 *
 * @since 3.8.5
 *
 * @param string $a The first string.
 * @param string $b The second string.
 *
 * @return int Returns > 0 if a is less than b; < 0 if a is greater than b
 *         and 0 if they are equal.
 */
function wfu_array_sort_function_string_desc($a, $b) {
	return -strcmp(strtolower($a), strtolower($b));
}

/**
 * Compare Two Strings Having a Second Property in Descending Order.
 *
 * This function returns the negative of the comparison result of two strings.
 * If the strings are equal then comparison will be done based on a second
 * property (id0) of the strings, so that 0 is never returned. It is part of an
 * array sorting mechanism. 
 *
 * @since 3.8.5
 *
 * @param array $a The first string. It is passed as an array. 'value' item of
 *        the array is the string. 'id0' item is the second property.
 * @param array $b The second string. It is passed as an array. 'value' item of
 *        the array is the string. 'id0' item is the second property.
 *
 * @return int Returns > 0 if a is less than b; < 0 if a is greater.
 */
function wfu_array_sort_function_string_desc_with_id0($a, $b) {
	$cmp = strcmp(strtolower($a["value"]), strtolower($b["value"]));
	if ( $cmp == 0 ) $cmp = ( (int)$a["id0"] < (int)$b["id0"] ? -1 : 1 );
	return -$cmp;
}

/**
 * Compare Two Numbers in Ascending Order.
 *
 * This function returns the comparison result of two numbers. It is part of an
 * array sorting mechanism.
 *
 * @since 3.8.5
 *
 * @param int|float|double $a The first number.
 * @param int|float|double $b The second number.
 *
 * @return int Returns -1 if a is less than b; 1 if a is greater than b
 *         and 0 if they are equal.
 */
function wfu_array_sort_function_numeric_asc($a, $b) {
	$aa = (double)$a;
	$bb = (double)$b;
	if ( $aa < $bb ) return -1;
	elseif ( $aa > $bb ) return 1;
	else return 0;
}

/**
 * Compare Two Numbers Having a Second Property in Ascending Order.
 *
 * This function returns the comparison result of two numbers. If the numbers
 * are equal then comparison will be done based on a second property (id0) of
 * the numbers, so that 0 is never returned. It is part of an array sorting
 * mechanism. 
 *
 * @since 3.8.5
 *
 * @param array $a The first number. It is passed as an array. 'value' item of
 *        the array is the number. 'id0' item is the second property.
 * @param array $b The second number. It is passed as an array. 'value' item of
 *        the array is the number. 'id0' item is the second property.
 *
 * @return int Returns -1 if a is less than b; 1 if a is greater.
 */
function wfu_array_sort_function_numeric_asc_with_id0($a, $b) {
	$aa = (double)$a["value"];
	$bb = (double)$b["value"];
	if ( $aa < $bb ) return -1;
	elseif ( $aa > $bb ) return 1;
	elseif ( (int)$a["id0"] < (int)$b["id0"] ) return -1;
	else return 1;
}

/**
 * Compare Two Numbers in Descending Order.
 *
 * This function returns the negstive of the comparison result of two numbers.
 * It is part of an array sorting mechanism.
 *
 * @since 3.8.5
 *
 * @param int|float|number $a The first number.
 * @param int|float|number $b The second number.
 *
 * @return int Returns 1 if a is less than b; -1 if a is greater than b
 *         and 0 if they are equal.
 */
function wfu_array_sort_function_numeric_desc($a, $b) {
	$aa = (double)$a;
	$bb = (double)$b;
	if ( $aa > $bb ) return -1;
	elseif ( $aa < $bb ) return 1;
	else return 0;
}

/**
 * Compare Two Numbers Having a Second Property in Descending Order.
 *
 * This function returns the negative of the comparison result of two numbers.
 * If the numbers are equal then comparison will be done based on a second
 * property (id0) of the numbers, so that 0 is never returned. It is part of an
 * array sorting mechanism. 
 *
 * @since 3.8.5
 *
 * @param array $a The first number. It is passed as an array. 'value' item of
 *        the array is the number. 'id0' item is the second property.
 * @param array $b The second number. It is passed as an array. 'value' item of
 *        the array is the number. 'id0' item is the second property.
 *
 * @return int Returns 1 if a is less than b; -1 if a is greater.
 */
function wfu_array_sort_function_numeric_desc_with_id0($a, $b) {
	$aa = (double)$a["value"];
	$bb = (double)$b["value"];
	if ( $aa > $bb ) return -1;
	elseif ( $aa < $bb ) return 1;
	elseif ( (int)$a["id0"] > (int)$b["id0"] ) return -1;
	else return 1;
}

/**
 * Sort an Array Based on Key.
 *
 * This function sorts an array based on a key. It is used to sort a tabular
 * list based on a column. Every item of the array is another associative array
 * representing a row of the table. The key of every item is the column of the
 * table.
 *
 * @since 2.2.1
 *
 * @param array $array. The array to sort.
 * @param string $on. The sorting column name. If it is preceeded by 's:' it
 *        will be sorted as string. If it is preceeded by 'n:' it will be sorted
 *        as numeric.
 * @param int $order Optional. The sorting order. It can be SORT_ASC or
 *        SORT_DESC.
 * @param bool $with_id0 Optional. A secord property will be used for sorting.
 *
 * @return array The sorted array.
 */
function wfu_array_sort($array, $on, $order = SORT_ASC, $with_id0 = false) {
    $new_array = array();
    $sortable_array = array();
	
	$pos = strpos($on, ":");
	if ( $pos !== false ) {
		$sorttype = substr($on, $pos + 1);
		if ( $sorttype == "" ) $sorttype = "s";
		$on = substr($on, 0, $pos);
	}
	else $sorttype = "s";

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = ( $with_id0 ? array( "id0" => $v["id0"], "value" => $v2 ) : $v2 );
                    }
                }
            } else {
                $sortable_array[$k] = $v;
				$with_id0 = false;
            }
        }

		uasort($sortable_array, "wfu_array_sort_function_".( $sorttype == "n" ? "numeric" : "string" )."_".( $order == SORT_ASC ? "asc" : "desc" ).( $with_id0 ? "_with_id0" : "" ));

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

/**
 * Output Array Contents.
 *
 * This function echoes array contents to show properly in a front-end page.
 *
 * @since 3.4.0
 *
 * @param array $arr. The array to echo.
 */
function wfu_echo_array($arr) {
	if ( !is_array($arr) ) return;
	echo '<pre>'.print_r($arr, true).'</pre>';
}

/**
 * Minify Code.
 *
 * This function minifies a piece of code. It is used to minify inline code of
 * the plugin. It supports minification of Javascript or CSS code.
 *
 * @since 4.2.0
 *
 * @param string $lang. The code language. It can be 'JS' or 'CSS'.
 * @param string $code. The code to minify.
 *
 * @return array An array holding minification result. Item 'result' is true if
 *         minification was successful and false otherwise. Item 'minified_code'
 *         holds the minified code.
 */
function wfu_minify_code($lang, $code) {
	$ret = array( "result" => false, "minified_code" => "" );
	$php_version = preg_replace("/-.*/", "", phpversion());
	$unsupported = false;
	$ret = wfu_compare_versions($php_version, '5.3.0');
	$unsupported = ( $ret['status'] && $ret['result'] == 'lower' );
	if ( !$unsupported ) {
		$path = ABSWPFILEUPLOAD_DIR;
		if ( !class_exists('MatthiasMullie\Minify\Minify') ) {
			include_once $path.'vendor/minifier/minify/src/Minify.php';
			include_once $path.'vendor/minifier/minify/src/CSS.php';
			include_once $path.'vendor/minifier/minify/src/JS.php';
			include_once $path.'vendor/minifier/minify/src/Exception.php';
			include_once $path.'vendor/minifier/minify/src/Exceptions/BasicException.php';
			include_once $path.'vendor/minifier/minify/src/Exceptions/FileImportException.php';
			include_once $path.'vendor/minifier/minify/src/Exceptions/IOException.php';
		}
		if ( !class_exists('MatthiasMullie\PathConverter\Converter') ) {
			include_once $path.'vendor/minifier/path-converter/src/ConverterInterface.php';
			include_once $path.'vendor/minifier/path-converter/src/Converter.php';
		}
		$minifier = null;
		eval('$minifier = new MatthiasMullie\Minify\\'.strtoupper($lang).'($code);');
		if ( $minifier !== null ) {
			$ret["result"] = true;
			$ret["minified_code"] = $minifier->minify();
		}
	}
	
	return $ret;
}

/**
 * Prepare CSS Code for Output.
 *
 * This function prepares CSS code for HTML output. It minifies the code if
 * necessary and encloses it in <style> tags.
 *
 * @since 4.0.0
 *
 * @param string $css. The CSS code to output.
 *
 * @return string The resulted HTML code.
 */
function wfu_css_to_HTML($css) {
	if ( WFU_VAR("WFU_MINIFY_INLINE_CSS") == "true" ) {
		$ret = wfu_minify_code("CSS", $css);
		if ( $ret["result"] ) $css = $ret["minified_code"];
	}
	$echo_str = "\n\t".'<style>';
	$echo_str .= "\n".$css;
	$echo_str .= "\n\t".'</style>';

	return $echo_str;
}

/**
 * Prepare Javascript Code for Output.
 *
 * This function prepares Javascript code for HTML output. It minifies the code
 * if necessary and encloses it in <script> tags.
 *
 * @since 4.0.0
 *
 * @param string $js. The Javascript code to output.
 *
 * @return string The resulted HTML code.
 */
function wfu_js_to_HTML($js) {
	if ( WFU_VAR("WFU_MINIFY_INLINE_JS") == "true" ) {
		$ret = wfu_minify_code("JS", $js);
		if ( $ret["result"] ) $js = $ret["minified_code"];
	}
	$echo_str = '<script type="text/javascript">';
	$echo_str .= "\n".$js;
	$echo_str .= "\n".'</script>';

	return $echo_str;
}

/**
 * Generate Basic Inline Javascript Loader Functions.
 *
 * This function returns the initialization code of the basic inline JS loader
 * functions:
 *
 *   wfu_js_decode_obj: This JS function generates an object from its string
 *   representation.
 *
 *   wfu_run_js: This JS function calls other JS functions. It is used to run
 *   inline functions safely. Inline functions use objects, like GlobalData,
 *   which initialize after Javascript files of the plugin have been loaded.
 *   Usually these files are declared at the header of a page and load before
 *   the inline code. So objects like GlobalData have been initialized and
 *   inline functions can run without errors. However sometimes Javascript files
 *   are declared at the footer, or handled by cache plugins and load after the
 *   inline code. In these cases wfu_run_js will not run the inline functions
 *   immediately. It will put them in a JS Bank, so that they run safely after
 *   the Javascript files have been loaded.
 *
 * @since 4.2.0
 *
 * @return string The HTML code of the inline Javascript loader functions.
 */
function wfu_init_run_js_script() {
//	$script = 'if (typeof wfu_js_decode_obj == "undefined") function wfu_js_decode_obj(obj_str) { var obj = null; if (obj_str == "window") obj = window; else { var match = obj_str.match(new RegExp(\'GlobalData(\\\\.(WFU|WFUB)\\\\[(.*?)\\\\](\\\\.(.*))?)?$\')); if (match) { obj = GlobalData; if (match[3]) obj = obj[match[2]][match[3]]; if (match[5]) obj = obj[match[5]]; } } return obj; }';
	$script = 'if (typeof wfu_js_decode_obj == "undefined") function wfu_js_decode_obj(obj_str) { var obj = null; if (obj_str == "window") obj = window; else { var dbs = String.fromCharCode(92); var match = obj_str.match(new RegExp(\'GlobalData(\' + dbs + \'.(WFU|WFUB)\' + dbs + \'[(.*?)\' + dbs + \'](\' + dbs + \'.(.*))?)?$\')); if (match) { obj = GlobalData; if (match[3]) obj = obj[match[2]][match[3]]; if (match[5]) obj = obj[match[5]]; } } return obj; }';
	$script .= "\n".'if (typeof wfu_run_js == "undefined") function wfu_run_js(obj_str, func) { if (typeof GlobalData == "undefined") { if (typeof window.WFU_JS_BANK == "undefined") WFU_JS_BANK = []; WFU_JS_BANK.push({obj_str: obj_str, func: func}) } else { var obj = wfu_js_decode_obj(obj_str); if (obj) obj[func].call(obj); } }';
	return wfu_js_to_HTML($script);
}

/**
 * Convert PHP Array to JS Object.
 *
 * This function converts an associative PHP array into a Javascript object.
 *
 * @since 4.0.0
 *
 * @param array $arr. The associative PHP array to convert.
 *
 * @return string The converted Javascript object as a string.
 */
function wfu_PHP_array_to_JS_object($arr) {
	$ret = "";
	foreach ( $arr as $prop => $value ) {
		if ( is_string($value) ) $ret .= ( $ret == "" ? "" : ", " )."$prop: \"$value\"";
		elseif ( is_numeric($value) ) $ret .= ( $ret == "" ? "" : ", " )."$prop: $value";
		elseif ( is_bool($value) ) $ret .= ( $ret == "" ? "" : ", " )."$prop: ".( $value ? "true" : "false" );
	}
	return ( $ret == "" ? "{ }" : "{ $ret }" );
}

/**
 * Convert PHP Array to URL GET Params.
 *
 * This function converts an associative PHP array into GET parameters to add in
 * a URL.
 *
 * @since 4.9.0
 *
 * @param array $arr. The associative PHP array to convert.
 *
 * @return string The converted GET parameters.
 */
function wfu_array_to_GET_params($arr) {
	$str = "";
	foreach ( $arr as $key => $var )
		$str .= ( $str == "" ? "" : "&" ).$key."=".$var;
	
	return $str;
}

//********************* Shortcode Attribute Functions **************************

/**
 * Insert a Category in a List of Categories.
 *
 * This function inserts a new category in a list of categories.
 *
 * @since 4.1.0
 *
 * @param array $categories. The list of categories.
 * @param string $before_category. Insert the new category before this one.
 * @param string $new_category. The new category to insert.
 *
 * @return array The updated list of categories.
 */
function wfu_insert_category($categories, $before_category, $new_category) {
	if ( $before_category == "" ) $index = count($categories);
	else {
		$index = array_search($before_category, array_keys($categories));
		if ( $index === false ) $index = count($categories);
	}
	
	return array_merge(array_slice($categories, 0, $index), $new_category, array_slice($categories, $index));
}

/**
 * Insert new Attributes in a List of Attributes.
 *
 * This function inserts one or more attributes in a list of attributes.
 *
 * @since 4.1.0
 *
 * @param array $attributes. The list of attributes.
 * @param string $in_category. Insert the new attribute in this category.
 * @param string $in_subcategory. Insert the new attribute in this subcategory.
 * @param string $position. Position of the new attribute. It can be 'first' or
 *        'last'.
 * @param array $new_attributes. The new attributes to insert.
 *
 * @return array The updated list of attributes.
 */
function wfu_insert_attributes($attributes, $in_category, $in_subcategory, $position, $new_attributes) {
	$index = -1;
	if ( $in_category == "" ) {
		if ( $position == "first" ) $index = 0;
		elseif ( $position == "last" ) $index = count($attributes);
	}
	else {
		foreach ( $attributes as $pos => $attribute ) {
			$match = ( $attribute["category"] == $in_category );
			if ( $in_subcategory != "" ) $match = $match && ( $attribute["subcategory"] == $in_subcategory );
			if ( $match ) {
				if ( $position == "first" ) {
					$index = $pos;
					break;
				}
				elseif ( $position == "last" ) {
					$index = $pos + 1;
				}
			}
		}
	}
	if ( $index > -1 ) array_splice($attributes, $index, 0, $new_attributes);
	
	return $attributes;
}

//********************* Plugin Options Functions *******************************

/**
 * Get Server Environment.
 *
 * This function gets the server environment, whether it is 32 or 64 bit.
 *
 * @since 2.6.0
 *
 * @redeclarable
 *
 * @return string The server environment, '32bit' or '64bit'.
 */
function wfu_get_server_environment() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$php_env = '';
	if ( PHP_INT_SIZE == 4 ) $php_env = '32bit';
	elseif ( PHP_INT_SIZE == 8 ) $php_env = '64bit';
	else {
		$int = "9223372036854775807";
		$int = intval($int);
		if ($int == 9223372036854775807) $php_env = '64bit';
		elseif ($int == 2147483647) $php_env = '32bit';
	}

	return $php_env;
}

/**
 * Get AJAX URL.
 *
 * This function gets the URL of admin-ajax.php for AJAX requests.
 *
 * @since 3.7.2
 *
 * @redeclarable
 *
 * @return string The full URL for AJAX requests.
 */
function wfu_ajaxurl() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	return ( $plugin_options['admindomain'] == 'siteurl' || $plugin_options['admindomain'] == '' ? site_url("wp-admin/admin-ajax.php") : ( $plugin_options['admindomain'] == 'adminurl' ? admin_url("admin-ajax.php") : home_url("wp-admin/admin-ajax.php") ) );
}

/**
 * Get Plugin Environment Variable Value.
 *
 * This function gets the value of a plugin's environment variable.
 *
 * @since 3.7.1
 *
 * @param string $varname The name of the environment variable.
 *
 * @return mixed The value of the environment variable.
 */
function WFU_VAR($varname) {
	if ( !isset($GLOBALS["WFU_GLOBALS"][$varname]) ) return false;
	if ( $GLOBALS["WFU_GLOBALS"][$varname][5] ) return $GLOBALS["WFU_GLOBALS"][$varname][3];
	//in case the environment variable is hidden then return the default value
	else return $GLOBALS["WFU_GLOBALS"][$varname][2];
}

/**
 * Get Plugin Version.
 *
 * This function gets the plugin's version.
 *
 * @since 2.4.6
 *
 * @return string The plugin's version.
 */
function wfu_get_plugin_version() {
	$plugin_data = get_plugin_data(WPFILEUPLOAD_PLUGINFILE);
	return $plugin_data['Version'];
}

/**
 * Get Plugin's Latest Version.
 *
 * This function gets the plugin's latest version from Iptanus Services Server.
 *
 * @since 2.4.6
 *
 * @redeclarable
 *
 * @return string The plugin's latest version.
 */
function wfu_get_latest_version() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$postfields = array();
	$postfields['action'] = 'wfuca_check_latest_version_free';
	$postfields['version_hash'] = WFU_VERSION_HASH;
	$url = ( $plugin_options["altserver"] == "1" && trim(WFU_VAR("WFU_ALT_IPTANUS_SERVER")) != "" ? ( trim(WFU_VAR("WFU_ALT_VERSION_SERVER")) != "" ? trim(WFU_VAR("WFU_ALT_VERSION_SERVER")) : trim(WFU_VAR("WFU_ALT_IPTANUS_SERVER")).'/wp-admin/admin-ajax.php' ) : WFU_VERSION_SERVER_URL );
	$result = null;
	if ( WFU_VAR("WFU_DISABLE_VERSION_CHECK") != "true" )
		$result = wfu_post_request($url, $postfields, false, false, 10);
	return $result;
}

/**
 * Compare Current and Latest Version.
 *
 * This function compares curent version with latest one.
 *
 * @since 2.4.6
 *
 * @param string $current The curent plugin version.
 * @param string $latest The latest plugin version.
 *
 * @return string The comparison result. It can have the following values:
 *                'equal': both versions are equal.
 *                'lower': current version is lower than latest.
 *                'current version invalid' current version is invalid.
 *                'latest version invalid' latest version is invalid.
 */
function wfu_compare_versions($current, $latest) {
	$ret['status'] = true;
	$ret['custom'] = false;
	$ret['result'] = 'equal';
	$res = preg_match('/^([0-9]*)\.([0-9]*)\.([0-9]*)(.*)/', $current, $cur_data);
	if ( !$res || count($cur_data) < 5 )
		return array( 'status' => false, 'custom' => false, 'result' => 'current version invalid' );
	if ( $cur_data[1] == '' || $cur_data[2] == '' || $cur_data[3] == '' )
		return array( 'status' => false, 'custom' => false, 'result' => 'current version invalid' );
	$custom = ( $cur_data[4] != '' );
	$res = preg_match('/^([0-9]*)\.([0-9]*)\.([0-9]*)/', $latest, $lat_data);
	if ( !$res || count($lat_data) < 4 )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'latest version invalid' );
	if ( $lat_data[1] == '' || $lat_data[2] == '' || $lat_data[3] == '' )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'latest version invalid' );
	if ( intval($cur_data[1]) < intval($lat_data[1]) )
		return array( 'status' => true, 'custom' => $custom, 'result' => 'lower' );
	elseif ( intval($cur_data[1]) > intval($lat_data[1]) )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'current version invalid' );
	if ( intval($cur_data[2]) < intval($lat_data[2]) )
		return array( 'status' => true, 'custom' => $custom, 'result' => 'lower' );
	elseif ( intval($cur_data[2]) > intval($lat_data[2]) )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'current version invalid' );
	if ( intval($cur_data[3]) < intval($lat_data[3]) )
		return array( 'status' => true, 'custom' => $custom, 'result' => 'lower' );
	elseif ( intval($cur_data[3]) > intval($lat_data[3]) )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'current version invalid' );
	return array( 'status' => true, 'custom' => $custom, 'result' => 'equal' );	
}

//********************* File / Directory Functions *****************************

/**
 * Get Root Path of Website.
 *
 * This function gets the root (absolute) path of the website. If it cannot be
 * retrieved then content path is returned.
 *
 * @since 4.0.0
 *
 * @return string The absolute path of the website.
 */
function wfu_abspath() {
	$path = WP_CONTENT_DIR;
	//remove trailing slash if exists
	if ( substr($path, -1) == '/' ) $path = substr($path, 0, -1);
	$pos = strrpos($path, '/');
	//to find abspath we go one dir up from content path
	if ( $pos !== false ) $path = substr($path, 0, $pos + 1);
	//else if we cannot go up we stay at content path adding a trailing slash
	else $path .= '/';
	
	return $path;
}

/**
 * Extract Extension from Filename.
 *
 * This function extracts the extension part from filename.
 *
 * @since 3.8.0
 *
 * @param string $basename The filename to extract the extension from.
 * @param bool $with_dot Optional. If true the dot symbol will be included in
 *        the extension.
 *
 * @return string The extracted extension.
 */
function wfu_fileext($basename, $with_dot = false) {
	if ( $with_dot ) return preg_replace("/^.*?(\.[^.]*)?$/", "$1", $basename);
	else return preg_replace("/^.*?(\.([^.]*))?$/", "$2", $basename);
}

/**
 * Extract Name Part from Filename.
 *
 * This function extracts the name part from filename without the extension.
 *
 * @since 3.8.0
 *
 * @param string $basename The filename to extract the name part from.
 *
 * @return string The extracted name part.
 */
function wfu_filename($basename) {
	return preg_replace("/^(.*?)(\.[^.]*)?$/", "$1", $basename);
}

/**
 * Extract Filename From Path.
 *
 * This function extracts the filename from path.
 *
 * @since 2.6.0
 *
 * @param string $path The path to extract the filename from.
 *
 * @return string The extracted filename.
 */
function wfu_basename($path) {
	if ( !$path || $path == "" ) return "";
	return preg_replace('/.*(\\\\|\\/)/', '', $path);
}

/**
 * Extract Dir From Path.
 *
 * This function extracts the dir part from path without the filename.
 *
 * @since 2.7.1
 *
 * @param string $path The path to extract the dir part from.
 *
 * @return string The extracted dir part.
 */
function wfu_basedir($path) {
	if ( !$path || $path == "" ) return "";
	return substr($path, 0, strlen($path) - strlen(wfu_basename($path)));
}

/**
 * Convert Absolute Path to Relative.
 *
 * This function converts an absolute path to relative one by removing the
 * root path of the website. If the path points to an FTP location then no
 * conversion happens. If the path is outside the root, then 'abs:' is appended
 * to the path.
 *
 * @since 3.1.0
 *
 * @param string $path The absolute path.
 *
 * @return string The relative path.
 */
function wfu_path_abs2rel($path) {
	$abspath_notrailing_slash = substr(wfu_abspath(), 0, -1);
	if ( substr($path, 0, 6) == 'ftp://' || substr($path, 0, 7) == 'ftps://' || substr($path, 0, 7) == 'sftp://' ) return $path;
	else {
		$is_outside_root = ( substr($path, 0, strlen($abspath_notrailing_slash)) != $abspath_notrailing_slash );
		if ( $is_outside_root ) return 'abs:'.$path;
//		else return str_replace($abspath_notrailing_slash, "", $path);
		else return substr($path, strlen($abspath_notrailing_slash));
	}
}

/**
 * Convert Relative Path to Absolute.
 *
 * This function converts a relative path to absolute one by prepending the root
 * path of the website.
 *
 * @since 3.1.0
 *
 * @param string $path The relative path.
 *
 * @return string The absolute path.
 */
function wfu_path_rel2abs($path) {
	if ( substr($path, 0, 1) == "/" ) $path = substr($path, 1);
	if ( substr($path, 0, 6) == 'ftp://' || substr($path, 0, 7) == 'ftps://' || substr($path, 0, 7) == 'sftp://' ) return $path;
	elseif ( substr($path, 0, 4) == 'abs:' ) return substr($path, 4);
	else return wfu_abspath().$path;
}

/**
 * Delete an Uploaded File.
 *
 * This function deletes an uploaded file from the website. It marks the file as
 * deleted in the database. It also deletes any linked attachments or
 * thumbnails.
 *
 * @since 4.2.0
 *
 * @redeclarable
 *
 * @param string $filepath The path of the file to delete.
 * @param int $userid The ID of the user who performs the deletion.
 *
 * @return bool True if the deletion succeeded, false otherwise.
 */
function wfu_delete_file_execute($filepath, $userid) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$filedata = wfu_get_filedata($filepath);
	$retid = wfu_log_action('delete', $filepath, $userid, '', 0, 0, '', null);
	$result = unlink($filepath);
	if ( !$result ) wfu_revert_log_action($retid);
	else {
		//delete linked attachment if exists and it is allowed to be deleted
		if ( $filedata != null && isset($filedata["media"]) && WFU_VAR("WFU_UPDATE_MEDIA_ON_DELETE") == "true" )
			wp_delete_attachment( $filedata["media"]["attach_id"] );
	}
	
	return $result;
}

/**
 * Extract FTP Information From ftpinfo Attribute.
 *
 * This function extracts FTP information from ftpinfo attribute of the uploader
 * shortcode.
 *
 * @since 4.11.2
 *
 * @param string $ftpdata The ftpinfo attribute.
 *
 * @return array {
 *         An array of extracted FTP information.
 *
 *         @type bool $error Defines whether there was an error during
 *               extraction of FTP information.
 *         @type array $data {
 *               The extracted FTP information.
 *
 *               @type string $username The FTP login username.
 *               @type string $password The FTP login password.
 *               @type string $ftpdomain The FTP domain.
 *               @type string $port The FTP port.
 *               @type bool $sftp Defines whether sFTP connection will be used.
 *         }
 * }
 */
function wfu_decode_ftpinfo($ftpdata) {
	$ret = array(
		"error" => true,
		"data" => array(
			"username" => "",
			"password" => "",
			"ftpdomain" => "",
			"port" => "",
			"sftp" => false
		)
	);
	$ftpdata_flat =  str_replace(array('\\:', '\\@'), array('\\_', '\\_'), $ftpdata);
	$pos1 = strpos($ftpdata_flat, ":");
	$pos2 = strpos($ftpdata_flat, "@");
	if ( $pos1 && $pos2 && $pos2 > $pos1 ) {
		$ret["error"] = false;
		$ret["data"]["username"] = str_replace(array('\\\\:', '\\\\@'), array(':', '@'), substr($ftpdata, 0, $pos1));
		$ret["data"]["password"] = str_replace(array('\\\\:', '\\\\@'), array(':', '@'), substr($ftpdata, $pos1 + 1, $pos2 - $pos1 - 1));
		$ftp_host = substr($ftpdata, $pos2 + 1);
		$ret["data"]["ftpdomain"] = preg_replace("/:.*/", "", $ftp_host);
		if ( trim($ret["data"]["ftpdomain"]) == "" ) $ret["error"] = true;
		$ftp_port = preg_replace("/^[^:]*:?/", "", $ftp_host);
		if ( substr($ftp_port, 0, 1) == "s" ) {
			$ret["data"]["sftp"] = true;
			$ftp_port = substr($ftp_port, 1);
		}
		$ret["data"]["port"] = $ftp_port;
	}
	elseif ( $pos2 ) {
		$ret["error"] = false;
		$ret["data"]["username"] = str_replace(array('\\\\:', '\\\\@'), array(':', '@'), substr($ftpdata, 0, $pos2));
		$ftp_host = substr($ftpdata, $pos2 + 1);
		$ret["data"]["ftpdomain"] = preg_replace("/:.*/", "", $ftp_host);
		if ( trim($ret["data"]["ftpdomain"]) == "" ) $ret["error"] = true;
		$ftp_port = preg_replace("/^[^:]*:?/", "", $ftp_host);
		if ( substr($ftp_port, 0, 1) == "s" ) {
			$ret["data"]["sftp"] = true;
			$ftp_port = substr($ftp_port, 1);
		}
		$ret["data"]["port"] = $ftp_port;
	}
	elseif ( $pos1 ) {
		$ret["error"] = true;
		$ret["data"]["username"] = str_replace(array('\\\\:', '\\\\@'), array(':', '@'), substr($ftpdata, 0, $pos1));
		$ret["data"]["password"] = str_replace(array('\\\\:', '\\\\@'), array(':', '@'),substr($ftpdata, $pos1 + 1));
	}
	else {
		$ret["error"] = true;
		$ret["data"]["username"] = str_replace(array('\\\\:', '\\\\@'), array(':', '@'), $ftpdata);
	}
	
	return $ret;
}

/**
 * Get Full Upload Path.
 *
 * This function calculates the full upload path of an uploader shortcode from
 * its attributes.
 *
 * @since 2.1.2
 *
 * @param array $params The shortcode attributes.
 *
 * @return string The full uplod path.
 */
function wfu_upload_plugin_full_path( $params ) {
	$path = $params["uploadpath"];
	if ( $params["accessmethod"] == 'ftp' && $params["ftpinfo"] != '' && $params["useftpdomain"] == "true" ) {
		//remove parent folder symbol (..) in path so that the path does not go outside host
		$ftpdata = str_replace('..', '', $params["ftpinfo"]);
		$ftpinfo = wfu_decode_ftpinfo($ftpdata);
		if ( !$ftpinfo["error"] ) {
			$data = $ftpinfo["data"];
			//extract relative FTP path
			$ftp_port = $data["port"];
			if ( $data["sftp"] && $ftp_port == "" ) $ftp_port = "22";
			$ftp_host = $data["ftpdomain"].( $ftp_port != "" ? ":".$ftp_port : "" );
			$ftp_username = str_replace('@', '%40', $data["username"]);   //if username contains @ character then convert it to %40
			$ftp_password = str_replace('@', '%40', $data["password"]);   //if password contains @ character then convert it to %40
			$start_folder = ( $data["sftp"] ? 's' : '' ).'ftp://'.$ftp_username.':'.$ftp_password."@".$ftp_host.'/';
		}
		else $start_folder = 'ftp://'.$params["ftpinfo"].'/';
	}
	else $start_folder = WP_CONTENT_DIR.'/';
	if ($path) {
		if ( $path == ".." || substr($path, 0, 3) == "../" ) {
			$start_folder = wfu_abspath();
			$path = substr($path, 2, strlen($path) - 2);
		}
		//remove additional parent folder symbols (..) in path so that the path does not go outside the $start_folder
		$path =  str_replace('..', '', $path);
		if ( substr($path, 0, 1) == "/" ) $path = substr($path, 1, strlen($path) - 1);
		if ( substr($path, -1, 1) == "/" ) $path = substr($path, 0, strlen($path) - 1);
		$full_upload_path = $start_folder;
		if ( $path != "" ) $full_upload_path .= $path.'/';
	}
	else {
		$full_upload_path = $start_folder;
	}
	return $full_upload_path;
}

/**
 * Get Full Upload Path.
 *
 * This function calculates the full upload path of an uploader shortcode from
 * its attributes.
 *
 * @since 2.1.2
 *
 * @param array $params The shortcode attributes.
 *
 * @return string The full upload path.
 */
function wfu_upload_plugin_directory( $path ) {
	$dirparts = explode("/", $path);
	return $dirparts[count($dirparts) - 1];
}

/**
 * Extract Additional Data From Complex Path.
 *
 * This function is used to extract sort, filename or filter information from
 * a complex path. A complex path is used by the plugin to pass additional
 * information between requests. In a complex path sort, filename and filter 
 * information are stored as [[-sort]], {{filename}} and ((filter)).
 *
 * @since 2.2.1
 *
 * @param string $path The complex path.
 *
 * @return array {
 *         Additional data extracted from path.
 *
 *         @type string $path The clean path.
 *         @type string $sort Sort information of a file list.
 *         @type string $file Filename of a specific file.
 *         @type string $filter Filter information of a file list.
 * }
 */
function wfu_extract_sortdata_from_path($path) {
	$ret['path'] = $path;
	$ret['sort'] = "";
	$ret['file'] = "";
	$ret['filter'] = "";
	//extract sort info
	$pos1 = strpos($path, '[[');
	$pos2 = strpos($path, ']]');
	if ( $pos1 !== false && $pos2 !== false )
		if ( $pos2 > $pos1 ) {
			$ret['sort'] = substr($path, $pos1 + 2, $pos2 - $pos1 - 2);
			$ret['path'] = str_replace('[['.$ret['sort'].']]', '', $path);
		}
	//extract filename info
	$pos1 = strpos($path, '{{');
	$pos2 = strpos($path, '}}');
	if ( $pos1 !== false && $pos2 !== false )
		if ( $pos2 > $pos1 ) {
			$ret['file'] = substr($path, $pos1 + 2, $pos2 - $pos1 - 2);
			$ret['path'] = str_replace('{{'.$ret['file'].'}}', '', $path);
		}
	//extract filter info
	$pos1 = strpos($path, '((');
	$pos2 = strpos($path, '))');
	if ( $pos1 !== false && $pos2 !== false )
		if ( $pos2 > $pos1 ) {
			$ret['filter'] = substr($path, $pos1 + 2, $pos2 - $pos1 - 2);
			$ret['path'] = str_replace('(('.$ret['filter'].'))', '', $path);
		}
	return $ret;
}

/**
 * Flatten A Complex Path.
 *
 * This function returns only the clean path from a complex path.
 *
 * @since 2.2.1
 *
 * @param string $path The complex path.
 *
 * @return string The clean path.
 */
function wfu_flatten_path($path) {
	$ret = wfu_extract_sortdata_from_path($path);
	return $ret['path'];
}

/**
 * Delete a Directory Recursively.
 *
 * This function deletes a directory recursively.
 *
 * @since 2.2.1
 *
 * @param string $dir The directory to delete.
 *
 * @return bool True if the deletion suceeded, false otherwise.
 */
function wfu_delTree($dir) {
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		is_dir("$dir/$file") ? wfu_delTree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}

/**
 * Get Top-Level Subdirectory Tree of a Directory.
 *
 * This function retrieves the first-level subdirectories of a directory.
 *
 * @since 2.7.1
 *
 * @param string $dir The directory to scan.
 *
 * @return array An array of subdirectories.
 */
function wfu_getTree($dir) {
	$tree = array();
	$files = @scandir($dir);
	if ( !is_array($files) ) $files = array();
	$files = array_diff($files, array('.','..'));
	foreach ($files as $file) {
		if ( is_dir("$dir/$file") ) array_push($tree, $file);
	}
	return $tree;
}
/**
 * Parse List of Folders From subfoldertree Attribute.
 *
 * This function calculates the list of subfolders of a subfoldertree attribute
 * of an uploader shortcode.
 *
 * @since 2.4.1
 *
 * @redeclarable
 *
 * @param string $subfoldertree The subfoldertree attribute of the shortcode.
 *
 * @return array {
 *         An array of folders.
 *
 *         @type array $path An array of folder paths.
 *         @type array $label An array of folder labels.
 *         @type array $level An array of folder levels.
 *         @type array $default An array defining which item is default.
 * }
 */
function wfu_parse_folderlist($subfoldertree) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$ret['path'] = array();
	$ret['label'] = array();
	$ret['level'] = array();
	$ret['default'] = array();

	if ( substr($subfoldertree, 0, 4) == "auto" ) return $ret;
	$subfolders = explode(",", $subfoldertree);
	if ( count($subfolders) == 0 ) return $ret;
	if ( count($subfolders) == 1 && trim($subfolders[0]) == "" ) return $ret;
	$dir_levels = array ( "root" );
	$prev_level = 0;
	$level0_count = 0;
	$default = -1;
	foreach ($subfolders as $subfolder) {
		$subfolder = trim($subfolder);			
		$star_count = 0;
		$start_spaces = "";
		$is_default = false;
		//check for folder level
		while ( $star_count < strlen($subfolder) ) {
			if ( substr($subfolder, $star_count, 1) == "*" ) {
				$star_count ++;
				$start_spaces .= "&nbsp;&nbsp;&nbsp;";
			}
			else break;
		}
		if ( $star_count - $prev_level <= 1 && ( $star_count > 0 || $level0_count == 0 ) ) {
			$subfolder = substr($subfolder, $star_count, strlen($subfolder) - $star_count);
			// check for default value
			if ( substr($subfolder, 0, 1) == '&' ) {
				$subfolder = substr($subfolder, 1);
				$is_default = true;
			}
			//split item in folder path and folder name
			$subfolder_items = explode('/', $subfolder);
			if ( count($subfolder_items) > 1 && $subfolder_items[1] != "" ) {
				$subfolder_dir = $subfolder_items[0];
				$subfolder_label = $subfolder_items[1];
			}
			else {
				$subfolder_dir = $subfolder;
				$subfolder_label = $subfolder;
			}
			if ( $subfolder_dir != "" ) {
				// set is_default flag to true only for the first default item
				if ( $is_default && $default == -1 ) $default = count($ret['path']);
				else $is_default = false;
				// set flag that root folder has been included (so that it is not included it again)
				if ( $star_count == 0 ) $level0_count = 1;
				if ( count($dir_levels) > $star_count ) $dir_levels[$star_count] = $subfolder_dir;
				else array_push($dir_levels, $subfolder_dir);
				$subfolder_path = "";
				for ( $i_count = 1; $i_count <= $star_count; $i_count++) {
					$subfolder_path .= $dir_levels[$i_count].'/';
				}
				array_push($ret['path'], $subfolder_path);
				array_push($ret['label'], $subfolder_label);
				array_push($ret['level'], $star_count);
				array_push($ret['default'], $is_default);
				$prev_level = $star_count;
			}
		}
	}

	return $ret;
}

/**
 * Calculate Size of File.
 *
 * This function calculates the size of a file. It uses a complex approach for
 * calculating very big files (over 2GB) even in 32bit server environments.
 *
 * @since 2.6.0
 *
 * @param string $filepath The file path.
 *
 * @return The file size.
 */
function wfu_filesize($filepath) {
	$fp = fopen($filepath, 'r');
	$pos = 0;
	if ($fp) {
		$size = 1073741824;
		fseek($fp, 0, SEEK_SET);
		while ($size > 1) {
			fseek($fp, $size, SEEK_CUR);
			if (fgetc($fp) === false) {
				fseek($fp, -$size, SEEK_CUR);
				$size = (int)($size / 2);
			}
			else {
				fseek($fp, -1, SEEK_CUR);
				$pos += $size;
			}
		}
		while (fgetc($fp) !== false)  $pos++;
		fclose($fp);
	}

    return $pos;
}

/**
 * Alternative Calculate Size of File.
 *
 * This function calculates the size of a file following an alternative method.
 * Again, it uses a complex approach for calculating very big files (over 2GB)
 * even in 32bit server environments.
 *
 * @since 2.6.0
 *
 * @param string $filepath The file path.
 *
 * @return The file size.
 */
function wfu_filesize2($filepath) {
    $fp = fopen($filepath, 'r');
    $return = false;
    if (is_resource($fp)) {
      if (PHP_INT_SIZE < 8) {
        // 32bit
        if (0 === fseek($fp, 0, SEEK_END)) {
          $return = 0.0;
          $step = 0x7FFFFFFF;
          while ($step > 0) {
            if (0 === fseek($fp, - $step, SEEK_CUR)) {
              $return += floatval($step);
            } else {
              $step >>= 1;
            }
          }
        }
      } elseif (0 === fseek($fp, 0, SEEK_END)) {
        // 64bit
        $return = ftell($fp);
      }
      fclose($fp);
    }
    return $return;
}

/**
 * Set Read Position on File.
 *
 * This function sets read position on a file. It uses a complex approach for
 * allowing correct positioning of very big files (over 2GB) even in 32bit
 * server environments.
 *
 * @since 2.6.0
 *
 * @param string $fp The file handle of the file.
 * @param int $pos The read position to set.
 * @param int $first Optional. If non-zero then position will start from
 *        beginning of file.
 */
function wfu_fseek($fp, $pos, $first = 1) {
	// set to 0 pos initially, one-time
	if ( $first ) fseek($fp, 0, SEEK_SET);

	// get pos float value
	$pos = floatval($pos);

	// within limits, use normal fseek
	if ( $pos <= PHP_INT_MAX )
		fseek($fp, $pos, SEEK_CUR);
	// out of limits, use recursive fseek
	else {
		fseek($fp, PHP_INT_MAX, SEEK_CUR);
		$pos -= PHP_INT_MAX;
		wfu_fseek($fp, $pos, 0);
	}
}

/**
 * Alternative Set Read Position on File.
 *
 * This function sets read position on a file following an alternative method.
 * Again, tt uses a complex approach for allowing correct positioning of very
 * big files (over 2GB) even in 32bit server environments.
 *
 * @since 2.6.0
 *
 * @param string $fp The file handle of the file.
 * @param int $pos The read position to set.
 *
 * @return int Upon success, returns 0 otherwise returns -1.
 */
function wfu_fseek2($fp, $pos) {
	$pos = floatval($pos);
	if ( $pos <= PHP_INT_MAX ) {
		return fseek($fp, $pos, SEEK_SET);
	}
	else {
		$fsize = wfu_filesize2($filepath);
		$opp = $fsize - $pos;
		if ( 0 === ($ans = fseek($fp, 0, SEEK_END)) ) {
			$maxstep = 0x7FFFFFFF;
			$step = $opp;
			if ( $step > $maxstep ) $step = $maxstep;
			while ($step > 0) {
				if ( 0 === ($ans = fseek($fp, - $step, SEEK_CUR)) ) {
					$opp -= floatval($step);
				}
				else {
					$maxstep >>= 1;
				}
				$step = $opp;
				if ( $step > $maxstep ) $step = $maxstep;
			}
		}
	}
	return $ans;
}

/**
 * Write Message to Debug Log.
 *
 * This function appends a message to the plugin's debug log file. This file is
 * located at /wp-content/debug_log.txt.
 *
 * @since 2.5.5
 *
 * @param string $message The message to log.
 */
function wfu_debug_log($message) {
	$logpath = WP_CONTENT_DIR.'/debug_log.txt';
	file_put_contents($logpath, $message, FILE_APPEND);
}

/**
 * Write Object Contents to Debug Log.
 *
 * This function appends the contents of an object to the plugin's debug log
 * file.
 *
 * @since 4.10.0
 *
 * @param mixed $obj The object to log.
 */
function wfu_debug_log_obj($obj) {
	wfu_debug_log(print_r($obj, true));
}

/**
 * Store Filepath to Safe.
 *
 * This function stores a file path into the current user's User Space and
 * returns a unique code corresponding to the file path. This process is used to
 * protect file paths from being exposed when needing to pass them as HTTP
 * request parameters.
 *
 * @since 3.0.0
 *
 * @param string $path The file path.
 *
 * @return The unique code coresponding to the file path.
 */
function wfu_safe_store_filepath($path) {
	$code = wfu_create_random_string(16);
	$safe_storage = ( WFU_USVAR_exists('wfu_filepath_safe_storage') ? WFU_USVAR('wfu_filepath_safe_storage') : array() );
	$safe_storage[$code] = $path;
	WFU_USVAR_store('wfu_filepath_safe_storage', $safe_storage);
	return $code;
}

/**
 * Retrieve Filepath from Safe.
 *
 * This function retrieves a file path, previously stored in current user's User
 * Space, based on its corresponding unique code.
 *
 * @since 3.0.0
 *
 * @param string $code The unique code.
 *
 * @return The file path coresponding to the code.
 */
function wfu_get_filepath_from_safe($code) {
	//sanitize $code
	$code = wfu_sanitize_code($code);
	if ( $code == "" ) return false;
	//return filepath from session variable, if exists
	if ( !WFU_USVAR_exists('wfu_filepath_safe_storage') ) return false;
	$safe_storage = WFU_USVAR('wfu_filepath_safe_storage');
	if ( !isset($safe_storage[$code]) ) return false;
	return $safe_storage[$code];
}

/**
 * Check if File Extension is Restricted.
 *
 * This function checks if the extension of a file name is restricted. It also
 * checks for double extensions. This function is not used anymore.
 *
 * @since 3.0.0
 * @deprecated 3.9.0 Use wfu_file_extension_blacklisted()
 * @see wfu_file_extension_blacklisted()
 *
 * @param string $filename The file name to check.
 *
 * @return bool True if extension is restricted, false otherwise.
 */
function wfu_file_extension_restricted($filename) {
	return ( 
		substr($filename, -4) == ".php" ||
		substr($filename, -3) == ".js" ||
		substr($filename, -4) == ".pht" ||
		substr($filename, -5) == ".php3" ||
		substr($filename, -5) == ".php4" ||
		substr($filename, -5) == ".php5" ||
		substr($filename, -6) == ".phtml" ||
		substr($filename, -4) == ".htm" ||
		substr($filename, -5) == ".html" ||
		substr($filename, -9) == ".htaccess" ||
		strpos($filename, ".php.") !== false ||
		strpos($filename, ".js.") !== false ||
		strpos($filename, ".pht.") !== false ||
		strpos($filename, ".php3.") !== false ||
		strpos($filename, ".php4.") !== false ||
		strpos($filename, ".php5.") !== false ||
		strpos($filename, ".phtml.") !== false ||
		strpos($filename, ".htm.") !== false ||
		strpos($filename, ".html.") !== false ||
		strpos($filename, ".htaccess.") !== false
	);
}

/**
 * Convert Time to Human-Readable Format.
 *
 * This function converts a time, given in integer format, into a human-readable
 * one providing number of days, hours, minutes and seconds.
 *
 * @since 4.0.0
 *
 * @param int $time The time to convert.
 *
 * @return string The time in human-readable format.
 */
function wfu_human_time($time) {
	$time = (int)$time;
	$days = (int)($time/86400);
	$time -= $days * 86400;
	$hours = (int)($time/3600);
	$time -= $hours * 3600;
	$minutes = (int)($time/60);
	$secs = $time - $minutes * 60;
	$human_time = ( $days > 0 ? $days."d" : "" ).( $hours > 0 ? $hours."h" : "" ).( $minutes > 0 ? $minutes."m" : "" ).( $secs > 0 ? $secs."s" : "" );
	if ( $human_time == "" ) $human_time == "0s";
	return $human_time;
}

/**
 * Convert File Size to Human-Readable Format.
 *
 * This function converts a file size, given in bytes, into a human-readable
 * format providing number of GBs, MBs, KBs and bytes.
 *
 * @since 3.1.0
 *
 * @param int $size The file size in bytes.
 * @param string $unit Optional. The size unit to use. It can be GB, MB, KB. If
 *        it is omitted then it will be calculated automatically.
 *
 * @return string The file size in human-readable format.
 */
function wfu_human_filesize($size, $unit = "") {
	if ( ( !$unit && $size >= 1<<30 ) || $unit == "GB" )
		return number_format($size / (1<<30), 2)."GB";
	if( ( !$unit && $size >= 1<<20 ) || $unit == "MB" )
		return number_format($size / (1<<20), 2)."MB";
	if( ( !$unit && $size >= 1<<10 ) || $unit == "KB" )
		return number_format($size / (1<<10), 2)."KB";
	return number_format($size)." bytes";
}

/**
 * Check if File Exists Including Chunks.
 *
 * This function checks if a file exists. It will also return true if chunks of
 * a file still uploading exist.
 *
 * @since 4.12.0
 *
 * @param int $path The file path to check.
 *
 * @return bool True if file exists, false otherwise.
 */
function wfu_file_exists_extended($path) {
	if ( wfu_file_exists($path) ) return true;
	
	return false;
}

/**
 * Check if File Exists.
 *
 * This function checks if a file exists. It is an extension to the original
 * PHP file_exists() function to take special actions in cases where the file
 * is stored in an sFTP location or perhaps in other external locations (cloud
 * services, WebDAV etc.).
 *
 * For the moment this functions will return false for a file stored in sFTP. In
 * a future release file_exists will be implemented for sFTP connections,
 * together with other relevant file functions, like filesize, fileperms, stat,
 * md5_file, mime_content_type, is_dir, pathinfo, unlink, getimagesize, unset.
 *
 * @since 3.9.3
 *
 * @param int $path The file path to check.
 *
 * @return bool True if file exists, false otherwise.
 */
function wfu_file_exists($path) {
	//sftp will return false; in a future release file_exists will be
	//implemented for sftp connections, together with other relevant file
	//functions, like filesize, fileperms, stat, md5_file, mime_content_type,
	//is_dir, pathinfo, unlink, getimagesize, unset.
	if ( substr($path, 0, 7) == "sftp://" ) {
		return false;
	}
	elseif ( file_exists($path) ) return true;
	
	return false;
}

//********************* User Functions *****************************************

/**
 * Get Matching User Role.
 *
 * This function checks if any of the user's roles are included in a list of
 * roles. If the user is administrator it will match. If 'all' is included in
 * the list of roles then it will also match. The function returns the matched
 * role.
 *
 * @since 2.1.2
 *
 * @param object $user The user to check.
 * @param array $param_roles A list of roles to match the user.
 *
 * @return string The matching role, or 'nomatch'.
 */
function wfu_get_user_role($user, $param_roles) {
	$result_role = 'nomatch';
	if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
		/* Go through the array of the roles of the current user */
		foreach ( $user->roles as $user_role ) {
			$user_role = strtolower($user_role);
			/* if this role matches to the roles in $param_roles or it is
			   administrator or $param_roles allow all roles then it is
			   approved */
			if ( in_array($user_role, $param_roles) || $user_role == 'administrator' || in_array('all', $param_roles) ) {
				/*  We approve this role of the user and exit */
				$result_role = $user_role;
				break;
			}
		}
	}
	/* if the user has no roles (guest) and guests are allowed, then it is
	   approved */
	elseif ( in_array('guests', $param_roles) ) {
		$result_role = 'guest';
	}
	return $result_role;		
}

/**
 * Get Valid User Roles.
 *
 * This function gets all user's valid roles by checking which of them are
 * included in $wp_roles global variable.
 *
 * @since 3.0.0
 *
 * @global array $wp_roles An array of Wordpress roles.
 *
 * @param object $user The user to check.
 *
 * @return array The list of user's valid roles.
 */
function wfu_get_user_valid_role_names($user) {
	global $wp_roles;
	
	$result_roles = array();
	if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
		/* get all valid roles */
		$roles = $wp_roles->get_names();
		/* Go through the array of the roles of the current user */
		foreach ( $user->roles as $user_role ) {
			$user_role = strtolower($user_role);
			/* If one role of the current user matches to the roles allowed to upload */
			if ( in_array($user_role, array_keys($roles)) ) array_push($result_roles, $user_role);
		}
	}

	return $result_roles;		
}

//*********************** DB Functions *****************************************************************************************************

/**
 * Log Action to Database.
 *
 * This function logs plugin's actions (uploads, renames, deletions etc.) in the
 * plugin's database tables. This function stores upload information about all
 * uploaded files.
 *
 * @since 2.4.1
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $action The action to log.
 * @param string $filepath The file path of the involved file.
 * @param int $userid The ID of the user who performs the action.
 * @param string $uploadid The unique ID of the upload, if this is an upload
 *        action.
 * @param int $pageid The ID of the upload page, if this is an upload action.
 * @param int $blogid The ID of the blog (in case this is a multisite
 *        installation).
 * @param int $sid The plugin ID of the upload form, if this is an upload
 *        action.
 * @param array $userdata {
 *        Any additional user data to store with the uploaded files.
 *
 *        @type array $userdata_field {
 *              Individual user data field.
 *
 *              @type string $label The title of the userdata field.
 *              @type string $value The value entered by the user in the field.
 *        }
 * }
 *
 * @return int The ID of the new record that was added in the database, or 0 if
 *         no record was added.
 */
function wfu_log_action($action, $filepath, $userid, $uploadid, $pageid, $blogid, $sid, $userdata) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	if ( !file_exists($filepath) && $action != 'datasubmit' && substr($action, 0, 5) != 'other' ) return;
	$parts = pathinfo($filepath);
	$relativepath = wfu_path_abs2rel($filepath);
//	if ( substr($relativepath, 0, 1) != '/' ) $relativepath = '/'.$relativepath;
	
	$retid = 0;
	if ( $action == 'upload' || $action == 'include' || $action == 'datasubmit' ) {
		if ( $action == 'upload' || $action == 'include' ) {
			// calculate and store file hash if this setting is enabled from Settings
			$filehash = '';
			if ( $plugin_options['hashfiles'] == '1' ) $filehash = md5_file($filepath);
			// calculate file size
			$filesize = filesize($filepath);
			// first make obsolete records having the same file path because the old file has been replaced
			$oldrecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE filepath = \''.esc_sql($relativepath).'\' AND date_to = 0');
			if ( $oldrecs ) {
				foreach ( $oldrecs as $oldrec ) wfu_make_rec_obsolete($oldrec);
			}
		}
		// attempt to create new log record
		$now_date = date('Y-m-d H:i:s');
		if ( $wpdb->insert($table_name1,
			array(
				'userid' 	=> $userid,
				'uploaduserid' 	=> $userid,
				'uploadtime' 	=> time(),
				'sessionid' => wfu_get_session_id(),
				'filepath' 	=> ( $action == 'datasubmit' ? '' : $relativepath ),
				'filehash' 	=> ( $action == 'datasubmit' ? '' : $filehash ),
				'filesize' 	=> ( $action == 'datasubmit' ? 0 : $filesize ),
				'uploadid' 	=> $uploadid,
				'pageid' 	=> $pageid,
				'blogid' 	=> $blogid,
				'sid' 		=> $sid,
				'date_from' 	=> $now_date,
				'date_to' 	=> 0,
				'action' 	=> $action
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s' )) !== false ) {
			$retid = $wpdb->insert_id;
			// if new log record has been created, also create user data records
			if ( $userdata != null && $uploadid != '' ) {
				foreach ( $userdata as $userdata_key => $userdata_field ) {
					$existing = $wpdb->get_row('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$uploadid.'\' AND property = \''.esc_sql($userdata_field['label']).'\' AND date_to = 0');
					if ($existing == null)
						$wpdb->insert($table_name2,
							array(
								'uploadid' 	=> $uploadid,
								'property' 	=> $userdata_field['label'],
								'propkey' 	=> $userdata_key,
								'propvalue' 	=> $userdata_field['value'],
								'date_from' 	=> $now_date,
								'date_to' 	=> 0
							),
							array( '%s', '%s', '%d', '%s', '%s', '%s' ));
				}
			}
		}
	}
	//for rename or move action the $action variable is of the form:
	//  $action = 'rename:'.$newfilepath;   (for rename action)
	//  $action = 'move:'.$newfilepath;   (for move action)
	//in order to pass the new file path
	elseif ( substr($action, 0, 6) == 'rename' || substr($action, 0, 4) == 'move' ) {
		$cleanaction = ( substr($action, 0, 6) == 'rename' ? 'rename' : 'move' );
		//get new filepath
		$newfilepath = substr($action, strlen($cleanaction) + 1);
		$relativepath = wfu_path_abs2rel($newfilepath);
//		if ( substr($relativepath, 0, 1) != '/' ) $relativepath = '/'.$relativepath;
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new rename record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $filerec->uploaduserid,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $relativepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> 0,
					'action' 	=> $cleanaction,
					'linkedto' 	=> $filerec->idlog,
					'filedata' 	=> $filerec->filedata
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' ) ) !== false )
				$retid = $wpdb->insert_id;
		}
	}
	elseif ( $action == 'delete' ) {
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new delete record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $filerec->uploaduserid,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $filerec->filepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> $now_date,
					'action' 	=> 'delete',
					'linkedto' 	=> $filerec->idlog,
					'filedata' 	=> $filerec->filedata
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )) != false )
				$retid = $wpdb->insert_id;
		}
	}
	elseif ( $action == 'download' ) {
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new download record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $filerec->uploaduserid,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $filerec->filepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> 0,
					'action' 	=> 'download',
					'linkedto' 	=> $filerec->idlog,
					'filedata' 	=> $filerec->filedata
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )) != false )
				$retid = $wpdb->insert_id;
		}
	}
	//for modify action the $action variable is of the form: $action = 'modify:'.$now_date; in order to pass the exact modify date
	elseif ( substr($action, 0, 6) == 'modify' ) {
		$now_date = substr($action, 7);
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new modify record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $filerec->uploaduserid,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $filerec->filepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> 0,
					'action' 	=> 'modify',
					'linkedto' 	=> $filerec->idlog,
					'filedata' 	=> $filerec->filedata
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )) != false )
				$retid = $wpdb->insert_id;
		}
	}
	elseif ( substr($action, 0, 10) == 'changeuser' ) {
		$new_user = substr($action, 11);
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new modify record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $new_user,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $filerec->filepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> 0,
					'action' 	=> 'changeuser',
					'linkedto' 	=> $filerec->idlog,
					'filedata' 	=> $filerec->filedata
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )) != false )
				$retid = $wpdb->insert_id;
		}
	}
	elseif ( substr($action, 0, 5) == 'other' ) {
		$info = substr($action, 6);
		$now_date = date('Y-m-d H:i:s');
		//insert new other type record
		if ( $wpdb->insert($table_name1,
			array(
				'userid' 	=> $userid,
				'uploaduserid' 	=> -1,
				'uploadtime' 	=> 0,
				'sessionid'	=> '',
				'filepath' 	=> $info,
				'filehash' 	=> '',
				'filesize' 	=> 0,
				'uploadid' 	=> '',
				'pageid' 	=> 0,
				'blogid' 	=> 0,
				'sid' 		=> '',
				'date_from' 	=> $now_date,
				'date_to' 	=> $now_date,
				'action' 	=> 'other',
				'linkedto' 	=> -1
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' )) != false )
			$retid = $wpdb->insert_id;
	}
	return $retid;
}

/**
 * Revert Database Log Action.
 *
 * This function reverts an action that was recently added in the database. It
 * will also make effective the before-the-last one.
 *
 * @since 2.4.1
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param int $idlog The ID of the database record to revert.
 */
function wfu_revert_log_action($idlog) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";

	$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$idlog);
	if ( $filerec != null ) {
		$prevfilerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$filerec->linkedto);
		if ( $prevfilerec != null ) {
			$wpdb->delete($table_name1,
				array( 'idlog' => $filerec->idlog ),
				array( '%d' )
			);
			$wpdb->update($table_name1,
				array( 'date_to' => 0 ),
				array( 'idlog' => $prevfilerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}
}

/**
 * Get User Name by ID.
 *
 * This function retrieves a user's username by its ID. It will always return a
 * non-empty username, even if user is not found.
 *
 * @since 2.4.1
 *
 * @redeclarable
 *
 * @param int $id The ID of the user.
 *
 * @return string The username.
 */
function wfu_get_username_by_id($id) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$user = get_user_by('id', $id);
	if ( $user == false && $id > 0 ) $username = 'unknown';
	elseif ( $user == false && $id == -999 ) $username = 'system';
	elseif ( $user == false ) $username = 'guest';
	else $username = $user->user_login;
	return $username;
}

/**
 * Get Number of Unread Files.
 *
 * This function retrieves the number of uploaded files that have not been read
 * by the administrator (admin has not opened Uploaded Files page in Dashboard
 * to review them).
 *
 * @since 4.7.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @redeclarable
 *
 * @return int The number of unread files.
 */
function wfu_get_unread_files_count() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";

	//get the last idlog read from options; create the option if it does not
	//exist pointing to the currently last idlog
	$last_idlog = get_option( "wordpress_file_upload_last_idlog" );
	if ( $last_idlog === false ) {
		$latest_idlog = $wpdb->get_var('SELECT MAX(idlog) FROM '.$table_name1);
		$last_idlog = array( 'pre' => $latest_idlog, 'post' => $latest_idlog, 'time' => time() );
		update_option( "wordpress_file_upload_last_idlog", $last_idlog );
	}
	$limit = (int)WFU_VAR("WFU_UPLOADEDFILES_RESET_TIME");
	$unread_files_count = 0;
	if ( $limit == -1 || time() > $last_idlog["time"] + $limit ) $unread_files_count = wfu_get_new_files_count($last_idlog["post"]);
	else $unread_files_count = wfu_get_new_files_count($last_idlog["pre"]);
	
	return $unread_files_count;
}

/**
 * Get Number of New Uploaded Files.
 *
 * This function retrieves the number of newly uploaded files by counting how
 * many where uploaded after a specific database record ID.
 *
 * @since 4.8.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @redeclarable
 *
 * @param int $last_idlog The database record ID which is the base for counting.
 *
 * @return int The number of new uploaded files.
 */
function wfu_get_new_files_count($last_idlog) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	return $wpdb->get_var('SELECT COUNT(idlog) FROM '.$table_name1.' WHERE action = \'upload\' AND idlog > '.(int)$last_idlog);
}

/**
 * Decode Raw File Transfers Log Data.
 *
 * This function converts raw file transfers log data stored in filedata field
 * of a file's database record into a structured array.
 *
 * @since 4.9.0
 *
 * @redeclarable
 *
 * @param string $data The raw log data.
 *
 * @return array {
 *         An array of file transfers log information.
 *
 *         $type string $service The cloud service used for the file transfer.
 *         $type bool $transferred True if the file transfer was successful.
 *         $type string $error Error message if the file transfer failed.
 *         $type string $destination The destination path of the transfer.
 *         $type string $new_filename The new file name of the transferred file.
 * }
 */
function wfu_read_log_data($data) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$ret['service'] = "";
	$ret['transferred'] = "";
	$ret['error'] = "";
	$ret['destination'] = "";
	$ret['new_filename'] = "";
	if ( substr($data, 0, 5) == "json:" ) {
		$logdata = json_decode(substr($data, 5), true);
		$ret['service'] = $logdata["service"];
		$ret['transferred'] = $logdata["transferred"];
		$ret['error'] = $logdata["error"];
		$ret['destination'] = $logdata["destination"];
		$ret['new_filename'] = $logdata["new_filename"];
	}
	else list($ret['service'], $ret['destination']) = explode("|", $data);
	
	return $ret;
}

/**
 * Get Database File Record From File Path.
 *
 * This function gets the most current database record of an uploaded file from
 * its path and also includes any userdata.
 *
 * @since 2.4.1
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $filepath The path of the file.
 * @param bool $include_userdata Include any userdata information in the
 *        returned record.
 *
 * @return object|null The database object of the file, or null if it is not
 *         found.
 */
function wfu_get_file_rec($filepath, $include_userdata) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	if ( !file_exists($filepath) ) return null;

	$relativepath = wfu_path_abs2rel($filepath);
//	if ( substr($relativepath, 0, 1) != '/' ) $relativepath = '/'.$relativepath;
	//if file hash is enabled, then search file based on its path and hash, otherwise find file based on its path and size
	if ( isset($plugin_options['hashfiles']) && $plugin_options['hashfiles'] == '1' ) {
		$filehash = md5_file($filepath);
		$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE filepath = \''.esc_sql($relativepath).'\' AND filehash = \''.$filehash.'\' AND date_to = 0 ORDER BY date_from DESC');
	}
	else {
		$stat = stat($filepath);
		$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE filepath = \''.esc_sql($relativepath).'\' AND filesize = '.$stat['size'].' AND date_to = 0 ORDER BY date_from DESC');
	}
	//get user data
	if ( $filerec != null && $include_userdata ) {
		$filerec->userdata = null;
		if ( $filerec->uploadid != '' ) {
			$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0 ORDER BY propkey');
		}
	}
	return $filerec;
}

/**
 * Get Valid Files From a List of Database Records.
 *
 * This function checks which records in a given list of database records of
 * uploaded files contain valid files and returns their file paths.
 *
 * @since 4.9.1
 *
 * @param array $recs An array of database records of uploaded files.
 *
 * @return array An array of file paths of valid files.
 */
function wfu_get_valid_affected_files($recs) {
	$valid_affected_files = array();
	$files_checked = array();
	foreach ($recs as $rec)
		if ( $latestrec = wfu_get_latest_rec_from_id($rec->idlog) ) {
			$file = wfu_path_rel2abs($latestrec->filepath);
			if ( !in_array($file, $files_checked) ) {
				if ( file_exists($file) ) array_push($valid_affected_files, $file);
				array_push($files_checked, $file);
			}
		}
	
	return $valid_affected_files;
}

/**
 * Get Database File Record From Record ID.
 *
 * This function gets the database record of an uploaded file from its record ID
 * and also includes any userdata.
 *
 * @since 3.9.4
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param int $idlog The database record ID.
 * @param bool $include_userdata Optional. Include any userdata information in
 *        the returned record.
 *
 * @return object|null The database object of the file, or null if it is not
 *         found.
 */
function wfu_get_file_rec_from_id($idlog, $include_userdata = false) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";

	$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$idlog);
	if ( $filerec != null && $include_userdata ) {
		$filerec->userdata = null;
		if ( $filerec->uploadid != '' ) {
			$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0 ORDER BY propkey');
		}
	}

	return $filerec;
}

/**
 * Get Userdata of Uploaded File by Database Record ID.
 *
 * This function gets the userdata (if any) of an uploaded file from its
 * database record ID.
 *
 * @since 4.6.0
 *
 * @param int $idlog The database record ID.
 *
 * @return array {
 *         An array of userdata.
 *
 *         @type $arrayitem {
 *               An individual userdata field.
 *
 *               @type string $property The title of the userdata field.
 *               @type string $value The value entered by the user in the field.
 *         }
 * }
 */
function wfu_get_userdata_from_id($idlog) {
	$userdata = array();
	$filerec = wfu_get_file_rec_from_id($idlog, true);
	if ( $filerec != null && $filerec->userdata != null )
		foreach ( $filerec->userdata as $item ) {
			$arrayitem = array(
				"property"	=> $item->property,
				"value"		=> $item->propvalue
			);
			array_push($userdata, $arrayitem);
		}
	
	return $userdata;
}

/**
 * Get Oldest Database Record From Unique ID.
 *
 * Every file upload has a unique ID. This unique ID remains the same for any
 * consecutive operations that happen on the file (renaming, transfer, deletion
 * etc.). This function gets the oldest (first) record related to this unique
 * ID, which is usually an 'upload' or 'include' action.
 *
 * @since 4.10.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $uniqueid The unique ID of the upload.
 *
 * @return object|null The oldest database record, or null if not found.
 */
function wfu_get_oldestrec_from_uniqueid($uniqueid) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE idlog IN (SELECT MIN(idlog) FROM '.$table_name1.' WHERE uploadid = \''.$uniqueid.'\')');
	if ( $filerecs == null ) return null;
	if ( count($filerecs) > 0 ) return $filerecs[0];
	else return null;
}

/**
 * Get Latest Database Record From Record ID.
 *
 * This function gets the most recend (latest) record of a linked series of
 * database upload records having the same unique ID. Every record is linked to
 * its newer one through 'linkedto' field.
 *
 * @since 4.2.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param int $idlog The database record ID.
 *
 * @return object|null The latest database record, or null if not found.
 */
function wfu_get_latest_rec_from_id($idlog) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$idlog);
	while ( $filerec != null && $filerec->date_to != "0000-00-00 00:00:00" )
		$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE linkedto = '.$filerec->idlog);
	
	return $filerec;
}

/**
 * Get Newer Linked Database Records From Record ID.
 *
 * This function gets the newer records of a linked series of database upload
 * records having the same unique ID. Every record is linked to its newer one
 * through 'linkedto' field.
 *
 * @since 4.7.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param int $idlog The database record ID.
 *
 * @return array An array of newer linked database records.
 */
function wfu_get_rec_new_history($idlog) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$filerecs = array();
	$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$idlog);
	while ( $filerec != null ) {
		array_push($filerecs, $filerec);
		$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE linkedto = '.$filerec->idlog);
	}
	
	return $filerecs;	
}

/**
 * Get Older Linked Database Records From Record ID.
 *
 * This function gets the older records of a linked series of database upload
 * records having the same unique ID. Every record is linked to its newer one
 * through 'linkedto' field.
 *
 * @since 4.7.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param int $idlog The database record ID.
 *
 * @return array An array of older linked database records.
 */
function wfu_get_rec_old_history($idlog) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$filerecs = array();
	$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$idlog);
	while ( $filerec != null ) {
		array_push($filerecs, $filerec);
		$filerec = ( $filerec->linkedto > 0 ? $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$filerec->linkedto) : null );
	}
	
	return $filerecs;	
}

/**
 * Get Latest Filedata Properties From Database Record ID
 *  
 * This function uses an uploaded file's database record ID to return the
 * filedata property of the corresponding record of the file in the database
 * holding data about its transfer to a service account like Dropbox, provided
 * that this record is still valid. If the record does not exist or exists but
 * it is absolete, then the function returns null, otherwise it returns an
 * array.
 *  
 * The [$service]["filepath"] item of the array is set to the final $filepath
 * of the file, in case that the original filename was renamed.
 *
 * @since 4.2.0
 *  
 * @param int $idlog Database record ID of the uploaded file.
 * @param bool $is_new Optional. It must be true if the function is called
 *        during addition of a new file.
 *
 *  @return array|null Returns the filedata array or null if it is not found.
 */
function wfu_get_latest_filedata_from_id($idlog, $is_new = false) {
	//get latest database record of file, if it is still valid
	$filerec = wfu_get_latest_rec_from_id($idlog);
	//return null if the record does not exist or it is obsolete
	if ( $filerec == null ) return null;

	return wfu_get_filedata_from_rec($filerec, $is_new, true, false);
}

/**
 * Get Filedata Properties From File Path
 *  
 * This function uses an uploaded file's path to return the filedata property of
 * the corresponding record of the file in the database holding data about its
 * transfer to a service account like Dropbox, provided that this record is
 * still valid.
 *
 * @since 4.2.0
 *  
 * @param string $filepath The path of the uploaded file.
 * @param bool $include_general_data Optional. Determines whether general upload
 *        data will be included in the returned filedata structure.
 *
 *  @return array|null Returns the filedata array or null if it is not found.
 */
function wfu_get_filedata($filepath, $include_general_data = false) {
	$filerec = wfu_get_file_rec($filepath, false);
	if ( $filerec == null ) return null;

	return wfu_get_filedata_from_rec($filerec, true, false, $include_general_data);
}

/**
 * Get Filedata Properties From Database Record
 *  
 * This function uses an uploaded file's database record to return the filedata
 * property of the corresponding record of the file in the database holding data
 * about its transfer to a service account like Dropbox, provided that this
 * record is still valid.
 *
 * @since 4.3.0
 *  
 * @param object $filerec The database record of the uploaded file.
 * @param bool $is_new Optional. It must be true if the function is called
 *        during addition of a new file.
 * @param bool $update_transfer Optional. Update filepath property in filedata
 *        of "transfer" type, if service records exist.
 * @param bool $include_general_data Optional. Determines whether general upload
 *        data will be included in the returned filedata structure.
 *
 *  @return array|null Returns the filedata array or null if it is not found.
 */
function wfu_get_filedata_from_rec($filerec, $is_new = false, $update_transfer = false, $include_general_data = false) {
	//return filedata, if it does not exist and we do not want to create a new
	//filedata structure return null, otherwise return an empty array
	if ( !isset($filerec->filedata) || is_null($filerec->filedata) ) $filedata = ( $is_new ? array() : null );
	else {
		$filedata = wfu_decode_array_from_string($filerec->filedata);
		if ( !is_array($filedata) ) $filedata = ( $is_new ? array() : null );
	}
	if ( !is_null($filedata) ) {
		//update filepath property in filedata of "transfer" type, if service
		//records exist
		if ( $update_transfer ) {
			foreach ( $filedata as $key => $data )
				if ( !isset($data["type"]) || $data["type"] == "transfer" )
					$filedata[$key]["filepath"] = $filerec->filepath;
		}
		//add idlog in filedata if $include_general_data is true
		if ( $include_general_data )
			$filedata["general"] = array(
				"type"	=> "data",
				"idlog"	=> $filerec->idlog
			);
	}
	
	return $filedata;
}

/**
 * Save Filedata To File Database Record
 *  
 * This function updates the filedata field of the database record of an
 * uploaded file.
 *
 * @since 4.2.0
 *  
 * @global object $wpdb The Wordpress database object.
 *
 * @param int $idlog The database record ID of the uploaded file to be updated.
 * @param array $filedata The new filedata structure to store.
 * @param bool $store_in_latest_rec Optional. Store in the latest linked
 *        database record and not the current one.
 *
 *  @return bool|int Returns false if errors, or the number of rows affected if
 *          successful.
 */
function wfu_save_filedata_from_id($idlog, $filedata, $store_in_latest_rec = true) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	if ( $store_in_latest_rec ) {
		$latestrec = wfu_get_latest_rec_from_id($idlog);
		$idlog = $latestrec->idlog;
	}
	return $wpdb->update($table_name1, array( 'filedata' => wfu_encode_array_to_string($filedata) ), array( 'idlog' => $idlog ), array( '%s' ), array( '%d' ));
}

/**
 * Get Userdata of Uploaded File From Database Record.
 *
 * This function gets the database record of an uploaded file from its database
 * record.
 *
 * @since 4.7.0
 *
 * @see wfu_get_userdata_from_id() For more information on the response array
 *      format.
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param object $filerec The database record of the uploaded file.
 *
 * @return array An array of userdata.
 */
function wfu_get_userdata_from_rec($filerec) {
	global $wpdb;
	$table_name2 = $wpdb->prefix . "wfu_userdata";

	$userdata = array();
	if ( $filerec->uploadid != '' ) {
		$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0 ORDER BY propkey');
		if ( $filerec->userdata != null )
			foreach ( $filerec->userdata as $item ) {
				$arrayitem = array(
					"property"	=> $item->property,
					"value"		=> $item->propvalue
				);
				array_push($userdata, $arrayitem);
			}
	}

	return $userdata;
}

/**
 * Get Userdata of Uploaded File From Unique ID.
 *
 * This function gets the database record of an uploaded file from the unique ID
 * of the upload.
 *
 * @since 3.11.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $uploadid The unique ID of the upload.
 *
 * @return object|null A userdata database record or null if not found.
 */
function wfu_get_userdata_from_uploadid($uploadid) {
	global $wpdb;
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$uploadid.'\' AND date_to = 0 ORDER BY propkey');

	return $userdata;
}

/**
 * Reassign File Hashes.
 *
 * The plugin calculates md5 hashes for all uploaded files, upon selection, to
 * verify later if the files have changed or not. This function reassignes the
 * hashes for all valid uploaded files. This function may take a lot of time
 * depending on the number and size of the uploaded files.
 *
 * @since 2.4.1
 *
 * @global object $wpdb The Wordpress database object.
 */
function wfu_reassign_hashes() {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	if ( $plugin_options['hashfiles'] == '1' ) {
		$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE filehash = \'\' AND date_to = 0');
		foreach( $filerecs as $filerec ) {
			//calculate full file path
			$filepath = wfu_path_rel2abs($filerec->filepath);
			if ( file_exists($filepath) ) {
				$filehash = md5_file($filepath);
				$wpdb->update($table_name1,
					array( 'filehash' => $filehash ),
					array( 'idlog' => $filerec->idlog ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}
}

/**
 * Make Uploaded File Database Record Obsolete.
 *
 * This function makes a database record of an uploaded file obsolete. This
 * means that the file is considered not valid anymore. Any related thumbnails
 * are deleted.
 *
 * @since 3.11.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @redeclarable
 *
 * @param object $filerec The database record to make obsolete.
 *
 * @return bool|int Returns false if errors, or the number of rows affected if
 *         successful.
 */
function wfu_make_rec_obsolete($filerec) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$filedata = wfu_get_filedata_from_rec($filerec, true);
	//update db record accordingly
	$wpdb->update($table_name1,
		array( 'date_to' => date('Y-m-d H:i:s'), 'filedata' => wfu_encode_array_to_string($filedata) ),
		array( 'idlog' => $filerec->idlog ),
		array( '%s', '%s' ),
		array( '%d' )
	);
}

/**
 * Synchronize Plugin's Database.
 *
 * This function updates database to reflect the current status of files.
 *
 * @since 2.4.1
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @redeclarable
 *
 * @return number The number of obsolete records found.
 */
function wfu_sync_database() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action <> \'other\' AND action <> \'datasubmit\' AND date_to = 0');
	$obsolete_count = 0;
	foreach( $filerecs as $filerec ) {
		$obsolete = true;
		//calculate full file path
		$filepath = wfu_path_rel2abs($filerec->filepath);
		if ( file_exists($filepath) ) {
			if ( $plugin_options['hashfiles'] == '1' ) {
				$filehash = md5_file($filepath);
				if ( $filehash == $filerec->filehash ) $obsolete = false;
			}
			else {
				$filesize = filesize($filepath);
				if ( $filesize == $filerec->filesize ) $obsolete = false;
			}
		}
		if ( $obsolete ) {
			wfu_make_rec_obsolete($filerec);
			$obsolete_count ++;
		}
	}
	return $obsolete_count;
}

/**
 * Get Uploaded File Database Records of Specific User.
 *
 * This function is used the retrieve the files uploaded by a specific user by
 * returning all the valid uploaded files' database records. If the user ID
 * provided starts with 'guest' then this means that the user is a guest and
 * retrieval will be done based on the session ID of the session that was
 * generated between the user's browser and the website when the user uploaded
 * files. This function will check if there are obsolete records. It will also
 * return any additional user data.
 *
 * @since 3.0.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param int|string $userid The user ID. If the user is a guest, it must be a
 *        string starting with 'guest' and then including the session ID.
 *
 * @return array An array of user's database records of uploaded files.
 */
function wfu_get_recs_of_user($userid) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	//if $userid starts with 'guest' then retrieval of records is done using sessionid and uploaduserid is zero (for guests)
	if ( substr($userid, 0, 5) == 'guest' )
		$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action <> \'other\' AND action <> \'datasubmit\' AND uploaduserid = 0 AND sessionid = \''.substr($userid, 5).'\' AND date_to = 0');
	else
		$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action <> \'other\' AND action <> \'datasubmit\' AND uploaduserid = '.$userid.' AND date_to = 0');
	$out = array();
	foreach( $filerecs as $filerec ) {
		$obsolete = true;
		//calculate full file path
		$filepath = wfu_path_rel2abs($filerec->filepath);
		if ( file_exists($filepath) ) {
			if ( $plugin_options['hashfiles'] == '1' ) {
				$filehash = md5_file($filepath);
				if ( $filehash == $filerec->filehash ) $obsolete = false;
			}
			else {
				$filesize = filesize($filepath);
				if ( $filesize == $filerec->filesize ) $obsolete = false;
			}
		}
		if ( $obsolete ) {
			wfu_make_rec_obsolete($filerec);
		}
		else {
			$filerec->userdata = null;
			if ( $filerec->uploadid != '' ) 
				$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0 ORDER BY propkey');
			array_push($out, $filerec);
		}
	}
	
	return $out;
}

/**
 * Get Filtered Uploaded Files Database Records.
 *
 * This function gets a list of database records of uploaded files based on a
 * list of filters. This function will check if there are obsolete records. It
 * will also return any additional user data.
 *
 * @since 3.2.1
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @redeclarable
 *
 * @param array $filter An array of filters to apply.
 *
 * @return array An array of matched database records of uploaded files.
 */
function wfu_get_filtered_recs($filter) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	$queries = array();
	// add default filters
	array_push($queries, 'action <> \'other\' AND action <> \'datasubmit\'');
	array_push($queries, 'date_to = 0');
	// construct user filter
	if ( isset($filter['user']) ) {
		if ( $filter['user']['all'] ) {
			if ( $filter['user']['guests'] ) $query = 'uploaduserid >= 0';
			else $query = 'uploaduserid > 0';
		}
		elseif ( count($filter['user']['ids']) == 1 && substr($filter['user']['ids'][0], 0, 5) == 'guest' )
			$query = 'uploaduserid = 0 AND sessionid = \''.substr($filter['user']['ids'][0], 5).'\'';
		else {
			if ( $filter['user']['guests'] ) array_push($filter['user']['ids'], '0');
			if ( count($filter['user']['ids']) == 1 ) $query = 'uploaduserid = '.$filter['user']['ids'][0];
			else $query = 'uploaduserid in ('.implode(",",$filter['user']['ids']).')';
		}
		array_push($queries, $query);
	}
	// construct size filter
	if ( isset($filter['size']) ) {
		if ( isset($filter['size']['lower']) && isset($filter['size']['upper']) )
			$query = 'filesize > '.$filter['size']['lower'].' AND filesize < '.$filter['size']['upper'];
		elseif ( isset($filter['size']['lower']) ) $query = 'filesize > '.$filter['size']['lower'];
		else $query = 'filesize < '.$filter['size']['upper'];
		array_push($queries, $query);
	}
	// construct date filter
	if ( isset($filter['date']) ) {
		if ( isset($filter['date']['lower']) && isset($filter['date']['upper']) )
			$query = 'uploadtime > '.$filter['date']['lower'].' AND uploadtime < '.$filter['date']['upper'];
		elseif ( isset($filter['date']['lower']) ) $query = 'uploadtime > '.$filter['date']['lower'];
		else $query = 'uploadtime < '.$filter['date']['upper'];
		array_push($queries, $query);
	}
	// construct file pattern filter
	if ( isset($filter['pattern']) ) {
		$query = 'filepath REGEXP \''.wfu_upload_plugin_wildcard_to_mysqlregexp($filter['pattern']).'\'';
		array_push($queries, $query);
	}
	// construct page/post filter
	if ( isset($filter['post']) ) {
		if ( count($filter['post']['ids']) == 1 ) $query = 'pageid = '.$filter['post']['ids'][0];
			else $query = 'pageid in ('.implode(",",$filter['post']['ids']).')';
		array_push($queries, $query);
	}
	// construct blog filter
	if ( isset($filter['blog']) ) {
		if ( count($filter['blog']['ids']) == 1 ) $query = 'blogid = '.$filter['blog']['ids'][0];
			else $query = 'blogid in ('.implode(",",$filter['blog']['ids']).')';
		array_push($queries, $query);
	}
	// construct userdata filter
	if ( isset($filter['userdata']) ) {
		if ( $filter['userdata']['criterion'] == "equal to" ) $valuecriterion = 'propvalue = \''.esc_sql($filter['userdata']['value']).'\'';
		elseif ( $filter['userdata']['criterion'] == "starts with" ) $valuecriterion = 'propvalue LIKE \''.esc_sql($filter['userdata']['value']).'%\'';
		elseif ( $filter['userdata']['criterion'] == "ends with" ) $valuecriterion = 'propvalue LIKE \'%'.esc_sql($filter['userdata']['value']).'\'';
		elseif ( $filter['userdata']['criterion'] == "contains" ) $valuecriterion = 'propvalue LIKE \'%'.esc_sql($filter['userdata']['value']).'%\'';
		elseif ( $filter['userdata']['criterion'] == "not equal to" ) $valuecriterion = 'propvalue <> \''.esc_sql($filter['userdata']['value']).'\'';
		elseif ( $filter['userdata']['criterion'] == "does not start with" ) $valuecriterion = 'propvalue NOT LIKE \''.esc_sql($filter['userdata']['value']).'%\'';
		elseif ( $filter['userdata']['criterion'] == "does not end with" ) $valuecriterion = 'propvalue NOT LIKE \'%'.esc_sql($filter['userdata']['value']).'\'';
		elseif ( $filter['userdata']['criterion'] == "does not contain" ) $valuecriterion = 'propvalue NOT LIKE \'%'.esc_sql($filter['userdata']['value']).'%\'';
		else $valuecriterion = 'propvalue = \''.esc_sql($filter['userdata']['value']).'\'';
		$query = 'uploadid in (SELECT DISTINCT uploadid FROM '.$table_name2.' WHERE date_to = 0 AND property = \''.esc_sql($filter['userdata']['field']).'\' AND '.$valuecriterion.')';
		array_push($queries, $query);
	}
	
	/**
	 * Customize Filter Queries.
	 *
	 * This filter allows custom actions to midify the queries that will be used
	 * to filter the selected records of a file viewer.
	 *
	 * @since 4.6.2
	 *
	 * @param array $queries An array of queries to filter the selected records.
	 * @param array $filter The filter array that generated the queries.
	 */
	$queries = apply_filters("_wfu_filtered_recs_queries", $queries, $filter);
	
	$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE '.implode(' AND ', $queries));
	$out = array();
	foreach( $filerecs as $filerec ) {
		$obsolete = true;
		//calculate full file path
		$filepath = wfu_path_rel2abs($filerec->filepath);
		if ( file_exists($filepath) ) {
			if ( $plugin_options['hashfiles'] == '1' ) {
				$filehash = md5_file($filepath);
				if ( $filehash == $filerec->filehash ) $obsolete = false;
			}
			else {
				$filesize = filesize($filepath);
				if ( $filesize == $filerec->filesize ) $obsolete = false;
			}
		}
		if ( $obsolete ) {
			wfu_make_rec_obsolete($filerec);
		}
		else {
			$filerec->userdata = null;
			if ( $filerec->uploadid != '' ) 
				$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0 ORDER BY propkey');
			array_push($out, $filerec);
		}
	}
	
	return $out;
}

/**
 * Get Uncached Option.
 *
 * This function gets an option from the website's Options table. It will first
 * delete any cached values of the option, so that the stored value in database
 * is returned.
 *
 * @since 3.5.0
 *
 * @param string $option The option name to retrieve.
 * @param mixed $default Optional. A default value to return in case option does
 *        not exist.
 *
 * @return mixed The uncached value of the option.
 */
function wfu_get_uncached_option($option, $default = false) {
	$GLOBALS['wp_object_cache']->delete( $option, 'options' );
	return get_option($option, $default);
}

/**
 * Get Plugin Option.
 *
 * This function gets a plugin option from the website's Options table. It uses
 * direct access to options table of the website in order to avoid caching
 * problems that may happen when retrieving plugin options from parallel server-
 * side scripts.
 *
 * @since 3.5.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $option The option name to retrieve.
 * @param mixed $default A default value to return in case option does not
 *        exist.
 * @param string $type Optional. The value type.
 *
 * @return mixed The value of the option.
 */
function wfu_get_option($option, $default, $type = "array") {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	$val = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $table_name1 WHERE option_name = %s", $option));
	if ( $val === null && $default !== false ) $val = $default;
	elseif ( $val !== null ) $val = ( $type == "array" ? wfu_decode_array_from_string($val) : $val );
	return $val;
}

/**
 * Get Plugin Option Item.
 *
 * This function gets an option item from the website's Options table. Option
 * items are stored in the option value in an encoded format like this:
 *
 *  [item_name1]item_value1{item_name1}[item_name2]item_value2{item_name2}...
 *
 * This format can be parsed and get the value of a specific item using a single
 * SQL command. This is exptremely important when working with parallel server-
 * side scripts, otherwise data may be lost.
 *
 * @since 4.12.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $option The option name that contains the item.
 * @param string $item The item name whose value to retrieve.
 *
 * @return null|string Null will be returned if option are item is not found,
 *         otherwise the item value will be returned as string.
 */
function wfu_get_option_item($option, $item) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	$val = $wpdb->get_var($wpdb->prepare("SELECT SQL_NO_CACHE IF (COUNT(option_value) = 0, NULL, IF (INSTR(option_value, %s) > 0, SUBSTRING_INDEX(SUBSTRING_INDEX(option_value, %s, -1), %s,  1), NULL)) FROM $table_name1 WHERE option_name = %s", '['.$item.']', '['.$item.']', '{'.$item.'}', $option));
	//wfu_debug_log("read:".$item." value:".$val."\n");
	return $val;
}

/**
 * Check If Plugin Option Item Exists.
 *
 * This function checks if an option item in the website's Options table exists.
 * Option items and their format are described in wfu_get_option_item() function
 * above.
 *
 * @since 4.12.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $option The option name that contains the item.
 * @param string $item The item name whose existence to check.
 *
 * @return null|bool Null will be returned if option is not found, true if the
 *         item exists, false otherwise.
 */
function wfu_option_item_exists($option, $item) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	$exists = $wpdb->get_var($wpdb->prepare("SELECT SQL_NO_CACHE IF (COUNT(option_value) = 0, NULL, IF (INSTR(option_value, %s) > 0, TRUE, FALSE)) FROM $table_name1 WHERE option_name = %s", '['.$item.']', $option));
	return $exists;
}

/**
 * Update Plugin Option.
 *
 * This function updates a plugin array option in the website's Options table or
 * creates it if it does not exist. It makes direct access to the website's
 * Options database table. It uses a single SQL command to insert or update the
 * option. This is necessary when working with parallel server-side scripts,
 * like the ones created when transferring multiple files to cloud services
 * asynchronously. The common Wordpress functions get_option() and
 * update_option() are not sufficient for such operations.
 *
 * @since 3.5.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $option The option name to update.
 * @param mixed $value The new value of the option.
 * @param string $type Optional. The value type.
 */
function wfu_update_option($option, $value, $type = "array") {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	$value = ( $type == "array" ? wfu_encode_array_to_string($value) : $value );
	$wpdb->query($wpdb->prepare("INSERT INTO $table_name1 (option_name, option_value) VALUES (%s, %s) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)", $option, $value));
}

/**
 * Run Process in Queue.
 *
 * It has been observed that parallel PHP scripts can read/write to the database
 * and also the file system concurrently. This will cause problems with uploads.
 * File parts are uploaded concurrently, however it is necessary that each one
 * is processed at the server-side separately, before the next one starts. The
 * reason is that when the server reads a new chunk, it stores and retrieves
 * data from session. If more than one chunks write to session at the same time,
 * then mixups will happen and the upload will eventually fail.
 *
 * This function put processes that need to run concurrently (called 'threads')
 * in a FIFO queue based on a unique queue ID. The first thread that comes is
 * the first to be executed. The next one will be executed after the first one
 * finishes. A timeout loop checks the thread status. If a thread takes too long
 * to complete, it is considered as failed and it is removed from the queue, so
 * that the queue continues to the next threads.
 *
 * @since 4.12.0
 *
 * @param string $queue_id The unique queue ID.
 * @param string $proc The function that is put in queue.
 * @param array $params The function parameters.
 *
 * @return array {
 *         The result of queue execution.
 *
 *         @type bool $result True if the process was executed successfully,
 *               false otherwise.
 *         @type string $thread_code The unique code of the current thread.
 *         @type integer $thread_index The index of the current thread.
 *         @type null|mixed $output The return value of the executed function in
 *               case of success, null otherwise.
 *         @type string $error Error code in case of thread execution failure.
 * }
 */
function wfu_run_process_in_queue($queue_id, $proc, $params) {
	$ret = array(
		"result" => false,
		"thread_code" => "",
		"thread_index" => 0,
		"output" => null,
		"error" => ""
	);
	if ( WFU_VAR("WFU_QUEUE_ACTIVE") == "true" ) {
		$queue = "wfu_queue_".$queue_id;
		if ( $queue_id == "" ) {
			$ret["error"] = "noid";
			return $ret;
		}
		$thread_code = wfu_create_random_string(16);
		wfu_join_queue($queue_id, $thread_code);
		$limit = intval(WFU_VAR("WFU_QUEUE_THREAD_TIMEOUT"));
		$waitloop = intval(WFU_VAR("WFU_QUEUE_LOOP_DELAY")) * 1000;
		$tcheck = time() + $limit;
		$last_thread = "";
		$abort = false;
		while (true) {
			$cur_thread = wfu_get_queue_thread($queue_id);
			if ( $cur_thread == $thread_code ) break;
			//calculate queue activity; if thread has changed then reset timer
			if ( $cur_thread != $last_thread ) {
				$last_thread = $cur_thread;
				$tcheck = time() + $limit;
			}
			//if time limit has passed this means that the current queue thread is
			//not progressing, so we need to exit the queue otherwise there will be
			//an infinite loop
			elseif ( time() > $tcheck ) {
				wfu_remove_queue_thread($queue_id, $thread_code);
				wfu_remove_queue_thread($queue_id, $cur_thread);
				$abort = true;
				break;
			}
			usleep($waitloop);
		}
		if ( $abort ) {
			$ret["error"] = "abort_thread";
			return $ret;
		}
		$thread_index = intval(wfu_get_option($queue."_count", 0, "string")) + 1;
		wfu_update_option($queue."_count", $thread_index, "string");
	}
	//create an array of references to the function arguments and pass this to
	//call_user_func_array instead of $args; this is a workaround to avoid PHP
	//warnings when the original function passes arguments by reference
	$args_byref = array();
	foreach ( $params as $key => &$arg ) $args_byref[$key] = &$arg;
	$output = call_user_func_array($proc, $args_byref);
	$ret["result"] = true;
	$ret["output"] = $output;
	if ( WFU_VAR("WFU_QUEUE_ACTIVE") == "true" ) {
		$ret["thread_code"] = $thread_code;
		$ret["thread_index"] = $thread_index;
		wfu_advance_queue($queue_id);
	}
	return $ret;
}

/**
 * Join Thread in Queue.
 *
 * This function adds a new thread in a queue. If the queue does not exist it
 * will be created.
 *
 * @since 4.12.0
 *
 * @param string $queue_id The unique queue ID.
 * @param string $thread The new thread code.
 */
function wfu_join_queue($queue_id, $thread) {
	global $wpdb;
	if ( $queue_id == "" ) return;
	$queue = "wfu_queue_".$queue_id;
	$table_name1 = $wpdb->prefix . "options";
	$wpdb->query($wpdb->prepare("INSERT INTO $table_name1 (option_name, option_value) VALUES (%s, %s) ON DUPLICATE KEY UPDATE option_value = CONCAT(option_value, IF (option_value = '', '', '|'), %s)", $queue, $thread, $thread));
}

/**
 * Advance Queue.
 *
 * This function advances a queue to the next thread.
 *
 * @since 4.12.0
 *
 * @param string $queue_id The unique queue ID.
 */
function wfu_advance_queue($queue_id) {
	global $wpdb;
	if ( $queue_id == "" ) return;
	$queue = "wfu_queue_".$queue_id;
	$table_name1 = $wpdb->prefix . "options";
	$wpdb->query($wpdb->prepare("UPDATE $table_name1 SET option_value = if (instr(option_value, '|') = 0, '', substr(option_value, instr(option_value, '|') + 1)) WHERE option_name = %s", $queue));
}

/**
 * Get Running Queue Thread.
 *
 * This function gets the currently running thread of a queue.
 *
 * @since 4.12.0
 *
 * @param string $queue_id The unique queue ID.
 */
function wfu_get_queue_thread($queue_id) {
	global $wpdb;
	if ( $queue_id == "" ) return;
	$queue = "wfu_queue_".$queue_id;
	$table_name1 = $wpdb->prefix . "options";
	return $wpdb->get_var($wpdb->prepare("SELECT substring_index(option_value, '|', 1) FROM $table_name1 WHERE option_name = %s", $queue));
}

/**
 * Remove Thread from Queue.
 *
 * This function removes a thread from a queue.
 *
 * @since 4.12.0
 *
 * @param string $queue_id The unique queue ID.
 * @param string $thread The thread code to remove.
 */
function wfu_remove_queue_thread($queue_id, $thread) {
	global $wpdb;
	if ( $queue_id == "" ) return;
	$queue = "wfu_queue_".$queue_id;
	$table_name1 = $wpdb->prefix . "options";
	$wpdb->query($wpdb->prepare("UPDATE $table_name1 SET option_value = replace(replace(replace(replace(option_value, concat('|', %s, '|'), '|'), concat(%s, '|'), ''), concat('|', %s), ''), %s, '') WHERE option_name = %s", $thread, $thread, $thread, $thread, $queue));
}

/**
 * Remove Queue.
 *
 * This function removes a queue from options database table.
 *
 * @since 4.12.0
 *
 * @param string $queue_id The unique queue ID.
 */
function wfu_remove_queue($queue_id) {
	if ( $queue_id == "" ) return;
	$queue = "wfu_queue_".$queue_id;
	delete_option($queue);
}

/**
 * Update Plugin Option Item.
 *
 * This function updates an option item in the website's Options table. Option
 * items and their format are described in wfu_get_option_item() function above.
 * It has to be noted that the update of an option item requires a complex SQL
 * query, consisting of an INSERT statement calling a SELECT statement. In case
 * that many such queries are executed at the same time (like it happens when
 * uploading a file in chunks), database deadlocks may occur. To overcome the
 * situation, the transaction will be repeated until it succeeds or when a pre-
 * defined timeout is reached. 
 *
 * @since 4.12.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $option The option name that contains the item.
 * @param string $item The item name whose value to retrieve.
 * @param string $value The new value of the item.
 *
 * @return false|int False if there was a DB error, or the number of rows
 *         affected.
 */
function wfu_update_option_item($option, $item, $value) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	$timeout = time();
	$val = false;
	$suppress_wpdb_errors = $wpdb->suppress_errors;
	if ( !$suppress_wpdb_errors ) $wpdb->suppress_errors(true);
	while ( $val === false && time() < $timeout + intval(WFU_VAR("WFU_US_DEADLOCK_TIMEOUT")) ) {
		$val = $wpdb->query($wpdb->prepare("INSERT INTO $table_name1 (option_name, option_value) SELECT SQL_NO_CACHE %s, IF (COUNT(option_value) = 0, %s, IF (INSTR(option_value, %s) = 0, CONCAT(option_value, %s), CONCAT(SUBSTRING_INDEX(option_value, %s, 1), %s, SUBSTRING_INDEX(option_value, %s, -1)))) FROM $table_name1 WHERE option_name = %s ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)", $option, '['.$item.']'.$value.'{'.$item.'}', '['.$item.']', '['.$item.']'.$value.'{'.$item.'}', '['.$item.']', '['.$item.']'.$value.'{'.$item.'}', '{'.$item.'}', $option));
		if ( $val === false && WFU_VAR("WFU_US_LOG_DBERRORS") == "true" ) error_log("Database error: ".$wpdb->last_error);
	}
	if ( !$suppress_wpdb_errors ) $wpdb->suppress_errors(false);
	return $val;
}

/**
 * Delete Plugin Option.
 *
 * This function deletes a plugin array option from the website's Options table.
 * It makes direct access to the website's Options database table so that
 * caching problems are avoided, when used together with the previous
 * wfu_get_option() and wfu_update_option() functions.
 *
 * @since 4.5.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $option The option name to update.
 */
function wfu_delete_option($option) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	$val = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $table_name1 WHERE option_name = %s", $option));
	$wpdb->query($wpdb->prepare("DELETE FROM $table_name1 WHERE option_name = %s", $option));
}

/**
 * Delete Plugin Option Item.
 *
 * This function deletes an option item in the website's Options table. Option
 * items and their format are described in wfu_get_option_item() function above.
 *
 * @since 4.12.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @param string $option The option name that contains the item.
 * @param string $item The item name whose value to retrieve.
 *
 * @return false|int False if there was a DB error, or the number of rows
 *         affected.
 */
function wfu_delete_option_item($option, $item) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	$timeout = time();
	$val = false;
	$suppress_wpdb_errors = $wpdb->suppress_errors;
	if ( !$suppress_wpdb_errors ) $wpdb->suppress_errors(true);
	while ( $val === false && time() < $timeout + intval(WFU_VAR("WFU_US_DEADLOCK_TIMEOUT")) ) {
		$val = $wpdb->query($wpdb->prepare("INSERT INTO $table_name1 (option_name, option_value) SELECT SQL_NO_CACHE %s, IF (COUNT(option_value) = 0, '', IF (INSTR(option_value, %s) = 0, option_value, CONCAT(SUBSTRING_INDEX(option_value, %s, 1), SUBSTRING_INDEX(option_value, %s, -1)))) FROM $table_name1 WHERE option_name = %s ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)", $option, '['.$item.']', '['.$item.']', '{'.$item.'}', $option));
		if ( $val === false && WFU_VAR("WFU_US_LOG_DBERRORS") == "true" ) error_log("Database error: ".$wpdb->last_error);
	}
	if ( !$suppress_wpdb_errors ) $wpdb->suppress_errors(false);
	return $val;
}

/**
 * Prepare Data of Uploaded Files for Export.
 *
 * This function generates a file that contains data of uploaded files in csv
 * format for export. It will either export data of all valid uploaded files or
 * data of all uploaded files (valid or not) of a specififc user.
 *
 * @since 3.5.0
 *
 * @global object $wpdb The Wordpress database object.
 *
 * @redeclarable
 *
 * @param array $params An array of parameters to pass to the function.
 *
 * @return string The path of the file that contains the prepared data.
 */
function wfu_export_uploaded_files($params) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$sep = WFU_VAR("WFU_EXPORT_DATA_SEPARATOR");
	$sep2 = WFU_VAR("WFU_EXPORT_USERDATA_SEPARATOR");
	$includeall = isset($params["username"]);

	$contents = "";
	$header = 'Name'.$sep.'Path'.$sep.'Upload User'.$sep.'Upload Time'.$sep.'Size'.$sep.'Page ID'.$sep.'Blog ID'.$sep.'Shortcode ID'.$sep.'Upload ID'.$sep.'User Data';
	$contents = $header;
	if ( $includeall ) {
		$user = get_user_by('login', $params["username"]);
		$userid = $user->ID;
		$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE uploaduserid = '.$userid);
	}
	else $filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action <> \'other\' AND date_to = 0');
	foreach( $filerecs as $filerec ) {
		if ( $filerec->action == 'datasubmit' ) $obsolete = false;
		else {
			$obsolete = true;
			//calculate full file path
			$filepath = wfu_path_rel2abs($filerec->filepath);
			if ( file_exists($filepath) ) {
				if ( $plugin_options['hashfiles'] == '1' ) {
					$filehash = md5_file($filepath);
					if ( $filehash == $filerec->filehash ) $obsolete = false;
				}
				else {
					$filesize = filesize($filepath);
					if ( $filesize == $filerec->filesize ) $obsolete = false;
				}
			}
		}
		//export file data if file is not obsolete
		if ( !$obsolete || $includeall ) {
			$username = wfu_get_username_by_id($filerec->uploaduserid);
			$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0 ORDER BY propkey');
			$line = ( $filerec->action == 'datasubmit' ? 'datasubmit' : wfu_basename($filerec->filepath) );
			$line .= $sep.( $filerec->action == 'datasubmit' ? '' :  wfu_basedir($filerec->filepath) );
			$line .= $sep.$username;
			$line .= $sep.( $filerec->uploadtime == null ? "" : date("Y-m-d H:i:s", $filerec->uploadtime) );
			$line .= $sep.( $filerec->action == 'datasubmit' ? '0' : $filerec->filesize );
			$line .= $sep.( $filerec->pageid == null ? "" : $filerec->pageid );
			$line .= $sep.( $filerec->blogid == null ? "" : $filerec->blogid );
			$line .= $sep.( $filerec->sid == null ? "" : $filerec->sid );
			$line .= $sep.$filerec->uploadid;
			$line2 = "";
			foreach ( $filerec->userdata as $userdata ) {
				if ( $line2 != "" ) $line2 .= $sep2;
				$line2 .= $userdata->property.":".str_replace(array("\n", "\r", "\r\n"), " ", $userdata->propvalue);
			}
			$line .= $sep.$line2;
			$contents .= "\n".$line;
		}
	}
	//create file
	$path = tempnam(sys_get_temp_dir(), 'wfu');
	file_put_contents($path, $contents);
	
	return $path;
}

/**
 * Get All Plugin Options.
 *
 * This function gets a list of all plugin's options and variables stored in
 * user space (usually session).
 *
 * @since 4.9.1
 *
 * @return array {
 *         An array of all plugin options.
 *
 *         $type string $name Name of option, an asterisk (*) denotes many
 *               occurencies.
 *         $type string $location Location of option, "db" or "session".
 *         $type bool $deleteOnPurge Delete this option when purging all plugin
 *               data.
 *         $type bool $extract Store this option when extracting plugin data.
 * }
 */
function wfu_get_all_plugin_options() {
	//structure of $options array; every item has the following properties:
	//  0: name of option, an asterisk (*) denotes many occurencies
	//  1: location of option, "db" or "session"
	//  2: delete this option when purging all plugin data
	//  3: store this option when extracting plugin data
	$options = array(
		//stored plugin's Settings
		array( "wordpress_file_upload_options", "db", true, true ),
		//wfu_log table version
		array( "wordpress_file_upload_table_log_version", "db", true, true ),
		//wfu_userdata version
		array( "wordpress_file_upload_table_userdata_version", "db", true, true ),
		//wfu_dbxqueue version
		array( "wordpress_file_upload_table_dbxqueue_version", "db", true, true ),
		//stored hooks
		array( "wordpress_file_upload_hooks", "db", true, true ),
		//transfer manager properties
		array( "wfu_transfermanager_props", "db", true, true ),
		//last file record that was read
		array( "wordpress_file_upload_last_idlog", "db", true, false ),
		//indices of stored shortcode parameters
		array( "wfu_params_index", "db", true, false ),
		//stored shortcode parameters
		array( "wfu_params_*", "db", true, false ),
		//stored advanced environment variables
		array( "wfu_environment_variables", "db", true, true ),
		//stored global tokens
		array( "wfu_gst_*", "db", true, false ),
		//data of unfinished uploaded files
		array( "wordpress_file_upload_unfinished_data", "db", true, false ),
		//list of stored variables in dboption user state
		array( "wfu_userstate_list", "db", true, false ),
		//stored variable value in dboption user state
		array( "wfu_userstate_*", "db", true, false ),
		//last time dboption user state was checked
		array( "wfu_userstate_list_last_check", "db", true, false ),
		//stored personal data policies
		array( "wordpress_file_upload_pd_policies", "db", true, true ),
		//last time admin was notified about DOS attack
		array( "wfu_admin_notification_about_DOS", "db", true, false ),
		//stored token for adding uploader shortcode
		array( "wfu_add_shortcode_ticket_for_wordpress_file_upload", "session", true, false ),
		//stored token for adding file viewer shortcode
		array( "wfu_add_shortcode_ticket_for_wordpress_file_upload_browser", "session", true, false ),
		//session array holding dir and file paths
		array( "wfu_filepath_safe_storage", "session", true, false ),
		//stored rename file flag when renaming file
		array( "wfu_rename_file", "session", true, false ),
		//stored rename file error when renaming file
		array( "wfu_rename_file_error", "session", true, false ),
		//stored create dir flag when creating dir
		array( "wfu_create_dir", "session", true, false ),
		//stored create dir error when creating dir
		array( "wfu_create_dir_error", "session", true, false ),
		//stored file details error when updating file details
		array( "wfu_filedetails_error", "session", true, false ),
		//stored hook data key when updating a hook
		array( "wfu_hook_data_key", "session", true, false ),
		//stored hook data title when updating a hook
		array( "wfu_hook_data_title", "session", true, false ),
		//stored hook data description when updating a hook
		array( "wfu_hook_data_description", "session", true, false ),
		//stored hook data code when updating a hook
		array( "wfu_hook_data_code", "session", true, false ),
		//stored hook data status when updating a hook
		array( "wfu_hook_data_status", "session", true, false ),
		//stored hook data scope when updating a hook
		array( "wfu_hook_data_scope", "session", true, false ),
		//stored hook data error message when updating a hook
		array( "wfu_hook_data_message", "session", true, false ),
		//stored data of file transfers tab
		array( "wfu_transfers_data", "session", true, false ),
		//stored token of upload form
		array( "wfu_token_*", "session", true, false ),
		//stored data of uploaded files
		array( "filedata_*", "session", true, false ),
		//stored status of upload
		array( "wfu_uploadstatus_*", "session", true, false ),
		//flag determining if this is the first pass of an upload
		array( "wfu_upload_first_pass_*", "session", true, false ),
		//stored approved captcha verification code
		array( "wfu_approvedcaptcha_*", "session", true, false ),
		//stored short tokens
		array( "wfu_ust_*", "session", true, false ),
		//stored shortcode data
		array( "wfu_shortcode_data_safe_storage", "session", true, false ),
		//stored number of deleted thumbnails
		array( "wfu_deleted_thumbnails_counter", "session", true, false ),
		//stored number of added thumbnails
		array( "wfu_added_thumbnails_counter", "session", true, false ),
		//stored consent data
		array( "WFU_Consent_Data", "session", true, false ),
		//stored browser actions
		array( "wfu_browser_actions_safe_storage", "session", true, false ),
		//stored data of chunked uploads
		array( "chunkdata_*", "session", true, false ),
		//stored flag of uploader form refresh status
		array( "wfu_check_refresh_*", "session", true, false ),
		//stored upload start time
		array( "wfu_start_time_*", "session", true, false ),
		//stored upload start time
		array( "wfu_start_time_*", "session", true, false )
	);
	

	return $options;
}

//********************* Widget Functions ****************************************************************************************

/**
 * Get Plugin Widget Object From ID.
 *
 * This function gets the object instance of a plugin widget from its ID.
 *
 * @since 3.4.0
 *
 * @global array $wp_registered_widgets List of all registered widgets.
 *
 * @param string $widgetid The ID of the widget object instance.
 *
 * @return WP_Widget|false The widget object instance or false if not found.
 */
function wfu_get_widget_obj_from_id($widgetid) {
	global $wp_registered_widgets;

	if ( !isset($wp_registered_widgets[$widgetid]) ) return false;
	if ( !isset($wp_registered_widgets[$widgetid]['callback']) ) return false;
	if ( !isset($wp_registered_widgets[$widgetid]['callback'][0]) ) return false;
	$obj = $wp_registered_widgets[$widgetid]['callback'][0];
	if ( !($obj instanceof WP_Widget) ) return false;
	
	return $obj;	
}

//********************* Shortcode Options Functions ****************************************************************************************

/**
 * Adjust Shortcode Definitions For Multi-Occurrencies
 *
 * This function adjusts shortcode definitions so that more than one attribute
 * definition exists for components who appear more than one time in placements
 * attribute (like userdata).
 *
 * @since 3.3.0
 *
 * @param array $shortcode_atts The shortcode attributes.
 *
 * @return array The adjusted shortcode attributes.
 */
function wfu_shortcode_attribute_definitions_adjusted($shortcode_atts) {
	//get attribute definitions
	$defs = wfu_attribute_definitions();
	$defs_indexed = array();
	$defs_indexed_flat = array();
	foreach ( $defs as $def ) {
		$defs_indexed[$def["attribute"]] = $def;
		$defs_indexed_flat[$def["attribute"]] = $def["value"];
	}
	//get placement attribute from shortcode
	$placements = "";
	if ( isset($shortcode_atts["placements"]) ) $placements = $shortcode_atts["placements"];
	else $placements = $defs_indexed_flat["placements"];
	//get component definitions
	$components = wfu_component_definitions();
	//analyse components that can appear more than once in placements
	foreach ( $components as $component ) {
		if ( $component["multiplacements"] ) {
			$componentid = $component["id"];
			//count component occurrences in placements
			$component_occurrences = substr_count($placements, $componentid);
			if ( $component_occurrences > 1 && isset($defs_indexed[$componentid]) ) {
				//add incremented attribute definitions in $defs_indexed_flat
				//array if occurrences are more than one
				for ( $i = 2; $i <= $component_occurrences; $i++ ) {
					foreach ( $defs_indexed[$componentid]["dependencies"] as $attribute )
						$defs_indexed_flat[$attribute.$i] = $defs_indexed_flat[$attribute];
				}
			}
		}
	}
	
	return $defs_indexed_flat;
}

/**
 * Generate Shortcode Parameters Index.
 *
 * This function generates a unique index number for each shortcode parameters.
 * The function takes into account the current post ID, the shortcode ID and the
 * current user's username to construct the index. All identifiers are stored in
 * 'wfu_params_index' option. The index is used to store the shortcode
 * attributes in options table for later use.
 *
 * @since 2.1.2
 *
 * @global object $post The current Post object.
 *
 * @param int $shortcode_id The ID of the shortcode.
 * @param string $user_login The current user's username.
 *
 * @return string The index number of the shortcode parameters.
 */
function wfu_generate_current_params_index($shortcode_id, $user_login) {
	global $post;
	$cur_index_str = '||'.$post->ID.'||'.$shortcode_id.'||'.$user_login;
	$cur_index_str_search = '\|\|'.$post->ID.'\|\|'.$shortcode_id.'\|\|'.$user_login;
	$index_str = get_option('wfu_params_index');
	$index = explode("&&", $index_str);
	foreach ($index as $key => $value) if ($value == "") unset($index[$key]);
	$index_match = preg_grep("/".$cur_index_str_search."$/", $index);
	if ( count($index_match) == 1 )
		foreach ( $index_match as $key => $value )
			if ( $value == "" ) unset($index_match[$key]);
	if ( count($index_match) <= 0 ) {
		$cur_index_rand = wfu_create_random_string(16);
		array_push($index, $cur_index_rand.$cur_index_str);
	}
	else {
		reset($index_match);
		$cur_index_rand = substr(current($index_match), 0, 16);
		if ( count($index_match) > 1 ) {
			$index_match_keys = array_keys($index_match);
			for ($i = 1; $i < count($index_match); $i++) {
				$ii = $index_match_keys[$i];
				unset($index[array_search($index_match[$ii], $index, true)]);
			}
		}
	}
	if ( count($index_match) != 1 ) {
		$index_str = implode("&&", $index);
		update_option('wfu_params_index', $index_str);
	}
	return $cur_index_rand;
}

/**
 * Get Stored Shortcode Parameters.
 *
 * This function gets the shortcode parameters, stored in options table, from
 * its parameters index. Some times the index corresponds to 2 or more sets of
 * params, so an additional check, based on session token needs to be done in
 * order to find the correct one.
 *
 * @since 2.1.2
 *
 * @param string $params_index The parameters index.
 * @param string $session_token Optional. A session token used to find the
 *        correct params.
 *
 * @return array {
 *         The shortcode parameters.
 *
 *         $type string $unique_id The unique ID of the upload.
 *         $type int $page_id The ID of the page with the upload form.
 *         $type int $shortcode_id The ID of the shortcode.
 *         $type string $user_login The username of the user who made the
 *               upload.
 * }
 */
function wfu_get_params_fields_from_index($params_index, $session_token = "") {
	$fields = array();
	$index_str = get_option('wfu_params_index');
	$index = explode("&&", $index_str);
	$index_match = preg_grep("/^".$params_index."/", $index);
	if ( count($index_match) >= 1 )
		foreach ( $index_match as $key => $value )
			if ( $value == "" ) unset($index_match[$key]);
	if ( count($index_match) > 0 ) {
		if ( $session_token == "" ) {
			reset($index_match);
			list($fields['unique_id'], $fields['page_id'], $fields['shortcode_id'], $fields['user_login']) = explode("||", current($index_match));
		}
		//some times $params_index corresponds to 2 or more sets of params, so
		//we need to check session token in order to find the correct one
		else {
			$found = false;
			foreach ( $index_match as $value ) {
				list($fields['unique_id'], $fields['page_id'], $fields['shortcode_id'], $fields['user_login']) = explode("||", $value);
				$sid = $fields['shortcode_id'];
				if ( WFU_USVAR_exists("wfu_token_".$sid) && WFU_USVAR("wfu_token_".$sid) == $session_token ) {
					$found = true;
					break;
				}
			}
			if ( !$found ) $fields = array();
		}
	}
	return $fields; 
}

/**
 * Store Shortcode Data in User's Space.
 *
 * This function stores shortcode data in current user's user space (usually
 * session).
 *
 * @since 3.2.0
 *
 * @param array $data The shortcode data to store.
 *
 * @return string A unique code representing the stored data.
 */
function wfu_safe_store_shortcode_data($data) {
	$code = wfu_create_random_string(16);
	$safe_storage = ( WFU_USVAR_exists('wfu_shortcode_data_safe_storage') ? WFU_USVAR('wfu_shortcode_data_safe_storage') : array() );
	$safe_storage[$code] = $data;
	WFU_USVAR_store('wfu_shortcode_data_safe_storage', $safe_storage);
	return $code;
}

/**
 * Get Stored Shortcode Data from User's Space.
 *
 * This function gets stored shortcode data from current user's user space
 * (usually session).
 *
 * @since 3.2.0
 *
 * @param string $code A unique code representing the stored data.
 *
 * @return array $data The stored shortcode data.
 */
function wfu_get_shortcode_data_from_safe($code) {
	//sanitize $code
	$code = wfu_sanitize_code($code);
	if ( $code == "" ) return '';
	//return shortcode data from session variable, if exists
	if ( !WFU_USVAR_exists('wfu_shortcode_data_safe_storage') ) return '';
	$safe_storage = WFU_USVAR('wfu_shortcode_data_safe_storage');
	if ( !isset($safe_storage[$code]) ) return '';
	return $safe_storage[$code];
}

/**
 * Clear Stored Shortcode Data from User's Space.
 *
 * This function clears stored shortcode data from current user's user space
 * (usually session).
 *
 * @since 3.2.0
 *
 * @param string $code A unique code representing the stored data.
 */
function wfu_clear_shortcode_data_from_safe($code) {
	//sanitize $code
	$code = wfu_sanitize_code($code);
	if ( $code == "" ) return;
	//clear shortcode data from session variable, if exists
	if ( !WFU_USVAR_exists('wfu_shortcode_data_safe_storage') ) return;
	$safe_storage = WFU_USVAR('wfu_shortcode_data_safe_storage');
	if ( !isset($safe_storage[$code]) ) return;
	unset($safe_storage[$code]);
	WFU_USVAR_store('wfu_shortcode_data_safe_storage', $safe_storage);
}

/**
 * Decode Dimensions Shortcode Attribute.
 *
 * This function converts shortcode attributes keeping dimensions data from
 * string to array.
 *
 * @since 2.1.2
 *
 * @param string $dimensions_str The dimensions shortcode attribute.
 *
 * @return array An array of element dimension values.
 */
function wfu_decode_dimensions($dimensions_str) {
	$components = wfu_component_definitions();
	$dimensions = array();

	foreach ( $components as $comp ) {
		if ( $comp['dimensions'] == null ) $dimensions[$comp['id']] = "";
		else foreach ( $comp['dimensions'] as $dimraw ) {
			list($dim_id, $dim_name) = explode("/", $dimraw);
			$dimensions[$dim_id] = "";
		}
	}
	$dimensions_raw = explode(",", $dimensions_str);
	foreach ( $dimensions_raw as $dimension_str ) {
		$dimension_raw = explode(":", $dimension_str);
		$item = strtolower(trim($dimension_raw[0]));
		foreach ( array_keys($dimensions) as $key ) {
			if ( $item == $key ) $dimensions[$key] = trim($dimension_raw[1]);
		}
	}
	return $dimensions;
}

/**
 * Remove Item From Placements Attribute.
 *
 * This function correctly removes an item from placements attribute of the
 * uploader shortcode.
 *
 * @since 3.8.0
 *
 * @param string $placements The placements shortcode attribute.
 * @param string $item The item to remove.
 *
 * @return string The new placements attribute.
 */
function wfu_placements_remove_item($placements, $item) {
	$itemplaces = explode("/", $placements);
	$newplacements = array();
	foreach ( $itemplaces as $section ) {
		$items_in_section = explode("+", trim($section));
		$newsection = array();
		foreach ( $items_in_section as $item_in_section ) {
			$item_in_section = strtolower(trim($item_in_section));
			if ( $item_in_section != "" && $item_in_section != $item ) array_push($newsection, $item_in_section);
		}
		if ( count($newsection) > 0 ) array_push($newplacements, implode("+", $newsection));
	}
	if ( count($newplacements) > 0 ) return implode("/", $newplacements);
	else return "";
}

//********************* Plugin Design Functions ********************************************************************************************

/**
 * Get Uploader Form Template.
 *
 * This function gets the template that will be used to render the uploader form
 * of the plugin. If not template name is defined, the default template will be
 * used.
 *
 * @since 4.0.0
 *
 * @redeclarable
 *
 * @param string $templatename The template to use.
 *
 * @return object The template object to use.
 */
function wfu_get_uploader_template($templatename = "") {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	if ($templatename != "") {
		$classname = "WFU_UploaderTemplate_$templatename";
		if ( class_exists($classname) )
			return call_user_func(array($classname, 'get_instance'));
		$filepath = ABSWPFILEUPLOAD_DIR."templates/uploader-$templatename.php";
		if ( file_exists($filepath) ) {
			include_once $filepath;
			$classname = "WFU_UploaderTemplate_$templatename";
			if ( class_exists($classname) )
				return call_user_func(array($classname, 'get_instance'));
		}
	}
	return WFU_Original_Template::get_instance();
}

/**
 * Get Front-End File Viewer Template.
 *
 * This function gets the template that will be used to render the front-end
 * file viewer of the plugin. If not template name is defined, the default
 * template will be used.
 *
 * @since 4.0.0
 *
 * @redeclarable
 *
 * @param string $templatename The template to use.
 *
 * @return object The template object to use.
 */
function wfu_get_browser_template($templatename = "") {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	if ($templatename != "") {
		$classname = "WFU_BrowserTemplate_$templatename";
		if ( class_exists($classname) )
			return call_user_func(array($classname, 'get_instance'));
		$filepath = ABSWPFILEUPLOAD_DIR."templates/browser-$templatename.php";
		if ( file_exists($filepath) ) {
			include_once $filepath;
			$classname = "WFU_BrowserTemplate_$templatename";
			if ( class_exists($classname) )
				return call_user_func(array($classname, 'get_instance'));
		}
	}
	return WFU_Original_Template::get_instance();
}

/**
 * Add Section in Uploader Form.
 *
 * This function adds a section in uploader form with the elements passed in
 * parameters. The first parameter passed is an array of the shortcode
 * attributes. The next parameters are the items to add in the new section.
 *
 * @since 2.1.2
 *
 * @redeclarable
 *
 * @return string The HTML code of the new section.
 */
function wfu_add_div() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$items_count = func_num_args();
	if ( $items_count == 0 ) return "";
	$items_raw = func_get_args();
	$params = $items_raw[0];
	unset($items_raw[0]);
	$items = array( );
	foreach ( $items_raw as $item_raw ) {
		if ( is_array($item_raw) ) array_push($items, $item_raw);
	}
	$items_count = count($items);
	if ( $items_count == 0 ) return "";
	
	$template = wfu_get_uploader_template($params["uploadertemplate"]);
	$data["ID"] = $params["uploadid"];
	$data["responsive"] = ( $params["fitmode"] == "responsive" );
	$data["items"] = $items;
	$data["params"] = $params;

	ob_start();
	$template->wfu_row_container_template($data);
	$str_output = ob_get_clean();
	return $str_output;
}

/**
 * Generate Plugin Element Template Output.
 *
 * This function generates the output of a plugin's element based on the defined
 * template and the data that the element will have.
 *
 * @since 4.0.0
 *
 * @param string $blockname The name of the element.
 * @param array $data An array of data to pass to the element.
 *
 * @return array An array holding the output of element. The item 'css' of the
 *         array holds CSS code of the element. The item 'js' holds Javascript
 *         code of the element. Items 'line1', 'line2' and so on hold the lines
 *         of the HTML code of the element.
 */
function wfu_read_template_output($blockname, $data) {
	$output = array();
	if ( isset($data["params"]["uploadertemplate"]) ) $template = wfu_get_uploader_template($data["params"]["uploadertemplate"]);
	else $template = wfu_get_browser_template($data["params"]["browsertemplate"]);
	$func = "wfu_".$blockname."_template";
	$sid = $data["ID"];
	ob_start();
	call_user_func(array($template, $func), $data);
	$str_output = ob_get_clean();
	
	$str_output = str_replace('$ID', $sid, $str_output);
	//extract css, javascript and HTML from output
	$match = array();
	preg_match("/<style>(.*)<\/style><script.*?>(.*)<\/script>(.*)/s", $str_output, $match);
	if ( count($match) == 4 ) {
		$output["css"] = trim($match[1]);
		$output["js"] = trim($match[2]);
		$html = trim($match[3]);
		$i = 1;
		foreach( preg_split("/((\r?\n)|(\r\n?))/", $html) as $line )
			$output["line".$i++] = $line;
	}
	
	return $output;
}

/**
 * Generate Plugin Element Output.
 *
 * This function generates the final HTML code of a plugin's element that is
 * ready for output.
 *
 * @since 4.0.0
 *
 * @param string $blockname The name of the element.
 * @param array $params The shortcode attributes.
 * @param array $additional_params Additional parameters passed to the function
 *        specific to the element.
 * @param int $occurrence_index The occurrence index of the element, in case
 *        that placements attribute contains more than one occurrencies of this
 *        element.
 *
 * @return string The HTML code of the element.
 */
function wfu_template_to_HTML($blockname, $params, $additional_params, $occurrence_index) {
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$block = call_user_func("wfu_prepare_".$blockname."_block", $params, $additional_params, $occurrence_index);
	if ( isset($params["uploadid"]) ) {
		$ID = $params["uploadid"];
		$WF = "WFU";
	}
	else {
		$ID = $params["browserid"];
		$WF = "WFUB";
	}
	$css = $block["css"];
	if ( $block["js"] != "" ) {
		$js = 'var '.$WF.'_JS_'.$ID.'_'.$blockname.' = function() {';
		$js .= "\n".$block["js"];
		$js .= "\n".'}';
		$js .= "\n".'wfu_run_js("window", "'.$WF.'_JS_'.$ID.'_'.$blockname.'");';
	}
	//relax css rules if this option is enabled
	if ( $plugin_options['relaxcss'] == '1' ) $css = preg_replace('#.*?/\*relax\*/\s*#', '', $css);
	$echo_str = wfu_css_to_HTML($css);
	$echo_str .= "\n".wfu_js_to_HTML($js);
	$k = 1;
	while ( isset($block["line".$k]) ) {
		if ( $block["line".$k] != "" ) $echo_str .= "\n".$block["line".$k];
		$k++;
	}

	return $echo_str;
}

/**
 * Extract CSS and Javascript Code From Components.
 *
 * This function extracts CSS and Javascript code from a components array
 * holding its output.
 *
 * @since 4.0.0
 *
 * @param array $section_array The component output to analyse.
 * @param string $css The parameter to store extracted CSS code.
 * @param string $js The parameter to store extracted Javascript code.
 */
function wfu_extract_css_js_from_components($section_array, &$css, &$js) {
	for ( $i = 1; $i < count($section_array); $i++ ) {
		if ( isset($section_array[$i]["css"]) ) $css .= ( $css == "" ? "" : "\n" ).$section_array[$i]["css"];
		if ( isset($section_array[$i]["js"]) ) $js .= ( $js == "" ? "" : "\n" ).$section_array[$i]["js"];
	}
	return;
}

/**
 * Add Loading Overlay in Plugin's Form.
 *
 * This function adds an overlay onto a plugin's form (uploader form or file
 * viewer) that shows a 'loading' icon when necessary.
 *
 * @since 3.5.0
 *
 * @redeclarable
 *
 * @param string $dlp Tab prefix of each HTML line.
 * @param string $code A code string to uniquely identify the overlay.
 *
 * @return string The HTML code of the loading overlay.
 */
function wfu_add_loading_overlay($dlp, $code) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$echo_str = $dlp.'<div id="wfu_'.$code.'_overlay" style="margin:0; padding: 0; width:100%; height:100%; position:absolute; left:0; top:0; border:none; background:none; display:none;">';
	$echo_str .= $dlp."\t".'<div style="margin:0; padding: 0; width:100%; height:100%; position:absolute; left:0; top:0; border:none; background-color:rgba(255,255,255,0.8); z-index:1;""></div>';
	$echo_str .= $dlp."\t".'<table style="margin:0; padding: 0; table-layout:fixed; width:100%; height:100%; position:absolute; left:0; top:0; border:none; background:none; z-index:2;"><tbody><tr><td align="center" style="border:none;">';
	$echo_str .= $dlp."\t\t".'<img src="'.WFU_IMAGE_OVERLAY_LOADING.'" /><br /><span>loading...</span>';
	$echo_str .= $dlp."\t".'</td></tr></tbody></table>';
	$echo_str .= $dlp.'</div>';
	
	return $echo_str;
}

/**
 * Add Pagination Header in Plugin's Form.
 *
 * This function adds a pagination header onto a plugin's form (uploader form or
 * file viewer).
 *
 * @since 3.5.0
 *
 * @redeclarable
 *
 * @param string $dlp Tab prefix of each HTML line.
 * @param string $code A code string to uniquely identify the pagination header.
 * @param int $curpage The current page to show in the pagination header.
 * @param int $pages Number of pages of the pagination header.
 * @param bool $nonce Optional. If false then a nonce will also be created.
 *
 * @return string The HTML code of the pagination header.
 */
function wfu_add_pagination_header($dlp, $code, $curpage, $pages, $nonce = false) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	if ($nonce === false) $nonce = wp_create_nonce( 'wfu-'.$code.'-page' );
	$echo_str = $dlp.'<div style="float:right;">';
	$echo_str .= $dlp."\t".'<label id="wfu_'.$code.'_first_disabled" style="margin:0 4px; font-weight:bold; opacity:0.5; cursor:default; display:'.( $curpage == 1 ? 'inline' : 'none' ).';">&#60;&#60;</label>';
	$echo_str .= $dlp."\t".'<label id="wfu_'.$code.'_prev_disabled" style="margin:0 4px; font-weight:bold; opacity:0.5; cursor:default; display:'.( $curpage == 1 ? 'inline' : 'none' ).';">&#60;</label>';
	$echo_str .= $dlp."\t".'<a id="wfu_'.$code.'_first" href="javascript:wfu_goto_'.$code.'_page(\''.$nonce.'\', \'first\');" style="margin:0 4px; font-weight:bold; display:'.( $curpage == 1 ? 'none' : 'inline' ).';">&#60;&#60;</a>';
	$echo_str .= $dlp."\t".'<a id="wfu_'.$code.'_prev" href="javascript:wfu_goto_'.$code.'_page(\''.$nonce.'\', \'prev\');" style="margin:0 4px; font-weight:bold; display:'.( $curpage == 1 ? 'none' : 'inline' ).';">&#60;</a>';
	$echo_str .= $dlp."\t".'<label style="margin:0 0 0 4px; cursor:default;">'.WFU_PAGINATION_PAGE.'</label>';
	$echo_str .= $dlp."\t".'<select id="wfu_'.$code.'_pages" style="margin:0 4px;" onchange="wfu_goto_'.$code.'_page(\''.$nonce.'\', \'sel\');">';
	for ( $i = 1; $i <= $pages; $i++ )
		$echo_str .= $dlp."\t\t".'<option value="'.$i.'"'.( $i == $curpage ? ' selected="selected"' : '' ).'>'.$i.'</option>';
	$echo_str .= $dlp."\t".'</select>';
	$echo_str .= $dlp."\t".'<label style="margin:0 4px 0 0; cursor:default;">'.WFU_PAGINATION_OF.$pages.'</label>';
	$echo_str .= $dlp."\t".'<label id="wfu_'.$code.'_next_disabled" style="margin:0 4px; font-weight:bold; opacity:0.5; cursor:default; display:'.( $curpage == $pages ? 'inline' : 'none' ).';">&#62;</label>';
	$echo_str .= $dlp."\t".'<label id="wfu_'.$code.'_last_disabled" style="margin:0 4px; font-weight:bold; opacity:0.5; cursor:default; display:'.( $curpage == $pages ? 'inline' : 'none' ).';">&#62;&#62;</label>';
	$echo_str .= $dlp."\t".'<a id="wfu_'.$code.'_next" href="javascript:wfu_goto_'.$code.'_page(\''.$nonce.'\', \'next\');" style="margin:0 4px; font-weight:bold; display:'.( $curpage == $pages ? 'none' : 'inline' ).';">&#62;</a>';
	$echo_str .= $dlp."\t".'<a id="wfu_'.$code.'_last" href="javascript:wfu_goto_'.$code.'_page(\''.$nonce.'\', \'last\');" style="margin:0 4px; font-weight:bold; display:'.( $curpage == $pages ? 'none' : 'inline' ).';">&#62;&#62;</a>';
	$echo_str .= $dlp.'</div>';
	
	return $echo_str;
}

/**
 * Add Bulk Actions Header in Plugin's Form.
 *
 * This function adds a bulk actions header onto a plugin's form (file viewer).
 *
 * @since 3.8.5
 *
 * @redeclarable
 *
 * @param string $dlp Tab prefix of each HTML line.
 * @param string $code A code string to uniquely identify the bulk actions
 *        header.
 * @param array $actions {
 *        The list of actions of the bulk actions header.
 *
 *        $type string $name The name slug of the action.
 *        $type string $title The title of the action.
 * }
 *
 * @return string The HTML code of the bulk actions header.
 */
function wfu_add_bulkactions_header($dlp, $code, $actions) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$echo_str = $dlp.'<div style="float:left;">';
	$echo_str .= $dlp."\t".'<select id="wfu_'.$code.'_bulkactions">';
	$echo_str .= $dlp."\t\t".'<option value="" selected="selected">'.( substr($code, 0, 8) == "browser_" ? WFU_BROWSER_BULKACTION_TITLE : "Bulk Actions").'</option>';
	foreach ( $actions as $action )
		$echo_str .= $dlp."\t\t".'<option value="'.$action["name"].'">'.$action["title"].'</option>';
	$echo_str .= $dlp."\t".'</select>';
	$echo_str .= $dlp."\t".'<input type="button" class="button action" value="'.( substr($code, 0, 8) == "browser_" ? WFU_BROWSER_BULKACTION_LABEL : "Apply").'" onclick="wfu_apply_'.$code.'_bulkaction();" />';
	$echo_str .= $dlp."\t".'<img src="'.WFU_IMAGE_OVERLAY_LOADING.'" style="display:none;" />';
	$echo_str .= $dlp.'</div>';
	
	return $echo_str;
}

/**
 * Parse Colors From Color Template.
 *
 * This function converts a color template (color triplet) into an array of
 * color values.
 *
 * @since 2.1.2
 *
 * @param string $template A color template to parse.
 *
 * @return array {
 *         A triplet of color values.
 *
 *         $type string $color Text color value.
 *         $type string $bgcolor Background color value.
 *         $type string $borcolor Border color value.
 * }
 */
function wfu_prepare_message_colors($template) {
	$color_array = explode(",", $template);
	$colors['color'] = $color_array[0];
	$colors['bgcolor'] = $color_array[1];
	$colors['borcolor'] = $color_array[2];
	return $colors;
}

//********************* Email Functions ****************************************************************************************************

/**
 * Send Notification Email.
 *
 * This function sends a notification email after files have been uploaded.
 *
 * @since 2.1.2
 *
 * @global object $blog_id The ID of the current blog.
 *
 * @redeclarable
 *
 * @param object $user The user that uploaded the files.
 * @param array $uploaded_file_paths An array of full paths of the uploaded
 *        files.
 * @param array $userdata_fields An array of userdata fields, if any.
 * @param array $params The shortcode attributes.
 *
 * @return string Empty if operation was successful, an error message otherwise.
 */
function wfu_send_notification_email($user, $uploaded_file_paths, $userdata_fields, $params) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $blog_id;
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	
	//get consent status
	$consent_revoked = ( $plugin_options["personaldata"] == "1" && $params["consent_result"] == "0" );
	$not_store_files = ( $params["personaldatatypes"] == "userdata and files" );
	//create necessary variables
	$only_filename_list = "";
	$target_path_list = "";
	foreach ( $uploaded_file_paths as $filepath ) {
		$only_filename_list .= ( $only_filename_list == "" ? "" : ", " ).wfu_basename($filepath);
		$target_path_list .= ( $target_path_list == "" ? "" : ", " ).$filepath;
	}
	
	//apply wfu_before_email_notification filter
	$changable_data['recipients'] = $params["notifyrecipients"];
	$changable_data['subject'] = $params["notifysubject"];
	$changable_data['message'] = $params["notifymessage"];
	$changable_data['headers'] = $params["notifyheaders"];
	$changable_data['user_data'] = $userdata_fields;
	$changable_data['filename'] = $only_filename_list;
	$changable_data['filepath'] = $target_path_list;
	$changable_data['error_message'] = '';
	$additional_data['shortcode_id'] = $params["uploadid"];
	/**
	 * Customize Notification Email.
	 *
	 * This filter allows custom actions to modify the notification email
	 * that is sent after a file upload.
	 *
	 * @since 2.7.3
	 *
	 * @param array $changable_data {
	 *     Email parameters that can be changed.
	 *
	 *     @type string $recipients A comma-separated list of email recipients.
	 *     @type string $subject The email subject.
	 *     @type string $message The email body.
	 *     @type array $user_data Additional user data associated with the
	 *           uploaded files.
	 *     @type string $filename A comma-separated list of file names.
	 *     @type string $filepath A comma-separated list of file full paths.
	 *     @type string $error_message An error message that needs to be
	 *           populated in case the email must not be sent.
	 * }
	 * @param array $additional_data {
	 *     Additional parameters of the upload.
	 *
	 *     @type int $shortcode_id The plugin ID of the upload form.
	 * }
	 */
	$ret_data = apply_filters('wfu_before_email_notification', $changable_data, $additional_data);
	
	if ( $ret_data['error_message'] == '' ) {
		$notifyrecipients = $ret_data['recipients'];
		$notifysubject = $ret_data['subject'];
		$notifymessage = $ret_data['message'];
		$notifyheaders = $ret_data['headers'];
		$userdata_fields = $ret_data['user_data'];
		$only_filename_list = $ret_data['filename'];
		$target_path_list = $ret_data['filepath'];

		if ( 0 == $user->ID ) {
			$user_login = "guest";
			$user_email = "";
		}
		else {
			$user_login = $user->user_login;
			$user_email = $user->user_email;
		}
		$search = array ('/%useremail%/', '/%n%/', '/%dq%/', '/%brl%/', '/%brr%/');	 
		$replace = array ($user_email, "\n", "\"", "[", "]");
		foreach ( $userdata_fields as $userdata_key => $userdata_field ) { 
			$ind = 1 + $userdata_key;
			array_push($search, '/%userdata'.$ind.'%/');  
			array_push($replace, $userdata_field["value"]);
		}   
//		$notifyrecipients =  trim(preg_replace('/%useremail%/', $user_email, $params["notifyrecipients"]));
		$notifyrecipients =  preg_replace($search, $replace, $notifyrecipients);
		$search = array ('/%n%/', '/%dq%/', '/%brl%/', '/%brr%/');	 
		$replace = array ("\n", "\"", "[", "]");
		$notifyheaders =  preg_replace($search, $replace, $notifyheaders);
		$search = array ('/%username%/', '/%useremail%/', '/%filename%/', '/%filepath%/', '/%blogid%/', '/%pageid%/', '/%pagetitle%/', '/%n%/', '/%dq%/', '/%brl%/', '/%brr%/');	 
		$replace = array ($user_login, ( $user_email == "" ? "no email" : $user_email ), $only_filename_list, $target_path_list, $blog_id, $params["pageid"], get_the_title($params["pageid"]), "\n", "\"", "[", "]");
		foreach ( $userdata_fields as $userdata_key => $userdata_field ) { 
			$ind = 1 + $userdata_key;
			array_push($search, '/%userdata'.$ind.'%/');  
			array_push($replace, $userdata_field["value"]);
		}   
		$notifysubject = preg_replace($search, $replace, $notifysubject);
		$notifymessage = preg_replace($search, $replace, $notifymessage);

		if ( $params["attachfile"] == "true" ) {
			$notify_sent = wp_mail($notifyrecipients, $notifysubject, $notifymessage, $notifyheaders, $uploaded_file_paths); 
		}
		else {
			$notify_sent = wp_mail($notifyrecipients, $notifysubject, $notifymessage, $notifyheaders); 
		}
		//delete files if it is required by consent policy
		if ( $consent_revoked && $not_store_files ) {
			foreach ( $uploaded_file_paths as $file ) unlink($file);
		}
		return ( $notify_sent ? "" : WFU_WARNING_NOTIFY_NOTSENT_UNKNOWNERROR );
	}
	else return $ret_data['error_message'];
}

/**
 * Send Notification Email to Admin.
 *
 * This function sends a notification email to admin.
 *
 * @since 3.9.0
 *
 * @redeclarable
 *
 * @param string $subject The email subject.
 * @param string $message The emal message.
 */
function wfu_notify_admin($subject, $message) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$admin_email = get_option("admin_email");
	if ( $admin_email === false ) return;
	wp_mail($admin_email, $subject, $message);
}

//********************* Media Functions ****************************************************************************************************

/**
 * Create Media Attachment of Uploaded File.
 *
 * This function creates a media attachment and associates it with an uploaded
 * file.
 *
 * This function incorporates contributions from Aaron Olin who made some
 * corrections regarding the upload path.
 *
 * @since 2.2.1
 *
 * @redeclarable
 *
 * @param string $file_path The file path of the uploaded file.
 * @param array $userdata_fields Any userdata fields defined with the file.
 * @param int $page_id The ID of a page to link the attachment.
 *
 * @return int The ID of the created Media attachment.
 */
function wfu_process_media_insert($file_path, $userdata_fields, $page_id){
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$wp_upload_dir = wp_upload_dir();
	$filetype = wp_check_filetype( wfu_basename( $file_path ), null );

	$attachment = array(
		'guid'           => $wp_upload_dir['url'] . '/' . wfu_basename( $file_path ), 
		'post_mime_type' => $filetype['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', wfu_basename( $file_path ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	$attach_id = wp_insert_attachment( $attachment, $file_path, $page_id ); 
	
	// If file is an image, process the default thumbnails for previews
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
	// Add userdata as attachment metadata
	foreach ( $userdata_fields as $userdata_field )
		$attach_data["WFU User Data"][$userdata_field["label"]] = $userdata_field["value"];
	$update_attach = wp_update_attachment_metadata( $attach_id, $attach_data );
	// link attachment with file in plugin's database
	$filedata = wfu_get_filedata($file_path, true);
	if ( $filedata != null ) {
		$filedata["media"] = array(
			"type"		=> "data",
			"attach_id"	=> $attach_id
		);
		wfu_save_filedata_from_id($filedata["general"]["idlog"], $filedata);
	}

	return $attach_id;	
}

//********************* Form Fields Functions ****************************************************************************************************

/**
 * Parse Userdata Callback.
 *
 * This is a callback function used in userdata parsing.
 *
 * @since 3.3.1
 *
 * @param string $matches A preg_replace_callback() function match.
 *
 * @return string The processed $matches string.
 */
function wfu_preg_replace_callback_func($matches) {
	return str_replace("[/]", "/", $matches[0]);
}

/**
 * Parse Upload Form Userdata.
 *
 * This function parses userdatalabel attribute, which holds userdata fields
 * properties, into an array.
 *
 * @since 3.3.0
 *
 * @param string $value Upload form userdatalabel attribute.
 *
 * @return array {
 *         Parsed userdata fields properties.
 *
 *         $type array {
 *               Parsed userdata field properties.
 *
 *               $type string $type The type of the field.
 *               $type string $label The label of the field.
 *               $type string $labelposition The position of the label in
 *                     relation to the field.
 *               $type bool $required Field is required.
 *               $type bool $donotautocomplete Field must not be autocompleted.
 *               $type bool $validate Validate the field before upload.
 *               $type bool $typehook Apply a hook on the field while typing.
 *               $type string $hintposition The position of the hint text in
 *                     relation to the field.
 *               $type string $default The default value of the field.
 *               $type string $data A data property specific per field type.
 *               $type string $group The field is grouped with other fields.
 *               $type string $format Field format, specific per type.
 *         }
 * }
 */
function wfu_parse_userdata_attribute($value){
	$fields = array();
	//read defaults
	$definitions_unindexed = wfu_formfield_definitions();
	$defaults = array();
	foreach ( $definitions_unindexed as $def ) {
		$default = array();
		$default["type"] = $def["type"];
		$default["label"] = $def["label"];
		$default["labelposition"] = "".substr($def["labelposition"], 5);
		$default["required"] = ( substr($def["required"], 5) == "true" );
		$default["donotautocomplete"] = ( substr($def["donotautocomplete"], 5) == "true" );
		$default["validate"] = ( substr($def["validate"], 5) == "true" );
		$default["typehook"] = ( substr($def["typehook"], 5) == "true" );
		$default["hintposition"] = "".substr($def["hintposition"], 5);
		$default["default"] = "".substr($def["default"], 5);
		$default["data"] = "".substr($def["data"], 5);
		$default["group"] = "".substr($def["group"], 5);
		$default["format"] = "".substr($def["format"], 5);
		$defaults[$def["type"]] = $default;
	}
//	$fields_arr = explode("/", $value);
	$value = str_replace("/", "[/]", $value);
	$value = preg_replace_callback("/\(.*\)/", "wfu_preg_replace_callback_func", $value);
	$fields_arr = explode("[/]", $value);
	//parse shortcode attribute to $fields
	foreach ( $fields_arr as $field_raw ) {
		$field_raw = trim($field_raw);
		$fieldprops = $defaults["text"];
		//read old default attribute
		if ( substr($field_raw, 0, 1) == "*" ) {
			$fieldprops["required"] = true;
			$field_raw = substr($field_raw, 1);
		}
		$field_parts = explode("|", $field_raw);
		//proceed if the first part, which is the label, is non-empty
		if ( trim($field_parts[0]) != "" ) {
			//get type, if exists, in order to adjust defaults
			$type_key = -1;
			$new_type = "";
			foreach ( $field_parts as $key => $part ) {
				$part = ltrim($part);
				$flag = substr($part, 0, 2);
				$val = substr($part, 2);
				if ( $flag == "t:" && $key > 0 && array_key_exists($val, $defaults) ) {
					$new_type = $val;
					$type_key = $key;
					break;
				}
			}
			if ( $new_type != "" ) {
				$fieldprops = $defaults[$new_type];
				unset($field_parts[$type_key]);
			}
			//store label
			$fieldprops["label"] = trim($field_parts[0]);
			unset($field_parts[0]);
			//get other properties
			foreach ( $field_parts as $part ) {
				$part = ltrim($part);
				$flag = substr($part, 0, 2);
				$val = "".substr($part, 2);
				if ( $flag == "s:" ) $fieldprops["labelposition"] = $val;
				elseif ( $flag == "r:" ) $fieldprops["required"] = ( $val == "1" );
				elseif ( $flag == "a:" ) $fieldprops["donotautocomplete"] = ( $val == "1" );
				elseif ( $flag == "v:" ) $fieldprops["validate"] = ( $val == "1" );
				elseif ( $flag == "d:" ) $fieldprops["default"] = $val;
				elseif ( $flag == "l:" ) $fieldprops["data"] = $val;
				elseif ( $flag == "g:" ) $fieldprops["group"] = $val;
				elseif ( $flag == "f:" ) $fieldprops["format"] = $val;
				elseif ( $flag == "p:" ) $fieldprops["hintposition"] = $val;
				elseif ( $flag == "h:" ) $fieldprops["typehook"] = ( $val == "1" );
			}
			array_push($fields, $fieldprops);
		}
	}

	return $fields;	
}

/**
 * Checke and Remove Honeypot Fields.
 *
 * The plugin uses honeypot userdata fields as an additional security measure
 * against bots. A honeypot is a field which is not visible to the user, but it
 * can be filled with a value. A human will not see the field, so it will not
 * fill it with data. On the other hand, a bot does not care about visibility.
 * If the field has a common name, like 'url' or 'website' it will think that it
 * is a normal field and will fill it with data. In this case the upload will
 * fail silently (the bot will think that it succeeded). If the honeypot field
 * is empty, then the upload will continue normally, however it will be removed
 * from userdata fields list because it is not necessary anymore.
 *
 * @since 4.10.1
 *
 * @param array $userdata_fields An array of userdata fields.
 * @param string $post_key A string to locate the value of the honeypot field
 *        in received POST parameters.
 *
 * @return bool True if the honeypot field is filled, false otherwise.
 */
function wfu_check_remove_honeypot_fields(&$userdata_fields, $post_key) {
	//check if honeypot userdata fields have been added to the form and if they
	//contain any data
	$honeypot_filled = false;
	foreach ( $userdata_fields as $userdata_key => $userdata_field ) {
		if ( $userdata_field["type"] == "honeypot" ) {
			$val = ( isset($_POST[$post_key.$userdata_key]) ? $_POST[$post_key.$userdata_key] : "" );
			//if a non-zero value has been passed to the server, this means
			//that it has been filled by a bot
			if ( $val != "" ) {
				$honeypot_filled = true;
				break;
			}
			//if the honeypot field is empty then remove it from
			//userdata_fields array because we do not want to be stored
			else unset($userdata_fields[$userdata_key]);
		}
	}
	
	//if any honeypot field has been filled then return true to denote that
	//the upload must be aborted
	return $honeypot_filled;
}

//************************* Cookie Functions ***********************************

/**
 * Read Session Cookie.
 *
 * This function reads the session cookie of the plugin that is used to store
 * user state information when User State handler is set to 'dboption'.
 *
 * @since 4.12.0
 *
 * @return string The session ID.
 */
function wfu_get_session_cookie() {
	return isset($_COOKIE[WPFILEUPLOAD_COOKIE]) ? wfu_sanitize_code(substr($_COOKIE[WPFILEUPLOAD_COOKIE], 0, 32)) : "";
}

/**
 * Set Session Cookie.
 *
 * This function sets the session cookie of the plugin that is used to store
 * user state information when User State handler is set to 'dboption'. This
 * function generates a session ID that composes of a random 32-digit string.
 *
 * @since 4.12.0
 *
 * @redeclarable
 */
function wfu_set_session_cookie() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	if ( !headers_sent() ) {
		$cookie = wfu_create_random_string(32);
		setcookie(
			WPFILEUPLOAD_COOKIE,
			$cookie,
			time() + intval(WFU_VAR("WFU_US_COOKIE_LIFE")) * 3600,
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			false,
			false
		);
		$_COOKIE[WPFILEUPLOAD_COOKIE] = $cookie;
	}
}

//********************* User State Functions ***********************************

/**
 * Initialize User State.
 *
 * This function initializes the user state. If user state handler is 'dboption'
 * then it sets the session cookie. If it is 'session' it starts the session
 * now or on demand, depending on 'WFU_US_SESSION_LEGACY' variable.
 *
 * @since 4.12.0
 *
 * @global string $wfu_user_state_handler The defined User State handler.
 *
 * @redeclarable
 */
function wfu_initialize_user_state() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "dboption" && WFU_VAR("WFU_US_DBOPTION_BASE") == "cookies" ) {
		if ( wfu_get_session_cookie() == "" ) wfu_set_session_cookie();
	}
	elseif ( WFU_VAR("WFU_US_SESSION_LEGACY") == "true" && !headers_sent() && ( function_exists("session_status") ? ( PHP_SESSION_ACTIVE !== session_status() ) : ( empty(session_id()) ) ) ) { session_start(); }
}

/**
 * Check if User State Variable Exists.
 *
 * This function checks if a variable exists in User State.
 *
 * @since 4.3.2
 *
 * @global string $wfu_user_state_handler The defined User State handler.
 *
 * @redeclarable
 *
 * @param string $var The variable to check.
 *
 * @return bool True if the variable exists, false otherwise.
 */
function WFU_USVAR_exists($var) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "dboption" ) 
		return ( WFU_VAR("WFU_US_DBOPTION_USEOLD") == "false" ? WFU_USVAR_exists_dboption($var) : WFU_USVAR_exists_dboption_old($var) );
	else return WFU_USVAR_exists_session($var);
}

/**
 * Get Variable From User State.
 *
 * This function gets the value of a variable from User State.
 *
 * @since 4.3.2
 *
 * @global string $wfu_user_state_handler The defined User State handler.
 *
 * @redeclarable
 *
 * @param string $var The variable to get.
 *
 * @return mixed The value of the variable.
 */
function WFU_USVAR($var) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "dboption" )
		return ( WFU_VAR("WFU_US_DBOPTION_USEOLD") == "false" ? WFU_USVAR_dboption($var) : WFU_USVAR_dboption_old($var) );
	else return WFU_USVAR_session($var);
}

/**
 * Get All User State Variables.
 *
 * This function gets the values of all User State variables.
 *
 * @since 4.3.2
 *
 * @global string $wfu_user_state_handler The defined User State handler.
 *
 * @redeclarable
 *
 * @return array An array of all User State variables.
 */
function WFU_USALL() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "dboption" )
		return ( WFU_VAR("WFU_US_DBOPTION_USEOLD") == "false" ? WFU_USALL_dboption() : WFU_USALL_dboption_old() );
	else return WFU_USALL_session();
}

/**
 * Store Variable In User State.
 *
 * This function stores the value of a variable in User State.
 *
 * @since 4.3.2
 *
 * @global string $wfu_user_state_handler The defined User State handler.
 *
 * @redeclarable
 *
 * @param string $var The variable to store.
 * @param mixed $value The value of the variable.
 */
function WFU_USVAR_store($var, $value) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "dboption" )
		( WFU_VAR("WFU_US_DBOPTION_USEOLD") == "false" ? WFU_USVAR_store_dboption($var, $value) : WFU_USVAR_store_dboption_old($var, $value) );
	else WFU_USVAR_store_session($var, $value);
}

/**
 * Remove Variable From User State.
 *
 * This function removes a variable from User State.
 *
 * @since 4.3.2
 *
 * @global string $wfu_user_state_handler The defined User State handler.
 *
 * @redeclarable
 *
 * @param string $var The variable to remove.
 */
function WFU_USVAR_unset($var) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "dboption" )
		( WFU_VAR("WFU_US_DBOPTION_USEOLD") == "false" ? WFU_USVAR_unset_dboption($var) : WFU_USVAR_unset_dboption_old($var) );
	else WFU_USVAR_unset_session($var);
}

/**
 * Check if Session Variable Exists.
 *
 * This function checks if a variable exists in Session.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to check.
 *
 * @return bool True if the variable exists, false otherwise.
 */
function WFU_USVAR_exists_session($var) {
	$session_id = session_id();
	$open_session = ( WFU_VAR("WFU_US_SESSION_LEGACY") != "true" && ( function_exists("session_status") ? ( PHP_SESSION_ACTIVE !== session_status() ) : ( empty(session_id()) ) ) );
	if ( $open_session ) session_start();
	$exists = isset($_SESSION[$var]);
	if ( $open_session ) session_write_close();
	return $exists;
}

/**
 * Get Variable From Session.
 *
 * This function gets the value of a variable from Session.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to get.
 *
 * @return mixed The value of the variable.
 */
function WFU_USVAR_session($var) {
	$session_id = session_id();
	$open_session = ( WFU_VAR("WFU_US_SESSION_LEGACY") != "true" && ( function_exists("session_status") ? ( PHP_SESSION_ACTIVE !== session_status() ) : ( empty(session_id()) ) ) );
	if ( $open_session ) session_start();
	$value = $_SESSION[$var];
	if ( $open_session ) session_write_close();
	return $value;
}

/**
 * Get All Session Variables.
 *
 * This function gets the values of all Session variables.
 *
 * @since 4.4.0
 *
 * @return array An array of all Session variables.
 */
function WFU_USALL_session() {
	$session_id = session_id();
	$open_session = ( WFU_VAR("WFU_US_SESSION_LEGACY") != "true" && ( function_exists("session_status") ? ( PHP_SESSION_ACTIVE !== session_status() ) : ( empty(session_id()) ) ) );
	if ( $open_session ) session_start();
	$all = $_SESSION;
	if ( $open_session ) session_write_close();
	return $all;
}

/**
 * Store Variable In Session.
 *
 * This function stores the value of a variable in Session.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to store.
 * @param mixed $value The value of the variable.
 */
function WFU_USVAR_store_session($var, $value) {
	$session_id = session_id();
	$open_session = ( WFU_VAR("WFU_US_SESSION_LEGACY") != "true" && ( function_exists("session_status") ? ( PHP_SESSION_ACTIVE !== session_status() ) : ( empty(session_id()) ) ) );
	if ( $open_session ) session_start();
	$_SESSION[$var] = $value;
	if ( $open_session ) session_write_close();
}

/**
 * Remove Variable From Session.
 *
 * This function removes a variable from Session.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to remove.
 */
function WFU_USVAR_unset_session($var) {
	$session_id = session_id();
	$open_session = ( WFU_VAR("WFU_US_SESSION_LEGACY") != "true" && ( function_exists("session_status") ? ( PHP_SESSION_ACTIVE !== session_status() ) : ( empty(session_id()) ) ) );
	if ( $open_session ) session_start();
	unset($_SESSION[$var]);
	if ( $open_session ) session_write_close();
}

/**
 * Get Session ID.
 *
 * This function gets session ID depending on the user state handler and
 * relevant advanced variables.
 *
 * @since 4.12.0
 *
 * @global string $wfu_user_state_handler The defined User State handler.
 *
 * @return string The Session ID.
 */
function wfu_get_session_id() {
	global $wfu_user_state_handler;
	$key = "";
	if ( ( $wfu_user_state_handler == "dboption" && WFU_VAR("WFU_US_DBOPTION_BASE") == "session" ) || $wfu_user_state_handler != "dboption" ) {
		$key = session_id();
		if ( WFU_VAR("WFU_US_SESSION_LEGACY") != "true" && ( function_exists("session_status") ? ( PHP_SESSION_ACTIVE !== session_status() ) : ( empty(session_id()) ) ) ) {
			session_start();
			$key = session_id();
			session_write_close();
		}
	}
	elseif ( $wfu_user_state_handler == "dboption" && WFU_VAR("WFU_US_DBOPTION_BASE") == "cookies" )
		$key = wfu_get_session_cookie();
	return $key;
}

/**
 * Flatten Session ID.
 *
 * This function removes dots and other symbols from session ID.
 *
 * @since 4.4.0
 *
 * @return string Flattened Session ID.
 */
function wfu_get_safe_session_id() {
	return preg_replace("/[^a-z0-9_]/", "", strtolower(wfu_get_session_id()));
}

/**
 * Get DB Option Data.
 *
 * This function gets User State data for a specific session, stored in the
 * website's database.
 *
 * @since 4.4.0
 *
 * @param string $id The Session ID.
 * @param string $default Optional. Default value for the data.
 * @param string $type Optional. The type of data value.
 *
 * @return array The DB Option data.
 */
function wfu_get_US_dboption_data($id, $default = false, $type = "array") {
	if ( $id == "" ) return false;
	return wfu_get_option("wfu_userstate_".$id, $default, $type);
}

/**
 * Update DB Option Time.
 *
 * This function updates the time that DB Option data of a specific Session
 * where last used.
 *
 * @since 4.4.0
 *
 * @param string $id The Session ID.
 */
function wfu_update_US_dboption_time($id) {
	$list = wfu_get_option("wfu_userstate_list", array());
	$list[$id] = time();
	wfu_update_option("wfu_userstate_list", $list);
}

/**
 * Check if Variable Exists in DB Option (old handler).
 *
 * This function checks if a variable exists in DB Option.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to check.
 *
 * @return bool True if the variable exists, false otherwise.
 */
function WFU_USVAR_exists_dboption_old($var) {
	$id = wfu_get_safe_session_id();
	$data = wfu_get_US_dboption_data($id);
	if ( $data === false ) return false;
	wfu_update_US_dboption_time($id);
	return isset($data[$var]);
}

/**
 * Check if Variable Exists in DB Option.
 *
 * This function checks if a variable exists in DB Option.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to check.
 *
 * @return bool True if the variable exists, false otherwise.
 */
function WFU_USVAR_exists_dboption($var) {
	$id = wfu_get_safe_session_id();
	if ( $id == "" ) return false;
	$exists = wfu_option_item_exists("wfu_userstate_".$id, $var);
	wfu_update_US_dboption_time($id);
	if ( $exists === null ) return false;
	else return $exists;
}

/**
 * Get Variable From DB Option (old handler).
 *
 * This function gets the value of a variable from DB Option.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to get.
 *
 * @return mixed The value of the variable.
 */
function WFU_USVAR_dboption_old($var) {
	$id = wfu_get_safe_session_id();
	$data = wfu_get_US_dboption_data($id);
	if ( $data === false ) return "";
	wfu_update_US_dboption_time($id);
	return $data[$var];
}

/**
 * Get Variable From DB Option.
 *
 * This function gets the value of a variable from DB Option.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to get.
 *
 * @return mixed The value of the variable.
 */
function WFU_USVAR_dboption($var) {
	$id = wfu_get_safe_session_id();
	if ( $id == "" ) return "";
	$value = wfu_get_option_item("wfu_userstate_".$id, $var);
	wfu_update_US_dboption_time($id);
	if ( $value === null ) return "";
	else return wfu_decode_array_from_string($value);
}

/**
 * Get All DB Option Variables (old handler).
 *
 * This function gets the values of all DB Option variables.
 *
 * @since 4.4.0
 *
 * @return array An array of all DB Option variables.
 */
function WFU_USALL_dboption_old() {
	$id = wfu_get_safe_session_id();
	$data = wfu_get_US_dboption_data($id);
	if ( $data === false ) return array();
	wfu_update_US_dboption_time($id);
	return $data;
}

/**
 * Get All DB Option Variables.
 *
 * This function gets the values of all DB Option variables.
 *
 * @since 4.4.0
 *
 * @return array An array of all DB Option variables.
 */
function WFU_USALL_dboption() {
	$id = wfu_get_safe_session_id();
	$data = wfu_get_US_dboption_data($id, false, "string");
	if ( $data === null ) return array();
	wfu_update_US_dboption_time($id);
	$arr = preg_split("/\[([^\]]*\][^{]*){[^}]*}/", $data, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	$data_arr = array();
	foreach ( $arr as $item ) {
		list($key, $value) = explode("]", $item);
		$data_arr[$key] = wfu_decode_array_from_string($value);
	}
	return $data_arr;
}

/**
 * Store Variable In DB Option (old handler).
 *
 * This function stores the value of a variable in DB Option.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to store.
 * @param mixed $value The value of the variable.
 */
function WFU_USVAR_store_dboption_old($var, $value) {
	$id = wfu_get_safe_session_id();
	$data = wfu_get_US_dboption_data($id, array());
	if ( $data === false ) return;
	$data[$var] = $value;
	wfu_update_option("wfu_userstate_".$id, $data);
	wfu_update_US_dboption_time($id);
	wfu_update_US_dboption_list();
}

/**
 * Store Variable In DB Option.
 *
 * This function stores the value of a variable in DB Option.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to store.
 * @param mixed $value The value of the variable.
 */
function WFU_USVAR_store_dboption($var, $value) {
	$id = wfu_get_safe_session_id();
	if ( $id == "" ) return;
	wfu_update_option_item("wfu_userstate_".$id, $var, wfu_encode_array_to_string($value));
	wfu_update_US_dboption_time($id);
	wfu_update_US_dboption_list();
}

/**
 * Remove Variable From DB Option (old handler).
 *
 * This function removes a variable from DB Option.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to remove.
 */
function WFU_USVAR_unset_dboption_old($var) {
	$id = wfu_get_safe_session_id();
	$data = wfu_get_US_dboption_data($id);
	if ( $data === false ) return;
	unset($data[$var]);
	wfu_update_option("wfu_userstate_".$id, $data);
	wfu_update_US_dboption_time($id);
}

/**
 * Remove Variable From DB Option.
 *
 * This function removes a variable from DB Option.
 *
 * @since 4.4.0
 *
 * @param string $var The variable to remove.
 */
function WFU_USVAR_unset_dboption($var) {
	$id = wfu_get_safe_session_id();
	if ( $id == "" ) return;
	wfu_delete_option_item("wfu_userstate_".$id, $var);
	wfu_update_US_dboption_time($id);
}

/**
 * Update DB Option List.
 *
 * This function checks when all DB Option Data were last used. DB Option data
 * that were last used before a long time, means that their Session has expired,
 * so they are not useful anymore and will be removed.
 *
 * @since 4.4.0
 */
function wfu_update_US_dboption_list() {
	$last_check_interval = time() - wfu_get_option("wfu_userstate_list_last_check", 0);
	$limit = WFU_VAR("WFU_US_DBOPTION_CHECK");
	if ( $last_check_interval < $limit ) return;
	
	$list = wfu_get_option("wfu_userstate_list", array());
	$changed = false;
	$limit = WFU_VAR("WFU_US_DBOPTION_LIFE");
	foreach ( $list as $id => $time ) {
		$interval = time() - $time;
		if ( $interval > $limit ) {
			$changed = true;
			unset($list[$id]);
			wfu_delete_option("wfu_userstate_".$id);
		}
	}
	if ( $changed ) wfu_update_option("wfu_userstate_list", $list);
	wfu_update_option("wfu_userstate_list_last_check", time());
}

//********************* Javascript Related Functions ****************************************************************************************************

/**
 * Inject Javascript Code.
 *
 * This function generates HTML output for injecting Javascript code. After
 * execution of the code, the HTML output is erased leaving no traces.
 *
 * @since 3.3.0
 *
 * @param string $code The Javascript code to inject.
 *
 * @return string The HTML output.
 */
function wfu_inject_js_code($code){
	$id = 'code_'.wfu_create_random_string(8);
	$html = '<div id="'.$id.'" style="display:none;"><script type="text/javascript">'.$code.'</script><script type="text/javascript">var div = document.getElementById("'.$id.'"); div.parentNode.removeChild(div);</script></div>';

	return $html;	
}

//********************* Consent Functions ****************************************************************************************************

/**
 * Get Consent Status of User.
 *
 * This function gets the consent status of a user.
 *
 * @since 4.5.0
 *
 * @param WPUser $user The user to get its consent status.
 *
 * @return string The consent status of the user:
 *         "1": the user has given its consent.
 *         "0": the user has not given its consent.
 *         "": the user has not answered to consent question.
 */
function wfu_check_user_consent($user) {
	//returns empty string if user has not completed consent question yet, "1"
	//if user has given consent, "0" otherwise
	$result = "";
	if ( $user->ID > 0 ) {
		//check in user meta for consent
		$data = get_the_author_meta( 'WFU_Consent_Data', $user->ID );
		if ( $data && isset($data["consent_status"]) )
			$result = $data["consent_status"];
	}
	else {
		//check in user state for consent
		if ( WFU_USVAR_exists('WFU_Consent_Data') ) {
			$data = WFU_USVAR('WFU_Consent_Data');
			if ( isset($data["consent_status"]) )
				$result = $data["consent_status"];
		}
	}
	
	return $result;
}

/**
 * Update Consent Status of User From Front-End.
 *
 * This function updates the consent status of a user when asked through an
 * upload form. If user is logged in, then consent status is stored in its
 * profile. If the user is not logged in, then consent status is store in User
 * State.
 *
 * @since 4.5.0
 *
 * @param WPUser $user The user to store its consent status.
 * @param string $consent_result The new consent status. It can be "yes", "no"
 *        or "".
 */
function wfu_update_user_consent($user, $consent_result) {
	if ( $user->ID > 0 ) {
		//check in user meta for consent
		$data = get_the_author_meta( 'WFU_Consent_Data', $user->ID );
		if ( !$data ) $data = array();
		$data["consent_status"] = ( $consent_result == "yes" ? "1" : ( $consent_result == "no" ? "0" : "" ) );
		update_user_meta( $user->ID, 'WFU_Consent_Data', $data );
	}
	else {
		//check in user state for consent
		if ( WFU_USVAR_exists('WFU_Consent_Data') ) $data = WFU_USVAR('WFU_Consent_Data');
		else $data = array();
		$data["consent_status"] = ( $consent_result == "yes" ? "1" : ( $consent_result == "no" ? "0" : "" ) );
		WFU_USVAR_store( 'WFU_Consent_Data', $data );
	}
}

/**
 * Show Consent Status Fields in User's Profile Page.
 *
 * This function outputs the HTML code of the consent status fields shown in
 * user's profile page.
 *
 * @since 4.5.0
 *
 * @param WPUser $user The involved user.
 */
function wfu_show_consent_profile_fields($user) {
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	if ( $plugin_options["personaldata"] != "1" ) return;
	
	$data = get_the_author_meta( 'WFU_Consent_Data', $user->ID );
	if ( !$data ) $data = array();
	if ( !isset($data["consent_status"]) ) $data["consent_status"] = "";
	$status = $data["consent_status"];
	
	$echo_str = "\n\t".'<h3>'.esc_html__( 'Wordpress File Upload Consent Status', 'wp-file-upload' ).'</h3>';
	$echo_str .= "\n\t".'<table class="form-table">';
	$echo_str .= "\n\t\t".'<tr>';
	$echo_str .= "\n\t\t\t".'<th><label>'.esc_html__( 'Consent Status', 'wp-file-upload' ).'</label></th>';
	$echo_str .= "\n\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t".'<label style="font-weight: bold;">'.( $status == "1" ? esc_html__( 'You have given your consent to store personal data.', 'wp-file-upload' ) : ( $status == "0" ? esc_html__( 'You have denied to store personal data.', 'wp-file-upload' ) : esc_html__( 'You have not answered to consent yet.', 'wp-file-upload' ) ) ).'</label>';
	$echo_str .= "\n\t\t\t".'</td>';
	$echo_str .= "\n\t\t".'</tr>';
	$echo_str .= "\n\t\t".'<tr>';
	$echo_str .= "\n\t\t\t".'<th></th>';
	$echo_str .= "\n\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t".'<label>'.esc_html__( 'Change status to', 'wp-file-upload' ).'</label>';
	$echo_str .= "\n\t\t\t\t".'<select name="consent_status">';
	$echo_str .= "\n\t\t\t\t\t".'<option value="-1" selected="selected">'.esc_html__( 'No change', 'wp-file-upload' ).'</option>';
	if ( $status == "1" ) {
		$echo_str .= "\n\t\t\t\t\t".'<option value="0">'.esc_html__( 'Revoke Consent', 'wp-file-upload' ).'</option>';
		$echo_str .= "\n\t\t\t\t\t".'<option value="">'.esc_html__( 'Clear Consent', 'wp-file-upload' ).'</option>';
	}
	elseif ( $status == "0" ) {
		$echo_str .= "\n\t\t\t\t\t".'<option value="1">'.esc_html__( 'Give Consent', 'wp-file-upload' ).'</option>';
		$echo_str .= "\n\t\t\t\t\t".'<option value="">'.esc_html__( 'Clear Consent', 'wp-file-upload' ).'</option>';
	}
	if ( $status == "" ) {
		$echo_str .= "\n\t\t\t\t\t".'<option value="0">'.esc_html__( 'Revoke Consent', 'wp-file-upload' ).'</option>';
		$echo_str .= "\n\t\t\t\t\t".'<option value="1">'.esc_html__( 'Give Consent', 'wp-file-upload' ).'</option>';
	}
	$echo_str .= "\n\t\t\t\t".'</select>';
	$echo_str .= "\n\t\t\t".'</td>';
	$echo_str .= "\n\t\t".'</tr>';
	/*
	if ( current_user_can( 'manage_options' ) ) {
		$echo_str .= "\n\t\t".'<tr>';
		$echo_str .= "\n\t\t\t".'<th><label>'.esc_html__( 'Personal Data Operations', 'wp-file-upload' ).'</label></th>';
		$echo_str .= "\n\t\t\t".'<td>';
		$echo_str .= "\n\t\t\t\t".'<input id="wfu_download_file_nonce" type="hidden" value="'.wp_create_nonce('wfu_download_file_invoker').'" />';
		$echo_str .= "\n\t\t\t\t".'<button type="button" class="button" onclick="wfu_download_file(\'exportdata\', 1);">'.esc_html__( 'Export User Data', 'wp-file-upload' ).'</button>';
		$echo_str .= "\n\t\t\t".'</td>';
		$echo_str .= "\n\t\t".'</tr>';
	}*/
	$echo_str .= "\n\t".'</table>';
	
	echo $echo_str;
}

/**
 * Update Consent Status of User From Back-End.
 *
 * This function updates the consent status of a user from its User Profile
 * page.
 *
 * @since 4.5.0
 *
 * @param int $user_id The ID of the involved user.
 */
function wfu_update_consent_profile_fields( $user_id ) {
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	if ( $plugin_options["personaldata"] != "1" ) return false;

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	$status = $_POST['consent_status'];
	if ( $status == '1' || $status == '0' || $status == '' ) {
		$data = get_the_author_meta( 'WFU_Consent_Data', $user_id );
		if ( !$data ) $data = array();
		$data["consent_status"] = $status;
		update_user_meta( $user_id, 'WFU_Consent_Data', $data );
	}
}

//********************* Browser Functions ****************************************************************************************************

/**
 * Store Front-End File Viewer Shortcode Attributes.
 *
 * This function stores the shortcode attributes of a front-end file viewer in
 * User Space for future retrieval.
 *
 * @since 3.6.1
 *
 * @param string $params The front-end file viewer shortcode attributes.
 *
 * @return string A unique code representing the stored shortcode.
 */
function wfu_safe_store_browser_params($params) {
	$code = wfu_create_random_string(16);
	$safe_storage = ( WFU_USVAR_exists('wfu_browser_actions_safe_storage') ? WFU_USVAR('wfu_browser_actions_safe_storage') : array() );
	$safe_storage[$code] = $params;
	WFU_USVAR_store('wfu_browser_actions_safe_storage', $safe_storage);
	return $code;
}

/**
 * Retrieve Stored Front-End File Viewer Shortcode Attributes.
 *
 * This function retrieved stored shortcode attributes of a front-end file
 * viewer  from User Space.
 *
 * @since 3.6.1
 *
 * @param string $code A unique code representing the stored shortcode.
 *
 * @return string The stored shortcode attributes.
 */
function wfu_get_browser_params_from_safe($code) {
	//sanitize $code
	$code = wfu_sanitize_code($code);
	if ( $code == "" ) return false;
	//return params from session variable, if exists
	if ( !WFU_USVAR_exists('wfu_browser_actions_safe_storage') ) return false;
	$safe_storage = WFU_USVAR('wfu_browser_actions_safe_storage');
	if ( !isset($safe_storage[$code]) ) return false;
	return $safe_storage[$code];
}

//********************* POST/GET Requests Functions ****************************************************************************************************

/**
 * Add Proxy in HTTP Request.
 *
 * This function adds proxy information inside an HTTP request configuration, if
 * proxy information is defined inside the website's configuration and if it is
 * active.
 *
 * @since 4.10.0
 *
 * @param array $config An HTTP request configuration structure.
 *
 * @return bool True if proxy is enabled and added, false otherwise.
 */
function wfu_add_proxy_param(&$config) {
	//include proxy support
	$proxy = new \WP_HTTP_Proxy();
	$proxy_enabled = $proxy->is_enabled();
	if ( $proxy_enabled ) {
		$config['proxy']['http'] = 'http://'.( $proxy->use_authentication() ? $proxy->authentication().'@' : '' ).$proxy->host().":".$proxy->port();
		$config['proxy']['https'] = 'http://'.( $proxy->use_authentication() ? $proxy->authentication().'@' : '' ).$proxy->host().":".$proxy->port();
		//make sure that wildcard asterisks (*) are removed from bypass hosts
		//to make it compatible with Guzzle format
		if ( defined('WP_PROXY_BYPASS_HOSTS') ) $config['proxy']['no'] = preg_split('|,\s*|', str_replace('*', '', WP_PROXY_BYPASS_HOSTS));
	}
	
	return $proxy_enabled;
}

/**
 * Parse Socket HTTP Response.
 *
 * This function tries to decode an HTTP response received through sockets and
 * return the clean response data.
 *
 * @since 3.10.0
 *
 * @param string $response The raw sockets HTTP response.
 *
 * @return string The clean HTTP response data.
 */
function wfu_decode_socket_response($response) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$ret = "";
	if (0 === strpos($response, 'HTTP/1.1 200 OK')) {
		$parts = preg_split("#\n\s*\n#Uis", $response);
		if ( count($parts) > 1 ) {
			$rawheader = strtolower(preg_replace("/\s/", "", $parts[0]));
			if ( strpos($rawheader, 'transfer-encoding:chunked') !== false ) {
				$ret = "";
				$pos = 0;
				while ( $pos < strlen($parts[1]) ) {
					$next = strpos($parts[1], "\r\n", $pos);
					$len = ( $next === false || $next == $pos ? 0 : hexdec(substr($parts[1], $pos, $next - $pos)) );
					if ( $len <= 0 ) break;
					$ret .= substr($parts[1], $next + 2, $len);
					$pos = $next + $len + 4;
				}
			}
			else $ret = $parts[1];
		}
	}
	return $ret;
}

/**
 * Send POST Request.
 *
 * This function sends a POST request using the method defined in Post Method
 * option of the plugin's Settings. It is noted that the post request is
 * executed synchronously. The function will wait for the response and then it
 * will finish.
 *
 * @since 2.6.0
 *
 * @param string $url The destination URL of the request.
 * @param array $params Parameters to pass to the POST request.
 * @param bool $verifypeer Optional. Verify the peer for secure (SSL) requests.
 * @param bool $internal_request Optional. True if this is an internal request
 *        to targetting /wp-admin area. In this case a username/password will
 *        also be passed to the request if Dashboard is password protected.
 * @param int $timeout Optional. Timeout of the request in seconds.
 *
 * @return string The response of the POST request.
 */
function wfu_post_request($url, $params, $verifypeer = true, $internal_request = false, $timeout = 0) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$default_args = array(
		'url' => $url,
		'params' => $params,
		'verifypeer' => $verifypeer,
		'internal_request' => $internal_request,
		'timeout' => $timeout
	);
	//check proxy
	$proxy = new WP_HTTP_Proxy();
	if ( isset($plugin_options['postmethod']) && $plugin_options['postmethod'] == 'curl' ) {
		// POST request using CURL
		$ch = curl_init($url);
		$options = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query($params),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
			CURLINFO_HEADER_OUT => false,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => $verifypeer,
			CURLOPT_SSL_VERIFYHOST => ( $verifypeer ? CURLOPT_SSL_VERIFYHOST : false )
		);
		if ( $timeout > 0 ) $options[CURLOPT_TIMEOUT] = $timeout;
		//for internal requests to /wp-admin area that is password protected
		//authorization is required
		if ( $internal_request && WFU_VAR("WFU_DASHBOARD_PROTECTED") == "true" ) {
			$options[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
			$options[CURLOPT_USERPWD] = WFU_VAR("WFU_DASHBOARD_USERNAME").":".WFU_VAR("WFU_DASHBOARD_PASSWORD");
		}
		if ( WFU_VAR("WFU_RELAX_CURL_VERIFY_HOST") == "true" ) $options[CURLOPT_SSL_VERIFYHOST] = false;
		//configure cURL request for proxy
		if ( $proxy->is_enabled() && $proxy->send_through_proxy($url) ) {
			$options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
			$options[CURLOPT_PROXY] = $proxy->host().":".$proxy->port();
			if ( $proxy->use_authentication() ) {
				$options[CURLOPT_PROXYAUTH] = CURLAUTH_ANY;
				$options[CURLOPT_PROXYUSERPWD] = $proxy->authentication();
			}
		}
		/**
		 * Customize POST Request Options.
		 *
		 * This filter allows custom actions to modify the POST request options
		 * before the request is sent.
		 *
		 * @since 4.10.0
		 *
		 * @param array $options An array of POST options.
		 * @param string $method The POST method. It can be 'fopen', 'curl' or
		 *        'sockets'.
		 * @param array $default_args {
		 *        Parameters of the POST request.
		 *
		 *        @type string $url Destination URL.
		 *        @type array $params The POST parameters.
		 *        @type bool $verifypeer True if peer needs to be verified.
		 *        @type bool $internal_request True if this is an internal
		 *              request (sent back to the website).
		 *        @type int $timeout The request timeout in seconds.
		 * }
		 */
		$options = apply_filters("_wfu_post_request_options", $options, "curl", $default_args);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		curl_close ($ch);
		return $result;
	}
	elseif ( isset($plugin_options['postmethod']) && $plugin_options['postmethod'] == 'socket' ) {
		// POST request using sockets
		$scheme = "";
		$port = 80;
		$errno = 0;
        $errstr = '';
		$ret = '';
		$url_parts = parse_url($url);
		$host = $url_parts['host'];
		$socket_host = $host;
		$path = $url_parts['path'];
		if ( $url_parts['scheme'] == 'https' ) { 
			$scheme = "ssl://";
			$port = 443;
			if ( $timeout == 0 ) $timeout = 30;
		}
		elseif ( $url['scheme'] != 'http' ) return '';
		//configure sockets request for proxy
		if ( $proxy->is_enabled() && $proxy->send_through_proxy($url) ) {
			$scheme = "";
			$socket_host = $proxy->host();
			$port = $proxy->port();
			$path = $url;
		}
		if ( $verifypeer ) $handle = fsockopen($scheme.$socket_host, $port, $errno, $errstr, ($timeout == 0 ? ini_get("default_socket_timeout") : $timeout));
		else {
			$context = stream_context_create(array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false
			)));
			$handle = stream_socket_client($scheme.$socket_host.":".$port, $errno, $errstr, ($timeout == 0 ? ini_get("default_socket_timeout") : $timeout), STREAM_CLIENT_CONNECT, $context);
		}
		if ( $errno !== 0 || $errstr !== '' ) $handle = false;
		if ( $handle !== false ) {
			$content = http_build_query($params);
			$request = "POST " . $path . " HTTP/1.1\r\n";
			$request .= "Host: " . $host . "\r\n";
			$request .= "Content-Type: application/x-www-form-urlencoded\r\n";
			//for internal requests to /wp-admin area that is password protected
			//authorization is required
			if ( $internal_request && WFU_VAR("WFU_DASHBOARD_PROTECTED") == "true" )
				$request .= "Authorization: Basic ".base64_encode(WFU_VAR("WFU_DASHBOARD_USERNAME").":".WFU_VAR("WFU_DASHBOARD_PASSWORD"))."\r\n";
			//add proxy authentication if exists and is required
			if ( $proxy->is_enabled() && $proxy->send_through_proxy($url) && $proxy->use_authentication() )
				$request .= $proxy->authentication_header()."\r\n";
			$request .= "Content-length: " . strlen($content) . "\r\n";
			$request .= "Connection: close\r\n\r\n";
			$request .= $content . "\r\n\r\n";
			/** This filter is explained above. */
			$request = apply_filters("_wfu_post_request_options", $request, "socket", $default_args);
			fwrite($handle, $request, strlen($request));
			$response = '';
			while ( !feof($handle) ) {
                $response .= fgets($handle, 4096);
            }
			fclose($handle);
			$ret = wfu_decode_socket_response($response);
		}
		return $ret;
	}
	else {
		// POST request using file_get_contents
		if ( $internal_request && WFU_VAR("WFU_DASHBOARD_PROTECTED") == "true" ) {
			$url = preg_replace("/^(http|https):\/\//", "$1://".WFU_VAR("WFU_DASHBOARD_USERNAME").":".WFU_VAR("WFU_DASHBOARD_PASSWORD")."@", $url);
		}
		$peer_key = version_compare(PHP_VERSION, '5.6.0', '<') ? 'CN_name' : 'peer_name';
		$http_array = array(
			'method'  => 'POST',
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'content' => http_build_query($params)
		);
		//configure fopen request for proxy
		if ( $proxy->is_enabled() && $proxy->send_through_proxy($url) ) {
			$http_array['proxy'] = 'tcp://'.$proxy->host().":".$proxy->port();
			if ( $proxy->use_authentication() )
				$http_array['header'] .= $proxy->authentication_header()."\r\n";
		}
		if ( $timeout > 0 ) $http_array['timeout'] = $timeout;
		//for internal requests to /wp-admin area that is password protected
		//authorization is required
		if ( $internal_request && WFU_VAR("WFU_DASHBOARD_PROTECTED") == "true" ) {
			$http_array['header'] .= "Authorization: Basic ".base64_encode(WFU_VAR("WFU_DASHBOARD_USERNAME").":".WFU_VAR("WFU_DASHBOARD_PASSWORD"))."\r\n";
		}
		$context_params = array( 'http' => $http_array );
		if ( !$verifypeer ) $context_params['ssl'] = array( 'verify_peer' => false, 'allow_self_signed' => true, 'verify_peer_name' => false );
		/** This filter is explained above. */
		$context_params = apply_filters("_wfu_post_request_options", $context_params, "fopen", $default_args);
		$context = stream_context_create($context_params);
		return file_get_contents($url, false, $context);
	}
}

?>
