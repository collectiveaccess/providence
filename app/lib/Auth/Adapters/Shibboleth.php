<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/Adapters/Shibboleth.php : default authentication backend
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2024 Whirl-i-Gig
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
require_once(__CA_BASE_DIR__.'/vendor/simplesamlphp/simplesamlphp/lib/_autoload.php');
require_once(__CA_LIB_DIR__.'/Auth/BaseAuthAdapter.php');
require_once(__CA_MODELS_DIR__.'/ca_users.php');

class ShibbolethAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	private $auth_config = null;
	
	/**
	 *
	 */
	private $opo_shibAuth = null;
	
	/**
	 *
	 */
	private $log = null;
	
	/**
	 *
	 */
	private $debug = false;
	
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
    public function __construct(){
    	if(caIsRunFromCLI()) { return; }
        $this->auth_config = Configuration::load(__CA_APP_DIR__."/conf/authentication.conf");
        $this->debug = (bool)$this->auth_config->get('shibboleth_debug');
        $shibSP = $this->auth_config->get('shibboleth_service_provider');
        
        $this->log = caGetLogger();
        
        if($this->debug) { $this->log->logDebug(_t("[Shibboleth::debug] Created new shib context")); }
        try{
            $this->opo_shibAuth = new \SimpleSAML\Auth\Simple($shibSP);
            session_write_close();
        } catch (Exception $e) {
       		if($this->debug) { $this->log->logDebug(_t("Could not create SimpleSAML auth object: %1", $e->getMessage())); }
            throw new ShibbolethException(_t("Could not create SimpleSAML auth object: %1", $e->getMessage()));
        }
        
        $map = $this->getAttributeMap();
		if (!array_key_exists('uid', $map)) {
			throw new ShibbolethException(_t("uid not found in attribute map"));
		}
		
		if (!array_key_exists('email', $map)) {
			throw new ShibbolethException(_t("email not found in attribute map"));
		}
    }
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function authenticate($username, $password = '', $options=null) {
    	if(caIsRunFromCLI()) { return false; }
    	
        if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] Attempting to authenticate with {$username}::{$password}")); }
		try{
        	$this->opo_shibAuth->requireAuth();
		} catch (Exception $e){
			if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] Error while attempting to authenticate with {$username}::{$password}: %1",$e->getMessage())); }
			throw new ShibbolethException(_t("Shibboleth error: %1", $e->getMessage()));
		}
		
		if(!$this->opo_shibAuth->isAuthenticated()){
			return false;
		}
		if (!($attrs = $this->opo_shibAuth->getAttributes())) { 
			if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] Received not attrbitures while attempting to authenticate with {$username}::{$password}")); }
			return false; 	
		}
		$uid = $this->mapAttribute('uid', $attrs);
		
		if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] Got uid {$uid} while attemtping to authenticate with {$username}::{$password}. Attributes were %1", print_R($attrs, true))); }
	    if (!$uid) { return false; }
		if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] Authentication with {$username}::{$password} was successful")); }
	   
	    return true;
	}
    # --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function getUserInfo($username, $password, $options=null) {
    	if(caIsRunFromCLI()) { return null; }
    	
    	if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] Getting user info with {$username}::{$password}")); }
        if(!$this->opo_shibAuth->isAuthenticated()){
            if (!$this->authenticate($username, $password)) {
        		if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] User info authentication with {$username}::{$password} failed")); }
                throw new ShibbolethException(_t("User could not be authenticated."));
            }
        }
        if($this->opo_shibAuth->isAuthenticated()){
            $default_roles =  $this->auth_config->get('shibboleth_users_default_roles');
            $default_groups = $this->auth_config->get('shibboleth_users_default_groups');
                
            $attrs = $this->opo_shibAuth->getAttributes();
            
        	if($this->debug) { 
            	$map = $this->getAttributeMap();
        		$this->log->logInfo(_t("[Shibboleth::debug] User info mapping was %1", print_R($map, true)));
        		$this->log->logInfo(_t("[Shibboleth::debug] User info attributes were %1", print_R($attrs, true)));
        	}
            
			$uid = $this->mapAttribute('uid', $attrs);
			$email = $this->mapAttribute('email', $attrs);
			$fname = $this->mapAttribute('fname', $attrs);
			$lname = $this->mapAttribute('lname', $attrs);
			
        	if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] User info got values uid={$uid}; email={$email}; fname={$fname}; lname={$lname}")); }
            
            if(empty($uid)) { 
        		if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] User info authentication with {$username}::{$password} failed because no user id was set")); }
            	throw new ShibbolethException(_t("User id not set."));
            }
            if(empty($email)) { 
        		if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] User info authentication with {$username}::{$password} failed because no email was set")); }
            	throw new ShibbolethException(_t("User email address not set."));
            }
            $ret = [
                'user_name' => $username ? $username : $uid,
				'email' => $email,
				'fname' => $fname,
				'lname' => $lname ? $lname : $email,
				'active' => 1,
				'roles' => $default_roles,
				'groups' => $default_groups
            ];
            if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] User info succeeded with {$username}::{$password}; values are: %1", print_R($ret, true))); }
            return $ret;
        } 
        
        if($this->debug) { $this->log->logInfo(_t("[Shibboleth::debug] User info failed with {$username}::{$password} failed")); }
		throw new ShibbolethException(_t("User could not be found."));
    }
	# --------------------------------------------------------------------------------
	/**
	 * @param string $username Username to create account for
	 * @param string $passowrd Ignored
	 */
	public function createUserAndGetPassword($username, $password=null) {
    	if(caIsRunFromCLI()) { return null; }
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		if(function_exists('mcrypt_create_iv')) {
			$password = base64_encode(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		} elseif(function_exists('openssl_random_pseudo_bytes')) {
			$password = base64_encode(openssl_random_pseudo_bytes(32));
		} else {
			throw new Exception('mcrypt or OpenSSL is required for CollectiveAccess Shibboleth support');
		}

        return $password;
	}
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function supports($feature) {
		switch($feature){
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
	 * @param string $username Username to update password for
	 * @param string $passowrd Ignored
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
	 * Deauthenticate session by removing SimpleSAML cookies
	 *
	 * @param array $options No options are currently supported.
	 *
	 * @return bool True on success
	 */
    public function deauthenticate($options=null) {
    	if(caIsRunFromCLI()) { return false; }
        setcookie("SimpleSAML", "", time()-3600, '/');
        setcookie("SimpleSAMLAuthToken", "", time()-3600, '/');
        setcookie($this->auth_config->get('shibboleth_token_cookie'), '', time()-3600, __CA_URL_ROOT__);
        return true;
    }
	# --------------------------------------------------------------------------------
	/**
	 * 
	 */
	private function mapAttribute(string $key, array $values) {
		$map = $this->getAttributeMap();
		foreach($map as $k => $v) {
			if($k === $key) {
				$x = $values[$v] ?? null;
				return is_array($x) ? array_shift($x) : $x;
			}
		}
		return null;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Fetch attribute map from configuration. 
	 *
	 * return array
	 */
	private function getAttributeMap() : ?array {
		if(is_array($map = $this->auth_config->get('shibboleth_field_map'))) {
			return $map;
		}
		throw new ShibbolethException(_t("shibboleth_field_map not found in configuration"));
	}
	# --------------------------------------------------------------------------------
}

class ShibbolethException extends Exception {}
