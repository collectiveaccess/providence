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
	 *
	 */
	private $state = null;

	# --------------------------------------------------------------------------------
    public function __construct(){
        $this->opo_auth_config = Configuration::load(__CA_APP_DIR__."/conf/authentication.conf");
        
        $this->state = 'applicationState';
    }
	# --------------------------------------------------------------------------------
	public function authenticate($ps_username, $ps_password = '', $pa_options=null) {
		if (!$this->isAuthenticated()) {
			$query = http_build_query([
				'client_id' => $this->opo_auth_config->get('okta_client_id'),
				'response_type' => 'code',
				'response_mode' => 'query',
				'scope' => 'openid profile',
				'redirect_uri' => 'http://develop/system/auth/callback',
				'state' => $this->state,
				'nonce' => random_bytes(32)
			]);
			header('Location: ' . $this->opo_auth_config->get('okta_issuer').'/oauth2/default/v1/authorize?'.$query);
			return false;
		}
		return $this->isAuthenticated();
	}
    # --------------------------------------------------------------------------------
    public function getUserInfo($ps_username, $ps_password, $pa_options=null) {
        if(!$this->isAuthenticated()){
            if (!$this->authenticate($ps_username, $ps_password)) {
                throw new OktaException(_t("User could not be authenticated."));
            }
        }
        if($this->isAuthenticated()){
            $va_default_roles =  $this->opo_auth_config->get('okta_users_default_roles');
            $va_default_groups = $this->opo_auth_config->get('okta_users_default_groups');
            
            $va_attrs = $this->getProfile();
            
            if ($return_minimal = caGetOption('minimal', $pa_options, false)) {
            	return ['user_name' => $va_attrs['sub']];
            } else {
				$headers = [
					'Authorization: SSWS ' . $this->opo_auth_config->get('okta_api_token'),
					'Accept: application/json',
					'Content-Type: application/json'
				];
				$url = $this->opo_auth_config->get('okta_issuer').'/api/v1/users/'.$va_attrs['sub'];
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POST, 0);
				$userjson = curl_exec($ch);
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if(curl_error($ch)) { new OktaException(_t("Could not connect to Okta to fetch user profile.")); }
				curl_close($ch);
				$user_info = json_decode($userjson, true);
			
				$url = $this->opo_auth_config->get('okta_issuer').'/api/v1/users/'.$va_attrs['sub'].'/groups';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POST, 0);
				$groupjson = curl_exec($ch);
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if(curl_error($ch)) { new OktaException(_t("Could not connect to Okta to fetch user groups.")); }
				curl_close($ch);
				$group_list = json_decode($groupjson, true);
			} 
			if (is_array($user_info)) {
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
				new OktaException(_t("User information is invalid."));
			}
        }
		throw new OktaException(_t("User could not be found."));
    }
	# --------------------------------------------------------------------------------
	public function createUserAndGetPassword($ps_username, $ps_password) {
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
	public function updatePassword($ps_username, $ps_password) {
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		return create_hash($ps_password);
	}
	# --------------------------------------------------------------------------------
	public function deleteUser($ps_username) {
		// ca_users takes care of deleting the db row for us. Nothing else to do here.
		return true;
	}
    # --------------------------------------------------------------------------------
    public function getAccountManagementLink() {
        return false;
    }
	# --------------------------------------------------------------------------------
	/**
	 * Deauthenticate session 
	 *
	 */
    public function deauthenticate($pa_options=null) {
       	setcookie("access_token", "", time()-3600, '/');
        return true;
    }
    # --------------------------------------------------------------------------------
	/**
	 * 
	 *
	 */
    public function callback($pa_options=null) {
    	if(array_key_exists('state', $_REQUEST) && $_REQUEST['state'] !== $this->state) {
            throw new \Exception('State does not match.');
        }
        if(array_key_exists('code', $_REQUEST)) {
            $exchange = $this->exchangeCode($_REQUEST['code']);
            
            if(!isset($exchange->access_token)) {
                die('Could not exchange code for an access token');
            }
            if($this->verifyJwt($exchange->access_token) == false) {
                die('Verification of JWT failed');
            }
            setcookie("access_token","$exchange->access_token",time()+$exchange->expires_in,"/",false);
            header('Location: / ');
        }
        die('An error during login has occurred');
    }
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function isAuthenticated() {
		if(isset($_COOKIE['access_token'])) {
			return true;
		}
		return false;
	}
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function getProfile() {
		if(!$this->isAuthenticated()) {
			return [];
		}
		$jwtVerifier = (new \Okta\JwtVerifier\JwtVerifierBuilder())
			->setIssuer($this->opo_auth_config->get('okta_issuer').'/oauth2/default')
			->setAudience('api://default')
			->setClientId($this->opo_auth_config->get('okta_client_id'))
			->build();
		$jwt = $jwtVerifier->verify($_COOKIE['access_token']);
		return $jwt->claims;
	}
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function verifyJwt($jwt) {
		try {
			$jwtVerifier = (new \Okta\JwtVerifier\JwtVerifierBuilder())
				->setIssuer($this->opo_auth_config->get('okta_issuer').'/oauth2/default')
				->setAudience('api://default')
				->setClientId($this->opo_auth_config->get('okta_client_id'))
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
		$query = http_build_query([
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => 'http://develop/system/auth/callback'
		]);
		
		$authHeaderSecret = base64_encode( $this->opo_auth_config->get('okta_client_id') . ':' . $this->opo_auth_config->get('okta_secret') );
		$headers = [
			'Authorization: Basic ' . $authHeaderSecret,
			'Accept: application/json',
			'Content-Type: application/x-www-form-urlencoded',
			'Connection: close',
			'Content-Length: 0'
		];
		$url = $this->opo_auth_config->get('okta_issuer').'/oauth2/default/v1/token?' . $query;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, 1);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if(curl_error($ch)) {
			$httpcode = 500;
		}
		curl_close($ch);
		return json_decode($output);
	}
}

class OktaException extends Exception {}
