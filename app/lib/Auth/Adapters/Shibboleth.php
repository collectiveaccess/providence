<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/Adapters/Shibboleth.php : default authentication backend
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2020 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage Auth
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once( '/var/simplesamlphp/lib/_autoload.php' );
require_once( __CA_LIB_DIR__ . '/Auth/BaseAuthAdapter.php' );
require_once( __CA_MODELS_DIR__ . '/ca_users.php' );

class ShibbolethAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	private $auth_config = null;

	# --------------------------------------------------------------------------------

	/**
	 *
	 */
	public function __construct() {
		$this->auth_config = Configuration::load( __CA_APP_DIR__ . "/conf/authentication.conf" );
		$shibSP            = $this->auth_config->get( 'shibboleth_service_provider' );
		try {
			$this->opo_shibAuth = new \SimpleSAML\Auth\Simple( $shibSP );
		} catch ( Exception $e ) {
			throw new ShibbolethException( "Could not create SimpleSAML auth object" );
		}
	}
	# --------------------------------------------------------------------------------

	/**
	 *
	 */
	public function authenticate( $username, $password = '', $options = null ) {
		try {
			$this->opo_shibAuth->requireAuth();
		} catch ( Exception $e ) {
			die( "Shibboleth error: {$e}" );
		}

		if ( ! $this->opo_shibAuth->isAuthenticated() ) {
			return false;
		}
		if ( ! ( $attrs = $this->opo_shibAuth->getAttributes() ) ) {
			return false;
		}


		$map = array_flip( $this->auth_config->get( 'shibboleth_field_map' ) );
		$uid = array_shift( $attrs[ $map['uid'] ] );
		if ( ! $uid ) {
			return false;
		}
		if ( ca_users::find( [ 'user_name' => $uid ], [ 'returnAs' => 'count' ] ) > 0 ) {
			return true;
		}

		return false;
	}
	# --------------------------------------------------------------------------------

	/**
	 *
	 */
	public function getUserInfo( $username, $password, $options = null ) {
		if ( ! $this->opo_shibAuth->isAuthenticated() ) {
			if ( ! $this->authenticate( $username, $password ) ) {
				throw new ShibbolethException( _t( "User could not be authenticated." ) );
			}
		}
		if ( $this->opo_shibAuth->isAuthenticated() ) {
			$default_roles  = $this->auth_config->get( 'shibboleth_users_default_roles' );
			$default_groups = $this->auth_config->get( 'shibboleth_users_default_groups' );

			$map = array_flip( $this->auth_config->get( 'shibboleth_field_map' ) );

			$attrs = $this->opo_shibAuth->getAttributes();

			if ( empty( $attrs[ $map['uid'] ][0] ) ) {
				throw new ShibbolethException( _t( "User id not set." ) );
			}
			if ( empty( $attrs[ $map['email'] ][0] ) ) {
				throw new ShibbolethException( _t( "User email address not set." ) );
			}

			return [
				'user_name' => $username ? $username : $attrs[ $map['uid'] ][0],
				'email'     => $attrs[ $map['email'] ][0],
				'fname'     => $attrs[ $map['fname'] ][0],
				'lname'     => $attrs[ $map['lname'] ][0] ? $attrs[ $map['lname'] ][0] : $attrs[ $map['email'] ][0],
				'active'    => 1,
				'roles'     => $default_roles,
				'groups'    => $default_groups
			];
		}
		throw new ShibbolethException( _t( "User could not be found." ) );
	}
	# --------------------------------------------------------------------------------

	/**
	 * @param string $username Username to create account for
	 * @param string $passowrd Ignored
	 */
	public function createUserAndGetPassword( $username, $password = null ) {
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		if ( function_exists( 'mcrypt_create_iv' ) ) {
			$password = base64_encode( mcrypt_create_iv( 32, MCRYPT_DEV_URANDOM ) );
		} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$password = base64_encode( openssl_random_pseudo_bytes( 32 ) );
		} else {
			throw new Exception( 'mcrypt or OpenSSL is required for CollectiveAccess Shibboleth support' );
		}

		return $password;
	}
	# --------------------------------------------------------------------------------

	/**
	 *
	 */
	public function supports( $feature ) {
		switch ( $feature ) {
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
	public function updatePassword( $username, $password ) {
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		return create_hash( $password );
	}
	# --------------------------------------------------------------------------------

	/**
	 *
	 */
	public function deleteUser( $username ) {
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
	public function deauthenticate( $options = null ) {
		setcookie( "SimpleSAML", "", time() - 3600, '/' );
		setcookie( "SimpleSAMLAuthToken", "", time() - 3600, '/' );
		setcookie( $this->auth_config->get( 'shibboleth_token_cookie' ), '', time() - 3600, __CA_URL_ROOT__ );

		return true;
	}
	# --------------------------------------------------------------------------------
}

class ShibbolethException extends Exception {
}
