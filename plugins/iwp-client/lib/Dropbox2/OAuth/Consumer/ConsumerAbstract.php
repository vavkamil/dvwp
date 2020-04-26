<?php

/**
* Abstract OAuth consumer
* @author Ben Tadiar <ben@handcraftedbyben.co.uk>
* @link https://github.com/benthedesigner/dropbox
* @package Dropbox\OAuth
* @subpackage Consumer
*/

abstract class Dropbox_ConsumerAbstract
{
    // Dropbox web endpoint. v2 API has just dropped the 1/ suffix to the below.
    const WEB_URL = 'https://www.dropbox.com/';
    
    // OAuth flow methods
    const AUTHORISE_METHOD = 'oauth2/authorize';
    // Beware - the documentation in one place says oauth2/token/revoke, but that appears to be wrong
    const DEAUTHORISE_METHOD = '2/auth/token/revoke';
    const ACCESS_TOKEN_METHOD = 'oauth2/token';
    // The next endpoint only exists with APIv1
    const OAUTH_UPGRADE = 'oauth2/token_from_oauth1';
    
    /**
     * Signature method, either PLAINTEXT or HMAC-SHA1
     * @var string
     */
    private $sigMethod = 'PLAINTEXT';

    private $token = null;
    
    /**
     * Output file handle
     * @var null|resource
     */
    protected $outFile = null;
    
    /**
     * Input file handle
     * @var null|resource
     */
    protected $inFile = null;
    
    /**
     * Authenticate using 3-legged OAuth flow, firstly
     * checking we don't already have tokens to use
     * @return void
     */
    protected function authenticate()
    {
        global $iwp_backup_core;
        
        $access_token = $this->storage->get('access_token');
        //Check if the new token type is set if not they need to be upgraded to OAuth2
        if (!empty($access_token) && isset($access_token->oauth_token) && !isset($access_token->token_type)) {
            $iwp_backup_core->log('OAuth v1 token found: upgrading to v2');
            $this->upgradeOAuth();
            $iwp_backup_core->log('OAuth token upgrade successful');
        }
        
        if (empty($access_token) || !isset($access_token->oauth_token)) {
            try {
                $this->getAccessToken();
            } catch(Exception $e) {
                $excep_class = get_class($e);
                // 04-Sep-2015 - Dropbox started throwing a 400, which caused a Dropbox_BadRequestException which previously wasn't being caught
                if ('Dropbox_BadRequestException' == $excep_class || 'Dropbox_Exception' == $excep_class) {
                    global $iwp_backup_core;
                    $iwp_backup_core->log($e->getMessage().' - need to reauthenticate this site with Dropbox (if this fails, then you can also try wiping your settings from the Expert Settings section)');
                    //$this->getRequestToken();
                    $this->authorise();
                } else {
                    throw $e;
                }
            }
        }
    }
    
    /**
    * Upgrade the user's OAuth1 token to a OAuth2 token
    * @return void
    */
    private function upgradeOAuth()
    {
		// N.B. This call only exists under API v1 - i.e. there is no APIv2 equivalent. Hence the APIv1 endpoint (API_URL) is used, and not the v2 (API_URL_V2)
	    $url = IWP_MMB_Dropbox_API::API_URL . self::OAUTH_UPGRADE;
	    $response = $this->fetch('POST', $url, '');
        $token = new stdClass();
        /*
	        oauth token secret and oauth token were needed by oauth1 
	        these are replaced in oauth2 with an access token
	        currently they are still there just in case a method somewhere is expecting them to both be set
	        as far as I can tell only the oauth token is used
	        after more testing token secret can be removed.
        */
        
        $token->oauth_token_secret = $response['body']->access_token;
        $token->oauth_token = $response['body']->access_token;
        $token->token_type = $response['body']->token_type;
        $this->storage->set($token, 'access_token'); 
        $this->storage->set('true','upgraded');
        $this->storage->do_unset('request_token');
    }
    
    /**
     * Obtain user authorisation
     * The user will be redirected to Dropbox' web endpoint
     * @link http://tools.ietf.org/html/rfc5849#section-2.2
     * @return void
     */
    private function authorise()
    {
        // Only redirect if not using CLI
        if (PHP_SAPI !== 'cli' && (!defined('DOING_CRON') || !DOING_CRON) && (!defined('DOING_AJAX') || !DOING_AJAX)) {
            $url = $this->getAuthoriseUrl();
            if (!headers_sent()) {
                header('Location: ' . $url);
                exit;
            } else {
                throw new Dropbox_Exception(sprintf(__('The %s authentication could not go ahead, because something else on your site is breaking it. Try disabling your other plugins and switching to a default theme. (Specifically, you are looking for the component that sends output (most likely PHP warnings/errors) before the page begins. Turning off any debugging settings may also help).', 'InfiniteWP'), 'Dropbox'));
            }
            ?><?php
            return false;
        }
        global $iwp_backup_core;
        $iwp_backup_core->log('Dropbox reauthorisation needed; but we are running from cron, AJAX or the CLI, so this is not possible');
        $this->storage->do_unset('access_token');
        throw new Dropbox_Exception(sprintf(__('You need to re-authenticate with %s, as your existing credentials are not working.', 'InfiniteWP'), 'Dropbox'));
     
        return false;
    }

    public function setToken($token)
    {

        $this->token = $token;

        return $this;
    }
    
    /**
    * Build the user authorisation URL
    * @return string
    */
    public function getAuthoriseUrl()
    {
	    /*
		    Generate a random key to be passed to Dropbox and stored in session to be checked to prevent CSRF
		    Uses OpenSSL or Mcrypt or defaults to pure PHP implementaion if neither are available.
		*/
	    
		global $iwp_backup_core;
		if (!function_exists('crypt_random_string')) $iwp_backup_core->ensure_phpseclib('Crypt_Random', 'Crypt/Random');

		$CSRF = base64_encode(crypt_random_string(16));
        $this->storage->set($CSRF,'CSRF');
        
        $appkey = $this->storage->get('appkey');
        
        if (!empty($appkey) && 'dropbox:' == substr($appkey, 0, 8)) {
			$key = substr($appkey, 8);
        } else if (!empty($appkey)) {
            $key = $appkey;
        }
        
        $params = array(
            'client_id' => empty($key) ? $this->oauth2_id : $key,
            'response_type' => 'code',
            'redirect_uri' => empty($key) ? $this->callback : $this->callbackhome,
            'state' => empty($key) ? $CSRF.$this->callbackhome : $CSRF,
        );
    
        // Build the URL and redirect the user
        $query = '?' . http_build_query($params, '', '&');
        $url = self::WEB_URL . self::AUTHORISE_METHOD . $query;
        return $url;
    }
    
    protected function deauthenticate()
    {
	    $url = IWP_MMB_Dropbox_API::API_URL_V2 . self::DEAUTHORISE_METHOD;
	    $response = $this->fetch('POST', $url, '', array('api_v2' => true));
        $this->storage->delete();
    }
    
    /**
     * Acquire an access token
     * Tokens acquired at this point should be stored to
     * prevent having to request new tokens for each API call
     * @link http://tools.ietf.org/html/rfc5849#section-2.3
     */
    public function getAccessToken()
    {
    
		// If this is non-empty, then we just received a code. It is stored in 'code' - our next job is to put it into the proper place.
	    $code = $this->storage->get('code');
        /*
            Checks to see if the user is using their own Dropbox App if so then they need to get
            a request token. If they are using our App then we just need to save these details
        */
        if (!empty($code)){	
            $appkey = $this->storage->get('appkey');
            if (!empty($appkey)){
                // Get the signed request URL
                $url = IWP_MMB_Dropbox_API::API_URL_V2 . self::ACCESS_TOKEN_METHOD;
                $params = array(
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $this->callbackhome,
                    'client_id' => $this->consumerKey,
                    'client_secret' => $this->consumerSecret,
                );
                $response = $this->fetch('POST', $url, '' , $params);
                
                $code  = json_decode(json_encode($response['body']),true);
            
            } else {
                $code = base64_decode($code);
                $code = json_decode($code, true);    
            }
            
	        /*
		        Again oauth token secret and oauth token were needed by oauth1 
		        these are replaced in oauth2 with an access token
		        currently they are still there just in case a method somewhere is expecting them to both be set
		        as far as I can tell only the oauth token is used
		        after more testing token secret can be removed.
			*/
            
            $token = new stdClass();
            $token->oauth_token_secret = $code['access_token'];
            $token->oauth_token = $code['access_token'];
            $token->account_id = $code['account_id'];
            $token->token_type = $code['token_type'];
            $token->uid = $code['uid'];
            $this->storage->set($token, 'access_token');
            $this->storage->do_unset('upgraded');
            
            //reset code
            $this->storage->do_unset('code');
	    } else {
		    throw new Dropbox_BadRequestException("No Dropbox Code found, will try to get one now", 400);
	    }
    }
    
    /**
     * Get the request/access token
     * This will return the access/request token depending on
     * which stage we are at in the OAuth flow, or a dummy object
     * if we have not yet started the authentication process
     * @return object stdClass
     */
   public function getToken()
    {
        return $this->token;
    }
    /**
     * Generate signed request URL
     * See inline comments for description
     * @link http://tools.ietf.org/html/rfc5849#section-3.4
     * @param string $method HTTP request method
     * @param string $url API endpoint to send the request to
     * @param string $call API call to send
     * @param array $additional Additional parameters as an associative array
     * @return array
     */
    protected function getSignedRequest($method, $url, $call, array $additional = array())
    {
        // Get the request/access token
        $token = $this->getToken();

        // Prepare the standard request parameters differently for OAuth1 and OAuth2; we still need OAuth1 to make the request to the upgrade token endpoint
        if (!empty($token)) {
            $params = array(
                'access_token' => $token,
            );

            /*
                To keep this API backwards compatible with the API v1 endpoints all v2 endpoints will also send to this method a api_v2 parameter this will then return just the access token as the signed request is not needed for any calls.
             */

            if (isset($additional['api_v2']) && $additional['api_v2'] == true) {
                unset($additional['api_v2']);
                if (isset($additional['content_download']) && $additional['content_download'] == true) {
                    unset($additional['content_download']);
                    $headers = array(
                        'Authorization: Bearer '.$params['access_token'],
                        'Content-Type:',
                        'Dropbox-API-Arg: '.json_encode($additional),
                    );
                    $additional = '';
                } else if (isset($additional['content_upload']) && $additional['content_upload'] == true) {
                    unset($additional['content_upload']);
                    $headers = array(
                        'Authorization: Bearer '.$params['access_token'],
                        'Content-Type: application/octet-stream',
                        'Dropbox-API-Arg: '.json_encode($additional),
                    );
                    $additional = '';
                } else {
                    $headers = array(
                        'Authorization: Bearer '.$params['access_token'],
                        'Content-Type: application/json',
                    );
                }
                return array(
                    'url' => $url . $call,
                    'postfields' => $additional,
                    'headers' => $headers,
                );
            }
        } else {
	        // Generate a random string for the request
	        $nonce = md5(microtime(true) . uniqid('', true));
	        $params = array(
	            'oauth_consumer_key' => $this->consumerKey,
	            'oauth_token' => $token->oauth_token,
	            'oauth_signature_method' => $this->sigMethod,
	            'oauth_version' => '1.0',
	            // Generate nonce and timestamp if signature method is HMAC-SHA1 
	            'oauth_timestamp' => ($this->sigMethod == 'HMAC-SHA1') ? time() : null,
	            'oauth_nonce' => ($this->sigMethod == 'HMAC-SHA1') ? $nonce : null,
	        );
	    }
    
        // Merge with the additional request parameters
        $params = array_merge($params, $additional);
        ksort($params);
    
        // URL encode each parameter to RFC3986 for use in the base string
        $encoded = array();
        foreach($params as $param => $value) {
            if ($value !== null) {
                // If the value is a file upload (prefixed with @), replace it with
                // the destination filename, the file path will be sent in POSTFIELDS
                if (isset($value[0]) && $value[0] === '@') $value = $params['filename'];
                # Prevent spurious PHP warning by only doing non-arrays
                if (!is_array($value)) $encoded[] = $this->encode($param) . '=' . $this->encode($value);
            } else {
                unset($params[$param]);
            }
        }
        
        // Build the first part of the string
        $base = $method . '&' . $this->encode($url . $call) . '&';
        
        // Re-encode the encoded parameter string and append to $base
        $base .= $this->encode(implode('&', $encoded));

        // Concatenate the secrets with an ampersand
        $key = $this->consumerSecret . '&' . $token->oauth_token_secret;
        
        // Get the signature string based on signature method
        $signature = $this->getSignature($base, $key);
        $params['oauth_signature'] = $signature;
        
        // Build the signed request URL
        $query = '?' . http_build_query($params, '', '&');
        
        return array(
            'url' => $url . $call . $query,
            'postfields' => $params,
        );
    }
    
    /**
     * Generate the oauth_signature for a request
     * @param string $base Signature base string, used by HMAC-SHA1
     * @param string $key Concatenated consumer and token secrets
     */
    private function getSignature($base, $key)
    {
        switch ($this->sigMethod) {
            case 'PLAINTEXT':
                $signature = $key;
                break;
            case 'HMAC-SHA1':
                $signature = base64_encode(hash_hmac('sha1', $base, $key, true));
                break;
        }
        
        return $signature;
    }
    
    /**
     * Set the OAuth signature method
     * @param string $method Either PLAINTEXT or HMAC-SHA1
     * @return void
     */
    public function setSignatureMethod($method)
    {
        $method = strtoupper($method);
        
        switch ($method) {
            case 'PLAINTEXT':
            case 'HMAC-SHA1':
                $this->sigMethod = $method;
                break;
            default:
                throw new Dropbox_Exception('Unsupported signature method ' . $method);
        }
    }
    
    /**
     * Set the output file
     * @param resource Resource to stream response data to
     * @return void
     */
    public function setOutFile($handle)
    {
        if (!is_resource($handle) || get_resource_type($handle) != 'stream') {
            throw new Dropbox_Exception('Outfile must be a stream resource');
        }
        $this->outFile = $handle;
    }
    
    /**
     * Set the input file
     * @param resource Resource to read data from
     * @return void
     */
    public function setInFile($handle) {
        $this->inFile = $handle;
    }
    
    /**
    * Parse response parameters for a token into an object
    * Dropbox returns tokens in the response parameters, and
    * not a JSON encoded object as per other API requests
    * @link http://oauth.net/core/1.0/#response_parameters
    * @param string $response
    * @return object stdClass
    */
    private function parseTokenString($response)
    {
        $parts = explode('&', $response);
        $token = new stdClass();
        foreach ($parts as $part) {
            list($k, $v) = explode('=', $part, 2);
            $k = strtolower($k);
            $token->$k = $v;
        }
        return $token;
    }
    
    /**
     * Encode a value to RFC3986
     * This is a convenience method to decode ~ symbols encoded
     * by rawurldecode. This will encode all characters except
     * the unreserved set, ALPHA, DIGIT, '-', '.', '_', '~'
     * @link http://tools.ietf.org/html/rfc5849#section-3.6
     * @param mixed $value
     */
    private function encode($value)
    {
        return str_replace('%7E', '~', rawurlencode($value));
    }
}
