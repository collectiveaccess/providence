<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/Adapters/Okta.php : default authentication backend
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Auth
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Auth/BaseAuthAdapter.php');
require_once(__CA_MODELS_DIR__.'/ca_users.php');

class OktaAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {
	/**
	 * Auth state; always "applicationState" (for now) 
	 */
	private $state = 'applicationState';
	
	/**
	 * app.conf configuration instance
	 */
	private $config = null;
	
	/**
	 * authentication.conf configuration instance
	 */
	private $auth_config = null;

	# --------------------------------------------------------------------------------
	/**
     *
     */
    public function __construct(){
        $this->config = Configuration::load();
        $this->auth_config = Configuration::load(__CA_APP_DIR__."/conf/authentication.conf");
    }
	# --------------------------------------------------------------------------------
	/**
     *
     */
	public function authenticate($username, $password = '', $options=null) {
		global $g_request;
			
		if (!$this->isAuthenticated()) {
			$query = http_build_query([
				'client_id' => $this->auth_config->get('okta_client_id'),
				'response_type' => 'code',
				'response_mode' => 'query',
				'scope' => 'openid profile',
				'redirect_uri' => $g_request ? caNavUrl($g_request, 'system', 'auth', 'callback', null, ['absolute' => true]) : '',
				'state' => $this->state,
				'nonce' => random_bytes(32)
			]);
			header('Location: '.$this->auth_config->get('okta_issuer').'/oauth2/default/v1/authorize?'.$query);
			return false;
		}
		return $this->isAuthenticated();
	}
    # --------------------------------------------------------------------------------
    /**
     *
     * @throws OktaException
     */
    public function getUserInfo($username, $password, $options=null) {
        if(!$this->isAuthenticated()){
            if (!$this->authenticate($username, $password)) {
                throw new OktaException(_t("User could not be authenticated."));
            }
        }
        if($this->isAuthenticated()){
            $va_default_roles =  $this->auth_config->get('okta_users_default_roles');
            $va_default_groups = $this->auth_config->get('okta_users_default_groups');
            
            $va_attrs = $this->getProfile();
            if (!$va_attrs || !is_array($va_attrs)) { 
            	throw new OktaException(_t("Token is invalid."));
            }
            if ($return_minimal = caGetOption('minimal', $options, false)) {
            	return ['user_name' => $va_attrs['sub']];
            } else {
				$headers = [
					'Authorization: SSWS ' . $this->auth_config->get('okta_api_token'),
					'Accept: application/json',
					'Content-Type: application/json'
				];
				
				$user_info = self::oktaRequest(
					$this->auth_config->get('okta_issuer').'/api/v1/users/'.$va_attrs['sub'], 
					$headers, 
					['jsonDecode' => true]
				);
			
				$group_list = self::oktaRequest(
					$this->auth_config->get('okta_issuer').'/api/v1/users/'.$va_attrs['sub'].'/groups', 
					$headers, 
					['jsonDecode' => true]
				);
			} 
			if (is_array($user_info)) {
				if ($user_info['status'] !== 'ACTIVE') {
					throw new OktaException(_t("User is not active."));
				}
				$va_groups = array_merge(
					$va_default_groups,
					array_filter(array_map(function($v) { return $v['profile']['name']; }, $group_list), function($v) { return strtolower($v) !== 'everyone'; })
				);
				return [
					'user_name' => $va_attrs['sub'],
					'email' => $user_info['profile']['email'],
					'fname' => $user_info['profile']['firstName'],
					'lname' => $user_info['profile']['lastName'],
					'active' => 1,
					'roles' => $va_default_roles,
					'groups' => $va_groups
				];
			} else {
				throw new OktaException(_t("User information is invalid."));
			}
        }
		throw new OktaException(_t("User could not be found."));
    }
	# --------------------------------------------------------------------------------
	/**
     *
     */
	public function createUserAndGetPassword($username, $password) {
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		if(function_exists('mcrypt_create_iv')) {
			$vs_password = base64_encode(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		} elseif(function_exists('openssl_random_pseudo_bytes')) {
			$vs_password = base64_encode(openssl_random_pseudo_bytes(32));
		} else {
			throw new Exception('mcrypt or OpenSSL is required for CollectiveAccess to run');
		}

        return $vs_password;
	}
	# --------------------------------------------------------------------------------
	/**
     *
     */
	public function supports($pn_feature) {
		switch($pn_feature){
		    case __CA_AUTH_ADAPTER_FEATURE_USE_ADAPTER_LOGIN_FORM__:
		        return true;
			case __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__:
			case __CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__:
				return false;
			case __CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__:
			    return true;
			default:
				return false;
		}
	}
	# --------------------------------------------------------------------------------
	/**
     *
     */
	public function updatePassword($username, $password) {
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		return create_hash($password);
	}
	# --------------------------------------------------------------------------------
	/**
     *
     */
	public function deleteUser($username) {
		// ca_users takes care of deleting the db row for us. Nothing else to do here.
		return true;
	}
    # --------------------------------------------------------------------------------
    /**
     *
     */
    public function getAccountManagementLink() {
        return false;
    }
	# --------------------------------------------------------------------------------
	/**
	 * Deauthenticate session 
	 *
	 */
    public function deauthenticate($options=null) {
       	setcookie("access_token", "", time()-3600, '/');
        return true;
    }
    # --------------------------------------------------------------------------------
	/**
	 * Handle Okta callback
	 *
	 */
    public function callback($options=null) {
    	if(array_key_exists('state', $_REQUEST) && $_REQUEST['state'] !== $this->state) {
            throw new \Exception('State does not match.');
        }
        if(array_key_exists('code', $_REQUEST)) {
            $exchange = $this->exchangeCode($_REQUEST['code']);
            if(!isset($exchange['access_token'])) {
                throw new OktaException(_t('Could not exchange Okta code for an access token'));
            }
            if($this->verifyJwt($exchange['access_token']) == false) {
                throw new OktaException(_t('Verification of Okta JWT failed'));
            }
            setcookie("access_token", $exchange['access_token'],time()+$exchange['expires_in'],"/",false);
            header('Location: '.$this->config->get('auth_login_url'));
        }
        throw new OktaException(_t('An Okta error during login has occurred'));
    }
	# --------------------------------------------------------------------------------
	/**
	 * Is user already authenticated?
	 *
	 * @return bool
	 */
	public function isAuthenticated() {
		if(isset($_COOKIE['access_token'])) {
			return true;
		}
		return false;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Verify JWT access token and return list of claims. Return empty array if not
	 * authenticated and false if verification failed.
	 *
	 * @return array
	 */
	public function getProfile() {
		if(!$this->isAuthenticated()) {
			return [];
		}
		$jwtVerifier = (new \Okta\JwtVerifier\JwtVerifierBuilder())
			->setIssuer($this->auth_config->get('okta_issuer').'/oauth2/default')
			->setAudience('api://default')
			->setClientId($this->auth_config->get('okta_client_id'))
			->build();
		$jwt = $jwtVerifier->verify($_COOKIE['access_token']);
		return $jwt->claims;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Verify JWT access token and return parsed token. Return false verification fails.
	 *
	 * @param string $jwt An access token
	 * @return \Okta\JwtVerifier\JWT
	 */
	public function verifyJwt($jwt) {
		try {
			$jwtVerifier = (new \Okta\JwtVerifier\JwtVerifierBuilder())
				->setIssuer($this->auth_config->get('okta_issuer').'/oauth2/default')
				->setAudience('api://default')
				->setClientId($this->auth_config->get('okta_client_id'))
				->build();
			return $jwtVerifier->verify($jwt);
		} catch (\Exception $e) {
			return false;
		}
	}
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function exchangeCode($code) {
		global $g_request;
		
		$query = http_build_query([
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $g_request ? caNavUrl($g_request, 'system', 'auth', 'callback', null, ['absolute' => true]) : ''
		]);
		
		$authHeaderSecret = base64_encode( $this->auth_config->get('okta_client_id') . ':' . $this->auth_config->get('okta_secret') );
		
		return self::oktaRequest(
			$this->auth_config->get('okta_issuer').'/oauth2/default/v1/token?'.$query, 
			[
				'Authorization: Basic ' . $authHeaderSecret,
				'Accept: application/json',
				'Content-Type: application/x-www-form-urlencoded',
				'Connection: close',
				'Content-Length: 0'
			],
			['post' => true, 'jsonDecode' => true]
		);
	}
	# --------------------------------------------------------------------------------
	/**
	 * Submit request to Okta API
	 *
	 * @param string $url
	 * @param array $headers
	 * @param array $options Options include:
	 *		post = Submit request as POST. [Default is false; GET is used]
	 *		jsonDecode = Decode response as JSON and return PHP array. [Default is false; raw response is returned]
	 *
	 * @return mixed String or array
	 * @throws OktaException
	 */
	private static function oktaRequest($url, $headers, $options=null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, caGetOption('post', $options, false) ? 1 : 0);
		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if(curl_error($ch)) { throw new OktaException(_t("Could not connect to Okta.")); }
		curl_close($ch);
		
		if (caGetOption('jsonDecode', $options, false)) {
			return json_decode($response, true);
		}
		return $response;		
	}
	# --------------------------------------------------------------------------------
}

class OktaException extends Exception {}
