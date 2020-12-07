<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/Adapters/HTTPHeader.php : Custom HTTP Headers auth
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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

require_once( __CA_LIB_DIR__ . '/Auth/BaseAuthAdapter.php' );
require_once( __CA_LIB_DIR__ . '/Auth/PasswordHash.php' );

class HTTPHeaderAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {
	# --------------------------------------------------------------------------------
	public function authenticate( $ps_username, $ps_password = '', $pa_options = null ) {
		if ( ! $ps_username ) {
			return false;
		}
		$o_log = new Eventlog();

		$o_auth_config          = Configuration::load( __CA_CONF_DIR__ . '/authentication.conf' );
		$vs_httpheader_username = $o_auth_config->get( "httpheader_username" );

		// if the HTTP header is missing or blank = bad authentication
		if ( ! isset( $_SERVER[ $vs_httpheader_username ] ) || $_SERVER[ $vs_httpheader_username ] == "" ) {

			$o_log->log( array(
				'CODE'    => 'LOGF',
				'SOURCE'  => 'HTTPHeaderAuthAdapter',
				'MESSAGE' => _t( 'Could not login user %1 using http headers are missing failed %2 [%3]', $ps_username,
					$vs_httpheader_username, $_SERVER['REMOTE_ADDR'] )
			) );

			return false;
		}

		// compare passed in ps_username and http header username.
		if ( $ps_username == $_SERVER[ $vs_httpheader_username ] ) {
			return true;
		}

		return false;
	}


	# --------------------------------------------------------------------------------
	public function createUserAndGetPassword( $ps_username, $ps_password ) {
		// We don't create users in HTTPHeader, we create users in getUserInfo below

		// We will create a password hash that is compatible with the CaUsers authentication adapter though
		// That way users could, in theory, turn off external db authentication later. The hash will not be used
		// for authentication in this adapter though.
		return create_hash( $ps_password );
	}


	# --------------------------------------------------------------------------------
	public function getUserInfo( $ps_username, $ps_password ) {

		$o_auth_config = Configuration::load( __CA_CONF_DIR__ . '/authentication.conf' );

		// learn which HTTP headers to inspect
		$vs_httpheader_username  = $o_auth_config->get( "httpheader_username" );
		$vs_httpheader_firstname = $o_auth_config->get( "httpheader_firstname" );
		$vs_httpheader_lastname  = $o_auth_config->get( "httpheader_lastname" );
		$vs_httpheader_email     = $o_auth_config->get( "httpheader_email" );

		// note: this will likely fail if one or more of these are missing for a user
		$va_return = array(
			'user_name' => $ps_username,
			'password'  => '',
			'fname'     => $_SERVER[ $vs_httpheader_firstname ],
			'lname'     => $_SERVER[ $vs_httpheader_lastname ],
			'email'     => $_SERVER[ $vs_httpheader_email ],
			'active'    => 1,
			'userclass' => 1,
		);

		return $va_return;
	}

	# --------------------------------------------------------------------------------
	public function supports( $pn_feature ) {
		switch ( $pn_feature ) {
			case __CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__:
				return true;
			case __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__:
			case __CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__:
			default:
				return false;
		}
	}

	# --------------------------------------------------------------------------------
	public function deleteUser( $ps_username ) {
		throw new AuthClassFeatureException( _t( "Authentication back-end doesn't support deleting users programmatically." ) );
	}

	public function updatePassword( $ps_username, $ps_password ) {
		throw new AuthClassFeatureException( _t( "Authentication back-end doesn't support updating users passwords." ) );
	}
	# --------------------------------------------------------------------------------
}

class HTTPHeaderException extends Exception {
}
