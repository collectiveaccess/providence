<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/Adapters/Shibboleth.php : default authentication backend
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
require_once('/var/simplesamlphp/lib/_autoload.php');
require_once(__CA_LIB_DIR__.'/Auth/BaseAuthAdapter.php');
require_once(__CA_MODELS_DIR__.'/ca_users.php');

class ShibbolethAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {

    public function __construct(){
        $this->opo_auth_config = Configuration::load(__CA_APP_DIR__."/conf/authentication.conf");
        $vs_shibSP = $this->opo_auth_config->get('shib_service_provider');
        try{
                $this->opo_shibAuth = new \SimpleSAML\Auth\Simple($vs_shibSP);
        } catch (Exception $e) {
                throw new ShibbolethException("Could not create SimpleSAML auth object");
        }
    }
	# --------------------------------------------------------------------------------
	public function authenticate($ps_username, $ps_password = '', $pa_options=null) {
		try{
        	$this->opo_shibAuth->requireAuth();
		} catch (Exception $e){
			die("Shibboleth error: {$e}");
		}
		
		if(!$this->opo_shibAuth->isAuthenticated()){
			return false;
		}
		if (!($va_attrs = $this->opo_shibAuth->getAttributes())) { return false; }
	    
	    
	    $uid = array_shift($va_attrs['eduPersonPrincipalName']);
	    
	    if (ca_users::find(['user_name' => $uid], ['returnAs' => 'count']) > 0) {
	        return true;
	    }
	    return false;
	}
    # --------------------------------------------------------------------------------
    public function getUserInfo($ps_username, $ps_password, $pa_options=null) {
        if(!$this->opo_shibAuth->isAuthenticated()){
            if (!$this->authenticate($ps_username, $ps_password)) {
                throw new ShibbolethException(_t("User could not be authenticated."));
            }
        }
        if($this->opo_shibAuth->isAuthenticated()){
            $va_default_roles =  $this->opo_auth_config->get('shibboleth_users_default_roles');
            $va_default_groups = $this->opo_auth_config->get('shibboleth_users_default_groups');
            
            $va_attrs = $this->opo_shibAuth->getAttributes();
            return [
                'user_name' => $ps_username ? $ps_username : $va_attrs['eduPersonPrincipalName'][0],
				'email' => $va_attrs['eduPersonPrincipalName'][0],
				'fname' => $va_attrs['eduPersonPrincipalName'][0],
				'lname' => $va_attrs['eduPersonPrincipalName'][0],
				'active' => 1,
				'roles' => $va_default_roles,
				'groups' => $va_default_groups
            ];
        }
		throw new ShibbolethException(_t("User could not be found."));
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
	 * Deauthenticate session by removing SimpleSAML cookies
	 *
	 */
    public function deauthenticate($pa_options=null) {
        setcookie("SimpleSAML", "", time()-3600, '/');
        setcookie("SimpleSAMLAuthToken", "", time()-3600, '/');
        setcookie($this->opo_auth_config->get('shib_token_cookie'), '', time()-3600, __CA_URL_ROOT__);
        return true;
    }
	# --------------------------------------------------------------------------------
}

class ShibbolethException extends Exception {}
